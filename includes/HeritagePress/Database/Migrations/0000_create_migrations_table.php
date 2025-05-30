<?php
namespace HeritagePress\Database\Migrations;

class CreateMigrationsTable {    public static function up() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'heritage_press_migrations';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            migration varchar(255) NOT NULL,
            batch int NOT NULL,
            executed_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY migration (migration)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }    public static function down() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'heritage_press_migrations';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
}
