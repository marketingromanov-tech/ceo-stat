<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_account_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3)->default('USD');
            $table->date('operation_date');
            $table->string('source', 32)->default('manual');
            $table->text('comment')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['trading_account_id', 'operation_date']);
            $table->index(['trading_account_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_operations');
    }
};
