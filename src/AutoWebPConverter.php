<?php

namespace mrfsrf;

use Exception;
use WebPConvert\WebPConvert;

class AutoWebPConverter
{

  private array $options = [
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
    'converter' => [
      'cwebp',
      'vips',
      'imagick',
      'gmagick',
      'imagemagick',
      'graphicsmagick'
    ]
  ];

  public function __construct()
  {
    \add_filter('wp_handle_upload', [$this, 'convert_to_webp']);
    \add_filter('upload_mimes', [$this, 'add_webp_mime']);
    \add_filter('wp_generate_attachment_metadata', [$this, 'handle_thumbnails'], 10, 2);
    \add_action('template_redirect', [$this, 'maybe_serve_webp']);
  }

  /**
   * Add WebP to allowed MIME types
   */
  public function add_webp_mime(array $mimes): array|null
  {
    $mimes['webp'] = 'image/webp';
    return $mimes;
  }

  /**
   * Convert uploaded image to WebP
   * Only process jpeg and png
   */
  public function convert_to_webp(array $upload): array
  {
    $allowed_types = ['image/jpeg', 'image/png'];
    if (!in_array($upload['type'], $allowed_types)) return $upload;

    $file_path = $upload['file'];
    $webp_path = $this->get_webp_path($file_path);

    try {
      $this->create_webp_image($file_path, $webp_path, $this->options);
      $upload['webp_file'] = $webp_path; // Keep original file and add WebP version
    } catch (Exception $e) {
      echo 'WebP Conversion Error: ' . $e->getMessage();
      add_action('admin_notices', function () use ($e) {
        printf(
          '<div class="error"><p>%s</p></div>',
          \esc_html('WebP conversion failed: ' . $e->getMessage())
        );
      });
    }

    return $upload;
  }

  /**
   * Get WebP filename from original
   */
  private function get_webp_path(string $file_path): string
  {
    return preg_replace('/\.(jpe?g|png)$/i', '.webp', $file_path);
  }

  /**
   * Create WebP version of image using GD
   */
  private function create_webp_image(
    string $source_path,
    string $webp_path,
    array $options = $this->options
  ): void {
    WebPConvert::convert($source_path, $webp_path, $options);
  }

  /**
   * Handle thumbnail conversion.
   */
  public function handle_thumbnails(array $metadata, string $attachment_id): array
  {
    if (!isset($metadata['sizes'])) return $metadata;

    $file = \get_attached_file($attachment_id);
    $dir = pathinfo($file, PATHINFO_DIRNAME);

    // Convert each thumbnail
    foreach ($metadata['sizes'] as $size => $data) {
      $thumbnail_path = $dir . '/' . $data['file'];
      $webp_path = $this->get_webp_path($thumbnail_path);

      $this->create_webp_image($thumbnail_path, $webp_path, $this->options);

      // Add WebP version to metadata
      $metadata['sizes'][$size]['webp'] = basename($webp_path);
    }

    return $metadata;
  }

  /**
   * Serve the generated Webp images instead.
   */
  public function maybe_serve_webp(): void
  {
    if (!$this->is_image_request()) return;

    $source = $this->get_image_path();
    if (!$source || !file_exists($source)) return;

    $destination = $this->get_webp_path($source);

    try {
      WebPConvert::serveConverted($source, $destination, [
        'fail' => 'original',  // Serve original if conversion fails
        'serve-image' => [
          'headers' => [
            'cache-control' => true,
            'vary-accept' => true,
          ],
          'cache-control-header' => 'public, max-age=31536000', // 1 year
        ],
        // TODO: Add Webp conversion for images that are not yet converted
        // 'convert' => $this->convert_options,
      ]);
      exit;
    } catch (\Exception $e) {
      error_log('WebP conversion failed: ' . $e->getMessage());
    }
  }

  /**
   * Only process if it's an image request
   */
  private function is_image_request(): bool
  {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    return preg_match('/\.(jpe?g|png)$/i', $uri);
  }

  private function get_image_path(): string|false
  {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (!$uri) return false;

    // Convert URL path to file system path
    $path = ABSPATH . ltrim($uri, '/');
    if (!$this->is_path_safe($path)) return false;

    return $path;
  }

  private function is_path_safe(string $path): bool
  {
    // Prevent directory traversal
    $uploads_dir = \wp_upload_dir()['basedir'];
    $real_path = realpath($path);
    $real_uploads = realpath($uploads_dir);

    if (!$real_path || !$real_uploads) return false;

    // Only allow access to files in uploads directory
    return strpos($real_path, $real_uploads) === 0;
  }
}
