<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Robot;
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

    public function render(): View
    {
        $robots = Robot::query()->with('account')
            ->when(auth()->user()->role->value === 'viewer', fn ($query) => $query->whereHas('viewers', fn ($viewerQuery) => $viewerQuery->whereKey(auth()->id())))
            ->latest()
            ->get();

        return view('livewire.robot-manager', ['robots' => $robots])->layout('components.layouts.app', ['title' => 'Роботы — CEO Stat']);
    }
}
