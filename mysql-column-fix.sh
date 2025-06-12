#!/bin/bash
# Direct MySQL column fixes for HeritagePress

echo "Adding missing columns to HeritagePress database..."

# MySQL connection details for MAMP
MYSQL_HOST="localhost"
MYSQL_USER="root"
MYSQL_PASS="root"
MYSQL_DB="wordpress"

# Execute SQL commands directly
mysql -h$MYSQL_HOST -u$MYSQL_USER -p$MYSQL_PASS $MYSQL_DB << EOF

-- Add missing columns
ALTER TABLE wp_hp_people ADD COLUMN IF NOT EXISTS person_id VARCHAR(50) NOT NULL AFTER gedcom;
ALTER TABLE wp_hp_families ADD COLUMN IF NOT EXISTS family_id VARCHAR(50) NOT NULL AFTER gedcom;
ALTER TABLE wp_hp_sources ADD COLUMN IF NOT EXISTS source_id VARCHAR(50) NOT NULL AFTER gedcom;
ALTER TABLE wp_hp_repositories ADD COLUMN IF NOT EXISTS name VARCHAR(255) NOT NULL AFTER repo_id;
ALTER TABLE wp_hp_media ADD COLUMN IF NOT EXISTS media_id VARCHAR(50) NOT NULL AFTER gedcom;

-- Show results
SHOW COLUMNS FROM wp_hp_people;
SHOW COLUMNS FROM wp_hp_families;
SHOW COLUMNS FROM wp_hp_sources;
SHOW COLUMNS FROM wp_hp_repositories;
SHOW COLUMNS FROM wp_hp_media;

EOF

echo "Column fixes completed!"
