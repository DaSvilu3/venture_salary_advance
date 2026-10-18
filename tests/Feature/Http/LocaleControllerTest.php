<?php

namespace Tests\Feature\Http;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class LocaleControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_switching_to_arabic_saves_the_locale_and_renders_right_to_left(): void
    {
        $user = User::factory()->employee()->create();

        $this->actingAs($user)
            ->from('/app')
            ->put(route('locale.update'), ['locale' => 'ar'])
            ->assertRedirect('/app')
            ->assertSessionHas('locale', 'ar');

        $this->assertSame('ar', $user->fresh()->locale);

        $this->actingAs($user->fresh())
            ->get('/app')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('locale', 'ar')
                ->where('direction', 'rtl')
                ->where('translations.Home', 'الرئيسية')
            );
    }

    public function test_guests_can_switch_locale_for_their_session(): void
    {
        $this->put(route('locale.update'), ['locale' => 'ar'])
            ->assertSessionHas('locale', 'ar');

        $this->get(route('login'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('direction', 'rtl'));
    }

    public function test_rejects_unsupported_locales(): void
    {
        $this->put(route('locale.update'), ['locale' => 'fr'])
            ->assertSessionHasErrors('locale')
            ->assertSessionMissing('locale');
    }
}
