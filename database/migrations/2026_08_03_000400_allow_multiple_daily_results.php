<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trading_results', function (Blueprint $table): void {
            $table->dropUnique('trading_results_daily_unique');
            $table->unsignedSmallInteger('sequence')->default(1)->after('traded_at');
            $table->unique(['trading_account_id', 'traded_at', 'source', 'sequence'], 'trading_results_sequence_unique');
        });
    }

    public function down(): void
    {
        Schema::table('trading_results', function (Blueprint $table): void {
            $table->dropUnique('trading_results_sequence_unique');
            $table->dropColumn('sequence');
            $table->unique(['trading_account_id', 'traded_at', 'source'], 'trading_results_daily_unique');
        });
    }
};
