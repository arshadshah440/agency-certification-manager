<?php
if (!defined('ABSPATH')) {
    exit;
}

class NM_Settings
{
    public static function init()
    {
        add_action('admin_menu', array(__CLASS__, 'add_settings_page'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_media_uploader'));
    }

    public static function add_settings_page()
    {
        add_options_page(
            'NM Applications Settings',
            'NM Applications',
            'manage_options',
            'nm-applications-settings',
            array(__CLASS__, 'render_settings_page')
        );
    }

    public static function register_settings()
    {
        register_setting('nm_applications_settings_group', 'nm_letters_custom_logo');

        add_settings_section(
            'nm_applications_main_section',
            'General Settings',
            null,
            'nm-applications-settings'
        );

        add_settings_field(
            'nm_letters_custom_logo',
            'Custom Logo URL for Letters',
            array(__CLASS__, 'render_logo_field'),
            'nm-applications-settings',
            'nm_applications_main_section'
        );
    }

    public static function render_logo_field()
    {
        $logo_url = get_option('nm_letters_custom_logo');
        ?>
        <input type="text" name="nm_letters_custom_logo" id="nm_letters_custom_logo" value="<?php echo esc_attr($logo_url); ?>" style="width: 400px;" />
        <button type="button" class="button" id="nm_upload_logo_button">Upload Image</button>
        <p class="description">Upload a custom logo to be used in the generated certification letters.</p>
        <?php if ($logo_url) : ?>
            <div style="margin-top: 10px;">
                <img src="<?php echo esc_url($logo_url); ?>" style="max-width: 200px; max-height: 100px;" />
            </div>
        <?php endif; ?>
        <?php
    }

    public static function enqueue_media_uploader($hook)
    {
        if ($hook !== 'settings_page_nm-applications-settings') {
            return;
        }

        wp_enqueue_media();

        wp_add_inline_script('jquery', "
            jQuery(document).ready(function($){
                $('#nm_upload_logo_button').click(function(e) {
                    e.preventDefault();
                    var image_frame;
                    if(image_frame){
                        image_frame.open();
                    }
                    image_frame = wp.media({
                        title: 'Select Logo',
                        multiple : false,
                        library : {
                            type : 'image',
                        }
                    });
                    image_frame.on('close',function() {
                        var selection =  image_frame.state().get('selection').first().toJSON();
                        $('#nm_letters_custom_logo').val(selection.url);
                    });
                    image_frame.on('select',function() {
                        var selection =  image_frame.state().get('selection').first().toJSON();
                        $('#nm_letters_custom_logo').val(selection.url);
                    });
                    image_frame.open();
                });
            });
        ");
    }

    public static function render_settings_page()
    {
        ?>
        <div class="wrap">
            <h1>NM Applications Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('nm_applications_settings_group');
                do_settings_sections('nm-applications-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}
