<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseSplit extends Model
{
    protected $fillable = ['expense_id', 'user_id', 'amount'];

    /**
     * Despesa à qual esta divisão pertence.
     */
    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    /**
     * Usuário que deve este valor.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
