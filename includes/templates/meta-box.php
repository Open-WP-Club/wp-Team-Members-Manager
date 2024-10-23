<?php
$name = get_post_meta($post->ID, '_team_member_name', true);
$email = get_post_meta($post->ID, '_team_member_email', true);
$title = get_post_meta($post->ID, '_team_member_title', true);
$website = get_post_meta($post->ID, '_team_member_website', true);
$titles = array_filter(explode("\n", get_option('team_member_titles', '')));
?>

<div class="team-member-meta">
  <p>
    <label><strong>Full Name:</strong></label><br>
    <input type="text" name="team_member_name" value="<?php echo esc_attr($name); ?>" required>
  </p>

  <p>
    <label><strong>Title:</strong></label><br>
    <select name="team_member_title" required>
      <option value="">Select Title</option>
      <?php foreach ($titles as $t): ?>
        <option value="<?php echo esc_attr($t); ?>" <?php selected($title, $t); ?>>
          <?php echo esc_html($t); ?>
        </option>
      <?php endforeach; ?>
    </select>
  </p>

  <p>
    <label><strong>Email:</strong></label><br>
    <input type="email" name="team_member_email" value="<?php echo esc_attr($email); ?>">
  </p>

  <p>
    <label><strong>Website:</strong></label><br>
    <input type="url" name="team_member_website" value="<?php echo esc_url($website); ?>">
  </p>
</div>