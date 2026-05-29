<?php

/**
 * Plugin Name: MUU Products
 * Description: Products CPT + REST meta + 管理シート taxonomy（親のみ）/ product_category は post meta で管理
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    // --- CPT -----------------------------------------------------------------
    register_post_type('product', [
        'label'        => 'Products',
        'public'       => false,
        'show_ui'      => true,
        'supports'     => ['title', 'thumbnail', 'custom-fields'],
        'show_in_rest' => true,
        'menu_icon'    => 'dashicons-products',
    ]);

    // --- メタ登録（既存項目） -----------------------------------------------
    $metas = [
        'cost'                => 'number',
        'length_cm'           => 'number',
        'width_cm'            => 'number',
        'height_cm'           => 'number',
        'weight_g'            => 'number',
        'carrier'             => 'string',
        'shipping_actual_yen' => 'number',
        'applied_weight_g'    => 'number',
        'amazon_size_label'   => 'string',
        'remark'              => 'string',
    ];
    foreach ($metas as $key => $type) {
        register_post_meta('product', $key, [
            'type'          => $type,
            'single'        => true,
            'show_in_rest'  => true,
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            },
        ]);
    }

    // --- 商品カテゴリ（共通の子カテゴリ）を meta として管理 -------------------
    // 例: meta.product_category = 'anime'
    register_post_meta('product', 'product_category', [
        'type'              => 'string',
        'single'            => true,
        'show_in_rest'      => true,
        'auth_callback'     => function () {
            return current_user_can('edit_posts');
        },
        'sanitize_callback' => function ($value) {
            // スラッグのみ許可（半角英数・ハイフン/アンダースコア）
            return is_string($value) ? sanitize_key($value) : '';
        },
    ]);

    // 後方互換: 旧UIの child_category を読む/書くクライアントがあっても壊さない
    register_post_meta('product', 'child_category', [
        'type'              => 'string',
        'single'            => true,
        'show_in_rest'      => true,
        'auth_callback'     => function () {
            return current_user_can('edit_posts');
        },
        'sanitize_callback' => function ($value) {
            return is_string($value) ? sanitize_key($value) : '';
        },
    ]);

    // --- 管理シート taxonomy（親タームのみ運用） ------------------------------
    register_taxonomy(
        'product_sheet',
        ['product'],
        [
            'label'             => '管理シート',
            'public'            => true,
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_in_rest'      => true,
            'show_admin_column' => true, // 一覧に列と絞り込み
            'query_var'         => 'product_sheet',
        ]
    );

    // 親ターム（存在しなければ作成）
    $parents = [
        'keln'      => 'ケルン用',
        'cocconiel' => 'コッコニール用',
        'signpost'  => 'サインポスト用',
    ];
    foreach ($parents as $slug => $name) {
        if (!term_exists($slug, 'product_sheet')) {
            wp_insert_term($name, 'product_sheet', ['slug' => $slug]);
        }
    }
});

/**
 * REST 検索・絞り込みの統合フィルタ（/wp-json/shipping/v1/search 経由想定）
 * - product_category: meta（OR）
 * - product_sheet: taxonomy（親指定で子を含む）
 * - 数値系: <= 比較（NUMERIC）
 * - 基本情報: id 完全一致 / q LIKE / carrier 完全一致 / amazon_size_label LIKE
 */
