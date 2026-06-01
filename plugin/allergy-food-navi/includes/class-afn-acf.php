<?php

namespace AFN;

if (! defined('ABSPATH')) {
    exit;
}

final class ACF
{
    /**
     * @param int|string|null $post_id
     * @param mixed $default
     * @return mixed
     */
    public static function get_field(string $field_name, $post_id = null, $default = null)
    {
        if (function_exists('get_field')) {
            $value = get_field($field_name, $post_id);
            return $value !== null ? $value : $default;
        }

        if ($post_id) {
            $meta = get_post_meta((int) $post_id, $field_name, true);
            return $meta !== '' ? $meta : $default;
        }

        return $default;
    }

    /**
     * @param mixed $value
     * @param int|string $post_id
     */
    public static function update_field(string $field_name, $value, $post_id): void
    {
        if (function_exists('update_field')) {
            update_field($field_name, $value, $post_id);
            return;
        }

        update_post_meta((int) $post_id, $field_name, $value);
    }
}
