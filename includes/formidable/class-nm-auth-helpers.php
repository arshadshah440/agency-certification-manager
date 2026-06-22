<?php

if(!defined('ABSPATH')){
    exit;
}
/**
 * Create WordPress User from Formidable Forms Submission
 * Add this code to your theme's functions.php file or a custom plugin
 */
require_once NM_APPS_PATH . 'includes/class-nm-create-post-on-register.php';

class NM_AUTH_HELPER
{

    /**
     * Initialize the class and register hooks
     */
    public static function init()
    {
        add_action('frm_after_create_entry', [__CLASS__, 'create_user_from_entry'], 30, 2);
    }

    /**
     * Main function to create WordPress user from Formidable form entry
     * 
     * @param int $entry_id The Formidable entry ID
     * @param int $form_id The Formidable form ID
     */
    public static function create_user_from_entry($entry_id, $form_id)
    {

        // Replace with your actual Formidable form ID
        $target_form_id = 22; // Change this to your form ID

        // Only run for specific form
        if ($form_id != $target_form_id) {
            return;
        }

        // Get form field values
        $user_data = self::get_form_field_values($entry_id);

        // Validate required fields
        if (!self::validate_user_data($user_data)) {
            error_log('Unable to validate' . $user_data['username'] . 'with email' . $user_data['email']);

            return;
        }

        // Check if user already exists
        if (self::user_exists($user_data['username'], $user_data['email'])) {
            error_log('User Exists' . $user_data['username'] . 'with email' . $user_data['email']);
            return;
        }

        // Prepare user data for WordPress
        $userdata = self::prepare_user_data($user_data);

        // Create the user
        $user_id = wp_insert_user($userdata);

        NM_Create_Post::create_post($entry_id,$form_id);

        // Handle user creation result
        self::handle_user_creation_result($user_id, $entry_id);
    }

    /**
     * Get field values from Formidable form entry
     * 
     * @param int $entry_id The entry ID
     * @return array User data from form fields
     */
    private static function get_form_field_values($entry_id)
    {

        // Field IDs
        $job_title = 449;
        $primary_first_name = 447;
        $primary_last_name = 448;
        $primary_email = 1985;
        $user_first_name = 462;
        $user_last_name = 463;
        $user_email = 464;

        // Get job title to determine which fields to use
        $job_title_value = sanitize_text_field(FrmProEntriesController::get_field_value_shortcode([
            'field_id' => $job_title,
            'entry_id' => $entry_id
        ]));

        // Check if job title is one of the executive roles
        $executive_roles = ['Clinical Director', 'Clinical Supervisor', 'CEO'];
        $is_executive = in_array($job_title_value, $executive_roles);

        // Determine which fields to use based on job title
        if ($is_executive) {
            $first_name_field_id = $primary_first_name;
            $last_name_field_id = $primary_last_name;
            $email_field_id = $primary_email;
        } else {
            $first_name_field_id = $user_first_name;
            $last_name_field_id = $user_last_name;
            $email_field_id = $user_email;
        }

        // Get field values
        $first_name = sanitize_text_field(FrmProEntriesController::get_field_value_shortcode([
            'field_id' => $first_name_field_id,
            'entry_id' => $entry_id
        ]));

        $last_name = sanitize_text_field(FrmProEntriesController::get_field_value_shortcode([
            'field_id' => $last_name_field_id,
            'entry_id' => $entry_id
        ]));

        $email = sanitize_email(FrmProEntriesController::get_field_value_shortcode([
            'field_id' => $email_field_id,
            'entry_id' => $entry_id
        ]));

        // Generate username from email (everything before @)
        $username = self::generate_username_from_email($email);

        // Generate a random password
        $password = wp_generate_password(12, true, true);

        return [
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'first_name' => $first_name,
            'last_name' => $last_name
        ];
    }

