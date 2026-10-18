<?php

namespace Tests\Feature\Http;

use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class HandleAppearanceTest extends TestCase
{
    public function test_pages_render_light_without_a_saved_choice(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('class="dark"', false);
    }

    #[TestWith(['system'])]
    #[TestWith(['light'])]
    public function test_anything_but_an_explicit_dark_choice_renders_light(string $saved): void
    {
        $this->withUnencryptedCookie('appearance', $saved)
            ->get(route('home'))
            ->assertDontSee('class="dark"', false);
    }

    public function test_an_explicit_dark_choice_renders_dark(): void
    {
        $this->withUnencryptedCookie('appearance', 'dark')
            ->get(route('home'))
            ->assertSee('class="dark"', false);
    }
}
