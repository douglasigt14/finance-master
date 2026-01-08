<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('last_four', 4)->nullable();
            $table->decimal('credit_limit', 15, 2);
            $table->tinyInteger('closing_day')->comment('Day of month when invoice closes (1-31)');
            $table->tinyInteger('due_day')->comment('Day of month when invoice is due (1-31)');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
