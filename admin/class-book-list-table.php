<?php
if (!class_exists('Book_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');

    class Book_List_Table extends WP_List_Table {

        // Prepare the list data
        public function prepare_items() {
            global $wpdb;

            // Get the books from the custom table
            $table_books = $wpdb->prefix . 'books';

            // Get all books (you can add conditions like 'status' for approved/rejected)
            $query = "SELECT * FROM $table_books";
            $books = $wpdb->get_results($query, ARRAY_A);

            // Set up data for table
            $this->items = $books;

            // Define columns
            $columns = $this->get_columns();
            $this->_column_headers = array($columns, array(), array());
        }

        // Define columns
        public function get_columns() {
            return array(
                'book_title'     => 'Book Title',
                'book_category'  => 'Category',
                'author_id'      => 'Author',
                'status'         => 'Status',
                'actions'        => 'Actions', // Actions column
            );
        }

        // Display column content
        public function column_default($item, $column_name) {
            switch ($column_name) {
                case 'book_title':
                    return esc_html($item['book_title']);
                case 'book_category':
                    return esc_html($item['book_category']);
                case 'author_id':
                    $author = get_userdata($item['author_id']);
                    return esc_html($author ? $author->display_name : 'Unknown');
                case 'status':
                    return esc_html($item['status']); // Display the status (approved/rejected)
                case 'actions':
                    $approve_url = wp_nonce_url(admin_url('admin.php?page=bookuploads&action=approve&book_id=' . $item['id']), 'approve_book');
                    $reject_url = wp_nonce_url(admin_url('admin.php?page=bookuploads&action=reject&book_id=' . $item['id']), 'reject_book');
                    $view_url = admin_url('post.php?post=' . $item['id'] . '&action=edit');
                    
                    return sprintf(
                        '<a href="%s">View</a> | <a href="%s">Approve</a> | <a href="%s">Reject</a>',
                        esc_url($view_url),
                        esc_url($approve_url),
                        esc_url($reject_url)
                    );
                default:
                    return print_r($item, true); // Show the whole array for debugging
            }
        }
    }
}
