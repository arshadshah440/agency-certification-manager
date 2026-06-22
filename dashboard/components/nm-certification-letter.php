<?php
if (!defined('ABSPATH')) {
    exit;
}
// Clean address to remove HTML
$nm_agency_address_raw = $agency_details['physical_address'] ?? '';
$nm_agency_address = wp_strip_all_tags(
    html_entity_decode($nm_agency_address_raw, ENT_QUOTES | ENT_HTML5, 'UTF-8')
);
$nm_agency_address = preg_replace('/\s+/', ' ', trim($nm_agency_address));

// Format letter date
$letter_date_formatted = !empty($letter_date) ? date('F j, Y', strtotime($letter_date)) : '';
?>

<div class="nm_letter_certficiations">
    <header>
        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
            <tr>
                <td width="50%" valign="center">
                    <?php
                    $custom_logo = get_option('nm_letters_custom_logo');
                    $logo_url = $custom_logo ? $custom_logo : trailingslashit(NM_APPS_URL) . 'assets/logo.png';
                    echo '<img src="' . esc_url($logo_url) . '" style="width:150px; margin-bottom:10px;" class="nm-logo-img">';
                    ?>
                </td>
                <td width="50%" valign="center" align="right" style="font-size:11px; text-align:right; line-height:1.4;">
                    <b>Jane Doe, Governor</b><br>
                    John Smith, Secretary<br>
                    Sarah Williams, Deputy Secretary<br>
                    Michael Brown, Acting Deputy Secretary<br>
                    Emily Davis, Acting Medicaid Director
                </td>
            </tr>
        </table>

        <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:15px;">
            <tr>
                <td style="border-bottom:2px solid #1aa6b7 !important;"></td>
            </tr>
        </table>
    </header>

    <div class="nm_body">
        <section>
            <p><?php echo $letter_date_formatted; ?></p>

            <p>
                RE: <strong><?php echo $nm_program_name; ?></strong> Initial Approval
            </p>

            <p>
                <?php echo $nm_agency_address; ?>
            </p>

            <p>
                Dear <?php echo $nm_agency_name; ?>,
            </p>

            <p>
                Thank you for the opportunity to review your application to serve Medical Assistance Programs (MAP)
                eligible adult recipients located at <?php echo $nm_agency_address; ?>. The
                <?php echo $nm_program_name; ?>
                application and approval process is overseen by the Health Care Authority (HCA).
            </p>

            <p>
                It has been determined that the agency’s application submission meets the
                <?php echo $nm_program_name; ?>
                requirements as detailed in 8.321.2 NMAC <?php echo $nm_program_name; ?>. Consequently, a
                <?php echo $nm_letter_durations; ?> year approval has been issued for <?php echo $nm_program_name; ?>
                <?php echo $for_details; ?>
                effective from <?php echo $letter_date_formatted; ?> to <?php echo $approval_end_date; ?>. HCA will
                conduct
                a site visit within this period.
                Following completion of a site visit, a three (3) year approval will be issued.
            </p>

            <p>
                The Medicaid ID and NPI numbers for the approved site are as follows:<br>
                <strong>Medicaid ID:</strong> <?php echo $nm_medical_id; ?> &nbsp; <strong>NPI:</strong>
                <?php echo $nm_npi; ?>
            </p>

            <p style="margin-bottom: 10px;">
                <?php echo $nm_letter_message; ?>
            </p>

            <p>
                If your agency is currently enrolled as a Medicaid provider, please submit the update and upload the
                supporting documents through the Provider Portal. If you are a new Medicaid provider, you must complete
                the Provider Participation Agreement (PPA) online. New Medicaid providers must submit this letter with
                the
                PPA. Your approved <?php echo $nm_program_name; ?> provider specialty will be added to your provider
                file
                after your enrollment request is approved.
            </p>

            <p>
                Kindly notify BHSD via email at <?php echo $current_user_email; ?> of any changes to the
                <?php echo $nm_program_name; ?> address. If your agency is no longer providing
                <?php echo $nm_program_name; ?> services,
                please contact BHSD and provide a reason for the cessation. If your agency wishes to expand
                <?php echo $nm_program_name; ?> services to additional agency locations, please note that an additional
                application must be submitted for each location.
            </p>

            <p>
                Should you have any questions, please contact <?php echo $current_user_email; ?>. All Medicaid provider
                enrollment questions should be referred to the Consolidated Customer Service Center (CCSC) at
                1-800-299-7304
                or via email at NM.Providers@hca.nm.gov.
            </p>

            <p>
                Sincerely,
            </p>

            <p>
                <?php echo ucfirst($user_name); ?><br>
                Behavioral Health Services Division<br>
                New Mexico Health Care Authority<br>
                PO Box 2348 - Santa Fe, NM 87504 | Phone: (505) 827-7750 Fax: (505) 827-6286
            </p>
        </section>
        <footer>

            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:15px;">
                <tr>
                    <td style="border-bottom:2px solid #1aa6b7 !important;"></td>
                </tr>
            </table>

            <p style="text-align:center; margin-top:15px;">
                <strong>New Mexico Health Care Authority</strong><br>
                PO Box 2348 - Santa Fe, NM 87504 | Phone: (505) 827-7750 Fax: (505) 827-6286
            </p>
        </footer>

    </div>
</div>