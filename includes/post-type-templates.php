<?php
class TeamMemberTemplates
{
  public static function init()
  {
    $instance = new self();
    add_action('edit_form_top', array($instance, 'addNavigationToEditPage'));
  }

  public function addNavigationToEditPage($post)
  {
    if ($post->post_type === 'team_member') {
?>
      <div class="team-member-edit-header">
        <?php TeamAdmin::renderNavigation('new'); ?>
      </div>
<?php
    }
  }
}
