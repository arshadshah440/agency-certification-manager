<?php
if (!defined('ABSPATH')) {
    exit;
}


class AgencyDetailsDisplay
{

    /**
     * Initialize the shortcode
     */
    public static function init()
    {
        add_shortcode('agency_details', [self::class, 'render_shortcode']);
        add_shortcode('agency_contact_email', [self::class, 'render_email_shortcode']);
        add_shortcode('cst_agency_id', [self::class, 'render_agency_id']);
        add_shortcode('dashboard_agency_render', [self::class, 'dashboard_agency_render']);
        add_shortcode('agency_detail_render', [self::class, 'agency_detail_render']);
        add_shortcode('current_user_details', [self::class, 'render_current_user_details_html']);
        add_shortcode('currentuser_contact_email', [self::class, 'currentuser_contact_email']);

    }

    public static function currentuser_contact_email()
    {
        $user = self::get_current_user_details();
        return $user['email'];
    }
    public static function get_current_user_details($user_id = 0)
    {

        // Default response
        $user_data = array(
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'username' => '',
            'name' => '',
            'user_address' => '',
            'cell_phone' => '',
            'home_phone' => '',
        );

        // Ensure user is logged in
        if (!is_user_logged_in() && $user_id == 0) {
            return $user_data;
        }

        $current_user = ($user_id == 0)
            ? wp_get_current_user()
            : get_user_by('id', (int) $user_id);

        if (!$current_user || !$current_user->ID) {
            return $user_data;
        }

        $user_id = $current_user->ID;

        // Core user fields
        $user_data['first_name'] = $current_user->first_name;
        $user_data['last_name'] = $current_user->last_name;
        $user_data['name'] = $current_user->first_name . ' ' . $current_user->last_name;
        $user_data['username'] = $current_user->user_login;
        $user_data['email'] = $current_user->user_email;

        // ACF user fields
        if (function_exists('get_field')) {
            $user_data['user_address'] = get_field('user_address', 'user_' . $user_id);
            $user_data['cell_phone'] = get_field('cell_phone', 'user_' . $user_id) ?? get_user_meta($user_id,'opre_details_cell_phone',true);
            $user_data['home_phone'] = get_field('home_phone', 'user_' . $user_id) ?? get_user_meta( $user_id, 'opre_details_home_phone', true );
        }

        return $user_data;
    }

    public static function render_current_user_details_html()
    {

        $user = self::get_current_user_details();

        if (empty($user['email'])) {
            return '<p class="user-details__not-logged-in">User not logged in.</p>';
        }

        ob_start();
        ?>
        <div class="user-details">
            <h3 class="user-details__title"><?php esc_html_e('User Details', 'textdomain'); ?></h3>

            <ul class="user-details__list">
                <li>
                    <strong><?php esc_html_e('Name:', 'textdomain'); ?></strong>
                    <?php echo esc_html(trim($user['first_name'] . ' ' . $user['last_name'])); ?>
                </li>

                <li>
                    <strong><?php esc_html_e('Email:', 'textdomain'); ?></strong>
                    <a href="mailto:<?php echo esc_attr($user['email']); ?>">
                        <?php echo esc_html($user['email']); ?>
                    </a>
                </li>

                <?php if (!empty($user['user_address'])): ?>
                    <li>
                        <strong><?php esc_html_e('Address:', 'textdomain'); ?></strong>
                        <?php echo wp_kses_post($user['user_address']); ?>
                    </li>
                <?php endif; ?>

                <?php if (!empty($user['cell_phone'])): ?>
                    <li>
                        <strong><?php esc_html_e('Cell Phone:', 'textdomain'); ?></strong>
                        <?php echo esc_html($user['cell_phone']); ?>
                    </li>
                <?php endif; ?>

                <?php if (!empty($user['home_phone'])): ?>
                    <li>
                        <strong><?php esc_html_e('Home Phone:', 'textdomain'); ?></strong>
                        <?php echo esc_html($user['home_phone']); ?>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
        <?php

        return ob_get_clean();
    }
    public static function dashboard_agency_render($atts)
    {
        ob_start();

        include NM_APPS_PATH . 'dashboard/pages/overview-dashboard.php';

        return ob_get_clean();

    }

    public static function agency_detail_render($atts)
    {
        ob_start();

        include NM_APPS_PATH . 'dashboard/pages/agency-dashboard.php';

        return ob_get_clean();
    }

    public static function render_agency_id($atts)
    {
        $entry_id = isset($_GET['entry']) ? intval($_GET['entry']) : 0;
        if (!$entry_id) {
            return '<p>No entry ID provided.</p>';
        }

        // Get agency details from existing function
        $agency_data = self::get_agency_details($entry_id);
        return $agency_data['agency_id'];
    }
    public static function render_email_shortcode($atts)
    {
        $entry_id = isset($_GET['entry']) ? intval($_GET['entry']) : 0;
        if (!$entry_id) {
            return '<p>No entry ID provided.</p>';
        }

        // Get agency details from existing function
        $agency_data = self::get_agency_details($entry_id);
        return $agency_data['primary_contact_email'];
    }

    /**
     * Shortcode handler
     */
    public static function render_shortcode($atts)
    {
        // Get entry ID from URL ?entry_id=123
        $entry_id = isset($_GET['entry']) ? intval($_GET['entry']) : 0;
        if (!$entry_id) {
            return '<p>No entry ID provided.</p>';
        }

        // Get agency details from existing function
        $agency_data = self::get_agency_details($entry_id);
        if (!$agency_data) {
            return '<p>No agency details found for this entry.</p>';
        }

        // Generate HTML
        return self::generate_html($agency_data);
    }

