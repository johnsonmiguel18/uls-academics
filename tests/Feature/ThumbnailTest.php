<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/** /thumbs/{width}/{source}/{path}: resized copies of uploaded photos, written once to public/thumbs. */
class ThumbnailTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    private string $photo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = storage_path('app/public/thumb-test');
        File::ensureDirectoryExists($this->dir);
        $this->photo = $this->dir . '/photo.png';
        $image = imagecreatetruecolor(1200, 800);
        imagepng($image, $this->photo);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        File::deleteDirectory(public_path('thumbs/480/storage/thumb-test'));
        File::deleteDirectory(public_path('thumbs/960/storage/thumb-test'));
        parent::tearDown();
    }

    public function test_makes_a_resized_webp_copy(): void
    {
        $this->get('/thumbs/480/storage/thumb-test/photo.png.webp')->assertOk()->assertHeader('Content-Type', 'image/webp');

        $this->assertSame(480, getimagesize(public_path('thumbs/480/storage/thumb-test/photo.png.webp'))[0]);
    }

    public function test_dot_segments_cannot_point_the_copy_outside_the_thumbs_folder(): void
    {
        // Enough "../" to climb to the root from both folders, then the photo's own absolute path:
        // the source resolves to the real photo, and the copy would be written over it.
        // (On Windows the photo's path has backslashes, which no URL may contain.)
        $path = str_repeat('../', 20) . ltrim(str_replace('\\', '/', $this->photo), '/');

        $this->get("/thumbs/480/storage/$path")->assertNotFound();
        $this->get('/thumbs/480/storage/thumb-test/./photo.png')->assertNotFound();

        $this->assertSame(1200, getimagesize($this->photo)[0], 'the original photo was overwritten');
    }

    public function test_a_broken_image_is_not_found_instead_of_an_error(): void
    {
        file_put_contents($this->dir . '/broken.jpg', 'not an image');

        $this->get('/thumbs/480/storage/thumb-test/broken.jpg.webp')->assertNotFound();
        $this->assertEmpty(glob(public_path('thumbs/480/storage/thumb-test/*')), 'a partial file was left behind');
    }

    public function test_only_images_inside_the_allowed_folder(): void
    {
        $this->get('/thumbs/480/storage/' . str_repeat('../', 3) . '.env')->assertNotFound();
        $this->get('/thumbs/480/storage/thumb-test/missing.png')->assertNotFound();
    }

    public function test_thumbnails_are_made_ahead_when_an_image_is_saved_and_by_the_warm_command(): void
    {
        // Saving a record with a new photo makes both sizes right away.
        \App\Models\News::create(['title' => 'With photo', 'slug' => 'with-photo', 'category' => 'research', 'content' => 'Body',
            'status' => 'draft', 'featured_image' => 'thumb-test/photo.png']);
        $this->assertFileExists(public_path('thumbs/480/storage/thumb-test/photo.png.webp'));
        $this->assertFileExists(public_path('thumbs/960/storage/thumb-test/photo.png.webp'));

        // The command fills in anything missing (e.g. photos copied in by hand) and skips what exists.
        imagepng(imagecreatetruecolor(800, 600), $this->dir . '/second.png');
        $this->artisan('thumbs:warm', ['--source' => 'storage'])->assertSuccessful();
        $this->assertSame(480, getimagesize(public_path('thumbs/480/storage/thumb-test/second.png.webp'))[0]);
        $this->assertFileExists(public_path('thumbs/960/storage/thumb-test/second.png.webp'));
    }

    public function test_palette_png_becomes_a_webp_copy_instead_of_an_error(): void
    {
        $palette = imagecreate(1000, 500); // palette image, like many small logos
        imagecolorallocate($palette, 25, 42, 138);
        imagepng($palette, $this->dir . '/logo.png');

        $this->get('/thumbs/480/storage/thumb-test/logo.png.webp')->assertOk()->assertHeader('Content-Type', 'image/webp');
    }
}
