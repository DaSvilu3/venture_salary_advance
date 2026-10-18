<?php

namespace Tests\Feature\Http\Admin;

use App\Models\Disclosure;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DisclosureControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_draft_becomes_the_current_disclosure_when_published(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $previous = Disclosure::factory()->create(['published_at' => now()->subMonth()]);

        $this->actingAs($admin)->post(route('admin.disclosures.store'), [
            'version' => '2026.2',
            'body_en' => 'New terms.',
            'body_ar' => 'شروط جديدة.',
        ]);

        $draft = Disclosure::query()->where('version', '2026.2')->sole();
        $this->assertNull($draft->published_at);
        $this->assertTrue(Disclosure::current()?->is($previous));

        $this->actingAs($admin)->post(route('admin.disclosures.publish', $draft));

        $this->assertTrue(Disclosure::current()?->is($draft));
    }

    public function test_a_published_version_cannot_be_published_again(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $published = Disclosure::factory()->create(['published_at' => now()->subDay()]);

        $this->actingAs($admin)
            ->post(route('admin.disclosures.publish', $published))
            ->assertSessionHasErrors(['disclosure' => 'This version is already published.']);

        $this->assertSame(now()->subDay()->toDateString(), $published->fresh()->published_at?->toDateString());
    }
}
