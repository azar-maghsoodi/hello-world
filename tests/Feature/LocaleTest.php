<?php

namespace Tests\Feature;

use App\Models\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_default_language_is_used_when_no_locale_is_set(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true, 'is_active' => true]);
        Language::factory()->create(['code' => 'fr', 'is_default' => false, 'is_active' => true]);

        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('locale', 'en'));
    }

    public function test_a_user_can_switch_the_active_locale(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true, 'is_active' => true]);
        Language::factory()->create(['code' => 'fr', 'is_default' => false, 'is_active' => true]);

        $this->put('/locale', ['locale' => 'fr'])->assertRedirect();

        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('locale', 'fr'));
    }

    public function test_switching_to_an_inactive_locale_is_ignored(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true, 'is_active' => true]);
        Language::factory()->create(['code' => 'de', 'is_default' => false, 'is_active' => false]);

        $this->put('/locale', ['locale' => 'de']);

        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('locale', 'en'));
    }

    public function test_only_active_languages_are_shared_with_the_frontend(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true, 'is_active' => true]);
        Language::factory()->create(['code' => 'de', 'is_default' => false, 'is_active' => false]);

        $this->get('/')->assertInertia(fn (Assert $page) => $page->has('languages', 1));
    }
}
