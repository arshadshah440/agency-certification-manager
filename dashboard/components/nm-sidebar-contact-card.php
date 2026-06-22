<?php
if (!defined('ABSPATH')) {
    exit;
}

$has_secondary_location = false;
$secondary_location_details = [];

if (!empty($entry_id) && class_exists('FrmEntryMeta')) {
    global $wpdb;
    $meta_rows = $wpdb->get_results(
        $wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id = %d", $entry_id)
    );
    $html_content = '';
    foreach ($meta_rows as $row) {
        $val = maybe_unserialize($row->meta_value);
        if (is_string($val) && strpos($val, 'Agency New Locations:') !== false) {
            $html_content = $val;
            break;
        }
    }
    
    if (!empty($html_content)) {
        // Strip HTML tags to get plain text
        $plain = wp_strip_all_tags($html_content);

        // Extract only the part after 'Agency New Locations:'
        $new_loc_pos = strpos($plain, 'Agency New Locations:');
        if ($new_loc_pos !== false) {
            $loc_text = substr($plain, $new_loc_pos + strlen('Agency New Locations:'));

            if (preg_match('/Physical Address:\s*(.+?)(?=Location Name:|Locations Npi:|Mailing Address:|$)/s', $loc_text, $m))
                $secondary_location_details['address'] = trim($m[1]);

            if (preg_match('/Location Name:\s*(.+?)(?=Physical Address:|Locations Npi:|Mailing Address:|$)/s', $loc_text, $m))
                $secondary_location_details['location_name'] = trim($m[1]);

            if (preg_match('/Locations Npi:\s*(.+?)(?=Physical Address:|Location Name:|Mailing Address:|Medicaid|$)/s', $loc_text, $m))
                $secondary_location_details['npi'] = trim($m[1]);

            if (preg_match('/Medicaid[^:]*:\s*(.+?)(?=Physical Address:|Location Name:|Locations Npi:|Mailing Address:|$)/si', $loc_text, $m))
                $secondary_location_details['medicaid_id'] = trim($m[1]);
        }

        $has_secondary_location = !empty(array_filter($secondary_location_details));
    }
}
?>

<h2><?php echo !empty($is_opre_manager) && $is_opre_manager ? 'USER ' : 'AGENCY'; ?> CONTACT INFORMATION</h2>

<?php if (empty($agency_detailed_content)) {
    $agency_detailed_content = AgencyDetailsDisplay::get_current_user_details($user_id);
    $user_phone = $agency_detailed_content['cell_phone'] ?: ($agency_detailed_content['home_phone'] ?? null);
} else {
    $user_phone = null;
} ?>

<div class="info-group">
    <p class="label">
        <?php echo isset($agency_detailed_content['username']) ? 'Username' : 'Agency Name'; ?>
    </p>
    <p class="value">
        <?php echo $agency_detailed_content['agency_name'] ?? ($agency_detailed_content['username'] ?? 'N/A'); ?>
    </p>
</div>

<div class="info-group">
    <p class="label">Contact Person's Name:</p>
    <p class="value">
        <?php echo $agency_detailed_content['contact_name'] ?? ($agency_detailed_content['name'] ?? 'N/A'); ?>
    </p>
</div>

<div class="info-group">
    <p class="label">Email:</p>
    <p class="value">
        <?php echo $agency_detailed_content['agency_email'] ?? ($agency_detailed_content['email'] ?? 'N/A'); ?>
    </p>
</div>

<div class="info-group">
    <p class="label">Phone:</p>
    <p class="value">
        <?= !empty($agency_detailed_content['phone']) ? $agency_detailed_content['phone'] : ($user_phone ?? 'N/A'); ?>
    </p>
</div>

