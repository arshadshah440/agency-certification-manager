<?php
if (!defined('ABSPATH')) {
    exit;
}
use Dompdf\Dompdf;

class NM_Helpers
{

    public static function init()
    {
        add_action('init', array(__CLASS__, 'register_new_role'));
        add_action('admin_init', array(__CLASS__, 'nm_hide_wp_admin'));
        add_action('set_user_role', array(__CLASS__, 'update_forms_on_role_change'), 10, 2);
        add_action('add_user_role', array(__CLASS__, 'update_forms_on_role_added'), 10, 2);
        add_filter('acf/load_field/name=select_the_application_form_for_this_company', array(__CLASS__, 'application_form_for_company'));
    }

    public static function nm_hide_wp_admin()
    {
        if (
            is_user_logged_in() &&
            is_admin() &&
            !defined('DOING_AJAX')
        ) {
            $user = wp_get_current_user();

            if (
                !in_array('administrator', $user->roles, true) &&
                !in_array('events_manager', $user->roles, true) &&
                !in_array('events', $user->roles, true) &&
                !in_array('editor', $user->roles, true)

            ) {
                wp_redirect(home_url());
                exit;
            }
        }
    }
    public static function register_new_role()
    {
        // Define all custom roles with their capabilities

        $custom_roles = array(
            'nm_manager' => array(
                'name' => __('NM Manager'),
                'capabilities' => array(
                    'read' => false,
                    'create_posts' => false,
                    'edit_posts' => false,
                )
            ),
            'tfpa-manager' => array(
                'name' => __('Treat First Program Manage'),
                'capabilities' => array(
                    'read' => false,
                    'create_posts' => false,
                    'edit_posts' => false,
                )
            ),
            'wpseo_editor' => array(
                'name' => __('SEO Editor'),
                'capabilities' => array(
                    'read' => false,
                    'create_posts' => false,
                    'edit_posts' => false,
                )
            ),
            'wpseo_manager' => array(
                'name' => __('SEO Manager'),
                'capabilities' => array(
                    'read' => false,
                    'create_posts' => false,
                    'edit_posts' => false,
                )
            ),
            'psr_manager' => array(
                'name' => __('PSR Manager'),
                'capabilities' => array(
                    'read' => false,
                    'create_posts' => false,
                    'edit_posts' => false,
                )
            ),
            'ccbhc-manager' => array(
                'name' => __('CCBHC Manager'),
                'capabilities' => array(
                    'read' => false,
                    'create_posts' => false,
                    'edit_posts' => false,
                )
            ),
            'full_dashboard' => array(
                'name' => __('Full Dashboard'),
                'capabilities' => array(
                    'read' => false,
                    'create_posts' => false,
                    'edit_posts' => false,
                )
            ),
            'mct-manager' => array(
                'name' => __('MCT Manager'),
                'capabilities' => array(
                    'read' => false,
                    'create_posts' => false,
                    'edit_posts' => false,
                )
            ),
            'act_manager' => array(
                'name' => __('ACT Manager'),
                'capabilities' => array(
                    'read' => false,
                    'create_posts' => false,
                    'edit_posts' => false,
                )
            ),
            'wpas_user' => array(
                'name' => __('Support User'),
                'capabilities' => array(
                    'read' => false,
                    'create_posts' => false,
                    'edit_posts' => false,
                )
            ),
            'wpas_agent' => array(
                'name' => __('Support Agent'),
                'capabilities' => array(
                    'read' => false,
                    'create_posts' => false,
                    'edit_posts' => false,
                )
            ),
            'wpas_support_manager' => array(
                'name' => __('Support Manager'),
                'capabilities' => array(
                    'read' => false,
                    'create_posts' => false,
                    'edit_posts' => false,
                )
            ),
            'wpas_manager' => array(
                'name' => __('Support Supervisor'),
                'capabilities' => array(
                    'read' => false,
                    'create_posts' => false,
                    'edit_posts' => false,
                )
            ),
            'ccss-manager' => array(
                'name' => __('CCSS Manager'),
                'capabilities' => array(
                    'read' => false,
                    'create_posts' => false,
                    'edit_posts' => false,
                )
            ),
            'sup_cert_manager' => array(
                'name' => __('Sup Cert Manager'),
                'capabilities' => array(
                    'read' => false,
                    'create_posts' => false,
                    'edit_posts' => false,
                )
            ),
            'opre-manager' => array(
                'name' => __('OPRE Manager'),
                'capabilities' => array(
                    'read' => false,
                    'create_posts' => false,
                    'edit_posts' => false,
                )
            ),
            'aartc_manager' => array(
                'name' => __('AARTC Manager'),
                'capabilities' => array(
                    'read' => false,
                    'create_posts' => false,
                    'edit_posts' => false,
                )
            ),
            'events' => array(
                'name' => __('Events'),
                'capabilities' => array(
                    'read' => true,
                    'create_posts' => true,
                    'edit_posts' => true,
                )
            ),
            'peer' => array(
                'name' => __('Peer'),
                'capabilities' => array(
                    'read' => false,
                    'create_posts' => false,
                    'edit_posts' => false,
                )
            ),
            'iop-manager' => array(
                'name' => __('IOP Manager'),
                'capabilities' => array(
                    'read' => false,
                    'create_posts' => false,
                    'edit_posts' => false,
                )
            ),
            'agency_dashboard' => array(
                'name' => __('Agency Dashboard'),
                'capabilities' => array(
                    'read' => false,
                    'create_posts' => false,
                    'edit_posts' => false,
                )
            ),
            'esig_form_manager' => array(
                'name' => __('E-sig Form Manager'),
                'capabilities' => array(
                    'read' => false,
                    'create_posts' => false,
                    'edit_posts' => false,
                )
            ),
            'agency_admin' => array(
                'name' => __('Agency admin'),
                'capabilities' => array(
                    'read' => false,
                    'create_posts' => false,
                    'edit_posts' => false,
                )
            ),
            'bhsd_agencies_admin' => array(
                'name' => __('BHSD agencies admin'),
                'capabilities' => array(
                    'read' => false,
                    'create_posts' => false,
                    'edit_posts' => false,
                )
            ),
            'agencyadmin' => array(
                'name' => __('Agency Admin'),
                'capabilities' => array(
                    'read' => false,
                    'create_posts' => false,
                    'edit_posts' => false,
                )
            ),
            'agency' => array(
                'name' => __('Agency'),
                'capabilities' => array(
                    'read' => false,
                    'create_posts' => false,
                    'edit_posts' => false,
                )
            ),
            'supervisor' => array(
                'name' => __('Supervisor'),
                'capabilities' => array(
                    'read' => false,
                    'create_posts' => false,
                    'edit_posts' => false,
                )
            ),
        );

        // Register all custom roles
        foreach ($custom_roles as $role_slug => $role_data) {
            add_role($role_slug, $role_data['name'], $role_data['capabilities']);
        }

        // Build array of all custom role capabilities for admin restrictions
        $restricted_roles = array_keys($custom_roles);

        // Check if current user has any of the restricted roles
        $user_has_restricted_role = false;
        foreach ($restricted_roles as $role) {
            if (current_user_can($role)) {
                $user_has_restricted_role = true;
                break;
            }
        }

        // Restrict all non-administrator users from accessing wp-admin (except AJAX)
        // if (is_admin() && !defined('DOING_AJAX') && !current_user_can('administrator')) {
        //     wp_redirect(home_url());
        //     exit;
        // }

        // Hide the admin bar for all non-administrator users
        if (!current_user_can('administrator')) {
            show_admin_bar(false);
        }
    }

    public static function update_forms_on_role_change($user_id, $role)
    {
        // Define roles that should get the form IDs
        $target_roles = array('administrator');

        // Form IDs to add
        $admin_form_ids = array('15', '25', '30', '38', '45', '47', '49', '50');

        // Check if the new role is administrator or supervisor
        if (in_array($role, $target_roles)) {
            // Update ACF field
            update_field('select_the_application_form_for_this_company', $admin_form_ids, 'user_' . $user_id);

        }
    }
    public static function update_forms_on_role_added($user_id, $role)
    {
        // Define roles that should get the form IDs
        $target_roles = array('administrator');

        // Form IDs to add
        $admin_form_ids = array('15', '25', '30', '38', '45', '47', '49', '50');

        // Check if the added role is administrator or supervisor
        if (in_array($role, $target_roles)) {
            // Update ACF field
            update_field('select_the_application_form_for_this_company', $admin_form_ids, 'user_' . $user_id);

        }
    }
    public static function get_formidable_forms_by_page_id($page_id)
    {
        $post = get_post($page_id);

        if (!$post) {
            return "";
        }
        $form_id = get_field("select_the_application_form_for_this_company", $post->ID);
        return $form_id;

    }

    public static function nm_send_mail_to_all_managers($entry_id, $agency_id, $attachment_url, $entry_status)
    {
        $form_id = FrmEntry::getOne($entry_id)->form_id ?? null;

        // Validate form_id exists
        if (empty($form_id)) {
            return false;
        }

        // Step 1: Get all users with role 'administrator' or 'nm-manager'
        $roles_to_check = ['administrator', 'nm-manager'];

        $args = [
            'role__in' => $roles_to_check,
            'fields' => ['ID', 'user_email', 'display_name'],
        ];

        $users = get_users($args);

        $agency_details = self::get_agency_details_by_its_id($agency_id);
        $agency_name = $agency_details['agency_name'] ?? 'N/A';

        if (!empty($users)) {
            foreach ($users as $user) {
                $user_id = $user->ID;

                // Step 2: Get the ACF field value
                $form_ids = get_field('select_the_application_form_for_this_company', 'user_' . $user_id);

                // Step 3: Check if form_id exists in user's form_ids array
                if (!empty($form_ids) && is_array($form_ids) && in_array($form_id, $form_ids)) {
                    // Prepare email content
                    $to = $user->user_email;
                    $subject = "Application Status Notification: Application #{$entry_id} – {$agency_name}";

                    $message = "Dear {$user->display_name},\n\n";
                    $message .= "The application for {$agency_name} agency has been officially {$entry_status}.\n\n";
                    $message .= "Please find the details below for your records:\n\n";
                    $message .= "Application ID: {$entry_id}\n";
                    $message .= "Agency ID: {$agency_id}\n";
                    $message .= "Status: $entry_status\n\n";
                    $message .= "Formal Certification Letter is attached to this email. Please ensure this document is filed according to standard procedures.\n\n";


                    // Prepare headers
                    $headers = ['Content-Type: text/plain; charset=UTF-8'];

                    // Prepare attachment
                    $attachments = [];
                    if (!empty($attachment_url)) {
                        // Convert URL to file path if needed
                        $attachment_path = str_replace(wp_get_upload_dir()['baseurl'], wp_get_upload_dir()['basedir'], $attachment_url);
                        if (file_exists($attachment_path)) {
                            $attachments[] = $attachment_path;
                        }
                    }

                    // Send email
                    wp_mail($to, $subject, $message, $headers, $attachments);
                }
            }
        }

        return true;
    }
    public static function application_form_for_company($field)
    {
        // Clear existing choices
        $field['choices'] = [];

        // Get all Formidable forms
        if (class_exists('FrmForm')) {
            $forms = FrmForm::getAll();

            if (!empty($forms)) {
                foreach ($forms as $form) {
                    // Use form name as label and ID as value
                    $field['choices'][$form->id] = $form->name;
                }
            } else {
                $field['choices'][''] = 'No Formidable forms found.';
            }
        } else {
            $field['choices'][''] = 'Formidable plugin not active.';
        }

        return $field;
    }
    public static function fetch_all_application_forms()
    {
        // Bail early if taxonomy doesn't exist
        if (!taxonomy_exists('applications')) {
            return [];
        }

        $args = [
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids', // optimize: only get IDs
            'tax_query' => [
                [
                    'taxonomy' => 'applications',
                    'field' => 'slug',
                    'terms' => ['agency-applications'],
                ],
            ],
        ];

        $query = new WP_Query($args);
        $form_map = [];

        if ($query->have_posts()) {
            foreach ($query->posts as $page_id) {
                $form_ids = self::get_formidable_forms_by_page_id($page_id);

                // Normalize result (empty array → null)
                $form_map[$page_id] = !empty($form_ids) ? $form_ids : [];
            }
        }

        wp_reset_postdata();

        return $form_map;
    }
    public static function get_available_applications_by_user($user_id)
    {
        // Safety check
        if (empty($user_id) || !is_numeric($user_id)) {
            return [];
        }

        // Get associated companies from ACF user field
        $available_applications = get_field('manager_associated_company', 'user_' . $user_id);

        $available_application_ids = [];

        // Normalize to array of IDs
        if (is_array($available_applications)) {
            $available_application_ids = $available_applications;
        } elseif (!empty($available_applications)) {
            $available_application_ids = [$available_applications];
        }

        // Remove duplicates and empty values
        $available_application_ids = array_unique(array_filter($available_application_ids));

        $applications_data = [];

        // Loop through associated company pages
        if (!empty($available_application_ids)) {
            foreach ($available_application_ids as $application_id) {
                // Fetch Formidable forms on that page
                $form_ids = self::get_formidable_forms_by_page_id($application_id);

                // Store result (even if no forms found)
                $applications_data[$application_id] = $form_ids ?: [];
            }
        }

        return $applications_data;
    }
    public static function check_user_roles($user_id = null)
    {
        // Default to current user if not provided
        if (empty($user_id)) {
            $user_id = get_current_user_id();
        }

        // Get user object
        $user = get_userdata($user_id);

        if (!$user || empty($user->roles)) {
            return 'no-role';
        }

        $roles = $user->roles;

        // Normalize for quick lookup
        $has_admin = in_array('administrator', $roles, true);
        $has_nm_manager = in_array('nm_manager', $roles, true);

        // Return specific role status
        if ($has_admin && $has_nm_manager) {
            return 'both';
        } elseif ($has_admin) {
            return 'admin-only';
        } elseif ($has_nm_manager) {
            return 'nm-manager-only';
        } else {
            return 'other';
        }
    }

