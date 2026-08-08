<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Robot;
use App\Models\RobotStatusPeriod;
use App\Services\RobotStatisticsService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class RobotManager extends Component
{
    public ?int $editingId = null;
    public string $name = '';
    public string $description = '';
    public bool $isActive = true;
    public ?int $deletingId = null;
    public string $deletePassword = '';
    public string $statusFilter = 'all';
    public string $sortBy = 'name';
    public string $statisticsPeriod = 'all';

    public function selectStatusFilter(string $filter): void
    {
        abort_unless(in_array($filter, ['all', 'active', 'paused', 'stopped'], true), 422);
        $this->statusFilter = $filter;
    }

    public function selectSort(string $sort): void
    {
        abort_unless(in_array($sort, ['profit', 'return', 'name'], true), 422);
        $this->sortBy = $sort;
    }

    public function selectStatisticsPeriod(string $period): void
    {
        abort_unless(in_array($period, ['all', '7_days', '30_days', '3_months', '6_months', 'current_year'], true), 422);
        $this->statisticsPeriod = $period;
    }

    public function edit(int $id): void
    {
        abort_unless(in_array(auth()->user()->role->value, ['admin', 'operator'], true), 403);
        $robot = Robot::query()->findOrFail($id);
        $this->editingId = $robot->id;
        $this->name = $robot->name;
        $this->description = $robot->description ?? '';
        $this->isActive = $robot->is_active;
    }

    public function save(): void
    {
        abort_unless(in_array(auth()->user()->role->value, ['admin', 'operator'], true), 403);
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'isActive' => ['boolean'],
        ]);
        $robot = Robot::query()->findOrNew($this->editingId);
        $old = $robot->exists ? $robot->toArray() : null;
        $robot->fill(['name' => $data['name'], 'description' => $data['description'] ?: null, 'is_active' => $data['isActive']])->save();
        $this->audit($robot->wasRecentlyCreated ? 'robot.created' : 'robot.updated', $robot, $old);
        $this->cancel();
        session()->flash('status', 'Робот сохранён.');
    }

    public function toggle(int $id): void
    {
        abort_unless(in_array(auth()->user()->role->value, ['admin', 'operator'], true), 403);
        $robot = Robot::query()->findOrFail($id);
        $old = $robot->toArray();
        $robot->update(['is_active' => ! $robot->is_active]);
        $this->audit('robot.status_changed', $robot, $old);
    }

    public function confirmDelete(int $id): void
    {
        abort_unless(auth()->user()->role->value === 'admin', 403);

        Robot::query()->findOrFail($id);
        $this->deletingId = $id;
        $this->deletePassword = '';
        $this->resetValidation('deletePassword');
    }

    public function cancelDelete(): void
    {
        $this->reset('deletingId', 'deletePassword');
        $this->resetValidation('deletePassword');
    }

    public function delete(): void
    {
        abort_unless(auth()->user()->role->value === 'admin', 403);

        $this->validate([
            'deletingId' => ['required', 'integer', 'exists:robots,id'],
            'deletePassword' => ['required', 'current_password'],
        ], [
            'deletePassword.required' => 'Введите пароль.',
            'deletePassword.current_password' => 'Неверный пароль.',
        ]);

        $robot = Robot::query()->with('account')->findOrFail($this->deletingId);
        $old = $robot->toArray();
        $old['account'] = $robot->account?->toArray();
        $old['results_count'] = $robot->account?->results()->count() ?? 0;

        $this->audit('robot.deleted', $robot, $old);
        $robot->delete();

        if ($this->editingId === $robot->id) {
            $this->cancel();
        }

        $this->cancelDelete();
        session()->flash('status', 'Робот, его счёт и вся история удалены.');
    }

    public function cancel(): void
    {
        $this->reset('editingId', 'name', 'description');
        $this->isActive = true;
        $this->resetValidation();
    }

    private function audit(string $action, Robot $robot, ?array $old): void
    {
        AuditLog::query()->create(['user_id' => auth()->id(), 'action' => $action, 'auditable_type' => Robot::class, 'auditable_id' => $robot->id, 'old_values' => $old, 'new_values' => $robot->toArray(), 'ip_address' => request()->ip(), 'user_agent' => request()->userAgent()]);
    }

    public function render(RobotStatisticsService $statisticsService): View
    {
        $robots = Robot::query()->with(['account', 'statusPeriods' => fn ($query) => $query->latest('starts_at')->latest('id')])
            ->when(auth()->user()->role->value === 'viewer', fn ($query) => $query->whereHas('viewers', fn ($viewerQuery) => $viewerQuery->whereKey(auth()->id())))
            ->get();

        [$from, $to] = $this->statisticsRange();
        $cards = $robots->map(function (Robot $robot) use ($statisticsService, $from, $to): array {
            $currentPeriod = $robot->statusPeriods->first(
                fn (RobotStatusPeriod $period) => $period->starts_at->lte(today()) && (! $period->ends_at || $period->ends_at->gte(today()))
            );
            $status = match ($currentPeriod?->status) {
                RobotStatusPeriod::STATUS_PAUSED => 'paused',
                RobotStatusPeriod::STATUS_DIAGNOSTICS, RobotStatusPeriod::STATUS_MAINTENANCE => 'stopped',
                default => 'active',
            };
            $statistics = $statisticsService->calculate($robot, $from, $to);
            $lastResult = $robot->account?->results()->withinTrackingPeriod()->latest('traded_at')->latest('sequence')->first();

            return [
                'robot' => $robot,
                'status' => $status,
                'profit' => $statistics['kpi']['profit'],
                'return_percent' => $statistics['kpi']['return_percent'],
                'last_result' => $lastResult,
            ];
        })->when($this->statusFilter !== 'all', fn ($cards) => $cards->where('status', $this->statusFilter));

        $cards = (match ($this->sortBy) {
            'profit' => $cards->sortByDesc('profit'),
            'return' => $cards->sortByDesc('return_percent'),
            default => $cards->sortBy(fn (array $card) => mb_strtolower($card['robot']->name)),
        })->values();

        return view('livewire.robot-manager', [
            'robots' => $robots,
            'cards' => $cards,
            'statisticsPeriodLabel' => match ($this->statisticsPeriod) {
                '7_days' => '7 дней',
                '30_days' => '30 дней',
                '3_months' => '3 месяца',
                '6_months' => '6 месяцев',
                'current_year' => 'текущий год',
                default => 'всё время',
            },
        ])->layout('components.layouts.app', ['title' => 'Роботы — CEO Stat']);
    }

    private function statisticsRange(): array
    {
        $today = CarbonImmutable::today();

        return match ($this->statisticsPeriod) {
            '7_days' => [$today->subDays(6), $today],
            '30_days' => [$today->subDays(29), $today],
            '3_months' => [$today->subMonths(3), $today],
            '6_months' => [$today->subMonths(6), $today],
            'current_year' => [$today->startOfYear(), $today],
            default => [null, null],
        };
    }
}
