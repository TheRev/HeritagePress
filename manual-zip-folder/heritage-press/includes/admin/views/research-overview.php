<?php
/**
 * Research Overview Dashboard
 * 
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="heritage-evidence-dashboard">
        <?php if (!empty($file_id)): ?>
            <div class="file-selector">
                <h2>Research Dashboard - File: <?php echo esc_html($file_id); ?></h2>
            </div>

            <?php if (!empty($dashboard_data)): ?>
                <div class="dashboard-stats">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <h3>Research Questions</h3>
                            <div class="stat-number"><?php echo $dashboard_data['research_questions']['total'] ?? 0; ?></div>
                            <div class="stat-details">
                                <span class="open"><?php echo $dashboard_data['research_questions']['open'] ?? 0; ?> Open</span>
                                <span class="resolved"><?php echo $dashboard_data['research_questions']['resolved'] ?? 0; ?> Resolved</span>
                            </div>
                        </div>

                        <div class="stat-card">
                            <h3>Information Statements</h3>
                            <div class="stat-number"><?php echo $dashboard_data['information_statements']['total'] ?? 0; ?></div>
                            <div class="stat-details">
                                <span class="verified"><?php echo $dashboard_data['information_statements']['verified'] ?? 0; ?> Verified</span>
                                <span class="unverified"><?php echo $dashboard_data['information_statements']['unverified'] ?? 0; ?> Unverified</span>
                            </div>
                        </div>

                        <div class="stat-card">
                            <h3>Evidence Analyses</h3>
                            <div class="stat-number"><?php echo $dashboard_data['evidence_analyses']['total'] ?? 0; ?></div>
                            <div class="stat-details">
                                <span class="direct"><?php echo $dashboard_data['evidence_analyses']['direct'] ?? 0; ?> Direct</span>
                                <span class="indirect"><?php echo $dashboard_data['evidence_analyses']['indirect'] ?? 0; ?> Indirect</span>
                            </div>
                        </div>

                        <div class="stat-card">
                            <h3>Proof Arguments</h3>
                            <div class="stat-number"><?php echo $dashboard_data['proof_arguments']['total'] ?? 0; ?></div>
                            <div class="stat-details">
                                <span class="proven"><?php echo $dashboard_data['proof_arguments']['proven'] ?? 0; ?> Proven</span>
                                <span class="probable"><?php echo $dashboard_data['proof_arguments']['probable'] ?? 0; ?> Probable</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-sections">
                    <div class="section-grid">
                        <div class="section-card">
                            <h3>Recent Activity</h3>
                            <div class="activity-list">
                                <p>Recent research activities will be shown here.</p>
                            </div>
                        </div>

                        <div class="section-card">
                            <h3>Pending Reviews</h3>
                            <div class="review-list">
                                <?php if (!empty($dashboard_data['pending_reviews'])): ?>
                                    <ul>
                                        <li><?php echo $dashboard_data['pending_reviews']['questions_needing_attention']; ?> questions need attention</li>
                                        <li><?php echo $dashboard_data['pending_reviews']['analyses_needing_review']; ?> analyses need review</li>
                                        <li><?php echo $dashboard_data['pending_reviews']['arguments_awaiting_review']; ?> arguments awaiting review</li>
                                    </ul>
                                <?php else: ?>
                                    <p>No pending reviews.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="section-card">
                            <h3>Quick Actions</h3>
                            <div class="quick-actions">
                                <a href="<?php echo admin_url('admin.php?page=heritage-research-questions&action=add&file_id=' . urlencode($file_id)); ?>" class="button button-primary">
                                    New Research Question
                                </a>
                                <a href="<?php echo admin_url('admin.php?page=heritage-information-statements&action=add&file_id=' . urlencode($file_id)); ?>" class="button">
                                    Add Information Statement
                                </a>
                                <a href="<?php echo admin_url('admin.php?page=heritage-citation-tools'); ?>" class="button">
                                    Citation Tools
                                </a>
                            </div>
                        </div>

                        <div class="section-card">
                            <h3>Evidence Quality Overview</h3>
                            <div class="quality-overview">
                                <?php if (!empty($dashboard_data['evidence_analyses']['by_confidence'])): ?>
                                    <div class="confidence-distribution">
                                        <?php foreach ($dashboard_data['evidence_analyses']['by_confidence'] as $level => $count): ?>
                                            <div class="confidence-bar">
                                                <span class="label"><?php echo ucfirst($level); ?></span>
                                                <div class="bar">
                                                    <div class="fill confidence-<?php echo strtolower($level); ?>" style="width: <?php echo ($count / $dashboard_data['evidence_analyses']['total']) * 100; ?>%"></div>
                                                </div>
                                                <span class="count"><?php echo $count; ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p>No evidence analyses to display.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <div class="no-data">
                    <p>No research data available for this file.</p>
                    <a href="<?php echo admin_url('admin.php?page=heritage-research-questions&action=add&file_id=' . urlencode($file_id)); ?>" class="button button-primary">
                        Start Your First Research Question
                    </a>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="file-selector">
                <h2>Select a Genealogy File</h2>
                <p>Please select a genealogy file to view research data.</p>
                
                <form method="get" action="">
                    <input type="hidden" name="page" value="heritage-evidence" />
                    <select name="file_id" onchange="this.form.submit()">
                        <option value="">Select a file...</option>
                        <!-- File options would be populated here -->
                        <option value="sample-file-1">Sample Family Tree 1</option>
                        <option value="sample-file-2">Sample Family Tree 2</option>
                    </select>
                    <input type="submit" class="button" value="Load File" />
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.heritage-evidence-dashboard {
    margin-top: 20px;
}

.dashboard-stats {
    margin-bottom: 30px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
}

.stat-card h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #666;
    text-transform: uppercase;
}

.stat-number {
    font-size: 36px;
    font-weight: bold;
    color: #0073aa;
    line-height: 1;
}

.stat-details {
    margin-top: 10px;
    font-size: 12px;
    color: #666;
}

.stat-details span {
    display: inline-block;
    margin: 0 5px;
}

.section-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.section-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
}

.section-card h3 {
    margin: 0 0 15px 0;
    color: #333;
}

.quick-actions .button {
    display: block;
    margin-bottom: 10px;
    text-align: center;
}

.confidence-distribution {
    margin-top: 10px;
}

.confidence-bar {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
    font-size: 12px;
}

.confidence-bar .label {
    width: 60px;
    flex-shrink: 0;
}

.confidence-bar .bar {
    flex: 1;
    height: 16px;
    background: #f0f0f0;
    border-radius: 8px;
    margin: 0 10px;
    overflow: hidden;
}

.confidence-bar .fill {
    height: 100%;
    transition: width 0.3s ease;
}

.confidence-high { background: #4CAF50; }
.confidence-medium { background: #FF9800; }
.confidence-low { background: #F44336; }
.confidence-uncertain { background: #9E9E9E; }

.confidence-bar .count {
    width: 30px;
    text-align: right;
    flex-shrink: 0;
}

.file-selector {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.no-data {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 40px;
    text-align: center;
}
</style>