    /**
     * Get user details by ID.
     *
     * @param int $user_id The WordPress user ID.
     * @return array|false Returns associative array of user details or false if user not found.
     */
    public static function nm_get_user_details_by_id($user_id)
    {
        if (!$user_id || !is_numeric($user_id)) {
            return false;
        }

        $user = get_userdata($user_id);

        if (!$user) {
            return false;
        }

        return [
            'user_id' => $user->ID,
            'first_name' => get_user_meta($user->ID, 'first_name', true),
            'last_name' => get_user_meta($user->ID, 'last_name', true),
            'email' => $user->user_email,
            'username' => $user->user_login,
            'role' => implode(', ', $user->roles), // optional
        ];
    }

    /**
     * Get ACF fields for an agency post.
     *
     * @param int $post_id The post ID.
     * @return array ACF field data.
     */
    public static function get_acf_fields($post_id)
    {
        if (!function_exists('get_field')) {
            return [];
        }

        $physical_address = get_field('physical_address', $post_id);
        $same_address = get_field('is_your_mailing_address_the_same_as_your_physical_address', $post_id);
        $mailing_address = get_field('mailing_address', $post_id) ?? $physical_address;

        // Handle repeater field: agency_sub_locations
        $sub_locations = [];
        if (have_rows('agency_sub_locations', $post_id)) {
            while (have_rows('agency_sub_locations', $post_id)) {
                the_row();
                $sub_locations[] = [
                    'physical_address' => get_sub_field('physical_address'),
                    'is_same' => get_sub_field('is_your_mailing_address_the_same_as_your_physical_address'),
                    'mailing_address' => get_sub_field('mailing_address') ?? get_sub_field('physical_address'),
                ];
            }
        }

        $email = get_field('email', $post_id);
        $phone = get_field('phone', $post_id);
        $agency_type = get_field('agency_type', $post_id);

        return [
            'physical_address' => $physical_address,
            'same_address' => $same_address,
            'mailing_address' => $mailing_address,
            'sub_locations' => $sub_locations,
            'email' => $email,
            'phone' => $phone,
            'agency_type' => $agency_type
        ];
    }

    /**
     * Get agency posts by user — handles hierarchy dynamically.
     *
     * @param int $user_id The user ID.
     * @return array|false Agency post details + ACF fields, or false if none found.
     */
    public static function get_agency_posts_by_user($user_id)
    {
        if (!$user_id || !is_numeric($user_id)) {
            return false;
        }

        $post_type = 'agencies';
        $post_type_obj = get_post_type_object($post_type);
        $is_hierarchical = $post_type_obj && !empty($post_type_obj->hierarchical);

        $args = [
            'post_type' => $post_type,
            'author' => $user_id,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        $user_posts = get_posts($args);
        if (!$user_posts) {
            return false;
        }

        $results = [];

        if ($is_hierarchical) {
            // CPT supports hierarchy → return parent posts
            foreach ($user_posts as $post) {
                $parent_id = $post->post_parent ? $post->post_parent : $post->ID;
                $parent_post = get_post($parent_id);

                if ($parent_post) {
                    $acf_data = self::get_acf_fields($parent_post->ID);
                    $results[] = array_merge(
                        [
                            'post_id' => $parent_post->ID,
                            'title' => $parent_post->post_title,
                            'slug' => $parent_post->post_name,
                            'url' => get_permalink($parent_post->ID),
                            'post_type' => $parent_post->post_type,
                            'author_id' => $parent_post->post_author,
                            'status' => $parent_post->post_status,

                        ],
                        $acf_data
                    );
                }
            }
        } else {
            // CPT is non-hierarchical → return first agency post
            $first_post = $user_posts[0];
            $acf_data = self::get_acf_fields($first_post->ID);

            $results[] = array_merge(
                [
                    'post_id' => $first_post->ID,
                    'title' => $first_post->post_title,
                    'slug' => $first_post->post_name,
                    'url' => get_permalink($first_post->ID),
                    'post_type' => $first_post->post_type,
                    'author_id' => $first_post->post_author,
                    'status' => $first_post->post_status,
                ],
                $acf_data
            );
        }

        return $results ?: false;
    }

    public static function nm_get_entry_count_by_status($status)
    {
        global $wpdb;

        $nm_table = $wpdb->prefix . 'nm_entries';


        if (empty($status)) {
            return [
                'success' => false,
                'message' => 'Invalid status.',
            ];
        }

        $date_30_days_ago = date('Y-m-d H:i:s', strtotime('-30 days'));

        $entries = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT entry_id FROM {$nm_table} WHERE entry_status LIKE %s AND created_at >= %s",
                '%' . $wpdb->esc_like(sanitize_text_field($status)) . '%',
                $date_30_days_ago
            )
        );


        if ($entries === false) {
            return [
                'success' => false,
                'message' => 'Query failed.',
                'error' => $wpdb->last_error,
            ];
        }

        $count = 0;
        foreach ($entries as $entry_id) {
            $entry = FrmEntry::getOne($entry_id, true);
            if (!$entry) {
                continue;
            }
            if ($entry->form_id == 29) {
                $count++;
            }
        }

