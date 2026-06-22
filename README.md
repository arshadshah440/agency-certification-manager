# Agency Certification Manager

A WordPress plugin for managing agency applications, certifications, and approvals. Built on top of **Formidable Forms Pro** and **Advanced Custom Fields**, it provides a full dashboard, role-based access control, PDF letter generation, and an audit trail for all application activity.

---

## Features

- **Application dashboard** — filterable overview by status, date range, and application type with CSV export
- **Certification letters** — server-side PDF generation via DOMPDF with approve/deny workflow and email delivery
- **Role-based access** — 20+ custom WordPress roles (managers, supervisors, agency admins, program-specific roles)
- **OPRE form (Form 29)** — daily submission limits with 24-hour cooldown per user and admin toggle
- **Agency notes** — persistent per-agency notes with user-scoped editing
- **Audit logging** — field-level change history for every form entry with user attribution
- **E-signature tracking** — integrates with `wp_esign_documents` for document status
- **Shortcodes** — embed dashboards and agency detail pages anywhere in WordPress

---

## Requirements

| Requirement | Version |
|---|---|
| WordPress | 5.8+ |
| PHP | 7.4+ |
| Formidable Forms Pro | Latest |
| Advanced Custom Fields (ACF) | Latest |
| DOMPDF | Bundled |

---

## Installation

1. Upload the `agency-certification-manager` folder to `/wp-content/plugins/`.
2. Activate the plugin from **Plugins → Installed Plugins**.
3. On activation the plugin creates four custom database tables:
   - `wp_nm_entries` — OPRE/Form 29 submission tracking
   - `wp_nm_frm_supplementals` — supplementary form metadata
   - `wp_nm_agency_notes` — agency notes
   - `wp_frm_entry_logs` — full entry change history
4. Configure the plugin at **Settings → NM Applications** (admin logo upload, OPRE limits, etc.).

---

## Configuration

Navigate to **WordPress Admin → Settings → NM Applications** to configure:

| Setting | Description |
|---|---|
| Custom logo | Logo used in generated certification letter PDFs |
| OPRE submission limit | Daily cap for Form 29 (OPRE) submissions per user |
| Form enable/disable | Toggle OPRE form availability site-wide |

---

## Shortcodes

| Shortcode | Description |
|---|---|
| `[nm_applications]` | Main application entry dashboard |
| `[dashboard_agency_render]` | Overview statistics dashboard |
| `[agency_detail_render]` | Individual agency detail page |
| `[agency_details]` | Display agency info block |
| `[current_user_details]` | Display the current logged-in user info |

---

## User Roles

The plugin registers and manages the following roles:

- `nm_manager`
- `supervisor`
- `opre-manager`
- `agency_dashboard`
- `agency_admin`
- `psr_manager`, `ccbhc-manager`, `mct-manager` (program-specific)

Standard WordPress `administrator` accounts have full access.

---

## AJAX Endpoints

All endpoints use WordPress `wp_ajax_*` and require a valid nonce.

| Action | Description |
|---|---|
| `nm_filter_dashboard` | Filter overview dashboard entries |
| `nm_filter_entries` | Filter entries for a specific agency |
| `nm_attachment_status` | Check attachment status for an entry |
| `nm_opre_status` | Update OPRE application status |
| `nm_change_entry_status` | Mark entry as viewed/acknowledged |
| `nm_view_entry_details` | Generate entry details PDF |
| `nm_update_agency_details` | Update agency information |
| `nm_generate_letter_callback` | Generate a certification letter PDF |
| `nm_save_letter_callback` | Persist letter to the database |
| `nm_load_doc_callback` | Load e-signature documents |

---

## Project Structure

```
agency-certification-manager/
├── nm-applications.php                      # Plugin entry point
├── assets/                                  # Frontend CSS/JS (Flatpickr, main styles)
├── dashboard/
│   ├── assets/                              # Dashboard styles, fonts, ui.js
│   ├── components/                          # Reusable PHP UI components
│   │   ├── nm-certification-letter.php
│   │   ├── nm-certification-deny.php
│   │   ├── nm-notes-log.php
│   │   └── nm-sidebar-contact-card.php
│   └── pages/
│       ├── overview-dashboard.php           # Stats & entry listing
│       └── agency-dashboard.php            # Per-agency detail view
├── includes/
│   ├── class-nm-applications-init.php       # Plugin bootstrap & DB setup
│   ├── class-nm-helpers.php                 # Core utility/query functions
│   ├── class-nm-settings.php                # Admin settings page
│   ├── class-nm-ajax-calls.php              # All AJAX handlers
│   ├── class-nm-shortcode.php               # Shortcode registration
│   ├── class-nm-agency-notes.php            # Notes CRUD
│   ├── class-nm-create-post-on-register.php # User/post creation on form submit
│   ├── class-formidable-entries-cache.php   # Entry query caching
│   ├── formidable/
│   │   ├── class-nm-auth-helpers.php        # Authentication helpers
│   │   └── class-nm-applications-forms.php  # Form hooks & setup
│   └── logger/
│       └── class-nm-formidable-logger.php   # Entry change audit logger
├── shortcode/
│   └── class-nm-agency-shortcodes.php
└── templates/
    └── nm-dashboard-manager.php             # Main dashboard template
```

---

## Frontend Dependencies

Loaded via CDN or bundled in `assets/`:

- [Flatpickr](https://flatpickr.js.org/) — date range picker
- [html2canvas](https://html2canvas.hertzen.com/) + [jsPDF](https://github.com/parallax/jsPDF) / [html2pdf.js](https://github.com/eKoopmans/html2pdf.js) — client-side PDF export
- [Font Awesome 6](https://fontawesome.com/) — icons
- [Lucide Icons](https://lucide.dev/) + [Iconoir](https://iconoir.com/) — additional icon sets
- Custom fonts: Helvetica Neue, Neue Haas Grotesk Display Pro

---

## Security

- All AJAX handlers verify a WordPress nonce before processing.
- Capability checks (`current_user_can`) gate every sensitive operation.
- Database queries use `$wpdb->prepare()` throughout.
- Output is escaped with `esc_html`, `esc_attr`, and `wp_kses` before rendering.

---

## License

Proprietary — NM Applications. All rights reserved.
