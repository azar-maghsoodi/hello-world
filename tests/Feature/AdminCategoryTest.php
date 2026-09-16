<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_an_admin_can_create_a_category(): void
    {
        $this->actingAs($this->admin)->post('/admin/categories', [
            'name' => 'Electronics',
            'is_active' => true,
        ])->assertRedirect('/admin/categories');

        $this->assertDatabaseHas('categories', [
            'name' => 'Electronics',
            'slug' => 'electronics',
            'parent_id' => null,
        ]);
    }

    public function test_an_admin_can_create_a_subcategory(): void
    {
        $parent = Category::factory()->create();

        $this->actingAs($this->admin)->post('/admin/categories', [
            'name' => 'Phones',
            'parent_id' => $parent->id,
            'is_active' => true,
        ])->assertRedirect('/admin/categories');

        $this->assertDatabaseHas('categories', [
            'name' => 'Phones',
            'parent_id' => $parent->id,
        ]);
    }

    public function test_a_category_cannot_be_its_own_parent(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin)->put("/admin/categories/{$category->id}", [
            'name' => $category->name,
            'parent_id' => $category->id,
            'is_active' => true,
        ]);

        $response->assertSessionHasErrors('parent_id');
    }

    public function test_an_admin_can_delete_a_category(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin)
            ->delete("/admin/categories/{$category->id}")
            ->assertRedirect('/admin/categories');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
