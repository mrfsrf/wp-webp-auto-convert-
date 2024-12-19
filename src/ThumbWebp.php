<?php

namespace Mrfsrf\WpWebpAutoConvert;

class ThumbWebp extends AutoWebPConverter
{
  public function __construct()
  {
    \add_filter('wp_generate_attachment_metadata', [$this, 'handle_thumbnails'], 10, 2); 
  }

  /** Handle thumbnail conversion. */
  public function handle_thumbnails(array $metadata, string $attachment_id): array
  {
    if (!isset($metadata['sizes'])) return $metadata;

    $file = \get_attached_file($attachment_id);
    $dir = pathinfo($file, PATHINFO_DIRNAME);
    
    foreach ($metadata['sizes'] as $size => $data) {
      $thumb_path = $dir . '/' . $data['file'];
      $webp_path  = $this->get_webp_path($thumb_path);
      $mime_type = str_replace('image/', '', $data['mime-type']);

      $this->create_webp_image($thumb_path, $webp_path, $data['mime-type']);
      // Add WebP version to metadata
      $metadata['sizes'][$size]['webp'] = basename($webp_path);
    }

    return $metadata;
  }
}
