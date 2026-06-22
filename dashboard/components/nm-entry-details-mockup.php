<?php

if (!defined('ABSPATH')) {
    exit;
}
$timestamp = strtotime($entry_created_at); // Convert string to Unix timestamp
$formattedDate = date('l, F j, Y', $timestamp);
$entry_status_details = NM_Helpers::nm_get_entry($entry_id);
$entry_status = isset($entry_status_details['entry_status']) ? $entry_status_details['entry_status'] : "";
$entry_letter_details = NM_Helpers::nm_get_latest_attachment_by_entry_id($entry_id);
$entry_letter_mockup = isset($entry_status_details['html_content']) ? wp_kses_post($entry_status_details['html_content']) : "";
$entry_details_nm = FrmEntry::getOne($entry_id);
$agency_id = isset($_GET['agency-id']) && !empty($_GET['agency-id']) ? $_GET['agency-id'] : "";
$agency_detailed_content = NM_Helpers::get_agency_details_by_its_id($agency_id);
$all_files_status = NM_Helpers::nm_are_all_entry_files_approved($entry_id);
$current_user_id = get_current_user_id();
$current_user_email = '';

$current_entry_manger = NM_Helpers::nm_get_managers_by_entry_form($entry_id);

$is_nm_manager = NM_Helpers::nm_user_has_access_to_entry_form($entry_id, $current_user_id);

// Check if the user ID is valid (not 0 for a logged-out user)
if ($current_user_id != 0) {
    // Get the user data object
    $user_info = get_userdata($current_user_id);

    // Access the email
    $current_user_email = $user_info->user_email;

    // Display the email
}

$set_first_user = get_field('set_the_first_person_for_letter_review', 'option');
$set_last_user = get_field('set_the_last_person_for_letter_review', 'option');
$user_info = get_userdata($set_first_user);
$first_reviewer_name = '';
$first_reviewer_email = '';
if ($user_info) {
    $first_reviewer_name = $user_info->display_name;
    $first_reviewer_email = $user_info->user_email;
}

$last_user_info = get_userdata($set_last_user);
$last_reviewer_name = '';
$last_reviewer_email = '';

if ($last_user_info) {
    $last_reviewer_name = $last_user_info->display_name;
    $last_reviewer_email = $last_user_info->user_email;
}

