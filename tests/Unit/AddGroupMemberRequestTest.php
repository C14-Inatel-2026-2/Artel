<?php

namespace Tests\Unit;

use App\Http\Requests\AddGroupMemberRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AddGroupMemberRequestTest extends TestCase
{
    /**
     * Teste 1 (Sem Mock | Positivo): Regras contêm campos alternativos para adicionar membro
     */
    public function test_add_group_member_rules_contain_conditional_fields(): void
    {
        // Arrange
        $request = new AddGroupMemberRequest();

        // Act
        $rules = $request->rules();

        // Assert
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('user_id', $rules);
        $this->assertContains('required_without:user_id', $rules['email']);
        $this->assertContains('required_without:email', $rules['user_id']);
    }

    /**
     * Teste 2 (Sem Mock | Negativo): Falha quando nenhum identificador é enviado
     */
    public function test_add_group_member_fails_when_no_identifier_provided(): void
    {
        // Arrange
        $request = new AddGroupMemberRequest();
        $payload = []; // Nenhum dado enviado

        // Act
        $validator = Validator::make($payload, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('email') || $validator->errors()->has('user_id')
        );
    }
}
