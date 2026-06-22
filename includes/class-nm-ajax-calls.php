<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once NM_APPS_PATH . 'includes/class-nm-helpers.php';
use Dompdf\Dompdf;

class NM_AJAX_CALLS
{

    public static function init()
    {
        add_action('wp_ajax_register_email_lookup', [__CLASS__, 'register_email_lookup']);
        add_action('wp_ajax_nopriv_register_email_lookup', [__CLASS__, 'register_email_lookup']);

        // dashboard filters
        add_action('wp_ajax_nm_filter_dashboard', [__CLASS__, 'nm_filter_dashboard']);
        add_action('wp_ajax_nopriv_nm_filter_dashboard', [__CLASS__, 'nm_filter_dashboard']);

        // entry filters
        add_action('wp_ajax_nm_filter_entries', [__CLASS__, 'nm_filter_entries']);
        add_action('wp_ajax_nopriv_nm_filter_entries', [__CLASS__, 'nm_filter_entries']);

        // attachment id
        add_action('wp_ajax_nm_attachment_status', [__CLASS__, 'nm_attachment_status']);
        add_action('wp_ajax_nopriv_nm_attachment_status', [__CLASS__, 'nm_attachment_status']);

        // attachment id
        add_action('wp_ajax_nm_opre_status', [__CLASS__, 'nm_opre_status']);
        add_action('wp_ajax_nopriv_nm_opre_status', [__CLASS__, 'nm_opre_status']);

        // attachment id
        add_action('wp_ajax_nm_change_entry_status', [__CLASS__, 'nm_change_entry_status']);
        add_action('wp_ajax_nopriv_nm_change_entry_status', [__CLASS__, 'nm_change_entry_status']);


        // attachment id
        add_action('wp_ajax_nm_replace_application_upload', [__CLASS__, 'nm_replace_application_upload_handler']);
        add_action('wp_ajax_nopriv_nm_replace_application_upload', [__CLASS__, 'nm_replace_application_upload_handler']);

        // nm_update_agency_details
        add_action('wp_ajax_nm_update_agency_details', [__CLASS__, 'nm_update_agency_details']);
        add_action('wp_ajax_nopriv_nm_update_agency_details', [__CLASS__, 'nm_update_agency_details']);

        // nm_generate_letter_callback
        add_action('wp_ajax_nm_generate_letter_callback', [__CLASS__, 'nm_generate_letter_callback']);
        add_action('wp_ajax_nopriv_nm_generate_letter_callback', [__CLASS__, 'nm_generate_letter_callback']);

        // nm_save_letter_callback
        add_action('wp_ajax_nm_save_letter_callback', [__CLASS__, 'nm_save_letter_callback']);
        add_action('wp_ajax_nopriv_nm_save_letter_callback', [__CLASS__, 'nm_save_letter_callback']);

        // nm_loaddocument
        add_action('wp_ajax_nm_load_doc_callback', [__CLASS__, 'nm_load_doc_callback']);
        add_action('wp_ajax_nopriv_nm_load_doc_callback', [__CLASS__, 'nm_load_doc_callback']);

        // nm_view entry details
        add_action('wp_ajax_nm_view_entry_details', [__CLASS__, 'nm_view_entry_details']);
        add_action('wp_ajax_nopriv_nm_view_entry_details', [__CLASS__, 'nm_view_entry_details']);

   // agency lookup by name
        add_action('wp_ajax_register_agency_lookup_by_name', [__CLASS__, 'register_agency_lookup_by_name']);
        add_action('wp_ajax_nopriv_register_agency_lookup_by_name', [__CLASS__, 'register_agency_lookup_by_name']);
    }

