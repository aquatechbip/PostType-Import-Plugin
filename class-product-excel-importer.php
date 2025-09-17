<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Load SimpleXLSX library
if ( file_exists( PEI_PLUGIN_DIR . 'includes/simplexlsx.class.php' ) ) {
    //require_once PEI_PLUGIN_DIR . 'includes/simplexlsx.class.php';
    require_once plugin_dir_path( __FILE__ ) . 'simplexlsx.class.php';

} else {
    add_action( 'admin_notices', function() {
        echo '<div class="error"><p><b>Product Excel Importer:</b> simplexlsx.class.php file not found.</p></div>';
    });
    return;
}

class Product_Excel_Importer {

    public static function init() {
        add_action( 'admin_menu', [__CLASS__, 'admin_menu'] );
    }

    public static function admin_menu() {
        add_menu_page(
            'Product Importer',
            'Product Importer',
            'manage_options',
            'product-excel-importer',
            [__CLASS__, 'import_page'],
            'dashicons-upload',
            25
        );
    }

    public static function import_page() {
        ?>
        <div class="wrap">
            <h1>Import Products from Excel</h1>
            <form method="post" enctype="multipart/form-data">
                <input type="file" name="excel_file" accept=".xlsx,.xls">
                <?php submit_button('Import Now'); ?>
            </form>
        </div>
        <?php

        if ( isset($_FILES['excel_file']) && !empty($_FILES['excel_file']['tmp_name']) ) {
            self::import_file($_FILES['excel_file']['tmp_name']);
        }
    }

    public static function import_file($file) {
        if ( $xlsx = \Shuchkin\SimpleXLSX::parse($file) ) {
            $imported = 0;
            $skipped  = 0;
            //print_r($xlsx->rows()); die('vvvvvvvv');
            foreach ($xlsx->rows() as $k => $r) {
                if ($k == 0) continue; // skip header row

                /*              
                $category    = sanitize_text_field($r[0]); // Level 1
                $subcategory = sanitize_text_field($r[1]); // Level 2
                $product     = sanitize_text_field($r[2]); // Level 3 (Product)
                */

                $subcategory    = sanitize_text_field($r[2]); // Level 1
                $category       = sanitize_text_field($r[1]); // Level 2
                $product        = sanitize_text_field($r[0]); // Level 3 (Product)

                if (empty($product)) continue;

                // ✅ Check duplicate product
                /*$existing = get_page_by_title($product, OBJECT, 'product_cpt');
                if ($existing) {
                    $skipped++;
                    continue;
                }*/

                $existing_query = new WP_Query([
                    'post_type'      => 'product_cpt',
                    'title'          => $product,
                    'posts_per_page' => 1,
                    'fields'         => 'ids'
                ]);

                if ($existing_query->have_posts()) {
                    $skipped++;
                    continue;
                }


                // Insert Category (Level 1)
            $parent_id = 0;
            if ($category) {
                $parent_term = term_exists($category, 'product_category');
                if (!$parent_term) {
                    $parent_term = wp_insert_term($category, 'product_category');
                }
                $parent_id = is_array($parent_term) ? $parent_term['term_id'] : $parent_term;
            }

            // Insert Sub-category (Level 2) under Category
            $child_id = 0;
            if ($subcategory) {
                $child_term = term_exists($subcategory, 'product_category');
                if (!$child_term) {
                    $child_term = wp_insert_term($subcategory, 'product_category', [
                        'parent' => $parent_id
                    ]);
                } else {
                    // যদি parent mismatch থাকে, তাহলে update করো
                    if (is_array($child_term) && isset($child_term['term_id'])) {
                        wp_update_term($child_term['term_id'], 'product_category', [
                            'parent' => $parent_id
                        ]);
                    }
                }
                $child_id = is_array($child_term) ? $child_term['term_id'] : $child_term;
            }

            // Insert Product CPT
            $post_id = wp_insert_post([
                'post_title'  => $product,
                'post_type'   => 'product_cpt',
                'post_status' => 'publish'
            ]);

            // Assign taxonomy (subcategory > category > product)
            if ($post_id) {
                $terms = [];
                if ($child_id) {
                    $terms[] = $child_id;
                }
                if ($parent_id) {
                    $terms[] = $parent_id;
                }
                if ($terms) {
                    wp_set_post_terms($post_id, $terms, 'product_category');
                }
            }


                $imported++;
            }

            echo '<div class="updated"><p>✅ Import completed. Imported: ' . $imported . ', Skipped (duplicates): ' . $skipped . '</p></div>';
        } else {
            echo '<div class="error"><p>❌ Error: ' . SimpleXLSX::parseError() . '</p></div>';
        }
    }
}
