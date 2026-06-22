<?php
if (!defined('ABSPATH')) {
	exit;
}
require_once NM_APPS_PATH . 'includes/class-nm-helpers.php';
require_once NM_APPS_PATH . 'includes/class-nm-shortcode.php';
require_once NM_APPS_PATH . 'includes/class-nm-create-post-on-register.php';
require_once NM_APPS_PATH . 'includes/class-nm-ajax-calls.php';
require_once NM_APPS_PATH . 'includes/formidable/class-nm-auth-helpers.php';
require_once NM_APPS_PATH . 'includes/formidable/class-nm-applications-forms.php';
require_once NM_APPS_PATH . 'shortcode/class-nm-agency-shortcodes.php';
require_once NM_APPS_PATH . 'includes/logger/class-nm-formidable-logger.php';
require_once NM_APPS_PATH . 'includes/class-nm-settings.php';
require_once NM_APPS_PATH . 'includes/class-nm-agency-notes.php';


class NM_Applications_Init
{

	public static function init()
	{
		// Initialize all modules
		add_action('plugins_loaded', array(__CLASS__, 'load_classes'));
		add_action("init", array(__CLASS__, "load_init_classes"));
		add_action("wp_enqueue_scripts", array(__CLASS__, "enqueue_scripts"));
		add_action('template_redirect', array(__CLASS__, 'private_page_redirects'));
		register_activation_hook(NM_PLUGIN_FILE, array(__CLASS__, 'activation_functions'));
		register_uninstall_hook(NM_PLUGIN_FILE, array(__CLASS__, 'ondelete_functions'));
		add_action('wp_logout', array(__CLASS__, 'redirect_after_logout'));
		// DOMPDF
		if (!class_exists('Dompdf\Dompdf')) {
			require_once WP_CONTENT_DIR . '/plugins/dompdf/autoload.inc.php';
		}


	}

	public static function redirect_after_logout()
	{
		wp_safe_redirect(home_url());
		exit;
	}

