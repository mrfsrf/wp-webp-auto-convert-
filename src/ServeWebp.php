<?php
namespace Mrfsrf\WpWebpAutoConvert;

class ServeWebp extends AutoWebPConverter
{

  public function __construct()
  {
    \add_action('template_redirect', [$this, 'maybe_serve_webp']); 
  }

  /** Serve the generated Webp images instead. */
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
        // TODO: Add Webp conversion for images that are not yet converted.
        // 'convert' => $this->convert_options,
      ]);
      exit;
    } catch (\Exception $e) {
      error_log('WebP conversion failed: ' . $e->getMessage());
    }
  }

  /** Only process if it's an image request. */
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
}
