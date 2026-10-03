<?php

namespace Tests\Unit;

use App\Http\Requests\StoreGroupRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;
class StoreGroupRequestTest extends TestCase
{
    public function test_store_group_request_has_expected_rules(): void
    {
        $request = new StoreGroupRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertContains('required', $rules['name']);
        $this->assertContains('string', $rules['name']);
        $this->assertContains('min:3', $rules['name']);
        $this->assertContains('max:255', $rules['name']);
    }
    public function test_store_group_validation_fails_when_name_is_too_short(): void
    {
        $request = new StoreGroupRequest();
        $payload = ['name' => 'ab'];

        $validator = Validator::make($payload, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }
}
