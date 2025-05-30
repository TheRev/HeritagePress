<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div id="heritage-press-media-import-progress" class="heritage-press-progress-container">
        <div class="progress-status">
            <div class="progress-bar">
                <div class="progress-fill" style="width: 0%;">
                    <span class="progress-text">0%</span>
                </div>
            </div>
            <div class="progress-stats">
                <span class="files-processed">0</span> / <span class="files-total">0</span> files processed
            </div>
        </div>

        <div class="progress-details hidden">
            <h3>Import Details</h3>
            <div class="import-summary">
                <p>Started: <span class="import-start-time"></span></p>
                <p>Status: <span class="import-status"></span></p>
                <p>Processing Rate: <span class="processing-rate"></span> files/second</p>
                <p>Estimated Time Remaining: <span class="time-remaining"></span></p>
            </div>
        </div>

        <div class="error-list hidden">
            <h3>Failed Imports</h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>File</th>
                        <th>Error</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>

        <div class="progress-actions">
            <button type="button" class="button button-primary pause-import hidden">Pause Import</button>
            <button type="button" class="button button-primary resume-import hidden">Resume Import</button>
            <button type="button" class="button button-secondary cancel-import">Cancel Import</button>
        </div>
    </div>
</div>
