<?php
/*
Plugin Name: Book Uploads manager
Description: A plugin to allow users to upload their books and store data in a custom table.
Version: 1.0
Author: TAS
*/

// Exit if accessed directly
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
ob_start();

// Include the necessary files for the plugin
require_once(plugin_dir_path(__FILE__) . 'admin/admin-menu.php');
require_once(plugin_dir_path(__FILE__) . 'includes/book-functions.php');
require_once(plugin_dir_path(__FILE__) . 'includes/book-actions.php');
require_once(plugin_dir_path(__FILE__) . 'includes/create-woo-product.php');


function book_plugin_create_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_books = $wpdb->prefix . 'books';

    $sql_books = "CREATE TABLE IF NOT EXISTS $table_books (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        book_title VARCHAR(255) NOT NULL,
        book_category BIGINT(20) UNSIGNED NOT NULL,
        book_short_desc TEXT NOT NULL,
        book_long_desc TEXT NOT NULL,
        author_id BIGINT(20) UNSIGNED NOT NULL,
        book_pdf VARCHAR(255) NOT NULL,
        featured_image VARCHAR(255) NOT NULL,
        rejection_reason VARCHAR(255),
        date_uploaded DATETIME NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_books);
}
// Activation hook to create database table
register_activation_hook(__FILE__, 'book_plugin_create_table');


function book_plugin_delete_table(){
    global $wpdb;
    $table_name = $wpdb->prefix . 'books';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}
// Deactivation hook to delete tables.
register_deactivation_hook(__FILE__, 'book_plugin_delete_table');


// Shortcode to display the book upload form
function bookuploads_display_upload_form() {
    // Check if the user is logged in
    if (!is_user_logged_in()) {
        echo "<div style='text-align: center;'>
                You need to register and log in as a Book Author to submit your book.
                <br/><br/>
                <a href='/become-a-book-author' style='text-decoration: none;'>
                    <button type='button'>Become a Book Author</button>
                </a>
            </div>";
        return;
    }

    // Get the current user's roles
    $current_user = wp_get_current_user();

    // Check if the user has the role 'Book Author' or 'Administrator'
    if (!in_array('book_author', $current_user->roles) && !in_array('administrator', $current_user->roles)) {
        echo "<div style='text-align: center;'>
                You need to register as a Book Author to submit your book.
                <br/><br/>
                <a href='/become-a-book-author' style='text-decoration: none;'>
                    <button type='button'>Become a Book Author</button>
                </a>
            </div>";
        return;
    }

    // Display the upload form
    ob_start();
    include(plugin_dir_path(__FILE__) . 'templates/book-upload-form.php');
    return ob_get_clean();
}
add_shortcode('book_upload_form', 'bookuploads_display_upload_form');