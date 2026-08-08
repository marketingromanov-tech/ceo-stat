<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\QuickResultEntry;
use App\Models\Robot;
use App\Models\TradingAccount;
use App\Models\TradingResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuickResultEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_global_action_and_opens_form(): void
    {
        $admin = $this->user(UserRole::Admin);
        $this->robot('Alpha');

        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertSeeLivewire(QuickResultEntry::class);
        Livewire::actingAs($admin)->test(QuickResultEntry::class)
            ->assertSet('isOpen', false)
            ->assertSee('Быстрая запись')
            ->call('open')
            ->assertSet('isOpen', true)
            ->assertSee('Робот')
            ->assertSee('Alpha');
    }

    public function test_operator_creates_result_and_dispatches_event(): void
    {
        $operator = $this->user(UserRole::Operator);
        [$robot, $account] = $this->robot('Operator robot');

        Livewire::actingAs($operator)->test(QuickResultEntry::class)
            ->call('open')
            ->set('robotId', $robot->id)
            ->set('resultDate', '2026-08-03')
            ->set('amount', '125.50')
            ->set('comment', 'Quick entry')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('isOpen', false)
            ->assertSet('robotId', null)
            ->assertSet('amount', '')
            ->assertDispatched('trading-result-saved', robotId: $robot->id, accountId: $account->id, date: '2026-08-03')
            ->assertSee('Результат сохранён.');

        $this->assertDatabaseHas(TradingResult::class, [
            'trading_account_id' => $account->id,
            'amount' => 125.50,
            'source' => 'manual',
            'comment' => 'Quick entry',
        ]);
    }

    public function test_robot_page_passes_context_and_open_preselects_robot_for_admin(): void
    {
        $admin = $this->user(UserRole::Admin);
        [$robot] = $this->robot('Context robot');

        $this->actingAs($admin)->get(route('robots.show', $robot))
            ->assertOk()
            ->assertSeeLivewire(QuickResultEntry::class);

        Livewire::actingAs($admin)->test(QuickResultEntry::class, ['contextRobotId' => $robot->id])
            ->assertSet('robotId', null)
            ->call('open')
            ->assertSet('isOpen', true)
            ->assertSet('robotId', $robot->id);
    }

    public function test_general_page_keeps_robot_unselected_for_operator(): void
    {
        $operator = $this->user(UserRole::Operator);
        $this->robot('General robot');

        Livewire::actingAs($operator)->test(QuickResultEntry::class)
            ->call('open')
            ->assertSet('isOpen', true)
            ->assertSet('robotId', null);
    }

    public function test_viewer_does_not_see_action_and_cannot_save_for_another_robot(): void
    {
        $viewer = $this->user(UserRole::Viewer);
        [$robot] = $this->robot('Hidden robot');

        $this->actingAs($viewer)->get(route('dashboard'))->assertOk()->assertDontSeeLivewire(QuickResultEntry::class);
        Livewire::actingAs($viewer)->test(QuickResultEntry::class)
            ->set('robotId', $robot->id)
            ->set('resultDate', '2026-08-03')
            ->set('amount', '10')
            ->call('save')
            ->assertForbidden();

        $this->assertDatabaseCount(TradingResult::class, 0);
    }

    public function test_amount_with_comma_is_saved(): void
    {
        $operator = $this->user(UserRole::Operator);
        [$robot, $account] = $this->robot('Comma robot');

        $this->save($operator, $robot, '-9,03');

        $this->assertEqualsWithDelta(-9.03, (float) TradingResult::query()->where('trading_account_id', $account->id)->value('amount'), 0.001);
    }

    public function test_negative_amount_is_saved(): void
    {
        $operator = $this->user(UserRole::Operator);
        [$robot, $account] = $this->robot('Negative robot');

        $this->save($operator, $robot, '-15.75');

        $this->assertEqualsWithDelta(-15.75, (float) TradingResult::query()->where('trading_account_id', $account->id)->value('amount'), 0.001);
    }

    public function test_zero_amount_is_saved(): void
    {
        $operator = $this->user(UserRole::Operator);
        [$robot, $account] = $this->robot('Zero robot');

        $this->save($operator, $robot, '0');

        $this->assertDatabaseHas(TradingResult::class, ['trading_account_id' => $account->id, 'amount' => 0]);
    }

    public function test_toggle_amount_sign_sets_minus_for_empty_value(): void
    {
        $operator = $this->user(UserRole::Operator);

        Livewire::actingAs($operator)->test(QuickResultEntry::class)
            ->assertSet('amount', '')
            ->call('toggleAmountSign')
            ->assertSet('amount', '-');
    }

    public function test_toggle_amount_sign_makes_comma_value_negative(): void
    {
        $operator = $this->user(UserRole::Operator);

        Livewire::actingAs($operator)->test(QuickResultEntry::class)
            ->set('amount', '9,03')
            ->call('toggleAmountSign')
            ->assertSet('amount', '-9,03');
    }

    public function test_toggle_amount_sign_makes_negative_value_positive(): void
    {
        $operator = $this->user(UserRole::Operator);

        Livewire::actingAs($operator)->test(QuickResultEntry::class)
            ->set('amount', '-9,03')
            ->call('toggleAmountSign')
            ->assertSet('amount', '9,03');
    }

    public function test_toggle_amount_sign_turns_zero_into_negative_zero(): void
    {
        $operator = $this->user(UserRole::Operator);

        Livewire::actingAs($operator)->test(QuickResultEntry::class)
            ->set('amount', '0')
            ->call('toggleAmountSign')
            ->assertSet('amount', '-0');
    }

    public function test_toggled_negative_value_is_saved_by_existing_flow(): void
    {
        $operator = $this->user(UserRole::Operator);
        [$robot, $account] = $this->robot('Toggled negative robot');

        Livewire::actingAs($operator)->test(QuickResultEntry::class)
            ->call('open')
            ->set('robotId', $robot->id)
            ->set('resultDate', '2026-08-03')
            ->set('amount', '9,03')
            ->call('toggleAmountSign')
            ->assertSet('amount', '-9,03')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEqualsWithDelta(-9.03, (float) TradingResult::query()->where('trading_account_id', $account->id)->value('amount'), 0.001);
    }

    public function test_robot_without_account_is_rejected(): void
    {
        $operator = $this->user(UserRole::Operator);
        $robot = Robot::query()->create(['name' => 'No account']);

        Livewire::actingAs($operator)->test(QuickResultEntry::class)
            ->call('open')
            ->set('robotId', $robot->id)
            ->set('resultDate', '2026-08-03')
            ->set('amount', '10')
            ->call('save')
            ->assertHasErrors(['robotId']);

        $this->assertDatabaseCount(TradingResult::class, 0);
    }

    private function user(UserRole $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    private function robot(string $name): array
    {
        $robot = Robot::query()->create(['name' => $name]);
        $account = TradingAccount::query()->create([
            'robot_id' => $robot->id,
            'name' => $name.' account',
            'platform' => 'manual',
            'currency' => 'USD',
            'initial_deposit' => 1000,
        ]);

        return [$robot, $account];
    }

    private function save(User $user, Robot $robot, string $amount): void
    {
        Livewire::actingAs($user)->test(QuickResultEntry::class)
            ->call('open')
            ->set('robotId', $robot->id)
            ->set('resultDate', '2026-08-03')
            ->set('amount', $amount)
            ->call('save')
            ->assertHasNoErrors();
    }
}
