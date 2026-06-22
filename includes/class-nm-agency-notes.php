<?php
if (!defined('ABSPATH')) {
    exit;
}

class NM_Agency_Notes
{
    const TABLE = 'nm_agency_notes';

    public static function init()
    {
        add_action('wp_ajax_nm_notes_get',    [__CLASS__, 'ajax_get']);
        add_action('wp_ajax_nm_notes_save',   [__CLASS__, 'ajax_save']);
        add_action('wp_ajax_nm_notes_delete', [__CLASS__, 'ajax_delete']);
        add_action('wp_ajax_nm_notes_get_all', [__CLASS__, 'ajax_get_all']);
        // Create table on every load if it doesn't exist yet
        add_action('plugins_loaded', [__CLASS__, 'maybe_create_table']);
    }

    public static function maybe_create_table()
    {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            self::create_table();
        }
    }

    public static function create_table()
    {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            agency_id   BIGINT UNSIGNED NOT NULL DEFAULT 0,
            note_title  VARCHAR(255)    NOT NULL DEFAULT '',
            note_text   LONGTEXT        NOT NULL,
            added_by    BIGINT UNSIGNED NOT NULL DEFAULT 0,
            added_by_name VARCHAR(255)  NOT NULL DEFAULT '',
            created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY agency_id (agency_id),
            KEY added_by (added_by)
        ) {$charset_collate};";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /* ── helpers ─────────────────────────────────────────────── */

    private static function can_write()
    {
        $user = wp_get_current_user();
        return array_intersect(['administrator', 'supervisor', 'nm_manager'], (array) $user->roles);
    }

    private static function agency_id_from_request()
    {
        return isset($_POST['agency_id']) ? absint($_POST['agency_id']) : 0;
    }

    public static function get_notes_for_agency(int $agency_id): array
    {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE agency_id = %d ORDER BY created_at DESC",
                $agency_id
            ),
            ARRAY_A
        ) ?: [];
    }

    /* ── AJAX: get list ───────────────────────────────────────── */

    public static function ajax_get()
    {
        check_ajax_referer('register_email_nonce', 'security');
        $agency_id = self::agency_id_from_request();
        if (!$agency_id) {
            wp_send_json_error(['message' => 'Invalid agency.']);
        }
        $notes = self::get_notes_for_agency($agency_id);
        $current_user_id = get_current_user_id();
        $can_write = (bool) self::can_write();
        wp_send_json_success([
            'notes'          => $notes,
            'current_user_id'=> $current_user_id,
            'can_write'      => $can_write,
        ]);
    }

    /* ── AJAX: save (insert or update) ───────────────────────── */

    public static function ajax_save()
    {
        check_ajax_referer('register_email_nonce', 'security');
        if (!self::can_write()) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }

        global $wpdb;
        $table      = $wpdb->prefix . self::TABLE;
        $agency_id  = self::agency_id_from_request();
        $note_id    = isset($_POST['note_id']) ? absint($_POST['note_id']) : 0;
        $title      = sanitize_text_field($_POST['note_title'] ?? '');
        $text       = sanitize_textarea_field($_POST['note_text'] ?? '');
        $user       = wp_get_current_user();
        $user_id    = $user->ID;

        if (!$agency_id || !$title || !$text) {
            wp_send_json_error(['message' => 'All fields are required.']);
        }

        if ($note_id) {
            // Only allow editing own note
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT added_by FROM {$table} WHERE id = %d", $note_id
            ));
            if (!$existing || (int) $existing->added_by !== $user_id) {
                wp_send_json_error(['message' => 'You can only edit your own notes.']);
            }
            $wpdb->update(
                $table,
                ['note_title' => $title, 'note_text' => $text],
                ['id' => $note_id],
                ['%s', '%s'],
                ['%d']
            );
            wp_send_json_success(['message' => 'Note updated.', 'note_id' => $note_id]);
        }

        $wpdb->insert($table, [
            'agency_id'     => $agency_id,
            'note_title'    => $title,
            'note_text'     => $text,
            'added_by'      => $user_id,
            'added_by_name' => $user->display_name,
            'created_at'    => current_time('mysql'),
        ], ['%d', '%s', '%s', '%d', '%s', '%s']);

        wp_send_json_success(['message' => 'Note saved.', 'note_id' => $wpdb->insert_id]);
    }

    /* ── AJAX: delete ─────────────────────────────────────────── */

    public static function ajax_delete()
    {
        check_ajax_referer('register_email_nonce', 'security');
        if (!self::can_write()) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }

        global $wpdb;
        $table   = $wpdb->prefix . self::TABLE;
        $note_id = isset($_POST['note_id']) ? absint($_POST['note_id']) : 0;
        $user_id = get_current_user_id();

        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT added_by FROM {$table} WHERE id = %d", $note_id
        ));
        if (!$existing || (int) $existing->added_by !== $user_id) {
            wp_send_json_error(['message' => 'You can only delete your own notes.']);
        }

        $wpdb->delete($table, ['id' => $note_id], ['%d']);
        wp_send_json_success(['message' => 'Note deleted.']);
    }

    /* ── AJAX: get all with pagination ──────────────────────── */

    public static function ajax_get_all()
    {
        check_ajax_referer('register_email_nonce', 'security');
        $agency_id = self::agency_id_from_request();
        if (!$agency_id) {
            wp_send_json_error(['message' => 'Invalid agency.']);
        }

        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 10;
        $offset = ($page - 1) * $per_page;

        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE agency_id = %d",
            $agency_id
        ));

        $notes = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE agency_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $agency_id,
                $per_page,
                $offset
            ),
            ARRAY_A
        ) ?: [];

        $current_user_id = get_current_user_id();
        $can_write = (bool) self::can_write();
        
        // Get agency name
        $agency_name = '';
        $agency_post = get_post($agency_id);
        if ($agency_post && $agency_post->post_type === 'agencies') {
            $agency_name = $agency_post->post_title;
        }

        wp_send_json_success([
            'notes'           => $notes,
            'total'           => intval($total),
            'page'            => $page,
            'per_page'        => $per_page,
            'current_user_id' => $current_user_id,
            'can_write'       => $can_write,
            'agency_name'     => $agency_name,
        ]);
    }
}