    /**
     * Fetch agency details
     */
    public static function get_agency_details($first_entry_id)
    {
        if (!class_exists('FrmEntryMeta'))
            return false;

        // Get location type from Formidable
        $location_type = FrmEntryMeta::get_entry_meta_by_field($first_entry_id, 894);

        $user_id = get_current_user_id();
        if (!$user_id) {
            error_log("User not logged in");
            return false;
        }

        $user = get_userdata($user_id);

        $agency_posts = get_posts([
            'post_type' => 'agencies',
            'numberposts' => 1,
            'fields' => 'ids',
            'author' => $user_id,
        ]);

        if (empty($agency_posts))
            return false;

        $agency_id = $agency_posts[0];
        $sub_locations = get_field('agency_sub_locations', $agency_id);

        $latest_sub_location = [];

        if ($location_type !== 'New provider' && is_array($sub_locations) && !empty($sub_locations)) {

            $locations = $sub_locations[count($sub_locations) - 1];

            // Clean address fields from HTML
            $physical = isset($locations['physical_address'])
                ? wp_strip_all_tags($locations['physical_address'])
                : '';

            $latest_sub_location = [
                'physical_address' => $physical,
                'location_name' => $locations['location_name'] ?? '',
                'locations_npi' => $locations['locations_npi'] ?? '',
            ];

            if (
                isset($locations['is_your_mailing_address_the_same_as_your_physical_address']) &&
                $locations['is_your_mailing_address_the_same_as_your_physical_address'] === 'No' && (
                    !empty($locations['mailing_address']) && wp_strip_all_tags($locations['mailing_address'])[0] != ",")
            ) {
                $latest_sub_location['mailing_address'] = trim(wp_strip_all_tags($locations['mailing_address']));
            }
        }


        error_log('sub_locationss' . print_r($latest_sub_location, true));
        $data = [
            'agency_id' => $agency_id,
            'location_type' => $location_type,
            'agency_name' => get_field('agency_name', $agency_id),
            'agency_dba' => get_field('dba', $agency_id),
            'parent_physical_address' => self::format_acf_address_to_formidable($agency_id, 'physical_address'),
            'primary_contact_email' => get_field('email', $agency_id),
            'primary_contact_name' => $user->display_name,
            'primary_contact_phone' => get_field('phone', $agency_id),
            'agency_medicaid_enrollment_id' => get_field('agency_medicaid_enrollment_id', $agency_id),
            'agency_group_npi' => get_field('agency_group_npi', $agency_id),
            'agency_new_locations' => $latest_sub_location ?? $sub_locations,
        ];

        return $data;
    }

    /**
     * Generate HTML from agency details array
     */
    public static function generate_html($agency_data, $wrapper_class = 'agency-details')
    {
        if (empty($agency_data) || !is_array($agency_data))
            return '';

        $html = '<div class="' . esc_attr($wrapper_class) . '">';

        foreach ($agency_data as $label => $value) {
            if ($value === null || $value === '' || $label === 'agency_id' || $label === 'location_type')
                continue;

            $formatted_label = ucwords(str_replace('_', ' ', $label));

            $html .= '<div class="field" style="margin-bottom:10px;">';
            $html .= '<strong class="label">' . esc_html($formatted_label) . ':</strong> ';

            if (is_array($value)) {
                // Handle repeater/sub-locations
                $html .= '<div class="sub-values">';
                foreach ($value as $sb_label => $sub_item) {
                    $html .= '<div class="sub-item" style="margin-bottom:8px; border-left:2px solid #ccc; padding-left:10px;">';
                    $sub_label_formatted = ucwords(str_replace('_', ' ', $sb_label));
                    if (is_array($sub_item)) {

                        foreach ($sub_item as $sub_label => $sub_value) {
                            error_log("Lables : $label , values : $sub_value");
                            // If the value contains HTML (like address), output raw
                            if (is_string($sub_value) && strpos($sub_value, '<div') === 0) {
                                $html .= '<div><strong>' . esc_html($sub_label_formatted) . ':</strong> ' . $sub_value . '</div>';
                            } else {
                                $html .= '<div><strong>' . esc_html($sub_label_formatted) . ':</strong> ' . esc_html($sub_value) . '</div>';
                            }
                        }
                    } else {
                        $html .= '<div><strong class="label">' . esc_html($sub_label_formatted) . ':</strong> ';

                        $html .= '<div>' . $sub_item . '</div></div>';
                    }
                    $html .= '</div>'; // .sub-item
                }
                $html .= '</div>'; // .sub-values
            } else {
                // For normal field, if it's HTML (like ACF address), output raw
                if (is_string($value) && strpos($value, '<div') === 0) {
                    $html .= $value;
                } else {
                    $html .= '<span class="value">' . esc_html($value) . '</span>';
                }
            }

            $html .= '</div>'; // .field
        }

        $html .= '</div>'; // wrapper

        return $html;
    }


    /**
     * Placeholder for formatting ACF addresses to Formidable format
     */
    public static function format_acf_address_to_formidable($post_id, $field_name)
    {
        $address = get_field($field_name, $post_id);
        if (is_array($address)) {
            return implode(', ', array_filter($address)); // simple comma-separated
        }
        return $address;
    }
}

// Initialize shortcode
AgencyDetailsDisplay::init();