<?php

/**
 * Plugin Name: Shipping REST Search
 * Description: /wp-json/shipping/v1/search で product を絞り込み検索（meta+taxonomy）。Productsプラグインと同一仕様。
 * Version: 0.3.2
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', function () {
    register_rest_route('shipping/v1', '/search', [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'muu_shipping_rest_search',
        'permission_callback' => '__return_true',
        'args'                => [
            'page'   => ['type' => 'integer', 'default' => 1],
            'per_page' => ['type' => 'integer', 'default' => 20],
            'orderby'  => ['type' => 'string', 'default' => 'date'],
            'order'    => ['type' => 'string', 'default' => 'desc'],
            // フィルタ
            'product_category'        => ['type' => 'string', 'required' => false],
            'child_category'          => ['type' => 'string', 'required' => false], // 互換
            'product_sheet'           => ['required' => false], // ID or CSV
            'brand_slug'              => ['type' => 'string', 'required' => false],
            'shipping_actual_yen_max' => ['type' => 'integer', 'required' => false],
            'weight_g_max'            => ['type' => 'integer', 'required' => false],
            'applied_weight_g_max'    => ['type' => 'integer', 'required' => false],
            '_embed'                  => ['type' => 'boolean', 'required' => false],
        ],
    ]);
});

function muu_shipping_rest_search(WP_REST_Request $req)
{
    $page     = max(1, (int) $req->get_param('page'));
    $per_page = max(1, min(100, (int) $req->get_param('per_page')));

    $args = [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'paged'          => $page,
        'posts_per_page' => $per_page,
        'orderby'        => sanitize_key($req->get_param('orderby') ?: 'date'),
        'order'          => strtoupper($req->get_param('order') ?: 'DESC') === 'ASC' ? 'ASC' : 'DESC',
        'meta_query'     => [],
        'tax_query'      => [],
    ];

    // --- 互換: child_category -> product_category ----------------------------
    if ($req['child_category'] && empty($req['product_category'])) {
        $req['product_category'] = $req['child_category'];
    }

    // meta: product_category（OR）
    $pc = $req->get_param('product_category');
    if (!empty($pc)) {
        $vals = array_values(array_filter(array_map(
            'sanitize_key',
            is_array($pc) ? $pc : array_map('trim', explode(',', (string)$pc))
        )));
        if ($vals) {
            $or = ['relation' => 'OR'];
            foreach ($vals as $v) {
                $or[] = ['key' => 'product_category', 'value' => $v, 'compare' => '='];
            }
            $args['meta_query'][] = $or;
        }
    }

    // taxonomy: product_sheet（brand_slug / product_sheet）
    $brand_ids = [];

    // brand_slug
    $bs = $req->get_param('brand_slug');
    if (!empty($bs)) {
        $slugs = array_filter(array_map('trim', explode(',', (string)$bs)));
        foreach ($slugs as $s) {
            $t = get_term_by('slug', sanitize_title($s), 'product_sheet');
            if ($t && !is_wp_error($t)) $brand_ids[] = (int)$t->term_id;
        }
    }

    // product_sheet（ID/スラッグ/CSV/配列を緩く受ける）
    $ps = $req->get_param('product_sheet');
    if (!empty($ps)) {
        $vals = is_array($ps) ? $ps : array_map('trim', explode(',', (string)$ps));
        foreach ($vals as $v) {
            if ($v === '' || $v === null) continue;
            if (is_numeric($v)) {
                $t = get_term((int)$v, 'product_sheet');
            } else {
                $t = get_term_by('slug', sanitize_title($v), 'product_sheet');
            }
            if ($t && !is_wp_error($t)) $brand_ids[] = (int)$t->term_id;
        }
    }

    if ($brand_ids) {
        $args['tax_query'][] = [
            'taxonomy'         => 'product_sheet',
            'field'            => 'term_id',
            'terms'            => array_values(array_unique($brand_ids)),
            'include_children' => true,
            'operator'         => 'IN',
        ];
    }

    // 数値 max（AND）
    $maxes = [
        'shipping_actual_yen_max' => 'shipping_actual_yen',
        'weight_g_max'            => 'weight_g',
        'applied_weight_g_max'    => 'applied_weight_g',
    ];
    foreach ($maxes as $param => $meta_key) {
        $v = $req->get_param($param);
        if ($v !== null && $v !== '') {
            $args['meta_query'][] = [
                'key'     => $meta_key,
                'value'   => (int)$v,
                'compare' => '<=',
                'type'    => 'NUMERIC',
            ];
        }
    }

    // ★ここに追加
    $args = apply_filters('rest_product_query', $args, $req);

    // WP_Query 実行
    add_filter('posts_clauses', 'muu_shipping_distinct_posts_clauses', 10, 2);
    $q = new WP_Query($args);
    remove_filter('posts_clauses', 'muu_shipping_distinct_posts_clauses', 10);

    // 一致する WP REST の形で各 post を整形（WP_REST_Posts_Controller を利用）
    $ctrl = new WP_REST_Posts_Controller('product');
    $items = [];
    foreach ($q->posts as $post) {
        $resp = $ctrl->prepare_item_for_response($post, $req);
        $data = $ctrl->prepare_response_for_collection($resp);
        // 互換: product_category -> child_category ミラー
        if (isset($data['meta']['product_category']) && !isset($data['meta']['child_category'])) {
            $data['meta']['child_category'] = $data['meta']['product_category'];
        }
        $items[] = $data;
    }

    $total       = (int) $q->found_posts;
    $total_pages = (int) ceil($total / max(1, $per_page));

    $payload = [
        'data' => $items,
        'meta' => [
            'total'       => $total,
            'total_pages' => $total_pages,
            'page'        => $page,
            'per_page'    => $per_page,
            'orderby'     => $args['orderby'],
            'order'       => $args['order'],
        ],
    ];

    // ヘッダ類（互換）
    $response = new WP_REST_Response($payload, 200);
    $response->header('X-WP-Total', (string)$total);
    $response->header('X-WP-TotalPages', (string)$total_pages);
    return $response;
}

function muu_shipping_distinct_posts_clauses($clauses, $wp_query)
{
    // meta_query+tax_query 併用時に重複防止
    $clauses['fields'] = 'DISTINCT ' . $clauses['fields'];
    return $clauses;
}