add_filter('rest_product_query', function ($args, $request) {
    /* ---------- 初期化 ---------- */
    if (empty($args['meta_query'])) $args['meta_query'] = [];
    if (empty($args['tax_query']))  $args['tax_query']  = [];

    /* ---------- 1) 商品カテゴリ（meta, OR 条件） ---------- */
    $catParam = $request->get_param('product_category');
    if (!empty($catParam)) {
        $cats = is_array($catParam) ? $catParam : explode(',', (string)$catParam);
        $cats = array_values(array_filter(array_map('sanitize_key', $cats)));
        if ($cats) {
            $or = ['relation' => 'OR'];
            foreach ($cats as $c) {
                $or[] = [
                    'key'     => 'product_category',
                    'value'   => $c,
                    'compare' => '=',
                ];
            }
            $args['meta_query'][] = $or;
        }
    }

    /* ---------- 2) 管理シート taxonomy（親→子含む） ---------- */
    $psParam = $request->get_param('product_sheet');
    if (!empty($psParam)) {
        $vals = is_array($psParam) ? $psParam : explode(',', (string)$psParam);
        $term_ids = [];
        foreach ($vals as $v) {
            $v = trim((string)$v);
            if ($v === '') continue;
            $term = is_numeric($v)
                ? get_term((int)$v, 'product_sheet')
                : get_term_by('slug', sanitize_title($v), 'product_sheet');
            if ($term && !is_wp_error($term)) {
                $term_ids[] = (int)$term->term_id;
            }
        }
        if ($term_ids) {
            $args['tax_query'][] = [
                'taxonomy'         => 'product_sheet',
                'field'            => 'term_id',
                'terms'            => $term_ids,
                'include_children' => true,
                'operator'         => 'IN',
            ];
        }
    }

    /* ---------- 3) 数値系フィルタ（<= / NUMERIC） ---------- */
    foreach (
        [
            'shipping_actual_yen' => 'shipping_actual_yen_max',
            'weight_g'            => 'weight_g_max',
            'applied_weight_g'    => 'applied_weight_g_max',
        ] as $metaKey => $paramKey
    ) {
        $val = $request->get_param($paramKey);
        if ($val !== null && $val !== '') {
            $args['meta_query'][] = [
                'key'     => $metaKey,
                'value'   => (int)$val,
                'compare' => '<=',
                'type'    => 'NUMERIC',
            ];
        }
    }

    /* ---------- 4) 基本情報（Laravel風 where 相当） ---------- */
    // ID 完全一致（CSV可）
    if ($id = $request->get_param('id')) {
        $args['post__in'] = array_map(
            'intval',
            preg_split('/\s*,\s*/', (string)$id, -1, PREG_SPLIT_NO_EMPTY)
        );
    }

    // 商品名 LIKE（WP標準 's'：タイトル/本文などに対する部分一致）
    if ($q = $request->get_param('q')) {
        $args['s'] = sanitize_text_field($q);
    }

    // 配送業者 完全一致
    if ($carrier = $request->get_param('carrier')) {
        $args['meta_query'][] = [
            'key'     => 'carrier',
            'value'   => sanitize_text_field($carrier),
            'compare' => '=',
        ];
    }

    // サイズラベル LIKE（部分一致）
    if ($asl = $request->get_param('amazon_size_label')) {
        $args['meta_query'][] = [
            'key'     => 'amazon_size_label',
            'value'   => $asl, // WP側で適切にエスケープされ LIKE '%...%' になる
            'compare' => 'LIKE',
        ];
    }

    /* ---------- 5) relation の整理 ---------- */
    if (!empty($args['meta_query']) && !isset($args['meta_query']['relation'])) {
        $args['meta_query']['relation'] = 'AND';
    }

    return $args;
}, 10, 2);

/**
 * 子タームが付いていた場合に親タームも自動付与（安全策）
 * ※ 現運用は親のみのはずだが、手動付与ミスの救済として有効化
 */
add_action('save_post_product', function ($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;
    if (get_post_status($post_id) === 'auto-draft') return;

    $terms = wp_get_post_terms($post_id, 'product_sheet', ['fields' => 'all']);
    if (empty($terms) || is_wp_error($terms)) return;

    $have_ids = [];
    foreach ($terms as $t) $have_ids[$t->term_id] = true;

    $add = [];
    foreach ($terms as $t) {
        $p = (int) $t->parent;
        while ($p) {
            if (empty($have_ids[$p])) {
                $add[]        = $p;
                $have_ids[$p] = true;
            }
            $parent_term = get_term($p, 'product_sheet');
            $p = ($parent_term && !is_wp_error($parent_term)) ? (int)$parent_term->parent : 0;
        }
    }

    if ($add) {
        static $running = false; // 再入防止
        if ($running) return;
        $running = true;

        $new_ids = array_map('intval', array_keys($have_ids)); // 既存＋親
        wp_set_object_terms($post_id, $new_ids, 'product_sheet', false);

        $running = false;
    }
}, 20);

