<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialOperation extends Model
{
    use HasFactory;

    public const TYPE_DEPOSIT = 'deposit';
    public const TYPE_WITHDRAWAL = 'withdrawal';
    public const TYPE_COMMISSION = 'commission';
    public const TYPE_EXPENSE = 'expense';
    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'trading_account_id',
        'type',
        'amount',
        'currency',
        'operation_date',
        'source',
        'comment',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'operation_date' => 'date',
        ];
    }

    public static function types(): array
    {
        return [
            self::TYPE_DEPOSIT,
            self::TYPE_WITHDRAWAL,
            self::TYPE_COMMISSION,
            self::TYPE_EXPENSE,
            self::TYPE_ADJUSTMENT,
        ];
    }

    public function tradingAccount(): BelongsTo
    {
        return $this->belongsTo(TradingAccount::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
