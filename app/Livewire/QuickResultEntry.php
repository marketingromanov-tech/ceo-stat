<?php

namespace App\Livewire;

use App\Models\Robot;
use App\Services\ManualTradingResultService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Livewire\Component;

class QuickResultEntry extends Component
{
    public bool $isOpen = false;
    public ?int $contextRobotId = null;
    public ?int $robotId = null;
    public string $resultDate = '';
    public string $amount = '';
    public string $comment = '';

    public function mount(?int $contextRobotId = null): void
    {
        $this->contextRobotId = $contextRobotId;
        $this->resultDate = now()->format('Y-m-d');
    }

    public function open(): void
    {
        $this->authorizeEntry();
        $this->robotId = $this->contextRobotId;
        $this->show();
    }

    #[On('open-quick-result-entry')]
    public function openForRobot(int $robotId): void
    {
        $this->authorizeEntry();
        $this->robotId = $robotId;
        $this->show();
    }

    private function show(): void
    {
        $this->isOpen = true;
        $this->resultDate = $this->resultDate ?: now()->format('Y-m-d');
        $this->resetValidation();
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->resetForm();
        $this->resetValidation();
    }

    public function save(ManualTradingResultService $resultService): void
    {
        $this->authorizeEntry();
        $this->amount = str_replace(["\u{00A0}", ' ', ','], ['', '', '.'], trim($this->amount));

        $validated = $this->validate([
            'robotId' => ['required', 'integer'],
            'resultDate' => ['required', 'date', 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'between:-9999999999999999.99,9999999999999999.99'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ], [
            'robotId.required' => 'Выберите робота.',
            'resultDate.required' => 'Укажите дату.',
            'amount.required' => 'Укажите результат.',
            'amount.numeric' => 'Введите результат числом, например -9,03.',
        ]);

        $robot = $this->availableRobotsQuery()->with('account')->find($validated['robotId']);
        if (! $robot) {
            $this->addError('robotId', 'Выбранный робот недоступен.');
            return;
        }
        if (! $robot->account) {
            $this->addError('robotId', 'У робота не настроен торговый счёт.');
            return;
        }
        if ($robot->tracking_started_at && $validated['resultDate'] < $robot->tracking_started_at->format('Y-m-d')) {
            $this->addError('resultDate', 'Дата не может быть раньше начала учёта робота.');
            return;
        }

        $date = CarbonImmutable::parse($validated['resultDate']);
        $resultService->create(
            $robot->account,
            $date,
            (float) $validated['amount'],
            $validated['comment'] ?: null,
            auth()->user(),
        );

        $robotId = $robot->id;
        $accountId = $robot->account->id;
        $this->isOpen = false;
        $this->resetForm();
        session()->flash('quick-result-status', 'Результат сохранён.');
        $this->dispatch('trading-result-saved', robotId: $robotId, accountId: $accountId, date: $date->toDateString());
    }

    public function render(): View
    {
        return view('livewire.quick-result-entry', [
            'robots' => $this->availableRobotsQuery()->with('account')->orderBy('name')->get(),
        ]);
    }

    private function availableRobotsQuery(): Builder
    {
        $query = Robot::query();

        if (auth()->user()->role->value === 'viewer') {
            $query->whereHas('viewers', fn (Builder $viewerQuery) => $viewerQuery->whereKey(auth()->id()));
        }

        return $query;
    }

    private function authorizeEntry(): void
    {
        abort_unless(in_array(auth()->user()->role->value, ['admin', 'operator'], true), 403);
    }

    private function resetForm(): void
    {
        $this->robotId = null;
        $this->resultDate = now()->format('Y-m-d');
        $this->amount = '';
        $this->comment = '';
    }
}
