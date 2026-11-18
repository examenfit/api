<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
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

        // feedback sits on the public practicing pages: catalog labels and a feedback POST, no auth
        $this->getJson('/api/feedback')->assertOk();
        $this->postJson('/api/feedback', [
            'feedback' => 'test',
            'collection' => null,
            'stream' => null,
            'exam' => null,
            'question' => null,
            'part' => null,
        ])->assertOk();
        foreach (['/api/feedback/abc', '/api/feedback/abc/def', '/api/feedback/abc/def/ghi'] as $uri) {
            $this->assertNotEquals(401, $this->getJson($uri)->getStatusCode());
        }
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

    public function test_user_switch_logs_in_as_linked_user_on_the_web_guard(): void
    {
        // regression: with auth:sanctum the default guard is sanctum's RequestGuard, which has no
        // login() — the switch must use the session guard explicitly (staging 500, 2026-11-18)
        $docent = User::factory()->create(['role' => 'docent', 'link' => 'link-1']);
        $demo = User::factory()->create(['role' => 'leerling', 'link' => 'link-1']);

        $this->actingAs($docent, 'sanctum')
            ->postJson('/api/user/switch', ['email' => $demo->email])
            ->assertOk();

        $this->assertAuthenticatedAs($demo, 'web');
    }

    public function test_annotation_oefensets_and_examens_have_no_role_gate(): void
    {
        // the zelf-oefenen pages (/zelf-oefenen/per-onderwerp, /hele-examens) call these as any logged-in
        // user (docent got 403 on staging, 2026-11-18); the admin annotation routes keep role:admin,author
        $router = app('router');
        foreach (['api/annotations/x/oefensets', 'api/annotations/x/examens'] as $uri) {
            $route = $router->getRoutes()->match(Request::create('/' . $uri, 'GET'));
            $this->assertNotContains('role:admin,author', $route->gatherMiddleware(), $uri);
        }
        foreach (['api/annotations/x', 'api/annotations-create-exams', 'api/streams/x/all-questions'] as $uri) {
            $route = $router->getRoutes()->match(Request::create('/' . $uri, 'GET'));
            $this->assertContains('role:admin,author', $route->gatherMiddleware(), $uri);
        }
    }
}
