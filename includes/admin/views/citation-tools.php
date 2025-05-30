<?php
/**
 * Citation Tools Page
 * 
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>Citation Tools</h1>
    <p>Tools for formatting citations according to Evidence Explained standards.</p>

    <div class="heritage-citation-tools">
        <div class="tools-grid">
            <!-- Citation Formatter Tool -->
            <div class="tool-section">
                <h2>Citation Formatter</h2>
                <p>Format citations according to Evidence Explained style guidelines.</p>
                
                <form id="citation-formatter">
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="source_type">Source Type</label>
                                </th>
                                <td>
                                    <select id="source_type" name="source_type">
                                        <option value="">Select source type...</option>
                                        <option value="birth_record">Birth Record</option>
                                        <option value="death_record">Death Record</option>
                                        <option value="marriage_record">Marriage Record</option>
                                        <option value="census">Census</option>
                                        <option value="will">Will/Probate</option>
                                        <option value="land">Land Record</option>
                                        <option value="immigration">Immigration Record</option>
                                        <option value="military">Military Record</option>
                                        <option value="newspaper">Newspaper</option>
                                        <option value="book">Book/Published Work</option>
                                        <option value="church">Church Record</option>
                                        <option value="court">Court Record</option>
                                        <option value="other">Other</option>
                                    </select>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="source_title">Source Title</label>
                                </th>
                                <td>
                                    <input type="text" id="source_title" name="source_title" class="regular-text" />
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="author_creator">Author/Creator</label>
                                </th>
                                <td>
                                    <input type="text" id="author_creator" name="author_creator" class="regular-text" />
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="repository">Repository</label>
                                </th>
                                <td>
                                    <input type="text" id="repository" name="repository" class="regular-text" />
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="collection">Collection</label>
                                </th>
                                <td>
                                    <input type="text" id="collection" name="collection" class="regular-text" />
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="location">Location</label>
                                </th>
                                <td>
                                    <input type="text" id="location" name="location" class="regular-text" />
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="date_created">Date</label>
                                </th>
                                <td>
                                    <input type="text" id="date_created" name="date_created" class="regular-text" />
                                    <p class="description">Date of creation or publication</p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="page_number">Page Number</label>
                                </th>
                                <td>
                                    <input type="text" id="page_number" name="page_number" class="regular-text" />
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="volume_number">Volume/Book Number</label>
                                </th>
                                <td>
                                    <input type="text" id="volume_number" name="volume_number" class="regular-text" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <p class="submit">
                        <input type="button" id="format_citation" class="button button-primary" value="Format Citation" />
                        <input type="button" id="clear_form" class="button" value="Clear Form" />
                    </p>
                </form>
                
                <div id="formatted_citation_output" style="display: none;">
                    <h3>Formatted Citation</h3>
                    <div class="citation-output">
                        <div id="citation_text"></div>
                        <button type="button" id="copy_citation" class="button">Copy to Clipboard</button>
                    </div>
                </div>
            </div>

            <!-- Citation Templates -->
            <div class="tool-section">
                <h2>Citation Templates</h2>
                <p>Quick access to common citation templates.</p>
                
                <div class="template-grid">
                    <div class="template-card" data-template="vital_record">
                        <h4>Vital Records</h4>
                        <p>Birth, death, marriage certificates</p>
                        <button class="button load-template">Load Template</button>
                    </div>
                    
                    <div class="template-card" data-template="census">
                        <h4>Census Records</h4>
                        <p>Federal and state census</p>
                        <button class="button load-template">Load Template</button>
                    </div>
                    
                    <div class="template-card" data-template="probate">
                        <h4>Probate Records</h4>
                        <p>Wills, estate files, probate court</p>
                        <button class="button load-template">Load Template</button>
                    </div>
                    
                    <div class="template-card" data-template="land">
                        <h4>Land Records</h4>
                        <p>Deeds, grants, land patents</p>
                        <button class="button load-template">Load Template</button>
                    </div>
                    
                    <div class="template-card" data-template="church">
                        <h4>Church Records</h4>
                        <p>Baptisms, marriages, burials</p>
                        <button class="button load-template">Load Template</button>
                    </div>
                    
                    <div class="template-card" data-template="newspaper">
                        <h4>Newspapers</h4>
                        <p>Articles, obituaries, notices</p>
                        <button class="button load-template">Load Template</button>
                    </div>
                </div>
            </div>

            <!-- Source Quality Assessment -->
            <div class="tool-section">
                <h2>Source Quality Assessment</h2>
                <p>Assess source quality using Mills' methodology.</p>
                
                <form id="quality-assessment">
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">Source Originality</th>
                                <td>
                                    <label><input type="radio" name="originality" value="ORIGINAL" /> Original</label><br>
                                    <label><input type="radio" name="originality" value="DERIVATIVE" /> Derivative</label><br>
                                    <label><input type="radio" name="originality" value="AUTHORED_DERIVATIVE" /> Authored Derivative</label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">Information Type</th>
                                <td>
                                    <label><input type="radio" name="information_type" value="PRIMARY" /> Primary</label><br>
                                    <label><input type="radio" name="information_type" value="SECONDARY" /> Secondary</label><br>
                                    <label><input type="radio" name="information_type" value="MIXED" /> Mixed</label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">Evidence Directness</th>
                                <td>
                                    <label><input type="radio" name="evidence_directness" value="DIRECT" /> Direct</label><br>
                                    <label><input type="radio" name="evidence_directness" value="INDIRECT" /> Indirect</label><br>
                                    <label><input type="radio" name="evidence_directness" value="NEGATIVE" /> Negative</label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">Informant Reliability</th>
                                <td>
                                    <select name="informant_reliability">
                                        <option value="">Select...</option>
                                        <option value="OFFICIAL">Official/Professional</option>
                                        <option value="KNOWLEDGEABLE">Knowledgeable Person</option>
                                        <option value="AVERAGE">Average Informant</option>
                                        <option value="QUESTIONABLE">Questionable</option>
                                        <option value="UNKNOWN">Unknown</option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <p class="submit">
                        <input type="button" id="assess_quality" class="button button-primary" value="Assess Quality" />
                    </p>
                </form>
                
                <div id="quality_assessment_result" style="display: none;">
                    <h3>Quality Assessment Result</h3>
                    <div class="assessment-result">
                        <div id="overall_strength"></div>
                        <div id="strength_explanation"></div>
                    </div>
                </div>
            </div>

            <!-- Research Log -->
            <div class="tool-section">
                <h2>Research Log Entry</h2>
                <p>Quick entry for research activities.</p>
                
                <form id="research-log-entry">
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="research_date">Research Date</label>
                                </th>
                                <td>
                                    <input type="date" id="research_date" name="research_date" value="<?php echo date('Y-m-d'); ?>" />
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="repository_visited">Repository/Website</label>
                                </th>
                                <td>
                                    <input type="text" id="repository_visited" name="repository_visited" class="regular-text" />
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="research_objective">Research Objective</label>
                                </th>
                                <td>
                                    <textarea id="research_objective" name="research_objective" rows="3" cols="50"></textarea>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="sources_searched">Sources Searched</label>
                                </th>
                                <td>
                                    <textarea id="sources_searched" name="sources_searched" rows="4" cols="50"></textarea>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="results_found">Results Found</label>
                                </th>
                                <td>
                                    <textarea id="results_found" name="results_found" rows="4" cols="50"></textarea>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <p class="submit">
                        <input type="button" id="save_research_log" class="button button-primary" value="Save Log Entry" />
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Citation Formatter
    $('#format_citation').on('click', function() {
        var formData = $('#citation-formatter').serialize();
        formData += '&action=format_citation&nonce=' + heritage_evidence_ajax.nonce;
        
        $.post(heritage_evidence_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                $('#citation_text').html('<pre>' + response.data.citation + '</pre>');
                $('#formatted_citation_output').show();
            } else {
                alert('Error formatting citation: ' + response.data.message);
            }
        });
    });
    
    // Clear Form
    $('#clear_form').on('click', function() {
        $('#citation-formatter')[0].reset();
        $('#formatted_citation_output').hide();
    });
    
    // Copy Citation
    $('#copy_citation').on('click', function() {
        var citationText = $('#citation_text pre').text();
        navigator.clipboard.writeText(citationText).then(function() {
            alert('Citation copied to clipboard!');
        });
    });
    
    // Load Templates
    $('.load-template').on('click', function() {
        var template = $(this).closest('.template-card').data('template');
        loadCitationTemplate(template);
    });
    
    // Quality Assessment
    $('#assess_quality').on('click', function() {
        var formData = $('#quality-assessment').serialize();
        formData += '&action=assess_evidence_quality&nonce=' + heritage_evidence_ajax.nonce;
        
        $.post(heritage_evidence_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                $('#overall_strength').html('<strong>Overall Strength:</strong> ' + response.data.strength);
                $('#strength_explanation').html('<p>' + response.data.explanation + '</p>');
                $('#quality_assessment_result').show();
            } else {
                alert('Error assessing quality: ' + response.data.message);
            }
        });
    });
    
    // Research Log
    $('#save_research_log').on('click', function() {
        var formData = $('#research-log-entry').serialize();
        formData += '&action=save_research_log&nonce=' + heritage_evidence_ajax.nonce;
        
        $.post(heritage_evidence_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                alert('Research log entry saved!');
                $('#research-log-entry')[0].reset();
            } else {
                alert('Error saving log entry: ' + response.data.message);
            }
        });
    });
});

function loadCitationTemplate(template) {
    var templates = {
        'vital_record': {
            'source_type': 'birth_record',
            'repository': 'State Vital Records Office',
            'collection': 'Birth Certificates'
        },
        'census': {
            'source_type': 'census',
            'repository': 'National Archives',
            'collection': 'U.S. Federal Census'
        },
        'probate': {
            'source_type': 'will',
            'repository': 'County Probate Court',
            'collection': 'Probate Records'
        },
        'land': {
            'source_type': 'land',
            'repository': 'County Recorder of Deeds',
            'collection': 'Land Records'
        },
        'church': {
            'source_type': 'other',
            'repository': 'Church Archives'
        },
        'newspaper': {
            'source_type': 'newspaper',
            'repository': 'Newspaper Archives'
        }
    };
    
    if (templates[template]) {
        var data = templates[template];
        Object.keys(data).forEach(function(key) {
            $('#' + key).val(data[key]);
        });
    }
}
</script>

<style>
.heritage-citation-tools {
    margin-top: 20px;
}

.tools-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 30px;
}

.tool-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
}

.tool-section h2 {
    margin-top: 0;
    color: #333;
}

.template-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.template-card {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    text-align: center;
}

.template-card h4 {
    margin: 0 0 8px 0;
    color: #333;
}

.template-card p {
    margin: 0 0 12px 0;
    font-size: 12px;
    color: #666;
}

.citation-output {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    margin-top: 10px;
}

.citation-output pre {
    background: #fff;
    border: 1px solid #ddd;
    padding: 10px;
    margin: 0 0 10px 0;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.assessment-result {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    margin-top: 10px;
}

.form-table th {
    width: 200px;
}

.form-table input[type="radio"] {
    margin-right: 5px;
}

.form-table label {
    display: inline-block;
    margin-bottom: 5px;
}
</style>
