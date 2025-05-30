<?php
namespace HeritagePress\Core;

use HeritagePress\Database\Database_Manager;

// Define the ModelObserver interface if it's not already defined elsewhere
if (!interface_exists(__NAMESPACE__ . '\ModelObserver')) {
    interface ModelObserver {
        public function creating($model): void;
        public function created($model): void;
        public function updating($model): void; // Remains for potential pre-update logic
        public function updated($old_model, $new_model): void; // Signature changed
        public function deleting($model): void;
        public function deleted($model): void;
        public function restored($model): void;
        public function force_deleting($model): void;
    }
}

/**
 * Audit Logger Observer
 * 
 * Logs all model changes for auditing purposes
 */
class Audit_Log_Observer implements ModelObserver {
    private $wpdb;
    private $audit_table_name;

    // Constructor updated to accept dependencies
    public function __construct($wpdb, $audit_table_name) {
        $this->wpdb = $wpdb;
        $this->audit_table_name = $audit_table_name;
    }

    public function creating($model): void {
        // "creating" is before the ID is assigned. 
        // We could log an "ATTEMPT_CREATE" here if desired.
    }

    public function created($model): void {
        // For CREATE, changed_fields contains the full new model data
        $this->log_to_database('CREATE', $model, $this->get_model_data_for_log($model));
    }

    public function updating($model): void {
        // This hook is called before the update operation.
        // It could be used to store the original state if the model itself tracked changes,
        // but in our current repository pattern, we fetch the old state separately.
    }    // Updated method to handle diff for UPDATE actions
    public function updated($old_model, $new_model): void {
        $old_data = $this->get_model_data_for_log($old_model);
        $new_data = $this->get_model_data_for_log($new_model);
        $diff = [];

        // Check for changed and new fields
        foreach ($new_data as $key => $new_value) {
            $old_value = array_key_exists($key, $old_data) ? $old_data[$key] : null;
            if (!array_key_exists($key, $old_data) || $new_value !== $old_value) {
                // Explicitly check for common false positive changes like 'updated_at' if needed,
                // but for a detailed audit, logging all changes is often preferred.
                // Skip if both are null, or if old_value was not set and new_value is null (no actual change)
                if (is_null($old_value) && !array_key_exists($key, $old_data) && is_null($new_value)) {
                    continue;
                }
                $diff[$key] = [
                    'old' => $old_value,
                    'new' => $new_value,
                ];
            }
        }

        // Check for removed fields (in old but not in new)
        foreach ($old_data as $key => $old_value) {
            if (!array_key_exists($key, $new_data)) {
                $diff[$key] = [
                    'old' => $old_value,
                    'new' => null, // Indicates the field was removed or set to null
                ];
            }
        }
        
        // Only log if there are actual changes.
        // The $new_model is passed for context (ID, UUID etc.)
        if (!empty($diff)) {
            $this->log_to_database('UPDATE', $new_model, $diff);
        } else {
            // Optionally, log an "UPDATE_NO_CHANGES" event or simply do nothing.
            // For now, if no diff, no audit log entry for update.
        }
    }

    public function deleting($model): void {
        $this->log_to_database('DELETE', $model, $this->get_model_data_for_log($model));
    }

    public function deleted($model): void {
        // This event fires after deletion. 
    }
    
    public function restored($model): void {
        // For RESTORE, changed_fields could show the state of the restored model.
        $this->log_to_database('RESTORE', $model, $this->get_model_data_for_log($model));
    }

    public function force_deleting($model): void {
        $this->log_to_database('FORCE_DELETE', $model, $this->get_model_data_for_log($model));
    }

    private function get_model_data_for_log($model): ?array {
        if (method_exists($model, 'toArray')) {
            return $model->toArray();
        }
        if (method_exists($model, 'get_attributes')) { // Common in some ORM-like patterns
            return $model->get_attributes();
        }
        return (array) $model; // Fallback
    }

