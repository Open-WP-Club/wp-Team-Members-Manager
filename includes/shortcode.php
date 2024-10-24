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
    $members_per_row = get_option('team_members_per_row', '4');
    $gap = get_option('team_member_gap', '20');

    $custom_css = "
            <style>
                .team-members-grid {
                    --members-per-row: {$members_per_row};
                    --team-gap: {$gap}px;
                }
            </style>
        ";

    $cache = TeamCache::get();
    if ($cache !== false) {
      return $custom_css . $cache;
    }

    $args = array(
      'post_type' => 'team_member',
      'posts_per_page' => -1,
      'orderby' => 'menu_order title',
      'order' => 'ASC'
    );

    $team_members = get_posts($args);

    if (empty($team_members)) {
      return '<p>No team members found.</p>';
    }

    ob_start();
    echo $custom_css;
?>
    <div class="team-members-grid">
      <?php foreach ($team_members as $member): ?>
        <?php
        $name = get_post_meta($member->ID, '_team_member_name', true);
        $email = get_post_meta($member->ID, '_team_member_email', true);
        $website = get_post_meta($member->ID, '_team_member_website', true);
        $departments = wp_get_object_terms($member->ID, 'team_department');
        $department_names = !is_wp_error($departments) ? wp_list_pluck($departments, 'name') : array();
        ?>
        <div class="team-member">
          <?php if (has_post_thumbnail($member->ID)): ?>
            <?php echo get_the_post_thumbnail($member->ID, 'medium', array('class' => 'team-member-image')); ?>
          <?php else: ?>
            <div class="default-avatar">
              <img src="<?php echo TEAM_PLUGIN_URL; ?>assets/images/default.svg" alt="Default Avatar">
            </div>
          <?php endif; ?>

          <h3><?php echo esc_html($name); ?></h3>

          <?php if (!empty($department_names)): ?>
            <div class="department"><?php echo esc_html(implode(', ', $department_names)); ?></div>
          <?php endif; ?>

          <?php if ($email): ?>
            <div class="email">
              <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
            </div>
          <?php endif; ?>

          <?php if ($website): ?>
            <div class="website">
              <a href="<?php echo esc_url($website); ?>" target="_blank">Website</a>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
<?php
    $output = ob_get_clean();
    TeamCache::set($output);
    return $custom_css . $output;
  }
}
