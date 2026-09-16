<?php

namespace Tests\Feature;

use App\Models\ProductAttribute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductAttributeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_an_admin_can_create_an_attribute(): void
    {
        $this->actingAs($this->admin)->post('/admin/attributes', [
            'name' => 'Color',
            'value' => 'Red',
        ])->assertRedirect('/admin/attributes');

        $this->assertDatabaseHas('product_attributes', [
            'name' => 'Color',
            'value' => 'Red',
        ]);
    }

    public function test_the_same_name_and_value_pair_cannot_be_created_twice(): void
    {
        ProductAttribute::factory()->create(['name' => 'Color', 'value' => 'Red']);

        $response = $this->actingAs($this->admin)->post('/admin/attributes', [
            'name' => 'Color',
            'value' => 'Red',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_an_admin_can_delete_an_attribute(): void
    {
        $attribute = ProductAttribute::factory()->create();

        $this->actingAs($this->admin)
            ->delete("/admin/attributes/{$attribute->id}")
            ->assertRedirect('/admin/attributes');

        $this->assertDatabaseMissing('product_attributes', ['id' => $attribute->id]);
    }
}
