<?php

namespace Mrfsrf\WpWebpAutoConvert;

use Mrfsrf\WpWebpAutoConvert\Helper\Notif;

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
    if (!$source || !\file_exists($source)) return;
    $destination = $this->get_webp_path($source);

    try {
      if (!@file_exists($destination)) {
        $this->serve($source, \mime_content_type($source));
        return;
      } else {
        $this->serve($destination, 'image/webp');
      }
      exit;
    } catch (\Throwable $e) { // Exception
      Notif::wp_admin($e, 'bla');
      // error_log('bla: ' . $e->getMessage());
    }
  }

  /** Only process if it's an image request. */
  private function is_image_request(): int|false
  {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    return \preg_match('/\.(jpe?g|png)$/i', $uri);
  }

  private function get_image_path(): string|false
  {
    $uri = \parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (!$uri) return false;

    // Convert URL path to file system path
    $path = ABSPATH . \ltrim($uri, '/');
    if (!$this->is_path_safe($path)) return false;

    return $path;
  }

  private function serve(string $filename, string $contentType): void
  {
    if (!\file_exists($filename))
      throw new \RuntimeException("File not found: $filename");

    // Step 1: Check if WebP is actually smaller than original
    if ($contentType === 'image/webp') {
      $baseFile = \preg_replace('/\.webp$/', '', $filename);
      $originalFile = null;
      $originalType = null;

      foreach (['.jpg', '.jpeg', '.png'] as $ext) {
        if (!file_exists($baseFile . $ext))
          continue;

        switch ($ext) {
          case '.jpg':
          case '.jpeg':
            $originalFile = $baseFile . $ext;
            $originalType = 'image/jpeg';
            break 2;

          case '.png':
            $originalFile = $baseFile . $ext;
            $originalType = 'image/png';
            break 2;

          case '.gif':
            $originalFile = $baseFile . $ext;
            $originalType = 'image/gif';
            break 2;
        }
      }

      // use $originalFile for size comparison
      if ($originalFile) {
        $webpSize = \filesize($filename);
        $originalSize = \filesize($originalFile);

        // Original is smaller, serve it instead
        if ($webpSize > $originalSize) {
          $this->serve($originalFile, $originalType);
          return;
        }
      }
    }

    // Step 2: Set headers
    header("Last-Modified: " . \gmdate("D, d M Y H:i:s", \filemtime($filename)) . " GMT");
    header('Content-Type: ' . $contentType);
    header('Cache-Control: public, max-age=31536000');
    header('Content-Length: ' . \filesize($filename));

    // Step 3: Clear any previous output
    if (\ob_get_level())
      \ob_end_clean();

    // Step 4: Output file in chunks to handle large files better
    $handle = fopen($filename, 'rb');
    if ($handle === false)
      throw new \RuntimeException('Could not open file for reading');

    while (!\feof($handle)) {
      $buffer = \fread($handle, 4096);
      if ($buffer === false) {
        fclose($handle);
        throw new \RuntimeException('Could not read file');
      }
      echo $buffer;
    }

    \fclose($handle);
    exit;
  }
}
