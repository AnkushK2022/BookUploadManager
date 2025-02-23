<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Admin menu setup for Book Uploads
function bookuploads_admin_menu() {
    add_menu_page(
        'Book Upload Manager',
        'Book Upload Manager',
        'manage_options',
        'bookupload-manager',
        'bookupload_display_books',
        'dashicons-book',
        6
    );

    // Add the submenu for viewing the book details
    add_submenu_page(
        'bookupload-manager',
        'View Book Details',
        'View Book',
        'manage_options',
        'bookupload-view-book',
        'bookupload_view_book_details'
    );
}
add_action('admin_menu', 'bookuploads_admin_menu');

// Admin page to display the list of books
function bookupload_display_books() {
    global $wpdb;
    $table_books = $wpdb->prefix . 'books';

    // Get all books
    $books = $wpdb->get_results("SELECT * FROM $table_books ORDER BY date_uploaded DESC", ARRAY_A);
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Book Uploads</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Author</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($books) : ?>
                    <?php foreach ($books as $book) : ?>
                        <tr>
                            <td><?php echo esc_html($book['book_title']); ?></td>
                            <td><?php echo esc_html(get_term($book['book_category'], 'product_cat')->name); ?></td>
                            <td>
                                <?php 
                                    // Get the author's name
                                    $author = get_user_by('id', $book['author_id']);
                                    echo esc_html($author ? $author->display_name : 'Unknown Author');
                                ?>
                            </td>
                            <td>
                                <?php 
                                // Determine the status color
                                $status_color = '';
                                $status_class = '';

                                if ($book['status'] === 'pending') {
                                    $status_color = 'orange';  // Pending status color
                                    $status_class = 'status-pending';
                                } elseif ($book['status'] === 'approved') {
                                    $status_color = 'green';  // Approved status color
                                    $status_class = 'status-approved';
                                } elseif ($book['status'] === 'rejected') {
                                    $status_color = 'red';  // Rejected status color
                                    $status_class = 'status-rejected';
                                }
                                ?>

                                <span class="<?php echo esc_attr($status_class); ?>" style="color: <?php echo esc_attr($status_color); ?>;">
                                    <?php echo esc_html(ucfirst($book['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($book['status'] === 'pending') : ?>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=bookupload-manager&book_id=' . $book['id'] . '&action=approve'), 'approve_book_' . $book['id']); ?>">Approve</a> |
                                    <a href="#" class="reject-link" data-book-id="<?php echo esc_attr($book['id']); ?>" data-nonce="<?php echo wp_create_nonce('reject_book_' . $book['id']); ?>">Reject</a>
                                <?php else : ?>
                                    <?php echo esc_html(ucfirst($book['status'])); ?>
                                <?php endif; ?>
                                | <a href="<?php echo admin_url('admin.php?page=bookupload-view-book&book_id=' . $book['id']); ?>">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5">No Books Found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function () {
            const rejectLinks = document.querySelectorAll('.reject-link');
            rejectLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();

                    const bookId = this.getAttribute('data-book-id');
                    const nonce = this.getAttribute('data-nonce');
                    const row = this.closest('td'); // Get the row where the reject link is

                    // Check if the form already exists
                    if (row.querySelector('.rejection-form')) {
                        return; // If the form exists, do nothing
                    }

                    // Otherwise, generate the form HTML
                    const formHtml = `
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="book_id" value="${bookId}">
                            <input type="hidden" name="_wpnonce" value="${nonce}">
                            <textarea style="width: 100%" cols="5" name="rejection_reason" placeholder="Enter the rejection reason" required></textarea>
                            <button type="submit">Submit Rejection</button>
                        </form>
                    `;

                    // Inject form below the reject link
                    row.insertAdjacentHTML('beforeend', `<span class="rejection-form">${formHtml}</span>`);
                });
            });
        });
    </script>
    <?php
}


