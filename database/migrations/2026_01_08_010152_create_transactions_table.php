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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('restrict');
            $table->foreignId('card_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['INCOME', 'EXPENSE']);
            $table->enum('payment_method', ['CASH', 'PIX', 'DEBIT', 'CREDIT'])->nullable();
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->date('transaction_date');
            $table->tinyInteger('installments_total')->default(1);
            $table->tinyInteger('installment_number')->default(1);
            $table->string('group_uuid', 36)->nullable()->comment('UUID to group installments');
            $table->boolean('is_paid')->default(false);
            $table->timestamps();

            $table->index('user_id');
            $table->index('category_id');
            $table->index('card_id');
            $table->index('type');
            $table->index('payment_method');
            $table->index('transaction_date');
            $table->index('group_uuid');
            $table->index('is_paid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