    public static function nm_opre_status()
    {
        // $entry_status = isset($_POST['entry_status']) ? sanitize_text_field($_POST['entry_status']) : 0;
        $entry_id = isset($_POST['entry_id']) ? sanitize_text_field($_POST['entry_id']) : 0;
        $entry_status = isset($_POST['entry_status']) ? wp_kses_post($_POST['entry_status']) : '';
        $letter_details = " ";

        $results = NM_Helpers::nm_upsert_entry($entry_id, $letter_details, $entry_status);

        wp_send_json_success([
            'success' => true,
            'message' => 'Status Update!!',
            'results' => $results
        ]);

    }
    public static function nm_change_entry_status()
    {
        global $wpdb;

        $entry_id = isset($_POST['currententry_id']) ? absint($_POST['currententry_id']) : 0;

        if (!$entry_id) {
            wp_send_json_error(['message' => 'Invalid entry ID.']);
            return;
        }

        $table_name = $wpdb->prefix . 'nm_entries';

        $updated = $wpdb->update(
            $table_name,
            ['is_viewed' => 1],         // SET is_viewed = 1
            ['entry_id' => $entry_id],  // WHERE entry_id = %d
            ['%d'],                     // format for is_viewed
            ['%d']                      // format for entry_id
        );

        if ($updated === false) {
            wp_send_json_error(['status' => false, 'message' => 'Failed to update entry.']);
            return;
        }

        wp_send_json_success(['status' => true, 'message' => 'Entry marked as viewed.']);
    }
    /**
     * AJAX handler to generate PDF from Formidable Forms entry
     * Requires: DomPDF library (composer require dompdf/dompdf)
     */
    public static function nm_view_entry_details()
    {
        // Get and sanitize entry ID from POST
        $entry_id = isset($_POST['entry_id']) ? intval($_POST['entry_id']) : 0;

        // Check if Formidable Forms is active
        if (!class_exists('FrmEntry')) {
            wp_send_json_error([
                'success' => false,
                'message' => 'Formidable Forms is not active.',
            ]);
            return;
        }

        // Get the entry
        $entry = FrmEntry::getOne($entry_id);

        if (!$entry) {
            wp_send_json_error([
                'success' => false,
                'message' => 'Entry not found.',
            ]);
            return;
        }


        // Get form fields
        $form_id = $entry->form_id;
        $fields = FrmField::get_all_for_form($form_id);
        $form = FrmForm::getOne($form_id);

        // Build HTML for PDF
        $html = self::build_entry_html($entry, $fields, $form);

        // $html= "<p>I am entry </p>";

        /**
         * ----------------------------------------------------
         * 2. Convert HTML → PDF
         * ----------------------------------------------------
         */

        $dompdf_options = new \Dompdf\Options();
        $dompdf_options->set('isRemoteEnabled', true);
        $dompdf_options->set('isHtml5ParserEnabled', true);
        $dompdf_options->set('defaultFont', 'DejaVu Sans');
        if (defined('ABSPATH')) {
            $dompdf_options->setChroot(realpath(ABSPATH));
        }
        $dompdf = new Dompdf($dompdf_options);
        $dompdf->loadHtml('<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        /**
         * ----------------------------------------------------
         * 3. Save PDF
         * ----------------------------------------------------
         */
        $upload_dir = wp_upload_dir();
        $file_name = sanitize_file_name('entry_details') . '-' . time() . '.pdf';
        $file_path = $upload_dir['basedir'] . '/' . $file_name;
        $file_url = $upload_dir['baseurl'] . '/' . $file_name;

        file_put_contents($file_path, $dompdf->output());

        wp_send_json_success([
            'success' => true,
            'message' => 'PDF generated successfully',
            'pdf_url' => $file_url,
            'pdf_path' => $file_path,
            'pdf_html' => $html
        ]);

    }

    /**
     * Build HTML content for PDF
     */
    private static function build_entry_html($entry, $fields, $form)
    {
        $entry_id = $entry->id;
        $form_name = isset($form->name) ? $form->name : 'Form Entry';

        // Start building HTML with inline CSS for PDF
        $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Entry #' . $entry_id . '</title>
       <style>
    * {
        font-family: DejaVu Sans, sans-serif !important;
    }
    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 12px;
        color: #333;
        line-height: 1.6;
    }
    .header {
        text-align: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 3px solid #0073aa;
    }
    .header h1 {
        margin: 0;
        color: #0073aa;
        font-size: 24px;
        font-family: DejaVu Sans, sans-serif;
    }
    .header h2 {
        margin: 5px 0 0 0;
        color: #666;
        font-size: 16px;
        font-weight: normal;
        font-family: DejaVu Sans, sans-serif;
    }
    .meta-info {
        background: #f5f5f5;
        padding: 15px;
        margin-bottom: 25px;
        border-radius: 4px;
    }
    .meta-info p {
        margin: 5px 0;
        font-family: DejaVu Sans, sans-serif;
    }
    .meta-info strong {
        font-family: DejaVu Sans, sans-serif;
    }
    .field-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }
    .field-table th {
        background-color: #0073aa;
        color: white;
        padding: 12px;
        text-align: left;
        font-family: DejaVu Sans, sans-serif;
    }
    .field-table td {
        padding: 10px 12px;
        font-family: DejaVu Sans, sans-serif;
        border-bottom: 1px solid #ddd;
    }
    .field-table tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    .field-name {
        width: 50%;
        vertical-align: top;
        font-family: DejaVu Sans, sans-serif;
    }
    .field-value {
        width: 50%;
        font-family: DejaVu Sans, sans-serif;
    }
    .footer {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #ddd;
        text-align: center;
        font-size: 10px;
        color: #999;
        font-family: DejaVu Sans, sans-serif;
    }
    .footer p {
        font-family: DejaVu Sans, sans-serif;
    }
    a {
        font-family: DejaVu Sans, sans-serif;
    }
</style>
    </head>
    <body>';

        // Header
        $html .= '<div class="header">';
        $html .= '<h1>' . esc_html($form_name) . '</h1>';
        $html .= '<h2>Entry #' . esc_html($entry_id) . '</h2>';
        $html .= '</div>';

        // Entry meta information
        $html .= '<div class="meta-info">';
        $html .= '<p><strong>Form ID:</strong> ' . esc_html($entry->form_id) . '</p>';
        $html .= '<p><strong>Submitted:</strong> ' . esc_html(
            wp_date(
                get_option('date_format') . ' ' . get_option('time_format'),
                strtotime($entry->created_at)
            )
        ) . '</p>';
        if ($entry->created_at !== $entry->updated_at) {
            $html .= '<p><strong>Last Updated:</strong> ' . esc_html(date('F j, Y g:i A', strtotime($entry->updated_at))) . '</p>';
        }
        $html .= '</div>';