	public static function activation_functions()
	{
		global $wpdb;

		// Define the custom table name using the new name
		$table_name = $wpdb->prefix . 'nm_frm_supplementals';
		$charset_collate = $wpdb->get_charset_collate();

		// SQL query to create the table
		$sql = "CREATE TABLE $table_name (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        frm_entry_id BIGINT(20) NOT NULL,
        meta_key VARCHAR(255) NOT NULL,
        meta_value LONGTEXT NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY frm_entry_id (frm_entry_id),
        KEY meta_key (meta_key(191))
    ) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		self::nm_create_entries_table();
		NM_Agency_Notes::create_table();
	}

	public static function nm_create_entries_table()
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'nm_entries';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        entry_id BIGINT UNSIGNED NOT NULL,
        entry_status VARCHAR(50) NOT NULL DEFAULT 'draft',
        is_viewed TINYINT(1) NOT NULL DEFAULT 0,
        html_content LONGTEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY entry_status (entry_status)
    ) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}


	public static function ondelete_functions()
	{
		global $wpdb;

		// Define the custom table name
		$table_name = $wpdb->prefix . 'nm_frm_supplementals';

		// IMPORTANT: Check if the table exists before attempting to drop it
		// and make sure the global $wpdb variable is used for safety.

		// SQL query to drop the table
		$sql = "DROP TABLE IF EXISTS `$table_name`";

		// $wpdb->query runs the SQL command
		$wpdb->query($sql);
	}
	public static function load_classes()
	{
		NM_Helpers::init();
		NM_Shortcode::init();
		NM_AJAX_CALLS::init();
		NM_AUTH_HELPER::init();
		NM_Applications_Controllers::init();
		AgencyDetailsDisplay::init();
		NM_Formidable_Entry_Logger::init();
		NM_Settings::init();
		NM_Agency_Notes::init();
	}

	public static function private_page_redirects()
	{
		self::nm_validate_opre_form();
		// Only run for non-logged-in users
		if (!is_user_logged_in()) {
			global $post;

			if (isset($post)) {
				// Replace 'require_login' with your ACF field name (true/false checkbox)
				$require_login = get_field('show_only_to_loggedin_users', $post->ID);

				if ($require_login) {
					// Redirect to login page, and return to this page after login
					wp_redirect(wp_login_url(get_permalink()));
					exit;
				}
			}
		}


	}
	public static function load_init_classes()
	{
		NM_Create_Post::init();
		self::nm_validate_opre_form();


	}
	public static function nm_validate_opre_form()
	{
		// Run only on the target page
		// Only run on the target page
		if (!is_page('opre-form')) {
			return;
		}


		// Only for logged-in users (needed for user-specific check)
		if (!is_user_logged_in()) {
			wp_redirect(home_url('/opre-logins'));
			exit;
		}

		$user_id = get_current_user_id();

		// Get form by key
		$form = FrmForm::getOne('opre');
		if (!$form) {
			return;
		}

		$is_enabled = get_field('enable_opre_applications', 'option');
		$total_allowed_entries = get_field('total_opre_application_allowed', 'option');



		global $wpdb;

		// Timestamp for last 24 hours
		$last_24_hours = gmdate('Y-m-d H:i:s', strtotime('-24 hours'));

		/**
		 * 1️ Count ALL submitted (non-draft) entries
		 *    regardless of user
		 */
		$total_entries_last_24h = (int) $wpdb->get_var(
			$wpdb->prepare(
				"
            SELECT COUNT(*)
            FROM {$wpdb->prefix}frm_items
            WHERE form_id    = %d
              AND is_draft   = 0
              AND created_at >= %s
            ",
				$form->id,
				$last_24_hours
			)
		);

		/**
		 * 2️Check if CURRENT USER has submitted in last 24 hours
		 */
		$user_has_submitted = (int) $wpdb->get_var(
			$wpdb->prepare(
				"
            SELECT COUNT(*)
            FROM {$wpdb->prefix}frm_items
            WHERE form_id    = %d
              AND user_id    = %d
              AND is_draft   = 0
              AND created_at >= %s
            ",
				$form->id,
				$user_id,
				$last_24_hours
			)
		);

		/**
		 * 3️Redirect current user if they already submitted
		 */
		// If total_allowed_entries is -1, there is no daily cap — skip that check
		$over_daily_limit = ((int) $total_allowed_entries !== -1) && ($total_entries_last_24h >= (int) $total_allowed_entries);

		if ($user_has_submitted > 0 || !$is_enabled || $over_daily_limit) {
			wp_redirect(home_url());
			exit;
		}

	}
	public static function enqueue_scripts()
	{
		// Enqueue CSS
		wp_enqueue_style(
			'theme-style', // Handle name
			NM_APPS_URL . '/assets/css/style.css', // File URL
			array(), // Dependencies
			filemtime(NM_APPS_PATH . '/assets/css/style.css') // Version number
		);

		wp_enqueue_style(
			'theme-style-dashboard', // Handle name
			NM_APPS_URL . 'dashboard/assets/style.css', // File URL
			array(), // Dependencies
			filemtime(NM_APPS_PATH . 'dashboard/assets/css/style.css') // Version number
		);

		wp_enqueue_style(
			'theme-style-dashboard-details', // Handle name
			NM_APPS_URL . 'dashboard/assets/application.css', // File URL
			array(), // Dependencies
			filemtime(NM_APPS_PATH . 'dashboard/assets/css/application.css') // Version number
		);

		wp_enqueue_style(
			'theme-style-flatpickr', // Handle name
			NM_APPS_URL . '/assets/css/flatpickr.css', // File URL
			array(), // Dependencies
			filemtime(NM_APPS_PATH . '/assets/css/flatpickr.css') // Version number
		);

		wp_enqueue_style(
			'theme-style-lucide', // Handle name
			'https://cdn.jsdelivr.net/npm/lucide/dist/lucide.css', // File URL
			array(), // Dependencies
			'1.0.0' // Version number
		);


		wp_enqueue_style(
			'theme-style-font-awesome', // Handle name
			'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', // File URL
			array(), // Dependencies
			'1.0.0' // Version number
		);

		wp_enqueue_style(
			'theme-style-iconoir', // Handle name
			'https://cdn.jsdelivr.net/gh/iconoir-icons/iconoir@main/css/iconoir.css', // File URL
			array(), // Dependencies
			'1.0.0' // Version number
		);
		wp_enqueue_style(
			'theme-style-fonts', // Handle name
			'https://fonts.cdnfonts.com/css/neue-haas-grotesk-display-pro?styles=23457,23459,23461', // File URL
			array(), // Dependencies
			'1.0.0' // Version number
		);
		// Enqueue JS
		wp_enqueue_script(
			'nm-script', // Handle name
			NM_APPS_URL . '/assets/js/nm-scripts.js', // File URL
			array('jquery'), // Dependencies
			filemtime(NM_APPS_PATH . '/assets/js/nm-scripts.js'), // Version number
			true // Load in footer
		);
		wp_enqueue_script(
			'nm-script-html-2df', // Handle name
			'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js', // File URL
			array('jquery'), // Dependencies
			'1.0.0', // Version number
			true // Load in footer
		);
		wp_enqueue_script(
			'nm-script-html-canvas', // Handle name
			'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js', // File URL
			array('jquery'), // Dependencies
			'1.0.0', // Version number
			true // Load in footer
		);
		wp_enqueue_script(
			'nm-script-html-umd', // Handle name
			'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js', // File URL
			array('jquery'), // Dependencies
			'1.0.0', // Version number
			true // Load in footer
		);
wp_enqueue_script(
			'nm-script-flatpickr', // Handle name
			NM_APPS_URL . '/assets/js/flatpickr.js', // File URL
			array('jquery'), // Dependencies
			filemtime(NM_APPS_PATH . '/assets/js/flatpickr.js'), // Version number
			true // Load in footer
		);
		wp_enqueue_script(
			'nm-script-dashboard', // Handle name
			NM_APPS_URL . 'dashboard/assets/js/ui.js', // File URL
			array('jquery'), // Dependencies
			filemtime(NM_APPS_PATH . 'dashboard/assets/js/ui.js'), // Version number
			true // Load in footer
		);
		// localise the ajax url
		wp_localize_script(
			'nm-script',
			'nm_ajax_obj',
			[
				'ajaxurl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('register_email_nonce'),
			]
		);

	}
}