if ($entry_details_nm) {
    $form_id = $entry_details_nm->form_id;
    $form_details = FrmForm::getOne($form_id);

    ?>
    <div class="loader_nm hide_this_loader_nm" id="nm_loader_wrapper">
        <img src="<?php echo NM_APPS_URL . '/assets/Loading.gif'; ?>" alt="">
    </div>
    <div class="application-details" id="nm-application-details">
        <div class="entry_details_nm_download" id="entry_details_nm_download">
            <h2>Application #<?php echo $entry_id; ?> <span style="color: black;"> (<?php echo $formattedDate; ?>)</span>
            </h2>
            <button id="nm_view_whole_entry" entry-details-id="<?php echo $entry_id; ?>">View Complete Entry</button>

        </div>
        <p>What would you like to do?</p>

        <div class="action-buttons nm-document-tabs">
            <button class="tab nmtabdetailed_entry active" tab-id='tab-review'>REVIEW SUPPORTING
                DOCUMENTS</button>
            <?php if ($form_id != 29) {
                ?>
                <button class="tab nmtabdetailed_entry" tab-id='tab-signing'>VIEW SIGNING DOCUMENT</button>
                <?php
            } ?>
            <button class="tab nmtabdetailed_entry" tab-id='tab-approve'>APPROVE/DENY PROVIDER</button>


        </div>

        <div id="tab-review" class="tab-content">
            <div class="document-list">
                <h3>Applicant Uploaded Documents</h3>

                <?php

                $document_list = [];

                if (!empty($entry_id)) {
                    $entryid = $entry_id;
                    $document_list = NM_Helpers::get_file_fields_from_entry($entryid);
                }

                if (!empty($document_list)) {

                    foreach ($document_list as $list) {

                        foreach ($list['files'] as $file) {
                            $status = get_post_meta($file['attachment_id'], '_nm_attachment_status', true);

                            ?>
                            <div class="document-item">
                                <div class="document-name">
                                    <i class="fa-solid fa-circle-dot"></i>
                                    <p>
                                        <?php
                                        $url = $file['attachment_url'] ?? '';
                                        $name = $list['field_name'] ?? '';

                                        if (empty($name) && !empty($url)) {
                                            $name = basename($url);
                                        }
                                        ?>

                                        <?php if (!empty($url)): ?>
                                        <p>
                                            <a href="<?php echo esc_url($url); ?>" target="_blank">
                                                <?php echo esc_html($name); ?>
                                            </a>
                                        </p>
                                    <?php endif; ?>
                                    </p>
                                </div>

                                <div class="document-actions">
                                    <button
                                        class="btn-accept nmattachment_status_update nmstatus_<?php echo ($status == 1) ? "1 " : " "; ?> <?php echo ($status == 0 || $status == 1 || !empty($entry_status)) ? "nm_disabled" : ""; ?>"
                                        entry-status="1" attachment-id="<?php echo esc_attr($file['attachment_id']); ?>"
                                        field-id="<?php echo esc_attr($list['field_id']); ?>">
                                        <?php echo ($status == 1) ? "ACCEPTED" : "ACCEPT"; ?>

                                    </button>

                                    <button
                                        class="btn-reject nmattachment_status_update nmstatus_<?php echo ($status == 0) ? "0 " : " "; ?><?php echo ($status == 0 || $status == 1 || !empty($entry_status)) ? "nm_disabled" : ""; ?>"
                                        entry-status="0" attachment-id="<?php echo esc_attr($file['attachment_id']); ?>"
                                        field-id="<?php echo esc_attr($list['field_id']); ?>">
                                        <?php echo ($status == 0) ? "REJECTED" : "REJECT"; ?>

                                    </button>
                                </div>
                            </div>

                            <?php
                        }
                    }
                } else {
                    ?>
                    <p>No Document Found.</p>
                    <?php
                }
                ?>

                <h3>Applicant Supplemental Documents</h3>

                <?php


                $document_list = [];

                if (!empty($entry_id)) {
                    $entryid = $entry_id;
                    $document_list = NM_Helpers::nm_get_custom_dynamic_meta_enhanced($entryid);
                }

                if (!empty($document_list)) {

                    foreach ($document_list as $list) {

                        $file = $list['attachment_details'];
                        if (!empty($file)) {

                            ?>
                            <div class="document-item">
                                <div class="document-name">
                                    <i class="fa-solid fa-circle-dot"></i>
                                    <p>
                                        <a href="<?php echo esc_url($file['attachment_url']); ?>" target="_blank">
                                            <?php echo esc_html($file['_nm_document_name'] ?? $file['attachment_name']); ?>
                                        </a>
                                    </p>
                                </div>

                            </div>

                            <?php
                        }
                    }
                } else {
                    ?>
                    <p>No Document Found.</p>
                    <?php
                }
                ?>

            </div>

            <hr class="divider">

            <div class="what-to-do">
                <h3>What would you like to do?</h3>
                <div class="action-buttons">
                    <button class="tab document-tab">UPLOAD/REPLACE DOCUMENTS</button>
                    <button class="tab document-tab" id="ask_provider_for_new_documents">ASK PROVIDER FOR NEW
                        DOCUMENTS</button>
                </div>
                <?php

                $document_list = [];

                if (!empty($entry_id)) {
                    $entryid = $entry_id;
                    $document_list = NM_Helpers::get_file_fields_from_entry($entryid);
                }

                if (!empty($document_list)) {

                    foreach ($document_list as $list) {

                        foreach ($list['files'] as $file) {
                            $status = get_post_meta($file['attachment_id'], '_nm_attachment_status', true);

                            if (!($status == "") && $status == "0") {

                                ?>
                                <div class="document-item">
                                    <div class="what-to-do-item">
                                        <div class="what-to-do-name"><i class="fa-solid fa-upload"></i> <a
                                                href="#"><?php echo $list['field_name']; ?></a></div>
                                        <div class="what-to-do-actions"><button class="btn-replace replace_nm_docs"
                                                entry-id="<?php echo $entry_id; ?>" field-id="<?php echo $list['field_id']; ?>"
                                                field-name="<?php echo $list['field_name']; ?>">REPLACE</button></div>
                                    </div>
                                </div>

                                <?php
                            }
                        }
                    }
                } else {
                    ?>
                    <p>No Document Found.</p>
                    <?php
                } ?>
            </div>

            <div class="hide_me_nm" id="replace_request_formwrapper_ar">
                <form action="/submit" method="POST" enctype="multipart/form-data" id="replace_request_formw_ar"
                    entry-id="<?php echo $entry_id; ?>">
                    <div class="card main-card">
                        <div class="table-container ">
                            <hr class="divider">
                            <div class="application-upload">
                                <h3 class="replace_doc_app_heading">Application upload replaced by post <span
                                        style="color: black;">(ID117545)</span></h3>
                                <p class="replace_doc_field_name">Replacing upload: <br> <span
                                        style="color: #DB3239;">Upload: Resume that
                                        documents at least one year of supervisory experience*</span></p>

                                <div class="upload-grid">
                                    <div class="grid-col-left">
                                        <label class="field-label">
                                            <p>Upload a new file: <span class="required">*</span></p>
                                        </label>
                                        <label class="upload-box nm_file_upload_drop" for="resume-upload-nm">
                                            <div class="content_wrappers_nm" id="content_wrappers_nm_replace">
                                                <i class="fa-solid fa-upload"></i>
                                                <p class="custom-text">Drop a file here or click to upload</p>
                                                <span class="custom-text">Maximum file size: 515MB</span>
                                            </div>

                                            <div class="nm-file-preview" id="replace_doc_preview" style="display:none;">
                                                <div class="nm-file-thumb" id="replace_doc_thumb"></div>
                                                <span class="nm-file-name" id="replace_doc_file_name"></span>
                                                <button type="button" class="nm-remove-file">✕</button>
                                            </div>



                                            <input type="file" id="resume-upload-nm" name="resume_file"
                                                style="display: none;">
                                        </label>
                                    </div>
                                    <div class="grid-col-right">
                                        <label class="field-label" for="file-note">
                                            <p> Add a note: <span class="required">*</span></p>
                                        </label>
                                        <textarea id="file-note" name="application_note"></textarea>
                                    </div>
                                </div>



                            </div>

                            <hr class="divider">
                            <div class="footer-actions">
                                <button type="submit" class="btn-submit">Submit</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div id="replace_request_formwrapper_ar">
                <form action="/submit" method="POST" enctype="multipart/form-data" id="replace_supplement_request_formw_ar"
                    entry-id="<?php echo $entry_id; ?>">
                    <div class="card main-card">
                        <div class="table-container ">
                            <hr class="divider">
                            <div class="application-upload">

                                <div class="supplemental-docs">
                                    <p class="bold-text">
                                        Do you need to upload supplemental documents on behalf of the provider?
                                    </p>

                                    <div class="button-group nm_btn_toggle_btns">
                                        <label class="btn_yes_wrapper">
                                            <input type="radio" class="btn-yes" name="toggle_supplemental_docs" value="yes">
                                            Yes
                                        </label>

                                        <label class="btn_no_wrapper">
                                            <input type="radio" class="btn-no" name="toggle_supplemental_docs" value="no"
                                                checked>
                                            No
                                        </label>
                                    </div>
                                </div>

                            </div>

                            <hr class="divider">

                            <div class="supporting-document-upload hide_me_nm" id="nm_supplemental_docs_form_wraper">
                                <h3>Upload Supporting Document</h3>
                                <div class="full-width-row">
                                    <label class="field-label" for="doc-name">
                                        <p>Document name <span class="required">*</span></p>
                                    </label>
                                    <input type="text" id="doc-name" name="document_name">
                                </div>
                                <div class="upload-grid">
                                    <div class="grid-col-left">
                                        <label class="field-label">Upload Supporting document:<span
                                                class="required">*</span></label>
                                        <label class="upload-box" for="support-doc-upload">
                                            <div class="content_wrappers_nm" id="content_wrappers_nm_supplement">
                                                <i class="fa-solid fa-upload"></i>
                                                <p class="custom-text">Drop a file here or click to upload</p>
                                                <span class="custom-text">Maximum file size: 515MB</span>
                                            </div>

                                            <div class="nm-file-preview" id="supplement_doc_preview" style="display:none;">
                                                <div class="nm-file-thumb" id="supplement_doc_thumb"></div>
                                                <span class="nm-file-name" id="supplement_doc_file_name"></span>
                                                <button type="button" class="nm-remove-file">✕</button>
                                            </div>
                                            <input type="file" id="support-doc-upload" name="supporting_file"
                                                style="display: none;">
                                        </label>
                                    </div>
                                    <div class="grid-col-right">
                                        <label class="field-label" for="doc-note">Add a note: <span
                                                class="required">*</span></label>
                                        <textarea id="doc-note" name="supporting_note"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="footer-actions" id="nm_supplement_footer_actions" style="display:none;">
                                <button type="submit" class="btn-submit">Submit</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

        </div>

        <?php if ($form_id != 29) {
            ?>
            <div id="tab-signing" class="tab-content" style="display: none;" entry-id="<?php echo $entry_id; ?>">
                <div class="signing-tab-wrapper">

                    <h3 class="tab-title">Signing Document</h3>




                    <div class="nm-document-preview-container">
                        <div class="document_doc_esign_doc" id="document_doc_esign_doc" iframeloaded="false">

                        </div>
                        <div class="contract-actions signing-footer-actions" style="margin-top:15px;">
                            <span class="btn-main-site">Back to Main Site</span>
                            <div class="right-buttons">
                                <span onclick="document.getElementById('contractFrame').contentWindow.print();"
                                    class="btn-black">
                                    Print Contract
                                </span>
                                <span onclick="document.getElementById('contractFrame').contentWindow.print();"
                                    class="btn-black">
                                    Download PDF
                                </span>
                            </div>
                        </div>
                        <div class="loader_nm hide_this_loader_nm" id="nm_loader_wrapper">
                            <img src="<?php echo NM_APPS_URL . '/assets/Loading.gif'; ?>" alt="">
                        </div>
                    </div>


                    <!-- <div class="signing-footer-actions">
                                <button class="btn-main-site">Back to Main Site</button>
                                <div class="right-buttons">
                                    <button class="btn-black">Print Document</button>
                                    <button class="btn-black">Save as PDF</button>
                                </div>
                            </div> -->

                </div>
            </div>
            <?php
        } ?>

        <div id="tab-approve" class="tab-content <?php echo !$all_files_status ? 'nm_click_not_allowed' : ''; ?>"
            style="display: none;">
            <?php if (!$all_files_status && empty($entry_status)) { ?>
                <p class="nm_warning_message_ar">Kindly First Approve/Deny All The Attachments For This Application.</p>
                <?php
            } else if ($form_id == 29) {
                ?>
                    <div class="card">
                        <div class="nmtable-container">
                        <?php if (empty($entry_status)) { ?>
                                <div class="nm_letter_actions" id="nm_letter_actions">

                                    <label class="field-label-small">Choose one:</label>

                                    <div class="button-group nm_btn_toggle_btns" id="opre_id_status_entry"
                                        entry-id="<?php echo $entry_id; ?>">
                                        <label class="btn_yes_wrapper">
                                            <input type="radio" class="btn-yes" name="toggle_entry_status_opre" value="approved"
                                              >
                                            APPROVE
                                        </label>

                                        <label class="btn_no_wrapper">
                                            <input type="radio" class="btn-no" name="toggle_entry_status_opre" value="denied">
                                            DENY
                                        </label>
                                    </div>
                                </div>
                        <?php } else {
                            ?>
                            <?php echo "<p class='nm_app_status'>Application Status :  <span class='btn-$entry_status'>$entry_status </span></p>"; ?>
                            <?php
                        } ?>
                        </div>
                    </div>
                <?php
            } else { ?>
                    <div class="card main-card" style="margin-top: 24px;">
                        <div class="nmtable-container">

                        <?php if (empty($entry_status)) { ?>
                                <div class="nm_letter_actions" id="nm_letter_actions">

                                    <label class="field-label-small">Choose one:</label>

                                    <div class="button-group nm_btn_toggle_btns">
                                        <label class="btn_yes_wrapper">
                                            <input type="radio" class="btn-yes" name="toggle_entry_status" value="approve" checked="">
                                            APPROVE
                                        </label>

                                        <label class="btn_no_wrapper">
                                            <input type="radio" class="btn-no" name="toggle_entry_status" value="deny">
                                            DENY
                                        </label>
                                    </div>
                                    <!-- <div class="action-row-spaced">
                        <button class="btn-approve">APPROVE</button>
                        <button class="btn-deny">DENY</button>
                    </div> -->

                                    <div class="nm_generate_letter" id="nm_approval_fields">

                                        <h3>Let's generate your APPROVAL letter.</h3>

                                        <div class="form-row-equal">
                                            <div>
                                                <label class="field-label-small">Date: <span class="required">*</span></label>
                                                <div style="position: relative;">
                                                    <input type="date" placeholder="Enter the Date" name="nm_letter_date"
                                                        id="nm_letter_date">
                                                </div>
                                            </div>
                                            <div>
                                                <label class="field-label-small">Enter The Duration In Years: <span
                                                        class="required">*</span></label>
                                                <input type="number" min="0" id="nm_letter_durations"
                                                    placeholder="Enter The Duration In Years">
                                            </div>
                                        </div>

                                        <div class="form-row-equal">
                                            <div>
                                                <label class="field-label-small">Select Approvals:</label>

                                                <select id="nm_program_name" name="nm_program_name">
                                                    <option value="<?php echo $form_details->name; ?>" data-short="CCSS" selected>
                                                    <?php echo $form_details->name; ?>
                                                    </option>
                                                </select>
                                            </div>
                                            <div>
                                                <?php
                                                if (str_contains('aartc', $form_details->form_key) || str_contains('iop', $form_details->form_key)) {
                                                    ?>
                                                    <label class="field-label-small"
                                                        form-name="<?php echo $form_details->form_key; ?>">Letter For:</label>

                                                    <select id="nm_program_name_iop" name="nm_program_name_iop">
                                                        <option value="- Substance Use Disorders (SUD)" data-short="CCSS" selected>
                                                            Substance Use Disorders (SUD)
                                                        </option>
                                                        <option value="- Mental Health (MH)" data-short="CCSS" selected>
                                                            Mental Health (MH)
                                                        </option>
                                                        <option value="Both Substance Use Disorders (SUD) and Mental Health (MH)"
                                                            data-short="CCSS" selected>
                                                            Both Substance Use Disorders (SUD) and Mental Health (MH)
                                                        </option>
                                                    </select>
                                                <?php
                                                }
                                                ?>
                                            </div>
                                        </div>

                                        <div class="full-width-row">
                                            <label class="field-label-small">Custom Message: <span class="required">*</span></label>
                                            <textarea placeholder="Add custom message (optional)" id="nm_letter_message"
                                                style="height: 120px;"></textarea>
                                        </div>
                                    </div>

                                    <div class="form-row-equals hide_nm_fields" id="deny_fields_nm">
                                        <h3>Let's generate your Denial letter.</h3>

                                        <div>
                                            <label class="field-label-small">Select Approvals:</label>

                                            <select id="nm_program_deny_reason" name="nm_program_deny_reason">
                                                <option value="The applicant did not respond to a request for additional information"
                                                    data-short="CCSS" selected>
                                                    The applicant did not respond to a request for additional information
                                                </option>
                                                <option value="The applicant does not have an RLD approved clinical supervisor"
                                                    data-short="CCSS" selected>
                                                    The applicant does not have an RLD approved clinical supervisor
                                                </option>
                                                <option value="The applicant has not have an evidence based practice" data-short="CCSS"
                                                    selected>
                                                    The applicant has not have an evidence based practice
                                                </option>
                                                <option value="The applicant did not provide the appropriate polices and procedures"
                                                    data-short="CCSS" selected>
                                                    The applicant did not provide the appropriate polices and procedures
                                                </option>
                                                <option value="The applicant chose to rescind their application" data-short="CCSS"
                                                    selected>
                                                    The applicant chose to rescind their application
                                                </option>
                                                <option value="The applicant did not provide evidence of training" data-short="CCSS"
                                                    selected>
                                                    The applicant did not provide evidence of training
                                                </option>
                                                <option value="The applicant did not demonstrate readiness to provide services 24/7"
                                                    data-short="CCSS" selected>
                                                    The applicant did not demonstrate readiness to provide services 24/7
                                                </option>
                                                <option value="The applicant did not provide all the supporting documentation required"
                                                    data-short="CCSS" selected>
                                                    The applicant did not provide all the supporting documentation required
                                                </option>
                                            </select>
                                        </div>


                                    </div>

                                    <div class="right-align-container">
                                        <button class="btn-approve" id="nm_generate_letter" entry-id="<?php echo $entry_id; ?>">GENERATE
                                            LETTER</button>
                                    </div>
                                </div>
                        <?php } else {
                            ?>
                            <div class="nm_app_status_wrapper" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                                <?php echo "<p class='nm_app_status' style='margin-bottom:0;'>Application Status :  <span class='btn-$entry_status'>$entry_status </span></p>"; ?>
                                <?php if (in_array(strtolower(trim($entry_status)), ['approved', 'approve']) && current_user_can('administrator')) { ?>
                                    <button class="btn-approve" id="nm_regenerate_letter_btn" style="margin: 0;">REGENERATE LETTER</button>
                                <?php } ?>
                            </div>
                            
                            <?php if (in_array(strtolower(trim($entry_status)), ['approved', 'approve']) && current_user_can('administrator')) { ?>
                                <div class="nm_letter_actions" id="nm_letter_actions" style="display: none;">
                                    <input type="radio" name="toggle_entry_status" value="approve" checked style="display:none;">
                                    
                                    <div class="nm_generate_letter" id="nm_approval_fields" style="display: block;">
                                        <h3 style="margin-top: 0;">Let's regenerate your APPROVAL letter.</h3>

                                        <div class="form-row-equal">
                                            <div>
                                                <label class="field-label-small">Date: <span class="required">*</span></label>
                                                <div style="position: relative;">
                                                    <input type="date" placeholder="Enter the Date" name="nm_letter_date"
                                                        id="nm_letter_date">
                                                </div>
                                            </div>
                                            <div>
                                                <label class="field-label-small">Enter The Duration In Years: <span
                                                        class="required">*</span></label>
                                                <input type="number" min="0" id="nm_letter_durations"
                                                    placeholder="Enter The Duration In Years">
                                            </div>
                                        </div>

                                        <div class="form-row-equal">
                                            <div>
                                                <label class="field-label-small">Select Approvals:</label>

                                                <select id="nm_program_name" name="nm_program_name">
                                                    <option value="<?php echo $form_details->name; ?>" data-short="CCSS" selected>
                                                    <?php echo $form_details->name; ?>
                                                    </option>
                                                </select>
                                            </div>
                                            <div>
                                                <?php
                                                if (str_contains('aartc', $form_details->form_key) || str_contains('iop', $form_details->form_key)) {
                                                    ?>
                                                    <label class="field-label-small"
                                                        form-name="<?php echo $form_details->form_key; ?>">Letter For:</label>

                                                    <select id="nm_program_name_iop" name="nm_program_name_iop">
                                                        <option value="- Substance Use Disorders (SUD)" data-short="CCSS" selected>
                                                            Substance Use Disorders (SUD)
                                                        </option>
                                                        <option value="- Mental Health (MH)" data-short="CCSS" selected>
                                                            Mental Health (MH)
                                                        </option>
                                                        <option value="Both Substance Use Disorders (SUD) and Mental Health (MH)"
                                                            data-short="CCSS" selected>
                                                            Both Substance Use Disorders (SUD) and Mental Health (MH)
                                                        </option>
                                                    </select>
                                                <?php
                                                }
                                                ?>
                                            </div>
                                        </div>

                                        <div class="full-width-row">
                                            <label class="field-label-small">Custom Message: <span class="required">*</span></label>
                                            <textarea placeholder="Add custom message (optional)" id="nm_letter_message"
                                                style="height: 120px;"></textarea>
                                        </div>
                                    </div>

                                    <div class="right-align-container">
                                        <button class="btn-approve" id="nm_generate_letter" entry-id="<?php echo $entry_id; ?>">GENERATE
                                            LETTER</button>
                                    </div>
                                </div>
                                <script>
                                    jQuery(document).on('click', '#nm_regenerate_letter_btn', function(e) {
                                        e.preventDefault();
                                        jQuery('#nm_letter_actions').slideToggle();
                                    });
                                </script>
                            <?php } ?>
                            <?php
                        } ?>

                            <div class="letter_previews_nm <?php echo empty($entry_status) || empty($entry_letter_mockup) ? 'hide_by_default_nm' : ''; ?>"
                                id="letter_previews_nm">
                                <h3>Letter Preview:</h3>
                                <div class="letter_preview_wrapper" id="letter_preview_wrapper" contenteditable="true">
                                <?php echo $entry_letter_mockup; ?>
                                </div>
                                <div class="right-align-container">

                                    <button class="btn-approve" id="nm_save_letter" entry-status="<?php echo $entry_status; ?>"
                                        entry-id="<?php echo $entry_id; ?>">Save
                                        Letter</button>
                                </div>
                            </div>
                        <?php if (!empty($entry_status) && !empty($entry_letter_mockup)) { ?>

                                <div class="nm_action_after_letter <?php echo (!$entry_letter_details) ? 'hide_by_default_nm' : ''; ?>"
                                    id="nm_action_after_letter">

                                    <h3 style="color: #333; font-size: 14px; margin-bottom: 12px;">What would you like to
                                        do?</h3>

                                    <div class="action-row-spaced">
                                        <a class="btn-outline-black" id="nm_download_pdf" download="letter.pdf"
                                            href="<?php echo ($entry_letter_details) && isset($entry_letter_details['url']) ? $entry_letter_details['url'] : '#'; ?>">DOWNLOAD
                                            LETTER</a>
                                    <?php if (($current_user_id != $set_first_user && $current_user_id != $set_last_user)) { ?>
                                            <button class="btn-orange nm_email_letter_btns" id="nm_email_letter_btn_firstrev"
                                                currentuseremail="<?php echo $current_user_email; ?>"
                                                touseremail="<?php echo $first_reviewer_email; ?>">EMAIL LETTER TO SUPERVISOR
                                
                                           </button>
                                    <?php } elseif ($current_user_id == $set_first_user && $current_user_id != $set_last_user) { ?>
                                            <div class="nm_reviewer_wrapper">
                                                <p>Do You Approve The Language Box For This Letter?</p>
                                                <div class="nm_review_actions_wrapper">

                                                    <button class="btn-orange nm_email_letter_btns" id="nm_email_letter_btn_current"
                                                        currentuseremail="<?php echo $current_user_email; ?>"
                                                        touseremail="<?php echo !empty($current_entry_manger) ? $current_entry_manger->user_email : ""; ?>">NO
                                                        & EMAIL REVISION NOTES TO
                                                        COORDINATOR </button>
                                                        
                                                    <button class="btn-orange nm_email_letter_btns" id="nm_email_letter_btn_last_rev"
                                                        currentuseremail="<?php echo $current_user_email; ?>"
                                                        touseremail="<?php echo !empty($current_entry_manger) ? $current_entry_manger->user_email : ""; ?>"> YES & EMAIL LETTER TO COORDINATOR
                                                    </button>
                                                </div>
                                            </div>

                                        <?php
                                    } else {
                                        ?>
                                            <div class="nm_reviewer_wrapper">
                                                <p>Do You Approve The Language Box For This Letter?</p>
                                                <div class="nm_review_actions_wrapper">
                                                    <button class="btn-orange nm_email_letter_btns nm-yes-send-to-provider" id="nm_email_letter_btn_provider"
                                                        currentuseremail="<?php echo $current_user_email; ?>"
                                                        touseremail="<?php echo $agency_detailed_content['agency_email']; ?>">Yes & EMAIL
                                                        LETTER TO
                                                        PROVIDER</button>
                                                    <button class="btn-orange nm_email_letter_btns" id="nm_email_letter_btn_coordinator"
                                                        currentuseremail="<?php echo $current_user_email; ?>"
                                                        touseremail="<?php echo !empty($current_entry_manger) ? $current_entry_manger->user_email : ""; ?>">No
                                                        & EMAIL
                                                        Revision Notes TO
                                                        Coordinator</button>
                                                </div>
                                            </div>
                                        <?php
                                    } ?>

                                    </div>


                                    <!-- Form 53 for NO & other buttons -->
                                    <div class="nm_letter_creation_form hide_me_form_ar"
                                        id="nm_letter_creation_form_53">
                                    <?php echo do_shortcode("[formidable id=53]"); ?>
                                    </div>

                                    <!-- Form 56 for YES & EMAIL LETTER TO PROVIDER button -->
                                    <div class="nm_letter_creation_form hide_me_form_ar"
                                        id="nm_letter_creation_form_56">
                                    <?php echo do_shortcode("[formidable id=56]"); ?>
                                    </div>

                                    <script>
                                        // Pre-fill Form 53
                                        jQuery("#nm_letter_creation_form_53 .nm_to_email_wrapper").find("input[type='email']").val('<?php echo $first_reviewer_email; ?>');
                                        jQuery("#nm_letter_creation_form_53").find("#field_add_attachment").val(1);
                                        jQuery("#nm_letter_creation_form_53").find("#field_entry_id").val('<?php echo $entry_id ?>');

                                        // Pre-fill Form 56 fields
                                        jQuery("#nm_letter_creation_form_56").find("input[name='item_meta[2268]']").val('<?php echo $entry_id ?>'); // Entry ID field
                                        jQuery("#nm_letter_creation_form_56").find("input[name='item_meta[2266]']").val('<?php echo $agency_detailed_content['agency_email']; ?>'); // Email field
                                        jQuery("#nm_letter_creation_form_56").find("input[name='item_meta[2271]']").val(1); // Attachment field

                                        // Show Form 56 when YES & EMAIL LETTER TO PROVIDER is clicked
                                        jQuery(document).on('click', '.nm-yes-send-to-provider', function(e) {
                                            e.preventDefault();
                                            jQuery('#nm_letter_creation_form_53').addClass('hide_me_form_ar');
                                            jQuery('#nm_letter_creation_form_56').removeClass('hide_me_form_ar');
                                        });

                                        // Show Form 53 for all other buttons
                                        jQuery(document).on('click', '.nm_email_letter_btns:not(.nm-yes-send-to-provider)', function(e) {
                                            e.preventDefault();
                                            jQuery('#nm_letter_creation_form_56').addClass('hide_me_form_ar');
                                            jQuery('#nm_letter_creation_form_53').removeClass('hide_me_form_ar');
                                        });
                                    </script>

                                    <!-- <div class="footer-buttons-split">
                            <button class="btn-orange" id="nm_save_draft">SAVE DRAFT</button>
                            <button class="btn-approve" id="nm_send_email_btn" entry-id="<?php echo $entry_id; ?>">SEND</button>
                        </div> -->
                                </div>
                        <?php } ?>
                        </div>
                    </div>
            <?php } ?>
        </div>

    </div>
<?php } else {
    ?>
    <p>No Entry Against this entry id.</p>
    <?php
} ?>