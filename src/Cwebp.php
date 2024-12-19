<?php

namespace Mrfsrf\WpWebpAutoConvert;

class Cwebp
{
  private static array $options;
  private static array $flag_map = [
    'quality'       => 'q',
    'alpha'         => 'alpha_q',       // transparency-compression quality (0..100)
    'alpha-method'  => 'alpha_method',  // transparency-compression method (0..1), default=1
    'near-lossless' => 'near_lossless', // near-lossless image preprocessing (0..100=off), default=100
    'sharp-yuv'     => 'sharp_yuv',     // use sharper (and slower) RGB->YUV conversion
    'encoding'      => 'm',             // assuming encoding maps to method
    'm-threading'   => 'mt',            // use multi-threading if available
    'auto-limit'    => 'af',            // auto-adjust filter strength
    'spatial-noise' => 'sns',           // spatial noise shaping
    'sharpness'     => 'sharpness',     // spatial noise shaping
    'hint'          => 'hint',          // specify image characteristics (only one): photo, picture or graph
    'low-memory'    => 'low-memory',    // reduce memory usage (slower encoding)
    'exact'         => 'exact'          
    // Add any custom mappings here
    // 'my_custom_option' => 'custom_flag'
  ];


  public static function init(array $options)
  {
    self::$options = $options;
  }

  public static function build_cwebp_command(
    string $source_path,
    string $webp_path,
    string $image_type
  ): string {
    $mime_type = str_replace('image/', '', $image_type);
    if (!isset(self::$options[$mime_type]))
      throw new \InvalidArgumentException("Unsupported image type: $image_type");

    $opts = self::$options[$mime_type];
    $command_parts = ['cwebp'];

    // Map the options to cwebp flags
    foreach ($opts as $key => $value) {
      if (!isset(self::$flag_map[$key])) continue;
      $flag = self::$flag_map[$key];

      // Handle boolean flags
      if (is_bool($value)) {
        if ($value)
          $command_parts[] = "-$flag";
        continue;
      }

      // Handle numeric and string flags
      $command_parts[] = "-$flag $value";
    }

    $command_parts[] = escapeshellarg($source_path);
    $command_parts[] = "-o " . escapeshellarg($webp_path);
    // error_log('command parts ' . print_r($command_parts, true));
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
