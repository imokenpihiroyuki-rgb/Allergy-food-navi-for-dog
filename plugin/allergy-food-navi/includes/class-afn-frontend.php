<?php

namespace AFN;

if (! defined('ABSPATH')) {
    exit;
}

final class Frontend
{
    public static function init(): void
    {
        add_filter('the_content', [__CLASS__, 'append_results'], 12);
    }

    public static function append_results(string $content): string
    {
        if (! Request_Context::should_handle_front_query()) {
            return $content;
        }

        if (! self::is_target_page() || ! self::has_search_params()) {
            return $content;
        }

        $query = new \WP_Query(Query_Builder::build_from_request(false));

        ob_start();
        echo '<section id="rf-results" class="rf-results">';
        echo '<h2>検索結果</h2>';

        self::render_summary();

        if ($query->have_posts()) {
            echo '<ul class="rf-results__simple">';
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $title = get_the_title();
                $maker = self::maker_label((int) $post_id);
                echo '<li>' . esc_html($title . '（' . $maker . '）') . '</li>';
            }
            echo '</ul>';

            echo '<nav class="rf-pagination">';
            echo wp_kses_post(paginate_links([
                'current' => max(1, (int) get_query_var('paged')),
                'total' => (int) $query->max_num_pages,
                'prev_text' => '← 前へ',
                'next_text' => '次へ →',
            ]));
            echo '</nav>';
        } else {
            echo '<p class="rf-empty">該当するフードが見つかりませんでした。</p>';
        }

        echo '</section>';

        wp_reset_postdata();

        return $content . ob_get_clean();
    }

    private static function render_summary(): void
    {
        $conditions = Query_Builder::get_search_conditions();

        $proteins = (array) ($conditions['protein'] ?? []);
        $makers = (array) ($conditions['maker'] ?? []);
        $hp = (string) ($conditions['hp'] ?? '');
        $oi = (string) ($conditions['oi'] ?? '');
        $sort = (string) ($conditions['sort'] ?? '');

        $options = [];
        if ($hp === '1') {
            $options[] = '加水分解タンパク質も除外';
        }
        if ($oi === '1') {
            $options[] = 'タンパク質以外の原材料も除外';
        }

        echo '<div class="rf-summary">';
        echo '<div><strong>除外アレルゲン:</strong> ' . esc_html($proteins ? implode('、', $proteins) : '（指定なし）') . '</div>';
        echo '<div><strong>除外オプション:</strong> ' . esc_html($options ? implode('・', $options) : '（指定なし）') . '</div>';
        echo '<div><strong>メーカー:</strong> ' . esc_html($makers ? implode('、', $makers) : '（指定なし）') . '</div>';
        echo '<div><strong>並び替え:</strong> ' . esc_html($sort === 'hydro_first' ? '加水分解フードを先頭' : 'メーカー順（おすすめ）') . '</div>';
        echo '</div>';
    }

    private static function maker_label(int $post_id): string
    {
        $maker = ACF::get_field(Config::KEY_MAKER, $post_id, '');
        if (is_array($maker)) {
            $maker = (string) (reset($maker) ?: '');
        }

        if ($maker === '' || ! in_array($maker, Config::MAKER_ORDER, true)) {
            return 'その他のメーカー';
        }

        return $maker;
    }

    private static function is_target_page(): bool
    {
        return is_page(Config::PAGE_SLUG);
    }

    private static function has_search_params(): bool
    {
        foreach (['protein', 'hp', 'oi', 'maker', 'sort', 'view', 'paged', 'pdf', 'pdf_show_ec'] as $key) {
            if (isset($_GET[$key])) {
                return true;
            }
        }

        return false;
    }
}
