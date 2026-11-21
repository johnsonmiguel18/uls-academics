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
}
