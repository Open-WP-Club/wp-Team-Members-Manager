<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

final class TeamAdmin
{
    private static ?TeamAdmin $instance = null;

    public static function init(): void
    {
        if (self::$instance !== null) {
            return;
        }
        self::$instance = new self();

        add_action('admin_menu', [self::$instance, 'addMenus']);
        add_action('admin_init', [self::$instance, 'registerSettings']);
        add_action('admin_enqueue_scripts', [self::$instance, 'enqueueAssets']);
        add_action('init', [self::$instance, 'registerDepartmentTaxonomy']);
        add_action('admin_notices', [self::$instance, 'addNavigationToTaxonomyPage']);
        add_action('edit_form_top', [self::$instance, 'addNavigationToEditPage']);
    }

    public function addMenus(): void
    {
        add_submenu_page(
            'users.php',
            __('Team Members', 'team-members-manager'),
            __('Team Members', 'team-members-manager'),
            'manage_options',
            'team-members',
            [$this, 'renderTeamPage']
        );
    }

    public function registerSettings(): void
    {
        register_setting('team_settings', 'team_members_per_row', [
            'type'              => 'integer',
            'default'           => 4,
            'sanitize_callback' => static fn($v): int => max(1, min(6, absint($v))),
        ]);

        register_setting('team_settings', 'team_member_gap', [
            'type'              => 'integer',
            'default'           => 20,
            'sanitize_callback' => static fn($v): int => max(0, min(100, absint($v))),
        ]);

        if (get_option('team_members_per_row') === false) {
            update_option('team_members_per_row', 4);
        }
        if (get_option('team_member_gap') === false) {
            update_option('team_member_gap', 20);
        }
    }

    public function registerDepartmentTaxonomy(): void
    {
        register_taxonomy('team_department', 'team_member', [
            'labels' => [
                'name'          => __('Departments', 'team-members-manager'),
                'singular_name' => __('Department', 'team-members-manager'),
                'menu_name'     => __('Departments', 'team-members-manager'),
                'add_new_item'  => __('Add New Department', 'team-members-manager'),
                'edit_item'     => __('Edit Department', 'team-members-manager'),
                'update_item'   => __('Update Department', 'team-members-manager'),
                'search_items'  => __('Search Departments', 'team-members-manager'),
                'not_found'     => __('No departments found', 'team-members-manager'),
            ],
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_in_menu'      => false,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'department'],
        ]);
    }

