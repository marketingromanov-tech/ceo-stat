<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Robot extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'tracking_started_at', 'is_active'];

    protected function casts(): array
    {
        return ['tracking_started_at' => 'date', 'is_active' => 'boolean'];
    }

    public function account(): HasOne
    {
        return $this->hasOne(TradingAccount::class);
    }
}
