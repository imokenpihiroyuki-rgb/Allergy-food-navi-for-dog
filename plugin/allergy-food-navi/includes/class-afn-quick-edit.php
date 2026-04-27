<?php

namespace AFN;

if (! defined('ABSPATH')) {
    exit;
}

final class Quick_Edit
{
    public static function init(): void
    {
        add_filter('manage_edit-' . Config::POST_TYPE . '_columns', [__CLASS__, 'add_column']);
        add_action('manage_' . Config::POST_TYPE . '_posts_custom_column', [__CLASS__, 'render_hidden_data'], 10, 2);
        add_action('quick_edit_custom_box', [__CLASS__, 'render_quick_edit_fields'], 10, 2);
        add_action('admin_print_footer_scripts-edit.php', [__CLASS__, 'print_quick_edit_script']);
        add_action('save_post_' . Config::POST_TYPE, [__CLASS__, 'save_quick_edit']);
    }

    public static function fields(): array
    {
        return [
            'product_url' => ['label' => '公式商品詳細', 'type' => 'url'],
            'official_buy_url' => ['label' => '公式（購入先）URL', 'type' => 'url'],
            'rc_certified_link' => ['label' => 'RC認定オンラインストア', 'type' => 'url'],
            'aff_amazon' => ['label' => 'Amazonアフィリエイト', 'type' => 'url'],
            'aff_rakuten' => ['label' => '楽天アフィリエイト', 'type' => 'url'],
            'aff_yahoo' => ['label' => 'Yahooアフィリエイト', 'type' => 'url'],
            'petline_link' => ['label' => 'どうぶつ病院宅配便リンク', 'type' => 'url'],
            'price' => ['label' => '参考価格（円）', 'type' => 'number'],
            'pack_size' => ['label' => '内容量・規格', 'type' => 'checkbox'],
        ];
    }

    public static function add_column(array $columns): array
    {
        $columns['afn_qe_edit'] = 'クイック編集';
        return $columns;
    }

    public static function render_hidden_data(string $column, int $post_id): void
    {
        if ($column !== 'afn_qe_edit') {
            return;
        }

        $attrs = [];
        foreach (self::fields() as $name => $def) {
            if ($def['type'] === 'checkbox') {
                $vals = ACF::get_field($name, $post_id, []);
                if (! is_array($vals)) {
                    $vals = (array) $vals;
                }
                $attrs[] = 'data-' . $name . '="' . esc_attr(wp_json_encode(array_values($vals))) . '"';
                continue;
            }

            $attrs[] = 'data-' . $name . '="' . esc_attr((string) ACF::get_field($name, $post_id, '')) . '"';
        }

        echo '<span class="afn-qe-data" ' . implode(' ', $attrs) . ' style="display:none;"></span>';
        echo '<em style="color:#777;">クイック編集で編集</em>';
    }

    public static function render_quick_edit_fields(string $column_name, string $post_type): void
    {
        if ($post_type !== Config::POST_TYPE || $column_name !== 'afn_qe_edit') {
            return;
        }

        wp_nonce_field('afn_qe_nonce', 'afn_qe_nonce');

        echo '<fieldset class="inline-edit-col-right"><div class="inline-edit-col">';
        foreach (self::fields() as $name => $def) {
            echo '<label class="afn-qe-field" style="display:block;margin:6px 0;">';
            echo '<span class="title" style="min-width:160px;display:inline-block;">' . esc_html($def['label']) . '</span>';
            echo '<span class="input-text-wrap" style="display:inline-block;min-width:60%;">';

            if ($def['type'] === 'checkbox') {
                foreach (self::pack_choices() as $value => $label) {
                    echo '<label style="margin-right:10px;display:inline-block;">';
                    echo '<input type="checkbox" name="afn_qe_' . esc_attr($name) . '[]" value="' . esc_attr($value) . '"> ' . esc_html($label);
                    echo '</label>';
                }
            } elseif ($def['type'] === 'number') {
                echo '<input type="number" step="0.01" min="0" name="afn_qe_' . esc_attr($name) . '" value="" style="width:100%;">';
            } else {
                echo '<input type="url" name="afn_qe_' . esc_attr($name) . '" value="" style="width:100%;">';
            }

            echo '</span></label>';
        }
        echo '</div></fieldset>';
    }

    public static function print_quick_edit_script(): void
    {
        $screen = get_current_screen();
        if (! $screen || $screen->post_type !== Config::POST_TYPE) {
            return;
        }

        $defs = self::fields();
        ?>
        <script>
        (function($){
          var defs = <?php echo wp_json_encode($defs); ?>;
          $(document).on('click', '.editinline', function(){
            var $row = $(this).closest('tr');
            var $data = $('.afn-qe-data', $row);
            var $qe = $('tr.inline-edit-row');
            Object.keys(defs).forEach(function(name){
              if (defs[name].type === 'checkbox') {
                var json = $data.attr('data-' + name) || '[]';
                var selected = [];
                try { selected = JSON.parse(json); } catch(e) { selected = []; }
                $qe.find('input[name="afn_qe_'+name+'[]"]').prop('checked', false);
                selected.forEach(function(v){
                  $qe.find('input[name="afn_qe_'+name+'[]"][value="'+v+'"]').prop('checked', true);
                });
              } else {
                $qe.find('input[name="afn_qe_'+name+'"]').val($data.data(name) || '');
              }
            });
          });
        })(jQuery);
        </script>
        <?php
    }

    public static function save_quick_edit(int $post_id): void
    {
        if (! isset($_POST['afn_qe_nonce']) || ! wp_verify_nonce(sanitize_text_field((string) wp_unslash($_POST['afn_qe_nonce'])), 'afn_qe_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (! current_user_can('edit_post', $post_id)) {
            return;
        }

        foreach (self::fields() as $name => $def) {
            $key = 'afn_qe_' . $name;

            if ($def['type'] === 'checkbox') {
                $vals = isset($_POST[$key]) ? array_map('sanitize_text_field', (array) wp_unslash($_POST[$key])) : [];
                ACF::update_field($name, $vals, $post_id);
                continue;
            }

            if (! array_key_exists($key, $_POST)) {
                continue;
            }

            $raw = (string) wp_unslash($_POST[$key]);
            if ($def['type'] === 'number') {
                $raw = preg_replace('/[^\d\.\-]/', '', trim($raw));
                $value = $raw === '' ? '' : (0 + $raw);
            } else {
                $value = esc_url_raw(trim($raw));
            }

            ACF::update_field($name, $value, $post_id);
        }
    }

    private static function pack_choices(): array
    {
        if (! function_exists('acf_get_field')) {
            return [];
        }

        $field = acf_get_field(Config::QUICK_EDIT_PACK_FIELD_KEY);
        if (! is_array($field) || empty($field['choices']) || ! is_array($field['choices'])) {
            return [];
        }

        return $field['choices'];
    }
}
