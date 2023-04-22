<?php

/**
 * Fired during plugin activation
 *
 * @link       https://codebears.io
 * @since      1.0.0
 *
 * @package    Gallery_Scrapper
 * @subpackage Gallery_Scrapper/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Gallery_Scrapper
 * @subpackage Gallery_Scrapper/includes
 * @author     Nikola <nikola@codebears.io>
 */
class Gallery_Scrapper_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/DatabaseMigrations.php';
        $databaseMigration = new DatabaseMigrations();
        $databaseMigration->createSourcesTable();

    }

}
