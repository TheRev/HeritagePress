<?php
/**
 * SQL statements for creating the family_relationships table
 */

$table_prefix = $wpdb->prefix . 'heritage_press_';

// Family Relationships table
$sql = "CREATE TABLE IF NOT EXISTS {$table_prefix}family_relationships (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid varchar(36) NOT NULL,
    file_id varchar(36) NOT NULL,
    individual_id bigint(20) UNSIGNED NOT NULL,
    family_id bigint(20) UNSIGNED NOT NULL,
    relationship_type varchar(20) NOT NULL COMMENT 'child, husband, wife, partner',
    pedigree_type varchar(20) NULL COMMENT 'birth, adoption, foster, sealing',
    birth_order int(11) NULL,
    is_current tinyint(1) DEFAULT 1,
    notes text NULL,
    shared_note_id bigint(20) UNSIGNED NULL,
    privacy tinyint(1) DEFAULT 0,
    status varchar(20) NOT NULL DEFAULT 'active',
    created_at datetime NOT NULL,
    updated_at datetime NOT NULL,
    deleted_at datetime NULL,
    PRIMARY KEY (id),
    KEY individual_id (individual_id),
    KEY family_id (family_id),
    KEY file_id (file_id),
    KEY relationship_type (relationship_type),
    KEY pedigree_type (pedigree_type),
    UNIQUE KEY uuid_fileid (uuid, file_id)
) {$charset_collate};";

// Execute SQL
dbDelta($sql);
