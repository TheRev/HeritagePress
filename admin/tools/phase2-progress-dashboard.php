<?php
/**
 * Heritage Press - Phase 2 Progress Dashboard
 * Tracks Phase 2 development progress and milestones
 * 
 * @package HeritagePress
 * @version 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add Phase 2 progress dashboard to admin menu
 */
function heritage_press_add_phase2_dashboard_menu() {
    add_submenu_page(
        'heritage-press',
        'Phase 2 Progress',
        '📊 Phase 2 Progress',
        'manage_heritage_press',
        'heritage-phase2-progress',
        'heritage_press_phase2_progress_page'
    );
}
add_action('admin_menu', 'heritage_press_add_phase2_dashboard_menu');

/**
 * Display Phase 2 progress dashboard
 */
function heritage_press_phase2_progress_page() {
    // Phase 2 milestones and progress
    $milestones = [
        'wordpress_integration' => [
            'title' => 'WordPress Integration Testing',
            'description' => 'Comprehensive WordPress compatibility testing',
            'tasks' => [
                'live_testing_tool' => ['name' => 'Live Testing Tool', 'status' => 'completed'],
                'admin_interface_check' => ['name' => 'Admin Interface Verification', 'status' => 'in_progress'],
                'ajax_endpoint_testing' => ['name' => 'AJAX Endpoint Testing', 'status' => 'completed'],
                'performance_optimization' => ['name' => 'Performance Optimization', 'status' => 'pending']
            ]
        ],
        'gedcom_enhancement' => [
            'title' => 'Enhanced GEDCOM Parser',
            'description' => 'GEDCOM 7.0 compliance with progress tracking',
            'tasks' => [
                'enhanced_parser' => ['name' => 'Enhanced Parser Class', 'status' => 'completed'],
                'progress_tracking' => ['name' => 'Progress Tracking', 'status' => 'in_progress'],
                'error_recovery' => ['name' => 'Error Recovery System', 'status' => 'pending'],
                'batch_processing' => ['name' => 'Batch Processing', 'status' => 'pending']
            ]
        ],
        'family_management' => [
            'title' => 'Family Management System',
            'description' => 'Complete family relationship management',
            'tasks' => [
                'family_editing' => ['name' => 'Family Editing Interface', 'status' => 'pending'],
                'relationship_validation' => ['name' => 'Relationship Validation', 'status' => 'pending'],
                'family_merge_split' => ['name' => 'Family Merge/Split', 'status' => 'pending'],
                'family_tree_display' => ['name' => 'Family Tree Display', 'status' => 'completed']
            ]
        ],
        'public_interface' => [
            'title' => 'Public Interface',
            'description' => 'Frontend genealogy displays for visitors',
            'tasks' => [
                'individual_profiles' => ['name' => 'Individual Profile Pages', 'status' => 'pending'],
                'responsive_trees' => ['name' => 'Responsive Family Trees', 'status' => 'pending'],
                'public_search' => ['name' => 'Public Search Interface', 'status' => 'pending'],
                'mobile_optimization' => ['name' => 'Mobile Optimization', 'status' => 'pending']
            ]
        ]
    ];
    
    // Calculate overall progress
    $total_tasks = 0;
    $completed_tasks = 0;
    $in_progress_tasks = 0;
    
    foreach ($milestones as $milestone) {
        foreach ($milestone['tasks'] as $task) {
            $total_tasks++;
            if ($task['status'] === 'completed') {
                $completed_tasks++;
            } elseif ($task['status'] === 'in_progress') {
                $in_progress_tasks++;
            }
        }
    }
    
    $completion_percentage = round(($completed_tasks / $total_tasks) * 100, 1);
    $progress_percentage = round((($completed_tasks + ($in_progress_tasks * 0.5)) / $total_tasks) * 100, 1);
    
    ?>
    <div class="wrap">
        <h1>📊 Heritage Press - Phase 2 Progress Dashboard</h1>
        <p>Track Phase 2 development milestones and completion status</p>
        
        <!-- Overall Progress -->
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🎯 Overall Phase 2 Progress</h2>
            <div style="display: flex; gap: 20px; margin: 20px 0;">
                <div style="background: #0073aa; color: white; padding: 20px; border-radius: 8px; text-align: center; flex: 1;">
                    <h3 style="margin: 0; color: white; font-size: 24px;"><?php echo $completion_percentage; ?>%</h3>
                    <p style="margin: 5px 0 0 0;">Tasks Completed</p>
                </div>
                <div style="background: #46b450; color: white; padding: 20px; border-radius: 8px; text-align: center; flex: 1;">
                    <h3 style="margin: 0; color: white; font-size: 24px;"><?php echo $completed_tasks; ?>/<?php echo $total_tasks; ?></h3>
                    <p style="margin: 5px 0 0 0;">Total Tasks</p>
                </div>
                <div style="background: #ffb900; color: white; padding: 20px; border-radius: 8px; text-align: center; flex: 1;">
                    <h3 style="margin: 0; color: white; font-size: 24px;"><?php echo $in_progress_tasks; ?></h3>
                    <p style="margin: 5px 0 0 0;">In Progress</p>
                </div>
            </div>
            
            <!-- Progress Bar -->
            <div style="background: #f1f1f1; height: 20px; border-radius: 10px; overflow: hidden; margin: 20px 0;">
                <div style="background: linear-gradient(90deg, #0073aa, #46b450); height: 100%; width: <?php echo $progress_percentage; ?>%; transition: width 0.3s ease;"></div>
            </div>
            <p style="text-align: center; color: #666;">Phase 2 Progress: <?php echo $progress_percentage; ?>%</p>
        </div>
        
        <!-- Milestone Details -->
        <?php foreach ($milestones as $key => $milestone): ?>
            <?php
            $milestone_total = count($milestone['tasks']);
            $milestone_completed = count(array_filter($milestone['tasks'], function($task) { return $task['status'] === 'completed'; }));
            $milestone_progress = round(($milestone_completed / $milestone_total) * 100, 1);
            ?>
            
            <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <div>
                        <h3 style="margin: 0;"><?php echo $milestone['title']; ?></h3>
                        <p style="margin: 5px 0 0 0; color: #666;"><?php echo $milestone['description']; ?></p>
                    </div>
                    <div style="text-align: right;">
                        <strong style="font-size: 18px;"><?php echo $milestone_progress; ?>%</strong><br>
                        <small style="color: #666;"><?php echo $milestone_completed; ?>/<?php echo $milestone_total; ?> tasks</small>
                    </div>
                </div>
                
                <!-- Task List -->
                <div style="margin-top: 15px;">
                    <?php foreach ($milestone['tasks'] as $task_key => $task): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; margin: 5px 0; background: #f9f9f9; border-radius: 5px;">
                            <span><?php echo $task['name']; ?></span>
                            <span class="task-status-<?php echo $task['status']; ?>" style="
                                padding: 5px 12px; 
                                border-radius: 15px; 
                                font-size: 12px; 
                                font-weight: bold;
                                <?php 
                                switch($task['status']) {
                                    case 'completed':
                                        echo 'background: #46b450; color: white;';
                                        break;
                                    case 'in_progress':
                                        echo 'background: #ffb900; color: white;';
                                        break;
                                    case 'pending':
                                        echo 'background: #ddd; color: #666;';
                                        break;
                                }
                                ?>
                            ">
                                <?php 
                                switch($task['status']) {
                                    case 'completed': echo '✅ Completed'; break;
                                    case 'in_progress': echo '🚧 In Progress'; break;
                                    case 'pending': echo '⏳ Pending'; break;
                                }
                                ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <!-- Phase 2 Timeline -->
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>📅 Phase 2 Timeline</h2>
            <div style="border-left: 3px solid #0073aa; padding-left: 20px; margin: 20px 0;">
                <div style="margin-bottom: 20px;">
                    <h4 style="margin: 0; color: #0073aa;">Week 1: WordPress Integration ✅</h4>
                    <p style="margin: 5px 0; color: #666;">May 31 - June 6, 2025</p>
                    <p>WordPress compatibility testing and integration fixes</p>
                </div>
                <div style="margin-bottom: 20px;">
                    <h4 style="margin: 0; color: #ffb900;">Week 2: GEDCOM Enhancement 🚧</h4>
                    <p style="margin: 5px 0; color: #666;">June 7 - June 13, 2025</p>
                    <p>Enhanced GEDCOM 7.0 parser with progress tracking</p>
                </div>
                <div style="margin-bottom: 20px;">
                    <h4 style="margin: 0; color: #ddd;">Week 3: Family Management ⏳</h4>
                    <p style="margin: 5px 0; color: #666;">June 14 - June 20, 2025</p>
                    <p>Complete family relationship management system</p>
                </div>
                <div style="margin-bottom: 20px;">
                    <h4 style="margin: 0; color: #ddd;">Week 4: Public Interface ⏳</h4>
                    <p style="margin: 5px 0; color: #666;">June 21 - June 27, 2025</p>
                    <p>Frontend genealogy displays and mobile optimization</p>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div style="background: #e7f3ff; padding: 20px; margin: 20px 0; border-left: 4px solid #0073aa;">
            <h3>🚀 Quick Actions</h3>
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <a href="<?php echo admin_url('admin.php?page=heritage-phase2-live-testing'); ?>" class="button button-primary">
                    🧪 Run Live Tests
                </a>
                <a href="<?php echo admin_url('admin.php?page=heritage-gedcom-upload'); ?>" class="button button-secondary">
                    📁 Test GEDCOM Upload
                </a>
                <a href="<?php echo admin_url('admin.php?page=heritage-family-tree-test'); ?>" class="button button-secondary">
                    🌳 Test Family Tree
                </a>
                <a href="<?php echo admin_url('admin.php?page=heritage-integration-test'); ?>" class="button button-secondary">
                    🔧 Integration Test
                </a>
            </div>
        </div>
        
        <!-- Phase 2 Notes -->
        <div style="background: #fff3cd; padding: 15px; margin: 15px 0; border-left: 4px solid #ffc107;">
            <h4>📝 Phase 2 Development Notes</h4>
            <ul>
                <li><strong>Started:</strong> May 31, 2025</li>
                <li><strong>Focus:</strong> GEDCOM Integration Enhancement & WordPress Optimization</li>
                <li><strong>Current Week:</strong> Week 1 - WordPress Integration Testing</li>
                <li><strong>Next Priority:</strong> Enhanced GEDCOM parser with progress tracking</li>
                <li><strong>Target Completion:</strong> End of June 2025</li>
            </ul>
        </div>
    </div>
    
    <style>
    .task-status-completed { animation: pulse-green 2s infinite; }
    .task-status-in_progress { animation: pulse-orange 2s infinite; }
    
    @keyframes pulse-green {
        0% { box-shadow: 0 0 0 0 rgba(70, 180, 80, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(70, 180, 80, 0); }
        100% { box-shadow: 0 0 0 0 rgba(70, 180, 80, 0); }
    }
    
    @keyframes pulse-orange {
        0% { box-shadow: 0 0 0 0 rgba(255, 185, 0, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(255, 185, 0, 0); }
        100% { box-shadow: 0 0 0 0 rgba(255, 185, 0, 0); }
    }
    </style>
    <?php
}
