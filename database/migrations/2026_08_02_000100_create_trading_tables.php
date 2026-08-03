<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('robots', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('trading_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('robot_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('broker')->nullable();
            $table->enum('platform', ['manual', 'mt4', 'mt5'])->default('manual');
            $table->string('external_login')->nullable()->index();
            $table->char('currency', 3)->default('USD');
            $table->decimal('initial_deposit', 18, 2)->default(0);
            $table->decimal('current_balance', 18, 2)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('trading_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('trading_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('traded_at')->index();
            $table->decimal('amount', 18, 2);
            $table->enum('source', ['manual', 'mt4', 'mt5'])->default('manual')->index();
            $table->string('external_id')->nullable();
            $table->text('comment')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['trading_account_id', 'source', 'external_id'], 'trading_results_external_unique');
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action')->index();
            $table->nullableMorphs('auditable');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('trading_results');
        Schema::dropIfExists('trading_accounts');
        Schema::dropIfExists('robots');
    }
};
