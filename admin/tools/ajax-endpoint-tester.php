<?php
/**
 * Heritage Press AJAX Endpoint Tester
 * 
 * This tool tests the AJAX endpoints in the Heritage Press plugin
 * to ensure they're working correctly in a WordPress environment
 * 
 * @package HeritagePress\Tools
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Heritage Press AJAX Endpoint Tester class
 */
class Heritage_Press_AJAX_Tester {
    /**
     * AJAX endpoints to test
     */
    private $endpoints = [
        'heritage_press_get_individual',
        'heritage_press_get_family',
        'heritage_press_get_source',
        'heritage_press_get_event',
        'heritage_press_get_place',
        'heritage_press_search'
    ];
    
    /**
     * Results of tests
     */
    private $results = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->setup_page();
    }
    
    /**
     * Set up the page
     */
    private function setup_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Heritage Press AJAX Endpoint Tester', 'heritage-press'); ?></h1>
            
            <div class="notice notice-info">
                <p><?php _e('This tool tests the AJAX endpoints to ensure they are functioning correctly.', 'heritage-press'); ?></p>
            </div>
            
            <?php $this->endpoint_list(); ?>
            
            <div id="ajax-test-results" style="margin-top: 20px; padding: 15px; background-color: #f7f7f7;">
                <h3><?php _e('Test Results', 'heritage-press'); ?></h3>
                <div id="results-container">
                    <p><?php _e('Click "Test All Endpoints" to begin testing.', 'heritage-press'); ?></p>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Test a single endpoint
            $('.test-endpoint').click(function(e) {
                e.preventDefault();
                var endpoint = $(this).data('endpoint');
                testEndpoint(endpoint);
            });
            
            // Test all endpoints
            $('#test-all-endpoints').click(function(e) {
                e.preventDefault();
                $('#results-container').html('<p><?php _e('Testing in progress...', 'heritage-press'); ?></p>');
                
                var endpoints = <?php echo json_encode($this->endpoints); ?>;
                var results = [];
                var testsCompleted = 0;
                
                // Test each endpoint sequentially
                function testNext(index) {
                    if (index >= endpoints.length) {
                        displayFinalResults(results);
                        return;
                    }
                    
                    testEndpoint(endpoints[index], function(result) {
                        results.push(result);
                        testsCompleted++;
                        testNext(index + 1);
                    });
                }
                
                testNext(0);
            });
            
            // Test individual endpoint
            function testEndpoint(endpoint, callback) {
                var resultContainer = $('#result-' + endpoint);
                resultContainer.html('<span style="color: blue;"><?php _e('Testing...', 'heritage-press'); ?></span>');
                
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: endpoint,
                        test: true,
                        _wpnonce: '<?php echo wp_create_nonce('heritage_press_ajax_test'); ?>'
                    },
                    success: function(response) {
                        var success = false;
                        var message = '';
                        
                        try {
                            // Check if response is valid JSON
                            var data = JSON.parse(response);
                            success = data.success === true;
                            message = data.message || '<?php _e('Endpoint responded correctly', 'heritage-press'); ?>';
                        } catch (e) {
                            success = false;
                            message = '<?php _e('Invalid JSON response', 'heritage-press'); ?>';
                        }
                        
                        var result = {
                            endpoint: endpoint,
                            success: success,
                            message: message
                        };
                        
                        displayResult(resultContainer, result);
                        
                        if (typeof callback === 'function') {
                            callback(result);
                        }
                    },
                    error: function(xhr, status, error) {
                        var result = {
                            endpoint: endpoint,
                            success: false,
                            message: '<?php _e('AJAX request failed', 'heritage-press'); ?>: ' + error
                        };
                        
                        displayResult(resultContainer, result);
                        
                        if (typeof callback === 'function') {
                            callback(result);
                        }
                    }
                });
            }
            
            // Display individual result
            function displayResult(container, result) {
                var html = result.success ?
                    '<span style="color: green;">✓ <?php _e('Success', 'heritage-press'); ?></span>: ' + result.message :
                    '<span style="color: red;">✗ <?php _e('Failed', 'heritage-press'); ?></span>: ' + result.message;
                    
                container.html(html);
            }
            
            // Display final results
            function displayFinalResults(results) {
                var successCount = 0;
                var failCount = 0;
                var html = '<h4><?php _e('Test Summary', 'heritage-press'); ?></h4>';
                
                // Count successes and failures
                results.forEach(function(result) {
                    if (result.success) {
                        successCount++;
                    } else {
                        failCount++;
                    }
                });
                
                // Display summary
                html += '<p>';
                html += '<span style="color: green;">' + successCount + ' <?php _e('successful', 'heritage-press'); ?></span>, ';
                html += '<span style="color: red;">' + failCount + ' <?php _e('failed', 'heritage-press'); ?></span>';
                html += '</p>';
                
                // Display table of results
                html += '<table class="widefat fixed" style="margin-top: 10px;">';
                html += '<thead><tr>';
                html += '<th><?php _e('Endpoint', 'heritage-press'); ?></th>';
                html += '<th><?php _e('Status', 'heritage-press'); ?></th>';
                html += '<th><?php _e('Message', 'heritage-press'); ?></th>';
                html += '</tr></thead><tbody>';
                
                results.forEach(function(result) {
                    html += '<tr>';
                    html += '<td>' + result.endpoint + '</td>';
                    html += '<td>' + (result.success ? 
                        '<span style="color: green;">✓</span>' : 
                        '<span style="color: red;">✗</span>') + '</td>';
                    html += '<td>' + result.message + '</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
                
                // Recommendations
                html += '<div style="margin-top: 15px; padding: 10px; background-color: #e7f0f5; border-left: 4px solid #00a0d2;">';
                html += '<h4><?php _e('Recommendations', 'heritage-press'); ?></h4>';
                
                if (failCount === 0) {
                    html += '<p><?php _e('All endpoints are functioning correctly!', 'heritage-press'); ?></p>';
                } else {
                    html += '<p><?php _e('There are issues with some AJAX endpoints. Please check:', 'heritage-press'); ?></p>';
                    html += '<ul style="list-style-type: disc; margin-left: 20px;">';
                    html += '<li><?php _e('AJAX action hooks are properly registered', 'heritage-press'); ?></li>';
                    html += '<li><?php _e('Nonce verification is implemented correctly', 'heritage-press'); ?></li>';
                    html += '<li><?php _e('PHP errors or exceptions in the endpoint handlers', 'heritage-press'); ?></li>';
                    html += '<li><?php _e('Required capabilities/permissions are set properly', 'heritage-press'); ?></li>';
                    html += '</ul>';
                }
                
                html += '</div>';
                
                $('#results-container').html(html);
            }
        });
        </script>
        <?php
    }
    
    /**
     * Display endpoint list
     */
    private function endpoint_list() {
        ?>
        <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px;">
            <h2><?php _e('AJAX Endpoints', 'heritage-press'); ?></h2>
            
            <p><?php _e('The following AJAX endpoints are registered by Heritage Press:', 'heritage-press'); ?></p>
            
            <table class="widefat" style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th><?php _e('Endpoint', 'heritage-press'); ?></th>
                        <th><?php _e('Purpose', 'heritage-press'); ?></th>
                        <th><?php _e('Status', 'heritage-press'); ?></th>
                        <th><?php _e('Action', 'heritage-press'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->endpoints as $endpoint): ?>
                        <tr>
                            <td><code><?php echo esc_html($endpoint); ?></code></td>
                            <td><?php echo $this->get_endpoint_description($endpoint); ?></td>
                            <td id="result-<?php echo esc_attr($endpoint); ?>">
                                <span style="color: #888;"><?php _e('Not tested', 'heritage-press'); ?></span>
                            </td>
                            <td>
                                <a href="#" class="button button-small test-endpoint" data-endpoint="<?php echo esc_attr($endpoint); ?>">
                                    <?php _e('Test', 'heritage-press'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <p style="margin-top: 20px;">
                <a href="#" id="test-all-endpoints" class="button button-primary">
                    <?php _e('Test All Endpoints', 'heritage-press'); ?>
                </a>
            </p>
        </div>
        <?php
    }
    
    /**
     * Get endpoint description
     */
    private function get_endpoint_description($endpoint) {
        $descriptions = [
            'heritage_press_get_individual' => __('Retrieves individual record data', 'heritage-press'),
            'heritage_press_get_family' => __('Retrieves family record data', 'heritage-press'),
            'heritage_press_get_source' => __('Retrieves source record data', 'heritage-press'),
            'heritage_press_get_event' => __('Retrieves event record data', 'heritage-press'),
            'heritage_press_get_place' => __('Retrieves place record data', 'heritage-press'),
            'heritage_press_search' => __('Searches across all genealogy records', 'heritage-press')
        ];
        
        return isset($descriptions[$endpoint]) ? $descriptions[$endpoint] : __('Unknown purpose', 'heritage-press');
    }
}

// Initialize the tester
new Heritage_Press_AJAX_Tester();