        // Field values table
        $html .= '<table class="field-table">';
        $html .= '<thead><tr><th><strong>Field Name</strong></th><th><strong>Value</strong></th></tr></thead>';
        $html .= '<tbody>';

        $visible_fields_count = 0;

        foreach ($fields as $field) {
            // Skip divider, html, and other non-data fields
            if (in_array($field->type, array('divider', 'html', 'end_divider', 'captcha'))) {
                continue;
            }

            // Skip hidden fields
            if ($field->type === 'hidden') {
                continue;
            }

            // Skip buttons (submit, next, previous, etc.)
            if (in_array($field->type, array('submit', 'button', 'next', 'previous', 'break'))) {
                continue;
            }

            // Check field options for visibility
            $field_options = maybe_unserialize($field->field_options);
            if (
                is_array($field_options) && isset($field_options['classes']) &&
                (strpos($field_options['classes'], 'frm_hidden') !== false ||
                    strpos($field_options['classes'], 'hidden') !== false)
            ) {
                continue;
            }

            $field_name = $field->name;
            $field_id = $field->id;

            // Get the field value
            $field_value = FrmEntryMeta::get_entry_meta_by_field($entry_id, $field_id);

            // If empty, try alternative method
            if (empty($field_value) && property_exists($entry, 'metas') && isset($entry->metas[$field_id])) {
                $field_value = $entry->metas[$field_id];
            }

            // Handle arrays (checkboxes, multi-select)
            if (is_array($field_value)) {
                $field_value = implode(', ', array_filter($field_value));
            }

            // Format file fields - for PDF, just show the filename or URL
            if ($field->type === 'file' && !empty($field_value)) {
                if (is_numeric($field_value)) {
                    $file_url = wp_get_attachment_url($field_value);
                    if ($file_url) {
                        $field_value = '<a href="' . $file_url . '">View File</a>';
                    }
                } else {
                    $field_value = basename($field_value) . ' (' . $field_value . ')';
                    $field_value = '<a href="' . $field_value . '">View File</a>';

                }
            }

            // Format user ID fields
            if ($field->type === 'user_id' && !empty($field_value)) {
                $user = get_userdata($field_value);
                if ($user) {
                    $field_value = $user->display_name . ' (ID: ' . $field_value . ')';
                }
            }

            // Display empty value as dash
            if (empty($field_value) && $field_value !== '0') {
                $field_value = '—';
            }

            $html .= '<tr>';
            $html .= '<td class="field-name" style="font-family: DejaVu Sans, sans-serif;"><strong>' . esc_html($field_name) . '</strong></td>';
            $value = $field_value;

            // Check if value contains HTML tags
            if ($value !== strip_tags($value)) {
                // Contains HTML → allow safe HTML
                $html .= '<td class="field-value">' . wp_kses_post($value) . '</td>';
            } else {
                // Plain text → escape normally
                $html .= '<td class="field-value">' . esc_html($value) . '</td>';
            }
            $html .= '</tr>';

            $visible_fields_count++;
        }

        if ($visible_fields_count === 0) {
            $html .= '<tr><td colspan="2" style="text-align: center;">No visible fields found</td></tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        // Footer
        $html .= '<div class="footer">';
        $html .= '<p>Generated on ' . date('F j, Y g:i A') . '</p>';
        $html .= '</div>';

        $html .= '</body></html>';

        return $html;
    }



    public static function nm_load_doc_callback()
    {
        $entryid = isset($_POST['entry_id']) ? sanitize_text_field($_POST['entry_id']) : 0;

        $document_details = NM_Helpers::get_esign_document_details_by_entry_id($entryid);


        $doc_id = isset($document_details['document_id']) ? $document_details['document_id'] : "";
        $invite_hash = isset($document_details['invite_hash']) ? $document_details['invite_hash'] : "";
        $checksum = isset($document_details['checksum']) ? $document_details['checksum'] : "";

        ob_start();

        if ($doc_id && $invite_hash && $checksum) {

            // 1. Prepare the data array (matches the plugin's required structure)
            $esig_data = [
                'invite' => $invite_hash,
                'csum' => $checksum
            ];

            // 2. Base64 encode the JSON string
            $encoded_string = base64_encode(json_encode($esig_data));

            // 3. Construct the final URL using the 'wpesig' parameter
            $contract_pdf_url = add_query_arg([
                'wpesig' => $encoded_string,
            ], site_url('/e-signature-document/'));
            ?>

            <iframe id="contractFrame" class="document-preview-container" src="<?php echo esc_url($contract_pdf_url); ?>"
                style="width:100%; height:800px; border:none;">
            </iframe>

        <?php } else if ($doc_id && (empty($invite_hash) || empty($checksum))) {
            ?>
                <iframe id="contractFrame" class="document-preview-container"
                    src="<?php echo site_url() . '/e-signature-document/?esigpreview=1&document_id=' . $doc_id . ''; ?>"
                    style="width:100%; height:800px; border:none;">
                </iframe>
            <?php
        } else { ?>
                <p>No signed contract found or missing security tokens.</p>
        <?php }
        $esign_doc = ob_get_clean();

        wp_send_json_success([
            'success' => true,
            'message' => 'PDF generated successfully',
            'esign_doc' => $esign_doc,
            'doc_details' => $document_details

        ]);
    }

