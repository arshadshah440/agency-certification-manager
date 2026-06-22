<?php
/**
 * High-Performance Caching System for Formidable Entries
 * 
 * Features:
 * 1. Multi-layer caching (Memory → WP Object Cache → Transients)
 * 2. Cache warming on page load
 * 3. Smart cache invalidation
 * 4. Automatic cache refresh
 * 5. Query result caching
 */

class Formidable_Entries_Cache {
    
    // Cache configuration
    const CACHE_VERSION = '1.0';
    const CACHE_DURATION = 3600; // 1 hour
    const CACHE_GROUP = 'formidable_entries';
    const MEMORY_CACHE_KEY = 'frm_entries_memory_cache';
    
    // Static memory cache (fastest)
    private static $memory_cache = [];
    
    /**
     * Initialize caching system on plugin/theme load
     * Hook this to 'init' or 'wp_loaded' action
     */
    public static function init() {
        // Warm cache on admin pages
        if (is_admin()) {
            add_action('admin_init', [__CLASS__, 'warm_cache'], 5);
        }
        
        // Warm cache on specific page loads
        add_action('wp_loaded', [__CLASS__, 'maybe_warm_cache'], 5);
        
        // Invalidate cache on entry updates
        add_action('frm_after_create_entry', [__CLASS__, 'invalidate_entry_cache'], 10, 2);
        add_action('frm_after_update_entry', [__CLASS__, 'invalidate_entry_cache'], 10, 2);
        add_action('frm_before_destroy_entry', [__CLASS__, 'invalidate_entry_cache'], 10, 1);
        
        // Invalidate cache on document status changes
        add_action('esign_document_status_changed', [__CLASS__, 'invalidate_document_cache'], 10, 2);
    }
    
