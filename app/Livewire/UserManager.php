<?php

namespace App\Livewire;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Robot;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class UserManager extends Component
{
    public ?int $editingId = null;
    public string $name = '';
    public string $email = '';
    public string $role = 'viewer';
    public string $password = '';
    public bool $isActive = true;
    public array $robotIds = [];
    public ?int $deletingId = null;
    public string $deletePassword = '';

    public function edit(int $id): void
    {
        $this->authorizeAdmin();
        $user = User::query()->with('robots')->findOrFail($id);
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role->value;
        $this->password = '';
        $this->isActive = $user->is_active;
        $this->robotIds = $user->robots->modelKeys();
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->authorizeAdmin();
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'role' => ['required', Rule::in(array_map(fn (UserRole $role) => $role->value, UserRole::cases()))],
            'password' => [$this->editingId ? 'nullable' : 'required', 'string', 'min:8'],
            'isActive' => ['boolean'],
            'robotIds' => ['array'],
            'robotIds.*' => ['integer', 'exists:robots,id'],
        ], [
            'password.required' => 'Задайте пароль.',
            'password.min' => 'Пароль должен содержать не менее 8 символов.',
        ]);

        $user = User::query()->findOrNew($this->editingId);
        $old = $user->exists ? $user->load('robots')->toArray() : null;
        $attributes = [
            'name' => $data['name'], 'email' => strtolower($data['email']),
            'role' => $data['role'], 'is_active' => $data['isActive'],
        ];
        if ($data['password'] !== '') {
            $attributes['password'] = $data['password'];
        }
        $user->fill($attributes)->save();
        $user->robots()->sync($data['role'] === UserRole::Viewer->value ? $data['robotIds'] : []);
        $this->audit($user->wasRecentlyCreated ? 'user.created' : 'user.updated', $user, $old);
        $this->cancel();
        session()->flash('status', 'Пользователь сохранён.');
    }

    public function confirmDelete(int $id): void
    {
        $this->authorizeAdmin();
        abort_if($id === auth()->id(), 422, 'Нельзя удалить свою учётную запись.');
        User::query()->findOrFail($id);
        $this->deletingId = $id;
        $this->deletePassword = '';
        $this->resetValidation('deletePassword');
    }

    public function delete(): void
    {
        $this->authorizeAdmin();
        $this->validate([
            'deletingId' => ['required', 'integer', 'exists:users,id', Rule::notIn([auth()->id()])],
            'deletePassword' => ['required', 'current_password'],
        ], [
            'deletePassword.required' => 'Введите пароль.',
            'deletePassword.current_password' => 'Неверный пароль.',
        ]);
        $user = User::query()->with('robots')->findOrFail($this->deletingId);
        $old = $user->toArray();
        $this->audit('user.deleted', $user, $old);
        $user->delete();
        $this->cancelDelete();
        session()->flash('status', 'Пользователь удалён.');
    }

    public function cancel(): void
    {
        $this->reset('editingId', 'name', 'email', 'password', 'robotIds');
        $this->role = UserRole::Viewer->value;
        $this->isActive = true;
        $this->resetValidation();
    }

    public function cancelDelete(): void
    {
        $this->reset('deletingId', 'deletePassword');
        $this->resetValidation('deletePassword');
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->role === UserRole::Admin, 403);
    }

    private function audit(string $action, User $user, ?array $old): void
    {
        AuditLog::query()->create([
            'user_id' => auth()->id(), 'action' => $action,
            'auditable_type' => User::class, 'auditable_id' => $user->id,
            'old_values' => $old, 'new_values' => $action === 'user.deleted' ? null : $user->load('robots')->toArray(),
            'ip_address' => request()->ip(), 'user_agent' => request()->userAgent(),
        ]);
    }

    public function render(): View
    {
        return view('livewire.user-manager', [
            'users' => User::query()->with('robots')->latest()->get(),
            'robots' => Robot::query()->orderBy('name')->get(),
            'deletingUser' => $this->deletingId ? User::query()->find($this->deletingId) : null,
        ])->layout('components.layouts.app', ['title' => 'Пользователи — CEO Stat']);
    }
}
