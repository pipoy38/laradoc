<?php
use App\Models\Article;
use App\Models\Media;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () { Storage::fake('public'); $this->actingAs(User::factory()->create()); });

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
