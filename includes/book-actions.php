<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

function bookupload_handle_actions() {
    global $wpdb;
    $table_books = $wpdb->prefix . 'books';

    // Handle POST request for rejection
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reject') {
        $book_id = intval($_POST['book_id']);
        $nonce = $_POST['_wpnonce'];

        // Verify nonce for security
        if (!wp_verify_nonce($nonce, 'reject_book_' . $book_id)) {
            wp_die('Nonce verification failed');
        }

        // Validate rejection reason
        if (isset($_POST['rejection_reason']) && !empty($_POST['rejection_reason'])) {
            $rejection_reason = sanitize_textarea_field($_POST['rejection_reason']);

            // Update the book status to "rejected" and store the rejection reason
            $update_result = $wpdb->update(
                $table_books,
                [
                    'status' => 'rejected',
                    'rejection_reason' => $rejection_reason,
                ],
                ['id' => $book_id]
            );

            // Check if the update was successful
            if ($update_result === false) {
                wp_die('Failed to update book status.');
            }

            // Fetch book and author details
            $book = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_books WHERE id = %d", $book_id), ARRAY_A);
            if ($book) {
                $author = get_user_by('id', $book['author_id']);
                if ($author) {
                    $author_email = $author->user_email;
                    $author_name = $author->display_name;

                    // Send rejection email with reason
                    wp_mail(
                        $author_email,
                        'Your Book Has Been Rejected',
                        'Dear ' . esc_html($author_name) . ',<br><br>' .
                        'We regret to inform you that your book titled "' . esc_html($book['book_title']) . '" has been rejected.<br>' .
                        '<strong>Reason:</strong> ' . esc_html($rejection_reason) . '<br><br>' .
                        'Thank you for understanding.<br><br>' .
                        'Best regards,<br>Team',
                        ['Content-Type: text/html; charset=UTF-8']
                    );
                }
            }

            // Redirect back to the books list
            wp_redirect(admin_url('admin.php?page=bookupload-manager'));
            exit;
        } else {
            wp_die('Rejection reason cannot be empty.');
        }
    }

    // Handle GET request for other actions (approve)
    if (isset($_GET['action']) && isset($_GET['book_id']) && isset($_GET['_wpnonce'])) {
        $action = sanitize_text_field($_GET['action']);
        $book_id = intval($_GET['book_id']);
        $nonce = $_GET['_wpnonce'];

        // Verify nonce for security
        if (!wp_verify_nonce($nonce, $action . '_book')) {
            wp_die('Nonce verification failed');
        }

        $book = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_books WHERE id = %d", $book_id), ARRAY_A);

        if ($book && $action === 'approve') {
            // Update the book status to approved
            $wpdb->update(
                $table_books,
                ['status' => 'approved'],
                ['id' => $book_id]
            );

            // Get the author details
            $author = get_user_by('id', $book['author_id']);
            if ($author) {
                $author_email = $author->user_email;

                // Send approval email
                wp_mail(
                    $author_email,
                    'Your Book Has Been Approved!',
                    'Congratulations! Your book "' . esc_html($book['book_title']) . '" has been approved.',
                    ['Content-Type: text/html; charset=UTF-8']
                );
            }

            // Include the file that creates the WooCommerce product
            require_once(plugin_dir_path(__FILE__) . 'includes/create-woo-product.php');
            create_woocommerce_product_from_book($book_id);

            // Redirect back to the books list
            wp_redirect(admin_url('admin.php?page=bookupload-manager'));
            exit;
        }
    }
}
add_action('admin_init', 'bookupload_handle_actions');

