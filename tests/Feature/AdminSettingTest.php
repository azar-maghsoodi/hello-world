<?php

namespace Tests\Feature;

use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_update_store_settings(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->put('/admin/settings', [
            'currency_code' => 'usd',
            'currency_symbol' => '$',
            'tax_rate' => 0.21,
            'shipping_cost' => 4.99,
        ])->assertRedirect('/admin/settings');

        $settings = StoreSetting::current();
        $this->assertSame('USD', $settings->currency_code);
        $this->assertSame('$', $settings->currency_symbol);
        $this->assertEquals(0.21, $settings->tax_rate);
        $this->assertEquals(4.99, $settings->shipping_cost);
    }

    public function test_customers_cannot_update_store_settings(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->actingAs($customer)->get('/admin/settings')->assertForbidden();
    }

    public function test_the_default_settings_are_eur(): void
    {
        $settings = StoreSetting::current();

        $this->assertSame('EUR', $settings->currency_code);
        $this->assertSame('€', $settings->currency_symbol);
    }
}
