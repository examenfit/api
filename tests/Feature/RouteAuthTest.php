<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
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
            ['GET', '/api/feedback'],
            ['POST', '/api/feedback'],
            ['POST', '/api/contact-requests'],
            ['GET', '/api/contact-requests'],
            ['GET', '/api/download-collection/abc'],
            ['GET', '/api/download-collection-corrections/abc'],
            ['GET', '/api/download-collection-pdf/abc'],
            ['GET', '/api/download-appendixes-pdf/abc'],
            ['GET', '/api/download-topic-appendixes-pdf/abc'],
            ['GET', '/api/streams/'],
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

    #[DataProvider('protectedRoutesProvider')]
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

    #[DataProvider('deletedRoutesProvider')]
    public function test_deleted_routes_are_gone(string $method, string $uri): void
    {
        $this->json($method, $uri)->assertNotFound();
    }

    public function test_public_practicing_routes_are_not_behind_auth(): void
    {
        // decided 2026-11-17: the pdf server fetches the html routes server-to-server; the practicing pages
        // /student/*, /a/* and /c/* call the others without a login. Without auth they reach model binding
        // and 404 on the dummy id (a 401 would mean auth was added again).
        foreach ([
            '/api/download-collection-html/abc',
            '/api/download-appendixes-html/abc',
            '/api/download-topic-appendixes-html/abc',
            '/api/collections/abc',
            '/api/topics/abc',
            '/api/activity_summary/abc',
            '/api/streams/abc/formuleblad',
        ] as $uri) {
            $this->getJson($uri)->assertNotFound();
        }
        $this->postJson('/api/collections/abc/def/elaborations')->assertNotFound();
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

        // routes without model binding, so the role check (not a 404) decides
        $this->actingAs($leerling, 'sanctum')->getJson('/api/contact-requests')->assertForbidden();
        $this->actingAs($leerling, 'sanctum')->getJson('/api/annotations-create-exams')->assertForbidden();
    }

    public function test_admin_can_read_contact_requests(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum')->getJson('/api/contact-requests')->assertOk();
    }
}
