<?php

namespace Tests\Unit;

use App\Http\Requests\StoreSettlementRequest;
use Tests\TestCase;

class StoreSettlementRequestTest extends TestCase
{
    public function test_store_settlement_request_contains_required_rules(): void
    {
        $request = new StoreSettlementRequest;

        $rules = $request->rules();

        $this->assertArrayHasKey('payer_id', $rules);
        $this->assertArrayHasKey('payee_id', $rules);
        $this->assertArrayHasKey('amount', $rules);
        $this->assertContains('required', $rules['payer_id']);
        $this->assertContains('different:payee_id', $rules['payer_id']);
        $this->assertContains('required', $rules['payee_id']);
        $this->assertContains('required', $rules['amount']);
        $this->assertContains('min:0.01', $rules['amount']);
    }
}
