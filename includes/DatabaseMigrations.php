<?php

class DatabaseMigrations
{
    // https://codex.wordpress.org/Creating_Tables_with_Plugins
    private $tableName;
    
    public function __construct()
    {
        global $wpdb;
        $this->tableName = $wpdb->prefix . "gallery_scrapper_sources";
        
    }

    function createSourcesTable()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $this->tableName (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  scrape_id mediumint(9) NULL,
  path text DEFAULT '' NOT NULL,
  attribute varchar(20) DEFAULT '' NOT NULL,
  alt_attribute varchar(20) DEFAULT '' NOT NULL,
  PRIMARY KEY  (id)  
) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

    }

    function deleteTable()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "DROP TABLE $this->tableName";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}