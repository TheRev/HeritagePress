/* HeritagePress Trees Administration JavaScript */
/* Handles form validation, AJAX interactions, and user interface enhancements */

(function ($) {
    'use strict';

    // Trees Administration Object
    var HPTrees = {
        // Initialize the trees admin interface
        init: function () {
            this.bindEvents();
            this.initValidation();
            this.initAjaxFunctions();
        },

        // Bind event handlers
        bindEvents: function () {
            // Form submission handling
            $(document).on('submit', '#hp-trees-form', this.handleFormSubmit);

            // Delete confirmations
            $(document).on('click', '.hp-delete-tree', this.confirmDelete);

            // GEDCOM ID validation
            $(document).on('blur', '#gedcom_id', this.validateGedcomId);

            // Real-time form validation
            $(document).on('input', '.hp-required', this.validateRequired);

            // Privacy level changes
            $(document).on('change', '#privacy_level', this.updatePrivacyDescription);

            // Search functionality
            $(document).on('submit', '#hp-trees-search-form', this.handleSearch);

            // Tab navigation
            $(document).on('click', '.hp-trees-nav .nav-tab', this.handleTabClick);
        },

        // Initialize form validation
        initValidation: function () {
            // Set up validation rules
            if ($('#hp-trees-form').length) {
                this.setupFormValidation();
            }

            // Initialize privacy level description
            this.updatePrivacyDescription();
        },

        // Initialize AJAX functions
        initAjaxFunctions: function () {
            // Auto-save functionality could go here
            // Real-time statistics updates
            this.initStatsUpdates();
        },

        // Handle form submission
        handleFormSubmit: function (e) {
            var form = $(this);
            var isValid = HPTrees.validateForm(form);

            if (!isValid) {
                e.preventDefault();
                HPTrees.showValidationErrors();
                return false;
            }

            // Show loading state
            HPTrees.setFormLoading(form, true);

            return true;
        },

        // Validate entire form
        validateForm: function (form) {
            var isValid = true;
            var errors = [];

            // Required field validation
            form.find('.hp-required').each(function () {
                var field = $(this);
                var value = field.val().trim();

                if (!value) {
                    HPTrees.markFieldError(field, hpTrees.strings.enter_tree_name);
                    errors.push(field.attr('name'));
                    isValid = false;
                } else {
                    HPTrees.markFieldSuccess(field);
                }
            });

            // GEDCOM ID format validation
            var gedcomField = form.find('#gedcom_id');
            if (gedcomField.length) {
                var gedcomValue = gedcomField.val().trim();
                if (gedcomValue && !this.isValidGedcomId(gedcomValue)) {
                    HPTrees.markFieldError(gedcomField, 'Invalid GEDCOM ID format');
                    errors.push('gedcom_id');
                    isValid = false;
                }
            }

            return isValid;
        },

        // Validate GEDCOM ID format
        isValidGedcomId: function (gedcomId) {
            // GEDCOM ID should be alphanumeric, no spaces, reasonable length
            var pattern = /^[a-zA-Z0-9_-]{1,20}$/;
            return pattern.test(gedcomId);
        },

        // Validate required fields in real-time
        validateRequired: function () {
            var field = $(this);
            var value = field.val().trim();

            if (field.hasClass('hp-required')) {
                if (value) {
                    HPTrees.markFieldSuccess(field);
                } else {
                    HPTrees.markFieldError(field, 'This field is required');
                }
            }
        },

        // Validate GEDCOM ID via AJAX
        validateGedcomId: function () {
            var field = $(this);
            var gedcomId = field.val().trim();

            if (!gedcomId) {
                HPTrees.clearFieldValidation(field);
                return;
            }

            if (!HPTrees.isValidGedcomId(gedcomId)) {
                HPTrees.markFieldError(field, 'Invalid GEDCOM ID format (alphanumeric only, max 20 characters)');
                return;
            }

            // Show loading
            HPTrees.setFieldLoading(field, true);

            // AJAX validation
            $.ajax({
                url: hpTrees.ajaxurl,
                type: 'POST',
                data: {
                    action: 'hp_trees_action',
                    trees_action: 'validate_gedcom_id',
                    gedcom: gedcomId,
                    exclude_id: $('#tree_id').val() || 0,
                    nonce: hpTrees.nonce
                },
                success: function (response) {
                    HPTrees.setFieldLoading(field, false);

                    if (response.success && response.data.available) {
                        HPTrees.markFieldSuccess(field, 'GEDCOM ID is available');
                    } else {
                        HPTrees.markFieldError(field, 'GEDCOM ID already exists');
                    }
                },
                error: function () {
                    HPTrees.setFieldLoading(field, false);
                    HPTrees.markFieldError(field, 'Unable to validate GEDCOM ID');
                }
            });
        },

        // Mark field as having an error
        markFieldError: function (field, message) {
            field.removeClass('hp-field-success').addClass('hp-field-error');

            var messageEl = field.next('.hp-validation-message');
            if (messageEl.length) {
                messageEl.removeClass('success').addClass('error').text(message);
            } else {
                field.after('<div class="hp-validation-message error">' + message + '</div>');
            }
        },

        // Mark field as successful
        markFieldSuccess: function (field, message) {
            field.removeClass('hp-field-error').addClass('hp-field-success');

            var messageEl = field.next('.hp-validation-message');
            if (message) {
                if (messageEl.length) {
                    messageEl.removeClass('error').addClass('success').text(message);
                } else {
                    field.after('<div class="hp-validation-message success">' + message + '</div>');
                }
            } else {
                messageEl.remove();
            }
        },

        // Clear field validation
        clearFieldValidation: function (field) {
            field.removeClass('hp-field-error hp-field-success');
            field.next('.hp-validation-message').remove();
        },

        // Set field loading state
        setFieldLoading: function (field, loading) {
            var spinner = field.next('.hp-spinner');

            if (loading) {
                if (!spinner.length) {
                    field.after('<span class="hp-spinner"></span>');
                }
            } else {
                spinner.remove();
            }
        },

        // Set form loading state
        setFormLoading: function (form, loading) {
            var submitBtn = form.find('input[type="submit"], button[type="submit"]');

            if (loading) {
                form.addClass('hp-loading');
                submitBtn.prop('disabled', true);
                submitBtn.after('<span class="hp-spinner"></span>');
            } else {
                form.removeClass('hp-loading');
                submitBtn.prop('disabled', false);
                submitBtn.next('.hp-spinner').remove();
            }
        },

        // Confirm tree deletion
        confirmDelete: function (e) {
            e.preventDefault();

            var link = $(this);
            var treeName = link.data('tree-name') || 'this tree';
            var message = hpTrees.strings.confirm_delete.replace('%s', treeName);

            if (confirm(message)) {
                window.location.href = link.attr('href');
            }
        },

        // Update privacy level description
        updatePrivacyDescription: function () {
            var select = $('#privacy_level');
            if (!select.length) return;

            var descriptions = {
                '0': 'Public - Tree is visible to all visitors',
                '1': 'Registered Users - Tree is visible to registered users only',
                '2': 'Private - Tree is visible to owner and administrators only'
            };

            var value = select.val();
            var description = descriptions[value] || '';

            var descEl = select.next('.description');
            if (descEl.length) {
                descEl.text(description);
            }
        },

        // Handle search form submission
        handleSearch: function (e) {
            var form = $(this);
            var searchInput = form.find('input[name="search"]');

            // Don't submit if search is empty and no current search
            if (!searchInput.val().trim() && !window.location.search.includes('search=')) {
                e.preventDefault();
                return false;
            }

            return true;
        },

        // Handle tab navigation
        handleTabClick: function (e) {
            e.preventDefault();

            var tab = $(this);
            var targetUrl = tab.attr('href');

            if (targetUrl && targetUrl !== '#') {
                window.location.href = targetUrl;
            }
        },

        // Setup form validation
        setupFormValidation: function () {
            // Add required field indicators
            $('.hp-required').each(function () {
                var field = $(this);
                var label = $('label[for="' + field.attr('id') + '"]');

                if (label.length && !label.find('.required').length) {
                    label.append(' <span class="required" style="color: #d63638;">*</span>');
                }
            });
        },

        // Initialize statistics updates
        initStatsUpdates: function () {
            // Could implement real-time statistics updates here
            // For now, just ensure stats are formatted nicely
            $('.hp-tree-stat .stat-value').each(function () {
                var value = $(this).text();
                if ($.isNumeric(value)) {
                    $(this).text(HPTrees.formatNumber(value));
                }
            });
        },

        // Format numbers with commas
        formatNumber: function (num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        },

        // Show validation errors summary
        showValidationErrors: function () {
            var errors = $('.hp-field-error');
            if (errors.length) {
                // Scroll to first error
                $('html, body').animate({
                    scrollTop: errors.first().offset().top - 100
                }, 300);

                // Focus first error field
                errors.first().focus();
            }
        },

        // Get tree statistics via AJAX
        getTreeStats: function (gedcom, callback) {
            $.ajax({
                url: hpTrees.ajaxurl,
                type: 'POST',
                data: {
                    action: 'hp_trees_action',
                    trees_action: 'get_tree_stats',
                    gedcom: gedcom,
                    nonce: hpTrees.nonce
                },
                success: function (response) {
                    if (response.success && callback) {
                        callback(response.data);
                    }
                },
                error: function () {
                    console.log('Failed to get tree statistics');
                }
            });
        },

        // Update statistics display
        updateStatsDisplay: function (stats) {
            $.each(stats, function (key, value) {
                var element = $('.stat-' + key + ' .stat-value');
                if (element.length) {
                    element.text(HPTrees.formatNumber(value));
                }
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function () {
        HPTrees.init();
    });

    // Make HPTrees available globally
    window.HPTrees = HPTrees;

})(jQuery);
