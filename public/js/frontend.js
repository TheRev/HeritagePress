/**
 * Heritage Press Frontend JavaScript
 *
 * @package HeritagePress
 */

(function($) {
    'use strict';

    // Document ready
    $(document).ready(function() {
        
        // Initialize frontend features
        initSearch();
        initNavigation();
        initFamilyTree();
        initModals();
        
    });

    /**
     * Initialize search functionality
     */
    function initSearch() {
        var $searchForm = $('.heritage-search-form');
        var $searchInput = $('.heritage-search-input');
        var $searchResults = $('.heritage-search-results');
        
        if ($searchForm.length) {
            // Live search functionality
            var searchTimeout;
            
            $searchInput.on('input', function() {
                var query = $(this).val().trim();
                
                clearTimeout(searchTimeout);
                
                if (query.length < 2) {
                    $searchResults.empty();
                    return;
                }
                
                searchTimeout = setTimeout(function() {
                    performSearch(query);
                }, 300);
            });
            
            // Form submission
            $searchForm.on('submit', function(e) {
                e.preventDefault();
                var query = $searchInput.val().trim();
                if (query.length >= 2) {
                    performSearch(query);
                }
            });
        }
        
        function performSearch(query) {
            // Show loading state
            $searchResults.html('<div class="heritage-loading">Searching...</div>');
            
            // Make AJAX request
            $.ajax({
                url: heritage_press_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'heritage_press_search',
                    query: query,
                    nonce: heritage_press_frontend.nonce
                },
                success: function(response) {
                    if (response.success && response.data.results) {
                        displaySearchResults(response.data.results);
                    } else {
                        $searchResults.html('<div class="heritage-no-results">No results found.</div>');
                    }
                },
                error: function() {
                    $searchResults.html('<div class="heritage-error">Search failed. Please try again.</div>');
                }
            });
        }
        
        function displaySearchResults(results) {
            var html = '';
            
            if (results.length === 0) {
                html = '<div class="heritage-no-results">No results found.</div>';
            } else {
                results.forEach(function(result) {
                    html += buildSearchResultHtml(result);
                });
            }
            
            $searchResults.html(html);
        }
        
        function buildSearchResultHtml(result) {
            var html = '<div class="heritage-search-result">';
            html += '<div class="heritage-search-result-info">';
            html += '<a href="' + result.url + '" class="heritage-search-result-name">' + escapeHtml(result.name) + '</a>';
            html += '<div class="heritage-search-result-details">' + escapeHtml(result.details) + '</div>';
            html += '</div>';
            html += '<div class="heritage-search-result-actions">';
            html += '<a href="' + result.url + '" class="heritage-btn heritage-btn-small">View</a>';
            html += '</div>';
            html += '</div>';
            return html;
        }
    }

    /**
     * Initialize navigation
     */
    function initNavigation() {
        // Highlight active navigation item
        var currentPath = window.location.pathname;
        $('.heritage-nav-link').each(function() {
            var href = $(this).attr('href');
            if (href && currentPath.indexOf(href) !== -1) {
                $(this).addClass('active');
            }
        });
        
        // Mobile menu toggle (if needed)
        $('.heritage-menu-toggle').on('click', function() {
            $('.heritage-nav-list').toggleClass('show');
        });
    }

    /**
     * Initialize family tree functionality
     */
    function initFamilyTree() {
        // Simple tree interactions
        $('.heritage-tree-node').on('click', function(e) {
            e.preventDefault();
            var individualId = $(this).data('individual-id');
            if (individualId) {
                loadIndividualDetails(individualId);
            }
        });
        
        // Tree zoom controls
        var zoomLevel = 1;
        $('.heritage-tree-zoom-in').on('click', function() {
            zoomLevel = Math.min(zoomLevel + 0.1, 2);
            $('.heritage-tree').css('transform', 'scale(' + zoomLevel + ')');
        });
        
        $('.heritage-tree-zoom-out').on('click', function() {
            zoomLevel = Math.max(zoomLevel - 0.1, 0.5);
            $('.heritage-tree').css('transform', 'scale(' + zoomLevel + ')');
        });
        
        $('.heritage-tree-zoom-reset').on('click', function() {
            zoomLevel = 1;
            $('.heritage-tree').css('transform', 'scale(1)');
        });
    }

    /**
     * Load individual details
     */
    function loadIndividualDetails(individualId) {
        $.ajax({
            url: heritage_press_frontend.ajax_url,
            type: 'POST',
            data: {
                action: 'heritage_press_get_individual',
                individual_id: individualId,
                nonce: heritage_press_frontend.nonce
            },
            success: function(response) {
                if (response.success && response.data.individual) {
                    showIndividualModal(response.data.individual);
                }
            },
            error: function() {
                alert('Failed to load individual details.');
            }
        });
    }

    /**
     * Initialize modal functionality
     */
    function initModals() {
        // Create modal if it doesn't exist
        if ($('.heritage-modal').length === 0) {
            $('body').append(
                '<div class="heritage-modal" style="display: none;">' +
                '<div class="heritage-modal-overlay"></div>' +
                '<div class="heritage-modal-content">' +
                '<div class="heritage-modal-header">' +
                '<h3 class="heritage-modal-title"></h3>' +
                '<button class="heritage-modal-close">&times;</button>' +
                '</div>' +
                '<div class="heritage-modal-body"></div>' +
                '</div>' +
                '</div>'
            );
        }
        
        // Modal close handlers
        $(document).on('click', '.heritage-modal-close, .heritage-modal-overlay', function() {
            closeModal();
        });
        
        // Escape key to close modal
        $(document).on('keydown', function(e) {
            if (e.keyCode === 27 && $('.heritage-modal').is(':visible')) {
                closeModal();
            }
        });
    }

    /**
     * Show individual in modal
     */
    function showIndividualModal(individual) {
        var modalTitle = individual.given_names + ' ' + individual.surname;
        var modalBody = buildIndividualModalContent(individual);
        
        $('.heritage-modal-title').text(modalTitle);
        $('.heritage-modal-body').html(modalBody);
        $('.heritage-modal').fadeIn();
    }

    /**
     * Build individual modal content
     */
    function buildIndividualModalContent(individual) {
        var html = '<div class="heritage-individual-modal">';
        
        // Basic information
        html += '<div class="heritage-detail-group">';
        html += '<div class="heritage-detail-label">Full Name</div>';
        html += '<div class="heritage-detail-value">' + escapeHtml(individual.given_names + ' ' + individual.surname) + '</div>';
        html += '</div>';
        
        if (individual.gender) {
            html += '<div class="heritage-detail-group">';
            html += '<div class="heritage-detail-label">Gender</div>';
            html += '<div class="heritage-detail-value">' + escapeHtml(individual.gender) + '</div>';
            html += '</div>';
        }
        
        if (individual.birth_date) {
            html += '<div class="heritage-detail-group">';
            html += '<div class="heritage-detail-label">Birth Date</div>';
            html += '<div class="heritage-detail-value">' + escapeHtml(individual.birth_date) + '</div>';
            html += '</div>';
        }
        
        if (individual.death_date) {
            html += '<div class="heritage-detail-group">';
            html += '<div class="heritage-detail-label">Death Date</div>';
            html += '<div class="heritage-detail-value">' + escapeHtml(individual.death_date) + '</div>';
            html += '</div>';
        }
        
        if (individual.notes) {
            html += '<div class="heritage-detail-group">';
            html += '<div class="heritage-detail-label">Notes</div>';
            html += '<div class="heritage-detail-value">' + escapeHtml(individual.notes) + '</div>';
            html += '</div>';
        }
        
        html += '</div>';
        return html;
    }

    /**
     * Close modal
     */
    function closeModal() {
        $('.heritage-modal').fadeOut();
    }

    /**
     * Utility functions
     */
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatDate(dateString) {
        if (!dateString) return '';
        var date = new Date(dateString);
        return date.toLocaleDateString();
    }

    // Global functions for external use
    window.HeritagePress = window.HeritagePress || {};
    window.HeritagePress.frontend = {
        showIndividualModal: showIndividualModal,
        closeModal: closeModal,
        performSearch: function(query) {
            // Public search function
            if (typeof performSearch === 'function') {
                performSearch(query);
            }
        }
    };

})(jQuery);