        return [
            'success' => true,
            'status' => sanitize_text_field($status),
            'form_id' => 29,
            'count' => str_pad($count, 2, '0', STR_PAD_LEFT),
            'entries' => $entries
        ];
    }

    public static function get_total_entry_of_all_avail_forms($formidlist, $status = '')
    {
        if (!is_array($formidlist) || empty($formidlist)) {
            return str_pad(0, 2, '0', STR_PAD_LEFT);
        }

        global $wpdb;

        // Store unique entry IDs across ALL sources
        $unique_entry_ids = [];

        // Last 30 days
        $date_30_days_ago = date('Y-m-d H:i:s', strtotime('-30 days'));

        /**
         * ------------------------------------------------
         * 1. Fetch entries from nm_entries (authoritative)
         * ------------------------------------------------
         */
        if (!isset($formidlist[29])) {
            $nm_entries = self::nm_get_entries_by_status($status, 30);

            if (!empty($nm_entries['data'])) {
                foreach ($nm_entries['data'] as $row) {
                    $entry_id = (int) $row['entry_id'];
                    $unique_entry_ids[$entry_id] = true;
                }
            }
        }


        /**
         * ------------------------------------------------
         * 2. Fetch Formidable entries
         * ------------------------------------------------
         */
        foreach ($formidlist as $form_id => $formdata) {

            $query = $wpdb->prepare(
                "SELECT id, created_at
             FROM {$wpdb->prefix}frm_items
             WHERE form_id = %d
               AND created_at >= %s
               AND is_draft = 0",
                $form_id,
                $date_30_days_ago
            );

            $entries = $wpdb->get_results($query);

            if (empty($entries)) {
                continue;
            }

            foreach ($entries as $entry) {
                $entry_id = (int) $entry->id;

                // Already counted via nm_entries or another form → skip
                if (isset($unique_entry_ids[$entry_id])) {
                    continue;
                }

                // Optional status filtering
                if (!empty($status)) {
                    $doc = self::get_esign_document_details_by_entry_id($entry_id);

                    if (
                        empty($doc) ||
                        empty($doc['document_status']) ||
                        stripos($doc['document_status'], $status) === false
                    ) {
                        continue;
                    }
                }

                // Mark entry as unique
                $unique_entry_ids[$entry_id] = true;
            }
        }

        // Final unique count
        $total_entries = count($unique_entry_ids);

        return str_pad($total_entries, 2, '0', STR_PAD_LEFT);
    }




    public static function get_formidable_entries_by_form_ids($form_ids = [], $page = 1, $per_page = 10, $filters = [])
    {
        $timings = [];
        $start_total = microtime(true);

        if (!class_exists('FrmEntry') || !class_exists('FrmEntryMeta')) {
            return [];
        }

        // 1. Setup & Normalization
        $clean_ids = array_map('intval', array_keys($form_ids));
        if (empty($clean_ids))
            return [];

        $has_form_29 = isset($form_ids[29]);
        if ($has_form_29) {
            $clean_ids = [29];
        }
        $ids_string = implode(',', $clean_ids);

        global $wpdb;
        $prefix = $wpdb->prefix;

        // 2. Fetch Agency Fields & Form Names in one go
        $agency_fields = $wpdb->get_results("SELECT id, form_id FROM {$prefix}frm_fields WHERE field_key LIKE '%agency_id%' AND form_id IN ($ids_string)");
        $agency_field_ids = wp_list_pluck($agency_fields, 'id');
        $agency_field_ids_str = implode(',', array_map('intval', $agency_field_ids));

        // Fetch User ID Fields for Form 29
        $user_id_field_ids = [];
        $user_id_field_ids_str = '';
        if ($has_form_29) {
            $user_id_fields = $wpdb->get_results("SELECT id, form_id FROM {$prefix}frm_fields WHERE (field_key LIKE '%user_id%' OR field_key LIKE '%user-id%') AND form_id IN ($ids_string)");
            $user_id_field_ids = wp_list_pluck($user_id_fields, 'id');
            $user_id_field_ids_str = implode(',', array_map('intval', $user_id_field_ids));

            // Fallback: If no user_id fields found, use agency_id fields (form 29 might use same structure)
            if (empty($user_id_field_ids_str) && !empty($agency_field_ids_str)) {
                $user_id_field_ids_str = $agency_field_ids_str;
            }
        }

        // Cache form names to avoid calling FrmForm::getOne inside the loop
        $form_names = $wpdb->get_results("SELECT id, form_key FROM {$prefix}frm_forms WHERE id IN ($ids_string)", OBJECT_K);

        // 3. Build Logic
        $where = ["it.form_id IN ($ids_string)", "it.is_draft = 0"];
        $params = [];

        // Date Filters
        if (!empty($filters['date_from'])) {
            $where[] = "it.created_at >= %s";
            $params[] = $filters['date_from'] . " 00:00:00";
        }
        if (!empty($filters['date_to'])) {
            $where[] = "it.created_at <= %s";
            $params[] = $filters['date_to'] . " 23:59:59";
        }

        // User ID Filtering for Form 29 (skip agency filtering)
        if ($has_form_29 && !empty($filters['user_ids'])) {
            $user_ids_clean = array_map('intval', (array) $filters['user_ids']);
            $user_ids_clean = array_filter($user_ids_clean); // Remove zeros and empty values
            if (!empty($user_ids_clean) && !empty($user_id_field_ids_str)) {
                $user_ids_str = implode(',', $user_ids_clean);
                $user_sub = "EXISTS (SELECT 1 FROM {$prefix}frm_item_metas im WHERE im.item_id = it.id AND im.field_id IN ($user_id_field_ids_str) AND im.meta_value IN ($user_ids_str))";
                $where[] = $user_sub;
            }
        }

        // Optimized Agency Filtering (EXISTS is faster than JOIN) - Skip for Form 29
        if (!$has_form_29 && !empty($agency_field_ids_str)) {
            $agency_sub = "EXISTS (SELECT 1 FROM {$prefix}frm_item_metas im WHERE im.item_id = it.id AND im.field_id IN ($agency_field_ids_str)";
            if (!empty($filters['agency_ids'])) {
                $agency_ids_clean = implode(',', array_map('intval', $filters['agency_ids']));
                $agency_sub .= " AND im.meta_value IN ($agency_ids_clean)";
            }
            $where[] = $agency_sub . ")";
        }

        // Status Filter (The 7-second culprit)
        $doc_join_sql = "";
        $doc_status = $filters['status'] ?? '';

        if (!$has_form_29) {

            // Joins
            $doc_join_sql = "
        LEFT JOIN {$prefix}esign_documents_meta em 
            ON em.meta_value = it.id
            AND em.meta_key = 'esig_formidable_entry_id'

        LEFT JOIN {$prefix}esign_documents ed 
            ON ed.document_id = em.document_id

        LEFT JOIN {$prefix}nm_entries nme 
            ON nme.entry_id = it.id
    ";

            // Apply status filter only if provided
            if (!empty($doc_status)) {

                $where[] = "
        (
            -- ✅ If nm_entries has a status → use it and ignore e-sign
            (nme.entry_status IS NOT NULL AND nme.entry_status LIKE %s)

            OR

            -- ✅ If nm_entries has NO status → fall back to e-sign
            (nme.entry_status IS NULL AND ed.document_status = %s)
        )
        ";

                $params[] = $doc_status;
                $params[] = $doc_status;
            }
        }

        $where_clause = implode(' AND ', $where);

        // 4. Run Count Query
        $start = microtime(true);
        $count_sql = "SELECT COUNT(it.id) FROM {$prefix}frm_items it " . (!empty($doc_status) ? $doc_join_sql : "") . " WHERE $where_clause";
        if (!empty($params)) {
            $total_results = (int) $wpdb->get_var($wpdb->prepare($count_sql, $params));
        } else {
            $total_results = (int) $wpdb->get_var($count_sql);
        }
        $timings['count_query'] = microtime(true) - $start;

        // 5. Run Data Query
        $start = microtime(true);
        $offset = ($page - 1) * $per_page;
        $sort_order = (isset($filters['sort_order']) && strtoupper($filters['sort_order']) === 'ASC') ? 'ASC' : 'DESC';

        // STRATEGY: Select ONLY the ID first. This is much faster for the DB to sort/limit.
        $data_sql = "SELECT it.id FROM {$prefix}frm_items it $doc_join_sql WHERE $where_clause ORDER BY it.created_at $sort_order LIMIT %d OFFSET %d";
        $data_params = array_merge($params, [$per_page, $offset]);
        $entry_ids = $wpdb->get_col($wpdb->prepare($data_sql, $data_params));
        $timings['data_query'] = microtime(true) - $start;

        // 6. Process Results
        if (empty($entry_ids))
            return ['entries' => [], 'pagination' => ['total' => $total_results]];

        $start = microtime(true);
        if (!$has_form_29)
            self::preload_esign_document_details($entry_ids);

        $entries_output = [];
        foreach ($entry_ids as $entry_id) {
            $entry = FrmEntry::getOne($entry_id, true);
            if (!$entry)
                continue;

            $fields = [];
            foreach ($entry->metas as $f_id => $val) {
                if (in_array($f_id, $agency_field_ids)) {
                    $fields['agency_id'] = (is_string($val) && str_contains($val, '[')) ? 0 : $val;
                    break;
                }
            }

            $due = self::get_days_left_and_date_after_30_days($entry->created_at);
            $entry_approdeny_details = self::nm_get_entry_created_at($entry->id);
            $entry_approval_status = 'none';
            $entry_approvaldeny_date = 'N/A';

            if ($entry->form_id == 29 && $entry_approdeny_details['success']) {
                $entry_approval_status = $entry_approdeny_details['entry_status'];
                $entry_approvaldeny_date = $entry_approdeny_details['created_at'];
            }



            $entries_output[$entry->id] = [
                'entry_id' => $entry->id,
                'form_id' => $entry->form_id,
                'form_name' => $form_names[$entry->form_id]->form_key ?? 'Unknown',
                'created_at' => date('Y-m-d', strtotime($entry->created_at)),
                'create_at_with_time' => date('Y-m-d H:i:s', strtotime($entry->created_at)),
                'due_date' => $due['target_date'],
                'entry_approvaldeny_date' => $entry_approvaldeny_date,
                'entry_approval_staus' => $entry_approval_status,
                'fields' => $fields,
                'time_trigger' => $due['days_left'],
                'document_details' => !$has_form_29 ? self::get_esign_document_details_by_entry_id($entry->id) : [],
            ];
        }
        $timings['load_entries'] = microtime(true) - $start;
        $timings['total'] = microtime(true) - $start_total;

        return [
            'entries' => array_values($entries_output),
            'pagination' => ['total' => $total_results, 'total_pages' => ceil($total_results / $per_page)],
            'timings' => $timings
        ];
    }




    public static function get_formidable_entries_by_form_ids_without_pagination($form_ids = [], $page = 1, $per_page = 10, $filters = [], $skip_pagination = false)
    {
        if (!class_exists('FrmEntry') || !class_exists('FrmEntryMeta')) {
            return [];
        }

        // ---- Normalize form IDs ----
        $clean_ids = [];
        foreach ($form_ids as $id => $name) {
            $clean_ids[] = intval($id);
        }

        if (empty($clean_ids)) {
            return [];
        }

        // ---- Detect if Form 29 is included ----
        $has_form_29 = in_array(29, $clean_ids, true);

        // ---- If Form 29 is present, only fetch Form 29 entries ----
        if ($has_form_29) {
            $clean_ids = [29];
        }

        // ---- Extract filter parameters ----
        $date_from = $filters['date_from'] ?? '';
        $date_to = $filters['date_to'] ?? '';
        $sort_order = strtoupper($filters['sort_order'] ?? 'DESC');
        $doc_status = isset($filters['status']) ? sanitize_text_field($filters['status']) : '';

        if (!in_array($sort_order, ['ASC', 'DESC'], true)) {
            $sort_order = 'DESC';
        }

        // ---- Fix reversed dates ----
        if (!empty($date_from) && !empty($date_to) && strtotime($date_from) > strtotime($date_to)) {
            [$date_from, $date_to] = [$date_to, $date_from];
        }

        global $wpdb;
        $ids_string = implode(',', $clean_ids);

        // ---- Get all fields matching "agency_id" ----
        $agency_field_ids = $wpdb->get_col("
        SELECT id
        FROM {$wpdb->prefix}frm_fields
        WHERE field_key LIKE '%agency_id%'
        AND form_id IN ($ids_string)
    ");

        $agency_field_ids_str = implode(',', $agency_field_ids);

        // ---- Base WHERE ----
        $where_conditions = [
            "it.form_id IN ($ids_string)",
            "it.is_draft = 0",
        ];

        // ---- Agency filter: only apply if NOT form 29 and there are agency fields ----
        if (!$has_form_29 && !empty($agency_field_ids_str)) {
            $where_conditions[] = "im.field_id IN ($agency_field_ids_str)";
            $where_conditions[] = "im.meta_value REGEXP '^[0-9]+$'";
        }

        $where_params = [];

        // ---- Date filters ----
        if (!empty($date_from)) {
            $where_conditions[] = "it.created_at >= %s";
            $where_params[] = $date_from . " 00:00:00";
        }

        if (!empty($date_to)) {
            $where_conditions[] = "it.created_at <= %s";
            $where_params[] = $date_to . " 23:59:59";
        }

        // ---- Document status filter (only apply if NOT form 29) ----
        if (!empty($doc_status) && !$has_form_29) {
            $where_conditions[] = "ed.document_status = %s";
            $where_params[] = $doc_status;
        }

        $where_clause = implode(' AND ', $where_conditions);


        // ---- Document joins (only if NOT form 29) ----
        $doc_joins = '';
        if (!$has_form_29) {
            $doc_joins = "
            LEFT JOIN {$wpdb->prefix}esign_documents_meta em
                ON em.meta_value = it.id
                AND em.meta_key = 'esig_formidable_entry_id'
            LEFT JOIN {$wpdb->prefix}esign_documents ed
                ON ed.document_id = em.document_id
        ";
        }

        // =============================================
        // 1️⃣ COUNT QUERY
        // =============================================
        // Use LEFT JOIN for Form 29 (it might not have meta entries)
        $meta_join_type = $has_form_29 ? 'LEFT JOIN' : 'INNER JOIN';

        $count_sql = "
        SELECT COUNT(DISTINCT it.id)
        FROM {$wpdb->prefix}frm_items it
        $meta_join_type {$wpdb->prefix}frm_item_metas im
            ON it.id = im.item_id
        $doc_joins
        WHERE $where_clause
    ";

        // Only use prepare if there are parameters
        if (!empty($where_params)) {
            $total_results = (int) $wpdb->get_var(
                $wpdb->prepare($count_sql, $where_params)
            );
        } else {
            $total_results = (int) $wpdb->get_var($count_sql);
        }

        // Debug logging
        if ($wpdb->last_error) {
            error_log('SQL Error: ' . $wpdb->last_error);
        }

        // =============================================
        // 2️⃣ DATA QUERY
        // =============================================
        $offset = ($page - 1) * $per_page;
        $data_params = array_merge($where_params, [$per_page, $offset]);

        // Build query with or without pagination
        if ($skip_pagination) {
            $data_sql = "
            SELECT DISTINCT it.id
            FROM {$wpdb->prefix}frm_items it
            $meta_join_type {$wpdb->prefix}frm_item_metas im
                ON it.id = im.item_id
            $doc_joins
            WHERE $where_clause
            ORDER BY it.created_at $sort_order
        ";

            // Only use prepare if there are parameters
            if (!empty($where_params)) {
                $entry_ids = $wpdb->get_col(
                    $wpdb->prepare($data_sql, $where_params)
                );
            } else {
                $entry_ids = $wpdb->get_col($data_sql);
            }
        } else {
            $data_sql = "
            SELECT DISTINCT it.id
            FROM {$wpdb->prefix}frm_items it
            $meta_join_type {$wpdb->prefix}frm_item_metas im
                ON it.id = im.item_id
            $doc_joins
            WHERE $where_clause
            ORDER BY it.created_at $sort_order
            LIMIT %d OFFSET %d
        ";

            $entry_ids = $wpdb->get_col(
                $wpdb->prepare($data_sql, $data_params)
            );
        }


        $total_pages = ceil($total_results / $per_page);

        if (empty($entry_ids)) {
            $pagination_data = $skip_pagination ? [] : [
                'total' => (int) $total_results,
                'per_page' => $per_page,
                'current_page' => $page,
                'total_pages' => $total_pages,
            ];

            return [
                'entries' => [],
                'pagination' => $pagination_data,
                'applied_filters' => [
                    'date_from' => $date_from,
                    'date_to' => $date_to,
                    'sort_order' => $sort_order,
                    'status' => $doc_status,
                    'skip_pagination' => $skip_pagination
                ]
            ];
        }

        // =============================================
        // 3️⃣ Load entries
        // =============================================
        $entries_output = [];

        foreach ($entry_ids as $entry_id) {
            $entry = FrmEntry::getOne($entry_id, true);
            if (!$entry) {
                continue;
            }

            // Map fields
            $field_map = [];
            $form_fields = FrmField::getAll(['fi.form_id' => $entry->form_id]);
            foreach ($form_fields as $field) {
                $field_map[$field->id] = $field->field_key;
            }

            // Extract agency_id
            $fields = [];
            foreach ($entry->metas as $field_id => $value) {
                $meta_key = $field_map[$field_id] ?? $field_id;
                if (str_contains($meta_key, 'agency_id')) {
                    $fields['agency_id'] = str_contains($value, '[') ? 0 : $value;
                }
            }

            $form = FrmForm::getOne($entry->form_id);

            // ---- Only get document details if NOT form 29 ----
            $document_details = [];
            if (!$has_form_29) {
                $document_details = self::get_esign_document_details_by_entry_id($entry->id);
            }

            $due_date_details = self::get_days_left_and_date_after_30_days($entry->created_at);

            $entries_output[] = [
                'entry_id' => $entry->id,
                'form_id' => $entry->form_id,
                'form_name' => $form->form_key,
                'created_at' => date('Y-m-d', strtotime($entry->created_at)),
                'create_at_with_time' => date('Y-m-d H:i:s', strtotime($entry->created_at)),
                'due_date' => $due_date_details['target_date'],
                'fields' => $fields,
                'time_trigger' => $due_date_details['days_left'],
                'document_details' => $document_details
            ];
        }

        // ---- Final return ----
        $pagination_data = $skip_pagination ? [] : [
            'total' => (int) $total_results,
            'per_page' => $per_page,
            'current_page' => $page,
            'total_pages' => $total_pages,
        ];

        return [
            'entries' => $entries_output,
            'pagination' => $pagination_data,
            'applied_filters' => [
                'date_from' => $date_from,
                'date_to' => $date_to,
                'sort_order' => $sort_order,
                'status' => $doc_status,
                'skip_pagination' => $skip_pagination
            ]
        ];
    }

    public static function generate_csv_file($entries_list, $is_opre_manager = false)
    {
        try {
            // Check if entries exist
            if (empty($entries_list) || empty($entries_list['entries'])) {
                return [
                    'success' => false,
                    'message' => 'No entries found to export.'
                ];
            }

            // Create uploads directory path
            $upload_dir = wp_upload_dir();
            $csv_dir = $upload_dir['basedir'] . '/nm_csv_exports';
            $csv_url_dir = $upload_dir['baseurl'] . '/nm_csv_exports';

            // Create directory if it doesn't exist
            if (!file_exists($csv_dir)) {
                wp_mkdir_p($csv_dir);
            }

            // Generate unique filename with timestamp
            $filename = 'applications_export_' . date('Y-m-d_His') . '_' . uniqid() . '.csv';
            $filepath = $csv_dir . '/' . $filename;
            $file_url = $csv_url_dir . '/' . $filename;

            // Open file for writing
            $file = fopen($filepath, 'w');

            if (!$file) {
                return [
                    'success' => false,
                    'message' => 'Failed to create CSV file.'
                ];
            }

            // Add UTF-8 BOM for proper Excel encoding
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Define CSV headers based on user role
            if ($is_opre_manager) {
                $headers = ['User', 'Type', 'Contact Name', 'Email', 'Phone', 'Date Submitted', 'Status', 'Approval/denial Date'];
            } else {
                $headers = ['Agency', 'Type', 'Contact Name', 'Email', 'Date Submitted', 'Status', 'Days Left'];
            }

            // Write headers
            fputcsv($file, $headers);

            // Process each entry
            foreach ($entries_list['entries'] as $entry) {
                $agency_id = $entry['fields']['agency_id'];

                // Get agency details
                $agency_details = self::get_agency_details_by_its_id($agency_id);

                if (empty($agency_details)) {
                    $agency_details = AgencyDetailsDisplay::get_current_user_details($agency_id);
                    if (empty($agency_details)) {
                        continue; // Skip if no agency details found
                    }
                }

                // Determine entry status
                $entry_status = 'Submitted';
                if (isset($entry['document_details']) && !empty($entry['document_details']) && isset($entry['document_details']['document_status'])) {
                    $entry_status = $entry['document_details']['document_status'] ?? 'Submitted';
                } else {
                    $entry_status = 'None';
                }

                // Calculate days left
                $days_left = 'None';
                if (!((strtolower($entry_status) == 'approve' || strtolower($entry_status) == 'approved') || (strtolower($entry_status) == 'deny' || strtolower($entry_status) == 'denied'))) {
                    if (($entry['time_trigger'] == 0)) {
                        $days_left = "Today";
                    } else if ($entry['time_trigger'] < 0) {
                        $days_left = "Overdue";
                    } else {
                        $days_left = $entry['time_trigger'] . ' days left';
                    }
                }

                // Prepare row data based on user role
                if ($is_opre_manager) {
                    // Fetch status & approval/denial date from nm_entries (same source as dashboard)
                    $entry_approdeny_details = self::nm_get_entry_created_at($entry['entry_id']);
                    $opre_status = 'None';
                    $opre_approval_date = 'N/A';

                    if ($entry['form_id'] == 29 && !empty($entry_approdeny_details['success'])) {
                        $opre_status = $entry_approdeny_details['entry_status'] ?? 'None';
                        $opre_approval_date = $entry_approdeny_details['created_at'] ?? 'N/A';
                    }

                    $row = [
                        $agency_details['agency_name'] ?? $agency_details['username'] ?? '',
                        strtoupper($entry['form_name']),
                        $agency_details['contact_name'] ?? $agency_details['name'] ?? '',
                        $agency_details['agency_email'] ?? $agency_details['email'] ?? '',
                        $agency_details['cell_phone'] ?? $agency_details['home_phone'] ?? '',
                        $entry['create_at_with_time'],
                        $opre_status,
                        $opre_approval_date,
                    ];
                } else {
                    $row = [
                        $agency_details['agency_name'] ?? $agency_details['username'] ?? '',
                        strtoupper($entry['form_name']),
                        $agency_details['contact_name'] ?? $agency_details['name'] ?? '',
                        $agency_details['agency_email'] ?? $agency_details['email'] ?? '',
                        $entry['created_at'],
                        !empty($entry_status) ? $entry_status : 'None',
                        $days_left
                    ];
                }

                // Write row to CSV
                fputcsv($file, $row);
            }

            // Close file
            fclose($file);

            // Clean up old CSV files (older than 24 hours)
            self::cleanup_old_csv_files($csv_dir);

            // Return success with file URL
            return [
                'success' => true,
                'message' => 'CSV file generated successfully.',
                'file_url' => $file_url,
                'filename' => $filename,
                'total_entries' => count($entries_list['entries'])
            ];

        } catch (Exception $e) {
            error_log('CSV Generation Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while generating CSV: ' . $e->getMessage()
            ];
        }
    }
    private static function cleanup_old_csv_files($directory)
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = glob($directory . '/applications_export_*.csv');
        $now = time();

        foreach ($files as $file) {
            if (is_file($file)) {
                // Delete files older than 24 hours
                if ($now - filemtime($file) >= 24 * 3600) {
                    @unlink($file);
                }
            }
        }
    }
    public static function get_all_entries_of_form_29()
    {
        if (!class_exists('FrmEntry')) {
            return [];
        }

        global $wpdb;

        $form_id = 29;

        // Fetch all non-draft entry IDs for form 29
        $entry_ids = $wpdb->get_col(
            $wpdb->prepare(
                "
            SELECT id
            FROM {$wpdb->prefix}frm_items
            WHERE form_id = %d
            AND is_draft = 0
            ORDER BY created_at DESC
            ",
                $form_id
            )
        );

        if (empty($entry_ids)) {
            return [];
        }

        $entries = [];

        foreach ($entry_ids as $entry_id) {
            $entry = FrmEntry::getOne($entry_id, true);
            if (!$entry) {
                continue;
            }

            // Get form info
            $form = FrmForm::getOne($entry->form_id);

            $entries[] = [
                'entry_id' => $entry->id,
                'form_id' => $entry->form_id,
                'form_name' => $form ? $form->name : '',
                'created_at' => $entry->created_at,
                'metas' => $entry->metas, // all field values
            ];
        }

        return $entries;
    }


    public static function get_formidable_entries_by_agency_ids($form_ids = [], $agency_id = 0, $page = 1, $per_page = 5, $user_id = 0)
    {
        if (!class_exists('FrmEntry') || !class_exists('FrmEntryMeta')) {
            return [];
        }

        // ---- Normalize form IDs ----
        $clean_ids = [];
        foreach ($form_ids as $id => $name) {
            $clean_ids[] = intval($id);
        }

        if (empty($clean_ids)) {
            return [];
        }

        // ---- Special case: agency_id = 0 and user_id != 0 ----
        $is_form_29_user_filter = ($agency_id == 0 && $user_id != 0);

        if ($is_form_29_user_filter) {
            // Override to only fetch Form 29
            $clean_ids = [29];
            // Use user_id as the filter value instead of agency_id
            $filter_value = $user_id;
        } else {
            $filter_value = $agency_id;
        }

        global $wpdb;
        $ids_string = implode(',', $clean_ids);

        // ---- Get all fields matching "agency_id" ----
        $agency_field_ids = $wpdb->get_col("
        SELECT id 
        FROM {$wpdb->prefix}frm_fields 
        WHERE field_key LIKE '%agency_id%' 
        AND form_id IN ($ids_string)
    ");

        if (empty($agency_field_ids)) {
            return [
                'entries' => [],
                'pagination' => [
                    'total' => 0,
                    'per_page' => $per_page,
                    'current_page' => $page,
                    'total_pages' => 0,
                ]
            ];
        }

        $agency_field_ids_str = implode(',', $agency_field_ids);

        //
        // ===============================
        // ⭐ Filter by agency_id (or user_id for Form 29)
        // ===============================
        //
        $where_clause = "
        it.form_id IN ($ids_string)
        AND it.is_draft = 0
        AND im.field_id IN ($agency_field_ids_str)
        AND im.meta_value = %s
    ";

        // ---- Document joins: skip for Form 29 user filter ----
        $doc_joins = '';
        if (!$is_form_29_user_filter) {
            $doc_joins = "
            LEFT JOIN {$wpdb->prefix}esign_documents_meta em
                ON em.meta_value = it.id 
                AND em.meta_key = 'esig_formidable_entry_id'
            LEFT JOIN {$wpdb->prefix}esign_documents ed
                ON ed.document_id = em.document_id
        ";
        }

        //
        // =============================================
        // ⭐ 1️⃣ COUNT QUERY
        // =============================================
        //
        $count_sql = "
        SELECT COUNT(DISTINCT it.id)
        FROM {$wpdb->prefix}frm_items it
        INNER JOIN {$wpdb->prefix}frm_item_metas im
            ON it.id = im.item_id
        $doc_joins
        WHERE $where_clause
    ";

        $total_results = $wpdb->get_var(
            $wpdb->prepare($count_sql, [$filter_value])
        );

        //
        // =============================================
        // ⭐ 2️⃣ DATA QUERY
        // =============================================
        //
        $offset = ($page - 1) * $per_page;

        $data_sql = "
        SELECT DISTINCT it.id
        FROM {$wpdb->prefix}frm_items it
        INNER JOIN {$wpdb->prefix}frm_item_metas im
            ON it.id = im.item_id
        $doc_joins
        WHERE $where_clause
        ORDER BY it.created_at DESC
        LIMIT %d OFFSET %d
    ";

        $entry_ids = $wpdb->get_col(
            $wpdb->prepare($data_sql, [$filter_value, $per_page, $offset])
        );

        $total_pages = ceil($total_results / $per_page);

        if (empty($entry_ids)) {
            return [
                'entries' => [],
                'pagination' => [
                    'total' => (int) $total_results,
                    'per_page' => $per_page,
                    'current_page' => $page,
                    'total_pages' => $total_pages,
                ]
            ];
        }

        //
        // Load each entry with meta + esign doc
        //
        $entries_output = [];

        foreach ($entry_ids as $entry_id) {
            $entry = FrmEntry::getOne($entry_id, true);
            if (!$entry)
                continue;

            // Map fields
            $field_map = [];
            $form_fields = FrmField::getAll(['fi.form_id' => $entry->form_id]);
            foreach ($form_fields as $field) {
                $field_map[$field->id] = $field->field_key;
            }

            // Extract agency_id
            $fields = [];
            foreach ($entry->metas as $field_id => $value) {
                $meta_key = $field_map[$field_id] ?? $field_id;

                if (str_contains($meta_key, 'agency_id')) {
                    if (str_contains($value, '[')) {
                        $value = 0;
                    }
                    $fields['agency_id'] = $value;
                }
            }

            $form = FrmForm::getOne($entry->form_id);

            // ---- Only get document details if NOT Form 29 user filter ----
            $document_details = [];
            if (!$is_form_29_user_filter) {
                $document_details = self::get_esign_document_details_by_entry_id($entry->id);
            }

            $due_date_details = self::get_days_left_and_date_after_30_days($entry->created_at);

            $entry_approdeny_details = self::nm_get_entry_created_at($entry->id);
            $entry_approval_status = 'none';
            $entry_approvaldeny_date = 'N/A';

            if ($entry->form_id == 29 && $entry_approdeny_details['success']) {
                $entry_approval_status = $entry_approdeny_details['entry_status'];
                $entry_approvaldeny_date = $entry_approdeny_details['created_at'];
            }

            $entries_output[$entry->id] = [
                'entry_id' => $entry->id,
                'form_id' => $entry->form_id,
                'form_key' => $form->form_key,
                'form_name' => $form->name,
                'created_at' => date('Y-m-d', strtotime($entry->created_at)),
                'due_date' => $due_date_details['target_date'],
                'entry_approvaldeny_date' => $entry_approvaldeny_date,
                'entry_approval_staus' => $entry_approval_status,
                'fields' => $fields,
                'time_trigger' => $due_date_details['days_left'],
                'document_details' => $document_details
            ];
        }

        return [
            'entries' => array_values($entries_output),
            'pagination' => [
                'total' => (int) $total_results,
                'per_page' => $per_page,
                'current_page' => $page,
                'total_pages' => $total_pages,
            ],
            'applied_filter' => [
                'agency_id' => $agency_id,
                'user_id' => $user_id,
                'filter_mode' => $is_form_29_user_filter ? 'form_29_by_user' : 'by_agency'
            ]
        ];
    }

    public static function nm_search_agencies_by_text($search_text)
    {
        global $wpdb;

        if (empty($search_text)) {
            return [];
        }

        $like = '%' . $wpdb->esc_like($search_text) . '%';

        $agency_ids = $wpdb->get_col(
            $wpdb->prepare(
                "
            SELECT DISTINCT p.ID
            FROM {$wpdb->posts} p

            /* Match agency author */
            INNER JOIN {$wpdb->users} u
                ON u.ID = p.post_author

            /* Only required ACF/meta fields */
            LEFT JOIN {$wpdb->postmeta} pm
                ON pm.post_id = p.ID
                AND pm.meta_key IN ('agency_name', 'email')

            WHERE p.post_type = 'agencies'
            AND p.post_status = 'publish'
            AND (
                p.post_title LIKE %s
                OR (pm.meta_key = 'agency_name' AND pm.meta_value LIKE %s)
                OR (pm.meta_key = 'email' AND pm.meta_value LIKE %s)
                OR u.display_name LIKE %s
            )
            ",
                $like,
                $like,
                $like,
                $like
            )
        );

        return array_map('intval', $agency_ids);
    }

    public static function nm_search_users_by_text($search_text)
    {
        global $wpdb;

        if (empty($search_text)) {
            return [];
        }

        $like = '%' . $wpdb->esc_like($search_text) . '%';

        $user_ids = $wpdb->get_col(
            $wpdb->prepare(
                "
            SELECT DISTINCT u.ID
            FROM {$wpdb->users} u
            WHERE u.display_name LIKE %s
               OR u.user_email LIKE %s
            ",
                $like,
                $like
            )
        );

        return array_map('intval', $user_ids);
    }

    public static function get_agency_details_by_its_id($agency_id)
    {
        $agency_details = get_post($agency_id);

        if ($agency_details && $agency_details->post_type == 'agencies') {
            $user = get_user_by('ID', $agency_details->post_author);

            return [
                'agency_name' => !empty($agency_details->post_title) ? $agency_details->post_title : get_field('agency_name', $agency_id),
                'agency_email' => get_field('email', $agency_id),
                'contact_name' => ($user) ? $user->display_name : '',
                'phone' => get_field('phone', $agency_id),
                'physical_address' => get_field('physical_address', $agency_id),
                'medicaid_id' => get_field('agency_medicaid_enrollment_id', $agency_id),
                'npi' => get_field('agency_group_npi', $agency_id),
            ];
        }

        return [];

    }

    public static function get_date_difference($specificDateString)
    {
        // Define the specific date you want to compare

        // Create DateTime objects for both dates
        $specificDate = new DateTime($specificDateString);
        $currentDate = new DateTime(); // Defaults to current date and time

        // Calculate the difference
        $interval = $currentDate->diff($specificDate);

        return $interval;
    }

    public static function get_days_left_and_date_after_30_days($date_time)
    {
        // Original date
        $startDate = new DateTime($date_time);

        // Target date (+30 days)
        $targetDate = (clone $startDate)->modify('+30 days');

        // Today (date-only comparison)
        $today = new DateTime('today');

        // Default days left
        $diff = $today->diff($targetDate);

        $daysLeft = (int) $diff->format('%r%a');

        return [
            'days_left' => $daysLeft,
            'target_date' => $targetDate->format('Y-m-d'),
        ];
    }




    public static function create_entry_list_mockup($entries = [], $pagetype, $is_opre_manager = false)
    {
        if (empty($entries)) {
            return "<p class='nmpl-18'>No Entries Found!</p>";
        }

        ob_start();

        foreach ($entries as $entry) {

            $agency_id = isset($entry['fields']['agency_id'])
                ? intval($entry['fields']['agency_id'])
                : 0;

            if ($agency_id <= 0) {
                continue;
            }

            // Get agency details
            $agency_details = self::get_agency_details_by_its_id($agency_id);

            if (empty($agency_details)) {
                $agency_details = AgencyDetailsDisplay::get_current_user_details($agency_id);
                if (empty($agency_details)) {
                    continue;

                }

            }

            // Prepare values safely
            $agency_name = esc_html($agency_details['agency_name'] ?? $agency_details['username']);
            $contact_name = esc_html($agency_details['contact_name'] ?? $agency_details['name']);
            $agency_email = esc_html($agency_details['agency_email'] ?? $agency_details['email']);
            $agency_phone = $phone = $agency_details['cell_phone'] ?: ($agency_details['home_phone'] ?? null);
            $form_name = strtoupper(esc_html($entry['form_name']));
            $created_at = esc_html($entry['created_at']);
            $days_left = intval($entry['time_trigger']);
            $status_color = NM_Helpers::nm_status_class_identifier((isset($entry['document_details']) && !empty($entry['document_details'])) ? $entry['document_details']['document_status'] : "None");
            $status_color_opre = NM_Helpers::nm_status_class_identifier((isset($entry['entry_approval_staus']) && !empty($entry['entry_approval_staus'])) ? $entry['entry_approval_staus'] : "None");
            $entry_status = 'N/A';
            if (isset($entry['document_details']) && !empty($entry['document_details']) && isset($entry['document_details']['document_status'])) {
                $entry_status = $entry['document_details']['document_status'];
            } else {
                $entry_status = 'None';
            }
            $days_left = 'N/A';
            $entry_due_left = false;
            if (!(str_contains('approve', strtolower($entry_status)) || str_contains('approved', strtolower($entry_status)) || str_contains('denied', strtolower($entry_status)) || str_contains('den', strtolower($entry_status)))) {
                if (($entry['time_trigger'] == 0)) {
                    $days_left = "Today";
                    $entry_due_left = true;

                } else if ($entry['time_trigger'] < 0) {
                    $days_left = "Overdue";
                    $entry_due_left = true;
                } else {
                    $days_left = $entry['time_trigger'] . ' days left';
                }
            }
            if ($pagetype == 'agency_overview') {
                ?>
                <tr data-type="CCSS" data-agency-id="<?php echo esc_attr($agency_id); ?>"
                    class="<?php echo !$is_opre_manager && $entry_due_left ? 'nm_entry_expired' : ''; ?>">
                    <td>
                        <a
                            href="<?php echo '' . site_url() . '/entry-details/?' . ($is_opre_manager ? 'user-id' : 'agency-id') . '=' . $agency_id . ''; ?>"><?php echo $agency_name; ?></a>
                    </td>

                    <?php echo !$is_opre_manager ? '<td>' . $form_name . ' </td>' : ''; ?>


                    <td><?php echo $contact_name; ?></td>

                    <td><?php echo $agency_email; ?></td>

                    <?php echo $is_opre_manager ? '<td>' . $agency_phone . '</td>' : ''; ?>


                    <td><?php echo $is_opre_manager ? $entry['create_at_with_time'] : $entry['created_at']; ?>
                        <?php if (!$is_opre_manager) { ?>
                        <td><span
                                class="badge <?php echo $status_color; ?>"><?php echo !empty($entry_status) ? $entry_status : 'None'; ?></span>
                        </td>
                    <?php } else { ?>
                        <td><span class="badge <?php echo $status_color_opre; ?>"><?php echo $entry['entry_approval_staus']; ?></span>
                        </td>
                    <?php } ?>

                    <?php if (!$is_opre_manager) {
                        ?>
                        <td>
                            <?php
                            echo $days_left;
                            ?>
                        </td>
                        <?php
                    } ?>
                    <?php echo $is_opre_manager ? '<td>' . $entry['entry_approvaldeny_date'] . '</td>' : ''; ?>


                </tr>

                <?php
            } else {

                $agency_id = $entry['fields']['agency_id'];

                $agency_details = NM_Helpers::get_agency_details_by_its_id($agency_id);
                $status_color = NM_Helpers::nm_status_class_identifier((isset($entry['document_details']) && !empty($entry['document_details'])) ? $entry['document_details']['document_status'] : "None");
                $status_color_opre = NM_Helpers::nm_status_class_identifier((isset($entry['entry_approval_staus']) && !empty($entry['entry_approval_staus'])) ? $entry['entry_approval_staus'] : "None");
                if (empty($agency_details)) {
                    continue;
                }
                ?>
                <tr data-type="CCSS" agency-id="<?php echo $agency_id; ?>"
                    class="<?php echo !$is_opre_manager && $entry_due_left ? 'nm_entry_expired' : ''; ?>">
                    <td><a href="#" entry-id="<?php echo $entry['entry_id']; ?>"
                            class="entry_id_filter"><?php echo 'Application #' . $entry['entry_id'] . ''; ?></a>
                    </td>
                    <?php if (!$is_opre_manager) { ?>
                        <td><span
                                class="badge <?php echo $status_color; ?>"><?php echo !empty($entry_status) ? $entry_status : 'None'; ?></span>
                        </td>
                    <?php } else { ?>
                        <td><span class="badge <?php echo $status_color_opre; ?>"><?php echo $entry['entry_approval_staus']; ?></span>
                        </td>
                    <?php } ?>
                    <?php if (!$is_opre_manager) { ?>

                        <td><?php echo strtoupper($entry['form_key']); ?></td>
                        <td><?php echo $entry['created_at']; ?></td>

                        <td><?php echo $entry['due_date']; ?></td>
                        <td> <?php echo $days_left; ?>
                        </td>
                    <?php } else {
                        ?>
                        <td><?php echo $agency_details['username']; ?></td>
                        <td><?php echo $agency_details['entry_approvaldeny_date']; ?></td>

                        <?php
                    } ?>
                </tr>
                <?php

            }
        }

        return ob_get_clean(); // FIXED: now returns the table HTML
    }

    public static function create_pagination_mockup($pagination = [], $currentpage = 1)
    {
        if (empty($pagination)) {
            return false;
        }

        $total_pages = intval($pagination['total_pages']);
        $window = 3; // number of visible page links

        ob_start();

        if ($total_pages > 1) {

            // Prev
            ?>
            <li class="page-item <?php echo ($currentpage <= 1) ? 'nm_disabled' : ''; ?>"
                page-id="<?php echo max(1, $currentpage - 1); ?>">
                <a href="#">&laquo;</a>
            </li>
            <?php

            /**
             * Calculate sliding window
             */
            $half = floor($window / 2);
            $start = max(1, $currentpage - $half);
            $end = min($total_pages, $start + $window - 1);

            // Fix window near the end
            if (($end - $start + 1) < $window) {
                $start = max(1, $end - $window + 1);
            }

            // Leading dots
            if ($start > 1) { ?>
                <li class="page-item dots">
                    <span>…</span>
                </li>
            <?php }

            // Page numbers
            for ($i = $start; $i <= $end; $i++) { ?>
                <li class="page-item <?php echo ($i == $currentpage) ? 'active' : ''; ?>" page-id="<?php echo $i; ?>">
                    <a href="#"><?php echo $i; ?></a>
                </li>
            <?php }

            // Trailing dots + last page
            if ($end < $total_pages) { ?>
                <li class="page-item dots">
                    <span>…</span>
                </li>

                <li class="page-item <?php echo ($currentpage == $total_pages) ? 'active' : ''; ?>"
                    page-id="<?php echo $total_pages; ?>">
                    <a href="#"><?php echo $total_pages; ?></a>
                </li>
            <?php }

            // Next
            ?>
            <li class="page-item <?php echo ($currentpage >= $total_pages) ? 'nm_disabled' : ''; ?>"
                page-id="<?php echo min($total_pages, $currentpage + 1); ?>">
                <a href="#">&raquo;</a>
            </li>
            <?php
        }

        return ob_get_clean();
    }



    /**
     * Get allowed forms for the current user with proper role & error handling.
     *
     * @return array|false  Array of form objects indexed by ID, or false on error.
     */
    public static function tb_get_user_allowed_forms($userId = 0)
    {

        $user_id = $userId == 0 ? get_current_user_id() : $userId;
        if (!$user_id) {
            return false;
        }

        $user_data = get_user_by('id', $user_id);
        if (!$user_data || !isset($user_data->roles)) {
            return false;
        }

        // Check roles
        $roles = $user_data->roles;
        $has_admin = in_array('administrator', $roles, true);
        $has_nm_manager = in_array('nm_manager', $roles, true);
        $has_supervisor = in_array('supervisor', $roles, true);

        // If not authorized → return false
        if (!$has_admin && !$has_nm_manager && !$has_supervisor) {
            return false;
        }

        // Get forms selected for this user
        $forms_arrays = get_field('select_the_application_form_for_this_company', 'user_' . $user_id);
        if (empty($forms_arrays) || !is_array($forms_arrays)) {
            return false;
        }

        // Ensure FrmForm class exists
        if (!class_exists('FrmForm')) {
            return false;
        }

        $form_name = [];

        foreach ($forms_arrays as $form_id) {
            $form = FrmForm::getOne($form_id);

            if (empty($form)) {
                false; // any missing form = fail the entire process
            }

            $form_name[$form_id] = $form;
        }

        return $form_name;
    }

    public static function nm_get_managers_by_entry_form($entry_id)
    {
        $entry_id = absint($entry_id);

        if (!$entry_id) {
            return [];
        }

        // Ensure Formidable is available
        if (!class_exists('FrmEntry')) {
            return [];
        }

        // Get Formidable entry
        $entry = FrmEntry::getOne($entry_id);

        if (empty($entry) || empty($entry->form_id)) {
            return [];
        }

        $form_id = (int) $entry->form_id;

        // Get nm_manager users only
        $users = get_users([
            'role' => 'nm_manager',
            'fields' => 'all',
        ]);

        if (empty($users)) {
            return [];
        }

        $matched_users = [];

        foreach ($users as $user) {
            // Get user's ACF field
            $selected_forms = get_field(
                'select_the_application_form_for_this_company',
                'user_' . $user->ID
            );

            if (empty($selected_forms)) {
                continue;
            }

            // Normalize to array
            if (!is_array($selected_forms)) {
                $selected_forms = [$selected_forms];
            }

            // Cast to int for safe comparison
            $selected_forms = array_map('intval', $selected_forms);

            if (in_array($form_id, $selected_forms, true)) {
                $matched_users[] = $user;
            }
        }

        return $matched_users[0];
    }

    public static function nm_user_has_access_to_entry_form($entry_id, $user_id)
    {
        // Validate params
        $entry_id = absint($entry_id);
        $user_id = absint($user_id);

        if (!$entry_id || !$user_id) {
            return false;
        }

        // Get Formidable entry
        if (!class_exists('FrmEntry')) {
            return false;
        }

        $entry = FrmEntry::getOne($entry_id);

        if (empty($entry) || empty($entry->form_id)) {
            return false;
        }

        $form_id = (int) $entry->form_id;

        // Get user ACF field (returns array or single value depending on field type)
        $selected_forms = get_field(
            'select_the_application_form_for_this_company',
            'user_' . $user_id
        );

        if (empty($selected_forms)) {
            return false;
        }

        // Normalize to array
        if (!is_array($selected_forms)) {
            $selected_forms = [$selected_forms];
        }

        // Cast all values to int for safe comparison
        $selected_forms = array_map('intval', $selected_forms);
        // Match found → return email
        if (in_array($form_id, $selected_forms, true)) {
            $user = get_userdata($user_id);

            if ($user && !empty($user->user_email)) {
                return $user->user_email;
            }
        }


        return false;
    }

    public static function preload_esign_document_details(array $entry_ids)
    {
        global $wpdb;

        if (empty($entry_ids)) {
            return;
        }

        static $loaded = false;
        static $cache = [];

        // Prevent double-loading
        if ($loaded) {
            return;
        }

        $entry_ids = array_map('intval', array_unique($entry_ids));
        $ids_sql = implode(',', $entry_ids);

        $sql = "
        SELECT 
            it.id AS entryID,
            ed.document_id,
            ed.document_title,
            ed.document_status,
            ed.date_created,
            ed.document_checksum,
            ei.invite_hash
        FROM {$wpdb->prefix}frm_items it
        JOIN {$wpdb->prefix}esign_documents_meta em
            ON em.meta_value = it.id
            AND em.meta_key = 'esig_formidable_entry_id'
        JOIN {$wpdb->prefix}esign_documents ed
            ON ed.document_id = em.document_id
        LEFT JOIN {$wpdb->prefix}esign_invitations ei
            ON ei.document_id = ed.document_id
        WHERE it.id IN ($ids_sql)
        AND ed.document_type = 'normal'
        AND ed.document_status NOT IN ('trash','duplicate')
        ORDER BY ed.document_id DESC
    ";

        $rows = $wpdb->get_results($sql);

        foreach ($rows as $row) {

            $status = $row->document_status;
            if ($status === 'awaiting') {
                $status = 'submitted';
            } elseif ($status === 'signed') {
                $status = 'acknowledged';
            }

            $cache[$row->entryID] = [
                'entry_id' => $row->entryID,
                'document_id' => $row->document_id,
                'document_title' => $row->document_title,
                'document_status' => $status,
                'date_created' => $row->date_created,
                'checksum' => $row->document_checksum,
                'invite_hash' => $row->invite_hash,
            ];
        }

        // Overlay nm_entries status (batch)
        $nm_rows = $wpdb->get_results("
        SELECT entry_id, entry_status
        FROM {$wpdb->prefix}nm_entries
        WHERE entry_id IN ($ids_sql)
    ");

        foreach ($nm_rows as $nm) {
            if (!empty($cache[$nm->entry_id])) {
                $cache[$nm->entry_id]['document_status'] = $nm->entry_status;
            }
        }

        self::$esign_cache = $cache;
        $loaded = true;
    }

    public static $esign_cache = [];

    public static function get_esign_document_details_by_entry_id($entry_id)
    {
        $entry_id = (int) $entry_id;

        // ✅ Instant return if already loaded
        if (isset(self::$esign_cache[$entry_id])) {
            return self::$esign_cache[$entry_id];
        }

        global $wpdb;

        $sql = "
        SELECT 
            it.id AS entryID,
            ed.document_id,
            ed.document_title,
            ed.document_status,
            ed.date_created,
            ed.document_checksum,
            ei.invite_hash
        FROM {$wpdb->prefix}frm_items it
        JOIN {$wpdb->prefix}esign_documents_meta em
            ON em.meta_value = it.id 
            AND em.meta_key = 'esig_formidable_entry_id'
        JOIN {$wpdb->prefix}esign_documents ed
            ON ed.document_id = em.document_id
        LEFT JOIN {$wpdb->prefix}esign_invitations ei
            ON ei.document_id = ed.document_id
        WHERE it.id = %d
        AND ed.document_type = 'normal'
        AND ed.document_status NOT IN ('trash','duplicate')
        ORDER BY ed.document_id DESC
        LIMIT 1
    ";

        $row = $wpdb->get_row($wpdb->prepare($sql, $entry_id));

        $document_status = 'None';

        if ($row) {
            if ($row->document_status === 'awaiting') {
                $document_status = 'submitted';
            } elseif ($row->document_status === 'signed') {
                $document_status = 'acknowledged';
            }
        }

        $entry_status_details = self::nm_get_entry($entry_id);
        if (!empty($entry_status_details['entry_status'])) {
            $document_status = $entry_status_details['entry_status'];
        }

        return self::$esign_cache[$entry_id] = [
            'entry_id' => $row->entryID ?? $entry_id,
            'document_id' => $row->document_id ?? null,
            'document_title' => $row->document_title ?? '',
            'document_status' => $document_status,
            'date_created' => $row->date_created ?? '',
            'checksum' => $row->document_checksum ?? '',
            'invite_hash' => $row->invite_hash ?? '',
        ];
    }



    public static function nm_status_class_identifier($status)
    {
        $status = strtolower($status);
        if ($status == "approve" || $status == "approved") {
            return "success";
        } else if ($status == "acknowledged") {
            return "info";
        } else if ($status == "submitted") {
            return "warning";
        } else if ($status == "denied" || $status == "deny") {
            return "destructive";
        } else {
            return "destructive";
        }
    }

    public static function get_file_fields_from_entry($entry_id)
    {
        if (!class_exists('FrmEntry') || !class_exists('FrmField')) {
            return [];
        }

        // --- DEFINE CUSTOM FIELD IDS TO INCLUDE ---
        // Replace 1005, 1006 with the actual numeric IDs you created for your custom meta.
        $custom_file_meta_ids = [1005, 1006];
        // ------------------------------------------

        $entry = FrmEntry::getOne($entry_id, true);
        if (!$entry) {
            return [];
        }

        $files = [];

        foreach ($entry->metas as $field_id => $value) {

            $field = FrmField::getOne($field_id);
            $is_custom_file_meta = in_array((int) $field_id, $custom_file_meta_ids);

            // CHECK: Include standard 'file' fields OR explicitly defined custom file meta IDs
            if (($field && $field->type === 'file') || $is_custom_file_meta) {

                // If it's custom meta, the value should be the attachment ID (or comma-separated list)
                $ids = is_array($value) ? $value : explode(',', $value);
                $ids = array_map('trim', $ids);

                $file_items = [];

                foreach ($ids as $id) {
                    // Skip if the value isn't a valid attachment ID (like if the custom meta stored a string)
                    if (!is_numeric($id) || empty($id))
                        continue;

                    $url = wp_get_attachment_url($id);
                    $name = basename($url); // extract file name

                    $file_items[] = [
                        'attachment_id' => $id,
                        'attachment_url' => $url,
                        'attachment_name' => $name,
                    ];
                }

                // Return full info
                $files[] = [
                    'field_id' => $field_id,
                    'field_name' => $field ? $field->name : 'Custom Meta ID ' . $field_id,
                    'files' => $file_items,
                ];
            }
        }

        return $files;
    }

    /**
     * Retrieves all custom meta key/value pairs from the 'nm_frm_supplementals' table,
     * specifically processing attachment IDs to return file details and associated post meta.
     *
     * @param int|string $entry_id The ID of the Formidable Forms entry (item_id).
     * @return array An array of custom meta objects, or an empty array if none found.
     */
    public static function nm_get_custom_dynamic_meta_enhanced($entry_id)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'nm_frm_supplementals';
        $entry_id = absint($entry_id);

        if ($entry_id === 0) {
            return [];
        }

        $sql = $wpdb->prepare(
            "SELECT meta_key, meta_value, created_at
         FROM `$table_name` 
         WHERE frm_entry_id = %d 
         ORDER BY created_at ASC",
            $entry_id
        );

        $results = $wpdb->get_results($sql);
        if (empty($results)) {
            return [];
        }

        $processed_meta = [];
        $base_file_key_prefix = 'supplemental_fields_'; // <-- ADJUSTED PREFIX

        foreach ($results as $row) {
            $meta_key = $row->meta_key;
            $value = maybe_unserialize($row->meta_value);

            // Check if this key holds the file/URL data
            $is_file_source_key = (strpos($meta_key, $base_file_key_prefix) === 0);

            // If this is a file/URL source key, we will process it
            if ($is_file_source_key) {

                $file_details = [
                    'attachment_id' => null,
                    'attachment_url' => null,
                    'attachment_name' => null,
                    '_nm_document_name' => null, // Placeholder for consistent output
                    '_nm_supporting_note' => null, // Placeholder for consistent output
                    'source_type' => 'other',
                ];

                $is_url = is_string($value) && filter_var($value, FILTER_VALIDATE_URL);
                $is_id = is_numeric($value) && $value > 0;

                $attachment_id = $is_id ? absint($value) : null;
                $url = null;

                if ($is_id) {
                    // Scenario 1: Value is a WordPress Attachment ID
                    $url = wp_get_attachment_url($attachment_id);

                    if ($url) {
                        $file_details['source_type'] = 'wp_attachment';
                        $file_details['attachment_id'] = $attachment_id;
                        $file_details['attachment_url'] = $url;
                        $file_details['attachment_name'] = basename($url);

                        // Retrieve custom meta ONLY from the WP attachment post
                        $file_details['_nm_document_name'] = get_post_meta($attachment_id, '_nm_document_name', true);
                        $file_details['_nm_supporting_note'] = get_post_meta($attachment_id, '_nm_supporting_note', true);
                    }
                } elseif ($is_url) {
                    // Scenario 2: Value is a direct URL string (External or Direct Upload Path)
                    $file_details['source_type'] = 'external_url';
                    $file_details['attachment_url'] = $value;
                    $file_details['attachment_name'] = basename(parse_url($value, PHP_URL_PATH));

                    // For a simple external URL, the two meta fields are NULL 
                    // because they are ONLY stored on a WP attachment post.
                    // Output structure remains consistent, but values are null.
                }

                // Only process the row if we found a valid URL/ID
                if ($file_details['attachment_url']) {
                    $processed_meta[] = [
                        'key' => $meta_key,
                        'value' => $value,
                        'created_at' => $row->created_at,
                        'attachment_details' => $file_details,
                    ];
                } else {
                    // Include the original row if it didn't match the file key logic, or if processing failed
                    $processed_meta[] = [
                        'key' => $meta_key,
                        'value' => $value,
                        'created_at' => $row->created_at,
                        'attachment_details' => null,
                    ];
                }
            }
        }

        return $processed_meta;
    }

    public static function nm_get_entry_created_at($entry_id)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'nm_entries';

        if (empty($entry_id)) {
            return [
                'success' => false,
                'message' => 'Invalid entry ID.',
            ];
        }

        // Fetch the created_at and entry_status columns for the given entry
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT created_at, entry_status FROM {$table} WHERE entry_id = %d",
                (int) $entry_id
            ),
            ARRAY_A
        );

        if (!$row) {
            return [
                'success' => false,
                'message' => 'Entry not found.',
            ];
        }

        return [
            'success' => true,
            'entry_id' => (int) $entry_id,
            'created_at' => date('Y-m-d', strtotime($row['created_at'])),
            'entry_status' => sanitize_text_field($row['entry_status']),
        ];
    }
    public static function nm_upsert_entry($entry_id, $html_content = null, $status = null)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'nm_entries';

        if (empty($entry_id)) {
            return [
                'success' => false,
                'message' => 'Invalid entry ID.',
            ];
        }

        // Check if entry exists
        $existing_row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE entry_id = %d",
                (int) $entry_id
            ),
            ARRAY_A
        );

        /**
         * -----------------------
         * UPDATE (Entry Exists)
         * -----------------------
         */
        if ($existing_row) {

            $data = [];
            $format = [];

            if ($html_content !== null) {
                $data['html_content'] = $html_content;
                $format[] = '%s';
            }

            if ($status !== null) {
                $data['entry_status'] = sanitize_text_field($status);
                $format[] = '%s';
            }

            // Nothing to update
            if (empty($data)) {
                return [
                    'success' => true,
                    'message' => 'No changes provided.',
                    'action' => 'noop',
                    'entry_id' => (int) $entry_id,
                ];
            }

            $updated = $wpdb->update(
                $table,
                $data,
                ['entry_id' => (int) $entry_id],
                $format,
                ['%d']
            );

            if ($updated === false) {
                return [
                    'success' => false,
                    'message' => 'Failed to update entry.',
                    'error' => $wpdb->last_error,
                ];
            }

            return [
                'success' => true,
                'message' => 'Entry updated successfully.',
                'action' => 'updated',
                'entry_id' => (int) $entry_id,
            ];
        }

        /**
         * -----------------------
         * INSERT (Entry Missing)
         * -----------------------
         */
        $insert_data = [
            'entry_id' => (int) $entry_id,
        ];

        $insert_format = ['%d'];

        // Optional fields on insert
        if ($html_content !== null) {
            $insert_data['html_content'] = $html_content;
            $insert_format[] = '%s';
        } else {
            $insert_data['html_content'] = '';
            $insert_format[] = '%s';
        }

        if ($status !== null) {
            $insert_data['entry_status'] = sanitize_text_field($status);
            $insert_format[] = '%s';
        }

        $inserted = $wpdb->insert(
            $table,
            $insert_data,
            $insert_format
        );

        if ($inserted === false) {
            return [
                'success' => false,
                'message' => 'Failed to insert entry.',
                'error' => $wpdb->last_error,
            ];
        }

        return [
            'success' => true,
            'message' => 'Entry created successfully.',
            'action' => 'inserted',
            'entry_id' => (int) $entry_id,
        ];
    }



    /**
     * Get Formidable field type by field ID
     *
     * @param int $field_id
     * @return string|null  Field type (e.g. text, email, checkbox) or null if not found
     */
    public static function get_formidable_field_type($field_id)
    {
        $field_id = absint($field_id);

        if (!$field_id) {
            return null;
        }

        // Preferred method: Formidable API
        if (class_exists('FrmField')) {
            $field = FrmField::getOne($field_id);

            if (!empty($field) && !empty($field->type)) {
                return $field->type;
            }
        }

        // Fallback: direct database query
        global $wpdb;

        $table = $wpdb->prefix . 'frm_fields';

        $type = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT type FROM {$table} WHERE id = %d",
                $field_id
            )
        );

        return $type ?: null;
    }
    /**
     * Get a date by adding/subtracting years from a given date
     *
     * @param string $date_string  Any valid date string (e.g. 2025-10-29)
     * @param int    $years        Number of years to add (use negative to subtract)
     *
     * @return string|null         Formatted date (e.g. October 29, 2026) or null on failure
     */
    public static function nm_get_date_from_years($date_string, $years)
    {
        if (empty($date_string) || !is_numeric($years)) {
            return null;
        }

        try {
            $date = new DateTime($date_string);
            $interval = new DateInterval('P' . abs((int) $years) . 'Y');

            if ($years >= 0) {
                $date->add($interval);
            } else {
                $date->sub($interval);
            }

            return $date->format('F j, Y');

        } catch (Exception $e) {
            return null;
        }
    }

    public static function nm_generate_doc_as_html($html, $filename = 'document')
    {
        $upload_dir = wp_upload_dir();

        $file_path = $upload_dir['basedir'] . '/' . $filename . '.doc';
        $file_url = $upload_dir['baseurl'] . '/' . $filename . '.doc';

        $html = '<html>
        <head>
            <meta charset="UTF-8">
        </head>
        <body>' . $html . '</body>
    </html>';

        file_put_contents($file_path, $html);

        return $file_url;
    }

    public static function nm_insert_entry($html_content, $status = 'draft', $entry_id = 0)
    {
        global $wpdb;

        if (!$entry_id) {
            return "Entry is required";
        }

        $table = $wpdb->prefix . 'nm_entries';

        $result = $wpdb->insert(
            $table,
            [
                'entry_id' => (int) $entry_id,
                'entry_status' => sanitize_text_field($status),
                'html_content' => wp_kses_post($html_content),
            ],
            ['%d', '%s', '%s']
        );

        if ($result === false) {
            return "error occcured!";
        }

        return $wpdb->insert_id;
    }

    public static function nm_get_entry($entry_id)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'nm_entries';

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE entry_id = %d",
                (int) $entry_id
            ),
            ARRAY_A
        );


        // Check for database errors
        if ($wpdb->last_error) {
            return [];
        }

        // More specific check - null means no result OR error
        if ($row === null) {
            return [];
        }

        // Verify we have the minimum required data
        if (!isset($row['entry_id'])) {
            return [];
        }


        $entry_status = '';

        if (isset($row['entry_status'])) {
            $status_lower = strtolower($row['entry_status']);
            if (str_contains('approve', ($status_lower)) || str_contains('approved', ($status_lower))) {
                $entry_status = "Approved";
            } else if (str_contains('denied', ($status_lower)) || str_contains('deny', ($status_lower))) {
                $entry_status = "Denied";
            }
        }

        return [
            'id' => (int) $row['id'],
            'entry_id' => (int) $row['entry_id'],
            'entry_status' => $entry_status,
            'html_content' => $row['html_content'] ?? '',
            'created_at' => $row['created_at'] ?? '',
            'updated_at' => $row['updated_at'] ?? '',
        ];
    }



    public static function generate_docupilot_document($entry_id, $format = 'docx', $template_detaills, $letter_status = 'approval')
    {
        // 1. Replace with your actual API Key from Docupilot Settings > API Keys
        $api_key = '4dd59fbe5d3598c4fe71d78bee28aba7';

        // 2. We use the URL from your curl, but add ?download=true to get the file back
        $url = "https://arshad.docupilot.app/dashboard/documents/create/40b2e5a8/03434a4e?download=true&output_type={$format}";

        if ($letter_status == 'deny') {
            $url = "https://arshad.docupilot.app/dashboard/documents/create/40b2e5a8/9e6cadcc?download=true&output_type={$format}";
        }


        $response = wp_remote_post($url, [
            'timeout' => 45,
            'headers' => [
                'Content-Type' => 'application/json',
                'apikey' => $api_key // Docupilot requires this header
            ],
            'body' => json_encode($template_detaills),
        ]);

        // Check for errors
        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }
        $body = json_decode(wp_remote_retrieve_body($response), true);

        // Initialize
        $file_url = '';

        // Check if response is array
        if (is_array($body)) {
            // Check top-level 'file_url'
            if (!empty($body['file_url'])) {
                $file_url = $body['file_url'];
            }
            // Check nested 'data.file_url'
            elseif (!empty($body['data']) && is_array($body['data']) && !empty($body['data']['file_url'])) {
                $file_url = $body['data']['file_url'];
            }
        }

        // If file URL is missing, return error
        if (empty($file_url)) {
            return [
                'error' => 'URL not found in response',
                'debug' => $body
            ];
        }

        // Download and store the file
        $attachment_id = self::nm_download_and_store_file_from_url($file_url);

        if (is_wp_error($attachment_id)) {
            return [
                'error' => 'Failed to store file',
                'debug' => $attachment_id->get_error_message()
            ];
        }

        // Save entry ID for reference
        update_post_meta($attachment_id, '_nm_entry_id', $entry_id);

        // Get URL for return
        $attachment_url = wp_get_attachment_url($attachment_id);

        return [
            'url' => $attachment_url,
            'template_data' => $template_detaills
        ];


    }

    public static function nm_download_and_store_file_from_url($file_url)
    {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // Download file to temp location
        $temp_file = download_url($file_url);

        if (is_wp_error($temp_file)) {
            return $temp_file;
        }

        // Extract filename
        $file_name = basename(parse_url($file_url, PHP_URL_PATH));

        // Prepare array for media_handle_sideload
        $file_array = array(
            'name' => $file_name,
            'tmp_name' => $temp_file,
        );

        // Store in Media Library
        $attachment_id = media_handle_sideload($file_array, 0);

        // Cleanup on failure
        if (is_wp_error($attachment_id)) {
            @unlink($temp_file);
            return $attachment_id;
        }

        return $attachment_id; // Media attachment ID
    }



    /**
     * Generate PDF from HTML and store it as a WordPress attachment
     *
     * @param string $html_content   Raw HTML content
     * @param string $document_name  Document name (without .pdf)
     *
     * @return array|false
     *  [
     *      'attachment_id' => int,
     *      'url'           => string, 
     *      'path'          => string,
     *      'file_name'     => string,
     *  ]
     */
    public static function nm_embed_logo_base64_for_pdf($html)
    {
        $logo_path = NM_APPS_PATH . 'assets/logo.png';
        if (!file_exists($logo_path)) {
            return $html;
        }

        $logo_base64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logo_path));
        $logo_url = trailingslashit(NM_APPS_URL) . 'assets/logo.png';

        // Case 1: img src contains the logo URL (normal fresh HTML).
        $html = str_replace(esc_url($logo_url), $logo_base64, $html);
        $html = str_replace($logo_url, $logo_base64, $html);

        // Case 2: img has class="nm-logo-img" but src is empty or wrong
        // (happens when old HTML was saved before the URL fix — wp_kses_post
        // stripped the data: URI leaving src="").
        $html = preg_replace_callback(
            '/<img([^>]*class=["\'][^"\']*nm-logo-img[^"\']*["\'][^>]*)>/i',
            function ($m) use ($logo_base64) {
                $attrs = $m[1];
                if (preg_match('/src=["\'][^"\']*["\']/i', $attrs)) {
                    $attrs = preg_replace('/src=["\'][^"\']*["\']/i', 'src="' . $logo_base64 . '"', $attrs);
                } else {
                    $attrs = ' src="' . $logo_base64 . '"' . $attrs;
                }
                return '<img' . $attrs . '>';
            },
            $html
        );

        return $html;
    }

    public static function nm_generate_pdf_attachment($html_content, $document_name, $entry_id = 0)
    {
        if (empty($html_content) || empty($document_name)) {
            return false;
        }

        /**
         * ----------------------------------------------------
         * 1. Extract Header and Footer from HTML Content
         * ----------------------------------------------------
         */
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html_content);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // Extract header
        $header_node = $xpath->query('//header')->item(0);

        // Always inject a fresh base64 logo via DOM setAttribute (safe — no truncation)
        // so the PDF logo is never affected by stale DB HTML or wp_kses_post stripping.
        $logo_path = NM_APPS_PATH . 'assets/logo.png';
        if (file_exists($logo_path) && $header_node) {
            $logo_b64_src = 'data:image/png;base64,' . base64_encode(file_get_contents($logo_path));
            $imgs = $header_node->getElementsByTagName('img');
            if ($imgs->length > 0) {
                $imgs->item(0)->setAttribute('src', $logo_b64_src);
            }
        }

        $header_html = $header_node ? $dom->saveHTML($header_node) : '';

        // Extract footer
        $footer_node = $xpath->query('//footer')->item(0);
        $footer_html = $footer_node ? $dom->saveHTML($footer_node) : '';

        // Extract all sections
        $sections = [];
        $section_nodes = $xpath->query('//section');
        foreach ($section_nodes as $section) {
            $sections[] = $dom->saveHTML($section);
        }

        /**
         * ----------------------------------------------------
         * 2. Build Complete HTML with Header/Footer Structure
         * ----------------------------------------------------
         */
        $styles = "
