<?php

defined('ABSPATH') || exit;

$name = get_post_meta($post->ID, '_team_member_name', true);
$email = get_post_meta($post->ID, '_team_member_email', true);
$website = get_post_meta($post->ID, '_team_member_website', true);
$departments = get_terms([
    'taxonomy'   => 'team_department',
    'hide_empty' => false,
]);
?>
<div class="team-member-meta">
    <p>
        <label for="team_member_name"><strong><?php esc_html_e('Full Name:', 'team-members-manager'); ?></strong></label><br>
        <input type="text"
               id="team_member_name"
               name="team_member_name"
               value="<?php echo esc_attr((string) $name); ?>"
               required
               class="widefat">
    </p>

    <p>
        <label for="team_member_department"><strong><?php esc_html_e('Department:', 'team-members-manager'); ?></strong></label><br>
        <?php if (!empty($departments) && !is_wp_error($departments)): ?>
            <select id="team_member_department"
                    name="team_member_department[]"
                    multiple
                    required
                    class="widefat"
                    style="min-height: 100px;">
                <?php foreach ($departments as $department): ?>
                    <option value="<?php echo esc_attr((string) $department->term_id); ?>"
                        <?php echo has_term($department->term_id, 'team_department', $post->ID) ? 'selected' : ''; ?>>
                        <?php echo esc_html($department->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php else: ?>
            <span class="description">
                <?php
                printf(
                    /* translators: %s: URL to add departments */
                    esc_html__('No departments found. %s', 'team-members-manager'),
                    '<a href="' . esc_url(admin_url('edit-tags.php?taxonomy=team_department&post_type=team_member')) . '">' .
                    esc_html__('Add departments first', 'team-members-manager') . '</a>.'
                );
                ?>
            </span>
        <?php endif; ?>
    </p>

    <p>
        <label for="team_member_email"><strong><?php esc_html_e('Email:', 'team-members-manager'); ?></strong></label><br>
        <input type="email"
               id="team_member_email"
               name="team_member_email"
               value="<?php echo esc_attr((string) $email); ?>"
               class="widefat">
    </p>

    <p>
        <label for="team_member_website"><strong><?php esc_html_e('Website:', 'team-members-manager'); ?></strong></label><br>
        <input type="url"
               id="team_member_website"
               name="team_member_website"
               value="<?php echo esc_url((string) $website); ?>"
               class="widefat"
               placeholder="https://">
    </p>
</div>
