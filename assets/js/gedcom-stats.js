/**
 * Update GEDCOM statistics on step 2 validation page
 */
jQuery(document).ready(function ($) {
    // Check if we're on the step 2 page and have a file key
    const urlParams = new URLSearchParams(window.location.search);
    const fileKey = urlParams.get('file');

    if (fileKey && urlParams.get('step') === '2') {
        // Show loading message
        $('.hp-data-table tbody').prepend('<tr id="loading-stats"><td colspan="2" style="text-align: center; font-style: italic;">Loading real GEDCOM data...</td></tr>');

        // Fetch real statistics
        $.ajax({
            url: hp_vars.ajaxurl,
            type: 'POST',
            data: {
                action: 'hp_get_gedcom_stats',
                file_key: fileKey,
                nonce: hp_vars.nonce
            },
            success: function (response) {
                $('#loading-stats').remove();

                if (response.success) {
                    const stats = response.data;

                    // Update statistics in the table
                    updateStatCell('Individuals', stats.individuals);
                    updateStatCell('Families', stats.families);
                    updateStatCell('Sources', stats.sources);
                    updateStatCell('Media Objects', stats.media);
                    updateStatCell('Notes', stats.notes);
                    updateStatCell('Repositories', stats.repositories);

                    // Update GEDCOM version if we have the cell
                    updateStatCell('GEDCOM Version', stats.gedcom_version);
                    updateStatCell('Encoding', stats.encoding);
                    updateStatCell('Source System', stats.source_system);

                    // Add additional info
                    const totalRecords = stats.total_records;
                    if (totalRecords > 0) {
                        $('.hp-validation-summary').append(
                            '<div style="margin-top: 15px; padding: 10px; background: #f0f8ff; border-left: 4px solid #0073aa;">' +
                            '<strong>File Analysis:</strong><br>' +
                            'Total Records: ' + totalRecords + '<br>' +
                            'Events: ' + stats.events + '<br>' +
                            'File Size: ' + formatFileSize(stats.file_size) + '<br>' +
                            'Total Lines: ' + stats.total_lines +
                            '</div>'
                        );
                    }

                    console.log('GEDCOM stats loaded:', stats);
                } else {
                    console.error('Failed to load GEDCOM stats:', response.data);
                    $('.hp-data-table tbody').append('<tr><td colspan="2" style="color: red; text-align: center;">Failed to load real statistics: ' + response.data + '</td></tr>');
                }
            },
            error: function (xhr, status, error) {
                $('#loading-stats').remove();
                console.error('AJAX error loading GEDCOM stats:', error);
                $('.hp-data-table tbody').append('<tr><td colspan="2" style="color: red; text-align: center;">Error loading statistics</td></tr>');
            }
        });
    }

    function updateStatCell(label, value) {
        $('.hp-data-table tbody tr').each(function () {
            const $row = $(this);
            const $th = $row.find('th');
            if ($th.text().trim() === label) {
                $row.find('td').html(value);
                $row.find('td').css('font-weight', 'bold');
                return false; // break the loop
            }
        });
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';

        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
});