// Admin page to view the details of a specific book
function bookupload_view_book_details() {
    global $wpdb;
    $book_id = isset($_GET['book_id']) ? (int) $_GET['book_id'] : 0;

    if (!$book_id) {
        echo 'Invalid Book ID';
        return;
    }

    $table_books = $wpdb->prefix . 'books';
    $book = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_books WHERE id = %d", $book_id), ARRAY_A);

    if (!$book) {
        echo 'Book not found.';
        return;
    }

    // Get the author's name
    $author = get_user_by('id', $book['author_id']);
    $author_name = $author ? $author->display_name : 'Unknown Author';
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Book Details</h1>
        <table class="form-table">
            <tr>
                <th>Title</th>
                <td><?php echo esc_html($book['book_title']); ?></td>
            </tr>
            <tr>
                <th>Category</th>
                <td><?php echo esc_html(get_term($book['book_category'], 'product_cat')->name); ?></td>
            </tr>
            <tr>
                <th>Short Description</th>
                <td><?php echo esc_html($book['book_short_desc']); ?></td>
            </tr>
            <tr>
                <th>Long Description</th>
                <td><?php echo esc_html($book['book_long_desc']); ?></td>
            </tr>
            <tr>
                <th>Author</th> <!-- Display Author Name -->
                <td><?php echo esc_html($author_name); ?></td>
            </tr>
            <tr>
                <th>Book PDF</th>
                <td><a href="<?php echo esc_url($book['book_pdf']); ?>" target="_blank">View PDF</a></td>
            </tr>
            <tr>
                <th>Featured Image</th>
                <td><img src="<?php echo esc_url($book['featured_image']); ?>" alt="Featured Image" style="max-width: 200px; max-height: 200px;" /></td>
            </tr>
            <tr>
                <th>Date Uploaded</th>
                <td><?php echo esc_html($book['date_uploaded']); ?></td>
            </tr>
            <tr>
                <th>Status</th>
                <td>
                    <?php 
                    // Determine the status color
                    $status_color = '';
                    $status_class = '';

                    if ($book['status'] === 'pending') {
                        $status_color = 'orange';  // Pending status color
                        $status_class = 'status-pending';
                    } elseif ($book['status'] === 'approved') {
                        $status_color = 'green';  // Approved status color
                        $status_class = 'status-approved';
                    } elseif ($book['status'] === 'rejected') {
                        $status_color = 'red';  // Rejected status color
                        $status_class = 'status-rejected';
                    }
                    ?>

                    <span class="<?php echo esc_attr($status_class); ?>" style="color: <?php echo esc_attr($status_color); ?>;">
                        <?php echo esc_html(ucfirst($book['status'])); ?>
                    </span>
                </td>
            </tr>
            <?php if($book['rejection_reason']) : ?>
            <tr>
                <th>Reason For Rejection</th>
                <td><?php echo esc_html($book['rejection_reason']); ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    <?php
}



// Handle book approval and rejection
function bookuploads_handle_approve_reject() {
    if (isset($_GET['book_id'], $_GET['action']) && isset($_GET['_wpnonce'])) {
        $book_id = intval($_GET['book_id']);
        $action = sanitize_text_field($_GET['action']);

        if (wp_verify_nonce($_GET['_wpnonce'], $action . '_book_' . $book_id) && current_user_can('manage_options')) {
            global $wpdb;
            $table_books = $wpdb->prefix . 'books';

            if ($action === 'approve') {
                $wpdb->update($table_books, ['status' => 'approved'], ['id' => $book_id]);
                
                // Create WooCommerce product on approval
                create_woocommerce_product_from_book($book_id);
            } elseif ($action === 'reject') {
                $wpdb->update($table_books, ['status' => 'rejected'], ['id' => $book_id]);
            }

            wp_redirect(admin_url('admin.php?page=bookupload-manager'));
            exit;
        }
    }
}
add_action('admin_init', 'bookuploads_handle_approve_reject');