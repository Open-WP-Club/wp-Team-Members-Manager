<?php
class TeamMeta
{
  public static function init()
  {
    $instance = new self();
    add_action('add_meta_boxes', array($instance, 'addMetaBox'));
    add_action('save_post', array($instance, 'saveData'));
  }

  public function addMetaBox()
  {
    add_meta_box(
      'team_member_details',
      'Team Member Details',
      array($this, 'renderMetaBox'),
      'team_member',
      'normal',
      'high'
    );
  }

  public function renderMetaBox($post)
  {
    wp_nonce_field('team_member_meta', 'team_member_nonce');
    include TEAM_PLUGIN_PATH . 'includes/templates/meta-box.php';
  }

  public function saveData($post_id)
  {
    if (
      !isset($_POST['team_member_nonce']) ||
      !wp_verify_nonce($_POST['team_member_nonce'], 'team_member_meta')
    ) {
      return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
      return;
    }

    $fields = array(
      'team_member_name' => 'sanitize_text_field',
      'team_member_email' => 'sanitize_email',
      'team_member_website' => 'esc_url_raw'
    );

    foreach ($fields as $field => $sanitize) {
      if (isset($_POST[$field])) {
        update_post_meta($post_id, '_' . $field, $sanitize($_POST[$field]));
      }
    }

    // Save departments
    if (isset($_POST['team_member_department'])) {
      $departments = array_map('absint', $_POST['team_member_department']);
      wp_set_object_terms($post_id, $departments, 'team_department');
    }
  }
}
