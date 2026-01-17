<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

final class TeamCache
{
    public static function init(): void
    {
        add_action('save_post_team_member', [self::class, 'clearCache']);
        add_action('deleted_post', [self::class, 'clearCacheOnDelete']);
        add_action('edit_term', [self::class, 'clearCache']);
        add_action('delete_term', [self::class, 'clearCache']);
        add_action('update_option_team_members_per_row', [self::class, 'clearCache']);
        add_action('update_option_team_member_gap', [self::class, 'clearCache']);
    }

    public static function clearCache(): void
    {
        delete_transient(TEAM_CACHE_KEY);
    }

    public static function clearCacheOnDelete(int $post_id): void
    {
        if (get_post_type($post_id) === 'team_member') {
            self::clearCache();
        }
    }

    public static function get(): mixed
    {
        return get_transient(TEAM_CACHE_KEY);
    }

    public static function set(string $content): void
    {
        set_transient(TEAM_CACHE_KEY, $content, DAY_IN_SECONDS);
    }
}
