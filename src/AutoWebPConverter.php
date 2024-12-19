<?php
namespace Mrfsrf\WpWebpAutoConvert;

use Exception;
use Mrfsrf\WpWebpAutoConvert\Cwebp;

class AutoWebPConverter
{
  protected array $options = [
    'png' => [
      'encoding'      => 'auto',
      'near-lossless' => 60,
      'quality'       => 85,
      'sharp-yuv'     => true,
    ],
    'jpeg' => [
      'encoding'      => 'auto',
      'quality'       => 75,
      'auto-limit'    => true,
      'sharp-yuv'     => true,
    ],
  ];

  public function __construct()
  {
    \add_filter('wp_handle_upload', [$this, 'convert_to_webp']);
    \add_filter('upload_mimes', [$this, 'add_webp_mime']);
  }

  /** Add WebP to allowed MIME types. */
  public function add_webp_mime(array $mimes): array|null
  {
    $mimes['webp'] = 'image/webp';
    return $mimes;
  }

  /**
   * Convert uploaded image to WebP.
   * Only process jpeg and png.
   */
  public function convert_to_webp(array $upload): array
  {
    $allowed_types = ['image/jpeg', 'image/png'];
    // TEMP!
    // $upload = $this->create_file_array($file);
    if (!in_array($upload['type'], $allowed_types)) return $upload;

    $file_path = $upload['file'];
    $webp_path = $this->get_webp_path($file_path);
    $mime_type = str_replace('image/', '', $upload['type']);

    try {
      $this->create_webp_image($file_path, $webp_path, $mime_type);
      $upload['webp_file'] = $webp_path; // Keep original file and add WebP version
    } catch (Exception $e) {
      // echo 'WebP Conversion Error: ' . $e->getMessage();
      \add_action('admin_notices', function () use ($e) {
        printf(
          '<div class="error"><p>%s</p></div>',
          \esc_html('WebP conversion failed: ' . $e->getMessage())
        );
      });
    }
    return $upload;
  }

  /** Get WebP filename from original. */
  protected function get_webp_path(string $file_path): string
  {
    return preg_replace('/\.(jpe?g|png)$/i', '.webp', $file_path);
  }

  /** Create WebP version of image. */
  protected function create_webp_image(
    string $source_path,
    string $webp_path,
    string $mime_type,
  ): void {
    $output = [];
    $return_status = null;
    \exec('command -v cwebp', $output, $return_status);

    if ($return_status === 0) {
      Cwebp::init($this->options);
      $command = Cwebp::build_cwebp_command(
        $source_path,
        $webp_path,
        $mime_type 
      );
      Cwebp::execute_cwebp_command($command);
    } else {
      throw new \RuntimeException("cwebp is not installed");
    }
  }

  protected function is_path_safe(string $path): bool
  {
    // Prevent directory traversal
    $uploads_dir  = \wp_upload_dir()['basedir'];
    $real_path    = realpath($path);
    $real_uploads = realpath($uploads_dir);

    if (!$real_path || !$real_uploads) return false;

    // Only allow access to files in uploads directory
    return strpos($real_path, $real_uploads) === 0;
  }
}
