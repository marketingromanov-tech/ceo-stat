<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradingResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'trading_account_id', 'created_by', 'traded_at', 'sequence', 'amount',
        'source', 'external_id', 'comment', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'traded_at' => 'date',
            'amount' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (TradingResult $result): void {
            $robot = $result->account?->robot;

            if ($robot && $robot->tracking_started_at === null) {
                $robot->update(['tracking_started_at' => $result->traded_at]);
            }
        });
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(TradingAccount::class, 'trading_account_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeWithinTrackingPeriod(Builder $query): Builder
    {
        return $query->whereHas('account.robot', function (Builder $robotQuery): void {
            $robotQuery->where(function (Builder $periodQuery): void {
                $periodQuery
                    ->whereNull('robots.tracking_started_at')
                    ->orWhereColumn('trading_results.traded_at', '>=', 'robots.tracking_started_at');
            });
        });
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->role !== UserRole::Viewer) {
            return $query;
        }

        $robotIds = $user->robots()->pluck('robots.id');

        return $query->whereHas('account', fn (Builder $accountQuery) => $accountQuery->whereIn('robot_id', $robotIds));
    }
}
