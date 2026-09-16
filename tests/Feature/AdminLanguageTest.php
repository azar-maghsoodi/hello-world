<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLanguageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_an_admin_can_add_a_language(): void
    {
        $this->actingAs($this->admin)->post('/admin/languages', [
            'code' => 'nl',
            'name' => 'Dutch',
            'native_name' => 'Nederlands',
            'is_active' => true,
        ])->assertRedirect('/admin/languages');

        $this->assertDatabaseHas('languages', ['code' => 'nl', 'name' => 'Dutch']);
    }

    public function test_language_codes_must_be_unique(): void
    {
        Language::factory()->create(['code' => 'fr']);

        $response = $this->actingAs($this->admin)->post('/admin/languages', [
            'code' => 'fr',
            'name' => 'French (dup)',
            'native_name' => 'Français',
            'is_active' => true,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_setting_a_language_as_default_unsets_the_previous_default(): void
    {
        $english = Language::factory()->create(['code' => 'en', 'is_default' => true, 'is_active' => true]);
        $french = Language::factory()->create(['code' => 'fr', 'is_default' => false, 'is_active' => true]);

        $this->actingAs($this->admin)->put("/admin/languages/{$french->id}", [
            'code' => $french->code,
            'name' => $french->name,
            'native_name' => $french->native_name,
            'is_active' => true,
            'is_default' => true,
        ])->assertRedirect('/admin/languages');

        $this->assertTrue($french->fresh()->is_default);
        $this->assertFalse($english->fresh()->is_default);
    }

    public function test_the_default_language_cannot_be_deleted(): void
    {
        $english = Language::factory()->create(['is_default' => true]);

        $response = $this->actingAs($this->admin)->delete("/admin/languages/{$english->id}");

        $response->assertSessionHasErrors('is_default');
        $this->assertDatabaseHas('languages', ['id' => $english->id]);
    }

    public function test_a_non_default_language_can_be_deleted(): void
    {
        $french = Language::factory()->create(['is_default' => false]);

        $this->actingAs($this->admin)
            ->delete("/admin/languages/{$french->id}")
            ->assertRedirect('/admin/languages');

        $this->assertDatabaseMissing('languages', ['id' => $french->id]);
    }

    public function test_customers_cannot_manage_languages(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->actingAs($customer)->get('/admin/languages')->assertForbidden();
    }
}