    public static function nm_save_letter_callback()
    {
        $entry_id      = isset($_POST['entry_id']) ? sanitize_text_field($_POST['entry_id']) : 0;
        $document_name = "letters";

        // Use the posted HTML from the frontend, as the user might have edited it.
        $html = isset($_POST['letter_mockup']) ? wp_kses_post($_POST['letter_mockup']) : '';

        // Note: Using wp_kses_post might strip some inline styles or custom tags like logo. 
        // If logo breaks, we may need a less restrictive sanitizer or custom allowed tags.
        // But for now, wp_kses_post is standard for wp content.
        // Actually wp_unslash might be needed before wp_kses_post if quotes are escaped.
        $html = isset($_POST['letter_mockup']) ? wp_unslash($_POST['letter_mockup']) : '';
        // Need to allow certain tags but wait, let's keep it unescaped for saving into DB 
        // to preserve the logo and structure if wp_kses_post is too restrictive.
        // A safer way if it's admin-only is to use $html without kses, but let's use wp_kses_post
        // Wait, the original code in generate_letter does: wp_kses_post($_POST['nm_letter_message'])
        // Let's use wp_unslash
        
        $html = isset($_POST['letter_mockup']) ? wp_unslash($_POST['letter_mockup']) : '';

        if (empty($html)) {
            wp_send_json_error(['message' => 'No letter found. Please generate the letter first.']);
            return;
        }

        $results            = NM_Helpers::nm_upsert_entry($entry_id, $html);
        $attachment_details = NM_Helpers::nm_generate_pdf_attachment($html, $document_name, $entry_id);

        $results['attachment_detail'] = $attachment_details;
        wp_send_json_success($results);
    }

