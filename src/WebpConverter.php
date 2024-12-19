<?php

namespace Mrfsrf\WpWebpAutoConvert;

class WebPConverter 
{
    private AutoWebPConverter $converter;
    private ThumbWebp $thumb_handler;
    private ServeWebp $serve_handler;
    private DeleteWebp $delete_handler;

    public function __construct(?array $user_config = null)
    {
        $config = new Config($user_config);
        $this->converter = new AutoWebPConverter($config);
        $this->thumb_handler = new ThumbWebp();
        $this->serve_handler = new ServeWebp();
        $this->delete_handler = new DeleteWebp();
    }
}
