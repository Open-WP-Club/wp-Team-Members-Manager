<?php
$name = get_post_meta($post->ID, '_team_member_name', true);
$email = get_post_meta($post->ID, '_team_member_email', true);
$website = get_post_meta($post->ID, '_team_member_website', true);
?>
<div class="team-member-meta">
  <p>
    <label><strong>Full Name:</strong></label><br>
    <input type="text" name="team_member_name" value="<?php echo esc_attr($name); ?>" required style="width: 100%;">
  </p>

  <p>
    <label><strong>Department:</strong></label><br>
    <?php
    $departments = get_terms(array(
      'taxonomy' => 'team_department',
      'hide_empty' => false
    ));
    if (!empty($departments) && !is_wp_error($departments)) :
    ?>
      <select name="team_member_department[]" multiple required style="width: 100%;">
        <?php foreach ($departments as $department) : ?>
          <option value="<?php echo esc_attr($department->term_id); ?>"
            <?php echo has_term($department->term_id, 'team_department', $post->ID) ? 'selected' : ''; ?>>
            <?php echo esc_html($department->name); ?>
          </option>
        <?php endforeach; ?>
      </select>
    <?php else : ?>
  <p>No departments found. <a href="<?php echo admin_url('edit-tags.php?taxonomy=team_department&post_type=team_member'); ?>">Add departments first</a>.</p>
<?php endif; ?>
</p>

<p>
  <label><strong>Email:</strong></label><br>
  <input type="email" name="team_member_email" value="<?php echo esc_attr($email); ?>" style="width: 100%;">
</p>

<p>
  <label><strong>Website:</strong></label><br>
  <input type="url" name="team_member_website" value="<?php echo esc_url($website); ?>" style="width: 100%;">
</p>
</div>