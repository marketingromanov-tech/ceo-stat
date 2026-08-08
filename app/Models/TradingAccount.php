<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TradingAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'robot_id', 'name', 'broker', 'platform', 'external_login',
        'currency', 'initial_deposit', 'current_balance', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'initial_deposit' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function robot(): BelongsTo
    {
        return $this->belongsTo(Robot::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(TradingResult::class);
    }

    public function financialOperations(): HasMany
    {
        return $this->hasMany(FinancialOperation::class);
    }
}
