<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InviteGroupMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('addMember', $this->route('group'));
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Informe um e-mail válido.',
            'email.email' => 'Informe um e-mail válido.',
            'email.exists' => 'Nenhum usuário encontrado com este e-mail.',
        ];
    }
}
