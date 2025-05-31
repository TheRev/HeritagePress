/**
 * Heritage Press Plugin - Public JavaScript
 * Frontend JavaScript for public genealogy displays
 */

(function($) {
    'use strict';

    // Heritage Press Public Class
    class HeritagePress {
        constructor() {
            this.init();
        }

        init() {
            this.bindEvents();
            this.setupSearch();
            this.setupFamilyTree();
        }

        bindEvents() {
            // Search form handling
            $('.hp-search-form').on('submit', this.handleSearch.bind(this));
            
            // Individual card interactions
            $('.hp-individual-card').on('click', this.handleIndividualClick.bind(this));
            
            // Family tree navigation
            $('.hp-tree-person').on('click', this.handleTreePersonClick.bind(this));
            
            // Pagination
            $('.hp-pagination a').on('click', this.handlePagination.bind(this));
        }

        setupSearch() {
            const searchInput = $('.hp-search-input');
            let searchTimeout;

            // Live search with debouncing
            searchInput.on('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.performSearch(e.target.value);
                }, 300);
            });
        }

        setupFamilyTree() {
            // Initialize family tree interactions
            this.setupTreeNavigation();
            this.setupTreeTooltips();
        }

        setupTreeNavigation() {
            $('.hp-tree-person').each(function() {
                const $person = $(this);
                const personId = $person.data('person-id');
                
                if (personId) {
                    $person.css('cursor', 'pointer');
                }
            });
        }

        setupTreeTooltips() {
            $('.hp-tree-person').hover(
                function() {
                    const $this = $(this);
                    const tooltip = $this.data('tooltip');
                    if (tooltip) {
                        $this.attr('title', tooltip);
                    }
                },
                function() {
                    $(this).removeAttr('title');
                }
            );
        }

        handleSearch(e) {
            e.preventDefault();
            const searchTerm = $('.hp-search-input').val().trim();
            
            if (searchTerm.length < 2) {
                this.showMessage('Please enter at least 2 characters to search.', 'warning');
                return;
            }

            this.performSearch(searchTerm);
        }

        performSearch(searchTerm) {
            if (!searchTerm || searchTerm.length < 2) {
                return;
            }

            const $results = $('.hp-search-results');
            this.showLoading($results);

            // Make AJAX request to search
            $.ajax({
                url: heritagePress.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'heritage_press_search_individuals',
                    search: searchTerm,
                    nonce: heritagePress.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.displaySearchResults(response.data);
                    } else {
                        this.showMessage('Search failed: ' + response.data, 'error');
                    }
                },
                error: () => {
                    this.showMessage('Search request failed. Please try again.', 'error');
                },
                complete: () => {
                    this.hideLoading($results);
                }
            });
        }

        displaySearchResults(data) {
            const $results = $('.hp-search-results');
            
            if (!data.individuals || data.individuals.length === 0) {
                $results.html('<p class="hp-no-results">No individuals found matching your search.</p>');
                return;
            }

            let html = '<div class="hp-results-list">';
            
            data.individuals.forEach(individual => {
                html += this.buildIndividualCard(individual);
            });
            
            html += '</div>';
            
            // Add pagination if needed
            if (data.pagination) {
                html += this.buildPagination(data.pagination);
            }
            
            $results.html(html);
        }

        buildIndividualCard(individual) {
            const birthDate = individual.birth_date || '';
            const deathDate = individual.death_date || '';
            const dates = this.formatDateRange(birthDate, deathDate);
            
            return `
                <div class="hp-individual-card" data-person-id="${individual.id}">
                    <div class="hp-individual-name">${this.escapeHtml(individual.given_names)} ${this.escapeHtml(individual.surname)}</div>
                    <div class="hp-individual-dates">${dates}</div>
                    <div class="hp-individual-details">
                        ${individual.birth_place ? `
                            <div class="hp-detail-item">
                                <span class="hp-detail-label">Birth Place</span>
                                <span class="hp-detail-value">${this.escapeHtml(individual.birth_place)}</span>
                            </div>
                        ` : ''}
                        ${individual.death_place ? `
                            <div class="hp-detail-item">
                                <span class="hp-detail-label">Death Place</span>
                                <span class="hp-detail-value">${this.escapeHtml(individual.death_place)}</span>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }

        buildPagination(pagination) {
            if (pagination.total_pages <= 1) {
                return '';
            }

            let html = '<div class="hp-pagination">';
            
            // Previous page
            if (pagination.current_page > 1) {
                html += `<a href="#" data-page="${pagination.current_page - 1}">← Previous</a>`;
            }
            
            // Page numbers
            for (let i = 1; i <= pagination.total_pages; i++) {
                if (i === pagination.current_page) {
                    html += `<span class="current">${i}</span>`;
                } else {
                    html += `<a href="#" data-page="${i}">${i}</a>`;
                }
            }
            
            // Next page
            if (pagination.current_page < pagination.total_pages) {
                html += `<a href="#" data-page="${pagination.current_page + 1}">Next →</a>`;
            }
            
            html += '</div>';
            return html;
        }

        handleIndividualClick(e) {
            const $card = $(e.currentTarget);
            const personId = $card.data('person-id');
            
            if (personId) {
                // Navigate to individual page or load details
                window.location.href = `${heritagePress.baseUrl}/individual/${personId}`;
            }
        }

        handleTreePersonClick(e) {
            e.preventDefault();
            const $person = $(e.currentTarget);
            const personId = $person.data('person-id');
            
            if (personId) {
                this.loadFamilyTree(personId);
            }
        }

        handlePagination(e) {
            e.preventDefault();
            const page = $(e.currentTarget).data('page');
            const currentSearch = $('.hp-search-input').val();
            
            this.performSearch(currentSearch, page);
        }

        loadFamilyTree(personId) {
            const $treeContainer = $('.hp-family-tree');
            this.showLoading($treeContainer);

            $.ajax({
                url: heritagePress.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'heritage_press_get_family_tree',
                    person_id: personId,
                    nonce: heritagePress.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.displayFamilyTree(response.data);
                    } else {
                        this.showMessage('Failed to load family tree: ' + response.data, 'error');
                    }
                },
                error: () => {
                    this.showMessage('Family tree request failed. Please try again.', 'error');
                },
                complete: () => {
                    this.hideLoading($treeContainer);
                }
            });
        }

        displayFamilyTree(treeData) {
            // Implementation for displaying family tree
            // This would be expanded based on the specific tree structure
            console.log('Family tree data:', treeData);
        }

        formatDateRange(birthDate, deathDate) {
            const birth = birthDate ? this.formatDate(birthDate) : '';
            const death = deathDate ? this.formatDate(deathDate) : '';
            
            if (birth && death) {
                return `${birth} - ${death}`;
            } else if (birth) {
                return `b. ${birth}`;
            } else if (death) {
                return `d. ${death}`;
            }
            return '';
        }

        formatDate(dateString) {
            if (!dateString) return '';
            
            const date = new Date(dateString);
            if (isNaN(date.getTime())) {
                return dateString; // Return original if not a valid date
            }
            
            return date.toLocaleDateString();
        }

        showLoading($container) {
            $container.addClass('hp-loading-overlay');
            if (!$container.find('.hp-loading').length) {
                $container.append('<div class="hp-loading"></div>');
            }
        }

        hideLoading($container) {
            $container.removeClass('hp-loading-overlay');
            $container.find('.hp-loading').remove();
        }

        showMessage(message, type = 'info') {
            const $message = $(`<div class="hp-message hp-message-${type}">${message}</div>`);
            $('body').prepend($message);
            
            setTimeout(() => {
                $message.fadeOut(() => $message.remove());
            }, 5000);
        }

        escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    }

    // Initialize when document is ready
    $(document).ready(() => {
        new HeritagePress();
    });

})(jQuery);
