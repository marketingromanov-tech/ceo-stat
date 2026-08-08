<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Robot;
use App\Models\TradingAccount;
use App\Models\TradingResult;
use App\Models\User;
use App\Services\ManualTradingResultService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualTradingResultServiceTest extends TestCase
{
    use RefreshDatabase;

    private TradingAccount $account;
    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->operator = User::factory()->create(['role' => UserRole::Operator, 'is_active' => true]);
        $robot = Robot::query()->create(['name' => 'Manual robot']);
        $this->account = TradingAccount::query()->create([
            'robot_id' => $robot->id,
            'name' => 'Main',
            'platform' => 'manual',
            'currency' => 'USD',
            'initial_deposit' => 1000,
        ]);
    }

    public function test_creates_manual_trading_result_and_audit_log(): void
    {
        $result = $this->service()->create(
            $this->account,
            CarbonImmutable::parse('2026-08-03'),
            125.50,
            'Manual entry',
            $this->operator,
        );

        $this->assertSame('manual', $result->source);
        $this->assertSame(1, $result->sequence);
        $this->assertSame($this->operator->id, $result->created_by);
        $this->assertSame('Manual entry', $result->comment);
        $this->assertDatabaseHas(AuditLog::class, [
            'user_id' => $this->operator->id,
            'action' => 'result.saved',
            'auditable_type' => TradingResult::class,
            'auditable_id' => $result->id,
        ]);
    }

    public function test_sequence_increases_for_multiple_results_on_same_day(): void
    {
        $first = $this->create('2026-08-03', 10);
        $second = $this->create('2026-08-03', 20);
        $third = $this->create('2026-08-03', 30);
        $nextDay = $this->create('2026-08-04', 40);

        $this->assertSame([1, 2, 3], [$first->sequence, $second->sequence, $third->sequence]);
        $this->assertSame(1, $nextDay->sequence);
        $this->assertSame(4, TradingResult::query()->count());
        $this->assertSame(4, AuditLog::query()->where('action', 'result.saved')->count());
    }

    public function test_zero_amount_is_saved_as_a_working_result(): void
    {
        $result = $this->create('2026-08-03', 0);

        $this->assertSame(0.0, (float) $result->amount);
        $this->assertDatabaseHas(TradingResult::class, ['id' => $result->id, 'amount' => 0]);
    }

    public function test_negative_amount_is_saved(): void
    {
        $result = $this->create('2026-08-03', -9.03);

        $this->assertSame(-9.03, (float) $result->amount);
        $this->assertDatabaseHas(TradingResult::class, ['id' => $result->id, 'amount' => -9.03]);
    }

    private function create(string $date, float $amount): TradingResult
    {
        return $this->service()->create(
            $this->account,
            CarbonImmutable::parse($date),
            $amount,
            null,
            $this->operator,
        );
    }

    private function service(): ManualTradingResultService
    {
        return app(ManualTradingResultService::class);
    }
}
