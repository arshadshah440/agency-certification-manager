<?php
$user_id = get_current_user_id();
$user_data = get_user_by('id', $user_id);

if (!$user_data) {
    return "USER DOES NOT EXIST!!";
}

$roles = $user_data->roles;

$issite_admin = in_array('administrator', $roles, true);
$issite_nm_manager = in_array('supervisor', $roles, true);
$approvallogviewers = get_field('approvedeny_log_viewers', 'option') ?? [];

// $forms_arrays = [];

// if ($has_nm_manager || $has_admin) {
//     $forms_arrays = get_field('select_the_application_form_for_this_company', 'user_' . $user_id) ?: [];
// } else { // neither admin nor nm_manager
//     wp_redirect(home_url());
//     exit;
// }

// $form_name = [];
// // Debugging
// if (class_exists('FrmForm')) {
//     foreach ($forms_arrays as $forms_array) {
//         $form = FrmForm::getOne($forms_array);
//         $form_name[$forms_array] = $form;
//     }

// }

$form_name = [];


$allowed_list = NM_Helpers::tb_get_user_allowed_forms();
// var_dump($allowed_list);
if ($allowed_list && is_array($allowed_list)) {
    $form_name = $allowed_list;
} else {
    wp_redirect(home_url());
    exit;
}


$entries_list = NM_Helpers::get_formidable_entries_by_form_ids($form_name, 1, 10);
// var_dump($entries_list['entries']);
$is_opre_manager = array_key_exists(29, $form_name);
$recent_logs = NM_Formidable_Entry_Logger::get_recent_logs(5);
$current_user_logs = NM_Formidable_Entry_Logger::get_entry_logs_by_user($user_id);
$approved_applications = NM_Helpers::nm_get_entries_by_status('approve', 30);
$denied_applications = NM_Helpers::nm_get_entries_by_status('deny', 30);

// echo "<pre>";
// print_r($recent_logs);
// echo "</pre>";
// var_dump($entries_list['pagination']['total_pages']);
?>

