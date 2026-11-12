<?php

namespace Tests\Unit;

use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class LoginRequestTest extends TestCase
{
    public function test_login_validation_fails_without_credentials(): void
    {
        $request = new LoginRequest;

        $validator = Validator::make([], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertSame('O e-mail é obrigatório.', $validator->errors()->first('email'));
        $this->assertSame('A senha é obrigatória.', $validator->errors()->first('password'));
    }

    public function test_login_validation_fails_with_invalid_email(): void
    {
        $request = new LoginRequest;

        $validator = Validator::make(
            ['email' => 'nao-e-email', 'password' => 'senha1234'],
            $request->rules(),
            $request->messages(),
        );

        $this->assertTrue($validator->fails());
        $this->assertSame('O e-mail informado não é válido.', $validator->errors()->first('email'));
    }

    public function test_credentials_returns_only_email_and_password(): void
    {
        $request = LoginRequest::create('/', 'POST', [
            'email' => 'maria@example.com',
            'password' => 'senha1234',
            'device_name' => 'celular',
        ]);

        $this->assertSame(
            ['email' => 'maria@example.com', 'password' => 'senha1234'],
            $request->credentials(),
        );
    }
}
