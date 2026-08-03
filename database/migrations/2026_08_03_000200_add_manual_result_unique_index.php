<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trading_results', function (Blueprint $table): void {
            $table->unique(['trading_account_id', 'traded_at', 'source'], 'trading_results_daily_unique');
        });
    }

    public function down(): void
    {
        Schema::table('trading_results', function (Blueprint $table): void {
            $table->dropUnique('trading_results_daily_unique');
        });
    }
};
