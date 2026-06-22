<?php


require_once NM_APPS_PATH . 'includes/class-nm-helpers.php';

$user_id = get_current_user_id();

$user_role = NM_Helpers::check_user_roles($user_id);

$available_forms_id = [];

switch ($user_role) {
    case 'admin-only':
        $available_forms_id = NM_Helpers::fetch_all_application_forms();
        break;

    case 'nm-manager-only':
        $available_forms_id = NM_Helpers::get_available_applications_by_user($user_id);
        break;

    case 'both':
        $available_forms_id = NM_Helpers::fetch_all_application_forms();
        break;

    default:
        $available_forms_id = [];
        break;
}

$available_forms = [];

if (!empty($available_forms_id)) {
    foreach ($available_forms_id as $key => $form_id) {
        ?>
<h4 formid="<?php echo $form_id; ?>"><?php echo get_the_title($key); ?></h4>
<?php
    }
}