<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddGroupMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required_without:user_id', 'nullable', 'email', 'exists:users,email'],
            'user_id' => ['required_without:email', 'nullable', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * Custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required_without' => 'Informe o e-mail ou o ID do usuário para adicionar ao grupo.',
            'email.email' => 'O e-mail informado não é válido.',
            'email.exists' => 'Nenhum usuário encontrado com este e-mail.',
            'user_id.exists' => 'Usuário não encontrado.',
        ];
    }
}