    /**
     * Generate username from email address
     * 
     * @param string $email Email address
     * @return string Generated username
     */
    private static function generate_username_from_email($email)
    {
        // Get the part before @ symbol
        $username_base = strstr($email, '@', true);

        // Sanitize for WordPress username requirements
        $username = sanitize_user($username_base, true);

        // If username already exists, append a number
        if (username_exists($username)) {
            $counter = 1;
            $new_username = $username . $counter;

            while (username_exists($new_username)) {
                $counter++;
                $new_username = $username . $counter;
            }

            $username = $new_username;
        }

        return $username;
    }

    /**
     * Validate user data
     * 
     * @param array $user_data User data to validate
     * @return bool True if valid, false otherwise
     */
    private static function validate_user_data($user_data)
    {

        if (empty($user_data['username']) || empty($user_data['email'])) {
            self::log_error('Username or email is empty');
            return false;
        }

        if (!is_email($user_data['email'])) {
            self::log_error('Invalid email format: ' . $user_data['email']);
            return false;
        }

        return true;
    }

    /**
     * Check if user already exists
     * 
     * @param string $username Username to check
     * @param string $email Email to check
     * @return bool True if user exists, false otherwise
     */
    private static function user_exists($username, $email)
    {

        if (username_exists($username)) {
            self::log_error('Username already exists: ' . $username);
            return true;
        }

        if (email_exists($email)) {
            self::log_error('Email already exists: ' . $email);
            return true;
        }

        return false;
    }

    /**
     * Prepare user data for wp_insert_user
     * 
     * @param array $user_data Raw user data from form
     * @return array Prepared user data
     */
    private static function prepare_user_data($user_data)
    {

        return [
            'user_login' => $user_data['username'],
            'user_email' => $user_data['email'],
            'user_pass' => $user_data['password'],
            'first_name' => $user_data['first_name'],
            'last_name' => $user_data['last_name'],
            'role' => self::get_user_role(), // Change role as needed
        ];
    }

    /**
     * Get the user role for new users
     * 
     * @return string User role
     */
    private static function get_user_role()
    {
        // Change this to: subscriber, contributor, author, editor, administrator
        return 'agency';
    }

    /**
     * Handle user creation result
     * 
     * @param int|WP_Error $user_id User ID or WP_Error object
     * @param int $entry_id Form entry ID
     */
    private static function handle_user_creation_result($user_id, $entry_id)
    {

        // Check for errors
        if (is_wp_error($user_id)) {
            self::log_error('User creation failed: ' . $user_id->get_error_message());
            return;
        }

        // Store the user ID in the form entry as meta data
        self::store_user_id_in_entry($entry_id, $user_id);

        // Send new user notification email
        self::send_user_notification($user_id);

        // Optional: Auto-login the user
        // self::auto_login_user($user_id);

        // Log success
        self::log_success($user_id);
    }

    /**
     * Store user ID in form entry meta
     * 
     * @param int $entry_id Form entry ID
     * @param int $user_id WordPress user ID
     */
    private static function store_user_id_in_entry($entry_id, $user_id)
    {
        update_post_meta($entry_id, 'created_user_id', $user_id);
    }

    /**
     * Send new user notification email
     * 
     * @param int $user_id User ID
     */
    private static function send_user_notification($user_id)
    {
        // Options: 'both', 'admin', 'user', or 'none'
        wp_new_user_notification($user_id, null, 'both');
    }

    /**
     * Auto-login user after registration
     * 
     * @param int $user_id User ID
     */
    private static function auto_login_user($user_id)
    {
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
    }

    /**
     * Log error message
     * 
     * @param string $message Error message
     */
    private static function log_error($message)
    {
        error_log('Formidable User Creation Error: ' . $message);
    }

    /**
     * Log success message
     * 
     * @param int $user_id User ID
     */
    private static function log_success($user_id)
    {
        error_log('WordPress user created successfully from Formidable form. User ID: ' . $user_id);
    }
}

