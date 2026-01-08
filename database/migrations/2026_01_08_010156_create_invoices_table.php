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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('card_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('cycle_month')->comment('Month of the billing cycle (1-12)');
            $table->smallInteger('cycle_year')->comment('Year of the billing cycle');
            $table->date('closing_date')->comment('Calculated closing date');
            $table->date('due_date')->comment('Calculated due date');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->boolean('is_paid')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('card_id');
            $table->index(['cycle_month', 'cycle_year']);
            $table->index('closing_date');
            $table->index('is_paid');
            $table->unique(['card_id', 'cycle_month', 'cycle_year'], 'unique_card_cycle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
