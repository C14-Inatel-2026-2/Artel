<?php

namespace Tests\Unit;

use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class RegisterRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_request_has_expected_rules(): void
    {
        $rules = (new RegisterRequest)->rules();

        $this->assertContains('required', $rules['name']);
        $this->assertContains('unique:users,email', $rules['email']);
        $this->assertContains('min:8', $rules['password']);
        $this->assertContains('confirmed', $rules['password']);
    }

    public function test_register_validation_passes_with_valid_payload(): void
    {
        $request = new RegisterRequest;

        $validator = Validator::make([
            'name' => 'Maria',
            'email' => 'maria@example.com',
            'password' => 'senha1234',
            'password_confirmation' => 'senha1234',
        ], $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    public function test_register_validation_fails_with_duplicated_email(): void
    {
        User::factory()->create(['email' => 'maria@example.com']);
        $request = new RegisterRequest;

        $validator = Validator::make([
            'name' => 'Maria',
            'email' => 'maria@example.com',
            'password' => 'senha1234',
            'password_confirmation' => 'senha1234',
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertSame('Este e-mail já está cadastrado.', $validator->errors()->first('email'));
    }

    public function test_register_validation_fails_when_password_confirmation_does_not_match(): void
    {
        $request = new RegisterRequest;

        $validator = Validator::make([
            'name' => 'Maria',
            'email' => 'maria@example.com',
            'password' => 'senha1234',
            'password_confirmation' => 'outra-senha',
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertSame('A confirmação de senha não confere.', $validator->errors()->first('password'));
    }
}
