<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// AJAX handler for saving the book data
add_action('wp_ajax_submit_book_upload', 'handle_book_upload');
add_action('wp_ajax_nopriv_submit_book_upload', 'handle_book_upload');

function handle_book_upload() {
    // Check if all required POST fields are set
    if (!isset($_POST['book_title'], $_POST['book_category'], $_POST['book_short_desc'], $_POST['book_long_desc'], $_FILES['book_pdf'])) {
        wp_send_json_error(['message' => 'Required fields are missing']);
    }

    // Get the form data
    $book_title = sanitize_text_field($_POST['book_title']);
    $book_category = intval($_POST['book_category']);
    $book_short_desc = sanitize_textarea_field($_POST['book_short_desc']);
    $book_long_desc = sanitize_textarea_field($_POST['book_long_desc']);

    // Handle the PDF upload
    $pdf_file = $_FILES['book_pdf'];
    if ($pdf_file['error'] != 0) {
        wp_send_json_error(['message' => 'PDF upload failed']);
    }

    // Upload the PDF file to the WordPress media library
    $upload_dir = wp_upload_dir();
    $pdf_file_path = $upload_dir['path'] . '/' . basename($pdf_file['name']);
    if (move_uploaded_file($pdf_file['tmp_name'], $pdf_file_path)) {
        $pdf_url = $upload_dir['url'] . '/' . basename($pdf_file['name']);
    } else {
        wp_send_json_error(['message' => 'Failed to upload PDF']);
    }

    // Handle the featured image upload (optional)
    $featured_image_url = '';
    if (!empty($_FILES['book_featured_image']['name'])) {
        $image_file = $_FILES['book_featured_image'];
        if ($image_file['error'] == 0) {
            $image_id = media_handle_upload('book_featured_image', 0);
            if (is_wp_error($image_id)) {
                wp_send_json_error(['message' => 'Failed to upload image']);
            }
            $featured_image_url = wp_get_attachment_url($image_id);
        }
    }

    // Insert the data into the database
    global $wpdb;
    $table_books = $wpdb->prefix . 'books';
    $wpdb->insert(
        $table_books,
        [
            'book_title' => $book_title,
            'book_category' => $book_category,
            'book_short_desc' => $book_short_desc,
            'book_long_desc' => $book_long_desc,
            'author_id' => get_current_user_id(),  // Or another method to get the author ID
            'book_pdf' => $pdf_url,
            'featured_image' => $featured_image_url,
            'date_uploaded' => current_time('mysql'),
            'status' => 'pending',
        ]
    );

    if ($wpdb->last_error) {
        wp_send_json_error(['message' => 'Database error: ' . $wpdb->last_error]);
    }

    // Success response
    wp_send_json_success(['message' => 'Book uploaded successfully!']);
}
