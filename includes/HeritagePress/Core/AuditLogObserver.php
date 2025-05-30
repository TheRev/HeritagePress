<?php
namespace HeritagePress\Core;

/**
 * Audit Logger Observer
 * 
 * Logs all model changes for auditing purposes
 */
class AuditLogObserver implements ModelObserver {
    public function creating($model): void {
        $this->logEvent('creating', $model);
    }

    public function created($model): void {
        $this->logEvent('created', $model);
    }

    public function updating($model): void {
        $this->logEvent('updating', $model);
    }

    public function updated($model): void {
        $this->logEvent('updated', $model);
    }

    public function deleting($model): void {
        $this->logEvent('deleting', $model);
    }

    public function deleted($model): void {
        $this->logEvent('deleted', $model);
    }

    private function logEvent(string $event, $model): void {
        $user_id = get_current_user_id();
        $model_class = get_class($model);
        $model_id = $model->id ?? 'new';
        $data = json_encode($model->toArray());

        error_log(sprintf(
            'AUDIT: [%s] User %d performed %s on %s (ID: %s) with data: %s',
            current_time('mysql'),
            $user_id,
            $event,
            $model_class,
            $model_id,
            $data
        ));
    }
}
