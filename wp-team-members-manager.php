<?php
/*
Plugin Name: Team Members Manager
Plugin URI: https://your-website.com/
Description: Manages and displays team members with customizable titles
Version: 1.1
Author: Your Name
Author URI: https://your-website.com/
License: GPL v2 or later
Text Domain: team-members-manager
*/

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

// Plugin Class
class TeamMembersManager
{
  private static $instance = null;
  private $plugin_path;
  private $plugin_url;
  private $cache_key = 'team_members_cache';

  public static function getInstance()
  {
    if (self::$instance == null) {
      self::$instance = new TeamMembersManager();
    }
    return self::$instance;
  }

  private function __construct()
  {
    $this->plugin_path = plugin_dir_path(__FILE__);
    $this->plugin_url = plugin_dir_url(__FILE__);

    // Initialize hooks
    add_action('admin_menu', array($this, 'addAdminMenus'));
    add_action('admin_init', array($this, 'registerSettings'));
    add_action('init', array($this, 'createCustomPostType'));
    add_action('wp_enqueue_scripts', array($this, 'enqueueFrontendAssets'));
    add_action('admin_enqueue_scripts', array($this, 'enqueueAdminAssets'));
    add_shortcode('team_members', array($this, 'teamMembersShortcode'));
    add_action('add_meta_boxes', array($this, 'addMetaBoxes'));
    add_action('save_post', array($this, 'saveMetaBoxData'));
    add_action('admin_notices', array($this, 'checkDirectoryPermissions'));

    // Cache clearing hooks
    add_action('before_delete_post', array($this, 'clearTeamCache'));
    add_action('wp_trash_post', array($this, 'clearTeamCache'));
    add_action('untrash_post', array($this, 'clearTeamCache'));
    add_action('update_option_team_member_titles', array($this, 'clearTeamCache'));
  }

  public function checkDirectoryPermissions()
  {
    $assets_dir = $this->plugin_path . 'assets';
    $css_dir = $assets_dir . '/css';
    $images_dir = $assets_dir . '/images';

    if (!file_exists($assets_dir) || !file_exists($css_dir) || !file_exists($images_dir)) {
      if (!is_writable($this->plugin_path)) {
        echo '<div class="error"><p>Plugin directory is not writable. Please check permissions.</p></div>';
        return;
      }

      // Create directories
      if (!file_exists($assets_dir)) {
        mkdir($assets_dir);
      }
      if (!file_exists($css_dir)) {
        mkdir($css_dir);
      }
      if (!file_exists($images_dir)) {
        mkdir($images_dir);
      }
    }
  }

  public function enqueueFrontendAssets()
  {
    wp_enqueue_style(
      'team-members-style',
      $this->plugin_url . 'assets/css/team-members.css',
      array(),
      '1.1'
    );
  }

  public function enqueueAdminAssets($hook)
  {
    if ('toplevel_page_manage-team' === $hook) {
      wp_enqueue_style(
        'team-members-admin',
        $this->plugin_url . 'assets/css/admin.css',
        array(),
        '1.1'
      );
    }
  }

  public function createCustomPostType()
  {
    $labels = array(
      'name'               => 'Team Members',
      'singular_name'      => 'Team Member',
      'menu_name'          => 'Team Members',
      'add_new'           => 'Add New Member',
      'add_new_item'      => 'Add New Team Member',
      'edit_item'         => 'Edit Team Member',
      'new_item'          => 'New Team Member',
      'view_item'         => 'View Team Member',
      'search_items'      => 'Search Team Members',
      'not_found'         => 'No team members found',
      'not_found_in_trash' => 'No team members found in Trash'
    );

    $args = array(
      'labels'             => $labels,
      'public'             => true,
      'show_ui'           => true,
      'show_in_menu'      => false,
      'supports'          => array('title', 'thumbnail'),
      'has_archive'       => false,
      'hierarchical'      => false,
      'menu_icon'         => 'dashicons-groups',
      'capability_type'   => 'post'
    );

    register_post_type('team_member', $args);
  }

  public function addAdminMenus()
  {
    add_menu_page(
      'Team Members',
      'Team Members',
      'manage_options',
      'manage-team',
      array($this, 'renderTeamPage'),
      'dashicons-groups',
      71
    );
  }

  public function registerSettings()
  {
    register_setting('team_titles_options', 'team_member_titles');
  }

  public function clearTeamCache($post_id = null)
  {
    if ($post_id !== null) {
      $post_type = get_post_type($post_id);
      if ($post_type !== 'team_member') {
        return;
      }
    }
    delete_transient($this->cache_key);
  }

  public function renderTeamPage()
  {
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }
?>
    <div class="wrap">
      <h1>Team Members Management</h1>

