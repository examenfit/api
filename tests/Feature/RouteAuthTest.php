<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// 2026-11-17 (work/todo/bug-api-unauthenticated-routes.txt): routes that were public before must now answer
// 401 without a session (auth runs before model binding, so dummy ids are fine), admin-only routes 403 for
// other roles, the deleted fix/test routes 404, and the pdf-server html routes stay public.
class RouteAuthTest extends TestCase
{
    use RefreshDatabase;

    public static function protectedRoutesProvider(): array
    {
        return [
            ['GET', '/api/log/collection/abc'],
            ['GET', '/api/log/latest/abc'],
            ['GET', '/api/activity_summary/abc'],
            ['GET', '/api/feedback'],
            ['POST', '/api/feedback'],
            ['POST', '/api/contact-requests'],
            ['GET', '/api/contact-requests'],
            ['GET', '/api/topics/abc'],
            ['GET', '/api/download-collection/abc'],
            ['GET', '/api/download-collection-corrections/abc'],
            ['GET', '/api/download-collection-pdf/abc'],
            ['GET', '/api/download-appendixes-pdf/abc'],
            ['GET', '/api/download-topic-appendixes-pdf/abc'],
            ['GET', '/api/collections/abc'],
            ['POST', '/api/collections/abc/def/elaborations'],
            ['GET', '/api/streams/'],
            ['GET', '/api/streams/abc/formuleblad'],
            ['GET', '/api/streams/abc/exams'],
            ['GET', '/api/streams/abc/scores'],
            ['GET', '/api/streams/abc'],
            ['GET', '/api/score'],
            ['GET', '/api/user/switch'],
            ['POST', '/api/user/switch'],
            ['GET', '/api/privileges/abc/scores'],
            ['GET', '/api/annotations/abc'],
            ['GET', '/api/streams/abc/all-questions'],
            ['PUT', '/api/annotation-question/abc/2024/1/1'],
        ];
    }

    /** @dataProvider protectedRoutesProvider */
    public function test_protected_routes_require_authentication(string $method, string $uri): void
    {
        $this->json($method, $uri)->assertUnauthorized();
    }

    public static function deletedRoutesProvider(): array
    {
        return [
            ['GET', '/api/fix_marcel'],
            ['GET', '/api/fix-totalPoints'],
            ['GET', '/api/custom/activities.tsv'],
            ['GET', '/api/test'],
        ];
    }

    /** @dataProvider deletedRoutesProvider */
    public function test_deleted_routes_are_gone(string $method, string $uri): void
    {
        $this->json($method, $uri)->assertNotFound();
    }

    public function test_pdf_server_html_routes_stay_public(): void
    {
        // decided 2026-11-17: not token-gated; without a session they reach model binding and 404 on the dummy id
        $this->getJson('/api/download-collection-html/abc')->assertNotFound();
        $this->getJson('/api/download-appendixes-html/abc')->assertNotFound();
        $this->getJson('/api/download-topic-appendixes-html/abc')->assertNotFound();
    }

    public function test_activity_log_stays_public_for_anonymous_tracking(): void
    {
        $this->postJson('/api/log', [
            'device_key' => 'd',
            'session_key' => 's',
            'origin' => 'o',
            'activity' => 'a',
        ])->assertStatus(201);
    }

    public function test_authenticated_user_can_list_streams(): void
    {
        $user = User::factory()->create(['role' => 'docent']);

        $this->actingAs($user, 'sanctum')->getJson('/api/streams/')->assertOk();
    }

    public function test_admin_only_routes_reject_other_roles(): void
    {
        $leerling = User::factory()->create(['role' => 'leerling']);

        $this->actingAs($leerling, 'sanctum')->getJson('/api/annotations/abc')->assertForbidden();
        $this->actingAs($leerling, 'sanctum')->getJson('/api/contact-requests')->assertForbidden();
    }

    public function test_admin_can_read_contact_requests(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum')->getJson('/api/contact-requests')->assertOk();
    }
}
