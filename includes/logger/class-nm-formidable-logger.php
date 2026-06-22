<?php
if (!defined('ABSPATH')) {
    exit;
}

class NM_Formidable_Entry_Logger
{

    /** 
     * Initialize class: register all hooks
     */
    public static function init()
    {
        add_filter('frm_validate_entry', [__CLASS__, 'capture_old_data'], 1, 2);
        add_action('frm_after_update_entry', [__CLASS__, 'log_changes'], 10, 2);
        add_action('my_after_update_entry_meta', [__CLASS__, 'log_changes'], 10, 2);
        add_shortcode('frm_entry_log', [__CLASS__, 'shortcode_entry_logs']);
    }

    /**
     * ======================================================================================
     * STEP 1: Capture EXISTING entry data before Formidable updates it
     * ======================================================================================
     */
    public static function capture_old_data($errors, $values)
    {
        if (!empty($values['id'])) {
            $entry_id = intval($values['id']);

            if (isset($GLOBALS['nm_old_entry_data'][$entry_id])) {
                return $errors;
            }

            $old_entry = FrmEntry::getOne($entry_id, true);

            if (!empty($old_entry)) {
                $GLOBALS['nm_old_entry_data'][$entry_id] = $old_entry->metas;
            }
        }

        return $errors;
    }

    /**
     * ======================================================================================
     * STEP 2: After update – compare old vs new and insert change logs
     * ======================================================================================
     */
    public static function log_changes($entry_id, $form_id)
    {
        global $wpdb;


        $table = self::get_table_name();

        self::maybe_create_table($table);

        $current_user = get_current_user_id();
        $new_entry = FrmEntry::getOne($entry_id, true);

        if (!$new_entry)
            return;

        $old_data = $GLOBALS['nm_old_entry_data'][$entry_id] ?? [];

        $changes_logged = 0;

        // Compare fields that exist in NEW entry
        foreach ($new_entry->metas as $field_id => $new_value) {
            if (!is_numeric($field_id))
                continue;

            $old_value = $old_data[$field_id] ?? '';

            $old_compare = is_array($old_value) ? $old_value : (string) $old_value;
            $new_compare = is_array($new_value) ? $new_value : (string) $new_value;

            if ($old_compare === $new_compare)
                continue;

            $field = FrmField::getOne($field_id);
            $field_name = $field ? $field->name : 'Unknown Field';

            $changes_logged += self::insert_log_row(
                $table,
                $entry_id,
                $current_user,
                $field_id,
                $old_value,
                $new_value,
                $field_name

            );
        }

        // Compare fields that existed before but were removed now
        foreach ($old_data as $field_id => $old_value) {
            if (!isset($new_entry->metas[$field_id])) {
                $field = FrmField::getOne($field_id);
                $field_name = $field ? $field->name : 'Unknown Field';
                $changes_logged += self::insert_log_row(
                    $table,
                    $entry_id,
                    $current_user,
                    $field_id,
                    $old_value,
                    "",
                    $field_name
                );
            }
        }

        unset($GLOBALS['nm_old_entry_data'][$entry_id]);
    }

    /**
     * Insert a change row into database
     */
    public static function insert_log_row($table, $entry_id, $user_id, $field_id, $old_value, $new_value, $fieldname)
    {
        global $wpdb;

        // $field = FrmField::getOne($field_id);
        // $field_name = $field ? $field->name : 'Unknown Field';

        $table = $table ?? $wpdb->prefix . 'frm_entry_logs';

        $insert = $wpdb->insert(
            $table,
            [
                'entry_id' => $entry_id,
                'user_id' => $user_id,
                'field_id' => $field_id,
                'field_name' => $fieldname,
                'old_value' => maybe_serialize($old_value),
                'new_value' => maybe_serialize($new_value),
                'changed_at' => current_time('mysql'),
            ]
        );

        return $insert ? 1 : 0;
    }

    /**
     * ======================================================================================
     * DATABASE TABLE HANDLING
     * ======================================================================================
     */
    private static function get_table_name()
    {
        global $wpdb;
        return $wpdb->prefix . 'frm_entry_logs';
    }

