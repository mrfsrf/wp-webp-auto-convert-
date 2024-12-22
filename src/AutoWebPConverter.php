<?php

namespace Mrfsrf\WpWebpAutoConvert;

use Mrfsrf\WpWebpAutoConvert\Cwebp;
use Mrfsrf\WpWebpAutoConvert\Helper\Notif;

class AutoWebPConverter
{
  protected Config $config;

  public function __construct(Config $config = null)
  {
    $this->config = $config; 
    \add_filter('wp_handle_upload', [$this, 'convert_to_webp']);
    \add_filter('upload_mimes', [$this, 'add_webp_mime']);
  }

  /** Add WebP to  MIME types. */
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
    if (!\in_array($upload['type'], ['image/jpeg', 'image/png']))
      return $upload;

    $file_path = $upload['file'];
    $webp_path = $this->get_webp_path($file_path);
    $mime_type = \str_replace('image/', '', $upload['type']);

    try {
      $this->create_webp_image($file_path, $webp_path, $mime_type);
      $upload['webp_file'] = $webp_path; // Keep original file and add WebP version
    } catch (\Throwable $e) {
      Notif::wp_admin($e, 'WebP conversion failed');
    }
    return $upload;
  }

  /** Get WebP filename from original. */
  protected function get_webp_path(string $file_path): string
  {
    return \preg_replace('/\.(jpe?g|png)$/i', '.webp', $file_path);
  }

  /** Create WebP version of image. */
  protected function create_webp_image(
    string $source_path,
    string $webp_path,
    string $mime_type,
  ): void {
    $return_status = null;
    \exec('command -v cwebp', [], $return_status);

    if ($return_status === 0) {
      $format_config = $this->config->get_format_config($mime_type);
      Cwebp::init($format_config);
      $command = Cwebp::build_cwebp_command(
        $source_path,
        $webp_path,
        $mime_type 
      );
      Cwebp::execute_cwebp_command($command);
    } else {
      Notif::wp_admin(
        new \RuntimeException(''),
        "cwebp is not installed"
      );
      return;
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
