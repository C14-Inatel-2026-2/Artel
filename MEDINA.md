# Testes Unitários - Medina (Pessoa 2)

## 📌 Escopo e Responsabilidades
* **Módulo:** Adição de Membros e Consulta de Saldos
* **Classes Alvo:**
  * `App\Http\Requests\AddGroupMemberRequest`
  * `App\Http\Controllers\GroupBalanceController`
  * `App\Services\ExpenseService` (via Mock)
* **Metas cumpridas:** 4 testes unitários (2 sem mock + 2 com mock, com caso negativo incluído).
* **Arquivos a criar:**
  1. `tests/Unit/AddGroupMemberRequestTest.php`
  2. `tests/Unit/GroupBalanceControllerTest.php`

---

## 📋 Detalhamento dos 4 Testes

| Teste | Tipo | Classificação | Método Alvo | Objetivo do Teste |
| :--- | :--- | :--- | :--- | :--- |
| **1** | Sem Mock | Positivo | `AddGroupMemberRequest::rules()` | Valida presença dos identificadores alternativos (`email` e `user_id` com `required_without`). |
| **2** | Sem Mock | Negativo | `AddGroupMemberRequest::rules()` | Valida que a requisição é rejeitada (`fails() === true`) quando nenhum identificador é enviado no payload. |
| **3** | Com Mock | Positivo | `GroupBalanceController::index()` | Mocka `ExpenseService::calculateBalance()` retornando saldos com sucesso e garantindo retorno HTTP 200 com os dados. |
| **4** | Com Mock | Negativo | `GroupBalanceController::index()` | Mocka `ExpenseService::calculateBalance()` simulando falha de cálculo (lança exceção) e garante que o erro é disparado. |

---

## 💻 Código para Implementação

### 1. Criar o arquivo `tests/Unit/AddGroupMemberRequestTest.php`:
```php
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
```

---

### 2. Criar o arquivo `tests/Unit/GroupBalanceControllerTest.php`:
```php
<?php

namespace Tests\Unit;

use App\Http\Controllers\GroupBalanceController;
use App\Models\Group;
use App\Services\ExpenseService;
use Tests\TestCase;

class GroupBalanceControllerTest extends TestCase
{
    /**
     * Teste 3 (Com Mock | Positivo): Consulta de balanço com sucesso retornando status 200
     */
    public function test_balance_index_returns_balance_data_successfully(): void
    {
        // Arrange
        $mockService = $this->createMock(ExpenseService::class);
        $mockService->expects($this->once())
            ->method('calculateBalance')
            ->willReturn([
                'group_id' => 1,
                'group_name' => 'Churrasco de Sexta',
                'balances' => [],
            ]);

        $controller = new GroupBalanceController($mockService);
        $group = new Group();

        // Act
        $response = $controller->index($group);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Churrasco de Sexta', $response->getData(true)['group_name']);
    }

    /**
     * Teste 4 (Com Mock | Negativo): Falha ao calcular balanço quando a service lança exceção
     */
    public function test_balance_index_fails_when_service_throws_exception(): void
    {
        // Arrange
        $mockService = $this->createMock(ExpenseService::class);
        $mockService->expects($this->once())
            ->method('calculateBalance')
            ->willThrowException(new \InvalidArgumentException('Grupo sem dados financeiros'));

        $controller = new GroupBalanceController($mockService);
        $group = new Group();

        // Assert & Act
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Grupo sem dados financeiros');

        $controller->index($group);
    }
}
```

---

## 🚀 Como Executar os Seus Testes
```bash
# Rodar apenas os seus arquivos de teste
php artisan test tests/Unit/AddGroupMemberRequestTest.php
php artisan test tests/Unit/GroupBalanceControllerTest.php
```
