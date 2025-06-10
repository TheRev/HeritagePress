/**
 * HeritagePress Settings JavaScript Module
 *
 * Handles settings-specific functionality
 */

(function ($) {
    'use strict';

    var HeritagePress_Settings = {
        /**
         * Initialize settings functionality
         */
        init: function () {
            this.bindEvents();
            this.initTabs();
            this.initValidation();
        },

        /**
         * Bind settings events
         */
        bindEvents: function () {
            // Settings form submission
            $('#hp-settings-form').on('submit', this.handleSettingsSave.bind(this));

            // Settings tabs
            $('.hp-settings-tab').on('click', this.handleTabSwitch);

            // Reset buttons
            $('.hp-reset-settings').on('click', this.handleSettingsReset.bind(this));

            // Import/Export settings
            $('.hp-import-settings, .hp-export-settings').on('click', this.handleSettingsImportExport.bind(this));

            // Field validation
            $('#hp-settings-form input, #hp-settings-form select').on('change', this.validateField.bind(this));
        },

        /**
         * Initialize settings tabs
         */
        initTabs: function () {
            // Show first tab by default
            $('.hp-settings-tab').first().addClass('active');
            $('.hp-settings-content').first().show();

            // Hide other tabs
            $('.hp-settings-content').not(':first').hide();
        },

        /**
         * Handle tab switching
         */
        handleTabSwitch: function (e) {
            e.preventDefault();

            var $tab = $(this);
            var target = $tab.data('target');

            // Update active tab
            $('.hp-settings-tab').removeClass('active');
            $tab.addClass('active');

            // Show target content, hide others
            $('.hp-settings-content').hide();
            $('#' + target + '-settings').fadeIn(300);

            // Save current tab in localStorage
            localStorage.setItem('hp_settings_active_tab', target);
        },

        /**
         * Handle settings form submission
         */
        handleSettingsSave: function (e) {
            e.preventDefault();

            var $form = $(e.target);
            var $submitButton = $form.find('input[type="submit"]');
            var formData = new FormData(e.target);

            // Validate form before submission
            if (!this.validateForm($form)) {
                return;
            }

            // Add action and nonce
            formData.append('action', 'hp_save_import_export_settings');
            formData.append('hp_settings_nonce', $('#hp_settings_nonce').val());

            // Disable button and show saving state
            $submitButton.prop('disabled', true);
            var originalText = $submitButton.val();
            $submitButton.val(hp_i18n.saving || 'Saving...');

            // Send AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    $submitButton.prop('disabled', false);
                    $submitButton.val(originalText);

                    if (response.success) {
                        this.showMessage('success', response.data.message);
                        this.markFormAsSaved($form);
                    } else {
                        this.showMessage('error', response.data.message || (hp_i18n.save_failed || 'Failed to save settings'));
                    }
                },
                error: () => {
                    $submitButton.prop('disabled', false);
                    $submitButton.val(originalText);
                    this.showMessage('error', hp_i18n.ajax_error || 'An error occurred during the request. Please try again.');
                }
            });
        },

        /**
         * Handle settings reset
         */
        handleSettingsReset: function (e) {
            e.preventDefault();

            var $button = $(e.target);
            var settingsType = $button.data('type') || 'all';

            // Confirm reset
            var confirmMessage = hp_i18n.confirm_reset || 'Are you sure you want to reset these settings to defaults?';
            if (!confirm(confirmMessage)) {
                return;
            }

            $button.prop('disabled', true);
            var originalText = $button.text();
            $button.text(hp_i18n.resetting || 'Resetting...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hp_reset_settings',
                    type: settingsType,
                    nonce: $('#hp_settings_nonce').val()
                },
                success: (response) => {
                    $button.prop('disabled', false);
                    $button.text(originalText);

                    if (response.success) {
                        this.showMessage('success', response.data.message);
                        // Reload page to show default values
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        this.showMessage('error', response.data.message || (hp_i18n.reset_failed || 'Failed to reset settings'));
                    }
                },
                error: () => {
                    $button.prop('disabled', false);
                    $button.text(originalText);
                    this.showMessage('error', hp_i18n.ajax_error || 'An error occurred. Please try again.');
                }
            });
        },

        /**
         * Handle settings import/export
         */
        handleSettingsImportExport: function (e) {
            e.preventDefault();

            var $button = $(e.target);
            var action = $button.hasClass('hp-export-settings') ? 'export' : 'import';

            if (action === 'export') {
                this.exportSettings();
            } else {
                this.importSettings();
            }
        },

        /**
         * Export settings
         */
        exportSettings: function () {
            // Get current form data
            var formData = new FormData($('#hp-settings-form')[0]);
            var settings = {};

            // Convert FormData to object
            for (var [key, value] of formData.entries()) {
                if (key !== 'action' && key !== 'hp_settings_nonce') {
                    settings[key] = value;
                }
            }

            // Create export data
            var exportData = {
                heritagepress_settings: settings,
                exported_at: new Date().toISOString(),
                version: '1.0'
            };

            // Create and download file
            var blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'heritagepress-settings-' + new Date().toISOString().split('T')[0] + '.json';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            this.showMessage('success', hp_i18n.settings_exported || 'Settings exported successfully');
        },

        /**
         * Import settings
         */
        importSettings: function () {
            // Create file input
            var $fileInput = $('<input type="file" accept=".json" style="display: none;">');
            $('body').append($fileInput);

            $fileInput.on('change', (e) => {
                var file = e.target.files[0];
                if (!file) return;

                var reader = new FileReader();
                reader.onload = (e) => {
                    try {
                        var importData = JSON.parse(e.target.result);
                        this.processSettingsImport(importData);
                    } catch (error) {
                        this.showMessage('error', hp_i18n.invalid_settings_file || 'Invalid settings file format');
                    }
                };
                reader.readAsText(file);

                // Remove file input
                $fileInput.remove();
            });

            $fileInput.click();
        },

        /**
         * Process settings import
         */
        processSettingsImport: function (importData) {
            if (!importData.heritagepress_settings) {
                this.showMessage('error', hp_i18n.invalid_settings_format || 'Invalid settings file format');
                return;
            }

            var settings = importData.heritagepress_settings;
            var $form = $('#hp-settings-form');

            // Apply settings to form
            Object.entries(settings).forEach(([key, value]) => {
                var $field = $form.find('[name="' + key + '"]');
                if ($field.length) {
                    if ($field.is(':checkbox') || $field.is(':radio')) {
                        $field.prop('checked', value === '1' || value === true);
                    } else {
                        $field.val(value);
                    }
                }
            });

            this.showMessage('success', hp_i18n.settings_imported || 'Settings imported successfully');
            this.markFormAsChanged($form);
        },

        /**
         * Initialize form validation
         */
        initValidation: function () {
            // Add validation classes
            $('#hp-settings-form').addClass('hp-validate-form');

            // Initialize validation tooltips
            this.initValidationTooltips();
        },

        /**
         * Initialize validation tooltips
         */
        initValidationTooltips: function () {
            // Add tooltips for fields with validation rules
            $('[data-validation]').each(function () {
                var $field = $(this);
                var validation = $field.data('validation');

                if (validation.required) {
                    $field.after('<span class="hp-required-indicator" title="' +
                        (hp_i18n.required_field || 'This field is required') + '">*</span>');
                }
            });
        },

        /**
         * Validate individual field
         */
        validateField: function (e) {
            var $field = $(e.target);
            var validation = $field.data('validation');

            if (!validation) return true;

            var value = $field.val();
            var isValid = true;
            var errorMessage = '';

            // Required validation
            if (validation.required && !value.trim()) {
                isValid = false;
                errorMessage = hp_i18n.field_required || 'This field is required';
            }

            // Pattern validation
            if (isValid && validation.pattern && value) {
                var pattern = new RegExp(validation.pattern);
                if (!pattern.test(value)) {
                    isValid = false;
                    errorMessage = validation.message || (hp_i18n.invalid_format || 'Invalid format');
                }
            }

            // Min/Max validation for numbers
            if (isValid && validation.min !== undefined && value) {
                var numValue = parseFloat(value);
                if (numValue < validation.min) {
                    isValid = false;
                    errorMessage = (hp_i18n.min_value || 'Minimum value is') + ' ' + validation.min;
                }
            }

            if (isValid && validation.max !== undefined && value) {
                var numValue = parseFloat(value);
                if (numValue > validation.max) {
                    isValid = false;
                    errorMessage = (hp_i18n.max_value || 'Maximum value is') + ' ' + validation.max;
                }
            }

            // Show/hide validation message
            this.showFieldValidation($field, isValid, errorMessage);

            return isValid;
        },

        /**
         * Show field validation
         */
        showFieldValidation: function ($field, isValid, message) {
            // Remove existing validation
            $field.removeClass('hp-field-valid hp-field-invalid');
            $field.next('.hp-field-validation').remove();

            if (isValid) {
                $field.addClass('hp-field-valid');
            } else {
                $field.addClass('hp-field-invalid');
                $field.after('<div class="hp-field-validation hp-field-error">' + message + '</div>');
            }
        },

        /**
         * Validate entire form
         */
        validateForm: function ($form) {
            var isValid = true;

            $form.find('[data-validation]').each((index, element) => {
                var $field = $(element);
                if (!this.validateField({ target: element })) {
                    isValid = false;
                }
            });

            return isValid;
        },

        /**
         * Mark form as saved
         */
        markFormAsSaved: function ($form) {
            $form.removeClass('hp-form-changed');
            $form.addClass('hp-form-saved');

            // Remove saved class after 3 seconds
            setTimeout(() => {
                $form.removeClass('hp-form-saved');
            }, 3000);
        },

        /**
         * Mark form as changed
         */
        markFormAsChanged: function ($form) {
            $form.addClass('hp-form-changed');
            $form.removeClass('hp-form-saved');
        },

        /**
         * Show message to user
         */
        showMessage: function (type, message) {
            // Remove existing messages
            $('.hp-message').remove();

            // Create new message
            var $message = $('<div class="hp-message notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');

            // Add to page
            $('.wrap h1').after($message);

            // Auto-dismiss after 5 seconds for success messages
            if (type === 'success') {
                setTimeout(() => {
                    $message.fadeOut();
                }, 5000);
            }

            // Scroll to message
            $('html, body').animate({
                scrollTop: $message.offset().top - 50
            }, 300);
        },

        /**
         * Get current settings as object
         */
        getCurrentSettings: function () {
            var settings = {};
            var $form = $('#hp-settings-form');

            $form.find('input, select, textarea').each(function () {
                var $field = $(this);
                var name = $field.attr('name');
                var value = $field.val();

                if (name) {
                    if ($field.is(':checkbox')) {
                        settings[name] = $field.is(':checked');
                    } else if ($field.is(':radio')) {
                        if ($field.is(':checked')) {
                            settings[name] = value;
                        }
                    } else {
                        settings[name] = value;
                    }
                }
            });

            return settings;
        },

        /**
         * Apply settings to form
         */
        applySettings: function (settings) {
            var $form = $('#hp-settings-form');

            Object.entries(settings).forEach(([name, value]) => {
                var $field = $form.find('[name="' + name + '"]');

                if ($field.length) {
                    if ($field.is(':checkbox')) {
                        $field.prop('checked', Boolean(value));
                    } else if ($field.is(':radio')) {
                        $field.filter('[value="' + value + '"]').prop('checked', true);
                    } else {
                        $field.val(value);
                    }
                }
            });

            this.markFormAsChanged($form);
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        HeritagePress_Settings.init();

        // Restore active tab from localStorage
        var activeTab = localStorage.getItem('hp_settings_active_tab');
        if (activeTab) {
            $('.hp-settings-tab[data-target="' + activeTab + '"]').click();
        }
    });

    // Make available globally
    window.HeritagePress_Settings = HeritagePress_Settings;

})(jQuery);
