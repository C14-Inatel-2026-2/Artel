<?php

namespace App\Http\Requests;

use App\Models\Group;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreSettlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('group'));
    }

    public function rules(): array
    {
        return [
            'payer_id' => ['required', 'integer', 'exists:users,id', 'different:payee_id'],
            'payee_id' => ['required', 'integer', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'payer_id.required' => 'O pagador do acerto é obrigatório.',
            'payer_id.integer' => 'O ID do pagador deve ser um número inteiro.',
            'payer_id.exists' => 'O pagador informado não foi encontrado.',
            'payer_id.different' => 'O pagador e o recebedor devem ser usuários diferentes.',
            'payee_id.required' => 'O recebedor do acerto é obrigatório.',
            'payee_id.integer' => 'O ID do recebedor deve ser um número inteiro.',
            'payee_id.exists' => 'O recebedor informado não foi encontrado.',
            'amount.required' => 'O valor do acerto é obrigatório.',
            'amount.numeric' => 'O valor deve ser um número válido.',
            'amount.min' => 'O valor do acerto deve ser maior que zero.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $group = $this->route('group');

            if (! $group instanceof Group) {
                return;
            }

            if ($this->filled('payer_id') && ! $group->hasMember((int) $this->payer_id)) {
                $validator->errors()->add('payer_id', 'O pagador deve ser um participante do grupo.');
            }

            if ($this->filled('payee_id') && ! $group->hasMember((int) $this->payee_id)) {
                $validator->errors()->add('payee_id', 'O recebedor deve ser um participante do grupo.');
            }
        });
    }
}
