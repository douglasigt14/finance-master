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
        Schema::create('recurring_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('restrict');
            $table->foreignId('card_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('debtor_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['INCOME', 'EXPENSE']);
            $table->enum('payment_method', ['CASH', 'PIX', 'DEBIT', 'CREDIT'])->nullable();
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->string('card_description')->nullable();
            $table->enum('frequency', ['WEEKLY', 'MONTHLY', 'YEARLY']);
            $table->tinyInteger('day_of_month')->nullable()->comment('Day of month for MONTHLY frequency (1-31)');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_execution_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('user_id');
            $table->index('category_id');
            $table->index('card_id');
            $table->index('is_active');
            $table->index('next_execution_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_transactions');
    }
};
