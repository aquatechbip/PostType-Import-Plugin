<?php
/**
 * Plugin Name: Product Excel Importer
 * Description: Import products with categories & subcategories from Excel sheet into custom post type (product_cpt).
 * Version: 1.1
 * Author: Your Name
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'PEI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Check required files exist
if ( file_exists( PEI_PLUGIN_DIR . 'includes/class-product-excel-importer.php' ) ) {
    require_once PEI_PLUGIN_DIR . 'includes/class-product-excel-importer.php';
    //add_action( 'plugins_loaded', ['Product_Excel_Importer', 'init'] );
    function pei_init() {
    new Product_Excel_Importer();
}
add_action( 'plugins_loaded', 'pei_init' );
} else {
    add_action( 'admin_notices', function() {
        echo '<div class="error"><p><b>Product Excel Importer:</b> Missing required files in includes/</p></div>';
    });
}
