<?php

declare(strict_types=1);

/**
 * Plugin Name: Team Member Manager for WordPress
 * Plugin URI: https://openwpclub.com
 * Description: Manages and displays team members with customizable layouts
 * Version: 2.0.0
 * Requires at least: 6.5
 * Requires PHP: 8.2
 * Author: OpenWPClub.com
 * License: GPL v2 or later
 * Text Domain: team-members-manager
 */

defined('ABSPATH') || exit;

define('TEAM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('TEAM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TEAM_CACHE_KEY', 'team_members_cache');
define('TEAM_VERSION', '2.0.0');

require_once TEAM_PLUGIN_PATH . 'includes/admin.php';
require_once TEAM_PLUGIN_PATH . 'includes/cache.php';
require_once TEAM_PLUGIN_PATH . 'includes/csv.php';
require_once TEAM_PLUGIN_PATH . 'includes/meta.php';
require_once TEAM_PLUGIN_PATH . 'includes/shortcode.php';

final class TeamMembers
{
    private static ?TeamMembers $instance = null;

    public static function init(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('init', [$this, 'registerPostType']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueFrontend']);
        add_action('template_redirect', [$this, 'restrictSingleView']);

        TeamAdmin::init();
        TeamCache::init();
        TeamCSV::init();
        TeamMeta::init();
        TeamShortcode::init();
    }

    public function registerPostType(): void
    {
        register_post_type('team_member', [
            'labels' => [
                'name'               => __('Team Members', 'team-members-manager'),
                'singular_name'      => __('Team Member', 'team-members-manager'),
                'add_new'            => __('Add New Member', 'team-members-manager'),
                'add_new_item'       => __('Add New Team Member', 'team-members-manager'),
                'edit_item'          => __('Edit Team Member', 'team-members-manager'),
                'new_item'           => __('New Team Member', 'team-members-manager'),
                'view_item'          => __('View Team Member', 'team-members-manager'),
                'search_items'       => __('Search Team Members', 'team-members-manager'),
                'not_found'          => __('No team members found', 'team-members-manager'),
                'not_found_in_trash' => __('No team members found in trash', 'team-members-manager'),
            ],
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'show_in_rest'       => true,
            'supports'           => ['title', 'thumbnail', 'custom-fields'],
            'has_archive'        => false,
            'rewrite'            => false,
        ]);
    }

    public function restrictSingleView(): void
    {
        if (is_singular('team_member')) {
            wp_safe_redirect(home_url());
            exit;
        }
    }

    public function enqueueFrontend(): void
    {
        wp_enqueue_style(
            'team-members',
            TEAM_PLUGIN_URL . 'assets/css/team-members.css',
            [],
            TEAM_VERSION
        );
    }
}

register_activation_hook(__FILE__, static function (): void {
    $dirs = ['assets/css', 'assets/images'];
    foreach ($dirs as $dir) {
        $path = TEAM_PLUGIN_PATH . $dir;
        if (!is_dir($path)) {
            wp_mkdir_p($path);
        }
    }
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, static function (): void {
    flush_rewrite_rules();
});

add_action('plugins_loaded', [TeamMembers::class, 'init']);
