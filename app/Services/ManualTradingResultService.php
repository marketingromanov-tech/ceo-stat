<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\TradingAccount;
use App\Models\TradingResult;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ManualTradingResultService
{
    public function create(
        TradingAccount $account,
        CarbonImmutable $tradedAt,
        float $amount,
        ?string $comment,
        User $author,
    ): TradingResult {
        return DB::transaction(function () use ($account, $tradedAt, $amount, $comment, $author): TradingResult {
            $lockedAccount = TradingAccount::query()->lockForUpdate()->findOrFail($account->id);
            $sequence = ((int) $lockedAccount->results()
                ->whereDate('traded_at', $tradedAt)
                ->where('source', 'manual')
                ->max('sequence')) + 1;

            $result = $lockedAccount->results()->create([
                'created_by' => $author->id,
                'traded_at' => $tradedAt,
                'sequence' => $sequence,
                'amount' => $amount,
                'source' => 'manual',
                'comment' => $comment ?: null,
            ]);

            AuditLog::query()->create([
                'user_id' => $author->id,
                'action' => 'result.saved',
                'auditable_type' => TradingResult::class,
                'auditable_id' => $result->id,
                'old_values' => null,
                'new_values' => $result->getChanges(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $result;
        });
    }
}
