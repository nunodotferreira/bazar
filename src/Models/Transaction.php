<?php

namespace Bazar\Models;

use Bazar\Support\Facades\Gateway;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Throwable;

class Transaction extends Model
{
    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'url',
        'driver_name',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'completed_at' => 'datetime',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key',
        'type',
        'amount',
        'driver',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'bazar_transactions';

    /**
     * Get the order for the transaction.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the name of the gateway driver.
     *
     * @return string
     */
    public function getDriverNameAttribute(): string
    {
        try {
            return Gateway::driver($this->driver)->name();
        } catch (Throwable $exception) {
            return $this->driver;
        }
    }

    /**
     * Get the URL of the transaction.
     *
     * @return string|null
     */
    public function getUrlAttribute(): ?string
    {
        try {
            return Gateway::driver($this->driver)->transactionUrl($this);
        } catch (Throwable $exception) {
            return null;
        }
    }

    /**
     * Determine if the payment is completed.
     *
     * @return bool
     */
    public function completed(): bool
    {
        return ! is_null($this->completed_at);
    }

    /**
     * Determine if the payment is pending.
     *
     * @return bool
     */
    public function pending(): bool
    {
        return ! $this->completed();
    }

    /**
     * Mark the transaction as completed.
     *
     * @param \DateTimeInterface|null  $date
     * @return $this
     */
    public function markAsCompleted(DateTimeInterface $date = null): Transaction
    {
        $date = $date ?: Carbon::now();

        if (is_null($this->completed_at) || $this->completed_at->notEqualTo($date)) {
            $this->forceFill(['completed_at' => $date])->save();
        }

        return $this;
    }

    /**
     * Mark the transaction as pending.
     *
     * @return $this
     */
    public function markAsPending(): Transaction
    {
        if (! is_null($this->completed_at)) {
            $this->forceFill(['completed_at' => null])->save();
        }

        return $this;
    }

    /**
     * Set the driver.
     *
     * @param  string  $driver
     * @return $this
     */
    public function driver(string $driver): Transaction
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * Scope the query to only include payments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePayment(Builder $query): Builder
    {
        return $query->where('type', 'payment');
    }

    /**
     * Scope the query to only include refunds.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRefund(Builder $query): Builder
    {
        return $query->where('type', 'refund');
    }

    /**
     * Scope a query to only include completed transactions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereNotNull('completed_at');
    }

    /**
     * Scope a query to only include pending transactions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('completed_at');
    }
}
