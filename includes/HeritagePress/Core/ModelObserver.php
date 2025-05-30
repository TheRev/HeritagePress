<?php
namespace HeritagePress\Core;

/**
 * Model Observer Interface
 * 
 * Implement this interface to create observers that react to model events
 */
interface ModelObserver {
    public function creating($model): void;
    public function created($model): void;
    public function updating($model): void;
    public function updated($old_model, $new_model): void; // Changed signature
    public function deleting($model): void;
    public function deleted($model): void;
}
