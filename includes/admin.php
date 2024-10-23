<?php
class TeamAdmin
{
  public static function init()
  {
    $instance = new self();
    add_action('admin_menu', array($instance, 'addMenus'));
    add_action('admin_init', array($instance, 'registerSettings'));
    add_action('admin_enqueue_scripts', array($instance, 'enqueueAssets'));
  }

  public function addMenus()
  {
    add_menu_page(
      'Team Members',
      'Team Members',
      'manage_options',
      'manage-team',
      array($this, 'renderPage'),
      'dashicons-groups',
      71
    );
  }

  public function enqueueAssets($hook)
  {
    if ('toplevel_page_manage-team' === $hook) {
      wp_enqueue_style(
        'team-members-admin',
        TEAM_PLUGIN_URL . 'assets/css/admin.css',
        array(),
        '1.1'
      );
    }
  }

  public function registerSettings()
  {
    register_setting('team_titles_options', 'team_member_titles');
  }

  public function renderPage()
  {
    include TEAM_PLUGIN_PATH . 'includes/templates/admin-page.php';
  }
}