<?php if ($has_secondary_location): ?>
    <style>
        .nm-sidebar-tabs .tab.nm-sidebar-tab-btn {
            height: 36px;
            padding: 0 12px;
            box-sizing: border-box;
            position: static !important;
            overflow: visible !important;
        }
        .nm-sidebar-tabs .tab.nm-sidebar-tab-btn.active {
            position: static !important;
            overflow: visible !important;
        }
        .nm-sidebar-tabs .tab.nm-sidebar-tab-btn:hover {
            background: var(--color-primary) !important;
            color: var(--color-white) !important;
            border-color: var(--color-primary) !important;
        }
        .nm-sidebar-tabs .tab.nm-sidebar-tab-btn.active::before,
        .nm-sidebar-tabs .tab.nm-sidebar-tab-btn.active:hover::before {
            display: none !important;
        }
    </style>
    <div class="action-buttons nm-sidebar-tabs" style="margin-bottom: 15px; display: flex; gap: 10px;">
        <button class="tab nm-sidebar-tab-btn active" data-target='tab-parent-loc'>Parent Location</button>
        <button class="tab nm-sidebar-tab-btn" data-target='tab-secondary-loc'>Secondary Location</button>
    </div>

    <div id="tab-parent-loc" class="nm-sidebar-tab-content" style="display: block;">
        <div class="info-group">
            <p class="label">Address:</p>
            <p class="value">
                <?php echo $agency_detailed_content['physical_address'] ?? ($agency_detailed_content['user_address'] ?? 'N/A'); ?>
            </p>
        </div>
        <?php if (!empty($agency_detailed_content['npi'])) { ?>
            <div class="info-group">
                <p class="label">NPI:</p>
                <p class="value"><?php echo $agency_detailed_content['npi']; ?></p>
            </div>
        <?php } ?>
        <?php if (!empty($agency_detailed_content['medicaid_id'])) { ?>
            <div class="info-group">
                <p class="label">Medicaid ID:</p>
                <p class="value"><?php echo $agency_detailed_content['medicaid_id']; ?></p>
            </div>
        <?php } ?>
    </div>

    <div id="tab-secondary-loc" class="nm-sidebar-tab-content" style="display: none;">
        <div class="info-group">
            <p class="label">Address:</p>
            <p class="value">
                <?php
                if (!empty($secondary_location_details['address'])) {
                    $raw = trim($secondary_location_details['address']);
                    // Format: "Street, City, State Zip Country"
                    // Split on commas: [street, city, state+zip+country]
                    $parts = array_map('trim', explode(',', $raw));
                    $street = $parts[0] ?? '';
                    $city   = isset($parts[1]) ? $parts[1] . ',' : '';
                    // Remaining after street and city
                    $rest   = implode(',', array_slice($parts, 2));
                    // Extract state, zip, country from rest
                    preg_match('/^\s*(.+?)\s+(\S+)\s+(\S+)\s*$/', trim($rest), $rm);
                    $state   = $rm[1] ?? trim($rest);
                    $zip     = $rm[2] ?? '';
                    $country = $rm[3] ?? '';
                    ?>
                    <div class="sim_address_field">
                        <div class="sim_address_row"><span class="street1"><?php echo esc_html($street); ?></span></div>
                        <div class="sim_address_row">
                            <span class="city"><?php echo esc_html($city); ?></span>
                            <span class="state"><?php echo esc_html($state); ?></span>
                            <span class="zip"><?php echo esc_html($zip); ?></span>
                            <span class="country"><?php echo esc_html($country); ?></span>
                        </div>
                    </div>
                    <?php
                } else {
                    echo 'N/A';
                }
                ?>
            </p>
        </div>
        <?php if (!empty($secondary_location_details['location_name'])) { ?>
            <div class="info-group">
                <p class="label">Location Name:</p>
                <p class="value"><?php echo esc_html($secondary_location_details['location_name']); ?></p>
            </div>
        <?php } ?>
        <?php if (!empty($secondary_location_details['npi'])) { ?>
            <div class="info-group">
                <p class="label">NPI:</p>
                <p class="value"><?php echo esc_html($secondary_location_details['npi']); ?></p>
            </div>
        <?php } ?>
        <?php if (!empty($secondary_location_details['medicaid_id'])) { ?>
            <div class="info-group">
                <p class="label">Medicaid ID:</p>
                <p class="value"><?php echo esc_html($secondary_location_details['medicaid_id']); ?></p>
            </div>
        <?php } ?>
    </div>

    <script>
        jQuery('.nm-sidebar-tabs').off('click', '.nm-sidebar-tab-btn').on('click', '.nm-sidebar-tab-btn', function(e) {
            e.preventDefault();
            jQuery('.nm-sidebar-tabs .nm-sidebar-tab-btn').removeClass('active');
            jQuery(this).addClass('active');
            var target = jQuery(this).attr('data-target');
            jQuery('.nm-sidebar-tab-content').hide();
            jQuery('#' + target).show();
        });
    </script>

<?php else: ?>

    <div class="info-group">
        <p class="label">Address:</p>
        <p class="value">
            <?php echo $agency_detailed_content['physical_address'] ?? ($agency_detailed_content['user_address'] ?? 'N/A'); ?>
        </p>
    </div>
    <?php if (!empty($agency_detailed_content['npi'])) { ?>
        <div class="info-group">
            <p class="label">NPI:</p>
            <p class="value"><?php echo $agency_detailed_content['npi']; ?></p>
        </div>
    <?php } ?>
    <?php if (!empty($agency_detailed_content['medicaid_id'])) { ?>
        <div class="info-group">
            <p class="label">Medicaid ID:</p>
            <p class="value"><?php echo $agency_detailed_content['medicaid_id']; ?></p>
        </div>
    <?php } ?>

<?php endif; ?>

<?php if ( current_user_can( 'administrator' ) ) : ?>
<div class="sidebar-actions">
    <button class="btn-outline-red" id="update_address_nm">UPDATE ADDRESS</button>
    <button class="btn-solid-green" id="send_email_nm">SEND EMAIL</button>
</div>
<?php endif; ?>
