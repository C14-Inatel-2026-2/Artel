<?php

namespace App\Http\Requests;

use App\Models\Group;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'split_type' => ['nullable', 'string', Rule::in(['equal', 'exact', 'percentage'])],
            'participants' => ['nullable', 'array', 'min:1'],
            'participants.*' => ['integer', 'distinct', 'exists:users,id'],
            'splits' => ['required_if:split_type,exact,percentage', 'array', 'min:1'],
            'splits.*.user_id' => ['required', 'integer', 'distinct', 'exists:users,id'],
            'splits.*.amount' => ['required_if:split_type,exact', 'numeric', 'min:0.01'],
            'splits.*.percentage' => ['required_if:split_type,percentage', 'numeric', 'min:0.01', 'max:100'],
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
            'split_type.in' => 'O tipo de divisão deve ser equal, exact ou percentage.',
            'participants.array' => 'Os participantes devem ser uma lista.',
            'participants.min' => 'Informe pelo menos um participante.',
            'participants.*.integer' => 'O ID de cada participante deve ser um número inteiro.',
            'participants.*.distinct' => 'Há participantes repetidos.',
            'participants.*.exists' => 'Um dos participantes informados não foi encontrado.',
            'splits.required_if' => 'Informe as parcelas da divisão.',
            'splits.array' => 'As parcelas devem ser uma lista.',
            'splits.min' => 'Informe pelo menos uma parcela.',
            'splits.*.user_id.required' => 'O usuário de cada parcela é obrigatório.',
            'splits.*.user_id.integer' => 'O ID do usuário de cada parcela deve ser um número inteiro.',
            'splits.*.user_id.distinct' => 'Há usuários repetidos nas parcelas.',
            'splits.*.user_id.exists' => 'Um dos usuários das parcelas não foi encontrado.',
            'splits.*.amount.required_if' => 'O valor de cada parcela é obrigatório.',
            'splits.*.amount.numeric' => 'O valor de cada parcela deve ser um número válido.',
            'splits.*.amount.min' => 'O valor de cada parcela deve ser de pelo menos 0,01.',
            'splits.*.percentage.required_if' => 'O percentual de cada parcela é obrigatório.',
            'splits.*.percentage.numeric' => 'O percentual de cada parcela deve ser um número válido.',
            'splits.*.percentage.min' => 'O percentual de cada parcela deve ser de pelo menos 0,01.',
            'splits.*.percentage.max' => 'O percentual de cada parcela não pode ultrapassar 100.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $group = $this->group();

            if ($group === null) {
                return;
            }

            $memberIds = $group->members()->pluck('users.id')->all();

            if ($this->filled('paid_by') && ! in_array((int) $this->paid_by, $memberIds, true)) {
                $validator->errors()->add('paid_by', 'O pagador deve ser um participante do grupo.');
            }

            foreach ((array) $this->input('participants', []) as $index => $userId) {
                if (is_numeric($userId) && ! in_array((int) $userId, $memberIds, true)) {
                    $validator->errors()->add("participants.$index", 'O participante deve pertencer ao grupo.');
                }
            }

            foreach ((array) $this->input('splits', []) as $index => $split) {
                $userId = is_array($split) ? ($split['user_id'] ?? null) : null;

                if (is_numeric($userId) && ! in_array((int) $userId, $memberIds, true)) {
                    $validator->errors()->add("splits.$index.user_id", 'O usuário da parcela deve pertencer ao grupo.');
                }
            }
        });
    }

    protected function group(): ?Group
    {
        $group = $this->route('group');

        return $group instanceof Group ? $group : null;
    }
}
