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
$letter_date_formatted = $approval_start_date ?? date('Y-m-d');
?>

<div class="nm_letter_certficiations">
    <header>
        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
            <tr>
                <td width="50%" valign="top">
                    <?php
                    $logo_path = NM_APPS_PATH . '/assets/logo.png';
                    if ( file_exists( $logo_path ) ) {
                        echo '<img src="' . esc_url( trailingslashit( NM_APPS_URL ) . 'assets/logo.png' ) . '" style="width:150px; margin-bottom:10px;" class="nm-logo-img">';
                    }
                    ?>
                </td>
                <td width="50%" valign="top" align="right" style="font-size:11px; text-align:right; line-height:1.4;">
                    Jane Doe, Governor<br>
                    John Smith, Secretary<br>
                    Alex Johnson, Deputy Secretary<br>
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
            <p>Date: <?php echo $letter_date_formatted; ?></p>

            <p>
                <strong><?php echo $nm_agency_name; ?></strong>
            </p>

            <p>
                <?php echo $nm_agency_address; ?>
            </p>

            <p>
                RE: <strong><?php echo $nm_program_name; ?></strong>
            </p>

            <p>
                Dear <?php echo $nm_agency_name; ?>,
            </p>


            <p>Thank you for making an application to provide <?php echo $nm_program_name; ?> services. Based on the
                review
                of your application, the New Mexico Health Care Authority-Behavioral Health Services Division (BHSD) is
                denying your application for the following reason:
            </p>

            <p>
                <?php echo $nm_program_deny_reason; ?>
            </p>

            <p>
                Based on this action, you have a right to a fair hearing. Please see the enclosed Fair Hearing Rights to
                request a hearing.
            </p>

            <p style="margin-bottom: 10px;">
                The Behavioral Health Services Division is committed to providing technical assistance and ongoing
                support.
                Should you have further questions or concerns, please contact the email address if the program for which
                you
                applied found here: <a href="https://nmrecovery.org/contacts" target="_blank">Contacts - New Mexico
                    Recovery
                    Project</a>
            </p>

            <p>
                Sincerely,

            </p>

            <p>
                BHSD Clinical Services Team
            </p>
        </section>

        <section>
            <h2 style="text-align: center;">HEARING RIGHTS</h2>

            <p>
                You, or your authorized representative, can ask for a hearing if you do not agree with what we have told
                you
                in this notice. Please send your hearing request to:
            </p>

            <p>
                New Mexico Health Care Authority<br>
                Office of Fair Hearings<br>
                P.O. Box 2348, Santa Fe, NM 87504-2348<br>
                Email: <a href="mailto:HCA-FairHearings@hca.nm.gov">HCA-FairHearings@hca.nm.gov</a><br>
                Fax: (505) 476-6215<br>
                Phone: (505) 476-6213
            </p>

            <h3>TIME LIMIT FOR ASKING FOR HEARING</h3>
            <p>You have 90 days from the date of this notice to ask for a hearing.</p>

            <h3>IF YOU WANT TO ASK FOR A HEARING, PLEASE FILL IN THIS SECTION AND RETURN IT TO THE HCA HEARINGS BUREAU
            </h3>

            <p>
                <strong> I want a hearing.</strong>
            </p>

            <p>
                Signature: _____________________________________________________________
            </p>

            <p>
                Date: ___________________________
            </p>

            <p>
                Name: ________________________________________________________________________
            </p>

            <p>
                Address: ______________________________________________________________________
            </p>

            <p>
                Telephone: ____________________________________________________________________
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