    public static function nm_generate_letter_callback()
    {
        if (!check_ajax_referer('register_email_nonce', 'security', false)) {
            wp_send_json_error(['message' => 'Security check failed.'], 400);
        }

        // Sanitize inputs
        $letter_date = sanitize_text_field($_POST['letter_date'] ?? date('Y-m-d'));
        $toggle_entry_status = sanitize_text_field($_POST['toggle_entry_status'] ?? '');
        $nm_program_name = sanitize_text_field($_POST['nm_program_name'] ?? '');
        $nm_letter_message = wp_kses_post($_POST['nm_letter_message'] ?? '');
        $document_name = sanitize_text_field($_POST['document_name'] ?? 'letter');
        $letter_for = sanitize_text_field($_POST['letter_for'] ?? '');
        $nm_letter_durations = sanitize_text_field($_POST['nm_letter_durations'] ?? '');
        $entry_id = sanitize_text_field($_POST['entry_id'] ?? '');
        $agency_id = sanitize_text_field($_POST['agency_id'] ?? '');
        $nm_program_deny_reason = sanitize_text_field($_POST['deny_reason'] ?? '');

        if (empty($entry_id) || empty($agency_id)) {
            wp_send_json_error(['message' => 'Required Details Not Found'], 400);
        }

        // Logged-in user email
        $user_id = get_current_user_id();
        $user_name = '';
        $current_user_email = '';
        if ($user_id) {
            $user_info = get_userdata($user_id);
            $current_user_email = $user_info->user_email;
            $user_name = $user_info->first_name . '' . $user_info->last_name . ' - ' . $nm_program_name . ' Manager';
        }

        // Dates
        $approval_start_date = date('Y-m-d');
        $approval_end_date = '';

        if (!empty($letter_date) && !empty($nm_letter_durations)) {
            $approval_end_date = NM_Helpers::nm_get_date_from_years(
                $letter_date,
                $nm_letter_durations
            );

            $approval_start_date = date('F j, Y', strtotime($letter_date));
        }

        // Agency details
        $agency_details = NM_Helpers::get_agency_details_by_its_id($agency_id);
        $nm_agency_name = $agency_details['agency_name'] ?? '';
        $nm_agency_address = $agency_details['physical_address'] ?? '';
        $nm_medical_id = $agency_details['medicaid_id'] ?? '';
        $nm_npi = $agency_details['npi'] ?? '';


        $nm_agency_address_raw = $agency_details['physical_address'] ?? '';

        // Remove HTML tags and decode entities
        $nm_agency_address = wp_strip_all_tags(
            html_entity_decode($nm_agency_address_raw, ENT_QUOTES | ENT_HTML5, 'UTF-8')
        );

        // Normalize whitespace
        $nm_agency_address = preg_replace('/\s+/', ' ', trim($nm_agency_address));


        /**
         * ----------------------------------------------------
         * TEMPLATE DATA (USED BY TEXT TEMPLATE)
         * ----------------------------------------------------
         */
        $template_data = [
            'template_date' => $approval_start_date ?? date('Y-m-d'),
            'program_name' => $nm_program_name,
            'agency_address' => $nm_agency_address,
            'agency_name' => $nm_agency_name,
            'letter_duration' => $nm_letter_durations . ' years',
            'for_details' => $letter_for,
            'approval_start_date' => $approval_start_date,
            'approval_end_date' => $approval_end_date,
            'medicaid_id' => $nm_medical_id,
            'npi' => $nm_npi,
            'custom_message' => $nm_letter_message,
            'ccss_contact_email' => $current_user_email,
            'program_contact_details' => ucfirst($user_name),
            'deny_reason' => $nm_program_deny_reason
        ];
        $html = '';
        $entry_status = '';

        if ($toggle_entry_status == 'approve') {
            /**
             * ----------------------------------------------------
             * 1. Load HTML / Text Template
             * ----------------------------------------------------
             */
            ob_start();
            include NM_APPS_PATH . '/dashboard/components/nm-certification-letter.php';
            $html = ob_get_clean();

            if (empty($html)) {
                wp_send_json_error(['message' => 'Failed to generate letter HTML'], 500);
            }

            /**
             * ----------------------------------------------------
             * 2 & 3. Convert HTML → PDF using the same function as Save Letter
             * ----------------------------------------------------
             */
            $attachment = NM_Helpers::nm_generate_pdf_attachment($html, $document_name, $entry_id);
            if (!$attachment) {
                wp_send_json_error(['message' => 'Failed to generate PDF'], 500);
            }
            $file_url     = $attachment['url'];
            $entry_status = 'approved';

        } else {
            /**
             * ----------------------------------------------------
             * 1. Load HTML / Text Template
             * ----------------------------------------------------
             */
            ob_start();
            include NM_APPS_PATH . '/dashboard/components/nm-certification-deny.php';
            $html = ob_get_clean();

            if (empty($html)) {
                wp_send_json_error(['message' => 'Failed to generate letter HTML'], 500);
            }

            /**
             * ----------------------------------------------------
             * 2 & 3. Convert HTML → PDF using the same function as Save Letter
             * ----------------------------------------------------
             */
            $attachment = NM_Helpers::nm_generate_pdf_attachment($html, $document_name . '-deny', $entry_id);
            if (!$attachment) {
                wp_send_json_error(['message' => 'Failed to generate PDF'], 500);
            }
            $file_url     = $attachment['url'];
            $entry_status = 'denied';

        }

        $results = NM_Helpers::nm_upsert_entry($entry_id, $html, $entry_status);

        $send_email_to_all = NM_Helpers::nm_send_mail_to_all_managers($entry_id, $agency_id, $file_url, $entry_status);

        /**
         * ----------------------------------------------------
         * 5. Return success
         * ----------------------------------------------------
         */
        wp_send_json_success([
            'message' => 'PDF generated successfully',
            'url' => $file_url,
            'template_data' => $template_data,
            'letter_mockup' => $html,
            'status_updated' => $results,
            'sent_email' => $send_email_to_all

        ]);

    }


    public static function nm_update_agency_details()
    {
        if (!check_ajax_referer('register_email_nonce', 'security', false)) {
            wp_send_json_error([
                'message' => 'Security check failed.'
            ], 400);
        }
        $post_id = absint($_POST['post_id']);
        $user_id = absint($_POST['user_id']);
        if (!$post_id && !$user_id) {
            wp_send_json_error(['message' => 'Invalid post']);
        }

        // Build ACF address array
        $acf_address = [
            'street1' => sanitize_text_field($_POST['address_1']),
            'street2' => sanitize_text_field($_POST['address_2']),
            'street3' => '',
            'city' => sanitize_text_field($_POST['city']),
            'state' => sanitize_text_field($_POST['state']),
            'zip' => sanitize_text_field($_POST['zip']),
            'country' => "US",
        ];

        if ($post_id) {
            update_field('physical_address', $acf_address, $post_id);

        } else if ($user_id) {
            update_field('user_address', $acf_address, 'user_' . $user_id);

        }



        wp_send_json_success();
    }

