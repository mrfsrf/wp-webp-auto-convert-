<?php
use mrfsrf\WPEnv;
use mrfsrf\AutoWebPConverter;
use mrfsrf\ServeWebp;
use mrfsrf\ThumbWebp;

WPEnv::check();

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
  add_action('admin_notices', function () {
    echo '<div class="error">
          <p>WebP Converter: vendor/autoload.php not found.
          Did you run composer install?</p></div>';
  });
  return;
}

require __DIR__ . '/vendor/autoload.php';

new AutoWebPConverter();
new ThumbWebp();
new ServeWebp();
