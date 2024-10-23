<?php
/*
Plugin Name: Team Members Manager
Description: Manages and displays team members with customizable titles
Version: 1.0
Author: Your Name
*/

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

// Plugin Class
class TeamMembersManager
{
  private static $instance = null;
  private $titles = array();

  public static function getInstance()
  {
    if (self::$instance == null) {
      self::$instance = new TeamMembersManager();
    }
    return self::$instance;
  }

  private function __construct()
  {
    // Initialize hooks
    add_action('admin_menu', array($this, 'addAdminMenus'));
    add_action('admin_init', array($this, 'registerSettings'));
    add_action('init', array($this, 'createCustomPostType'));
    add_shortcode('team_members', array($this, 'teamMembersShortcode'));
    add_action('add_meta_boxes', array($this, 'addMetaBoxes'));
    add_action('save_post', array($this, 'saveMetaBoxData'));
  }

  // Create custom post type
  public function createCustomPostType()
  {
    $args = array(
      'public' => true,
      'label'  => 'Team Members',
      'show_in_menu' => 'users.php', // Show in Users menu
      'supports' => array('title', 'thumbnail'),
      'menu_icon' => 'dashicons-groups'
    );
    register_post_type('team_member', $args);
  }

  // Add admin menus
  public function addAdminMenus()
  {
    add_submenu_page(
      'users.php',
      'Team Titles',
      'Team Titles',
      'manage_options',
      'team-titles',
      array($this, 'titleSettingsPage')
    );
  }

  // Register settings
  public function registerSettings()
  {
    register_setting('team_titles_options', 'team_member_titles');
  }

  // Settings page for titles
  public function titleSettingsPage()
  {
?>
    <div class="wrap">
      <h2>Team Member Titles</h2>
      <form method="post" action="options.php">
        <?php settings_fields('team_titles_options'); ?>
        <table class="form-table">
          <tr>
            <th scope="row">Titles (one per line)</th>
            <td>
              <textarea name="team_member_titles" rows="10" cols="50"><?php echo esc_textarea(get_option('team_member_titles')); ?></textarea>
              <p class="description">Enter one title per line. These will appear in the dropdown when creating team members.</p>
            </td>
          </tr>
        </table>
        <?php submit_button(); ?>
      </form>
    </div>
  <?php
  }

  // Add meta boxes
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

  // Render meta box
  public function renderMetaBox($post)
  {
    $name = get_post_meta($post->ID, '_team_member_name', true);
    $email = get_post_meta($post->ID, '_team_member_email', true);
    $title = get_post_meta($post->ID, '_team_member_title', true);
    $website = get_post_meta($post->ID, '_team_member_website', true);

    wp_nonce_field('team_member_meta_box', 'team_member_meta_box_nonce');

    // Get titles from options
    $titles = explode("\n", get_option('team_member_titles', ''));
    $titles = array_map('trim', $titles);
  ?>
    <p>
      <label for="team_member_name">Full Name:</label><br>
      <input type="text" id="team_member_name" name="team_member_name" value="<?php echo esc_attr($name); ?>" size="40">
    </p>
    <p>
      <label for="team_member_email">Email:</label><br>
      <input type="email" id="team_member_email" name="team_member_email" value="<?php echo esc_attr($email); ?>" size="40">
    </p>
    <p>
      <label for="team_member_title">Title:</label><br>
      <select id="team_member_title" name="team_member_title">
        <option value="">Select Title</option>
        <?php foreach ($titles as $t): ?>
          <option value="<?php echo esc_attr($t); ?>" <?php selected($title, $t); ?>><?php echo esc_html($t); ?></option>
        <?php endforeach; ?>
      </select>
    </p>
    <p>
      <label for="team_member_website">Website URL:</label><br>
      <input type="url" id="team_member_website" name="team_member_website" value="<?php echo esc_url($website); ?>" size="40">
    </p>
<?php
  }

  // Save meta box data
  public function saveMetaBoxData($post_id)
  {
    if (!isset($_POST['team_member_meta_box_nonce'])) {
      return;
    }

    if (!wp_verify_nonce($_POST['team_member_meta_box_nonce'], 'team_member_meta_box')) {
      return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
      return;
    }

    // Save the fields
    if (isset($_POST['team_member_name'])) {
      update_post_meta($post_id, '_team_member_name', sanitize_text_field($_POST['team_member_name']));
    }
    if (isset($_POST['team_member_email'])) {
      update_post_meta($post_id, '_team_member_email', sanitize_email($_POST['team_member_email']));
    }
    if (isset($_POST['team_member_title'])) {
      update_post_meta($post_id, '_team_member_title', sanitize_text_field($_POST['team_member_title']));
    }
    if (isset($_POST['team_member_website'])) {
      update_post_meta($post_id, '_team_member_website', esc_url_raw($_POST['team_member_website']));
    }
  }

  // Shortcode function
  public function teamMembersShortcode($atts)
  {
    $args = array(
      'post_type' => 'team_member',
      'posts_per_page' => -1,
      'orderby' => 'title',
      'order' => 'ASC'
    );

    $team_members = get_posts($args);

    $output = '<div class="team-members-grid">';

    foreach ($team_members as $member) {
      $name = get_post_meta($member->ID, '_team_member_name', true);
      $email = get_post_meta($member->ID, '_team_member_email', true);
      $title = get_post_meta($member->ID, '_team_member_title', true);
      $website = get_post_meta($member->ID, '_team_member_website', true);

      $output .= '<div class="team-member">';
      if (has_post_thumbnail($member->ID)) {
        $output .= get_the_post_thumbnail($member->ID, 'thumbnail');
      }
      $output .= '<h3>' . esc_html($name) . '</h3>';
      $output .= '<p class="title">' . esc_html($title) . '</p>';
      $output .= '<p class="email"><a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a></p>';
      if ($website) {
        $output .= '<p class="website"><a href="' . esc_url($website) . '" target="_blank">Website</a></p>';
      }
      $output .= '</div>';
    }

    $output .= '</div>';

    // Add basic CSS
    $output .= '
        <style>
            .team-members-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 2em;
                padding: 1em;
            }
            .team-member {
                text-align: center;
                padding: 1em;
                border: 1px solid #ddd;
                border-radius: 5px;
            }
            .team-member img {
                max-width: 150px;
                height: auto;
                border-radius: 50%;
                margin-bottom: 1em;
            }
            .team-member h3 {
                margin: 0.5em 0;
            }
            .team-member .title {
                color: #666;
                margin: 0.5em 0;
            }
        </style>';

    return $output;
  }
}

// Initialize the plugin
add_action('plugins_loaded', array('TeamMembersManager', 'getInstance'));