<div class="container">
    <h1 class="title">WELCOME, [<?php echo $user_data->display_name; ?>]</h1>

    <div class="grid">

        <aside class="sidebar">
            <div class="card status-card">
                <h2>Status of Applications</h2>
                <div class="applications">
                    <div class="status-item custom-status-list">
                        <div>
                            <p class="label">Applications Recorded</p>
                            <p class="status-sub">Since last 30 days</p>
                        </div>
                        <p class="number"><?php echo NM_Helpers::get_total_entry_of_all_avail_forms($form_name); ?></p>
                    </div>
                    <div class="status-list">
                        <div class="status-item success">
                            <div>
                                <p class="status-label">Approved</p>
                                <p class="status-sub">Since last 30 days</p>
                            </div>
                            <p class="status-number">
                                <?php if (!$is_opre_manager) { ?>
                                    <?php echo NM_Helpers::get_total_entry_of_all_avail_forms($form_name, 'approve'); ?>

                                <?php } else {
                                    ?>
                                    <?php echo NM_Helpers::nm_get_entry_count_by_status('approved')['count']; ?>

                                    <?php
                                } ?>
                            </p>
                        </div>
                        <div class="status-item info">
                            <div>
                                <p class="status-label">Acknowledged </p>
                                <p class="status-sub">Since last 30 days</p>
                            </div>
                            <p class="status-number">
                                <?php echo NM_Helpers::get_total_entry_of_all_avail_forms($form_name, 'acknowledged'); ?>
                            </p>
                        </div>
                        <div class="status-item destructive">
                            <div>
                                <p class="status-label">Denied</p>
                                <p class="status-sub">Since last 30 days</p>
                            </div>
                            <p class="status-number">
                                <?php if (!$is_opre_manager) { ?>
                                    <?php echo NM_Helpers::get_total_entry_of_all_avail_forms($form_name, 'denied'); ?>

                                <?php } else {
                                    ?>
                                    <?php echo NM_Helpers::nm_get_entry_count_by_status('denied')['count']; ?>

                                    <?php
                                } ?>
                            </p>
                        </div>
                        <div class="status-item warning">
                            <div>
                                <p class="status-label">Submitted</p>
                                <p class="status-sub">Since last 30 days</p>
                            </div>
                            <p class="status-number">
                                <?php echo NM_Helpers::get_total_entry_of_all_avail_forms($form_name, 'submitted'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>


            <?php if ($issite_admin || $issite_nm_manager) {
                ?>

                <div class="sidebar-card" id="nm_bhsd_logs">
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


            <?php if (in_array($user_id, $approvallogviewers)) {
                ?>

                <div class="sidebar-card" id="nm_bhsd_logs">
                    <h2 class="blue-header">Approve/Deny LOG</h2>
                    <div class="timeline">


                        <?php echo do_shortcode('[nm_entries_last_30_days]'); ?>

                    </div>
                </div>
            <?php }
            ?>


            <div class="sidebar-card" id="nm_bhsd_documentation">
                <h2 class="blue-header">How to use this dashboard to process applications</h2>
                <div class="timeline">
                    <?php if ($issite_nm_manager) {
                        ?>
                        <a href="<?php echo home_url('/docs/supervisors'); ?>" target="_blank">View Supervisor Guides &
                            FAQs</a>
                        <?php
                    } else if ($issite_admin) {
                        ?>
                            <a href="<?php echo home_url('/docs/supervisors'); ?>" target="_blank">View Supervisor Guides &
                                FAQs</a>
                            <a href="<?php echo home_url('/docs/documents'); ?>" target="_blank">View Staff Guides & FAQs</a>
                        <?php
                    } else {
                        ?>
                            <a href="<?php echo home_url('/docs/documents'); ?>" target="_blank">View Staff Guides & FAQs</a>
                        <?php
                    } ?>
                </div>
            </div>

        </aside>

        <main class="main-content">
            <div class="card main-card">



                <div class="tabs" id="nm-dashboard-tabs">
                    <button class="nmtab active" form-id="-1"><i class="fa-solid fa-eye"></i> View
                        All</button>
                    <?php
                    foreach ($form_name as $key => $value) {
                        ?>
                        <button class="nmtab"
                            form-id="<?php echo $key; ?>"><?php echo strtoupper($value->form_key); ?></button>
                        <?php
                    }
                    ?>
                </div>

                <div class="providers-list">
                    <h2>Providers Lists</h2>
                    <div class="nm_secondary_filters">
                        <div class="search_nm_input_filters">
                            <input type="text" id="nm_search_input" placeholder="Search...">
                            <img src="<?php echo NM_APPS_URL . '/assets/search.svg'; ?>" alt="">
                        </div>
                        <button id="nm_exp_to_csv">Export To CSV</button>

                    </div>
                </div>

                <div class="filters">
                    <div class="filter">
                        <i class="iconoir iconoir-filter-alt"></i>
                    </div>

                    <div class="select-wrapper custom-wrapper">
                        <select id="sortSelect">
                            <option value="DESC">Most Recent</option>
                            <option value="ASC">Oldest</option>
                        </select>
                    </div>

                    <?php if (!$is_opre_manager) { ?>

                        <div class="select-wrapper">
                            <select id="statusSelect">
                                <option value="">All Status</option>
                                <option value="approved">Approved</option>
                                <option value="signed">Acknowledged</option>
                                <option value="denied">Denied</option>
                                <option value="awaiting">Submitted</option>
                            </select>
                        </div>
                    <?php }
                    ; ?>
                    <div></div>

                    <!-- <div class="select-wrapper">
                        <select id="typeSelect">
                            <option value="all">All Types</option>
                            <option value="CCSS">CCSS</option>
                            <option value="AARTC">AARTC</option>
                            <option value="ACT">ACT</option>
                            <option value="CCBHC">CCBHC</option>
                            <option value="IOP">IOP</option>
                            <option value="MCT">MCT</option>
                            <option value="OPRE">OPRE</option>
                            <option value="PSR">PSR</option>
                        </select>
                    </div> -->

                    <div class="date-range-filter-container custom-wrapper">
                        <button id="dateRangeToggle" class="filter-dropdown-toggle select-wrapper active">
                            <span id="dateRangeDisplay">Date Range</span>
                            <!-- <span class="arrow-icon"> &#x2304</span> -->
                        </button>
                        <input type="text" id="date_range" class="nm-opacity-0" placeholder="Select date range" />
                    </div>
                    <button class="reset" id="nmresetFilters">
                        <i class="fa-solid fa-rotate-left"></i> Reset Filter
                    </button>
                </div>

                <div class="table-container" id="nm_table_container_wrapper">
                    <table id="applicationTable">
                        <thead>
                            <tr>
                                <th> <?php echo $is_opre_manager ? 'User' : 'Agency'; ?></th>
                                <?php echo !$is_opre_manager ? '<th>Type</th>' : ''; ?>

                                <th>Contact Name</th>
                                <th>Email</th>
                                <?php echo $is_opre_manager ? '<th>Phone Number</th>' : ''; ?>
                                <th>Date Submitted</th>
                                <th>Status</th>

                                <?php if (!$is_opre_manager) {
                                    ?>
                                    <th>Days Left</th>
                                    <?php
                                } ?>
                                <?php echo $is_opre_manager ? '<th>Approval/Denial Date </th>' : ''; ?>


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
                                $entry_status = 'Submitted';
                                if (isset($entry['document_details']) && !empty($entry['document_details']) && isset($entry['document_details']['document_status'])) {
                                    $entry_status = $entry['document_details']['document_status'] ?? 'Submitted';
                                } else {
                                    $entry_status = 'None';
                                }
                                $days_left = 'None';
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
                                    <td><a href="<?php
                                    echo site_url() . '/entry-details/?' .
                                        ($is_opre_manager ? 'user-id' : 'agency-id') .
                                        '=' . $agency_id;
                                    ?>">
                                            <?php echo $agency_details['agency_name'] ?? $agency_details['username']; ?>
                                        </a>

                                    </td>
                                    <?php echo !$is_opre_manager ? '<td>' . strtoupper($entry['form_name']) . ' </td>' : ''; ?>
                                    <td><?php echo $agency_details['contact_name'] ?? $agency_details['name']; ?></td>
                                    <td><?php echo $agency_details['agency_email'] ?? $agency_details['email']; ?></td>
                                    <?php echo $is_opre_manager ? '<td>' . $agency_details['cell_phone'] . '</td>' : ''; ?>

                                    <td><?php echo $is_opre_manager ? $entry['create_at_with_time'] : $entry['created_at']; ?>
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
                                        <td> <?php echo $days_left; ?>
                                        </td>
                                        <?php
                                    } ?>
                                    <?php echo $is_opre_manager ? '<td>' . $entry['entry_approvaldeny_date'] . '</td>' : ''; ?>


                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                    <div class="loader_nm hide_this_loader_nm" id="nm_loader_wrapper">
                        <img src="<?php echo NM_APPS_URL . '/assets/Loading.gif'; ?>" alt="">
                    </div>
                </div>
                <?php
                $total_pages = intval($entries_list['pagination']['total_pages']);
                $current_page = 1; // replace with actual current page if available
                $window = 3;

                if ($total_pages > 1) { ?>
                    <nav class="pagination-container" aria-label="Page navigation">
                        <ul class="pagination" id="pagination_nm" data-type="agency_overview">

                            <!-- Prev -->
                            <li class="page-item <?php echo ($current_page <= 1) ? 'nm_disabled' : ''; ?>"
                                page-id="<?php echo max(1, $current_page - 1); ?>">
                                <a href="#">&laquo;</a>
                            </li>

                            <?php
                            /**
                             * Calculate sliding window
                             */
                            $half = floor($window / 2);
                            $start = max(1, $current_page - $half);
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
                                <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>"
                                    page-id="<?php echo $i; ?>">
                                    <a href="#"><?php echo $i; ?></a>
                                </li>
                            <?php }

                            // Trailing dots + last page
                            if ($end < $total_pages) { ?>
                                <li class="page-item dots">
                                    <span>…</span>
                                </li>

                                <li class="page-item <?php echo ($current_page == $total_pages) ? 'active' : ''; ?>"
                                    page-id="<?php echo $total_pages; ?>">
                                    <a href="#"><?php echo $total_pages; ?></a>
                                </li>
                            <?php } ?>

                            <!-- Next -->
                            <li class="page-item <?php echo ($current_page >= $total_pages) ? 'nm_disabled' : ''; ?>"
                                page-id="<?php echo min($total_pages, $current_page + 1); ?>">
                                <a href="#">&raquo;</a>
                            </li>

                        </ul>
                    </nav>
                <?php } ?>



            </div>

        </main>
    </div>
</div>