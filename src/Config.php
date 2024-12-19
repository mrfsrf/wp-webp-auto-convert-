<?php

namespace Mrfsrf\WpWebpAutoConvert;

class Config
{
  private array $config;
  private array $defaults = [
    'png' => [
      'encoding'      => 'auto',
      'near-lossless' => 60,
      'quality'       => 85,
      'sharp-yuv'     => true,
    ],
    'jpeg' => [
      'encoding'      => 'auto',
      'quality'       => 75,
      'auto-limit'    => true,
      'sharp-yuv'     => true,
    ]
  ];

  public function __construct(?array $config = null)
  {
    $this->config = $this->defaults;
    if ($config !== null) $this->merge($config);
  }

  private function merge(array $config): void
  {
    foreach ($config as $key => $value) {
      switch ($key) {
        case 'quality':
          $this->config['png']['quality'] = $value;
          $this->config['jpeg']['quality'] = $value;
          break;

        case 'sharp-yuv':
        case 'encoding':
          $this->config['png'][$key] = $value;
          $this->config['jpeg'][$key] = $value;
          break;

        case 'near-lossless':
          $this->config['png'][$key] = $value;
          break;

        case 'auto-limit':
          $this->config['jpeg'][$key] = $value;
          break;

        default:
          $this->config['png'][$key] = $value;
          $this->config['jpeg'][$key] = $value;
          break;
      }
    }
  }

  // public function get(): array
  // {
  //   return $this->config; 
  // }

  public function get_format_config(string $format): array
  {
    return $this->config[$format] ?? [];
  }
}
