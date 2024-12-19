<?php

namespace Mrfsrf\WpWebpAutoConvert;

class Cwebp
{
  private static array $options;

  public static function init(array $options)
  {
    self::$options = $options;
  }

  public static function build_cwebp_command(
    string $source_path, 
    string $webp_path,
    string $image_type): string
  {
    $mime_type = str_replace('image/', '', $image_type);
     if (!isset(self::$options[$mime_type]))
      throw new \InvalidArgumentException("Unsupported image type: $image_type");

    $opts = self::$options[$mime_type];
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
    error_log('command parts ' . print_r($command_parts, true));
    return implode(' ', $command_parts);
  }

  static function execute_cwebp_command(string $command): void
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
}
