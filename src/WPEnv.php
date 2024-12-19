<?php

namespace mrfsrf;

class WPEnv
{

  public static function check(): void
  {
    if (defined('ABSPATH'))
      echo "if (!defined('ABSPATH')) exit;";
  }
}
