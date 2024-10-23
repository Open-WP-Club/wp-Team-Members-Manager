<?php
class TeamShortcode
{
  public static function init()
  {
    $instance = new self();
    add_shortcode('team_members', array($instance, 'render'));
  }

  public function render($atts)
  {
    $cache = TeamCache::get();
    if ($cache !== false) {
      return $cache;
    }

    $members = get_posts(array(
      'post_type' => 'team_member',
      'posts_per_page' => -1,
      'orderby' => 'title',
      'order' => 'ASC'
    ));

    ob_start();
    include TEAM_PLUGIN_PATH . 'includes/templates/shortcode.php';
    $output = ob_get_clean();

    TeamCache::set($output);
    return $output;
  }
}
