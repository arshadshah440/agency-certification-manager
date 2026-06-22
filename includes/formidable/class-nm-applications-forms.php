<?php

if (!defined('ABSPATH')) {
    exit;
}


class NM_Applications_Controllers
{
    public static function init()
    {
        add_action('frm_after_create_entry', [__CLASS__, 'update_agency_locations'], 30, 2);
        add_filter('frm_notification_attachment', [__CLASS__, 'nm_attach_latest_pdf_if_field_true'], 10, 3);
        add_filter('frm_validate_entry', [__CLASS__, 'nm_validate_opre_form_submission'], 10, 3);

    }
    public static function nm_validate_opre_form_submission($errors, $values, $form)
    {

        // Only target OPRE form
        if ($form->form_key !== 'opre') {
            return $errors;
        }

        // Must be logged in
        if (!is_user_logged_in()) {
            $errors['form'] = __('You must be logged in to submit this form.', 'textdomain');
            return $errors;
        }

        $user_id = get_current_user_id();

        $is_enabled = get_field('enable_opre_applications', 'option');
        $total_allowed_entries = (int) get_field('total_opre_application_allowed', 'option');

        global $wpdb;

        $last_24_hours = gmdate('Y-m-d H:i:s', strtotime('-24 hours'));

        // 1️⃣ Total submissions in last 24 hours (all users)
        $total_entries_last_24h = (int) $wpdb->get_var(
            $wpdb->prepare(
                "
			SELECT COUNT(*)
			FROM {$wpdb->prefix}frm_items
			WHERE form_id = %d
			  AND is_draft = 0
			  AND created_at >= %s
			",
                $form->id,
                $last_24_hours
            )
        );

        // 2️⃣ Check if current user already submitted
        $user_has_submitted = (int) $wpdb->get_var(
            $wpdb->prepare(
                "
			SELECT COUNT(*)
			FROM {$wpdb->prefix}frm_items
			WHERE form_id = %d
			  AND user_id = %d
			  AND is_draft = 0
			  AND created_at >= %s
			",
                $form->id,
                $user_id,
                $last_24_hours
            )
        );

        // 3️⃣ Validation rules
        if (!$is_enabled) {
            $errors['form'] = __('OPRE applications are currently closed.', 'textdomain');
        }

        // If total_allowed_entries is -1, there is no daily cap — skip that check
        if ($total_allowed_entries !== -1 && $total_entries_last_24h >= $total_allowed_entries) {
            $errors['form'] = __('The daily application limit has been reached. Please try again later.', 'textdomain');
        }

        if ($user_has_submitted > 0) {
            $errors['form'] = __('You have already submitted an OPRE application in the last 24 hours.', 'textdomain');
        }

        return $errors;
    }
    public static function nm_attach_latest_pdf_if_field_true($attachments, $form, $args)
    {

        error_log("ahjsdhajhdjadhajdh");
        // Only for a specific form and email notification
        if ($form->id == 53 && $args['email_key'] == 5418) {
            error_log("kililo");

            $entry = $args['entry'];

            // Replace 789 with the field ID you want to check
            $field_value = isset($entry->metas[2014]) ? $entry->metas[2014] : '';
            $entry_id = isset($entry->metas[2013]) ? $entry->metas[2013] : '';

            // Only attach if field value is '1' (true)
            if ($field_value == '1') {
                error_log("attaching new docs $field_value entry id $entry_id");
                // Use your helper function to get the latest attachment by entry ID

                $file_path = self::nm_get_latest_attachment_by_entry_id($entry_id);

                if ($file_path && file_exists($file_path)) {
                    $attachments[] = $file_path;
                }
            }
        }

        return $attachments;
    }

    // Your helper function to get the latest attachment for an entry
    public static function nm_get_latest_attachment_by_entry_id($entry_id)
    {
        $args = [
            'post_type' => 'attachment',
            'posts_per_page' => 1,
            'post_status' => 'inherit',
            'meta_query' => [
                [
                    'key' => '_nm_letter_related_entry_id',
                    'value' => $entry_id,
                    'compare' => '='
                ]
            ],
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        $attachments = get_posts($args);

        if (empty($attachments)) {
            return false;
        }

        return get_attached_file($attachments[0]->ID);
    }
    public static function update_agency_locations($entry_id, $form_id)
    {
        // Replace with your actual Formidable form ID
        $target_form_id = 31; // Change this to your form ID

        // Only run for specific form
        if ($form_id != $target_form_id) {
            error_log('id not matched');
            return;
        }
        $user_id = get_current_user_id();
        if (!$user_id) {
            error_log("not logged in");
            return false;
        }
        $form_data = self::get_form_field_values($entry_id);
        error_log("user id $user_id");
        if ($form_data) {
            error_log("nava data");
            self::add_repeater_row_to_user_agency($form_data, $user_id);
        }
        return;
    }
    private static function get_form_field_values($entry_id)
    {

        // Field IDs
        $location_type = 894;
        $location_name = 896;
        $location_npi = 898;
        $is_mailing_same = 899;
        $mailing_address = 901;
        $physical_address = 900;


        $data = $_POST['item_meta'];


        if ($data[894] == 'New provider') {
            return;
        }


        $location_name = $data[896];
        $location_npi = $data[898];
        // Prepare addresses
        $physicalAddress = [
            'street1' => sanitize_text_field($data[900]['line1']),
            'street2' => sanitize_text_field($data[900]['line2']),
            'street3' => '',
            'city' => sanitize_text_field($data[900]['city']),
            'state' => sanitize_text_field($data[900]['state']),
            'zip' => sanitize_text_field($data[900]['zip']),
            'country' => 'US',
        ];
        $is_mailing = '';
        // If “Mailing same as physical” = No, use separate address; otherwise copy physical
        if (isset($data[899]) && strtolower($data[899]) == 'no') {
            $is_mailing = $data[899];
            $mailingAddress = [
                'street1' => sanitize_text_field($data[901]['line1']),
                'street2' => sanitize_text_field($data[901]['line2']),
                'street3' => '',
                'city' => sanitize_text_field($data[901]['city']),
                'state' => sanitize_text_field($data[901]['state']),
                'zip' => sanitize_text_field($data[901]['zip']),
                'country' => 'US',
            ];
        } else {
            $mailingAddress = $physicalAddress;
        }

        return [
            'location_name' => $location_name,
            'locations_npi' => $location_npi,
            'physical_address' => $physicalAddress,
            'is_your_mailing_address_the_same_as_your_physical_address' => $is_mailing,
            'mailing_address' => $mailingAddress
        ];
    }
    public static function add_repeater_row_to_user_agency(array $new_row, $user_id)
    {

        // 1. Get current user ID


        // 2. Get Agency post owned by current user
        //    (Assumes "agency" post type & meta key "owner" stores user ID)
        $agency = get_posts([
            'post_type' => 'agencies',
            'numberposts' => 1,
            'fields' => 'ids',
            'author' => $user_id,
        ]);

        if (empty($agency)) {
            error_log("nae lubhi");
        }

        $agency_id = $agency[0];

        // 3. Repeater field name
        $repeater_field = 'agency_sub_locations';

        // 4. Add a new row to repeater
        // $new_row must be key-value array matching ACF fields inside repeater
        $added = add_row($repeater_field, $new_row, $agency_id);

        if (!$added) {
            error_log('error a gya');
        }
        error_log("added new row");

        return true;
    }



}