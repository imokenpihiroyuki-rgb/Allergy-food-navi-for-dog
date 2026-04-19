<?php

namespace AFN;

if (! defined('ABSPATH')) {
    exit;
}

final class Utils
{
    /**
     * @param mixed $default
     * @return mixed
     */
    public static function request(string $key, $default = '')
    {
        if (! isset($_GET[$key])) {
            return $default;
        }

        $value = wp_unslash($_GET[$key]);

        if (is_array($value)) {
            $clean = array_map('sanitize_text_field', $value);
            return array_values(array_filter($clean, static function ($item) {
                return $item !== '';
            }));
        }

        return sanitize_text_field((string) $value);
    }

    public static function normalize_url($value): string
    {
        if (is_array($value)) {
            $value = $value['url'] ?? '';
        }

        $value = str_replace(["\r", "\n", "\t", '　'], '', (string) $value);
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        return (string) esc_url_raw($value);
    }
}