// Default configuration (will be overridden by localized script)
window.heritage_press_frontend = window.heritage_press_frontend || {
    ajax_url: '',
    nonce: '',
    messages: {
        loading: 'Loading...',
        no_results: 'No results found.',
        error: 'An error occurred.'
    }
};

/* Modal CSS (inline for simplicity) */
jQuery(document).ready(function($) {
    if ($('.heritage-modal-styles').length === 0) {
        $('head').append(
            '<style class="heritage-modal-styles">' +
            '.heritage-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999; }' +
            '.heritage-modal-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); }' +
            '.heritage-modal-content { position: relative; background: #fff; width: 90%; max-width: 600px; margin: 50px auto; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }' +
            '.heritage-modal-header { padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }' +
            '.heritage-modal-title { margin: 0; font-size: 20px; color: #2c3e50; }' +
            '.heritage-modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #7f8c8d; }' +
            '.heritage-modal-close:hover { color: #2c3e50; }' +
            '.heritage-modal-body { padding: 20px; max-height: 70vh; overflow-y: auto; }' +
            '.heritage-individual-modal .heritage-detail-group { margin-bottom: 15px; padding: 10px; background: #f8f9fa; border-radius: 4px; }' +
            '.heritage-individual-modal .heritage-detail-label { font-weight: bold; color: #34495e; margin-bottom: 5px; }' +
            '.heritage-individual-modal .heritage-detail-value { color: #555; }' +
            '.heritage-loading, .heritage-no-results, .heritage-error { text-align: center; padding: 20px; color: #7f8c8d; }' +
            '.heritage-error { color: #e74c3c; }' +
            '</style>'
        );
    }
});
