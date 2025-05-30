<?php
/**
 * Evidence Manager Class - Extensions for Elizabeth Shown Mills Evidence Methodology
 *
 * This class extends the basic GEDCOM 7 sourcing with Evidence Explained methodology
 * to support the Source → Information → Evidence → Proof framework.
 *
 * @package HeritagePress
 */

namespace HeritagePress\Database;

class Evidence_Manager {
    
    /**
     * Create additional tables for Evidence methodology
     */
    public function create_evidence_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_prefix = $wpdb->prefix . 'heritage_press_';

        $sql = array();

        // Information Statements table - tracks individual pieces of information from sources
        $sql[] = "CREATE TABLE {$table_prefix}information_statements (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid varchar(36) NOT NULL,
            file_id varchar(36) NOT NULL,
            source_id bigint(20) UNSIGNED NOT NULL,
            citation_id bigint(20) UNSIGNED NULL, /* Optional link to specific citation */
            statement_text text NOT NULL, /* The actual information statement from the source */
            statement_type ENUM('PRIMARY', 'SECONDARY', 'HEARSAY', 'MIXED') NOT NULL,
            information_quality ENUM('FIRSTHAND', 'SECONDHAND', 'THIRDHAND', 'UNKNOWN') NOT NULL,
            specific_location varchar(255) NULL, /* Page, line, paragraph, image coordinates */
            transcription_notes text NULL, /* Notes about transcription accuracy, legibility */
            language_original varchar(10) NULL, /* Original language if transcribed/translated */
            informant_assessment text NULL, /* Assessment of informant's knowledge */
            context_notes text NULL, /* Surrounding context that affects interpretation */
            extraction_date datetime DEFAULT CURRENT_TIMESTAMP,
            extracted_by_user_id bigint(20) UNSIGNED NULL,
            verification_status ENUM('UNVERIFIED', 'VERIFIED', 'QUESTIONED', 'DISPROVEN') DEFAULT 'UNVERIFIED',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uuid (uuid),
            KEY file_id (file_id),
            KEY source_id (source_id),
            KEY citation_id (citation_id),
            KEY statement_type (statement_type),
            KEY information_quality (information_quality),
            CONSTRAINT fk_info_statements_source FOREIGN KEY (source_id) REFERENCES {$table_prefix}sources(id) ON DELETE CASCADE,
            CONSTRAINT fk_info_statements_citation FOREIGN KEY (citation_id) REFERENCES {$table_prefix}citations(id) ON DELETE SET NULL
        ) $charset_collate;";

        // Evidence Analysis table - interpretations of information statements
        $sql[] = "CREATE TABLE {$table_prefix}evidence_analysis (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid varchar(36) NOT NULL,
            file_id varchar(36) NOT NULL,
            information_statement_id bigint(20) UNSIGNED NOT NULL,
            research_question_id bigint(20) UNSIGNED NULL, /* Link to specific research question */
            evidence_type ENUM('DIRECT', 'INDIRECT', 'NEGATIVE') NOT NULL,
            relevance_score tinyint DEFAULT 5, /* 1-10 scale of relevance to research question */
            reliability_assessment text NULL, /* Detailed assessment of reliability */
            interpretation_notes text NOT NULL, /* How this information is interpreted as evidence */
            limitations text NULL, /* Known limitations or weaknesses of this evidence */
            corroboration_needed text NULL, /* What additional evidence is needed */
            conflicts_with text NULL, /* References to conflicting evidence */
            analyst_user_id bigint(20) UNSIGNED NULL,
            analysis_date datetime DEFAULT CURRENT_TIMESTAMP,
            confidence_level ENUM('HIGH', 'MEDIUM', 'LOW', 'UNCERTAIN') DEFAULT 'MEDIUM',
            evidence_weight ENUM('STRONG', 'MODERATE', 'WEAK', 'NEGLIGIBLE') DEFAULT 'MODERATE',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uuid (uuid),
            KEY file_id (file_id),
            KEY information_statement_id (information_statement_id),
            KEY research_question_id (research_question_id),
            KEY evidence_type (evidence_type),
            KEY confidence_level (confidence_level),
            CONSTRAINT fk_evidence_analysis_info FOREIGN KEY (information_statement_id) REFERENCES {$table_prefix}information_statements(id) ON DELETE CASCADE
        ) $charset_collate;";

        // Research Questions table - specific genealogical questions being investigated
        $sql[] = "CREATE TABLE {$table_prefix}research_questions (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid varchar(36) NOT NULL,
            file_id varchar(36) NOT NULL,
            question_text text NOT NULL,
            question_type ENUM('IDENTITY', 'RELATIONSHIP', 'EVENT', 'DATE', 'PLACE', 'OTHER') NOT NULL,
            individual_id bigint(20) UNSIGNED NULL, /* Primary subject of question */
            family_id bigint(20) UNSIGNED NULL, /* Family context if applicable */
            event_id bigint(20) UNSIGNED NULL, /* Event context if applicable */
            status ENUM('OPEN', 'RESOLVED', 'ABANDONED', 'ON_HOLD') DEFAULT 'OPEN',
            priority ENUM('HIGH', 'MEDIUM', 'LOW') DEFAULT 'MEDIUM',
            research_notes text NULL,
            methodology_notes text NULL, /* Research strategy and approach */
            created_by_user_id bigint(20) UNSIGNED NULL,
            assigned_to_user_id bigint(20) UNSIGNED NULL,
            target_resolution_date date NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uuid (uuid),
            KEY file_id (file_id),
            KEY individual_id (individual_id),
            KEY family_id (family_id),
            KEY event_id (event_id),
            KEY status (status),
            KEY priority (priority),
            CONSTRAINT fk_research_questions_individual FOREIGN KEY (individual_id) REFERENCES {$table_prefix}individuals(id) ON DELETE CASCADE,
            CONSTRAINT fk_research_questions_family FOREIGN KEY (family_id) REFERENCES {$table_prefix}families(id) ON DELETE CASCADE,
            CONSTRAINT fk_research_questions_event FOREIGN KEY (event_id) REFERENCES {$table_prefix}events(id) ON DELETE CASCADE
        ) $charset_collate;";

        // Proof Arguments table - assembled evidence supporting conclusions
        $sql[] = "CREATE TABLE {$table_prefix}proof_arguments (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid varchar(36) NOT NULL,
            file_id varchar(36) NOT NULL,
            research_question_id bigint(20) UNSIGNED NOT NULL,
            conclusion_statement text NOT NULL,
            argument_text longtext NOT NULL, /* Detailed proof argument */
            conclusion_type ENUM('PROVEN', 'PROBABLE', 'POSSIBLE', 'DISPROVEN', 'INDETERMINATE') NOT NULL,
            confidence_percentage tinyint NULL, /* 0-100% confidence in conclusion */
            methodology_used text NULL, /* GPS, BCG standards, etc. */
            evidence_summary text NULL, /* Summary of key evidence points */
            correlation_analysis text NULL, /* How evidence pieces fit together */
            gaps_identified text NULL, /* Known gaps in evidence */
            future_research_needed text NULL, /* Suggestions for additional research */
            peer_review_status ENUM('UNREVIEWED', 'UNDER_REVIEW', 'APPROVED', 'REJECTED') DEFAULT 'UNREVIEWED',
            reviewed_by_user_id bigint(20) UNSIGNED NULL,
            review_date datetime NULL,
            review_notes text NULL,
            published_status ENUM('DRAFT', 'INTERNAL', 'PUBLIC') DEFAULT 'DRAFT',
            created_by_user_id bigint(20) UNSIGNED NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uuid (uuid),
            KEY file_id (file_id),
            KEY research_question_id (research_question_id),
            KEY conclusion_type (conclusion_type),
            KEY peer_review_status (peer_review_status),
            CONSTRAINT fk_proof_arguments_question FOREIGN KEY (research_question_id) REFERENCES {$table_prefix}research_questions(id) ON DELETE CASCADE
        ) $charset_collate;";

        // Evidence-to-Proof Links table - links individual evidence to proof arguments
        $sql[] = "CREATE TABLE {$table_prefix}proof_evidence_links (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            proof_argument_id bigint(20) UNSIGNED NOT NULL,
            evidence_analysis_id bigint(20) UNSIGNED NOT NULL,
            evidence_role ENUM('PRIMARY', 'SUPPORTING', 'CONTRADICTORY', 'CONTEXTUAL') NOT NULL,
            weight_in_argument ENUM('CRITICAL', 'IMPORTANT', 'SUPPLEMENTARY', 'MINIMAL') NOT NULL,
            usage_notes text NULL, /* How this evidence is used in the argument */
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_proof_evidence (proof_argument_id, evidence_analysis_id),
            KEY proof_argument_id (proof_argument_id),
            KEY evidence_analysis_id (evidence_analysis_id),
            KEY evidence_role (evidence_role),
            CONSTRAINT fk_proof_evidence_proof FOREIGN KEY (proof_argument_id) REFERENCES {$table_prefix}proof_arguments(id) ON DELETE CASCADE,
            CONSTRAINT fk_proof_evidence_analysis FOREIGN KEY (evidence_analysis_id) REFERENCES {$table_prefix}evidence_analysis(id) ON DELETE CASCADE
        ) $charset_collate;";

        // Source Quality Assessment table - detailed Mills-style source evaluation
        $sql[] = "CREATE TABLE {$table_prefix}source_quality_assessments (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid varchar(36) NOT NULL,
            source_id bigint(20) UNSIGNED NOT NULL,
            assessment_type ENUM('ORIGINALITY', 'TIMELINESS', 'COMPLETENESS', 'INFORMANT_QUALITY') NOT NULL,
            
            /* Mills' Source Categories */
            originality ENUM('ORIGINAL', 'DERIVATIVE', 'AUTHORED_DERIVATIVE', 'UNKNOWN') NULL,
            information_type ENUM('PRIMARY', 'SECONDARY', 'MIXED', 'UNKNOWN') NULL,
            evidence_type ENUM('DIRECT', 'INDIRECT', 'NEGATIVE', 'MIXED') NULL,
            
            /* Detailed Quality Factors */
            creation_timeliness ENUM('CONTEMPORARY', 'NEAR_CONTEMPORARY', 'RETROSPECTIVE', 'UNKNOWN') NULL,
            creator_relationship ENUM('FIRSTHAND', 'WITNESS', 'INFORMANT', 'COMPILER', 'UNKNOWN') NULL,
            creator_reliability ENUM('OFFICIAL', 'PROFESSIONAL', 'KNOWLEDGEABLE', 'AVERAGE', 'QUESTIONABLE', 'UNKNOWN') NULL,
            preservation_quality ENUM('EXCELLENT', 'GOOD', 'FAIR', 'POOR', 'FRAGMENTARY') NULL,
            completeness ENUM('COMPLETE', 'MOSTLY_COMPLETE', 'PARTIAL', 'FRAGMENTARY', 'UNKNOWN') NULL,
            legibility ENUM('CLEAR', 'READABLE', 'DIFFICULT', 'BARELY_READABLE', 'ILLEGIBLE') NULL,
            
            /* Bias and Limitations */
            potential_bias text NULL, /* Known or suspected biases */
            limitations text NULL, /* Limitations of the source */
            strengths text NULL, /* Particular strengths */
            
            /* Assessment Details */
            assessment_notes longtext NULL,
            assessor_user_id bigint(20) UNSIGNED NULL,
            assessment_date datetime DEFAULT CURRENT_TIMESTAMP,
            overall_reliability_score tinyint NULL, /* 1-10 scale */
            
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uuid (uuid),
            KEY source_id (source_id),
            KEY assessment_type (assessment_type),
            KEY originality (originality),
            KEY information_type (information_type),
            KEY evidence_type (evidence_type),
            CONSTRAINT fk_source_quality_source FOREIGN KEY (source_id) REFERENCES {$table_prefix}sources(id) ON DELETE CASCADE
        ) $charset_collate;";

        foreach ($sql as $query) {
            $result = $wpdb->query($query);
            if ($result === false) {
                error_log("Evidence Manager SQL Error: " . $wpdb->last_error);
                error_log("Failed Query: " . $query);
            }
        }

        // Add foreign key for research questions in evidence analysis
        $wpdb->query("ALTER TABLE {$table_prefix}evidence_analysis 
            ADD CONSTRAINT fk_evidence_analysis_question 
            FOREIGN KEY (research_question_id) REFERENCES {$table_prefix}research_questions(id) ON DELETE SET NULL;");
    }

    /**
     * Get database table prefix
     */
    public static function get_table_prefix() {
        global $wpdb;
        return $wpdb->prefix . 'heritage_press_';
    }
}
