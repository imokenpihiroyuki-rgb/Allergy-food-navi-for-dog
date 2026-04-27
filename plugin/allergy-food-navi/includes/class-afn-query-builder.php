<?php

namespace AFN;

if (! defined('ABSPATH')) {
    exit;
}

final class Query_Builder
{
    public static function build_from_request(bool $for_pdf = false): array
    {
        $args = [
            'post_type' => Config::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => $for_pdf ? -1 : 24,
            'paged' => $for_pdf ? 1 : max(1, get_query_var('paged') ?: (int) (Utils::request('paged', 1))),
        ];

        $proteins = Utils::request('protein', []);
        $hp = Utils::request('hp', '');
        $oi = Utils::request('oi', '');
        $makers = Utils::request('maker', []);
        $sort = Utils::request('sort', '');

        $meta = ['relation' => 'AND'];

        if (! empty($proteins)) {
            foreach ($proteins as $value) {
                $needle = '"' . $value . '"';
                $meta[] = ['key' => Config::KEY_PROTEIN, 'value' => $needle, 'compare' => 'NOT LIKE'];
                if ($oi === '1') {
                    $meta[] = ['key' => Config::KEY_OTHER_ING, 'value' => $needle, 'compare' => 'NOT LIKE'];
                }
                if ($hp === '1') {
                    $meta[] = ['key' => Config::KEY_HYDROLYZED, 'value' => $needle, 'compare' => 'NOT LIKE'];
                }
            }
        }

        if (! empty($makers)) {
            $or = ['relation' => 'OR'];
            foreach ($makers as $maker) {
                $or[] = ['key' => Config::KEY_MAKER, 'value' => sanitize_text_field($maker), 'compare' => 'LIKE'];
            }
            $meta[] = $or;
        }

        if (count($meta) > 1) {
            $args['meta_query'] = $meta;
        }

        if ($sort === 'hydro_first') {
            if (empty($args['meta_query'])) {
                $args['meta_query'] = ['relation' => 'AND'];
            }
            $args['meta_query']['hydro_flag_clause'] = [
                'key' => Config::KEY_HYDRO_FLAG,
                'type' => 'NUMERIC',
            ];
            $args['orderby'] = [
                'hydro_flag_clause' => 'DESC',
                'date' => 'DESC',
            ];

            return $args;
        }

        self::apply_maker_order($args);

        return $args;
    }

    public static function get_search_conditions(): array
    {
        return [
            'protein' => Utils::request('protein', []),
            'hp' => Utils::request('hp', ''),
            'oi' => Utils::request('oi', ''),
            'maker' => Utils::request('maker', []),
            'sort' => Utils::request('sort', ''),
            'view' => Utils::request('view', 'card'),
            'pdf_show_ec' => Utils::request('pdf_show_ec', ''),
        ];
    }

    private static function apply_maker_order(array &$args): void
    {
        $tmp = new \WP_Query(array_merge($args, ['fields' => 'ids', 'posts_per_page' => -1]));
        $ids = $tmp->posts;

        if (empty($ids)) {
            return;
        }

        $buckets = array_fill_keys(Config::MAKER_ORDER, []);
        $others = 'その他のメーカー';

        foreach ($ids as $post_id) {
            $maker = ACF::get_field(Config::KEY_MAKER, $post_id, '');
            if (is_array($maker)) {
                $maker = (string) (reset($maker) ?: '');
            }

            $label = $maker !== '' ? $maker : $others;
            if (! in_array($label, Config::MAKER_ORDER, true)) {
                $label = $others;
            }

            $buckets[$label][] = $post_id;
        }

        $sorted_ids = [];
        foreach (Config::MAKER_ORDER as $maker_name) {
            foreach ($buckets[$maker_name] as $post_id) {
                $sorted_ids[] = $post_id;
            }
        }

        if (! empty($sorted_ids)) {
            $args['post__in'] = $sorted_ids;
            $args['orderby'] = 'post__in';
        }
    }
}
