<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_promote_a_customer_to_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->actingAs($admin)->put("/admin/users/{$customer->id}", [
            'role' => User::ROLE_ADMIN,
        ])->assertRedirect('/admin/users');

        $this->assertSame(User::ROLE_ADMIN, $customer->fresh()->role);
    }

    public function test_an_admin_cannot_demote_themselves(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->put("/admin/users/{$admin->id}", [
            'role' => User::ROLE_CUSTOMER,
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertSame(User::ROLE_ADMIN, $admin->fresh()->role);
    }
}
