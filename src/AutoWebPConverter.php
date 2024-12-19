<?php

namespace mrfsrf;

// error_reporting(E_ALL);
// ini_set('display_errors', 1);
use Exception;

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
  ];

  public function __construct(string $file = null)
  {
    // $this->convert_to_webp($file);
    \add_filter('wp_handle_upload', [$this, 'convert_to_webp']);
    \add_filter('upload_mimes', [$this, 'add_webp_mime']);
    \add_filter('wp_generate_attachment_metadata', [$this, 'handle_thumbnails'], 10, 2);
    // TODO implement
    // \add_action('template_redirect', [$this, 'maybe_serve_webp']);
  }

  /**
   * Add WebP to allowed MIME types.
   */
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
      echo 'WebP Conversion Error: ' . $e->getMessage();
      \add_action('admin_notices', function () use ($e) {
        printf(
          '<div class="error"><p>%s</p></div>',
          \esc_html('WebP conversion failed: ' . $e->getMessage())
        );
      });
    }
    // print_r($upload);
    return $upload;
  }

  /**
   * Get WebP filename from original.
   */
  private function get_webp_path(string $file_path): string
  {
    return preg_replace('/\.(jpe?g|png)$/i', '.webp', $file_path);
  }

  /**
   * Create WebP version of image.
   */
  private function create_webp_image(
    string $source_path,
    string $webp_path,
    string $mime_type,
  ): void {
    $output = [];
    $return_status = null;
    \exec('command -v cwebp', $output, $return_status);

    if ($return_status === 0) {
      $command = $this->build_cwebp_command(
        $source_path,
        $webp_path,
        $mime_type 
      );
      $this->execute_cwebp_command($command);
    } else {
      throw new \RuntimeException("cwebp is not installed");
    }
  }

  /**
   * Handle thumbnail conversion.
   */
  public function handle_thumbnails(array $metadata, string $attachment_id): array
  {
    if (!isset($metadata['sizes'])) return $metadata;

    $file = \get_attached_file($attachment_id);
    $dir = pathinfo($file, PATHINFO_DIRNAME);

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
    // if (!$this->is_image_request()) return;

    // $source = $this->get_image_path();
    // if (!$source || !file_exists($source)) return;

    // $destination = $this->get_webp_path($source);

    // try {
    //   WebPConvert::serveConverted($source, $destination, [
    //     'fail' => 'original',  // Serve original if conversion fails
    //     'serve-image' => [
    //       'headers' => [
    //         'cache-control' => true,
    //         'vary-accept' => true,
    //       ],
    //       'cache-control-header' => 'public, max-age=31536000', // 1 year
    //     ],
    //     // TODO: Add Webp conversion for images that are not yet converted.
    //     // 'convert' => $this->convert_options,
    //   ]);
    //   exit;
    // } catch (Exception $e) {
    //   error_log('WebP conversion failed: ' . $e->getMessage());
    // }
  }

  /**
   * Only process if it's an image request.
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

  private function build_cwebp_command(
    string $source_path, 
    string $webp_path,
    string $image_type): string
  {
    if (!isset($this->options[$image_type]))
      throw new \InvalidArgumentException("Unsupported image type: $image_type");

    $opts = $this->options[$image_type];
    $command_parts = ['cwebp'];

    // Map the options to cwebp flags
    foreach ($opts as $key => $value) {
      switch ($key) {
        case 'quality':
          $command_parts[] = "-q $value";
          break;
        case 'near-lossless':
          $command_parts[] = "-near_lossless $value";
          break;
        case 'sharp-yuv':
          if ($value === true) {
            $command_parts[] = "-sharp_yuv";
          }
          break;
        case 'encoding':
          // Handle 'auto' encoding - might want to implement specific logic
          // or ignore it since cwebp handles this automatically
          break;
        case 'auto-limit':
          // This might need custom implementation depending on what 
          // auto-limit is supposed to do
          break;
      }
    }

    $command_parts[] = escapeshellarg($source_path);
    $command_parts[] = "-o " . escapeshellarg($webp_path);

    return implode(' ', $command_parts);
  }

  private function execute_cwebp_command(string $command): void
  {
    try {
      $output = [];
      $return_var = null;
      exec($command, $output, $return_var);

      if ($return_var !== 0) {
        throw new \RuntimeException(
          "cwebp conversion failed with status $return_var: " . implode("\n", $output)
        );
      }
    } catch (\Exception $e) {
      throw new \RuntimeException("Failed to execute cwebp: " . $e->getMessage());
    }
  }

  ///// TEMP just for testing
  // private function create_file_array($file_path)
  // {
  //   if (!file_exists($file_path))
  //     throw new \Exception("File not found: $file_path");

  //   // Get file info
  //   $path_info = pathinfo($file_path);
  //   $mime_type = mime_content_type($file_path);
  //   // Get absolute path
  //   $absolute_path = realpath($file_path);

  //   return [
  //     'name' => $path_info['basename'],
  //     'type' => $mime_type,
  //     'tmp_name' => $absolute_path,
  //     'error' => 0,
  //     'file' => $file_path,
  //     'size' => filesize($file_path)
  //   ];
  //   // print_r($a);
  //   // return $a;
  // }
}
