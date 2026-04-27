<?php

namespace AFN;

if (! defined('ABSPATH')) {
    exit;
}

final class Bootstrap
{
    public static function init(): void
    {
        add_action('plugins_loaded', [__CLASS__, 'on_plugins_loaded']);
    }

    public static function on_plugins_loaded(): void
    {
        Frontend::init();
        Redirect::init();

        if (is_admin()) {
            Quick_Edit::init();
        }
    }
}
