<?php
class TeamAdmin
{
  public static function init()
  {
    $instance = new self();
    add_action('admin_menu', array($instance, 'addMenus'));
    add_action('admin_init', array($instance, 'registerSettings'));
    add_action('admin_enqueue_scripts', array($instance, 'enqueueAssets'));
    add_action('init', array($instance, 'registerDepartmentTaxonomy'));
    add_action('admin_notices', array($instance, 'addNavigationToTaxonomyPage'));
  }

  public function addMenus()
  {
    add_submenu_page(
      'users.php',
      'Team Members',
      'Team Members',
      'manage_options',
      'team-members',
      array($this, 'renderTeamPage')
    );
  }

  public function registerSettings()
  {
    register_setting('team_settings', 'team_members_per_row');
    register_setting('team_settings', 'team_member_gap');

    // Add default values if not set
    if (!get_option('team_members_per_row')) {
      update_option('team_members_per_row', '4');
    }
    if (!get_option('team_member_gap')) {
      update_option('team_member_gap', '20');
    }
  }

  public function registerDepartmentTaxonomy()
  {
    register_taxonomy('team_department', 'team_member', array(
      'labels' => array(
        'name' => 'Departments',
        'singular_name' => 'Department',
        'menu_name' => 'Departments',
        'add_new_item' => 'Add New Department',
        'edit_item' => 'Edit Department',
        'update_item' => 'Update Department',
        'search_items' => 'Search Departments',
        'not_found' => 'No departments found'
      ),
      'hierarchical' => true,
      'show_ui' => true,
      'show_in_menu' => false,
      'show_admin_column' => true,
      'query_var' => true,
      'rewrite' => array('slug' => 'department')
    ));
  }

  public static function renderNavigation($current_page = '')
  {
?>
    <div class="team-navigation">
      <a href="<?php echo esc_url(admin_url('users.php?page=team-members')); ?>"
        class="button<?php echo $current_page === 'members' ? ' button-primary' : ''; ?>">
        Manage Members
      </a>

      <a href="<?php echo esc_url(admin_url('post-new.php?post_type=team_member')); ?>"
        class="button<?php echo $current_page === 'new' ? ' button-primary' : ''; ?>">
        Add New Member
      </a>

      <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=team_department&post_type=team_member')); ?>"
        class="button<?php echo $current_page === 'departments' ? ' button-primary' : ''; ?>">
        Manage Departments
      </a>

      <button type="button" class="button" onclick="jQuery('#team-settings').slideToggle();">
        Display Settings
      </button>
    </div>
  <?php
  }

  public function addNavigationToTaxonomyPage()
  {
    $screen = get_current_screen();
    if ($screen->taxonomy === 'team_department') {
      $this->renderNavigation('departments');
    }
  }

  public function enqueueAssets($hook)
  {
    $allowed_hooks = array(
      'users_page_team-members',
      'edit-team_department',
      'team_member_page_team-settings',
      'post-new.php',
      'post.php'
    );

    // Check if we're on a team member post type page
    global $post_type;
    $is_team_page = in_array($hook, $allowed_hooks) ||
      ($post_type === 'team_member' && ($hook === 'post.php' || $hook === 'post-new.php'));

    if ($is_team_page) {
      wp_enqueue_style(
        'team-members-admin',
        TEAM_PLUGIN_URL . 'assets/css/admin.css',
        array(),
        '1.1'
      );
    }
  }

  private function renderSettings()
  {
    $members_per_row = get_option('team_members_per_row', '4');
    $gap = get_option('team_member_gap', '20');
  ?>
    <div id="team-settings" class="team-settings-panel">
      <h2>Team Display Settings</h2>
      <form method="post" action="options.php">
        <?php settings_fields('team_settings'); ?>
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="team_members_per_row">Members Per Row</label>
            </th>
            <td>
              <select name="team_members_per_row" id="team_members_per_row">
                <?php for ($i = 1; $i <= 6; $i++): ?>
                  <option value="<?php echo $i; ?>" <?php selected($members_per_row, $i); ?>>
                    <?php echo $i; ?> <?php echo $i === 1 ? 'Member' : 'Members'; ?>
                  </option>
                <?php endfor; ?>
              </select>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="team_member_gap">Space Between Members (px)</label>
            </th>
            <td>
              <input type="number"
                id="team_member_gap"
                name="team_member_gap"
                value="<?php echo esc_attr($gap); ?>"
                min="0"
                max="100">
              <p class="description">Minimum: 0px, Maximum: 100px</p>
            </td>
          </tr>
        </table>
        <?php submit_button('Save Settings'); ?>
      </form>
    </div>
  <?php
  }

  public function renderTeamPage()
  {
  ?>
    <div class="wrap team-members-admin">
      <h1 class="wp-heading-inline">Team Members</h1>
      <?php self::renderNavigation('members'); ?>
      <hr class="wp-header-end">

      <!-- Settings Panel (Hidden by Default) -->
      <?php $this->renderSettings(); ?>

      <!-- Team Members List -->
      <?php
      $args = array(
        'post_type' => 'team_member',
        'posts_per_page' => -1,
        'orderby' => 'menu_order title',
        'order' => 'ASC'
      );

      $team_members = get_posts($args);

      if ($team_members): ?>
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
<?php
  }
}