      <!-- Titles Section -->
      <div class="team-section">
        <h2>Manage Titles</h2>
        <form method="post" action="options.php">
          <?php
          settings_fields('team_titles_options');
          do_settings_sections('team_titles_options');
          ?>
          <table class="form-table">
            <tr>
              <th scope="row">Available Titles</th>
              <td>
                <textarea name="team_member_titles" rows="5" cols="40"><?php echo esc_textarea(get_option('team_member_titles')); ?></textarea>
                <p class="description">Enter one title per line. These titles will be available when creating team members.</p>
              </td>
            </tr>
          </table>
          <?php submit_button('Save Titles'); ?>
        </form>
      </div>

      <!-- Team Members List -->
      <div class="team-section">
        <h2>Team Members</h2>
        <a href="<?php echo admin_url('post-new.php?post_type=team_member'); ?>" class="button button-primary">Add New Member</a>

        <?php
        $team_members = get_posts(array(
          'post_type' => 'team_member',
          'posts_per_page' => -1,
          'orderby' => 'title',
          'order' => 'ASC'
        ));

        if ($team_members): ?>
          <table class="wp-list-table widefat fixed striped">
            <thead>
              <tr>
                <th width="60">Image</th>
                <th>Name</th>
                <th>Title</th>
                <th>Email</th>
                <th>Website</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($team_members as $member): ?>
                <tr>
                  <td>
                    <?php
                    if (has_post_thumbnail($member->ID)) {
                      echo get_the_post_thumbnail($member->ID, array(50, 50));
                    } else {
                      echo '<img src="' . esc_url($this->plugin_url . 'assets/images/default-avatar.png') . '" width="50" height="50" alt="Default Avatar">';
                    }
                    ?>
                  </td>
                  <td><?php echo esc_html(get_post_meta($member->ID, '_team_member_name', true)); ?></td>
                  <td><?php echo esc_html(get_post_meta($member->ID, '_team_member_title', true)); ?></td>
                  <td><?php echo esc_html(get_post_meta($member->ID, '_team_member_email', true)); ?></td>
                  <td>
                    <?php
                    $website = get_post_meta($member->ID, '_team_member_website', true);
                    if ($website) {
                      echo '<a href="' . esc_url($website) . '" target="_blank">Visit Website</a>';
                    }
                    ?>
                  </td>
                  <td>
                    <a href="<?php echo get_edit_post_link($member->ID); ?>" class="button button-small">Edit</a>
                    <a href="<?php echo get_delete_post_link($member->ID); ?>" class="button button-small" onclick="return confirm('Are you sure you want to delete this team member?')">Delete</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p>No team members found. Click "Add New Member" to create your first team member.</p>
        <?php endif; ?>
      </div>

