<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

final class TeamShortcode
{
    private static bool $styles_enqueued = false;

    public static function init(): void
    {
        add_shortcode('team_members', [self::class, 'render']);
        add_action('wp_enqueue_scripts', [self::class, 'maybeEnqueueStyles']);
    }

    public static function maybeEnqueueStyles(): void
    {
        if (!is_singular()) {
            return;
        }

        global $post;
        if (!$post || !has_shortcode($post->post_content, 'team_members')) {
            return;
        }

        self::enqueueStyles();
    }

    private static function enqueueStyles(): void
    {
        if (self::$styles_enqueued) {
            return;
        }
        self::$styles_enqueued = true;

        wp_enqueue_style('team-members');

        $members_per_row = (int) get_option('team_members_per_row', 4);
        $gap = (int) get_option('team_member_gap', 20);

        wp_add_inline_style('team-members', ".team-members-grid{--members-per-row:{$members_per_row};--team-gap:{$gap}px}");
    }

    /**
     * @param array<string, mixed>|string $atts
     */
    public static function render(array|string $atts = []): string
    {
        self::enqueueStyles();

        $atts = shortcode_atts([
            'department' => '',
            'limit'      => -1,
        ], $atts, 'team_members');

        $department = sanitize_text_field($atts['department']);
        $limit = (int) $atts['limit'];

        $cache_key = '_' . md5($department . '|' . $limit);
        $cache = TeamCache::get($cache_key);
        if ($cache !== false && is_string($cache)) {
            return $cache;
        }

        $query_args = [
            'post_type'      => 'team_member',
            'posts_per_page' => $limit > 0 ? $limit : -1,
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
        ];

        if ($department !== '') {
            $query_args['tax_query'] = [
                [
                    'taxonomy' => 'team_department',
                    'field'    => 'slug',
                    'terms'    => array_map('trim', explode(',', $department)),
                ],
            ];
        }

        $team_members = get_posts($query_args);

        if (empty($team_members)) {
            return '<p>' . esc_html__('No team members found.', 'wp-team-manager') . '</p>';
        }

        ob_start();
        ?>
        <div class="team-members-grid">
            <?php foreach ($team_members as $member):
                $name = get_post_meta($member->ID, '_team_member_name', true);
                $email = get_post_meta($member->ID, '_team_member_email', true);
                $website = get_post_meta($member->ID, '_team_member_website', true);
                $departments = wp_get_object_terms($member->ID, 'team_department');
                $department_names = !is_wp_error($departments) ? wp_list_pluck($departments, 'name') : [];
                ?>
                <div class="team-member">
                    <?php if (has_post_thumbnail($member->ID)): ?>
                        <?php echo get_the_post_thumbnail($member->ID, 'medium', ['class' => 'team-member-image']); ?>
                    <?php else: ?>
                        <div class="default-avatar">
                            <img src="<?php echo esc_url(TEAM_PLUGIN_URL . 'assets/images/default.svg'); ?>"
                                 alt="<?php esc_attr_e('Default Avatar', 'wp-team-manager'); ?>"
                                 loading="lazy">
                        </div>
                    <?php endif; ?>

                    <h3><?php echo esc_html((string) $name); ?></h3>

                    <?php if (!empty($department_names)): ?>
                        <div class="department"><?php echo esc_html(implode(', ', $department_names)); ?></div>
                    <?php endif; ?>

                    <?php if ($email): ?>
                        <div class="email">
                            <a href="mailto:<?php echo esc_attr((string) $email); ?>"><?php echo esc_html((string) $email); ?></a>
                        </div>
                    <?php endif; ?>

                    <?php if ($website): ?>
                        <div class="website">
                            <a href="<?php echo esc_url((string) $website); ?>" target="_blank" rel="noopener">
                                <?php esc_html_e('Website', 'wp-team-manager'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        $output = ob_get_clean();

        if ($output !== false) {
            TeamCache::set($output, $cache_key);
            return $output;
        }

        return '';
    }
}
