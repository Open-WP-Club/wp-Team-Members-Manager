<?php

declare(strict_types=1);

defined('WP_UNINSTALL_PLUGIN') || exit;

// Remove all team member posts and their meta
$posts = get_posts([
    'post_type'      => 'team_member',
    'posts_per_page' => -1,
    'post_status'    => 'any',
    'fields'         => 'ids',
]);

foreach ($posts as $post_id) {
    wp_delete_post($post_id, true);
}

// Remove taxonomy terms
$terms = get_terms([
    'taxonomy'   => 'team_department',
    'hide_empty' => false,
    'fields'     => 'ids',
]);

if (!is_wp_error($terms)) {
    foreach ($terms as $term_id) {
        wp_delete_term($term_id, 'team_department');
    }
}

// Remove options
delete_option('team_members_per_row');
delete_option('team_member_gap');

// Remove transient cache
delete_transient('team_members_cache');
