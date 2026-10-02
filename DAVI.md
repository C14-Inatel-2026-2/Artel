# Testes Unitários - Davi (Pessoa 3)

## 📌 Escopo e Responsabilidades
* **Módulo:** Regras de Domínio e Criação de Despesas
* **Classes Alvo:**
  * `App\Http\Requests\StoreExpenseRequest`
  * `App\Services\ExpenseService`
  * `App\Http\Controllers\ExpenseController`
* **Metas cumpridas:** 4 testes unitários (2 sem mock + 2 com mock, com caso negativo incluído).
* **Arquivos a criar:**
  1. `tests/Unit/ExpenseServiceTest.php`
  2. `tests/Unit/ExpenseControllerTest.php`

---

## 📋 Detalhamento dos 4 Testes

| Teste | Tipo | Classificação | Método Alvo | Objetivo do Teste |
| :--- | :--- | :--- | :--- | :--- |
| **1** | Sem Mock | Positivo | `StoreExpenseRequest::rules()` | Valida presença de regras obrigatórias (`description`, `amount` com `min:0.01`, e `paid_by`). |
| **2** | Sem Mock | Negativo | `ExpenseService::createExpense()` | Valida que lançará `InvalidArgumentException` se o grupo fornecido não possuir membros para divisão da despesa. |
| **3** | Com Mock | Positivo | `ExpenseController::store()` | Mocka `ExpenseService` e `StoreExpenseRequest`, validando que o controller retorna HTTP 201 Created. |
| **4** | Com Mock | Negativo | `ExpenseController::store()` | Mocka `ExpenseService` lançando `InvalidArgumentException` e verifica se o controller captura o erro e retorna HTTP 422 Unprocessable Entity. |

---

## 💻 Código para Implementação

### 1. Criar o arquivo `tests/Unit/ExpenseServiceTest.php`:
```php
<?php

namespace Tests\Unit;

use App\Http\Requests\StoreExpenseRequest;
use App\Models\Group;
use App\Services\ExpenseService;
use InvalidArgumentException;
use Tests\TestCase;

class ExpenseServiceTest extends TestCase
{
    /**
     * Teste 1 (Sem Mock | Positivo): Regras de requisição de despesa obrigatórias e valores mínimos
     */
    public function test_store_expense_request_contains_required_rules(): void
    {
        // Arrange
        $request = new StoreExpenseRequest();

        // Act
        $rules = $request->rules();

        // Assert
        $this->assertArrayHasKey('description', $rules);
        $this->assertArrayHasKey('amount', $rules);
        $this->assertArrayHasKey('paid_by', $rules);
        $this->assertContains('required', $rules['amount']);
        $this->assertContains('min:0.01', $rules['amount']);
    }

    /**
     * Teste 2 (Sem Mock | Negativo): Criação de despesa em grupo sem membros lança InvalidArgumentException
     */
    public function test_create_expense_fails_when_group_has_no_members(): void
    {
        // Arrange
        $service = new ExpenseService();
        $group = new Group();
        $group->setRelation('members', collect([])); // Coleção vazia de membros em memória

        // Assert & Act
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('O grupo não possui participantes para divisão da despesa.');

        $service->createExpense($group, [
            'description' => 'Conta de Luz',
            'amount' => 150.00,
            'paid_by' => 1,
        ]);
    }
}
```

---

### 2. Criar o arquivo `tests/Unit/ExpenseControllerTest.php`:
```php
<?php

namespace Tests\Unit;

use App\Http\Controllers\ExpenseController;
use App\Http\Requests\StoreExpenseRequest;
use App\Models\Expense;
use App\Models\Group;
use App\Services\ExpenseService;
use InvalidArgumentException;
use Tests\TestCase;

class ExpenseControllerTest extends TestCase
{
    /**
     * Teste 3 (Com Mock | Positivo): Criação de despesa com sucesso retorna HTTP 201
     */
    public function test_store_creates_expense_and_returns_status_201(): void
    {
        // Arrange
        $mockService = $this->createMock(ExpenseService::class);
        $expense = new Expense();
        $mockService->expects($this->once())
            ->method('createExpense')
            ->willReturn($expense);

        $mockRequest = $this->createMock(StoreExpenseRequest::class);
        $mockRequest->method('validated')
            ->willReturn(['description' => 'Mercado', 'amount' => 80.0, 'paid_by' => 1]);

        $controller = new ExpenseController($mockService);
        $group = new Group();

        // Act
        $response = $controller->store($mockRequest, $group);

        // Assert
        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * Teste 4 (Com Mock | Negativo): Captura erro de negócio da service e retorna HTTP 422
     */
    public function test_store_catches_invalid_argument_exception_and_returns_422(): void
    {
        // Arrange
        $mockService = $this->createMock(ExpenseService::class);
        $mockService->expects($this->once())
            ->method('createExpense')
            ->willThrowException(new InvalidArgumentException('Participante inválido para divisão.'));

        $mockRequest = $this->createMock(StoreExpenseRequest::class);
        $mockRequest->method('validated')->willReturn([]);

        $controller = new ExpenseController($mockService);
        $group = new Group();

        // Act
        $response = $controller->store($mockRequest, $group);

        // Assert
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('Participante inválido para divisão.', $response->getData(true)['message']);
    }
}
```

---

## 🚀 Como Executar os Seus Testes
```bash
# Rodar apenas os seus arquivos de teste
php artisan test tests/Unit/ExpenseServiceTest.php
php artisan test tests/Unit/ExpenseControllerTest.php
```
