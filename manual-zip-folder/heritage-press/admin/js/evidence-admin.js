/**
 * Heritage Press Evidence Explained Admin JavaScript
 * 
 * Handles interactive functionality for the Evidence Explained methodology
 * admin interface including AJAX forms, modal dialogs, and user interactions.
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        Heritage.init();
    });

    // Main Heritage object
    window.Heritage = {
        init: function() {
            this.bindEvents();
            this.initializeComponents();
        },

        bindEvents: function() {
            this.bindFormEvents();
            this.bindModalEvents();
            this.bindBulkActions();
            this.bindSearchFilters();
            this.bindAjaxActions();
            this.bindNavigation();
            this.bindQualityAssessment();
        },

        initializeComponents: function() {
            this.initializeLivePreviews();
            this.initializeTooltips();
            this.initializeProgressTracking();
            this.initializeAutoSave();
        }
    };

    /**
     * Form Event Handlers
     */
    Heritage.bindFormEvents = function() {
        // Research Question Form
        $('.heritage-research-question-form').on('submit', this.handleResearchQuestionSubmit);
        
        // Information Statement Form
        $('.heritage-information-statement-form').on('submit', this.handleInformationStatementSubmit);
        
        // Evidence Analysis Form
        $('.heritage-evidence-analysis-form').on('submit', this.handleEvidenceAnalysisSubmit);
        
        // Proof Argument Form
        $('.heritage-proof-argument-form').on('submit', this.handleProofArgumentSubmit);

        // Auto-assessment triggers
        $('.heritage-auto-assess').on('click', this.triggerAutoAssessment);
        
        // Form field changes for live preview
        $('.heritage-form-container input, .heritage-form-container textarea, .heritage-form-container select')
            .on('input change', this.updateLivePreview);

        // Quality factor selection
        $('.heritage-quality-factors input[type="checkbox"]').on('change', this.updateQualityScore);
    };

    /**
     * Research Question Form Handler
     */
    Heritage.handleResearchQuestionSubmit = function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const formData = new FormData(this);
        formData.append('action', 'heritage_save_research_question');
        formData.append('nonce', heritage_admin.nonce);

        Heritage.showSpinner($form);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                Heritage.hideSpinner($form);
                
                if (response.success) {
                    Heritage.showNotice('success', response.data.message || 'Research question saved successfully.');
                    
                    if (response.data.redirect) {
                        setTimeout(() => {
                            window.location.href = response.data.redirect;
                        }, 1000);
                    }
                } else {
                    Heritage.showNotice('error', response.data || 'Error saving research question.');
                }
            },
            error: function() {
                Heritage.hideSpinner($form);
                Heritage.showNotice('error', 'Network error. Please try again.');
            }
        });
    };

    /**
     * Information Statement Form Handler
     */
    Heritage.handleInformationStatementSubmit = function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const formData = new FormData(this);
        formData.append('action', 'heritage_save_information_statement');
        formData.append('nonce', heritage_admin.nonce);

        Heritage.showSpinner($form);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                Heritage.hideSpinner($form);
                
                if (response.success) {
                    Heritage.showNotice('success', response.data.message || 'Information statement saved successfully.');
                    Heritage.updateLivePreview();
                    
                    if (response.data.redirect) {
                        setTimeout(() => {
                            window.location.href = response.data.redirect;
                        }, 1000);
                    }
                } else {
                    Heritage.showNotice('error', response.data || 'Error saving information statement.');
                }
            },
            error: function() {
                Heritage.hideSpinner($form);
                Heritage.showNotice('error', 'Network error. Please try again.');
            }
        });
    };

    /**
     * Evidence Analysis Form Handler
     */
    Heritage.handleEvidenceAnalysisSubmit = function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const formData = new FormData(this);
        formData.append('action', 'heritage_save_evidence_analysis');
        formData.append('nonce', heritage_admin.nonce);

        Heritage.showSpinner($form);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                Heritage.hideSpinner($form);
                
                if (response.success) {
                    Heritage.showNotice('success', response.data.message || 'Evidence analysis saved successfully.');
                    
                    // Update quality assessment if provided
                    if (response.data.quality_assessment) {
                        Heritage.updateQualityDisplay(response.data.quality_assessment);
                    }
                    
                    if (response.data.redirect) {
                        setTimeout(() => {
                            window.location.href = response.data.redirect;
                        }, 1000);
                    }
                } else {
                    Heritage.showNotice('error', response.data || 'Error saving evidence analysis.');
                }
            },
            error: function() {
                Heritage.hideSpinner($form);
                Heritage.showNotice('error', 'Network error. Please try again.');
            }
        });
    };

    /**
     * Proof Argument Form Handler
     */
    Heritage.handleProofArgumentSubmit = function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const formData = new FormData(this);
        formData.append('action', 'heritage_save_proof_argument');
        formData.append('nonce', heritage_admin.nonce);

        Heritage.showSpinner($form);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                Heritage.hideSpinner($form);
                
                if (response.success) {
                    Heritage.showNotice('success', response.data.message || 'Proof argument saved successfully.');
                    
                    if (response.data.redirect) {
                        setTimeout(() => {
                            window.location.href = response.data.redirect;
                        }, 1000);
                    }
                } else {
                    Heritage.showNotice('error', response.data || 'Error saving proof argument.');
                }
            },
            error: function() {
                Heritage.hideSpinner($form);
                Heritage.showNotice('error', 'Network error. Please try again.');
            }
        });
    };

    /**
     * Modal Event Handlers
     */
    Heritage.bindModalEvents = function() {
        // Open quality assessment modal
        $('.heritage-assess-quality').on('click', function(e) {
            e.preventDefault();
            const analysisId = $(this).data('analysis-id');
            Heritage.openQualityModal(analysisId);
        });

        // Close modal
        $('.heritage-modal-close, .heritage-modal-overlay').on('click', function(e) {
            if (e.target === this) {
                Heritage.closeModal();
            }
        });

        // Escape key closes modal
        $(document).on('keydown', function(e) {
            if (e.keyCode === 27) {
                Heritage.closeModal();
            }
        });

        // Save quality assessment
        $('.heritage-save-quality-assessment').on('click', this.saveQualityAssessment);
    };

    /**
     * Open Quality Assessment Modal
     */
    Heritage.openQualityModal = function(analysisId) {
        const $modal = $('#heritage-quality-modal');
        
        if ($modal.length === 0) {
            // Create modal if it doesn't exist
            this.createQualityModal();
        }

        // Load current assessment data
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'heritage_get_quality_assessment',
                analysis_id: analysisId,
                nonce: heritage_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    Heritage.populateQualityModal(response.data);
                    $('#heritage-quality-modal').show().attr('data-analysis-id', analysisId);
                    $('body').addClass('heritage-modal-open');
                }
            }
        });
    };

    /**
     * Create Quality Assessment Modal
     */
    Heritage.createQualityModal = function() {
        const modalHTML = `
            <div id="heritage-quality-modal" class="heritage-modal" style="display: none;">
                <div class="heritage-modal-overlay"></div>
                <div class="heritage-modal-content">
                    <div class="heritage-modal-header">
                        <h2>Quality Assessment</h2>
                        <button class="heritage-modal-close">&times;</button>
                    </div>
                    <div class="heritage-modal-body">
                        <div class="heritage-quality-factors">
                            <h3>Quality Factors</h3>
                            <div class="factor-group">
                                <label>
                                    <input type="checkbox" name="source_original" value="1">
                                    <span>Original source (not derivative)</span>
                                </label>
                            </div>
                            <div class="factor-group">
                                <label>
                                    <input type="checkbox" name="contemporary_record" value="1">
                                    <span>Contemporary record</span>
                                </label>
                            </div>
                            <div class="factor-group">
                                <label>
                                    <input type="checkbox" name="informant_knowledge" value="1">
                                    <span>Informant had direct knowledge</span>
                                </label>
                            </div>
                            <div class="factor-group">
                                <label>
                                    <input type="checkbox" name="unbiased_informant" value="1">
                                    <span>Informant was unbiased</span>
                                </label>
                            </div>
                            <div class="factor-group">
                                <label>
                                    <input type="checkbox" name="reliable_source" value="1">
                                    <span>Source has proven reliability</span>
                                </label>
                            </div>
                        </div>
                        <div class="heritage-confidence-display">
                            <h3>Confidence Level</h3>
                            <div class="confidence-score">
                                <span class="score-value">0</span>/100
                            </div>
                            <div class="confidence-level">Medium</div>
                        </div>
                    </div>
                    <div class="heritage-modal-footer">
                        <button class="button button-primary heritage-save-quality-assessment">Save Assessment</button>
                        <button class="button heritage-modal-close">Cancel</button>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHTML);
        this.bindQualityFactorEvents();
    };

    /**
     * Close Modal
     */
    Heritage.closeModal = function() {
        $('.heritage-modal').hide();
        $('body').removeClass('heritage-modal-open');
    };

    /**
     * Bulk Action Handlers
     */
    Heritage.bindBulkActions = function() {
        // Select all checkbox
        $('.heritage-select-all').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.heritage-bulk-checkbox').prop('checked', isChecked);
            Heritage.updateBulkActionButtons();
        });

        // Individual checkboxes
        $('.heritage-bulk-checkbox').on('change', this.updateBulkActionButtons);

        // Bulk action buttons
        $('.heritage-bulk-delete').on('click', this.handleBulkDelete);
        $('.heritage-bulk-export').on('click', this.handleBulkExport);
        $('.heritage-bulk-status-change').on('click', this.handleBulkStatusChange);
    };

    /**
     * Update Bulk Action Button States
     */
    Heritage.updateBulkActionButtons = function() {
        const checkedCount = $('.heritage-bulk-checkbox:checked').length;
        $('.heritage-bulk-actions .button').prop('disabled', checkedCount === 0);
        $('.heritage-selected-count').text(checkedCount);
    };

    /**
     * Handle Bulk Delete
     */
    Heritage.handleBulkDelete = function(e) {
        e.preventDefault();
        
        const selectedIds = $('.heritage-bulk-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedIds.length === 0) {
            return;
        }

        if (!confirm(`Are you sure you want to delete ${selectedIds.length} items? This action cannot be undone.`)) {
            return;
        }

        const action = $(this).data('action');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: action,
                ids: selectedIds,
                nonce: heritage_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    Heritage.showNotice('success', `${selectedIds.length} items deleted successfully.`);
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    Heritage.showNotice('error', response.data || 'Error deleting items.');
                }
            },
            error: function() {
                Heritage.showNotice('error', 'Network error. Please try again.');
            }
        });
    };

    /**
     * Search and Filter Handlers
     */
    Heritage.bindSearchFilters = function() {
        // Search input
        $('.heritage-search-box input[type="search"]').on('input', Heritage.debounce(function() {
            Heritage.updateFilters();
        }, 300));

        // Filter changes
        $('.heritage-filters-form select, .heritage-filters-form input').on('change', this.updateFilters);

        // Clear filters
        $('.heritage-clear-filters').on('click', this.clearFilters);

        // Apply filters
        $('.heritage-apply-filters').on('click', this.applyFilters);
    };

    /**
     * Update Filters
     */
    Heritage.updateFilters = function() {
        const $form = $('.heritage-filters-form');
        const searchTerm = $('.heritage-search-box input[type="search"]').val();
        
        if ($form.length === 0) return;

        const formData = $form.serialize();
        const params = new URLSearchParams(formData);
        
        if (searchTerm) {
            params.set('search', searchTerm);
        }

        // Update URL without reload
        const newUrl = window.location.pathname + '?' + params.toString();
        window.history.pushState(null, '', newUrl);

        // Reload content via AJAX
        Heritage.loadFilteredContent(params);
    };

    /**
     * Load Filtered Content
     */
    Heritage.loadFilteredContent = function(params) {
        const $container = $('.heritage-list-container, .heritage-grid-container');
        
        Heritage.showSpinner($container);

        $.ajax({
            url: window.location.href,
            type: 'GET',
            data: params.toString(),
            success: function(response) {
                const $newContent = $(response).find('.heritage-list-container, .heritage-grid-container').html();
                $container.html($newContent);
                Heritage.hideSpinner($container);
                Heritage.bindEvents(); // Re-bind events for new content
            },
            error: function() {
                Heritage.hideSpinner($container);
                Heritage.showNotice('error', 'Error loading filtered content.');
            }
        });
    };

    /**
     * AJAX Action Handlers
     */
    Heritage.bindAjaxActions = function() {
        // Duplicate actions
        $('.heritage-duplicate-question, .heritage-duplicate-statement, .heritage-duplicate-analysis, .heritage-duplicate-argument')
            .on('click', this.handleDuplicate);

        // Delete actions
        $('.heritage-delete-question, .heritage-delete-statement, .heritage-delete-analysis, .heritage-delete-argument')
            .on('click', this.handleDelete);

        // Export actions
        $('.heritage-export-question, .heritage-export-statement, .heritage-export-analysis, .heritage-export-argument')
            .on('click', this.handleExport);

        // Print actions
        $('.heritage-print-question, .heritage-print-statement, .heritage-print-analysis, .heritage-print-argument')
            .on('click', this.handlePrint);

        // Status change actions
        $('.heritage-status-change').on('click', this.handleStatusChange);
    };

    /**
     * Handle Duplicate Action
     */
    Heritage.handleDuplicate = function(e) {
        e.preventDefault();
        
        const itemId = $(this).data('item-id') || $(this).data('question-id') || $(this).data('statement-id') || 
                      $(this).data('analysis-id') || $(this).data('argument-id');
        const itemType = $(this).data('item-type') || 'item';
        
        if (!confirm(`Create a duplicate of this ${itemType}?`)) {
            return;
        }

        const action = $(this).data('action') || 'heritage_duplicate_item';

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: action,
                item_id: itemId,
                nonce: heritage_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    Heritage.showNotice('success', `${itemType} duplicated successfully.`);
                    if (response.data.redirect) {
                        setTimeout(() => {
                            window.location.href = response.data.redirect;
                        }, 1000);
                    }
                } else {
                    Heritage.showNotice('error', response.data || `Error duplicating ${itemType}.`);
                }
            },
            error: function() {
                Heritage.showNotice('error', 'Network error. Please try again.');
            }
        });
    };

    /**
     * Handle Delete Action
     */
    Heritage.handleDelete = function(e) {
        e.preventDefault();
        
        const itemId = $(this).data('item-id') || $(this).data('question-id') || $(this).data('statement-id') || 
                      $(this).data('analysis-id') || $(this).data('argument-id');
        const itemType = $(this).data('item-type') || 'item';
        
        if (!confirm(`Are you sure you want to delete this ${itemType}? This action cannot be undone.`)) {
            return;
        }

        const action = $(this).data('action') || 'heritage_delete_item';

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: action,
                item_id: itemId,
                nonce: heritage_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    Heritage.showNotice('success', `${itemType} deleted successfully.`);
                    setTimeout(() => {
                        window.location.href = response.data.redirect || window.location.href.replace(/[?&]action=[^&]*/, '').replace(/[?&]id=[^&]*/, '');
                    }, 1000);
                } else {
                    Heritage.showNotice('error', response.data || `Error deleting ${itemType}.`);
                }
            },
            error: function() {
                Heritage.showNotice('error', 'Network error. Please try again.');
            }
        });
    };

    /**
     * Handle Export Action
     */
    Heritage.handleExport = function(e) {
        e.preventDefault();
        
        const itemId = $(this).data('item-id') || $(this).data('question-id') || $(this).data('statement-id') || 
                      $(this).data('analysis-id') || $(this).data('argument-id');
        const action = $(this).data('action') || 'heritage_export_item';

        // Create temporary link for download
        const downloadUrl = ajaxurl + '?' + $.param({
            action: action,
            item_id: itemId,
            nonce: heritage_admin.nonce
        });

        const link = document.createElement('a');
        link.href = downloadUrl;
        link.target = '_blank';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    /**
     * Handle Print Action
     */
    Heritage.handlePrint = function(e) {
        e.preventDefault();
        window.print();
    };

    /**
     * Navigation Handlers
     */
    Heritage.bindNavigation = function() {
        // Tab navigation
        $('.heritage-tabs .nav-tab').on('click', function(e) {
            e.preventDefault();
            const targetTab = $(this).attr('href');
            
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            $('.heritage-tab-content').hide();
            $(targetTab).show();
        });

        // Step navigation in forms
        $('.heritage-step-nav button').on('click', function(e) {
            e.preventDefault();
            const direction = $(this).data('direction');
            Heritage.navigateFormStep(direction);
        });
    };

    /**
     * Navigate Form Steps
     */
    Heritage.navigateFormStep = function(direction) {
        const $currentStep = $('.heritage-form-step.active');
        const currentIndex = $currentStep.index();
        let nextIndex;

        if (direction === 'next') {
            nextIndex = currentIndex + 1;
        } else {
            nextIndex = currentIndex - 1;
        }

        const $nextStep = $('.heritage-form-step').eq(nextIndex);
        
        if ($nextStep.length > 0) {
            $currentStep.removeClass('active');
            $nextStep.addClass('active');
            
            // Update step navigation
            Heritage.updateStepNavigation(nextIndex);
        }
    };

    /**
     * Quality Assessment
     */
    Heritage.bindQualityAssessment = function() {
        // Auto-assessment trigger
        $('.heritage-auto-assess').on('click', this.triggerAutoAssessment);
        
        // Manual quality factor changes
        $('.heritage-quality-factors input[type="checkbox"]').on('change', this.updateQualityScore);
    };

    /**
     * Trigger Auto Assessment
     */
    Heritage.triggerAutoAssessment = function(e) {
        e.preventDefault();
        
        const analysisId = $(this).data('analysis-id');
        const $button = $(this);
        
        $button.prop('disabled', true).text('Assessing...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'heritage_assess_evidence_quality',
                analysis_id: analysisId,
                nonce: heritage_admin.nonce
            },
            success: function(response) {
                $button.prop('disabled', false).text('Auto-Assess Quality');
                
                if (response.success) {
                    Heritage.updateQualityDisplay(response.data);
                    Heritage.showNotice('success', 'Quality assessment completed.');
                } else {
                    Heritage.showNotice('error', response.data || 'Error performing quality assessment.');
                }
            },
            error: function() {
                $button.prop('disabled', false).text('Auto-Assess Quality');
                Heritage.showNotice('error', 'Network error. Please try again.');
            }
        });
    };

    /**
     * Update Quality Score
     */
    Heritage.updateQualityScore = function() {
        const checkedFactors = $('.heritage-quality-factors input[type="checkbox"]:checked').length;
        const totalFactors = $('.heritage-quality-factors input[type="checkbox"]').length;
        const baseScore = (checkedFactors / totalFactors) * 100;
        
        // Apply weightings and adjustments based on Evidence Explained methodology
        let adjustedScore = baseScore;
        
        // Source originality (higher weight)
        if ($('#source_original').is(':checked')) {
            adjustedScore += 10;
        }
        
        // Contemporary record (high weight)
        if ($('#contemporary_record').is(':checked')) {
            adjustedScore += 15;
        }
        
        // Informant knowledge (medium weight)
        if ($('#informant_knowledge').is(':checked')) {
            adjustedScore += 10;
        }
        
        // Cap at 100
        adjustedScore = Math.min(100, adjustedScore);
        
        // Update display
        $('.confidence-score .score-value').text(Math.round(adjustedScore));
        
        let confidenceLevel = 'low';
        if (adjustedScore >= 80) {
            confidenceLevel = 'high';
        } else if (adjustedScore >= 60) {
            confidenceLevel = 'medium';
        }
        
        $('.confidence-level').text(confidenceLevel.charAt(0).toUpperCase() + confidenceLevel.slice(1));
    };

    /**
     * Live Previews
     */
    Heritage.initializeLivePreviews = function() {
        // Statement preview
        $('.heritage-statement-text').on('input', Heritage.debounce(function() {
            const text = $(this).val();
            $('.heritage-statement-preview .preview-content').html(text || '<em>Enter statement text to see preview</em>');
        }, 300));

        // Analysis preview
        $('.heritage-analysis-text').on('input', Heritage.debounce(function() {
            const text = $(this).val();
            $('.heritage-analysis-preview .preview-content').html(Heritage.formatText(text) || '<em>Enter analysis text to see preview</em>');
        }, 300));

        // Citation preview
        $('.heritage-source-citation').on('input', Heritage.debounce(function() {
            const citation = $(this).val();
            $('.heritage-citation-preview').html(citation || '<em>Enter source citation to see preview</em>');
        }, 300));
    };

    /**
     * Update Live Preview
     */
    Heritage.updateLivePreview = function() {
        // Get form values
        const statementText = $('.heritage-statement-text').val();
        const sourceCitation = $('.heritage-source-citation').val();
        const analysisText = $('.heritage-analysis-text').val();

        // Update previews
        if (statementText) {
            $('.heritage-statement-preview .preview-content').html(statementText);
        }
        
        if (sourceCitation) {
            $('.heritage-citation-preview').html(sourceCitation);
        }
        
        if (analysisText) {
            $('.heritage-analysis-preview .preview-content').html(Heritage.formatText(analysisText));
        }
    };

    /**
     * Auto-save functionality
     */
    Heritage.initializeAutoSave = function() {
        let autoSaveTimeout;
        
        $('.heritage-form-container input, .heritage-form-container textarea, .heritage-form-container select')
            .on('input change', function() {
                clearTimeout(autoSaveTimeout);
                autoSaveTimeout = setTimeout(Heritage.performAutoSave, 5000); // Auto-save after 5 seconds of inactivity
            });
    };

    /**
     * Perform Auto Save
     */
    Heritage.performAutoSave = function() {
        const $form = $('.heritage-form-container form');
        
        if ($form.length === 0) return;

        const formData = new FormData($form[0]);
        formData.append('action', 'heritage_auto_save');
        formData.append('nonce', heritage_admin.nonce);

        // Show auto-save indicator
        Heritage.showAutoSaveIndicator();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                Heritage.hideAutoSaveIndicator(response.success);
            },
            error: function() {
                Heritage.hideAutoSaveIndicator(false);
            }
        });
    };

    /**
     * Utility Functions
     */
    Heritage.showSpinner = function($element) {
        $element.addClass('heritage-loading');
        if ($element.find('.heritage-spinner').length === 0) {
            $element.append('<div class="heritage-spinner"></div>');
        }
    };

    Heritage.hideSpinner = function($element) {
        $element.removeClass('heritage-loading');
        $element.find('.heritage-spinner').remove();
    };

    Heritage.showNotice = function(type, message) {
        const $notice = $(`<div class="notice notice-${type} is-dismissible"><p>${message}</p></div>`);
        $('.wrap h1').after($notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            $notice.fadeOut(() => $notice.remove());
        }, 5000);
    };

    Heritage.showAutoSaveIndicator = function() {
        if ($('.heritage-autosave-indicator').length === 0) {
            $('body').append('<div class="heritage-autosave-indicator">Saving...</div>');
        }
    };

    Heritage.hideAutoSaveIndicator = function(success) {
        const $indicator = $('.heritage-autosave-indicator');
        const message = success ? 'Saved' : 'Save failed';
        
        $indicator.text(message).addClass(success ? 'success' : 'error');
        
        setTimeout(() => {
            $indicator.fadeOut(() => $indicator.remove());
        }, 2000);
    };

    Heritage.formatText = function(text) {
        // Simple text formatting for previews
        return text.replace(/\n/g, '<br>').replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    };

    Heritage.debounce = function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = function() {
                clearTimeout(timeout);
                func.apply(this, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    };

    Heritage.updateQualityDisplay = function(assessment) {
        if (assessment.confidence_score) {
            $('.confidence-score .score-value').text(assessment.confidence_score);
        }
        
        if (assessment.confidence_level) {
            $('.confidence-level').text(assessment.confidence_level.charAt(0).toUpperCase() + assessment.confidence_level.slice(1));
        }
        
        if (assessment.quality_factors) {
            Object.keys(assessment.quality_factors).forEach(factor => {
                $(`#${factor}`).prop('checked', assessment.quality_factors[factor]);
            });
        }
    };

    Heritage.populateQualityModal = function(data) {
        if (data.quality_factors) {
            Object.keys(data.quality_factors).forEach(factor => {
                $(`#heritage-quality-modal input[name="${factor}"]`).prop('checked', data.quality_factors[factor]);
            });
        }
        
        if (data.confidence_score) {
            $('#heritage-quality-modal .score-value').text(data.confidence_score);
        }
        
        if (data.confidence_level) {
            $('#heritage-quality-modal .confidence-level').text(data.confidence_level.charAt(0).toUpperCase() + data.confidence_level.slice(1));
        }
    };

    Heritage.saveQualityAssessment = function(e) {
        e.preventDefault();
        
        const analysisId = $('#heritage-quality-modal').attr('data-analysis-id');
        const factors = {};
        
        $('#heritage-quality-modal input[type="checkbox"]').each(function() {
            factors[$(this).attr('name')] = $(this).is(':checked');
        });

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'heritage_save_quality_assessment',
                analysis_id: analysisId,
                quality_factors: factors,
                nonce: heritage_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    Heritage.closeModal();
                    Heritage.showNotice('success', 'Quality assessment saved successfully.');
                    
                    // Update the page display
                    if (response.data.confidence_score) {
                        $(`.heritage-confidence-score[data-analysis-id="${analysisId}"]`).text(response.data.confidence_score);
                    }
                } else {
                    Heritage.showNotice('error', response.data || 'Error saving quality assessment.');
                }
            },
            error: function() {
                Heritage.showNotice('error', 'Network error. Please try again.');
            }
        });
    };

    Heritage.bindQualityFactorEvents = function() {
        $('#heritage-quality-modal .heritage-quality-factors input[type="checkbox"]').on('change', function() {
            Heritage.updateQualityScore();
        });
    };

})(jQuery);

// Export for use in other scripts
window.Heritage = window.Heritage || {};
