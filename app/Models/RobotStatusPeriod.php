<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RobotStatusPeriod extends Model
{
    use HasFactory;

    public const STATUS_WORKING = 'working';
    public const STATUS_DIAGNOSTICS = 'diagnostics';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_PAUSED = 'paused';

    protected $fillable = [
        'robot_id',
        'status',
        'starts_at',
        'ends_at',
        'comment',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    public static function nonWorkingStatuses(): array
    {
        return [self::STATUS_DIAGNOSTICS, self::STATUS_MAINTENANCE, self::STATUS_PAUSED];
    }

    public function robot(): BelongsTo
    {
        return $this->belongsTo(Robot::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
