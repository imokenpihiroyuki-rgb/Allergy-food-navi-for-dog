<?php

namespace AFN;

if (! defined('ABSPATH')) {
    exit;
}

final class Redirect
{
    public static function init(): void
    {
        add_action('template_redirect', [__CLASS__, 'maybe_redirect_qr'], 0);
    }

    public static function maybe_redirect_qr(): void
    {
        if (! isset($_GET['rf_go'])) {
            return;
        }

        $post_id = isset($_GET['pid']) ? (int) $_GET['pid'] : 0;
        $to = isset($_GET['to']) ? sanitize_key((string) wp_unslash($_GET['to'])) : '';

        if ($post_id <= 0 || $to === '') {
            wp_die('Link not found');
        }

        $map = [
            'official' => 'product_url',
            'petline' => 'petline_link',
            'rc' => 'rc_certified_link',
            'amazon' => 'aff_amazon',
            'rakuten' => 'aff_rakuten',
            'yahoo' => 'aff_yahoo',
        ];

        if (! isset($map[$to])) {
            wp_die('Link not found');
        }

        $url = Utils::normalize_url(ACF::get_field($map[$to], $post_id, ''));
        if ($url === '') {
            wp_die('Link not found');
        }

        if (strpos($url, '//') === 0) {
            $url = 'https:' . $url;
        }

        $url = (string) esc_url_raw($url);
        if ($url === '') {
            wp_die('Link not found');
        }

        nocache_headers();
        wp_redirect($url, 302);
        exit;
    }
}