      <!-- Shortcode Info -->
      <div class="team-section">
        <h2>Shortcode Usage</h2>
        <p>Use the following shortcode to display your team members grid anywhere on your site:</p>
        <code>[team_members]</code>
      </div>
    </div>
  <?php
  }

  public function addMetaBoxes()
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
    $name = get_post_meta($post->ID, '_team_member_name', true);
    $email = get_post_meta($post->ID, '_team_member_email', true);
    $title = get_post_meta($post->ID, '_team_member_title', true);
    $website = get_post_meta($post->ID, '_team_member_website', true);

    wp_nonce_field('team_member_meta_box', 'team_member_meta_box_nonce');

    $titles = array_filter(explode("\n", get_option('team_member_titles', '')));
    $titles = array_map('trim', $titles);
  ?>
    <div class="team-member-meta-box">
      <p>
        <label for="team_member_name"><strong>Full Name:</strong></label><br>
        <input type="text" id="team_member_name" name="team_member_name"
          value="<?php echo esc_attr($name); ?>" size="40" required>
      </p>

      <p>
        <label for="team_member_email"><strong>Email:</strong></label><br>
        <input type="email" id="team_member_email" name="team_member_email"
          value="<?php echo esc_attr($email); ?>" size="40">
      </p>

      <p>
        <label for="team_member_title"><strong>Title:</strong></label><br>
        <select id="team_member_title" name="team_member_title" required>
          <option value="">Select Title</option>
          <?php foreach ($titles as $t): ?>
            <option value="<?php echo esc_attr($t); ?>"
              <?php selected($title, $t); ?>>
              <?php echo esc_html($t); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </p>

      <p>
        <label for="team_member_website"><strong>Website URL:</strong></label><br>
        <input type="url" id="team_member_website" name="team_member_website"
          value="<?php echo esc_url($website); ?>" size="40">
      </p>

      <p>
        <strong>Featured Image:</strong><br>
        Set the featured image to add a profile photo. If no image is set, a default avatar will be used.
      </p>
    </div>
  <?php
  }

  public function saveMetaBoxData($post_id)
  {
    if (
      !isset($_POST['team_member_meta_box_nonce']) ||
      !wp_verify_nonce($_POST['team_member_meta_box_nonce'], 'team_member_meta_box')
    ) {
      return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
      return;
    }

    if (!current_user_can('edit_post', $post_id)) {
      return;
    }

    $fields = array(
      'team_member_name' => 'sanitize_text_field',
      'team_member_email' => 'sanitize_email',
      'team_member_title' => 'sanitize_text_field',
      'team_member_website' => 'esc_url_raw'
    );

    foreach ($fields as $field => $sanitize_callback) {
      if (isset($_POST[$field])) {
        update_post_meta(
          $post_id,
          '_' . $field,
          $sanitize_callback($_POST[$field])
        );
      }
    }

    $this->clearTeamCache($post_id);
  }

  public function teamMembersShortcode($atts)
  {
    $cache = get_transient($this->cache_key);
    if ($cache !== false) {
      return $cache;
    }

    $args = array(
      'post_type' => 'team_member',
      'posts_per_page' => -1,
      'orderby' => 'title',
      'order' => 'ASC'
    );

    $team_members = get_posts($args);

    ob_start();
  ?>
    <div class="team-members-grid">
      <?php foreach ($team_members as $member): ?>
        <?php
        $name = get_post_meta($member->ID, '_team_member_name', true);
        $email = get_post_meta($member->ID, '_team_member_email', true);
        $title = get_post_meta($member->ID, '_team_member_title', true);
        $website = get_post_meta($member->ID, '_team_member_website', true);
        ?>
        <div class="team-member">
          <?php if (has_post_thumbnail($member->ID)): ?>
            <?php echo get_the_post_thumbnail($member->ID, 'thumbnail'); ?>
          <?php else: ?>
            <img src="<?php echo esc_url($this->plugin_url . 'assets/images/default-avatar.png'); ?>"
              alt="Default Avatar" class="default-avatar">
          <?php endif; ?>

          <h3><?php echo esc_html($name); ?></h3>
          <p class="title"><?php echo esc_html($title); ?></p>

          <?php if ($email): ?>
            <p class="email">
              <a href="mailto:<?php echo esc_attr($email); ?>">
                <?php echo esc_html($email); ?>
              </a>
            </p>
          <?php endif; ?>

          <?php if ($website): ?>
            <p class="website">
              <a href="<?php echo esc_url($website); ?>" target="_blank">
                Website
              </a>
            </p>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
<?php

    $output = ob_get_clean();
    set_transient($this->cache_key, $output);

    return $output;
  }
}

// Initialize the plugin
function team_members_init()
{
  TeamMembersManager::getInstance();
}
add_action('plugins_loaded', 'team_members_init');

// Activation hook
register_activation_hook(__FILE__, 'team_members_activate');

function team_members_activate()
{
  // Create assets directory and subdirectories if they don't exist
  $plugin_assets = plugin_dir_path(__FILE__) . 'assets';

  if (!file_exists($plugin_assets)) {
    mkdir($plugin_assets);
    mkdir($plugin_assets . '/css');
    mkdir($plugin_assets . '/images');
  }

  // Clear the cache on activation
  delete_transient('team_members_cache');

  // Flush rewrite rules for custom post type
  flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'team_members_deactivate');

function team_members_deactivate()
{
  // Clear the cache on deactivation
  delete_transient('team_members_cache');

  // Flush rewrite rules
  flush_rewrite_rules();
}

// Uninstall hook
register_uninstall_hook(__FILE__, 'team_members_uninstall');

function team_members_uninstall()
{
  // Remove all plugin data
  $team_members = get_posts(array(
    'post_type' => 'team_member',
    'numberposts' => -1,
    'post_status' => 'any'
  ));

  foreach ($team_members as $member) {
    wp_delete_post($member->ID, true);
  }

  // Remove plugin options
  delete_option('team_member_titles');

  // Clear any remaining cache
  delete_transient('team_members_cache');

  // Clean up post meta
  global $wpdb;
  $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_team_member_%'");

  // Remove plugin directories
  $plugin_assets = plugin_dir_path(__FILE__) . 'assets';
  if (file_exists($plugin_assets)) {
    array_map('unlink', glob("$plugin_assets/css/*.*"));
    array_map('unlink', glob("$plugin_assets/images/*.*"));
    rmdir("$plugin_assets/css");
    rmdir("$plugin_assets/images");
    rmdir($plugin_assets);
  }
}
