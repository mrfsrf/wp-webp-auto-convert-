<?php

namespace Mrfsrf\WpWebpAutoConvert\Helper;

use \Throwable;

Class Notif 
{
  public static function wp_admin(Throwable $e, string $message): void
  {
    \add_action('admin_notices', function () use ($e, $message): void
    {
      \printf(
        '<div class="error"><p>%s</p></div>',
        \esc_html(\sprintf('%s: %s', $message, $e->getMessage()))
      );
    });
  }
}
