<?php
if (!defined('ABSPATH')) {
    exit;
}
$allowed_list = NM_Helpers::tb_get_user_allowed_forms();

if ($allowed_list && is_array($allowed_list)) {
    $form_name = $allowed_list;
} else {
    wp_redirect(home_url());
    exit;
}

$agency_id = isset($_GET['agency-id']) && !empty($_GET['agency-id']) ? $_GET['agency-id'] : "";
$user_id = isset($_GET['user-id']) && !empty($_GET['user-id']) ? $_GET['user-id'] : "";
$$is_opre_manager = false;

if ((empty($agency_id) && empty($user_id)) || !is_user_logged_in()) {
    wp_redirect(home_url());
    exit;
} else {
    if (!empty($agency_id)) {
        $agency_details = get_post($agency_id);

        if ($agency_details && $agency_details->post_type !== 'agencies') {
            wp_redirect(home_url());
            exit;
        }
    } else if (!empty($user_id)) {
        $user_data = get_user_by('id', $user_id);
        if (!$user_data || !isset($user_data->roles)) {
            wp_redirect(home_url());
            exit;
        } else {
            $allowed_list = NM_Helpers::tb_get_user_allowed_forms();
            $is_opre_manager = array_key_exists(29, $allowed_list);
            if (!$is_opre_manager) {
                wp_redirect(home_url());
                exit;
            }
        }
    } else {
        wp_redirect(home_url());
        exit;
    }

}
$entries_list = [];
if ($is_opre_manager) {
    $entries_list = NM_Helpers::get_formidable_entries_by_agency_ids($form_name, 0, 1, 4, $user_id);
} else {
    $entries_list = NM_Helpers::get_formidable_entries_by_agency_ids($form_name, $agency_id, 1, 5);
}
$currentuser_id = get_current_user_id();

$agency_detailed_content = NM_Helpers::get_agency_details_by_its_id($agency_id);
if (empty($agency_detailed_content)) {
    $agency_detailed_content = AgencyDetailsDisplay::get_current_user_details($user_id);
}
$current_user_logs = NM_Formidable_Entry_Logger::get_entry_logs_by_user($currentuser_id);
$user_data_currentuser = get_user_by('id', $currentuser_id);

if (!$user_data_currentuser) {
    return "USER DOES NOT EXIST!!";
}

$roles = $user_data_currentuser->roles;

$issite_admin = in_array('administrator', $roles, true);
$issite_nm_manager = in_array('supervisor', $roles, true);

?>

