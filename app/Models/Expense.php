<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expense extends Model
{
    protected $fillable = ['description', 'amount', 'paid_by', 'group_id'];

    /**
     * Usuário que pagou a despesa.
     */
    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    /**
     * Grupo da despesa.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Divisões da despesa.
     */
    public function splits(): HasMany
    {
        return $this->hasMany(ExpenseSplit::class);
    }
}