    public static function nm_replace_application_upload_handler()
    {

        // Check nonce if you added one for security
        // if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'your_nonce')) {
        //     wp_send_json_error(['message' => 'Security check failed']);
        // }

        // Get application ID
        $entry_id = isset($_POST['entry_id']) ? sanitize_text_field($_POST['entry_id']) : 0;
        $field_id = isset($_POST['field_id']) ? sanitize_text_field($_POST['field_id']) : 0;
        $application_note = isset($_POST['application_note']) ? sanitize_text_field($_POST['application_note']) : 0;
        $toggle_supplemental_docs = isset($_POST['toggle_supplemental_docs']) ? sanitize_text_field($_POST['toggle_supplemental_docs']) : 0;
        $document_name = isset($_POST['document_name']) ? sanitize_text_field($_POST['document_name']) : "";
        $supporting_note = isset($_POST['supporting_note']) ? sanitize_text_field($_POST['supporting_note']) : "";

        $response = [
            'updated_fields' => [],
            'uploaded_files' => [],
        ];

        // ---------- Handle main file ----------
        if (!empty($_FILES['resume_file']['name'])) {
            $main_file = $_FILES['resume_file'];

            // Use WordPress upload function
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('resume_file', 0); // 0 means no post parent
            if (is_wp_error($attachment_id)) {
                wp_send_json_error(['message' => 'Error uploading main file']);
            }

            $main_file_url = wp_get_attachment_url($attachment_id);
            $response['uploaded_files']['resume_file'] = $attachment_id;
            update_post_meta($attachment_id, '_nm_attachment_status', 1);
            update_post_meta($attachment_id, '_nm_attachment_notes', $application_note);


        }

        // ---------- Handle supplemental file if provided ----------
        if (!empty($_FILES['supporting_file']['name']) && $toggle_supplemental_docs == "yes") {
            $support_file = $_FILES['supporting_file'];

            $support_attachment_id = media_handle_upload('supporting_file', 0);
            if (is_wp_error($support_attachment_id)) {
                wp_send_json_error(['message' => 'Error uploading support file']);
            }

            $support_file_url = wp_get_attachment_url($support_attachment_id);
            update_post_meta($support_attachment_id, '_nm_document_name', $document_name);
            update_post_meta($support_attachment_id, '_nm_supporting_note', $supporting_note);
            update_post_meta($support_attachment_id, '_nm_attachment_status', 1);

            $response['uploaded_files']['supporting_file'] = $support_attachment_id;
        }

        // ---------- Save additional dynamic values ----------


        if (isset($response['uploaded_files']['resume_file']) && !empty($response['uploaded_files']['resume_file'])) {
            self::nm_replace_value_entries($entry_id, $field_id, $application_note, $response['uploaded_files']['resume_file']);

        }

        if (isset($response['uploaded_files']['supporting_file']) && !empty($response['uploaded_files']['supporting_file'])) {
            self::my_insert_frm_custom_meta_sql($entry_id, 'supplemental_fields', $response['uploaded_files']['supporting_file']);
        }

        // Return success response with file URLs
        wp_send_json_success($response);

    }

    public static function nm_replace_value_entries($entryid, $field_id, $application_note = "", $attachment_id)
    {
        if (!empty($attachment_id) && !empty($entryid) && !empty($field_id)) {
            $updated = FrmEntryMeta::update_entry_meta(
                $entryid,
                $field_id,
                null,
                $attachment_id
            );

            if ($updated) {
                do_action(
                    'my_after_update_entry_meta',
                    $entryid,
                    $field_id,
                    $attachment_id
                );
            }
            // update_post_meta($attachment_id, '_nm_attachment_notes', $application_note);

            $notess = get_post_meta($attachment_id, '_nm_attachment_notes', true);


            if ($updated) {
                wp_send_json_success(['message' => 'main file field entry updated' . $notess, 'response' => $attachment_id]);
            } else {
                wp_send_json_error(['message' => 'Error uploading main file']);
            }

            //  if ($updated) {
            //     // --- NEW CODE START ---
            //     // This manually triggers the "After Update" hooks that ApproveMe is listening for
            //     $entry = FrmEntry::getOne($entryid);
            //     do_action('frm_after_update_entry', $entryid, $entry->form_id);

            //     // Specifically trigger Formidable's internal automation engine
            //     FrmFormActionsController::trigger_actions('update', $entry->form_id, $entryid, 'all');
            //     // --- NEW CODE END ---

            //     wp_send_json_success(['message' => 'main file field entry updated', 'response' => $attachment_id]);
            // } else {
            //     wp_send_json_error(['message' => 'Error uploading main file']);
            // }
        }
    }