<div class="container">
    <a href="/dashboard?restore=true" class="btn-solid-green" id="nm_back_filter_ar">
        ← Back to Dashboard
    </a>

    <h1 class="title"><?php echo $agency_detailed_content['agency_name'] ?? $agency_detailed_content['username']; ?>
    </h1>

    <div class="nm-grid-wrapper">

        <main class="main-content">

            <div class="card main-card">
                <div class=" table-container custom-table-container" id="nm_table_container_wrapper">

                    <table class="application-table">
                        <thead>
                            <tr>
                                <th>Applications</th>
                                <th>Status</th>

                                <?php if (!$is_opre_manager) {
                                    ?>
                                    <th>Type</th>
                                    <th>Date Submitted</th>
                                    <th>Due Date</th>
                                    <th>Days Left</th>
                                    <?php
                                } else {
                                    ?>
                                    <th>Username</th>
                                    <th>Approval/Denial Date</th>
                                    <?php
                                } ?>

                            </tr>
                        </thead>
                        <tbody id="nmtableBody">
                            <?php foreach ($entries_list['entries'] as $entry) {
                                $agency_id = $entry['fields']['agency_id'];

                                $agency_details = NM_Helpers::get_agency_details_by_its_id($agency_id);
                                $status_color = NM_Helpers::nm_status_class_identifier((isset($entry['document_details']) && !empty($entry['document_details'])) ? $entry['document_details']['document_status'] : "None");
                                $status_color_opre = NM_Helpers::nm_status_class_identifier((isset($entry['entry_approval_staus']) && !empty($entry['entry_approval_staus'])) ? $entry['entry_approval_staus'] : "None");
                                if (empty($agency_details)) {
                                    $agency_details = AgencyDetailsDisplay::get_current_user_details($agency_id);
                                    if (empty($agency_details)) {
                                        continue;
                                    }
                                }
                                $entry_status = 'N/A';
                                if (isset($entry['document_details']) && !empty($entry['document_details']) && isset($entry['document_details']['document_status'])) {
                                    $entry_status = $entry['document_details']['document_status'];
                                } else {
                                    $entry_status = 'None';
                                }
                                $days_left = 'N/A';
                                $entry_due_left = false;
                                if (!((strtolower($entry_status) == 'approve' || strtolower($entry_status) == 'approved') || (strtolower($entry_status) == 'deny' || strtolower($entry_status) == 'denied'))) {
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
                                        <td><span
                                                class="badge <?php echo $status_color_opre; ?>"><?php echo $entry['entry_approval_staus']; ?></span>
                                        </td>
                                    <?php } ?>
                                    <?php if (!$is_opre_manager) {
                                        ?>

                                        <td><?php echo strtoupper($entry['form_key']); ?></td>
                                        <td><?php echo $entry['created_at']; ?></td>

                                        <td><?php echo $entry['due_date']; ?></td>
                                        <td> <?php echo $days_left; ?>
                                        </td>
                                        <?php
                                    } else {
                                        ?>
                                        <td><?php echo $agency_details['username']; ?></td>
                                        <td> <?php echo $entry['entry_approvaldeny_date']; ?></td>
                                        <?php
                                    }
                                    ?>

                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                    <div class="loader_nm hide_this_loader_nm" id="nm_loader_wrapper">
                        <img src="<?php echo NM_APPS_URL . '/assets/Loading.gif'; ?>" alt="">
                    </div>
                </div>
                <?php if (intval($entries_list['pagination']['total_pages']) > 0) { ?>
                    <nav class="pagination-container agency-dashbooard-nm" aria-label="Page navigation">
                        <ul class="pagination" id="pagination_nm" data-type="agency_details">
                            <li class="page-item <?php echo intval($entries_list['pagination']['total_pages']) <= 1 ? "nm_disabled" : ""; ?>"
                                page-id="0"><a href="#">&laquo;</a></li>
                            <?php for ($i = 1; $i <= intval($entries_list['pagination']['total_pages']); $i++) {
                                ?>
                                <li class="page-item <?php echo ($i == 1) ? 'active' : ''; ?>" page-id="<?php echo $i; ?>"><a
                                        href="#"><?php echo $i; ?></a>
                                </li>
                                <?php
                            }
                            ?>
                            <li class="page-item <?php echo intval($entries_list['pagination']['total_pages']) <= 1 ? "nm_disabled" : ""; ?>"
                                page-id="2"><a href="#">&raquo;</a></li>
                        </ul>
                    </nav>
                <?php } ?>

                <hr class="divider table-divider">
                <div id="nm_entry_details_wrapper">

                    <?php

                    if (!empty($entries_list['entries'])) {
                        $entry_id = $entries_list['entries'][0]['entry_id'];
                        $entry_created_at = $entries_list['entries'][0]['created_at'];
                        include NM_APPS_PATH . '/dashboard/components/nm-entry-details-mockup.php';

                    }

                    ?>
                </div>

        </main>
        <aside class="sidebar">

            <div class="sidebar-card" id="nm_agency_contact_card">
                <?php $entry_id = !empty($entries_list['entries'][0]['entry_id']) ? $entries_list['entries'][0]['entry_id'] : null; ?>
                <?php include NM_APPS_PATH . '/dashboard/components/nm-sidebar-contact-card.php'; ?>
            </div>

            <div class="sidebar-card" id="nm_notes_log_card">
                <?php include NM_APPS_PATH . '/dashboard/components/nm-notes-log.php'; ?>
            </div>

            <?php if ($issite_admin || $issite_nm_manager) {
                ?>
                <div class="sidebar-card">
                    <h2 class="blue-header">BHSD ACTIVITY LOG</h2>
                    <div class="timeline">


                        <?php if ($current_user_logs && !(empty($current_user_logs))) {
                            foreach ($current_user_logs as $log) {
                                $log_user = get_user_by('id', $log['user_id']);
                                $username = ($log_user) ? $log_user->display_name : "N/A";
                                $field_type = NM_Helpers::get_formidable_field_type($log['field_id']);
                                if ($field_type == 'hidden' || empty($log['old_value'])) {
                                    continue;
                                }
                                ?>
                                <div class="timeline-item">
                                    <div class="dot green"></div>
                                    <div class="content">
                                        <p class="log-date-top"><i class="fa-regular fa-clock"></i>
                                            <?php echo $log['changed_at']; ?></p>
                                        <p class="log-text">
                                            <?php echo '' . $username . ' updated the value of field named ' . $log['field_name'] . ' from ' . $log['old_value'] . ' to ' . $log['new_value'] . ' in the entry with id: <a href="' . site_url() . '/entry-details/?entry-id=' . $log['entry_id'] . '"> ' . $log['entry_id'] . '</a>'; ?>
                                        </p>
                                        <p class="log-status">Status: <span class="blue-text">Updated successfully.</span></p>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            ?>
                            <p>No Entry Exists.</p>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                <?php
            } ?>

            <div class="sidebar-card">
                <h2 class="blue-header">SUPPORT</h2>
                <div class="sidebar-actions">
                    <a id="btn-solid-blue" target="_blank"
                        href="https://8996cd213a.nxcli.io/support-page/?wpsc-section=ticket-list">OPEN TICKETS</a>
                    <a id="btn-solid-green" target="_blank"
                        href="<?php echo site_url() . '/support-page/?wpsc-section=new-ticket'; ?>">CREATE TICKETS</a>
                </div>
                <hr class="divider">
                <!-- <p class="support-email">support@example.com</p> -->
            </div>
        </aside>
    </div>
</div>

<div class="nm_modal_wrapper" id="nm_modal_wrapper" agency-id="<?php echo $agency_id; ?>"
    user-id="<?php echo $user_id; ?>">
    <div class="nm-modal-overlay">
        <div class="nm-modal" role="dialog" aria-modal="true">


            <div class="nm-modal-header">
                <h2>Update Address</h2>
                <button class="nm-modal-close" aria-label="Close" id="close_btns_modal">&times;</button>
            </div>


            <div class="nm-modal-body">
                <?php echo do_shortcode("[formidable id=52]"); ?>
            </div>

        </div>
    </div>
</div>

<div class="nm_modal_wrapper" id="nm_email_modal_wrapper"
    agency-email="<?php echo $agency_detailed_content['agency_email'] ?? $agency_detailed_content['email']; ?>">
    <div class="nm-modal-overlay">
        <div class="nm-modal" role="dialog" aria-modal="true">


            <div class="nm-modal-header">
                <h2>Send Email</h2>
                <button class="nm-modal-close" aria-label="Close" id="close_send_email_nm">&times;</button>
            </div>


            <div class="nm-modal-body">
                <?php echo do_shortcode("[formidable id=53]"); ?>
            </div>

        </div>
    </div>
</div>

<div class="nm_modal_wrapper" id="nm_entrydetails_modal_wrapper">
    <div class="nm-modal-overlay">
        <div class="nm-modal" role="dialog" aria-modal="true">


            <div class="nm-modal-header">
                <h2>View Full Entry</h2>
                <button class="nm-modal-close" aria-label="Close" id="close_send_email_nm">&times;</button>
            </div>


            <div class="nm-modal-body">

            </div>

        </div>
    </div>
</div>

<div class="nm_modal_wrapper" id="nm_confirmation_modal">
    <div class="nm-modal-overlay">
        <div class="nm-modal" role="dialog" aria-modal="true">


            <div class="nm-modal-header">
                <h2>Confirm Status:</h2>
                <button class="nm-modal-close close_confirmation_nm" id="close_nm_modal"
                    aria-label="Close">&times;</button>
            </div>


            <div class="nm-modal-body">
                Are you sure?
            </div>


            <div class="nm-modal-footer">
                <button class="nm-btn nm-btn-secondary close_confirmation_nm" id="close_confirmation_nm">No</button>
                <button class="nm-btn nm-btn-primary" id="proceed_confirmation_nm">Yes</button>
            </div>


        </div>
    </div>
</div>