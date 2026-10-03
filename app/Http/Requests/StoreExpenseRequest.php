<?php

namespace App\Http\Requests;

use App\Models\Group;
use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'min:3', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_by' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'A descrição da despesa é obrigatória.',
            'description.min' => 'A descrição deve ter pelo menos 3 caracteres.',
            'description.max' => 'A descrição não pode ultrapassar 255 caracteres.',
            'amount.required' => 'O valor da despesa é obrigatório.',
            'amount.numeric' => 'O valor deve ser um número válido.',
            'amount.min' => 'O valor da despesa deve ser maior que zero.',
            'paid_by.required' => 'O responsável pelo pagamento é obrigatório.',
            'paid_by.integer' => 'O ID do pagador deve ser um número inteiro.',
            'paid_by.exists' => 'O usuário informado não foi encontrado.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $group = $this->route('group');

            if ($group instanceof Group && $this->filled('paid_by')) {
                if (! $group->members()->where('users.id', $this->paid_by)->exists()) {
                    $validator->errors()->add('paid_by', 'O pagador deve ser um participante do grupo.');
                }
            }
        });
    }
}
