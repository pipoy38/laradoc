<?php

use App\Models\Article;
use App\Models\Media;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());
});

it('uploads an image from the editor and returns its location', function () {
    $r = $this->postJson('/admin/media/upload', ['file' => UploadedFile::fake()->image('shot.png', 400, 300)]);
    $r->assertCreated()->assertJsonStructure(['location', 'id']);
    $m = Media::first();
    expect($m->mime)->toBe('image/png')->and($m->width)->toBe(400)->and($m->uploaded_by)->not->toBeNull();
    Storage::disk('public')->assertExists($m->disk_path);
});

it('rejects non-images and oversized files', function () {
    $this->postJson('/admin/media/upload', ['file' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')])->assertStatus(422);
    $this->postJson('/admin/media/upload', ['file' => UploadedFile::fake()->image('big.jpg')->size(6000)])->assertStatus(422);
});

it('rejects svg uploads', function () {
    $this->postJson('/admin/media/upload', ['file' => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>')])->assertStatus(422);
    expect(Media::count())->toBe(0);
});

it('lists, edits alt text and deletes, refusing deletes for files in use', function () {
    $m = Media::factory()->create(['uploaded_by' => auth()->id()]);
    Storage::disk('public')->put($m->disk_path, 'x');
    $this->get('/admin/media')->assertOk()->assertSee($m->original_name);
    $this->patch("/admin/media/{$m->id}", ['alt' => 'A screenshot'])->assertRedirect();
    expect($m->fresh()->alt)->toBe('A screenshot');
    app(Settings::class)->set('logo_media_id', (string) $m->id);
    $this->delete("/admin/media/{$m->id}")->assertSessionHas('err');
    app(Settings::class)->set('logo_media_id', null);
    Article::factory()->create(['og_image_id' => $m->id]);
    $this->delete("/admin/media/{$m->id}")->assertSessionHas('err');
    Article::query()->update(['og_image_id' => null]);
    $this->delete("/admin/media/{$m->id}")->assertSessionHas('ok');
    expect(Media::count())->toBe(0);
    Storage::disk('public')->assertMissing($m->disk_path);
});

it('refuses to delete the current favicon', function () {
    $m = Media::factory()->create(['uploaded_by' => auth()->id()]);
    Storage::disk('public')->put($m->disk_path, 'x');
    app(Settings::class)->set('favicon_media_id', (string) $m->id);
    $this->delete("/admin/media/{$m->id}")->assertSessionHas('err');
    expect(Media::count())->toBe(1);
    Storage::disk('public')->assertExists($m->disk_path);
});

it('uploads via the non-JSON form path and redirects to the media index with a flash', function () {
    $r = $this->post('/admin/media/upload', ['file' => UploadedFile::fake()->image('shot.png', 10, 10)]);
    $r->assertRedirect('/admin/media')->assertSessionHas('ok');
    expect(Media::count())->toBe(1);
});

/**
 * Wraps a genuine image's temp file in a new UploadedFile that declares a misleading
 * client filename and Content-Type — simulating an attacker who sends a real image's
 * bytes with a dangerous-looking name. getMimeType() always sniffs the real bytes (it
 * ignores the constructor's declared $mimeType), so this exercises the actual server-side
 * MIME sniff exactly as a real malicious request would.
 */
dataset('disguised images', [
    'html-named' => ['shell.html', 'text/html'],
    'svg-named' => ['logo.svg', 'image/svg+xml'],
]);

it('stores an upload under an extension derived from its sniffed content, not its filename', function (string $asName, string $declaredMime) {
    $real = UploadedFile::fake()->image('real.png', 10, 10);
    $disguised = new UploadedFile($real->getPathname(), $asName, $declaredMime, null, true);
    $r = $this->postJson('/admin/media/upload', ['file' => $disguised]);
    $r->assertCreated();
    expect(Media::first()->disk_path)->toEndWith('.png');
})->with('disguised images');