    public static function renderNavigation(string $current_page = ''): void
    {
        ?>
        <div class="team-navigation">
            <a href="<?php echo esc_url(admin_url('users.php?page=team-members')); ?>"
               class="button<?php echo $current_page === 'members' ? ' button-primary' : ''; ?>">
                <?php esc_html_e('Manage Members', 'team-members-manager'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('post-new.php?post_type=team_member')); ?>"
               class="button<?php echo $current_page === 'new' ? ' button-primary' : ''; ?>">
                <?php esc_html_e('Add New Member', 'team-members-manager'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=team_department&post_type=team_member')); ?>"
               class="button<?php echo $current_page === 'departments' ? ' button-primary' : ''; ?>">
                <?php esc_html_e('Manage Departments', 'team-members-manager'); ?>
            </a>
            <button type="button" class="button team-settings-toggle">
                <?php esc_html_e('Display Settings', 'team-members-manager'); ?>
            </button>
            <button type="button" class="button team-csv-toggle">
                <?php esc_html_e('Import/Export', 'team-members-manager'); ?>
            </button>
        </div>
        <?php
    }

    public function addNavigationToTaxonomyPage(): void
    {
        $screen = get_current_screen();
        if ($screen?->taxonomy === 'team_department') {
            self::renderNavigation('departments');
        }
    }

    public function addNavigationToEditPage(\WP_Post $post): void
    {
        if ($post->post_type === 'team_member') {
            echo '<div class="team-member-edit-header">';
            self::renderNavigation('new');
            echo '</div>';
        }
    }

    public function enqueueAssets(string $hook): void
    {
        $screen = get_current_screen();
        $is_team_page = $hook === 'users_page_team-members'
            || $screen?->taxonomy === 'team_department'
            || ($screen?->post_type === 'team_member' && in_array($hook, ['post.php', 'post-new.php'], true));

        if (!$is_team_page) {
            return;
        }

        wp_enqueue_style(
            'team-members-admin',
            TEAM_PLUGIN_URL . 'assets/css/admin.css',
            [],
            TEAM_VERSION
        );

        wp_add_inline_script('jquery', "
            jQuery(function($) {
                $('.team-settings-toggle').on('click', function() {
                    $('#team-settings').slideToggle(200);
                    $('#team-csv-panel').slideUp(200);
                });
                $('.team-csv-toggle').on('click', function() {
                    $('#team-csv-panel').slideToggle(200);
                    $('#team-settings').slideUp(200);
                });

                // Handle import form submissions
                $('.team-import-form').on('submit', function(e) {
                    e.preventDefault();
                    var form = $(this);
                    var formData = new FormData(this);
                    var resultDiv = form.find('.import-result');
                    var submitBtn = form.find('button[type=submit]');
                    var originalText = submitBtn.text();

                    submitBtn.prop('disabled', true).text('" . esc_js(__('Importing...', 'team-members-manager')) . "');
                    resultDiv.removeClass('notice-success notice-error').hide();

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                resultDiv.addClass('notice notice-success').html('<p>' + response.data.message + '</p>').show();
                                if (response.data.details && response.data.details.errors && response.data.details.errors.length > 0) {
                                    resultDiv.append('<p><strong>" . esc_js(__('Errors:', 'team-members-manager')) . "</strong></p><ul><li>' + response.data.details.errors.join('</li><li>') + '</li></ul>');
                                }
                                setTimeout(function() { location.reload(); }, 2000);
                            } else {
                                resultDiv.addClass('notice notice-error').html('<p>' + response.data.message + '</p>').show();
                            }
                        },
                        error: function() {
                            resultDiv.addClass('notice notice-error').html('<p>" . esc_js(__('An error occurred during import.', 'team-members-manager')) . "</p>').show();
                        },
                        complete: function() {
                            submitBtn.prop('disabled', false).text(originalText);
                        }
                    });
                });
            });
        ");
    }

    private function renderSettings(): void
    {
        $members_per_row = (int) get_option('team_members_per_row', 4);
        $gap = (int) get_option('team_member_gap', 20);
        ?>
        <div id="team-settings" class="team-settings-panel" style="display:none;">
            <h2><?php esc_html_e('Team Display Settings', 'team-members-manager'); ?></h2>
            <form method="post" action="options.php">
                <?php settings_fields('team_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="team_members_per_row"><?php esc_html_e('Members Per Row', 'team-members-manager'); ?></label>
                        </th>
                        <td>
                            <select name="team_members_per_row" id="team_members_per_row">
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php selected($members_per_row, $i); ?>>
                                        <?php echo $i; ?> <?php echo $i === 1 ? __('Member', 'team-members-manager') : __('Members', 'team-members-manager'); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="team_member_gap"><?php esc_html_e('Space Between Members (px)', 'team-members-manager'); ?></label>
                        </th>
                        <td>
                            <input type="number"
                                   id="team_member_gap"
                                   name="team_member_gap"
                                   value="<?php echo esc_attr((string) $gap); ?>"
                                   min="0"
                                   max="100">
                            <p class="description"><?php esc_html_e('Minimum: 0px, Maximum: 100px', 'team-members-manager'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Save Settings', 'team-members-manager')); ?>
            </form>
        </div>
        <?php
    }

    private function renderCSVPanel(): void
    {
        ?>
        <div id="team-csv-panel" class="team-csv-panel" style="display:none;">
            <h2><?php esc_html_e('Import / Export', 'team-members-manager'); ?></h2>

            <div class="team-csv-sections">
                <!-- Export Section -->
                <div class="team-csv-section">
                    <h3><?php esc_html_e('Export', 'team-members-manager'); ?></h3>
                    <p class="description"><?php esc_html_e('Download your team data as CSV files.', 'team-members-manager'); ?></p>

                    <div class="team-csv-buttons">
                        <a href="<?php echo esc_url(TeamCSV::getMembersExportUrl()); ?>" class="button button-primary">
                            <span class="dashicons dashicons-download"></span>
                            <?php esc_html_e('Export Members', 'team-members-manager'); ?>
                        </a>
                        <a href="<?php echo esc_url(TeamCSV::getDepartmentsExportUrl()); ?>" class="button">
                            <span class="dashicons dashicons-download"></span>
                            <?php esc_html_e('Export Departments', 'team-members-manager'); ?>
                        </a>
                    </div>
                </div>

                <!-- Import Members Section -->
                <div class="team-csv-section">
                    <h3><?php esc_html_e('Import Members', 'team-members-manager'); ?></h3>
                    <p class="description">
                        <?php esc_html_e('CSV format: name, email, website, departments, image_url', 'team-members-manager'); ?>
                    </p>

                    <form class="team-import-form" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="team_import_members">
                        <?php wp_nonce_field('team_import_members', '_wpnonce'); ?>

                        <p>
                            <input type="file" name="csv_file" accept=".csv" required>
                        </p>
                        <p>
                            <label>
                                <input type="checkbox" name="download_images" value="1">
                                <?php esc_html_e('Download images from URLs', 'team-members-manager'); ?>
                            </label>
                            <span class="description"><?php esc_html_e('(may take longer)', 'team-members-manager'); ?></span>
                        </p>
                        <p>
                            <button type="submit" class="button button-primary">
                                <span class="dashicons dashicons-upload"></span>
                                <?php esc_html_e('Import Members', 'team-members-manager'); ?>
                            </button>
                        </p>
                        <div class="import-result" style="display:none;"></div>
                    </form>
                </div>

                <!-- Import Departments Section -->
                <div class="team-csv-section">
                    <h3><?php esc_html_e('Import Departments', 'team-members-manager'); ?></h3>
                    <p class="description">
                        <?php esc_html_e('CSV format: name, slug, description', 'team-members-manager'); ?>
                    </p>

                    <form class="team-import-form" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="team_import_departments">
                        <?php wp_nonce_field('team_import_departments', '_wpnonce'); ?>

                        <p>
                            <input type="file" name="csv_file" accept=".csv" required>
                        </p>
                        <p>
                            <button type="submit" class="button button-primary">
                                <span class="dashicons dashicons-upload"></span>
                                <?php esc_html_e('Import Departments', 'team-members-manager'); ?>
                            </button>
                        </p>
                        <div class="import-result" style="display:none;"></div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    public function renderTeamPage(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'team-members-manager'));
        }
        ?>
        <div class="wrap team-members-admin">
            <h1 class="wp-heading-inline"><?php esc_html_e('Team Members', 'team-members-manager'); ?></h1>
            <?php self::renderNavigation('members'); ?>
            <hr class="wp-header-end">

            <?php $this->renderSettings(); ?>
            <?php $this->renderCSVPanel(); ?>

            <?php
            $team_members = get_posts([
                'post_type'      => 'team_member',
                'posts_per_page' => -1,
                'orderby'        => 'menu_order title',
                'order'          => 'ASC',
            ]);

            if ($team_members): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th class="column-image"><?php esc_html_e('Image', 'team-members-manager'); ?></th>
                            <th class="column-name"><?php esc_html_e('Name', 'team-members-manager'); ?></th>
                            <th class="column-department"><?php esc_html_e('Department', 'team-members-manager'); ?></th>
                            <th class="column-contact"><?php esc_html_e('Contact', 'team-members-manager'); ?></th>
                            <th class="column-actions"><?php esc_html_e('Actions', 'team-members-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($team_members as $member):
                            $departments = wp_get_object_terms($member->ID, 'team_department');
                            $email = get_post_meta($member->ID, '_team_member_email', true);
                            $website = get_post_meta($member->ID, '_team_member_website', true);
                            $name = get_post_meta($member->ID, '_team_member_name', true);
                            ?>
                            <tr>
                                <td class="column-image">
                                    <?php if (has_post_thumbnail($member->ID)): ?>
                                        <?php echo get_the_post_thumbnail($member->ID, [50, 50]); ?>
                                    <?php else: ?>
                                        <img src="<?php echo esc_url(TEAM_PLUGIN_URL . 'assets/images/default.svg'); ?>"
                                             width="50" height="50" alt="<?php esc_attr_e('Default Avatar', 'team-members-manager'); ?>">
                                    <?php endif; ?>
                                </td>
                                <td class="column-name">
                                    <strong>
                                        <a href="<?php echo esc_url((string) get_edit_post_link($member->ID)); ?>">
                                            <?php echo esc_html((string) $name); ?>
                                        </a>
                                    </strong>
                                </td>
                                <td class="column-department">
                                    <?php
                                    if (!is_wp_error($departments) && !empty($departments)) {
                                        echo esc_html(implode(', ', wp_list_pluck($departments, 'name')));
                                    }
                                    ?>
                                </td>
                                <td class="column-contact">
                                    <?php if ($email): ?>
                                        <a href="mailto:<?php echo esc_attr((string) $email); ?>" title="<?php esc_attr_e('Email', 'team-members-manager'); ?>">
                                            <span class="dashicons dashicons-email"></span>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($website): ?>
                                        <a href="<?php echo esc_url((string) $website); ?>" target="_blank" rel="noopener" title="<?php esc_attr_e('Website', 'team-members-manager'); ?>">
                                            <span class="dashicons dashicons-admin-links"></span>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td class="column-actions">
                                    <a href="<?php echo esc_url((string) get_edit_post_link($member->ID)); ?>"
                                       class="button button-small">
                                        <?php esc_html_e('Edit', 'team-members-manager'); ?>
                                    </a>
                                    <a href="<?php echo esc_url((string) get_delete_post_link($member->ID)); ?>"
                                       class="button button-small"
                                       onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete this team member?', 'team-members-manager')); ?>')">
                                        <?php esc_html_e('Delete', 'team-members-manager'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-items">
                    <p>
                        <?php
                        printf(
                            /* translators: %s: URL to add new team member */
                            esc_html__('No team members found. %s', 'team-members-manager'),
                            '<a href="' . esc_url(admin_url('post-new.php?post_type=team_member')) . '">' . esc_html__('Add your first team member', 'team-members-manager') . '</a>.'
                        );
                        ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
