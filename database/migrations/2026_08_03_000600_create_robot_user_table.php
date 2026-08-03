<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('robot_user', function (Blueprint $table): void {
            $table->foreignId('robot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['robot_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('robot_user');
    }
};
