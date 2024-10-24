<?php
/*
Plugin Name: WP Team Members Manager
Plugin URI: https://openwpclub.com
Description: Manages and displays team members with customizable titles
Version: 1.0
Author: OpenWPClub.com
License: GPL v2 or later
Text Domain: team-members-manager
*/

if (!defined('ABSPATH')) {
  exit;
}

// Define plugin constants
define('TEAM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('TEAM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TEAM_CACHE_KEY', 'team_members_cache');

// Include required files
require_once TEAM_PLUGIN_PATH . 'includes/admin.php';
require_once TEAM_PLUGIN_PATH . 'includes/cache.php';
require_once TEAM_PLUGIN_PATH . 'includes/meta.php';
require_once TEAM_PLUGIN_PATH . 'includes/shortcode.php';
require_once TEAM_PLUGIN_PATH . 'includes/post-type-templates.php';

// Initialize the plugin
class TeamMembers
{
  private static $instance = null;

  public static function init()
  {
    if (self::$instance == null) {
      self::$instance = new TeamMembers();
    }
    return self::$instance;
  }

  private function __construct()
  {
    add_action('init', array($this, 'registerPostType'));
    add_action('wp_enqueue_scripts', array($this, 'enqueueFrontend'));
    add_action('template_redirect', array($this, 'restrictSingleView'));

    // Initialize components
    TeamAdmin::init();
    TeamCache::init();
    TeamMeta::init();
    TeamShortcode::init();
    TeamMemberTemplates::init();
  }

  public function registerPostType()
  {
    register_post_type('team_member', array(
      'labels' => array(
        'name' => 'Team Members',
        'singular_name' => 'Team Member',
        'add_new' => 'Add New Member',
        'add_new_item' => 'Add New Team Member',
        'edit_item' => 'Edit Team Member',
        'new_item' => 'New Team Member',
        'view_item' => 'View Team Member',
        'search_items' => 'Search Team Members',
        'not_found' => 'No team members found',
        'not_found_in_trash' => 'No team members found in trash'
      ),
      'public' => true,
      'publicly_queryable' => false,
      'show_ui' => true,
      'show_in_menu' => false,
      'supports' => array('title', 'thumbnail'),
      'has_archive' => false,
      'rewrite' => false
    ));
  }

  public function restrictSingleView()
  {
    if (is_singular('team_member')) {
      wp_redirect(home_url());
      exit;
    }
  }

  public function enqueueFrontend()
  {
    wp_enqueue_style(
      'team-members',
      TEAM_PLUGIN_URL . 'assets/css/team-members.css',
      array(),
      '1.1'
    );
  }
}

// Activation/Deactivation hooks
register_activation_hook(__FILE__, function () {
  // Create assets directories
  foreach (array('assets/css', 'assets/images') as $dir) {
    $path = TEAM_PLUGIN_PATH . $dir;
    if (!file_exists($path)) {
      mkdir($path, 0755, true);
    }
  }
  flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
  flush_rewrite_rules();
});

// Initialize plugin
add_action('plugins_loaded', array('TeamMembers', 'init'));
