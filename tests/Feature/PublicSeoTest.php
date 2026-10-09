<?php

namespace Tests\Feature;

use App\Models\ProfessionalProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSeoTest extends TestCase
{
    use RefreshDatabase;

    // Le migrations seminano già il team (Sara = founder, slug sara-alessandri):
    // qui completiamo solo i campi usati dai meta/JSON-LD.
    private function createFounder(): ProfessionalProfile
    {
        $profile = ProfessionalProfile::where('is_founder', true)->firstOrFail();
        $profile->update([
            'phone'       => '+39 333 0000000',
            'address'     => 'Via Roma 1, Bologna',
            'is_bookable' => true,
        ]);

        return $profile;
    }

    public function test_landing_renders_server_side_seo_tags(): void
    {
        $this->createFounder();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('<title inertia>Dott.ssa Sara Alessandri — Psicologa</title>', false);
        $response->assertSee('<meta name="description"', false);
        $response->assertSee('<link rel="canonical"', false);
        $response->assertSee('property="og:title"', false);
        $response->assertSee('application/ld+json', false);
        $response->assertSee('MedicalBusiness', false);
        // Le pagine pubbliche per ospiti non devono avere noindex
        $response->assertDontSee('noindex', false);
    }

    public function test_booking_pages_have_meta(): void
    {
        $this->createFounder();

        $this->get('/prenota')
            ->assertOk()
            ->assertSee('<title inertia>Prenota un primo colloquio', false)
            ->assertSee('<meta name="description"', false);

        $this->get('/prenota/sara-alessandri')
            ->assertOk()
            ->assertSee('Prenota con Dott.ssa Sara Alessandri', false)
            ->assertSee('<link rel="canonical"', false);
    }

    public function test_group_enrollment_form_indexable_and_thankyou_noindex(): void
    {
        $this->get('/iscrizione-gruppo')
            ->assertOk()
            ->assertSee('<meta name="description"', false)
            ->assertDontSee('noindex', false);

        $this->get('/iscrizione-gruppo/grazie')
            ->assertOk()
            ->assertSee('noindex,nofollow', false);
    }

    public function test_sitemap_lists_public_pages(): void
    {
        $this->createFounder();

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee(route('home'), false);
        $response->assertSee(route('booking.index'), false);
        $response->assertSee(route('groups.public.create'), false);
        $response->assertSee(route('contact-requests.create', 'sara-alessandri'), false);
    }

    public function test_authenticated_pages_are_noindex(): void
    {
        $this->createFounder();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/mia-area')
            ->assertSee('noindex,nofollow', false);
    }
}
