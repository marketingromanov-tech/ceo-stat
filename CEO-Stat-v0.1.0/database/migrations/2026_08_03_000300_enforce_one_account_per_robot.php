<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trading_accounts', function (Blueprint $table): void {
            $table->unique('robot_id', 'trading_accounts_robot_unique');
        });
    }

    public function down(): void
    {
        Schema::table('trading_accounts', function (Blueprint $table): void {
            $table->dropUnique('trading_accounts_robot_unique');
        });
    }
};
