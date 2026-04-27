<?php

namespace AFN;

if (! defined('ABSPATH')) {
    exit;
}

final class Request_Context
{
    /**
     * フロントメインクエリのみに限定。
     * ログイン/管理画面/REST/AJAX/cron では処理しない。
     */
    public static function should_handle_front_query(): bool
    {
        if (is_admin()) {
            return false;
        }

        if (wp_doing_ajax()) {
            return false;
        }

        if (defined('REST_REQUEST') && REST_REQUEST) {
            return false;
        }

        if (defined('DOING_CRON') && DOING_CRON) {
            return false;
        }

        if (self::is_login_request()) {
            return false;
        }

        return true;
    }

    public static function is_login_request(): bool
    {
        global $pagenow;

        return isset($pagenow) && $pagenow === 'wp-login.php';
    }
}
