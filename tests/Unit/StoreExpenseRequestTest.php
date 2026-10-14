<?php

namespace Tests\Unit;

use App\Http\Requests\StoreExpenseRequest;
use Tests\TestCase;

class StoreExpenseRequestTest extends TestCase
{
    /**
     * Teste 1 (Sem Mock | Positivo): Regras de requisição de despesa obrigatórias e valores mínimos
     */
    public function test_store_expense_request_contains_required_rules(): void
    {
        // Arrange
        $request = new StoreExpenseRequest;

        // Act
        $rules = $request->rules();

        // Assert
        $this->assertArrayHasKey('description', $rules);
        $this->assertArrayHasKey('amount', $rules);
        $this->assertArrayHasKey('paid_by', $rules);
        $this->assertContains('required', $rules['amount']);
        $this->assertContains('min:0.01', $rules['amount']);
    }
}