<style>
    @page {
        margin: 140px 50px 120px 50px;
    }

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 12px;
        line-height: 1.3;
        color: #333;
    }

    /* Fixed header on every page */
    header {
        position: fixed;
        top: -120px;
        left: 0;
        right: 0;
        height: 200px;
        background-color: #fff;
    }

    header table {
        width: 100%;
        border-collapse: collapse;
    }

    header td {
        vertical-align: top;
        background: transparent !important;
        border: none !important;
        padding: 0 !important;
    }

    header img {
        width: 150px;
        margin-bottom: 10px;
    }

    .header-right {
        font-size: 11px;
        text-align: right;
        line-height: 1.4;
    }

    header table + table {
        margin-top: 15px;
    }

    header table + table td {
        border-bottom: 2px solid #1aa6b7 !important;
    }

    /* Fixed footer on every page */
    footer {
        position: fixed;
        bottom: -100px;
        left: 0;
        right: 0;
        height: 80px;
        background-color: #fff;
    }

    footer table {
        width: 100%;
        margin-top: 15px;
        border-collapse: collapse;
    }

    footer table td {
        border-bottom: 2px solid #1aa6b7 !important;
        background: transparent !important;
        padding: 0 !important;
    }

    footer p {
        text-align: center;
        margin-top: 10px;
        font-size: 11px;
    }

    /* Content sections - Allow natural page breaks */
    section {
        margin: 90px 0px 20px 0;
        page-break-inside: auto;
    }

    section h2 {
        text-align: center;
        margin-top: 15px;
        margin-bottom: 10px;
        font-size: 15px;
        page-break-after: avoid;
        page-break-inside: avoid;
    }

    section h3 {
        margin-top: 15px;
        margin-bottom: 10px;
        font-size: 13px;
        font-weight: bold;
        page-break-after: avoid;
        page-break-inside: avoid;
    }

    section p {
        margin: 8px 0;
        page-break-inside: avoid;
        orphans: 3;
        widows: 3;
    }

    section a {
        color: #1aa6b7;
        text-decoration: none;
    }

    .nm_body {
        page-break-inside: auto;
    }

    .nm_body p {
        margin: 8px 0;
        page-break-inside: avoid;
    }

    /* Force page break between sections */
    .section-break {
        page-break-before: always;
        page-break-after: avoid;
    }

    /* Prevent page breaks inside these elements */
    .keep-together {
        page-break-inside: avoid;
    }

    /* Table styling */
    table {
        border-collapse: collapse;
        page-break-inside: auto;
    }

    table tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }

    /* Remove background from all tables */
    table td {
        background: transparent !important;
        border: none !important;
    }

    /* Signature lines - keep together */
    p:has(u),
    p + p:has(u) {
        page-break-inside: avoid;
    }
