<?php
if (!defined('ABSPATH')) {
  exit;
}

// Get current filters
$current_department = isset($_GET['department']) ? absint($_GET['department']) : 0;

// Get all departments for filters
$departments = get_terms(array(
  'taxonomy' => 'team_department',
  'hide_empty' => false
));

// Get team members with filters
$args = array(
  'post_type' => 'team_member',
  'posts_per_page' => -1,
  'orderby' => 'menu_order title',
  'order' => 'ASC'
);

// Add taxonomy queries if filters are set
if ($current_department) {
  $args['tax_query'] = array(
    array(
      'taxonomy' => 'team_department',
      'terms' => $current_department
    )
  );
}

$team_members = get_posts($args);
?>

<div class="wrap team-members-admin">
  <h1 class="wp-heading-inline">Team Members</h1>
  <a href="<?php echo esc_url(admin_url('post-new.php?post_type=team_member')); ?>" class="page-title-action">
    Add New Member
  </a>
  <hr class="wp-header-end">

  <!-- Quick Actions -->
  <div class="team-quick-actions">
    <div class="quick-links">
      <a href="<?php echo esc_url(admin_url('users.php?page=edit-tags.php?taxonomy=team_department&post_type=team_member')); ?>" class="button">
        Manage Departments
      </a>
    </div>
    <div class="shortcode-info">
      <code>[team_members]</code>
      <span class="description">Use this shortcode to display team members</span>
    </div>
  </div>

  <!-- Filters -->
  <div class="team-filters">
    <form method="get" action="">
      <input type="hidden" name="page" value="team-members">

      <?php if (!is_wp_error($departments) && !empty($departments)): ?>
        <select name="department">
          <option value="">All Departments</option>
          <?php foreach ($departments as $dept): ?>
            <option value="<?php echo esc_attr($dept->term_id); ?>"
              <?php selected($current_department, $dept->term_id); ?>>
              <?php echo esc_html($dept->name); ?>
            </option>
          <?php endforeach; ?>
        </select>
      <?php endif; ?>

      <button type="submit" class="button">Filter</button>

      <?php if ($current_department): ?>
        <a href="<?php echo esc_url(admin_url('users.php?page=team-members')); ?>" class="button">
          Reset Filters
        </a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Team Members List -->
  <?php if ($team_members): ?>
    <table class="wp-list-table widefat fixed striped">
      <thead>
        <tr>
          <th class="column-image">Image</th>
          <th class="column-name">Name</th>
          <th class="column-department">Department</th>
          <th class="column-contact">Contact</th>
          <th class="column-actions">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($team_members as $member):
          $member_departments = wp_get_object_terms($member->ID, 'team_department');
          $email = get_post_meta($member->ID, '_team_member_email', true);
          $website = get_post_meta($member->ID, '_team_member_website', true);
          $name = get_post_meta($member->ID, '_team_member_name', true);
        ?>
          <tr>
            <td class="column-image">
              <?php if (has_post_thumbnail($member->ID)): ?>
                <?php echo get_the_post_thumbnail($member->ID, array(50, 50)); ?>
              <?php else: ?>
                <img src="<?php echo esc_url(TEAM_PLUGIN_URL . 'assets/images/default.svg'); ?>"
                  width="50" height="50" alt="Default Avatar">
              <?php endif; ?>
            </td>
            <td class="column-name">
              <strong>
                <a href="<?php echo get_edit_post_link($member->ID); ?>">
                  <?php echo esc_html($name); ?>
                </a>
              </strong>
            </td>
            <td class="column-department">
              <?php
              if (!is_wp_error($member_departments) && !empty($member_departments)) {
                $dept_names = wp_list_pluck($member_departments, 'name');
                echo esc_html(implode(', ', $dept_names));
              }
              ?>
            </td>
            <td class="column-contact">
              <?php if ($email): ?>
                <a href="mailto:<?php echo esc_attr($email); ?>" title="Email">
                  <span class="dashicons dashicons-email"></span>
                </a>
              <?php endif; ?>
              <?php if ($website): ?>
                <a href="<?php echo esc_url($website); ?>" target="_blank" title="Website">
                  <span class="dashicons dashicons-admin-links"></span>
                </a>
              <?php endif; ?>
            </td>
            <td class="column-actions">
              <a href="<?php echo get_edit_post_link($member->ID); ?>"
                class="button button-small" title="Edit">
                Edit
              </a>
              <a href="<?php echo get_delete_post_link($member->ID); ?>"
                class="button button-small"
                onclick="return confirm('Are you sure you want to delete this team member?')"
                title="Delete">
                Delete
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <div class="no-items">
      <p>No team members found. <a href="<?php echo esc_url(admin_url('post-new.php?post_type=team_member')); ?>">Add your first team member</a>.</p>
    </div>
  <?php endif; ?>
</div>