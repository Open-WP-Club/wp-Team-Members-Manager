<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

final class TeamMeta
{
    public static function init(): void
    {
        add_action('add_meta_boxes', [self::class, 'addMetaBox']);
        add_action('save_post_team_member', [self::class, 'saveData'], 10, 2);
    }

    public static function addMetaBox(): void
    {
        add_meta_box(
            'team_member_details',
            __('Team Member Details', 'wp-team-manager'),
            [self::class, 'renderMetaBox'],
            'team_member',
            'normal',
            'high'
        );
    }

    public static function renderMetaBox(\WP_Post $post): void
    {
        wp_nonce_field('team_member_meta', 'team_member_nonce');
        include TEAM_PLUGIN_PATH . 'includes/templates/meta-box.php';
    }

    public static function saveData(int $post_id, \WP_Post $post): void
    {
        if (!isset($_POST['team_member_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['team_member_nonce'], 'team_member_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $fields = [
            'team_member_name'    => 'sanitize_text_field',
            'team_member_email'   => 'sanitize_email',
            'team_member_website' => 'esc_url_raw',
        ];

        foreach ($fields as $field => $sanitize) {
            $value = isset($_POST[$field]) ? $sanitize($_POST[$field]) : '';
            update_post_meta($post_id, '_' . $field, $value);
        }

        if (isset($_POST['team_member_department']) && is_array($_POST['team_member_department'])) {
            $departments = array_map('absint', $_POST['team_member_department']);
            wp_set_object_terms($post_id, $departments, 'team_department');
        } else {
            wp_set_object_terms($post_id, [], 'team_department');
        }
    }
}