</style>";

        /**
         * ----------------------------------------------------
         * 3. Construct Multi-page HTML with Natural Flow
         * ----------------------------------------------------
         */
        $sections_html = '';
        $total_sections = count($sections);

        foreach ($sections as $index => $section_content) {
            // Add section break class to force new page for each section
            if ($index > 0) {
                $section_content = preg_replace(
                    '/^<section/',
                    '<section class="section-break"',
                    $section_content,
                    1
                );
            }

            $sections_html .= $section_content;
        }

        $complete_html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    ' . $styles . '
</head>
<body>
    <!-- Header (appears on every page) -->
    ' . $header_html . '
    
    <!-- Footer (appears on every page) -->
    ' . $footer_html . '
    
    <!-- Content Sections (auto-paginate when content is large) -->
    ' . $sections_html . '
</body>
</html>';

        // Swap URL logo → base64 NOW, after DOMDocument is done.
        // Doing it earlier causes DOMDocument::loadHTML() to silently truncate the
        // ~8 000-char base64 attribute, producing a corrupt data URI that DOMPDF drops.
        $complete_html = self::nm_embed_logo_base64_for_pdf($complete_html);

        /**
         * ----------------------------------------------------
         * 4. Render PDF with proper configuration
         * ----------------------------------------------------
         */
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('enable_php', false);
        $options->set('defaultFont', 'DejaVu Sans');

        // Important: Set DPI for better rendering
        $options->set('dpi', 96);

        if (defined('ABSPATH')) {
            $options->setChroot(realpath(ABSPATH));
        }

        $dompdf = new Dompdf($options);

        $dompdf->loadHtml($complete_html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        /**
         * ----------------------------------------------------
         * 5. Save File
         * ----------------------------------------------------
         */
        $upload_dir = wp_upload_dir();
        $file_name = sanitize_file_name($document_name) . '-' . time() . '.pdf';
        $file_path = trailingslashit($upload_dir['basedir']) . $file_name;

        file_put_contents($file_path, $dompdf->output());

        /**
         * ----------------------------------------------------
         * 6. Insert as Attachment
         * ----------------------------------------------------
         */
        $attachment = [
            'post_mime_type' => 'application/pdf',
            'post_title' => sanitize_text_field($document_name),
            'post_content' => '',
            'post_status' => 'inherit',
        ];

        $attachment_id = wp_insert_attachment($attachment, $file_path);

        if (is_wp_error($attachment_id)) {
            return false;
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';

        wp_update_attachment_metadata(
            $attachment_id,
            wp_generate_attachment_metadata($attachment_id, $file_path)
        );

        /**
         * ----------------------------------------------------
         * 7. Save Entry ID in Attachment Meta
         * ----------------------------------------------------
         */
        if (!empty($entry_id)) {
            update_post_meta($attachment_id, '_nm_letter_related_entry_id', intval($entry_id));
        }

        /**
         * ----------------------------------------------------
         * 8. Return Data
         * ----------------------------------------------------
         */
        return [
            'attachment_id' => $attachment_id,
            'url' => wp_get_attachment_url($attachment_id),
            'path' => $file_path,
            'file_name' => $file_name,
        ];
    }


    /**
     * Get latest PDF attachment for a given entry ID
     *
     * @param int $entry_id
     *
     * @return array|false
     *  [
     *      'attachment_id' => int,
     *      'url'           => string,
     *      'path'          => string,
     *      'file_name'     => string,
     *      'title'         => string,
     *      'date'          => string,
     *  ]
     */
    public static function nm_get_latest_attachment_by_entry_id($entry_id)
    {
        $entry_id = intval($entry_id);

        if (!$entry_id) {
            return false;
        }

        $args = [
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => [
                [
                    'key' => '_nm_letter_related_entry_id',
                    'value' => $entry_id,
                    'compare' => '=',
                ],
            ],
        ];

        $attachments = get_posts($args);

        if (empty($attachments)) {
            return false;
        }

        $attachment = $attachments[0];
        $file_path = get_attached_file($attachment->ID);

        return [
            'attachment_id' => $attachment->ID,
            'url' => wp_get_attachment_url($attachment->ID),
            'path' => $file_path,
            'file_name' => basename($file_path),
            'title' => get_the_title($attachment->ID),
            'date' => get_the_date('Y-m-d H:i:s', $attachment->ID),
        ];
    }

    public static function nm_get_entries_by_status($status, $last_days = null)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'nm_entries';
        $where = [];
        $params = [];

        // Status filter
        if (!empty($status)) {
            $where[] = "entry_status LIKE %s";
            $params[] = '%' . sanitize_text_field($status) . '%';
        }

        // Date filter
        if (!empty($last_days) && is_numeric($last_days)) {
            $where[] = "created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)";
            $params[] = (int) $last_days;
        }

        $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $query = "
        SELECT DISTINCT entry_id, entry_status
        FROM {$table}
        {$where_sql}
        ORDER BY entry_id DESC
    ";

        $results = $wpdb->get_results(
            $wpdb->prepare($query, $params),
            ARRAY_A
        );

        if ($wpdb->last_error) {
            return false;
        }

        return [
            'count' => count($results),
            'data' => $results,
        ];
    }



    /**
     * Check whether all files associated with an entry are approved
     *
     * @param int $entry_id
     * @return bool True if all files are approved, false otherwise
     */
    public static function nm_are_all_entry_files_approved($entry_id)
    {
        if (empty($entry_id)) {
            return false;
        }

        $document_list = self::get_file_fields_from_entry($entry_id);

        if (empty($document_list)) {
            return true; // No documents found
        }

        foreach ($document_list as $list) {
            if (empty($list['files'])) {
                continue; // A field exists but has no files
            }

            foreach ($list['files'] as $file) {
                if (empty($file['attachment_id'])) {
                    return false;
                }

                // Fetch attachment status
                $status = get_post_meta(
                    $file['attachment_id'],
                    '_nm_attachment_status',
                    true
                );

                /**
                 * Strict validation:
                 * - Status must exist
                 * - Status must be approved (1)
                 */
                if ($status === '' || $status === null) {
                    return false; // Missing status
                }

                // if ((int) $status !== 1) {
                //     return false; // Not approved
                // }
            }
        }

        return true;
    }



}
