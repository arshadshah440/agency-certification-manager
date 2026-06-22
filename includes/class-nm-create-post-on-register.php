<?php
if (!defined('ABSPATH')) {
    exit;
}

class NM_Create_Post
{
    public static function init()
    {
    }


    public static function create_post($entry_id, $form_id)
    {
        // Only process for your target form
        if ($form_id != 22) {
            return;
        }

        $data = $_POST['item_meta'];

        // Field IDs
        $job_title = 449;
        // $primary_first_name = 447;
        // $primary_last_name = 448;
        // $primary_email = 1985;
        // $user_first_name = 462;
        // $user_last_name = 463;
        // $user_email = 464;

        // Get job title to determine which fields to use
        $job_title_value = sanitize_text_field(FrmProEntriesController::get_field_value_shortcode([
            'field_id' => $job_title,
            'entry_id' => $entry_id
        ]));

        // Check if job title is one of the executive roles
        $executive_roles = ['Clinical Director', 'Clinical Supervisor', 'CEO'];
        $is_executive = in_array($job_title_value, $executive_roles);
        $emailid = 0;
        if ($is_executive) {
            $emailid = 1985;
        } else {
            $emailid = 464;
        }
        $email = sanitize_email(FrmProEntriesController::get_field_value_shortcode([
            'field_id' => $emailid,
            'entry_id' => $entry_id
        ]));
        // Get user by primary email (Field 452)
        $user = get_user_by('email', $email);
        if (!$user) {
            error_log('❌ No user found for email: ' . $email);
            return;
        }
        $userID = $user->ID;

        // Create the new Agency post
        $agency_args = [
            'post_type' => 'agencies',
            'post_title' => sanitize_text_field($data[443]), // Agency Name
            'post_content' => 'Auto-generated from registration form.',
            'post_author' => $userID,
            'post_status' => 'publish',
        ];
        $agencyID = wp_insert_post($agency_args);

        if (is_wp_error($agencyID)) {
            error_log('❌ Agency post creation failed: ' . $agencyID->get_error_message());
            return;
        }

        // Prepare addresses
        $physicalAddress = [
            'street1' => sanitize_text_field($data[445]['line1']),
            'street2' => sanitize_text_field($data[445]['line2']),
            'street3' => '',
            'city' => sanitize_text_field($data[445]['city']),
            'state' => sanitize_text_field($data[445]['state']),
            'zip' => sanitize_text_field($data[445]['zip']),
            'country' => 'US',
        ];

        // If “Mailing same as physical” = No, use separate address; otherwise copy physical
        if (isset($data[441]) && strtolower($data[441]) == 'no' && isset($data[446]) && !empty($data[446])) {
            $mailingAddress = [
                'street1' => sanitize_text_field($data[446]['line1']),
                'street2' => sanitize_text_field($data[446]['line2']),
                'street3' => '',
                'city' => sanitize_text_field($data[446]['city']),
                'state' => sanitize_text_field($data[446]['state']),
                'zip' => sanitize_text_field($data[446]['zip']),
                'country' => 'US',
            ];
        } else {
            $mailingAddress = $physicalAddress;
        }

        // Update ACF fields for Agency (based on JSON keys)
        update_field('field_60818d93e3284', $data[443], $agencyID); // Agency Name
        update_field('physical_address', $physicalAddress, $agencyID); // Physical Address
        update_field('field_6075da4e2775c', $data[441], $agencyID); // Mailing same as physical
        update_field('mailing_address', $mailingAddress, $agencyID); // Mailing Address
        update_field('field_5eb4107a1725d', $data[1985], $agencyID); // Email
        update_field('field_5eb4108d1725e', $data[455], $agencyID); // Phone
        update_field('field_607c368b5c6ef', $data[458], $agencyID); // Medicaid Enrollment ID
        update_field('field_607c36f95c6f0', $data[459], $agencyID); // Group NPI
        update_field('field_607c37045c6f1', $data[457], $agencyID); // Agency Type (select)
        update_field('field_607c37ad5c6f2', ($data[457] == 'Other' ? 'Other' : ''), $agencyID); // Other agency type
        update_field('field_6086ced72ce4c', 'No', $agencyID); // Roster Approved
        update_field('field_6084461555260', $data[468], $agencyID); // Active status
        update_field('field_634e8a3d01a54', 0, $agencyID); // Sup Cert Auto Add (false)

        // Optional: Add secondary contact info as custom meta (if you plan to use later)
        update_post_meta($agencyID, 'secondary_contact_first', sanitize_text_field($data[462]));
        update_post_meta($agencyID, 'secondary_contact_last', sanitize_text_field($data[463]));
        update_post_meta($agencyID, 'secondary_contact_email', sanitize_email($data[1985]));

        /*
         * Link User to Agency
         */
        update_field('field_607ac18a9e963', $agencyID, 'user_' . $userID); // Agency ID link
        update_field('field_607c38683ca3c', $data[449], 'user_' . $userID); // Job Title
        update_field('field_607c38ab3ca3d', ($data[457] == 'Other' ? 'Other' : ''), 'user_' . $userID); // Job Title Other
        update_field('field_607c3a243ca3e', 'Yes', 'user_' . $userID); // Primary Contact
        update_field('field_634e8ab8bb12c', 0, 'user_' . $userID); // Sup Cert Auto Add
        update_field('field_63f3b713da399', $data[455], 'user_' . $userID); // Phone

        error_log('✅ Agency created successfully: ID ' . $agencyID);
    }

}