    private static function maybe_create_table($table)
    {
        global $wpdb;

        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table)
            return;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            entry_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED,
            field_id BIGINT UNSIGNED,
            field_name VARCHAR(255),
            old_value LONGTEXT,
            new_value LONGTEXT,
            changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_entry (entry_id),
            INDEX idx_changed (changed_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * ======================================================================================
     * NEW FEATURE:
     * Return latest X logs (no filtering – global)
     * ======================================================================================
     */
    public static function get_recent_logs($limit = 10)
    {
        global $wpdb;

        $table = self::get_table_name();
        $limit = intval($limit);

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} ORDER BY changed_at DESC LIMIT %d",
                $limit
            ),
            ARRAY_A
        );

        return $results ?: [];
    }

    /**
     * ======================================================================================
     * SHORTCODE: Show logs for a specific entry
     * ======================================================================================
     */
    public static function shortcode_entry_logs($atts)
    {
        global $wpdb;

        $atts = shortcode_atts(
            [
                'entry_id' => 0,
                'note' => false,
            ],
            $atts
        );

        $entry_id = intval($atts['entry_id']);
        $note = ($atts['note']);
        $note = !empty($note) ? true : false;

        if (!$entry_id) {
            return '<p>No entry ID provided.</p>';
        }

        $table = self::get_table_name();

        $logs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table}
             WHERE entry_id = %d
             ORDER BY changed_at DESC",
                $entry_id
            )
        );

        if (empty($logs)) {
            return '<p>No logs found for this entry.</p>';
        }

        // Pass note flag to renderer
        return self::render_logs_html($logs, $entry_id, $note);
    }


    public static function get_entry_logs_by_user($user_id, $entry_id = 0)
    {
        global $wpdb;

        $user_id = absint($user_id);
        $entry_id = absint($entry_id);

        if (!$user_id) {
            return [];
        }

        $table = self::get_table_name();
        $limit = 10;

        if ($entry_id) {
            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT *
                 FROM {$table}
                 WHERE user_id = %d AND entry_id = %d
                 ORDER BY changed_at DESC
                 LIMIT %d",
                    $user_id,
                    $entry_id,
                    $limit
                ),
                ARRAY_A
            );
        }

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
             FROM {$table}
             WHERE user_id = %d
             ORDER BY changed_at DESC
             LIMIT %d",
                $user_id,
                $limit
            ),
            ARRAY_A
        );
    }




    /**
     * Render logs HTML output (same as original)
     */
    /**
     * Render logs HTML output
     */
    private static function render_logs_html($logs, $entry_id, $note = false)
    {
        ob_start();

        foreach ($logs as $log) {

            $user = get_user_by('id', $log->user_id);
            $username = $user->display_name ?? $log->user_id;

            $dt = new DateTime($log->changed_at);
            $changedAt = $dt->format('F j, Y');

            // DEFAULT VALUES
            $old_value = self::get_readable_value($log->field_id, $log->old_value);
            $new_value = self::render_attachment_link($log->new_value);

            /**
             * NOTES MODE
             * - Show ONLY new value
             * - No field name
             * - Attachment link + attachment notes meta
             */
            if ($note) {

                $attachment_note = self::get_attachment_note_meta($log->new_value, true);


                // Skip if no attachment note exists
                if (empty($attachment_note)) {
                    continue;
                }
                ?>
                <div class="timeline-item ss">
                    <div class="dot green"></div>
                    <div class="content">
                        <p class="log-text">
                            <strong><?php echo esc_html($username); ?></strong> added a note
                        </p>

                        <p class="log-subtext">
                            <?php echo wp_kses_post($new_value); ?><br>
                            <em><?php echo esc_html($attachment_note); ?></em>
                        </p>

                        <p class="log-date">
                            <i class="fa-regular fa-clock"></i>
                            <?php echo esc_html($changedAt); ?>
                        </p>
                    </div>
                </div>
                <?php
                continue;
            }
            ?>

            <!-- DEFAULT MODE -->
            <?php if (!empty($old_value) && $old_value !== "") { ?>
                <div class="timeline-item">
                    <div class="dot green"></div>
                    <div class="content">
                        <p class="log-text">
                            <strong><?php echo esc_html($username); ?></strong> updated a field
                        </p>

                        <p class="log-subtext">
                            <strong><?php echo esc_html($log->field_name); ?></strong> :
                            from <?php echo wp_kses_post($old_value); ?>
                            to <?php echo wp_kses_post($new_value); ?>
                        </p>

                        <p class="log-date">
                            <i class="fa-regular fa-clock"></i>
                            <?php echo esc_html($changedAt); ?>
                        </p>
                    </div>
                </div>
            <?php } ?>


            <?php
        }

        return ob_get_clean();
    }



    /**
     * Render attachment link from ID or URL
     */
    private static function render_attachment_link($value)
    {
        $value = maybe_unserialize($value);

        $attachment_id = 0;
        $attachment_url = '';

        // Attachment ID
        if (is_numeric($value)) {
            $attachment_id = (int) $value;
            $attachment_url = wp_get_attachment_url($attachment_id);
        }

        // Attachment URL
        elseif (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
            $attachment_url = esc_url_raw($value);
            $attachment_id = attachment_url_to_postid($attachment_url);
        }
        if (!$attachment_url) {
            return $value;
        }

        // Get filename
        if ($attachment_id && ($file = get_attached_file($attachment_id))) {
            $file_name = basename($file);
        } else {
            $file_name = basename(parse_url($attachment_url, PHP_URL_PATH));
        }

        return sprintf(
            '<a href="%s" target="_blank" rel="noopener">%s</a>',
            esc_url($attachment_url),
            esc_html($file_name)
        );
    }

    /**
     * Get attachment notes meta
     */
    private static function get_attachment_note_meta($value, $note_status = false)
    {
        $attachment_id = 0;

        if (is_numeric($value)) {
            $attachment_id = (int) $value;
        } elseif (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
            $attachment_id = attachment_url_to_postid($value);
        }

        if (!$attachment_id) {
            return '';
        }

        if ($note_status) {
            $note = get_post_meta($attachment_id, '_nm_attachment_notes', true);

        } else {
            $note = get_post_meta($attachment_id, '_nm_supporting_note', true);

        }

        // if (empty($note)) {
        //     $note = get_post_meta($attachment_id, '_nm_attachment_notes', true);
        // }

        return $note ?: '';
    }

    private static function get_readable_value($field_id, $value)
    {
        $value = maybe_unserialize($value);

        if ($field_id === 0) {
            return is_array($value)
                ? esc_url($value['url'] ?? '')
                : esc_url($value);
        }

        $field = FrmField::getOne($field_id);

        if ($field && in_array($field->type, ['file', 'file_upload'], true)) {

            if (is_numeric($value)) {
                $url = wp_get_attachment_url($value);
                return $url
                    ? '<a href="' . esc_url($url) . '" target="_blank">' . esc_html(basename($url)) . '</a>'
                    : esc_html($value);
            }

            if (is_array($value)) {
                $links = array_map(function ($v) {
                    if (is_numeric($v)) {
                        $url = wp_get_attachment_url($v);
                        return $url
                            ? '<a href="' . esc_url($url) . '" target="_blank">' . esc_html(basename($url)) . '</a>'
                            : esc_html($v);
                    }
                    return esc_url($v);
                }, $value);

                return implode(', ', $links);
            }

            return esc_url($value);
        }

        if (is_array($value)) {
            return implode(', ', array_map('esc_html', $value));
        }

        return esc_html($value);
    }


    /**
     * Format table output
     */
    private static function format_value($value)
    {
        if (is_serialized($value)) {
            $value = maybe_unserialize($value);
        }

        if (is_array($value)) {
            return empty($value) ? '(empty)' : implode(', ', $value);
        }

        return $value !== '' ? esc_html($value) : '(empty)';
    }

}
