<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

final class TeamCSV
{
    private static ?TeamCSV $instance = null;

    public static function init(): void
    {
        if (self::$instance !== null) {
            return;
        }
        self::$instance = new self();

        // Export handlers (admin_post for direct downloads)
        add_action('admin_post_team_export_members', [self::$instance, 'exportMembers']);
        add_action('admin_post_team_export_departments', [self::$instance, 'exportDepartments']);

        // Import handlers (AJAX for processing)
        add_action('wp_ajax_team_import_members', [self::$instance, 'importMembers']);
        add_action('wp_ajax_team_import_departments', [self::$instance, 'importDepartments']);
    }

    /**
     * Export team members to CSV
     */
    public function exportMembers(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized access', 'wp-team-manager'));
        }

        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'team_export_members')) {
            wp_die(__('Security check failed', 'wp-team-manager'));
        }

        $members = get_posts([
            'post_type'      => 'team_member',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
        ]);

        $filename = 'team-members-' . gmdate('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        if ($output === false) {
            wp_die(__('Failed to create output stream', 'wp-team-manager'));
        }

        // UTF-8 BOM for Excel compatibility
        fwrite($output, "\xEF\xBB\xBF");

        // CSV header
        fputcsv($output, ['name', 'email', 'website', 'departments', 'image_url']);

        foreach ($members as $member) {
            $name = get_post_meta($member->ID, '_team_member_name', true);
            $email = get_post_meta($member->ID, '_team_member_email', true);
            $website = get_post_meta($member->ID, '_team_member_website', true);

            $departments = wp_get_object_terms($member->ID, 'team_department');
            $department_names = !is_wp_error($departments)
                ? implode(', ', wp_list_pluck($departments, 'name'))
                : '';

            $image_url = get_the_post_thumbnail_url($member->ID, 'full') ?: '';

            fputcsv($output, [
                $name ?: '',
                $email ?: '',
                $website ?: '',
                $department_names,
                $image_url,
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Export departments to CSV
     */
    public function exportDepartments(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized access', 'wp-team-manager'));
        }

        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'team_export_departments')) {
            wp_die(__('Security check failed', 'wp-team-manager'));
        }

        $departments = get_terms([
            'taxonomy'   => 'team_department',
            'hide_empty' => false,
        ]);

        $filename = 'team-departments-' . gmdate('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        if ($output === false) {
            wp_die(__('Failed to create output stream', 'wp-team-manager'));
        }

        // UTF-8 BOM for Excel compatibility
        fwrite($output, "\xEF\xBB\xBF");

        // CSV header
        fputcsv($output, ['name', 'slug', 'description']);

        if (!is_wp_error($departments)) {
            foreach ($departments as $department) {
                fputcsv($output, [
                    $department->name,
                    $department->slug,
                    $department->description,
                ]);
            }
        }

        fclose($output);
        exit;
    }

    /**
     * Import team members from CSV
     */
    public function importMembers(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized access', 'wp-team-manager')]);
        }

        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'team_import_members')) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-team-manager')]);
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(['message' => __('No file uploaded or upload error', 'wp-team-manager')]);
        }

        $file = $_FILES['csv_file']['tmp_name'];
        $download_images = isset($_POST['download_images']) && $_POST['download_images'] === '1';

        $handle = fopen($file, 'r');
        if ($handle === false) {
            wp_send_json_error(['message' => __('Failed to read CSV file', 'wp-team-manager')]);
        }

        // Skip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Read and validate header
        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            wp_send_json_error(['message' => __('Empty CSV file', 'wp-team-manager')]);
        }

        $header = array_map('strtolower', array_map('trim', $header));
        $required = ['name'];
        $missing = array_diff($required, $header);

        if (!empty($missing)) {
            fclose($handle);
            wp_send_json_error([
                'message' => sprintf(
                    __('Missing required columns: %s', 'wp-team-manager'),
                    implode(', ', $missing)
                ),
            ]);
        }

        $results = ['created' => 0, 'updated' => 0, 'errors' => []];

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, array_pad($row, count($header), ''));
            if ($data === false) {
                continue;
            }

            $name = sanitize_text_field(trim($data['name'] ?? ''));
            if (empty($name)) {
                continue;
            }

            $result = $this->importSingleMember($data, $download_images);
            if ($result['success']) {
                if ($result['action'] === 'created') {
                    $results['created']++;
                } else {
                    $results['updated']++;
                }
            } else {
                $results['errors'][] = sprintf('%s: %s', $name, $result['message']);
            }
        }

        fclose($handle);

        wp_send_json_success([
            'message' => sprintf(
                __('Import completed: %d created, %d updated', 'wp-team-manager'),
                $results['created'],
                $results['updated']
            ),
            'details' => $results,
        ]);
    }

    /**
     * Import a single team member
     *
     * @param array<string, string> $data
     * @return array{success: bool, action?: string, message?: string}
     */
    private function importSingleMember(array $data, bool $download_images): array
    {
        $name = sanitize_text_field(trim($data['name'] ?? ''));
        $email = sanitize_email(trim($data['email'] ?? ''));
        $website = esc_url_raw(trim($data['website'] ?? ''));
        $departments_str = trim($data['departments'] ?? '');
        $image_url = esc_url_raw(trim($data['image_url'] ?? ''));

        // Check if member exists by name
        $existing = get_posts([
            'post_type'      => 'team_member',
            'posts_per_page' => 1,
            'meta_query'     => [
                [
                    'key'   => '_team_member_name',
                    'value' => $name,
                ],
            ],
        ]);

        $is_update = !empty($existing);
        $post_id = $is_update ? $existing[0]->ID : 0;

        // Create or update post
        $post_data = [
            'post_type'   => 'team_member',
            'post_status' => 'publish',
            'post_title'  => $name,
        ];

        if ($is_update) {
            $post_data['ID'] = $post_id;
            $post_id = wp_update_post($post_data, true);
        } else {
            $post_id = wp_insert_post($post_data, true);
        }

        if (is_wp_error($post_id)) {
            return ['success' => false, 'message' => $post_id->get_error_message()];
        }

        // Update meta
        update_post_meta($post_id, '_team_member_name', $name);
        update_post_meta($post_id, '_team_member_email', $email);
        update_post_meta($post_id, '_team_member_website', $website);

        // Process departments
        if (!empty($departments_str)) {
            $department_names = array_map('trim', explode(',', $departments_str));
            $term_ids = [];

            foreach ($department_names as $dept_name) {
                if (empty($dept_name)) {
                    continue;
                }

                $term = get_term_by('name', $dept_name, 'team_department');
                if ($term) {
                    $term_ids[] = $term->term_id;
                } else {
                    // Create the department
                    $new_term = wp_insert_term($dept_name, 'team_department');
                    if (!is_wp_error($new_term)) {
                        $term_ids[] = $new_term['term_id'];
                    }
                }
            }

            wp_set_object_terms($post_id, $term_ids, 'team_department');
        }

        // Download and attach image
        if ($download_images && !empty($image_url)) {
            $this->downloadAndAttachImage($post_id, $image_url);
        }

        return ['success' => true, 'action' => $is_update ? 'updated' : 'created'];
    }

    /**
     * Download image from URL and set as featured image
     */
    private function downloadAndAttachImage(int $post_id, string $url): bool
    {
        // Require necessary functions
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // Download and attach image
        $attachment_id = media_sideload_image($url, $post_id, '', 'id');

        if (is_wp_error($attachment_id)) {
            return false;
        }

        set_post_thumbnail($post_id, $attachment_id);
        return true;
    }

    /**
     * Import departments from CSV
     */
    public function importDepartments(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized access', 'wp-team-manager')]);
        }

        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'team_import_departments')) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-team-manager')]);
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(['message' => __('No file uploaded or upload error', 'wp-team-manager')]);
        }

        $file = $_FILES['csv_file']['tmp_name'];

        $handle = fopen($file, 'r');
        if ($handle === false) {
            wp_send_json_error(['message' => __('Failed to read CSV file', 'wp-team-manager')]);
        }

        // Skip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Read and validate header
        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            wp_send_json_error(['message' => __('Empty CSV file', 'wp-team-manager')]);
        }

        $header = array_map('strtolower', array_map('trim', $header));
        $required = ['name'];
        $missing = array_diff($required, $header);

        if (!empty($missing)) {
            fclose($handle);
            wp_send_json_error([
                'message' => sprintf(
                    __('Missing required columns: %s', 'wp-team-manager'),
                    implode(', ', $missing)
                ),
            ]);
        }

        $results = ['created' => 0, 'updated' => 0, 'errors' => []];

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, array_pad($row, count($header), ''));
            if ($data === false) {
                continue;
            }

            $name = sanitize_text_field(trim($data['name'] ?? ''));
            if (empty($name)) {
                continue;
            }

            $slug = sanitize_title($data['slug'] ?? $name);
            $description = sanitize_textarea_field($data['description'] ?? '');

            // Check if department exists by slug
            $existing = get_term_by('slug', $slug, 'team_department');

            if ($existing) {
                // Update existing department
                $result = wp_update_term($existing->term_id, 'team_department', [
                    'name'        => $name,
                    'description' => $description,
                ]);

                if (!is_wp_error($result)) {
                    $results['updated']++;
                } else {
                    $results['errors'][] = sprintf('%s: %s', $name, $result->get_error_message());
                }
            } else {
                // Create new department
                $result = wp_insert_term($name, 'team_department', [
                    'slug'        => $slug,
                    'description' => $description,
                ]);

                if (!is_wp_error($result)) {
                    $results['created']++;
                } else {
                    $results['errors'][] = sprintf('%s: %s', $name, $result->get_error_message());
                }
            }
        }

        fclose($handle);

        wp_send_json_success([
            'message' => sprintf(
                __('Import completed: %d created, %d updated', 'wp-team-manager'),
                $results['created'],
                $results['updated']
            ),
            'details' => $results,
        ]);
    }

    /**
     * Get export URL for members
     */
    public static function getMembersExportUrl(): string
    {
        return wp_nonce_url(
            admin_url('admin-post.php?action=team_export_members'),
            'team_export_members'
        );
    }

    /**
     * Get export URL for departments
     */
    public static function getDepartmentsExportUrl(): string
    {
        return wp_nonce_url(
            admin_url('admin-post.php?action=team_export_departments'),
            'team_export_departments'
        );
    }
}
