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
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('debtor_id')->nullable()->after('card_id')->constrained()->onDelete('set null');
            $table->text('card_description')->nullable()->after('description');
            
            $table->index('debtor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['debtor_id']);
            $table->dropColumn(['debtor_id', 'card_description']);
        });
    }
};
