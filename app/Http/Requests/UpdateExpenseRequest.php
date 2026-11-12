<?php

namespace App\Http\Requests;

use App\Models\Expense;
use App\Models\Group;

class UpdateExpenseRequest extends StoreExpenseRequest
{
    public function authorize(): bool
    {
        $group = $this->group();

        return $group !== null && (bool) $this->user()?->can('view', $group);
    }

    protected function group(): ?Group
    {
        $expense = $this->route('expense');

        return $expense instanceof Expense ? $expense->group : null;
    }
}
