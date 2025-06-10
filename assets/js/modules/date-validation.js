/**
 * HeritagePress Date Validation JavaScript Module
 *
 * Handles date validation and conversion functionality
 */

(function ($) {
    'use strict';

    var HeritagePress_DateValidation = {
        /**
         * Initialize date validation functionality
         */
        init: function () {
            this.bindEvents();
            this.initDateFields();
        },

        /**
         * Bind date validation events
         */
        bindEvents: function () {
            // Date field validation on blur
            $(document).on('blur', '.hp-date-input', this.validateDateField.bind(this));

            // Date conversion button clicks
            $(document).on('click', '.hp-convert-date', this.convertDate.bind(this));

            // Real-time validation on input (with debounce)
            $(document).on('input', '.hp-date-input', this.debounce(this.validateDateFieldRealtime.bind(this), 1000));

            // Clear validation when focused
            $(document).on('focus', '.hp-date-input', this.clearDateValidation);
        },

        /**
         * Initialize date fields
         */
        initDateFields: function () {
            // Add helper text to date fields
            $('.hp-date-input').each(function () {
                var $field = $(this);
                if (!$field.next('.hp-date-help').length) {
                    $field.after('<div class="hp-date-help">' +
                        (hp_i18n.date_help || 'Examples: 1 JAN 1950, ABT 1950, BET 1950 AND 1960') +
                        '</div>');
                }
            });
        },

        /**
         * Validate a date field
         */
        validateDateField: function (e) {
            var $field = $(e.target);
            this.performDateValidation($field);
        },

        /**
         * Real-time date validation (debounced)
         */
        validateDateFieldRealtime: function (e) {
            var $field = $(e.target);
            if ($field.val().trim().length > 3) { // Only validate if we have enough characters
                this.performDateValidation($field);
            }
        },

        /**
         * Perform date validation
         */
        performDateValidation: function ($field) {
            var dateString = $field.val().trim();

            if (!dateString) {
                this.clearDateValidation($field);
                return;
            }

            // Remove existing validation indicators
            $field.removeClass('hp-date-valid hp-date-invalid');
            $field.next('.hp-date-validation').remove();

            // Show loading indicator
            $field.after('<div class="hp-date-validation hp-date-loading">' +
                (hp_i18n.validating || 'Validating...') + '</div>');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hp_validate_date',
                    date_string: dateString,
                    nonce: $('#hp_admin_nonce').val()
                },
                success: (response) => {
                    $field.next('.hp-date-loading').remove();

                    if (response.success && response.data.is_valid) {
                        this.showValidationSuccess($field, response.data);
                    } else {
                        this.showValidationError($field, response.data.message || 'Invalid date format');
                    }
                },
                error: () => {
                    $field.next('.hp-date-loading').remove();
                    this.showValidationError($field, 'Validation failed');
                }
            });
        },

        /**
         * Show validation success
         */
        showValidationSuccess: function ($field, data) {
            $field.addClass('hp-date-valid');

            var validationInfo = '<div class="hp-date-validation hp-date-success">';
            validationInfo += '<strong>' + (hp_i18n.parsed || 'Parsed') + ':</strong> ' + (data.formatted || 'N/A') + '<br>';
            validationInfo += '<strong>' + (hp_i18n.calendar || 'Calendar') + ':</strong> ' + data.calendar + '<br>';

            if (data.modifier) {
                validationInfo += '<strong>' + (hp_i18n.modifier || 'Modifier') + ':</strong> ' + data.modifier + '<br>';
            }

            if (data.is_range) {
                validationInfo += '<strong>' + (hp_i18n.range || 'Range') + ':</strong> ' + (hp_i18n.yes || 'Yes') + '<br>';
            }

            validationInfo += '<button type="button" class="hp-convert-date button button-small">' +
                (hp_i18n.convert_date || 'Convert Date') + '</button>';
            validationInfo += '</div>';

            $field.after(validationInfo);
        },

        /**
         * Show validation error
         */
        showValidationError: function ($field, message) {
            $field.addClass('hp-date-invalid');
            $field.after('<div class="hp-date-validation hp-date-error">' + message + '</div>');
        },

        /**
         * Clear date validation
         */
        clearDateValidation: function (e) {
            var $field = e && e.target ? $(e.target) : e;
            $field.removeClass('hp-date-valid hp-date-invalid');
            $field.next('.hp-date-validation').remove();
        },

        /**
         * Convert date to different formats
         */
        convertDate: function (e) {
            e.preventDefault();

            var $button = $(e.target);
            var $field = $button.closest('.hp-date-validation').prev('.hp-date-input');
            var dateString = $field.val().trim();

            if (!dateString) {
                alert(hp_i18n.enter_date_first || 'Please enter a date first');
                return;
            }

            $button.prop('disabled', true).text(hp_i18n.converting || 'Converting...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hp_convert_date',
                    date_string: dateString,
                    target_format: 'standard',
                    nonce: $('#hp_admin_nonce').val()
                },
                success: (response) => {
                    if (response.success) {
                        this.showDateConversions($field, response.data);
                    } else {
                        alert((hp_i18n.conversion_failed || 'Date conversion failed') + ': ' + response.data.message);
                    }
                },
                error: () => {
                    alert(hp_i18n.conversion_request_failed || 'Date conversion request failed');
                },
                complete: () => {
                    $button.prop('disabled', false).text(hp_i18n.convert_date || 'Convert Date');
                }
            });
        },

        /**
         * Show date conversions
         */
        showDateConversions: function ($field, conversions) {
            // Remove existing conversions
            $field.next('.hp-date-conversions').remove();

            var output = '<div class="hp-date-conversions">';
            output += '<h4>' + (hp_i18n.date_conversions || 'Date Conversions') + ':</h4>';
            output += '<p><strong>' + (hp_i18n.original || 'Original') + ':</strong> ' + conversions.original + '</p>';
            output += '<p><strong>' + (hp_i18n.standard || 'Standard') + ':</strong> ' + (conversions.standard || 'N/A') + '</p>';
            output += '<p><strong>' + (hp_i18n.calendar || 'Calendar') + ':</strong> ' + conversions.calendar + '</p>';

            if (conversions.julian_day) {
                output += '<p><strong>' + (hp_i18n.julian_day || 'Julian Day') + ':</strong> ' + conversions.julian_day + '</p>';
            }

            // Add calendar conversion options
            output += '<div class="hp-calendar-conversions">';
            output += '<strong>' + (hp_i18n.convert_to || 'Convert to') + ':</strong> ';
            output += '<button type="button" class="hp-convert-calendar button button-small" data-calendar="Hebrew">' +
                (hp_i18n.hebrew || 'Hebrew') + '</button> ';
            output += '<button type="button" class="hp-convert-calendar button button-small" data-calendar="Julian">' +
                (hp_i18n.julian || 'Julian') + '</button> ';
            output += '<button type="button" class="hp-convert-calendar button button-small" data-calendar="French">' +
                (hp_i18n.french || 'French Republican') + '</button>';
            output += '</div>';

            output += '</div>';

            $field.after(output);

            // Bind calendar conversion events
            $('.hp-convert-calendar').on('click', (e) => {
                this.convertToCalendar($field, $(e.target).data('calendar'));
            });
        },

        /**
         * Convert to specific calendar
         */
        convertToCalendar: function ($field, targetCalendar) {
            var dateString = $field.val().trim();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hp_convert_date_calendar',
                    date_string: dateString,
                    target_calendar: targetCalendar,
                    nonce: $('#hp_admin_nonce').val()
                },
                success: (response) => {
                    if (response.success) {
                        this.showCalendarConversion($field, response.data);
                    } else {
                        alert((hp_i18n.calendar_conversion_failed || 'Calendar conversion failed') + ': ' + response.data.error);
                    }
                },
                error: () => {
                    alert(hp_i18n.calendar_conversion_request_failed || 'Calendar conversion request failed');
                }
            });
        },

        /**
         * Show calendar conversion result
         */
        showCalendarConversion: function ($field, conversionData) {
            var $conversions = $field.next('.hp-date-conversions');

            if ($conversions.length) {
                var conversionHtml = '<p><strong>' + conversionData.target_calendar + ':</strong> ' +
                    conversionData.converted + '</p>';
                $conversions.find('.hp-calendar-conversions').before(conversionHtml);
            }
        },

        /**
         * Debounce function
         */
        debounce: function (func, wait) {
            var timeout;
            return function executedFunction() {
                var context = this;
                var args = arguments;
                var later = function () {
                    timeout = null;
                    func.apply(context, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        /**
         * Get date validation status
         */
        getDateValidationStatus: function ($field) {
            if ($field.hasClass('hp-date-valid')) {
                return 'valid';
            } else if ($field.hasClass('hp-date-invalid')) {
                return 'invalid';
            } else {
                return 'unknown';
            }
        },

        /**
         * Validate all date fields in a form
         */
        validateAllDateFields: function ($form) {
            var validationPromises = [];

            $form.find('.hp-date-input').each((index, element) => {
                var $field = $(element);
                if ($field.val().trim()) {
                    validationPromises.push(this.validateDateFieldAsync($field));
                }
            });

            return Promise.all(validationPromises);
        },

        /**
         * Async date validation
         */
        validateDateFieldAsync: function ($field) {
            return new Promise((resolve, reject) => {
                var dateString = $field.val().trim();

                if (!dateString) {
                    resolve({ field: $field, valid: true });
                    return;
                }

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'hp_validate_date',
                        date_string: dateString,
                        nonce: $('#hp_admin_nonce').val()
                    },
                    success: (response) => {
                        resolve({
                            field: $field,
                            valid: response.success && response.data.is_valid,
                            data: response.data
                        });
                    },
                    error: () => {
                        resolve({ field: $field, valid: false, error: 'Validation failed' });
                    }
                });
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        HeritagePress_DateValidation.init();
    });

    // Make available globally
    window.HeritagePress_DateValidation = HeritagePress_DateValidation;

})(jQuery);
