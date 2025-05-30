<?php
/**
 * Model Observer Interface
 */

namespace HeritagePress\Core;

/**
 * Interface ModelObserver
 */
interface ModelObserver {
    public function created($model);
    public function updated($model, $changes);
    public function deleted($model);
}