    /**
     * Inserts a new custom meta value directly into the Formidable Forms entry meta table.
     */
    public static function my_insert_frm_custom_meta_sql($entry_id, $base_key, $meta_value)
    {
        global $wpdb;

        // --- 1. Define Table Name and Sanitize Inputs ---
        $table_name = $wpdb->prefix . 'nm_frm_supplementals';

        $entry_id = absint($entry_id);
        $base_key = sanitize_key($base_key);
        $meta_value = maybe_serialize($meta_value);

        if ($entry_id === 0 || empty($base_key)) {
            error_log('Failed to insert custom dynamic meta: Invalid Entry ID or Base Key provided.');
            return false;
        }

        // --- 2. Dynamically Create the Unique Meta Key ---
        // We combine the base key, the entry ID, and a unique 13-digit microtime ID
        // to ensure maximum uniqueness across multiple submissions.
        $unique_id = str_replace('.', '', microtime(true)); // Use microseconds for high uniqueness

        // Final key structure: e.g., 'support_file_123_1734415805456'
        $meta_key = $base_key . '_' . $entry_id . '_' . $unique_id;

        // Ensure the key doesn't exceed the 255 character limit for the database
        $meta_key = substr($meta_key, 0, 255);

        // --- 3. Perform the SQL INSERT ---
        $result = $wpdb->insert(
            $table_name,
            array(
                'frm_entry_id' => $entry_id,
                'meta_key' => $meta_key,
                'meta_value' => $meta_value,
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%s')
        );

        if ($result) {
            $log_table = null;
            $current_user = get_current_user_id();

            NM_Formidable_Entry_Logger::insert_log_row(
                $log_table,      // Use default logs table
                $entry_id,       // Entry ID
                $current_user,   // User ID
                0,               // Field ID = 0 (not a normal form field)
                '',              // Old value
                $meta_value,     // New value
                $base_key        // Field name = base key
            );

            wp_send_json_success(['message' => 'Supplementals Documents Added!', 'results' => $result]);
        } else {
            error_log('SQL Error inserting custom dynamic meta: ' . $wpdb->last_error);

            wp_send_json_error(['message' => 'Error uploading supplementals file']);
        }

    }




    public static function nm_attachment_status()
    {
        // Validate nonce
        if (!check_ajax_referer('register_email_nonce', 'security', false)) {
            wp_send_json_error([
                'message' => 'Security check failed.'
            ], 400);
        }
        $status = isset($_POST['status']) ? ($_POST['status']) : '';
        $attachment_id = isset($_POST['attachid']) ? sanitize_text_field($_POST['attachid']) : '';


        if ($status === '' || $attachment_id == '') {
            wp_send_json_error([
                'message' => 'Some Fields is missing values'
            ], 400);
        }

        update_post_meta($attachment_id, '_nm_attachment_status', $status);

        wp_send_json_success(['message' => "Status Updated Successfully", 'status' => $status]);

    }

    public static function register_email_lookup()
    {
        check_ajax_referer('register_email_nonce', 'security');

        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';

        if (empty($email)) {
            wp_send_json_error('Email is required.');
        }

        // Example: maybe check if email exists in users table
        $user = get_user_by('email', $email);

        if ($user) {
            $user_data = NM_Helpers::get_agency_posts_by_user($user->ID);
            wp_send_json_success(['exists' => true, 'agency_details' => $user_data]);
        }

        wp_send_json_success(['exists' => false, 'email' => $email]);
    }

  public static function register_agency_lookup_by_name()
    {
        check_ajax_referer('register_email_nonce', 'security');

        $agency_name = isset($_POST['agency_name']) ? sanitize_text_field($_POST['agency_name']) : '';

        if (empty($agency_name)) {
            wp_send_json_error('Agency name is required.');
            return;
        }

        global $wpdb;
        $like = '%' . $wpdb->esc_like($agency_name) . '%';

        // Find agencies whose title matches the input
        $agencies = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, post_author 
                 FROM {$wpdb->posts} 
                 WHERE post_type = 'agencies' 
                 AND post_status = 'publish' 
                 AND post_title LIKE %s",
                $like
            )
        );

        if (empty($agencies)) {
            wp_send_json_success(['exists' => false, 'agency_name' => $agency_name]);
            return;
        }

        // Collect results across all matched agencies, reusing the same helper
        // that the email lookup uses so the data shape is identical
        $all_details = [];
        foreach ($agencies as $agency_post) {
            $details = NM_Helpers::get_agency_posts_by_user($agency_post->post_author);
            if ($details) {
                foreach ($details as $d) {
                    // Avoid duplicates if multiple matched posts share an author
                    $all_details[$d['post_id']] = $d;
                }
            }
        }

        $all_details = array_values($all_details);

