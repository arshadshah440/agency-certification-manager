<?php
if (!defined('ABSPATH')) {
    exit;
}

/*
 * Resolve the numeric agency post ID.
 * On agency pages: $agency_id is the post ID (set in agency-dashboard.php scope).
 * On user pages:   $agency_id is empty but $user_id is set — find their agency post.
 */
$_notes_raw_agency = isset($agency_id) ? $agency_id : '';
$_notes_raw_user   = isset($user_id)   ? $user_id   : '';

$_notes_agency_id = 0;

if (!empty($_notes_raw_agency)) {
    $_notes_agency_id = absint($_notes_raw_agency);
} elseif (!empty($_notes_raw_user)) {
    // Fallback: use the WP user ID as a proxy key so notes persist per user
    // Store agency_id = 0 marker + user_id in a negative-safe way:
    // We use a synthetic ID = user_id value stored in agency_id column with a
    // convention prefix. Simplest approach: store user_id directly as agency_id
    // since this page is uniquely identified by it.
    $_notes_agency_id = absint($_notes_raw_user) * -1; // negative = user-based
    // Actually keep it simple: just use absint user_id — the column is UNSIGNED
    // so store it as-is; it won't clash because agency posts have different IDs.
    $_notes_agency_id = absint($_notes_raw_user);
}

$_notes_current_uid = get_current_user_id();
$_notes_user_obj    = wp_get_current_user();
$_can_write         = (bool) array_intersect(
    ['administrator', 'supervisor', 'nm_manager'],
    (array) $_notes_user_obj->roles
);
$_notes_list = $_notes_agency_id
    ? NM_Agency_Notes::get_notes_for_agency($_notes_agency_id)
    : [];
?>

<div class="nm-notes-header">
    <h2 class="blue-header">NOTE LOG</h2>
    <?php if ($_can_write): ?>
        <button class="nm-note-btn nm-note-btn-add" id="nm_add_note_btn"
                data-agency="<?php echo esc_attr($_notes_agency_id); ?>">
            <i class="fa-solid fa-plus"></i> Add Note
        </button>
    <?php endif; ?>
</div>

<div class="timeline nm-notes-list" id="nm_notes_list"
     data-agency="<?php echo esc_attr($_notes_agency_id); ?>">
    <?php if (empty($_notes_list)): ?>
        <p class="nm-no-notes">No notes yet.</p>
    <?php else: 
        $display_count = min(3, count($_notes_list));
        for ($i = 0; $i < $display_count; $i++):
            $note = $_notes_list[$i];
            $is_own = (int) $note['added_by'] === (int) $_notes_current_uid; ?>
        <div class="nm-note-item" data-note-id="<?php echo esc_attr($note['id']); ?>">
            <div class="content nm-note-content">
                <div class="nm-note-row-top">
                    <span class="nm-note-title log-text"><?php echo esc_html($note['note_title']); ?></span>
                    <div class="nm-note-actions">
                        <?php if ($is_own && $_can_write): ?>
                            <button class="nm-note-icon-btn nm-note-edit"
                                    title="Edit"
                                    data-id="<?php echo esc_attr($note['id']); ?>"
                                    data-title="<?php echo esc_attr($note['note_title']); ?>"
                                    data-text="<?php echo esc_attr($note['note_text']); ?>">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </button>
                            <button class="nm-note-icon-btn nm-note-delete"
                                    title="Delete"
                                    data-id="<?php echo esc_attr($note['id']); ?>">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        <?php endif; ?>
                        <button class="nm-note-icon-btn nm-note-view"
                                title="View"
                                data-id="<?php echo esc_attr($note['id']); ?>"
                                data-title="<?php echo esc_attr($note['note_title']); ?>"
                                data-text="<?php echo esc_attr($note['note_text']); ?>"
                                data-user="<?php echo esc_attr($note['added_by_name']); ?>"
                                data-date="<?php echo esc_attr(date('M j, Y g:i A', strtotime($note['created_at']))); ?>">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>
                <p class="log-date-top">
                    <i class="fa-regular fa-clock"></i>
                    <?php echo esc_html(date('M j, Y g:i A', strtotime($note['created_at']))); ?>
                    <span class="nm-note-by-inline">
                        <i class="fa-regular fa-user"></i>
                        <?php echo esc_html($note['added_by_name']); ?>
                    </span>
                </p>
            </div>
        </div>
    <?php endfor; 
    
    if (count($_notes_list) > 3): ?>
        <div class="nm-see-all-notes">
            <button class="nm-see-all-notes-btn" id="nm_see_all_notes_btn">
                See All (<?php echo count($_notes_list); ?>)
            </button>
        </div>
    <?php endif; 
    endif; ?>