    private function log_to_database(string $action, $model, ?array $changed_data_override = null): void {
        if (!$this->wpdb || !$this->audit_table_name) {
            error_log('AuditLogObserver: wpdb or audit_table_name not initialized.');
            return; 
        }

        $user_id = get_current_user_id(); 
        $ip_address = $this->get_ip_address();
        
        $entity_table = null;
        if (method_exists($model, 'get_table_name_for_audit')) {
            $entity_table = $model->get_table_name_for_audit();
        } elseif (property_exists($model, 'table_name_for_audit')) {
            $entity_table = $model->table_name_for_audit;
        } else {
            // Fallback: try to guess from class name (needs refinement)
            $class_name_parts = explode('\\', get_class($model));
            $base_class_name = end($class_name_parts);
            // Basic pluralization and snake_case
            $entity_table = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $base_class_name));
            if (!preg_match('/s$/i', $entity_table)) { // Basic plural 's'
                 $entity_table .= 's';
            }
        }
        // Ensure table name is just the suffix, not the full prefixed name
        $prefix_to_remove = Database_Manager::get_table_prefix();
        if ($entity_table && strpos($entity_table, $prefix_to_remove) === 0) {
            $entity_table = substr($entity_table, strlen($prefix_to_remove));
        }


        $entity_id = $model->id ?? (method_exists($model, 'get_id') ? $model->get_id() : null);
        $entity_uuid = $model->uuid ?? (method_exists($model, 'get_uuid') ? $model->get_uuid() : null);
        $file_id = $model->file_id ?? (method_exists($model, 'get_file_id') ? $model->get_file_id() : null);

        $changed_fields_payload = null;
        if ($changed_data_override !== null) {
            $changed_fields_payload = $changed_data_override;
        // For CREATE, UPDATE, RESTORE, DELETE, FORCE_DELETE, $changed_data_override provides the relevant data.
        // No need for further fallbacks if $changed_data_override is the primary source for these actions.
        } elseif ($action === 'CREATE' || $action === 'RESTORE') { // Default for create/restore if no override
             $changed_fields_payload = $this->get_model_data_for_log($model);
        }
        // For other actions or if specific logic is needed, it can be added here.
        // The current setup relies on $changed_data_override being correctly passed.
        
        $description = sprintf('%s on %s (ID: %s)', $action, is_object($model) ? get_class($model) : 'UnknownEntity', $entity_id ?? 'N/A');

        $insert_result = $this->wpdb->insert(
            $this->audit_table_name,
            [
                'file_id' => $file_id,
                'user_id' => $user_id,
                'action' => $action,
                'entity_table' => $entity_table,
                'entity_id' => $entity_id,
                'entity_uuid' => $entity_uuid,
                'changed_fields' => $changed_fields_payload ? wp_json_encode($changed_fields_payload) : null,
                'description' => $description,
                'ip_address' => $ip_address,
                'timestamp' => current_time('mysql', 1), // GMT timestamp
            ],
            [
                '%s', // file_id
                '%d', // user_id
                '%s', // action
                '%s', // entity_table
                '%d', // entity_id (allow null if not yet set, though %d might coerce to 0)
                '%s', // entity_uuid
                '%s', // changed_fields (JSON string)
                '%s', // description
                '%s', // ip_address
                '%s', // timestamp
            ]
        );

        if ($insert_result === false) {
            error_log('AuditLogObserver: Failed to insert audit log. DB Error: ' . $this->wpdb->last_error);
        }
    }
    
    private function get_ip_address(): ?string {
        $ip_keys = [
            'HTTP_CLIENT_IP', 
            'HTTP_X_FORWARDED_FOR', 
            'HTTP_X_FORWARDED', 
            'HTTP_X_CLUSTER_CLIENT_IP', 
            'HTTP_FORWARDED_FOR', 
            'HTTP_FORWARDED', 
            'REMOTE_ADDR'
        ];
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return sanitize_text_field($ip);
                    }                }
            }
        }
        return isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : null;
    }
}
