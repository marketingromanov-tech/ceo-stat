<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('robot_status_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('robot_id')->constrained()->cascadeOnDelete();
            $table->string('status', 32);
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->text('comment')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['robot_id', 'starts_at']);
            $table->index(['robot_id', 'ends_at']);
            $table->index(['robot_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('robot_status_periods');
    }
};