    /**
     * Warm cache - preload all frequently accessed data
     */
    public static function warm_cache() {
        // Check if cache warming is needed
        $last_warmed = get_transient('frm_cache_last_warmed');
        if ($last_warmed && (time() - $last_warmed) < 300) { // Don't warm more than once per 5 minutes
            return;
        }
        
        global $wpdb;
        
        // Get all active form IDs
        $form_ids = wp_cache_get('frm_active_form_ids', self::CACHE_GROUP);
        if (false === $form_ids) {
            $form_ids = $wpdb->get_col("
                SELECT DISTINCT form_id 
                FROM {$wpdb->prefix}frm_items 
                WHERE is_draft = 0
            ");
            wp_cache_set('frm_active_form_ids', $form_ids, self::CACHE_GROUP, self::CACHE_DURATION);
        }
        
        // Preload all entries data
        self::preload_all_entries($form_ids);
        
        // Preload all document details
        self::preload_all_documents();
        
        // Preload all agency fields
        self::preload_agency_fields($form_ids);
        
        // Preload form details
        self::preload_forms($form_ids);
        
        set_transient('frm_cache_last_warmed', time(), 300);
    }
    
    /**
     * Conditionally warm cache based on context
     */
    public static function maybe_warm_cache() {
        // Only warm cache if user is logged in and on specific pages
        if (!is_user_logged_in()) {
            return;
        }
        
        // Check if we're on a page that uses the entries data
        $current_page = $_GET['page'] ?? '';
        $warm_pages = ['your-entries-page', 'your-dashboard-page']; // Add your page slugs
        
        if (in_array($current_page, $warm_pages)) {
            self::warm_cache();
        }
    }
    
    /**
     * Preload all entries data into cache
     */
    private static function preload_all_entries($form_ids) {
        if (empty($form_ids)) {
            return;
        }
        
        global $wpdb;
        $form_ids_str = implode(',', array_map('intval', $form_ids));
        
        // Load all entries with essential data
        $entries = $wpdb->get_results("
            SELECT 
                it.id,
                it.form_id,
                it.created_at,
                it.updated_at
            FROM {$wpdb->prefix}frm_items it
            WHERE it.form_id IN ($form_ids_str)
                AND it.is_draft = 0
            ORDER BY it.created_at DESC
        ", ARRAY_A);
        
        // Cache by form_id for quick filtering
        $entries_by_form = [];
        foreach ($entries as $entry) {
            $entries_by_form[$entry['form_id']][] = $entry;
        }
        
        // Store in cache
        foreach ($entries_by_form as $form_id => $form_entries) {
            wp_cache_set("entries_form_{$form_id}", $form_entries, self::CACHE_GROUP, self::CACHE_DURATION);
        }
        
        // Store complete index
        wp_cache_set('all_entries_index', $entries, self::CACHE_GROUP, self::CACHE_DURATION);
    }
    
    /**
     * Preload all document details
     */
    private static function preload_all_documents() {
        global $wpdb;
        
        // Load all esign documents
        $esign_docs = $wpdb->get_results("
            SELECT 
                em.meta_value as entry_id,
                ed.document_id,
                ed.document_title,
                ed.document_status,
                ed.date_created,
                ed.document_checksum,
                ei.invite_hash,
                CASE 
                    WHEN ed.document_status = 'awaiting' THEN 'submitted'
                    WHEN ed.document_status = 'signed' THEN 'acknowledged'
                    ELSE ed.document_status
                END as normalized_status
            FROM {$wpdb->prefix}esign_documents_meta em
            INNER JOIN {$wpdb->prefix}esign_documents ed ON ed.document_id = em.document_id
            LEFT JOIN {$wpdb->prefix}esign_invitations ei ON ei.document_id = ed.document_id
            WHERE em.meta_key = 'esig_formidable_entry_id'
                AND ed.document_type = 'normal'
                AND ed.document_status NOT IN ('trash', 'duplicate')
        ", ARRAY_A);
        
        // Index by entry_id, keep only latest document
        $docs_by_entry = [];
        foreach ($esign_docs as $doc) {
            $entry_id = $doc['entry_id'];
            if (!isset($docs_by_entry[$entry_id])) {
                $docs_by_entry[$entry_id] = $doc;
            }
        }
        
        // Load all nm_entries
        $nm_entries = $wpdb->get_results("
            SELECT entry_id, entry_status
            FROM {$wpdb->prefix}nm_entries
        ", ARRAY_A);
        
        // Override with nm_entries status (takes precedence)
        foreach ($nm_entries as $nm) {
            $entry_id = $nm['entry_id'];
            if (!isset($docs_by_entry[$entry_id])) {
                $docs_by_entry[$entry_id] = [
                    'entry_id' => $entry_id,
                    'document_id' => null,
                    'document_title' => null,
                    'document_status' => $nm['entry_status'],
                    'normalized_status' => $nm['entry_status'],
                    'date_created' => null,
                    'document_checksum' => null,
                    'invite_hash' => null,
                ];
            } else {
                $docs_by_entry[$entry_id]['document_status'] = $nm['entry_status'];
                $docs_by_entry[$entry_id]['normalized_status'] = $nm['entry_status'];
            }
        }
        
        wp_cache_set('all_documents', $docs_by_entry, self::CACHE_GROUP, self::CACHE_DURATION);
    }
    
    /**
     * Preload agency fields
     */
    private static function preload_agency_fields($form_ids) {
        if (empty($form_ids)) {
            return;
        }
        
        global $wpdb;
        $form_ids_str = implode(',', array_map('intval', $form_ids));
        
        // Load agency field IDs
        $agency_fields = $wpdb->get_results("
            SELECT id, form_id, field_key
            FROM {$wpdb->prefix}frm_fields
            WHERE field_key LIKE '%agency_id%'
                AND form_id IN ($form_ids_str)
        ", ARRAY_A);
        
        $fields_by_form = [];
        $field_ids = [];
        foreach ($agency_fields as $field) {
            $fields_by_form[$field['form_id']][] = $field['id'];
            $field_ids[] = $field['id'];
        }
        
        wp_cache_set('agency_fields_by_form', $fields_by_form, self::CACHE_GROUP, self::CACHE_DURATION);
        wp_cache_set('all_agency_field_ids', $field_ids, self::CACHE_GROUP, self::CACHE_DURATION);
        
        // Load all agency meta values
        if (!empty($field_ids)) {
            $field_ids_str = implode(',', $field_ids);
            $agency_metas = $wpdb->get_results("
                SELECT item_id, field_id, meta_value
                FROM {$wpdb->prefix}frm_item_metas
                WHERE field_id IN ($field_ids_str)
                    AND CAST(meta_value AS UNSIGNED) > 0
            ", ARRAY_A);
            
            $metas_by_entry = [];
            foreach ($agency_metas as $meta) {
                $metas_by_entry[$meta['item_id']] = $meta['meta_value'];
            }
            
            wp_cache_set('agency_metas', $metas_by_entry, self::CACHE_GROUP, self::CACHE_DURATION);
        }
    }
    
    /**
     * Preload form details
     */
    private static function preload_forms($form_ids) {
        if (empty($form_ids)) {
            return;
        }
        
        $forms = [];
        foreach ($form_ids as $form_id) {
            $form = FrmForm::getOne($form_id);
            if ($form) {
                $forms[$form_id] = [
                    'id' => $form->id,
                    'form_key' => $form->form_key,
                    'name' => $form->name,
                ];
            }
        }
        
        wp_cache_set('all_forms', $forms, self::CACHE_GROUP, self::CACHE_DURATION);
    }
    
    /**
     * Invalidate cache when entry changes
     */
    public static function invalidate_entry_cache($entry_id, $form_id = null) {
        // Clear all related caches
        wp_cache_delete('all_entries_index', self::CACHE_GROUP);
        
        if ($form_id) {
            wp_cache_delete("entries_form_{$form_id}", self::CACHE_GROUP);
        }
        
        // Clear memory cache
        self::$memory_cache = [];
        
        // Force cache rewarm on next request
        delete_transient('frm_cache_last_warmed');
    }
    
    /**
     * Invalidate document cache
     */
    public static function invalidate_document_cache($document_id, $entry_id = null) {
        wp_cache_delete('all_documents', self::CACHE_GROUP);
        self::$memory_cache = [];
        delete_transient('frm_cache_last_warmed');
    }
    
}

// Initialize the caching system
add_action('init', ['Formidable_Entries_Cache', 'init']);