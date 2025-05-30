/**
 * Heritage Press Admin JavaScript
 *
 * @package HeritagePress
 */

(function($) {
    'use strict';    // Document ready
    $(document).ready(function() {
        
        // Initialize admin features
        initGedcomImport();
        initSearchForms();
        initConfirmDialogs();
        initTooltips();
        initIndividualsPage();
        initModal();
        
    });

    /**
     * Initialize GEDCOM import functionality
     */
    function initGedcomImport() {
        var $form = $('#gedcom-upload-form');
        var $progress = $('#import-progress');
        var $spinner = $('#import-spinner');
        
        if ($form.length) {
            $form.on('submit', function(e) {
                e.preventDefault();
                
                var fileInput = $('#gedcom_file')[0];
                if (!fileInput.files || !fileInput.files[0]) {
                    alert(heritage_press_admin.messages.no_file_selected);
                    return;
                }
                
                var file = fileInput.files[0];
                var maxSize = 32 * 1024 * 1024; // 32MB
                
                if (file.size > maxSize) {
                    alert(heritage_press_admin.messages.file_too_large);
                    return;
                }
                
                if (!isValidGedcomFile(file.name)) {
                    alert(heritage_press_admin.messages.invalid_file_type);
                    return;
                }
                
                // Start import process
                startImport();
            });
        }
        
        function isValidGedcomFile(filename) {
            var validExtensions = ['.ged', '.gedcom'];
            var extension = filename.toLowerCase().substring(filename.lastIndexOf('.'));
            return validExtensions.indexOf(extension) !== -1;
        }
        
        function startImport() {
            $spinner.addClass('is-active');
            $progress.show();
            $form.addClass('loading');
            
            // Simulate progress for demo
            // In real implementation, this would be AJAX-based with server progress updates
            var progress = 0;
            var stages = [
                heritage_press_admin.messages.reading_file,
                heritage_press_admin.messages.parsing_data,
                heritage_press_admin.messages.importing_individuals,
                heritage_press_admin.messages.importing_families,
                heritage_press_admin.messages.finalizing_import
            ];
            var currentStage = 0;
            
            var progressInterval = setInterval(function() {
                progress += Math.random() * 15 + 5;
                
                if (progress > 100) {
                    progress = 100;
                    clearInterval(progressInterval);
                    completeImport();
                }
                
                // Update stage
                var stageProgress = Math.floor((progress / 100) * stages.length);
                if (stageProgress > currentStage && stageProgress < stages.length) {
                    currentStage = stageProgress;
                    $('.progress-text').text(stages[currentStage]);
                }
                
                $('.progress-fill').css('width', progress + '%');
            }, 800);
        }
        
        function completeImport() {
            $('.progress-fill').css('width', '100%');
            $('.progress-text').text(heritage_press_admin.messages.import_complete);
            $spinner.removeClass('is-active');
            $form.removeClass('loading');
            
            // Show success message
            setTimeout(function() {
                alert(heritage_press_admin.messages.import_success);
                // In real implementation, redirect to results page
                // window.location.href = heritage_press_admin.admin_url + 'admin.php?page=heritage-press';
            }, 1000);
        }
    }    /**
     * Initialize search forms
     */
    function initSearchForms() {
        $('.search-form').each(function() {
            var $form = $(this);
            var $input = $form.find('input[type="search"]');
            var searchType = $form.data('search-type') || 'individuals';
            
            // Prevent default form submission
            $form.on('submit', function(e) {
                e.preventDefault();
                performSearch($form, searchType);
            });
            
            // Auto-search on input with debouncing
            var searchTimeout;
            $input.on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    performSearch($form, searchType);
                }, 500);
            });
            
            // Clear search button
            $form.find('.clear-search').on('click', function(e) {
                e.preventDefault();
                $input.val('');
                performSearch($form, searchType);
            });
        });
    }

    /**
     * Perform AJAX search
     */
    function performSearch($form, searchType) {
        var searchTerm = $form.find('input[type="search"]').val();
        var $resultsContainer = $('#search-results');
        var $spinner = $form.find('.spinner');
        
        if (!$resultsContainer.length) {
            $resultsContainer = $('.individuals-list, .families-list').first();
        }
        
        $spinner.addClass('is-active');
        
        var ajaxAction = 'heritage_press_search_' + searchType;
        
        $.ajax({
            url: heritage_press_admin.ajax_url,
            type: 'POST',
            data: {
                action: ajaxAction,
                search: searchTerm,
                page: 1,
                per_page: 20,
                nonce: heritage_press_admin.nonce
            },
            success: function(response) {
                $spinner.removeClass('is-active');
                
                if (response.success) {
                    if (searchType === 'individuals') {
                        updateIndividualsList(response.data);
                    } else if (searchType === 'families') {
                        updateFamiliesList(response.data);
                    }
                } else {
                    console.error('Search failed:', response.data);
                }
            },
            error: function(xhr, status, error) {
                $spinner.removeClass('is-active');
                console.error('Search error:', error);
                alert('Search failed. Please try again.');
            }
        });
    }

    /**
     * Update individuals list with search results
     */
    function updateIndividualsList(data) {
        var $container = $('.individuals-list');
        if (!$container.length) return;
        
        var html = '';
        
        if (data.individuals && data.individuals.length > 0) {
            data.individuals.forEach(function(individual) {
                var birthYear = individual.birth_date ? new Date(individual.birth_date).getFullYear() : '';
                var deathYear = individual.death_date ? new Date(individual.death_date).getFullYear() : '';
                var lifespan = '';
                
                if (birthYear || deathYear) {
                    lifespan = '(' + (birthYear || '?') + ' - ' + (deathYear || (individual.living_status === 'living' ? 'living' : '?')) + ')';
                }
                
                html += '<div class="individual-item" data-id="' + individual.id + '">';
                html += '<div class="individual-info">';
                html += '<h3>' + individual.full_name + '</h3>';
                html += '<p class="individual-details">';
                html += '<span class="sex">' + (individual.sex === 'M' ? 'Male' : individual.sex === 'F' ? 'Female' : 'Unknown') + '</span>';
                if (lifespan) {
                    html += ' • <span class="lifespan">' + lifespan + '</span>';
                }
                html += '</p>';
                html += '</div>';
                html += '<div class="individual-actions">';
                html += '<button class="button view-individual" data-id="' + individual.id + '">View</button>';
                html += '<button class="button edit-individual" data-id="' + individual.id + '">Edit</button>';
                html += '<button class="button delete-individual" data-id="' + individual.id + '">Delete</button>';
                html += '</div>';
                html += '</div>';
            });
        } else {
            html = '<div class="no-results"><p>No individuals found.</p></div>';
        }
        
        $container.html(html);
        
        // Update pagination if available
        if (data.pagination && data.pagination.pages > 1) {
            updatePagination(data.pagination);
        }
        
        // Bind click events
        bindIndividualActions();
    }

    /**
     * Update families list with search results
     */
    function updateFamiliesList(data) {
        var $container = $('.families-list');
        if (!$container.length) return;
        
        var html = '<div class="no-results"><p>Family management coming soon.</p></div>';
        $container.html(html);
    }

    /**
     * Bind click events for individual actions
     */
    function bindIndividualActions() {
        $('.view-individual').off('click').on('click', function() {
            var individualId = $(this).data('id');
            viewIndividual(individualId);
        });
        
        $('.edit-individual').off('click').on('click', function() {
            var individualId = $(this).data('id');
            editIndividual(individualId);
        });
        
        $('.delete-individual').off('click').on('click', function() {
            var individualId = $(this).data('id');
            if (confirm('Are you sure you want to delete this individual? This action cannot be undone.')) {
                deleteIndividual(individualId);
            }
        });
    }

    /**
     * Initialize individuals page functionality
     */
    function initIndividualsPage() {
        // Handle individual action buttons
        $(document).on('click', '.view-individual', function(e) {
            e.preventDefault();
            var individualId = $(this).data('individual-id');
            viewIndividual(individualId);
        });

        $(document).on('click', '.edit-individual', function(e) {
            e.preventDefault();
            var individualId = $(this).data('individual-id');
            editIndividual(individualId);
        });

        $(document).on('click', '.delete-individual', function(e) {
            e.preventDefault();
            var individualId = $(this).data('individual-id');
            var confirmMessage = $(this).data('confirm');
            
            if (confirm(confirmMessage)) {
                deleteIndividual(individualId);
            }
        });

        $(document).on('click', '#add-individual-btn', function(e) {
            e.preventDefault();
            addNewIndividual();
        });

        // Handle live search on individuals page
        var searchTimeout;
        $('#individual-search-input').on('input', function() {
            clearTimeout(searchTimeout);
            var query = $(this).val();
            
            if (query.length >= 2 || query.length === 0) {
                searchTimeout = setTimeout(function() {
                    performIndividualSearch(query);
                }, 300);
            }
        });
    }

    /**
     * Initialize modal functionality
     */
    function initModal() {
        // Close modal handlers
        $(document).on('click', '.heritage-modal-close', function() {
            closeModal();
        });

        $(document).on('click', '.heritage-modal', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Save individual form
        $(document).on('click', '#save-individual', function(e) {
            e.preventDefault();
            saveIndividualForm();
        });

        // ESC key to close modal
        $(document).on('keydown', function(e) {
            if (e.keyCode === 27) { // ESC key
                closeModal();
            }
        });
    }

    /**
     * View individual details
     */
    function viewIndividual(individualId) {
        $.ajax({
            url: heritage_press_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'heritage_press_get_individual',
                nonce: heritage_press_admin.nonce,
                id: individualId
            },
            beforeSend: function() {
                showModal();
                $('#individual-modal-content').html('<div class="loading-spinner"><span class="spinner is-active"></span><span>Loading...</span></div>');
            },
            success: function(response) {
                if (response.success) {
                    displayIndividualDetails(response.data, 'view');
                } else {
                    $('#individual-modal-content').html('<p class="error">Failed to load individual details.</p>');
                }
            },
            error: function() {
                $('#individual-modal-content').html('<p class="error">An error occurred while loading individual details.</p>');
            }
        });
    }

    /**
     * Edit individual
     */
    function editIndividual(individualId) {
        $.ajax({
            url: heritage_press_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'heritage_press_get_individual',
                nonce: heritage_press_admin.nonce,
                id: individualId
            },
            beforeSend: function() {
                showModal();
                $('#individual-modal-content').html('<div class="loading-spinner"><span class="spinner is-active"></span><span>Loading...</span></div>');
            },
            success: function(response) {
                if (response.success) {
                    displayIndividualDetails(response.data, 'edit');
                } else {
                    $('#individual-modal-content').html('<p class="error">Failed to load individual details.</p>');
                }
            },
            error: function() {
                $('#individual-modal-content').html('<p class="error">An error occurred while loading individual details.</p>');
            }
        });
    }

    /**
     * Add new individual
     */
    function addNewIndividual() {
        showModal();
        $('#individual-modal-title').text('Add New Individual');
        displayIndividualDetails({}, 'edit');
    }

    /**
     * Delete individual
     */
    function deleteIndividual(individualId) {
        $.ajax({
            url: heritage_press_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'heritage_press_delete_individual',
                nonce: heritage_press_admin.nonce,
                id: individualId
            },
            beforeSend: function() {
                showLoadingOverlay();
            },
            success: function(response) {
                hideLoadingOverlay();
                if (response.success) {
                    // Remove the row from the table
                    $('tr[data-individual-id="' + individualId + '"]').fadeOut(400, function() {
                        $(this).remove();
                    });
                    
                    // Show success message
                    showNotice('Individual deleted successfully.', 'success');
                } else {
                    showNotice('Failed to delete individual: ' + response.data, 'error');
                }
            },
            error: function() {
                hideLoadingOverlay();
                showNotice('An error occurred while deleting the individual.', 'error');
            }
        });
    }

    /**
     * Display individual details in modal
     */
    function displayIndividualDetails(individual, mode) {
        var isEditing = mode === 'edit';
        var isNew = !individual.id;
        
        $('#individual-modal-title').text(isNew ? 'Add New Individual' : (isEditing ? 'Edit Individual' : 'Individual Details'));
        
        var html = '';
        
        if (isEditing) {
            html += '<form id="individual-form">';
            html += '<input type="hidden" name="id" value="' + (individual.id || '') + '">';
            
            html += '<div class="form-row">';
            html += '<div class="form-group">';
            html += '<label for="given_names">Given Names</label>';
            html += '<input type="text" id="given_names" name="given_names" value="' + (individual.given_names || '') + '">';
            html += '</div>';
            html += '<div class="form-group">';
            html += '<label for="surname">Surname</label>';
            html += '<input type="text" id="surname" name="surname" value="' + (individual.surname || '') + '">';
            html += '</div>';
            html += '</div>';
            
            html += '<div class="form-row">';
            html += '<div class="form-group">';
            html += '<label for="title">Title</label>';
            html += '<input type="text" id="title" name="title" value="' + (individual.title || '') + '">';
            html += '</div>';
            html += '<div class="form-group">';
            html += '<label for="sex">Sex</label>';
            html += '<select id="sex" name="sex">';
            html += '<option value="">Select...</option>';
            html += '<option value="M"' + (individual.sex === 'M' ? ' selected' : '') + '>Male</option>';
            html += '<option value="F"' + (individual.sex === 'F' ? ' selected' : '') + '>Female</option>';
            html += '</select>';
            html += '</div>';
            html += '</div>';
            
            html += '<div class="form-row">';
            html += '<div class="form-group">';
            html += '<label for="birth_date">Birth Date</label>';
            html += '<input type="text" id="birth_date" name="birth_date" value="' + (individual.birth_date || '') + '" placeholder="e.g., 1850-01-15">';
            html += '</div>';
            html += '<div class="form-group">';
            html += '<label for="birth_place">Birth Place</label>';
            html += '<input type="text" id="birth_place" name="birth_place" value="' + (individual.birth_place || '') + '">';
            html += '</div>';
            html += '</div>';
            
            html += '<div class="form-row">';
            html += '<div class="form-group">';
            html += '<label for="death_date">Death Date</label>';
            html += '<input type="text" id="death_date" name="death_date" value="' + (individual.death_date || '') + '" placeholder="e.g., 1920-12-25">';
            html += '</div>';
            html += '<div class="form-group">';
            html += '<label for="death_place">Death Place</label>';
            html += '<input type="text" id="death_place" name="death_place" value="' + (individual.death_place || '') + '">';
            html += '</div>';
            html += '</div>';
            
            html += '<div class="form-row">';
            html += '<div class="form-group">';
            html += '<label for="living_status">Living Status</label>';
            html += '<select id="living_status" name="living_status">';
            html += '<option value="unknown"' + (individual.living_status === 'unknown' ? ' selected' : '') + '>Unknown</option>';
            html += '<option value="living"' + (individual.living_status === 'living' ? ' selected' : '') + '>Living</option>';
            html += '<option value="deceased"' + (individual.living_status === 'deceased' ? ' selected' : '') + '>Deceased</option>';
            html += '</select>';
            html += '</div>';
            html += '</div>';
            
            html += '<div class="form-row">';
            html += '<div class="form-group full-width">';
            html += '<label for="notes">Notes</label>';
            html += '<textarea id="notes" name="notes" rows="4">' + (individual.notes || '') + '</textarea>';
            html += '</div>';
            html += '</div>';
            
            html += '</form>';
            
            $('#save-individual').show();
        } else {
            // View mode
            var fullName = (individual.given_names || '') + ' ' + (individual.surname || '');
            if (individual.title) {
                fullName = individual.title + ' ' + fullName;
            }
            
            html += '<div class="individual-details">';
            html += '<h3>' + fullName.trim() + '</h3>';
            
            html += '<div class="details-grid">';
            if (individual.sex) {
                html += '<div class="detail-item"><strong>Sex:</strong> ' + (individual.sex === 'M' ? 'Male' : individual.sex === 'F' ? 'Female' : individual.sex) + '</div>';
            }
            if (individual.birth_date || individual.birth_place) {
                html += '<div class="detail-item"><strong>Birth:</strong> ';
                if (individual.birth_date) html += individual.birth_date;
                if (individual.birth_date && individual.birth_place) html += ', ';
                if (individual.birth_place) html += individual.birth_place;
                html += '</div>';
            }
            if (individual.death_date || individual.death_place) {
                html += '<div class="detail-item"><strong>Death:</strong> ';
                if (individual.death_date) html += individual.death_date;
                if (individual.death_date && individual.death_place) html += ', ';
                if (individual.death_place) html += individual.death_place;
                html += '</div>';
            }
            if (individual.living_status && individual.living_status !== 'unknown') {
                html += '<div class="detail-item"><strong>Status:</strong> ' + individual.living_status.charAt(0).toUpperCase() + individual.living_status.slice(1) + '</div>';
            }
            html += '</div>';
            
            if (individual.notes) {
                html += '<div class="notes-section">';
                html += '<h4>Notes</h4>';
                html += '<p>' + individual.notes.replace(/\n/g, '<br>') + '</p>';
                html += '</div>';
            }
            
            html += '</div>';
            
            $('#save-individual').hide();
        }
        
        $('#individual-modal-content').html(html);
    }

    /**
     * Save individual form
     */
    function saveIndividualForm() {
        var formData = {};
        $('#individual-form').serializeArray().forEach(function(item) {
            formData[item.name] = item.value;
        });
        
        formData.action = 'heritage_press_save_individual';
        formData.nonce = heritage_press_admin.nonce;
        
        $.ajax({
            url: heritage_press_admin.ajax_url,
            type: 'POST',
            data: formData,
            beforeSend: function() {
                showLoadingOverlay();
            },
            success: function(response) {
                hideLoadingOverlay();
                if (response.success) {
                    closeModal();
                    showNotice('Individual saved successfully.', 'success');
                    
                    // Refresh the page or update the table
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showNotice('Failed to save individual: ' + response.data, 'error');
                }
            },
            error: function() {
                hideLoadingOverlay();
                showNotice('An error occurred while saving the individual.', 'error');
            }
        });
    }

    /**
     * Perform individual search
     */
    function performIndividualSearch(query) {
        $.ajax({
            url: heritage_press_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'heritage_press_search_individuals',
                nonce: heritage_press_admin.nonce,
                search: query,
                per_page: 20
            },
            success: function(response) {
                if (response.success) {
                    updateIndividualsTable(response.data.individuals);
                    updateResultsInfo(response.data.pagination.total);
                }
            },
            error: function() {
                console.error('Search failed');
            }
        });
    }

    /**
     * Update individuals table with search results
     */
    function updateIndividualsTable(individuals) {
        var tbody = $('#individuals-list');
        tbody.empty();
        
        if (individuals.length === 0) {
            tbody.append('<tr class="no-items"><td colspan="5" class="colspanchange">No individuals found.</td></tr>');
            return;
        }
        
        individuals.forEach(function(individual) {
            var row = buildIndividualRow(individual);
            tbody.append(row);
        });
    }

    /**
     * Build individual table row
     */
    function buildIndividualRow(individual) {
        var fullName = (individual.full_name || '(No name)');
        var sex = individual.sex === 'M' ? 'Male' : 
                  individual.sex === 'F' ? 'Female' : 
                  'Unknown';
        
        var birthInfo = formatEventInfo(individual.birth_date, individual.birth_place);
        var deathInfo = formatEventInfo(individual.death_date, individual.death_place);
        
        var html = '<tr data-individual-id="' + individual.id + '">';
        html += '<td class="name column-name column-primary">';
        html += '<strong><a href="#" class="view-individual" data-individual-id="' + individual.id + '">' + fullName + '</a></strong>';
        html += '<div class="row-actions">';
        html += '<span class="view"><a href="#" class="view-individual" data-individual-id="' + individual.id + '">View</a> |</span> ';
        html += '<span class="edit"><a href="#" class="edit-individual" data-individual-id="' + individual.id + '">Edit</a> |</span> ';
        html += '<span class="delete"><a href="#" class="delete-individual" data-individual-id="' + individual.id + '" data-confirm="Are you sure you want to delete this individual?">Delete</a></span>';
        html += '</div>';
        html += '</td>';
        html += '<td class="sex column-sex">' + sex + '</td>';
        html += '<td class="birth column-birth">' + birthInfo + '</td>';
        html += '<td class="death column-death">' + deathInfo + '</td>';
        html += '<td class="actions column-actions">';
        html += '<div class="button-group">';
        html += '<button type="button" class="button button-small view-individual" data-individual-id="' + individual.id + '">View</button>';
        html += '<button type="button" class="button button-small edit-individual" data-individual-id="' + individual.id + '">Edit</button>';
        html += '</div>';
        html += '</td>';
        html += '</tr>';
        
        return html;
    }

    /**
     * Format event info (date and place)
     */
    function formatEventInfo(date, place) {
        if (!date && !place) return '—';
        
        var html = '<div class="event-info">';
        if (date) html += '<div class="event-date">' + date + '</div>';
        if (place) html += '<div class="event-place">' + place + '</div>';
        html += '</div>';
        
        return html;
    }

    /**
     * Show modal
     */
    function showModal() {
        $('#individual-modal').show();
        $('body').addClass('modal-open');
    }

    /**
     * Close modal
     */
    function closeModal() {
        $('#individual-modal').hide();
        $('body').removeClass('modal-open');
        $('#save-individual').hide();
    }

    /**
     * Show loading overlay
     */
    function showLoadingOverlay() {
        $('#heritage-loading-overlay').show();
    }

    /**
     * Hide loading overlay
     */
    function hideLoadingOverlay() {
        $('#heritage-loading-overlay').hide();
    }

    /**
     * Show admin notice
     */
    function showNotice(message, type) {
        var noticeClass = type === 'error' ? 'notice-error' : 'notice-success';
        var notice = '<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>';
        
        // Insert after the header
        $('.wrap h1').after(notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $('.notice').fadeOut();
        }, 5000);
    }

    /**
     * Update results info
     */
    function updateResultsInfo(total) {
        var text = total === 1 ? 
            total + ' individual found' :
            total + ' individuals found';
        $('.displaying-num').text(text);
    }

    // ...existing code...
})(jQuery);

// Default messages (will be overridden by localized script)
window.heritage_press_admin = window.heritage_press_admin || {
    messages: {
        no_file_selected: 'Please select a GEDCOM file to import.',
        file_too_large: 'File is too large. Maximum size is 32MB.',
        invalid_file_type: 'Please select a valid GEDCOM file (.ged or .gedcom).',
        reading_file: 'Reading GEDCOM file...',
        parsing_data: 'Parsing genealogy data...',
        importing_individuals: 'Importing individuals...',
        importing_families: 'Importing families and relationships...',
        finalizing_import: 'Finalizing import...',
        import_complete: 'Import completed successfully!',
        import_success: 'Your GEDCOM file has been imported successfully. You can now view your family tree data.',
        confirm_archive: 'Are you sure you want to archive this tree? Archived trees can be restored later.',
        confirm_delete: 'Are you sure you want to permanently delete this item? This action cannot be undone.',
        error_occurred: 'An error occurred. Please try again.'
    },
    ajax_url: '',
    nonce: '',
    admin_url: ''
};
