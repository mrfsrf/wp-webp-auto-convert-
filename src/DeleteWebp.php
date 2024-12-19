<?php

namespace Mrfsrf\WpWebpAutoConvert;

class DeleteWebp extends AutoWebPConverter
{
  public function __construct()
  {
    \add_action('delete_attachment', [$this, 'delete_webp_files']);
  }

  public function delete_webp_files(int $attachment_id): void
  {
    $file = \get_attached_file($attachment_id);
    if (!$file) return;

    // Delete the WebP version of the main file
    $webp_path = $this->get_webp_path($file);
    if (file_exists($webp_path))
      @unlink($webp_path);

    // Get and delete WebP versions of all thumbnails
    $metadata = \wp_get_attachment_metadata($attachment_id);
    if (!empty($metadata['sizes'])) {
      $dir = pathinfo($file, PATHINFO_DIRNAME);

      foreach ($metadata['sizes'] as $size => $data) {
        if (isset($data['webp'])) {
          $thumb_webp = $dir . '/' . $data['webp'];
          if (file_exists($thumb_webp))
            @unlink($thumb_webp);
        }
      }
    }

  }
}
