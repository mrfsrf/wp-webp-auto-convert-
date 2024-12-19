<?php

// if (!defined('ABSPATH')) exit;

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
  add_action('admin_notices', function () {
    echo '<div class="error"><p>WebP Converter: vendor/autoload.php not found. Did you run composer install?</p></div>';
  });
  return;
}

require __DIR__ . '/vendor/autoload.php';

// new mrfsrf\AutoWebPConverter();

new mrfsrf\AutoWebPConverter('./placeholder.png');
