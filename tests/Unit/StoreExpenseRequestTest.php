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

    public function test_store_expense_request_contains_split_rules(): void
    {
        $rules = (new StoreExpenseRequest)->rules();

        $this->assertArrayHasKey('split_type', $rules);
        $this->assertArrayHasKey('participants', $rules);
        $this->assertArrayHasKey('splits', $rules);
        $this->assertArrayHasKey('splits.*.user_id', $rules);
        $this->assertArrayHasKey('splits.*.amount', $rules);
        $this->assertArrayHasKey('splits.*.percentage', $rules);
        $this->assertContains('required_if:split_type,exact,percentage', $rules['splits']);
    }
}
