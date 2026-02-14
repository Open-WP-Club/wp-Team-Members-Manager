<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

final class TeamCache
{
    public static function init(): void
    {
        add_action('save_post_team_member', [self::class, 'clearAll']);
        add_action('deleted_post', [self::class, 'clearOnDelete']);
        add_action('edit_term', [self::class, 'clearAll']);
        add_action('delete_term', [self::class, 'clearAll']);
        add_action('update_option_team_members_per_row', [self::class, 'clearAll']);
        add_action('update_option_team_member_gap', [self::class, 'clearAll']);
    }

    public static function clearAll(): void
    {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $wpdb->esc_like('_transient_' . TEAM_CACHE_KEY) . '%'
            )
        );
    }

    public static function clearOnDelete(int $post_id): void
    {
        if (get_post_type($post_id) === 'team_member') {
            self::clearAll();
        }
    }

    public static function get(string $key = ''): mixed
    {
        return get_transient(TEAM_CACHE_KEY . $key);
    }

    public static function set(string $content, string $key = ''): void
    {
        set_transient(TEAM_CACHE_KEY . $key, $content, DAY_IN_SECONDS);
    }
}