</div>

<!-- Add / Edit Note Modal -->
<div class="nm_modal_wrapper nm-note-modal-wrapper" id="nm_note_form_modal" style="display:none;">
    <div class="nm-modal-overlay">
        <div class="nm-modal" role="dialog" aria-modal="true">
            <div class="nm-modal-header">
                <h2 id="nm_note_modal_title" style="margin-bottom:0px;">Add Note</h2>
                <button class="nm-modal-close" id="nm_note_modal_close" aria-label="Close">×</button>
            </div>
            <div class="nm-modal-body">
                <input type="hidden" id="nm_note_id" value="">
                <input type="hidden" id="nm_note_agency_id" value="<?php echo esc_attr($_notes_agency_id); ?>">
                <div class="full-width-row" style="margin-bottom:14px;">
                    <label class="field-label-small" for="nm_note_title_input">Title</label>
                    <input type="text" id="nm_note_title_input" placeholder="Note title…">
                </div>
                <div class="full-width-row">
                    <label class="field-label-small" for="nm_note_text_input">Note</label>
                    <textarea id="nm_note_text_input" placeholder="Write your note…" style="height:120px;resize:vertical;"></textarea>
                </div>
            </div>
            <div class="nm-modal-footer">
                <button class="nm-btn nm-btn-secondary" id="nm_note_form_cancel">Cancel</button>
                <button class="nm-btn nm-btn-primary" id="nm_note_form_save">Save Note</button>
            </div>
        </div>
    </div>
</div>

<!-- View Note Modal -->
<div class="nm_modal_wrapper nm-note-modal-wrapper" id="nm_note_view_modal" style="display:none;">
    <div class="nm-modal-overlay">
        <div class="nm-modal" role="dialog" aria-modal="true">
            <div class="nm-modal-header">
                <h2 id="nm_view_note_title" style="margin-bottom:0px;">Note</h2>
                <button class="nm-modal-close" id="nm_view_note_modal_close" aria-label="Close">×</button>
            </div>
            <div class="nm-modal-body">
                <p id="nm_view_note_text" style="font-size:13px;line-height:1.6;white-space:pre-wrap;"></p>
                <hr class="divider" style="margin:16px 0;">
                <p style="font-size:11px;color:#666;">
                    <i class="fa-regular fa-user"></i> <span id="nm_view_note_user"></span>
                    &nbsp;&nbsp;
                    <i class="fa-regular fa-clock"></i> <span id="nm_view_note_date"></span>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="nm_modal_wrapper nm-note-modal-wrapper" id="nm_note_delete_modal" style="display:none;">
    <div class="nm-modal-overlay">
        <div class="nm-modal" role="dialog" aria-modal="true">
            <div class="nm-modal-header">
                <h2>Delete Note</h2>
                <button class="nm-modal-close nm-note-delete-cancel" aria-label="Close">×</button>
            </div>
            <div class="nm-modal-body">Are you sure you want to delete this note?</div>
            <div class="nm-modal-footer">
                <button class="nm-btn nm-btn-secondary nm-note-delete-cancel">No</button>
                <button class="nm-btn nm-btn-primary" id="nm_note_delete_confirm">Yes, Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- See All Notes Modal -->
<div class="nm_modal_wrapper nm-note-modal-wrapper" id="nm_all_notes_modal" style="display:none;">
    <div class="nm-modal-overlay">
        <div class="nm-modal" role="dialog" aria-modal="true">
            <div class="nm-modal-header">
                <div>
                    <h2 style="margin-bottom:4px;">All Notes</h2>
                    <p id="nm_all_notes_agency" style="font-size:12px;color:#ffffff;margin:0;"></p>
                </div>
                <button class="nm-modal-close" id="nm_all_notes_close" aria-label="Close">×</button>
            </div>
            <div class="nm-modal-body">
                <div class="nm-notes-list-modal" id="nm_all_notes_list">
                    <div class="loader_nm hide_this_loader_nm">
                        <img src="<?php echo NM_APPS_URL . '/assets/Loading.gif'; ?>" alt="">
                    </div>
                </div>
            </div>
            <div class="nm-modal-footer">
                <div class="nm-notes-pagination" id="nm_notes_pagination"></div>
                <button class="nm-btn nm-btn-primary" id="nm_back_to_all_notes" style="display:none; margin: 0 auto;"><i class="fa-solid fa-arrow-left" style="margin-right:6px;"></i>Back to All Notes</button>
            </div>
        </div>
    </div>
</div>