        wp_send_json_success([
            'exists'         => true,
            'agency_details' => $all_details,
        ]);
    }

    public static function nm_filter_entries()
    {

        // Validate nonce
        if (!check_ajax_referer('register_email_nonce', 'security', false)) {
            wp_send_json_error([
                'message' => 'Security check failed.'
            ], 400);
        }
        $entry_id = isset($_POST['entry_id']) ? sanitize_text_field($_POST['entry_id']) : '';


        if (empty($entry_id)) {
            wp_send_json_error([
                'message' => 'Entry ID must have a value'
            ], 400);
        }
        $entry = FrmEntry::getOne($entry_id, true);
        if (!$entry || empty($entry)) {
            wp_send_json_error([
                'message' => 'Entry Not Found'
            ], 400);
        }

        $entry_created_at = $entry->created_at;

        ob_start();

        include NM_APPS_PATH . '/dashboard/components/nm-entry-details-mockup.php';

        $entry_detailed_mockup = ob_get_clean();


        ob_start();

        echo do_shortcode("[frm_entry_log entry_id='$entry_id' note='true']");

        $note_logs = ob_get_clean();

        // Generate sidebar contact card
        $agency_id = isset($_POST['agency_id']) ? sanitize_text_field($_POST['agency_id']) : '';
        $user_id = get_current_user_id();
        $is_opre_manager = false; // By default false here, but we can grab role logic if needed
        $allowed_list = NM_Helpers::tb_get_user_allowed_forms();
        if ($allowed_list && array_key_exists(29, $allowed_list)) {
            $is_opre_manager = true;
        }

        $agency_detailed_content = NM_Helpers::get_agency_details_by_its_id($agency_id);
        if (empty($agency_detailed_content)) {
            $agency_detailed_content = AgencyDetailsDisplay::get_current_user_details($user_id);
        }

        $entry_id = isset($_POST['entry_id']) ? intval($_POST['entry_id']) : 0;

        ob_start();
        include NM_APPS_PATH . '/dashboard/components/nm-sidebar-contact-card.php';
        $sidebar_contact_card = ob_get_clean();

        // Return success response
        wp_send_json_success([
            'message' => 'Entries retrieved successfully.',
            'entrydetailsmockup' => $entry_detailed_mockup,
            'note_activitymockup' => $note_logs,
            'sidebar_contact_card' => $sidebar_contact_card
        ]);

    }
    public static function nm_filter_dashboard()
    {
        $timings = [];

        // ---- Security check ----
        $start = microtime(true);
        if (!check_ajax_referer('register_email_nonce', 'security', false)) {
            wp_send_json_error(['message' => 'Security check failed.'], 400);
        }

        // ---- Sanitize & normalize inputs ----
        $start = microtime(true);
        $currentForm = isset($_POST['currentForm']) ? intval($_POST['currentForm']) : 0;
        $sortby = sanitize_text_field($_POST['sortby'] ?? 'DESC');
        $status = sanitize_text_field($_POST['status'] ?? '');
        $datefrom = sanitize_text_field($_POST['datefrom'] ?? '');
        $dateto = sanitize_text_field($_POST['dateto'] ?? '');
        $currentpage = max(1, intval($_POST['currentpage'] ?? 1));
        $currentpagetype = sanitize_text_field($_POST['currentpagetype'] ?? 'agency_overview');
        $agency_id = $_POST['agency_id'] ?? '';
        $search_text = sanitize_text_field($_POST['search'] ?? '');
        $exporttocsv = filter_var($_POST['exportcsv'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (!$currentForm) {
            wp_send_json_error(['message' => 'Form ID is missing.'], 400);
        }

        // ---- Prepare filters (skip empty values) ----
        $start = microtime(true);
        $filters = array_filter([
            'date_from' => $datefrom,
            'date_to' => $dateto,
            'sort_order' => $sortby ?: 'DESC',
            'status' => $status,
        ]);

        if ($search_text !== '') {
            $filters['agency_ids'] = NM_Helpers::nm_search_agencies_by_text($search_text);
        }


        try {
            // ---- Resolve allowed forms ----
            $start = microtime(true);
            if ($currentForm === -1 || $currentForm === '-1') {
                $allowed_list = NM_Helpers::tb_get_user_allowed_forms();
            } else {
                $allowed_list = [$currentForm => 'Form Name'];
            }

            $is_opre_manager = isset($allowed_list[29]);
            if ($is_opre_manager) {
                $filters['user_ids'] = NM_Helpers::nm_search_users_by_text($search_text);
            }

            // ---- Fetch entries ----
            if ($currentpagetype === 'agency_overview') {
                $entries = NM_Helpers::get_formidable_entries_by_form_ids(
                    $allowed_list,
                    $currentpage,
                    10,
                    $filters
                );
            } else {
                $entries = NM_Helpers::get_formidable_entries_by_agency_ids(
                    $allowed_list,
                    $agency_id,
                    $currentpage,
                    5
                );
            }

            if (empty($entries) || empty($entries['entries'])) {
                wp_send_json_success([
                    'message' => 'No entries found.',
                    'entrylistmockup' => '<p>No Entries Found</p>',
                    'paginationlistmockup' => '',
                    'total_entries' => 0,
                    'pagination' => [],
                ]);
            }

            // ---- Render UI ----
            $entry_list_mockup = NM_Helpers::create_entry_list_mockup(
                $entries['entries'],
                $currentpagetype,
                $is_opre_manager
            );

            $pagination_mockup = NM_Helpers::create_pagination_mockup(
                $entries['pagination'],
                $currentpage
            );

            // ---- CSV Export ----
            $csv_file = [];
            if ($exporttocsv) {
                $csv_entries = NM_Helpers::get_formidable_entries_by_form_ids_without_pagination(
                    $allowed_list,
                    $currentpage,
                    10,
                    $filters,
                    true
                );

                $csv_file = NM_Helpers::generate_csv_file(
                    $csv_entries,
                    $is_opre_manager
                );
            } else {
                $timings['csv_export'] = 0;
            }




            wp_send_json_success([
                'message' => 'Entries retrieved successfully.',
                'entrylistmockup' => $entry_list_mockup,
                'paginationlistmockup' => $pagination_mockup,
                'csv_file_export' => $csv_file,
                'total_entries' => count($entries['entries']),
                'pagination' => $entries['pagination'],
                'timings' => $entries['timings']
            ]);

        } catch (Throwable $e) {
            error_log('NM Filter Dashboard ERROR: ' . $e->getMessage());

            wp_send_json_error([
                'message' => 'An unexpected error occurred.',
                'error' => $e->getMessage(),
                'timings' => $timings,
            ], 500);
        }
    }

}
