<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradingResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'trading_account_id', 'created_by', 'traded_at', 'amount',
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

    public function account(): BelongsTo
    {
        return $this->belongsTo(TradingAccount::class, 'trading_account_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
