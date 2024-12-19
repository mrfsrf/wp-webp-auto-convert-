<?php
// TODO
namespace Mrfsrf\WpWebpAutoConvert\Helper;

use Timber\Image\Operation as ImageOperation;

class CWebpConverter extends ImageOperation
{
  // private $quality;

  // public function __construct($quality) {
  //     $this->quality = $quality;
  // }

  public function filename($src_filename, $src_extension = 'webp')
  {
    // return $src_filename . '.webp';
  }

  public function run($load_filename, $save_filename)
  {
  // Check if cwebp is available
  //   $cwebp_path = shell_exec('which cwebp');
  //   if (empty($cwebp_path)) {
  //     return false;
  //   }

  //   $command = sprintf(
  //     '%s -q %d %s -o %s',
  //     trim($cwebp_path),
  //     $this->quality,
  //     escapeshellarg($load_filename),
  //     escapeshellarg($save_filename)
  //   );

  //   exec($command, $output, $return_var);
  //   return $return_var === 0;
  // }
}
