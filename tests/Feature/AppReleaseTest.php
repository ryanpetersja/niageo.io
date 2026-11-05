<?php

namespace Tests\Feature;

use App\Models\AppRelease;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AppReleaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Config::set('services.app_releases.token', 'ci-secret');
        $this->travelTo('2026-11-05 14:32:00');
    }

    private function apk(string $name = 'app-release.apk'): UploadedFile
    {
        return UploadedFile::fake()->create($name, 2048, 'application/vnd.android.package-archive');
    }

    public function test_ci_publishes_a_build_and_the_page_offers_it(): void
    {
        $response = $this->withToken('ci-secret')->post('/api/v1/app-releases', [
            'version' => '0.2.0',
            'build_number' => 57,
            'git_sha' => 'abcdef1234567890',
            'notes' => 'Operations tab',
            'apk' => $this->apk(),
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('display_version', '0.2.0 (build 57)')
            ->assertJsonPath('released_at', '2026-11-05T14:32:00+00:00')
            ->assertJsonPath('file_name', 'niageo-ops-0.2.0-57.apk');

        $release = AppRelease::first();
        Storage::disk('local')->assertExists($release->file_path);
        $this->assertSame(64, strlen($release->sha256));

        $user = User::factory()->create();
        $this->actingAs($user)->get('/app')
            ->assertOk()
            ->assertSee('Version 0.2.0')
            ->assertSee('build 57')
            ->assertSee('Nov 5, 2026 at 14:32')
            ->assertSee('Download APK')
            ->assertSee('Operations tab')
            ->assertSee('commit abcdef1');

        $download = $this->actingAs($user)->get(route('app.releases.download', $release));
        $download->assertOk();
        $this->assertStringStartsWith('application/vnd.android.package-archive', $download->headers->get('content-type'));
        $this->assertStringContainsString('niageo-ops-0.2.0-57.apk', $download->headers->get('content-disposition'));
    }

    public function test_phones_get_the_latest_build_with_a_signed_link_that_needs_no_login(): void
    {
        $this->withToken('ci-secret')->post('/api/v1/app-releases', ['version' => '0.1.0', 'build_number' => 3, 'apk' => $this->apk()], ['Accept' => 'application/json'])->assertCreated();
        $this->withToken('ci-secret')->post('/api/v1/app-releases', ['version' => '0.2.0', 'build_number' => 9, 'apk' => $this->apk()], ['Accept' => 'application/json'])->assertCreated();

        Sanctum::actingAs(User::factory()->create());
        $latest = $this->getJson('/api/v1/app-releases/latest')
            ->assertOk()
            ->assertJsonPath('build_number', 9)
            ->assertJsonPath('version', '0.2.0');

        $url = $latest->json('download_url');
        $this->assertStringContainsString('signature=', $url);

        // A fresh, signed-out client (the phone's browser) can use the signed link.
        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->get($url)->assertOk();

        $unsigned = route('app.releases.download', AppRelease::latestFor());
        $this->get($unsigned)->assertRedirect(route('login'));
    }

    public function test_publishing_needs_the_token_and_an_apk(): void
    {
        $this->withToken('wrong')->post('/api/v1/app-releases', ['version' => '0.2.0', 'apk' => $this->apk()], ['Accept' => 'application/json'])
            ->assertUnauthorized();

        $this->withToken('ci-secret')->post('/api/v1/app-releases', ['version' => '0.2.0', 'apk' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain')], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'The file must be an Android package (.apk).');

        Config::set('services.app_releases.token', null);
        $this->withToken('')->post('/api/v1/app-releases', ['version' => '0.2.0', 'apk' => $this->apk()], ['Accept' => 'application/json'])
            ->assertUnauthorized();

        $this->getJson('/api/v1/app-releases/latest')->assertUnauthorized();
        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/v1/app-releases/latest')->assertNotFound();
    }

    public function test_admins_upload_and_remove_builds_from_the_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)->post('/app/releases', ['version' => '0.2.0', 'apk' => $this->apk()])->assertForbidden();

        $this->actingAs($admin)->post('/app/releases', ['version' => '0.2.0', 'notes' => 'Built on my laptop', 'apk' => $this->apk()])
            ->assertRedirect(route('app.releases'))
            ->assertSessionHas('success', 'Build 0.2.0 (build 1) published.');

        $this->actingAs($admin)->post('/app/releases', ['version' => '0.2.1', 'apk' => $this->apk()])->assertRedirect();
        $this->assertSame(2, AppRelease::latestFor()->build_number, 'the build number keeps counting up');

        $first = AppRelease::orderBy('id')->first();
        $this->actingAs($admin)->get('/app')->assertOk()->assertSee('Earlier builds')->assertSee('uploaded by ' . $admin->name);

        $this->actingAs($admin)->delete(route('app.releases.destroy', $first))->assertRedirect(route('app.releases'));
        $this->assertNull(AppRelease::find($first->id));
        Storage::disk('local')->assertMissing($first->file_path);
    }
}
