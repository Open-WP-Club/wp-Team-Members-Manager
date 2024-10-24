<?php
class TeamCache
{
  public static function init()
  {
    $instance = new self();
    add_action('save_post', array($instance, 'clearCache'));
    add_action('deleted_post', array($instance, 'clearCache'));
    add_action('edit_term', array($instance, 'clearCache'));
    add_action('delete_term', array($instance, 'clearCache'));
    add_action('update_option_team_members_per_row', array($instance, 'clearCache'));
    add_action('update_option_team_member_gap', array($instance, 'clearCache'));
  }

  public function clearCache($post_id = null)
  {
    if ($post_id && get_post_type($post_id) !== 'team_member') {
      return;
    }
    delete_transient(TEAM_CACHE_KEY);
  }

  public static function get()
  {
    return get_transient(TEAM_CACHE_KEY);
  }

  public static function set($content)
  {
    set_transient(TEAM_CACHE_KEY, $content, DAY_IN_SECONDS);
  }
}
