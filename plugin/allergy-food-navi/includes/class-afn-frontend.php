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

        $conditions = Query_Builder::get_search_conditions();
        $view = self::normalize_view((string) ($conditions['view'] ?? 'card'));
        $query = new \WP_Query(Query_Builder::build_from_request(false));

        ob_start();
        echo '<section id="rf-results" class="rf-results">';
        echo '<h2>検索結果</h2>';

        self::render_summary($conditions);

        if ($query->have_posts()) {
            if ($view === 'table') {
                self::render_table_view($query);
            } elseif ($view === 'text') {
                self::render_text_view($query);
            } else {
                self::render_card_view($query);
            }

            self::render_pagination($query);
        } else {
            echo '<p class="rf-empty">該当するフードが見つかりませんでした。</p>';
        }

        echo '</section>';

        wp_reset_postdata();

        return $content . ob_get_clean();
    }

    private static function render_summary(array $conditions): void
    {
        $proteins = (array) ($conditions['protein'] ?? []);
        $makers = (array) ($conditions['maker'] ?? []);
        $hp = (string) ($conditions['hp'] ?? '');
        $oi = (string) ($conditions['oi'] ?? '');
        $sort = (string) ($conditions['sort'] ?? '');
        $view = self::normalize_view((string) ($conditions['view'] ?? 'card'));

        $options = [];
        if ($hp === '1') {
            $options[] = '加水分解タンパク質も除外';
        }
        if ($oi === '1') {
            $options[] = 'タンパク質以外の原材料も除外';
        }

        $view_labels = [
            'card' => 'カード表示',
            'table' => 'テーブル表示',
            'text' => 'テキスト表示',
        ];

        echo '<div class="rf-summary">';
        echo '<div><strong>除外アレルゲン:</strong> ' . esc_html($proteins ? implode('、', $proteins) : '（指定なし）') . '</div>';
        echo '<div><strong>除外オプション:</strong> ' . esc_html($options ? implode('・', $options) : '（指定なし）') . '</div>';
        echo '<div><strong>メーカー:</strong> ' . esc_html($makers ? implode('、', $makers) : '（指定なし）') . '</div>';
        echo '<div><strong>並び替え:</strong> ' . esc_html($sort === 'hydro_first' ? '加水分解フードを先頭' : 'メーカー順（おすすめ）') . '</div>';
        echo '<div><strong>表示形式:</strong> ' . esc_html($view_labels[$view]) . '</div>';
        echo '</div>';
    }

    private static function render_table_view(\WP_Query $query): void
    {
        echo '<div class="rf-table-wrap"><table class="rf-table"><thead><tr><th>フード</th><th>メーカー</th><th>主なタンパク質</th></tr></thead><tbody>';

        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $title = get_the_title();
            $main_protein = (string) ACF::get_field('main_protein', $post_id, '');
            echo '<tr>';
            echo '<td>' . esc_html($title) . '</td>';
            echo '<td>' . esc_html(self::maker_label((int) $post_id)) . '</td>';
            echo '<td>' . esc_html($main_protein !== '' ? $main_protein : '-') . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table></div>';
    }

    private static function render_text_view(\WP_Query $query): void
    {
        echo '<ul class="rf-results__text">';

        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $title = get_the_title();
            echo '<li>・' . esc_html($title . '（' . self::maker_label((int) $post_id) . '）') . '</li>';
        }

        echo '</ul>';
    }

    private static function render_card_view(\WP_Query $query): void
    {
        echo '<div class="rf-results__cards">';

        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $title = get_the_title();
            $maker = self::maker_label((int) $post_id);
            $main_protein = (string) ACF::get_field('main_protein', $post_id, '');

            echo '<article class="rf-card">';
            echo '<h3 class="rf-card__title">' . esc_html($title) . '</h3>';
            echo '<ul class="rf-card__meta">';
            echo '<li><strong>メーカー:</strong> ' . esc_html($maker) . '</li>';
            if ($main_protein !== '') {
                echo '<li><strong>主なタンパク質:</strong> ' . esc_html($main_protein) . '</li>';
            }
            echo '</ul>';
            echo '</article>';
        }

        echo '</div>';
    }

    private static function render_pagination(\WP_Query $query): void
    {
        echo '<nav class="rf-pagination">';
        echo wp_kses_post(paginate_links([
            'current' => max(1, (int) get_query_var('paged')),
            'total' => (int) $query->max_num_pages,
            'prev_text' => '← 前へ',
            'next_text' => '次へ →',
        ]));
        echo '</nav>';
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

    private static function normalize_view(string $view): string
    {
        $allowed = ['card', 'table', 'text'];
        return in_array($view, $allowed, true) ? $view : 'card';
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
