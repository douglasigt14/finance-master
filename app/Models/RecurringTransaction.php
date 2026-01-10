<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringTransaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'card_id',
        'debtor_id',
        'type',
        'payment_method',
        'amount',
        'description',
        'card_description',
        'frequency',
        'day_of_month',
        'start_date',
        'end_date',
        'next_execution_date',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'next_execution_date' => 'date',
            'is_active' => 'boolean',
            'day_of_month' => 'integer',
        ];
    }

    /**
     * Get the user that owns the recurring transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category that owns the recurring transaction.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the card that owns the recurring transaction.
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    /**
     * Get the debtor that owns the recurring transaction.
     */
    public function debtor(): BelongsTo
    {
        return $this->belongsTo(Debtor::class);
    }

    /**
     * Scope a query to only include active recurring transactions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include recurring transactions ready to execute.
     */
    public function scopeReadyToExecute($query)
    {
        return $query->where('is_active', true)
            ->where('next_execution_date', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now()->toDateString());
            });
    }

    /**
     * Scope a query to include recurring transactions that should have generated their first transaction.
     * This includes transactions where start_date is in the past but next_execution_date is in the future.
     */
    public function scopePendingFirstExecution($query)
    {
        return $query->where('is_active', true)
            ->where('start_date', '<=', now()->toDateString())
            ->where('next_execution_date', '>', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now()->toDateString());
            });
    }
}
