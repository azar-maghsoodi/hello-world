<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_the_profile_page(): void
    {
        $this->get('/profile')->assertRedirect('/login');
    }

    public function test_a_user_can_view_their_profile_page(): void
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id, 'phone' => '555-1234']);

        $this->actingAs($user)
            ->get('/profile')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Shop/Profile')
                ->where('profile.phone', '555-1234')
            );
    }

    public function test_a_user_can_update_their_profile_creating_it_if_missing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/profile', [
            'name' => 'New Name',
            'email' => $user->email,
            'phone' => '555-9999',
            'city' => 'Springfield',
        ])->assertRedirect('/profile');

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('555-9999', $user->profile->phone);
        $this->assertSame('Springfield', $user->profile->city);
    }

    public function test_a_user_can_update_an_existing_profile(): void
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id, 'phone' => '555-1111']);

        $this->actingAs($user)->put('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '555-2222',
        ])->assertRedirect('/profile');

        $this->assertSame('555-2222', $user->profile()->first()->phone);
        $this->assertSame(1, Profile::where('user_id', $user->id)->count());
    }

    public function test_email_must_be_unique_when_updating_profile(): void
    {
        $existing = User::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->put('/profile', [
            'name' => $user->name,
            'email' => $existing->email,
        ])->assertSessionHasErrors('email');
    }
}
