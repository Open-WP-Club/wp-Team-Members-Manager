<?php
class TeamAdmin
{
  public static function init()
  {
    $instance = new self();
    add_action('admin_menu', array($instance, 'addMenus'));
    add_action('init', array($instance, 'registerTaxonomy'));
    add_action('admin_init', array($instance, 'registerSettings'));
    add_action('admin_enqueue_scripts', array($instance, 'enqueueAssets'));
  }

  public function addMenus()
  {
    // Add main Team Members page
    $hook = add_submenu_page(
      'users.php',
      'Team Members',
      'Team Members',
      'manage_options',
      'team-members',
      array($this, 'renderTeamPage')
    );

    // Add Settings page
    add_submenu_page(
      'users.php',
      'Team Settings',
      'Team Settings',
      'manage_options',
      'team-settings',
      array($this, 'renderSettingsPage')
    );

    // Other menu items...
  }

  public function registerSettings()
  {
    register_setting('team_settings', 'team_member_width');
    register_setting('team_settings', 'team_member_gap');

    // Add default values if not set
    if (!get_option('team_member_width')) {
      update_option('team_member_width', '300');
    }
    if (!get_option('team_member_gap')) {
      update_option('team_member_gap', '20');
    }
  }

  public function renderSettingsPage()
  {
    $width = get_option('team_member_width', '300');
    $gap = get_option('team_member_gap', '20');
?>
    <div class="wrap">
      <h1>Team Members Settings</h1>
      <form method="post" action="options.php">
        <?php settings_fields('team_settings'); ?>
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="team_member_width">Member Card Width (px)</label>
            </th>
            <td>
              <input type="number"
                id="team_member_width"
                name="team_member_width"
                value="<?php echo esc_attr($width); ?>"
                min="200"
                max="800">
              <p class="description">Minimum: 200px, Maximum: 800px</p>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="team_member_gap">Space Between Cards (px)</label>
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
        <?php submit_button(); ?>
      </form>
    </div>
<?php
  }
}
