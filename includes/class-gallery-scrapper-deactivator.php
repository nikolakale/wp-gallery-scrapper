<?php

class Gallery_Scrapper_Deactivator {
	public static function deactivate() {
        $timestamp = wp_next_scheduled( 'gallery_scrapper_cron_hook' );
        wp_unschedule_event( $timestamp, 'gallery_scrapper_cron_hook' );

        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/DatabaseMigrations.php';
        $databaseMigration = new DatabaseMigrations();
        $databaseMigration->deleteTable();
	}

}