/* ============================================================================
 *  （任意）メンテユーティリティ：必要時のみ有効化
 *  使用方法:
 *    1) wp-config.php に define('MUU_MAINT_MODE', true); を一時追加
 *    2) 管理者ログイン後に以下のURLを叩く
 *       - ?muu_migrate_children=1          … 旧子ターム→meta.product_category に移行
 *       - ?muu_preview_common_children=1   … 共通子スラッグの存在確認
 *       - ?muu_delete_common_children=1    … 共通子スラッグを物理削除
 *    3) 作業後は MUU_MAINT_MODE を外す/false に戻す
 * ========================================================================== */
if (defined('MUU_MAINT_MODE') && MUU_MAINT_MODE) {
    add_action('admin_init', function () {
        if (!is_user_logged_in() || !current_user_can('manage_options')) return;

        $tax = 'product_sheet';
        $child_slugs = [
            'game-console',
            'household',
            'toys',
            'electronics',
            'wristwatch',
            'fishing',
            'anime',
            'pokemon',
            'fashion',
            'other',
        ];

        // 子→meta 移行（冪等）
        if (isset($_GET['muu_migrate_children'])) {
            $q = new WP_Query([
                'post_type'      => 'product',
                'post_status'    => 'any',
                'posts_per_page' => -1,
                'fields'         => 'ids',
            ]);

            foreach ($q->posts as $pid) {
                // 付与ターム取得
                $terms = wp_get_post_terms($pid, $tax, ['fields' => 'all']);
                if (is_wp_error($terms)) continue;

                // 子ターム（上記スラッグ）なら meta に移行
                foreach ($terms as $t) {
                    if (in_array($t->slug, $child_slugs, true)) {
                        update_post_meta($pid, 'product_category', sanitize_key($t->slug));
                        update_post_meta($pid, 'child_category', sanitize_key($t->slug)); // 互換
                    }
                }

                // 親だけ残す
                $only_parents = [];
                foreach ($terms as $t) {
                    $p = (int) $t->parent;
                    if ($p === 0) {
                        $only_parents[] = $t->term_id; // すでに親
                    } else {
                        // 親を遡って最上位親を収集
                        while ($p) {
                            $parent_term = get_term($p, $tax);
                            if ($parent_term && !is_wp_error($parent_term)) {
                                $only_parents[] = $parent_term->term_id;
                                $p = (int) $parent_term->parent;
                            } else {
                                break;
                            }
                        }
                    }
                }
                $only_parents = array_values(array_unique(array_map('intval', $only_parents)));
                if ($only_parents) {
                    wp_set_object_terms($pid, $only_parents, $tax, false);
                }
            }
            wp_die(esc_html('migrate done: 子→meta 変換 & 親タームのみへ置換'));
        }

        // プレビュー
        if (isset($_GET['muu_preview_common_children'])) {
            $lines = [];
            foreach ($child_slugs as $slug) {
                $t = get_term_by('slug', $slug, $tax);
                if ($t && !is_wp_error($t)) {
                    $lines[] = "{$slug} (term_id={$t->term_id}, parent={$t->parent})";
                } else {
                    $lines[] = "{$slug} (not found)";
                }
            }
            wp_die(nl2br(esc_html(implode("\n", $lines))), 'Preview children');
        }

        // 一括削除
        if (isset($_GET['muu_delete_common_children'])) {
            $deleted = 0;
            $not_found = [];
            foreach ($child_slugs as $slug) {
                $t = get_term_by('slug', $slug, $tax);
                if ($t && !is_wp_error($t)) {
                    $r = wp_delete_term($t->term_id, $tax);
                    if (!is_wp_error($r)) {
                        $deleted++;
                    }
                } else {
                    $not_found[] = $slug;
                }
            }
            $msg = sprintf(
                'delete common children done: %d deleted / not found: %s',
                $deleted,
                $not_found ? implode(', ', $not_found) : 'none'
            );
            wp_die(esc_html($msg), 'Delete children');
        }
    });
}
