<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

function create_woocommerce_product_from_book($book_id) {
    global $wpdb;
    
    // Get the book details from the database
    $table_books = $wpdb->prefix . 'books';
    $book = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_books WHERE id = %d", $book_id), ARRAY_A);
    
    if (!$book) {
        return;
    }

    // Set the author ID (defaults to 0 if author is unknown)
    $author_id = isset($book['author_id']) ? intval($book['author_id']) : 0;

    // Create a new WooCommerce product
    $post_data = array(
        'post_title'    => sanitize_text_field($book['book_title']), // Book Title
        'post_content'  => sanitize_textarea_field($book['book_long_desc']), // Long Description
        'post_excerpt'  => sanitize_textarea_field($book['book_short_desc']), // Short Description
        'post_status'   => 'publish', // Set the status as published
        'post_type'     => 'product', // Product post type
    );
    
    // Insert the product into the WordPress database
    $product_id = wp_insert_post($post_data);

    if (is_wp_error($product_id)) {
        return;
    }

    // Set the price to 0 (free product)
    update_post_meta($product_id, '_regular_price', '0');
    update_post_meta($product_id, '_price', '0');

    // Mark the product as downloadable and virtual
    update_post_meta($product_id, '_downloadable', 'yes');
    update_post_meta($product_id, '_virtual', 'yes');

    // Optionally, set a featured image if available
    if (!empty($book['featured_image'])) {
        $image_id = attachment_url_to_postid($book['featured_image']);
        if ($image_id) {
            set_post_thumbnail($product_id, $image_id);
        }
    }

    // Assign the product category
    if (!empty($book['book_category'])) {
        $category_id = (int) $book['book_category']; // Ensure it's an integer
        $category_name = get_term($category_id, 'product_cat')->name;

        // Set category to the product
        wp_set_object_terms($product_id, $category_id, 'product_cat');
    }

    // Add the downloadable file using WooCommerce API
    add_book_pdf_as_downloadable_file($product_id, $book['book_pdf'], $book['book_title']);
}

/**
 * Adds the book PDF as a downloadable file to the WooCommerce product.
 *
 * @param int    $product_id The WooCommerce product ID.
 * @param string $file_url   The URL of the downloadable PDF.
 * @param string $file_title The title of the downloadable file.
 */
function add_book_pdf_as_downloadable_file($product_id, $file_url, $file_title) {
    // Validate the file URL
    if (empty($file_url)) {
        return;
    }

    // Create a new WC_Product_Download instance
    $download = new WC_Product_Download();

    // Prepare the downloadable file details
    $file_md5 = md5($file_url);
    $download->set_name($file_title); // Name of the file
    $download->set_id($file_md5);    // Unique ID for the file
    $download->set_file($file_url);  // File URL

    // Get the product object
    $product = wc_get_product($product_id);
    if (!$product) {
        return;
    }

    // Get the existing downloads (if any) and add the new one
    $downloads = (array) $product->get_downloads();
    $downloads[$file_md5] = $download;

    // Set the downloads for the product
    $product->set_downloads($downloads);
    $product->save();
}
