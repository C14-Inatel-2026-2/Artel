# Testes Unitários - Johncy (Pessoa 1)

## 📌 Escopo e Responsabilidades
* **Módulo:** Gestão e Validação de Grupos
* **Classes Alvo:**
  * `App\Http\Requests\StoreGroupRequest`
  * `App\Http\Controllers\GroupController`
* **Metas cumpridas:** 4 testes unitários (2 sem mock + 2 com mock, com caso negativo incluído).
* **Arquivos a criar:**
  1. `tests/Unit/StoreGroupRequestTest.php`
  2. `tests/Unit/GroupControllerTest.php`

---

## 📋 Detalhamento dos 4 Testes

| Teste | Tipo | Classificação | Método Alvo | Objetivo do Teste |
| :--- | :--- | :--- | :--- | :--- |
| **1** | Sem Mock | Positivo | `StoreGroupRequest::rules()` | Valida presença das regras obrigatórias para o campo `name` (`required`, `min:3`, `max:255`). |
| **2** | Sem Mock | Negativo | `StoreGroupRequest::rules()` | Valida que a validação falha (`fails() === true`) quando o nome informado tem menos de 3 caracteres. |
| **3** | Com Mock | Positivo | `GroupController::destroy()` | Mocka o model `Group`, verifica que o método `delete()` foi chamado 1 vez e retorna HTTP 200 com mensagem de sucesso. |
| **4** | Com Mock | Negativo | `GroupController::destroy()` | Mocka o model `Group` simulando falha no `delete()` (lança exceção) e garante que o erro seja propagado. |

---

## 💻 Código para Implementação

### 1. Criar o arquivo `tests/Unit/StoreGroupRequestTest.php`:
```php
<?php

namespace Tests\Unit;

use App\Http\Requests\StoreGroupRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreGroupRequestTest extends TestCase
{
    /**
     * Teste 1 (Sem Mock | Positivo): Valida se as regras obrigatórias do campo name estão presentes
     */
    public function test_store_group_request_has_expected_rules(): void
    {
        // Arrange
        $request = new StoreGroupRequest();

        // Act
        $rules = $request->rules();

        // Assert
        $this->assertArrayHasKey('name', $rules);
        $this->assertContains('required', $rules['name']);
        $this->assertContains('string', $rules['name']);
        $this->assertContains('min:3', $rules['name']);
        $this->assertContains('max:255', $rules['name']);
    }

    /**
     * Teste 2 (Sem Mock | Negativo): Validação falha quando o nome possui menos de 3 caracteres
     */
    public function test_store_group_validation_fails_when_name_is_too_short(): void
    {
        // Arrange
        $request = new StoreGroupRequest();
        $payload = ['name' => 'ab']; // Inválido: menor que 3 caracteres

        // Act
        $validator = Validator::make($payload, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }
}
```

---

### 2. Criar o arquivo `tests/Unit/GroupControllerTest.php`:
```php
<?php

namespace Tests\Unit;

use App\Http\Controllers\GroupController;
use App\Models\Group;
use Tests\TestCase;

class GroupControllerTest extends TestCase
{
    /**
     * Teste 3 (Com Mock | Positivo): Exclui grupo com sucesso chamando delete() no model e retorna 200
     */
    public function test_destroy_deletes_group_and_returns_success_response(): void
    {
        // Arrange
        $groupMock = $this->createMock(Group::class);
        $groupMock->expects($this->once())
            ->method('delete');

        $controller = new GroupController();

        // Act
        $response = $controller->destroy($groupMock);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['message' => 'Grupo excluído com sucesso.'], $response->getData(true));
    }

    /**
     * Teste 4 (Com Mock | Negativo): Propaga exceção caso ocorra erro ao deletar o grupo
     */
    public function test_destroy_propagates_exception_on_delete_failure(): void
    {
        // Arrange
        $groupMock = $this->createMock(Group::class);
        $groupMock->expects($this->once())
            ->method('delete')
            ->willThrowException(new \RuntimeException('Erro no banco de dados ao excluir grupo'));

        $controller = new GroupController();

        // Assert & Act
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Erro no banco de dados ao excluir grupo');

        $controller->destroy($groupMock);
    }
}
```

---

## 🚀 Como Executar os Seus Testes
```bash
# Rodar apenas os seus arquivos de teste
php artisan test tests/Unit/StoreGroupRequestTest.php
php artisan test tests/Unit/GroupControllerTest.php
```
