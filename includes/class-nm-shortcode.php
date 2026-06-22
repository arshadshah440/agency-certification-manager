<?php
if (!defined('ABSPATH')) {
    exit;
}

class NM_Shortcode
{

    public static function init()
    {
        add_shortcode('nm_applications', array(__CLASS__, 'render_shortcode'));
        add_shortcode('current_user_email', array(__CLASS__, 'nm_current_user_email'));
        add_shortcode('nm_entries_last_30_days', array(__CLASS__, 'nm_entries_last_30_days_shortcode'));
    }

    public static function render_shortcode($atts)
    {
        ob_start();

        if (is_user_logged_in() && (current_user_can("administrator") || current_user_can("nm_manager"))) {


            $template_path = NM_APPS_PATH . 'templates/nm-dashboard-manager.php';
            if (file_exists($template_path)) {
                include $template_path;
            } else {
                echo '<div class="nm-applications"><h3>' . esc_html__('Dashboard template not found.', 'nm-applications') . '</h3></div>';
            }
        } else {
            echo '<div class="nm-applications"><h3>' . esc_html__('Unauthorized user, redirecting...', 'nm-applications') . '</h3></div>';

            // Optional: Redirect to login page after a short delay
            echo '<script>setTimeout(function(){ window.location.href = "' . esc_url(wp_login_url()) . '"; }, 1500);</script>';
        }

        return ob_get_clean();
    }

    public static function nm_current_user_email($atts)
    {
        $user_id = get_current_user_id();

        if (!$user_id) {
            return '';
        }

        $user = get_userdata($user_id);

        if (!$user || empty($user->user_email)) {
            return '';
        }

        return esc_html($user->user_email);
    }



    public static function nm_entries_last_30_days_shortcode($atts)
    {
        global $wpdb;

        // ---------- parse shortcode attributes ----------
        $atts = shortcode_atts(
            array(
                'status' => '',
                'limit' => 50,
            ),
            $atts,
            'nm_entries_last_30_days'
        );

        $limit = absint($atts['limit']) ?: 50;
        $status = sanitize_text_field($atts['status']);

        // ---------- resolve the entry table ----------
        $entries_table = $wpdb->prefix . 'nm_entries';

        // ---------- fetch base entries (last 30 days) ----------
        if ($status !== '') {
            $rows = $wpdb->get_results(
                $wpdb->prepare( 
                    "SELECT id, user_id, entry_id, entry_status, is_viewed, created_at, updated_at
             FROM {$entries_table}
             WHERE created_at >= DATE_SUB( NOW(), INTERVAL 30 DAY )
               AND entry_status = %s
             ORDER BY created_at DESC
             LIMIT %d",
                    $status,
                    $limit
                )
            );
        } else {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, user_id, entry_id, entry_status, is_viewed, created_at, updated_at
             FROM {$entries_table}
             WHERE created_at >= DATE_SUB( NOW(), INTERVAL 30 DAY )
             ORDER BY created_at DESC
             LIMIT %d",
                    $limit
                )
            );
        }

        if (empty($rows)) {
            return '<p class="nm-entries-empty">No entries found in the last 30 days.</p>';
        }

        // ---------- collect all entry IDs for the meta lookup ----------
        $entry_ids = array_map(fn($r) => intval($r->entry_id), $rows);

        // ---------- auto-discover where agency_id is stored ----------
        $agency_map = self::nm_resolve_agency_ids($entry_ids);

        // ---------- build output ----------
        ob_start();
        ?>
        <ul class="nm-entries-wrapper">
            <?php foreach ($rows as $row):
                $agency_id = $agency_map[$row->entry_id] ?? '';
                if (is_empty($agency_id)) {
                    continue;
                }
                $entry_url = $agency_id
                    ? add_query_arg(array('agency-id' => $agency_id), home_url('/entry-details/'))
                    : '';
                ?>
                <li data-agency="<?php echo $row->is_viewed; ?>"
                    class="<?php echo $row->is_viewed == 1 ? 'marked_read_nm' : 'marked_unread_nm'; ?>">
                    Entry
                    <?php if ($entry_url): ?>
                        <a data-agency="<?php echo $agency_id; ?>"  data-entry="<?php echo $row->entry_id; ?>" href="<?php echo esc_url($entry_url); ?>">
                            #<?php echo esc_html($row->entry_id); ?>
                        </a>
                    <?php else: ?>
                        #<?php echo esc_html($row->entry_id); ?>
                    <?php endif; ?>
                    was marked as <?php echo esc_html($row->entry_status); ?>
                    on <?php echo esc_html($row->created_at); ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php
        return ob_get_clean();
    }

    /**
     * Auto-discovers agency_id values for a set of entry IDs.
     *
     * Checks these tables in order (stops at first hit):
     *   1. wp_frm_item_metas  — Formidable Forms (field_key LIKE '%agency_id%')
     *   2. wp_gf_entry_meta   — Gravity Forms    (meta_key  LIKE '%agency_id%')
     *   3. wp_postmeta        — Custom post meta  (meta_key  LIKE '%agency_id%')
     *
     * Returns: array keyed by entry_id => agency_id value.
     */
    private static function nm_resolve_agency_ids(array $entry_ids): array
    {
        global $wpdb;

        if (empty($entry_ids)) {
            return [];
        }

        $entry_placeholders = implode(',', array_fill(0, count($entry_ids), '%d'));

        $frm_fields = $wpdb->prefix . 'frm_fields';
        $frm_metas = $wpdb->prefix . 'frm_item_metas';

        if ($wpdb->get_var("SHOW TABLES LIKE '{$frm_metas}'") !== $frm_metas) {
            return [];
        }

        // Fetch ALL field IDs whose key contains 'agency_id'
        // (covers agency_id, agency_id2, agency_id3 ... across different forms)
        $field_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT id FROM {$frm_fields} WHERE field_key LIKE %s",
                '%agency_id%'
            )
        );

        if (empty($field_ids)) {
            return [];
        }

        $field_placeholders = implode(',', array_fill(0, count($field_ids), '%d'));

        // Now query metas matching ANY of those field IDs for our entry IDs
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT item_id AS entry_id, meta_value AS agency_id
             FROM {$frm_metas}
             WHERE field_id IN ({$field_placeholders})
               AND item_id   IN ({$entry_placeholders})",
                array_merge($field_ids, $entry_ids)
            )
        );

        if (empty($results)) {
            return [];
        }

        return array_column((array) $results, 'agency_id', 'entry_id');
    }

}
