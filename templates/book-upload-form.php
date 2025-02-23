<div class="form-title"><h2>Upload Your Book</h2></div>
<form id="book-upload-form" class="apply-form" method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="submit_book_upload">

    <table class="form-table">
        <tr>
            <th><label for="book_title">Book Title *</label></th>
            <td><input type="text" id="book_title" name="book_title" required></td>
        </tr>

        <tr>
            <th><label for="book_category">Book Category *</label></th>
            <td>
                <select id="book_category" name="book_category" required>
                    <option value="">Select Category</option>
                    <?php
                    // Get all WooCommerce categories or custom categories
                    $args = array(
                        'taxonomy'   => 'product_cat',
                        'orderby'    => 'name',
                        'hide_empty' => false,
                    );
                    $categories = get_terms($args);
                    foreach ($categories as $category) {
                        echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
                    }
                    ?>
                </select>
            </td>
        </tr>

        <tr>
            <th><label for="book_short_desc">Short Description *</label></th>
            <td><textarea id="book_short_desc" name="book_short_desc" required></textarea></td>
        </tr>

        <tr>
            <th><label for="book_long_desc">Long Description *</label></th>
            <td><textarea id="book_long_desc" name="book_long_desc" required></textarea></td>
        </tr>

        <tr>
            <th><label for="book_featured_image">Featured Image *</label></th>
            <td><input type="file" id="book_featured_image" name="book_featured_image" accept="image/*"></td>
        </tr>

        <tr>
            <th><label for="book_pdf">Book PDF *<br/>(max-size="10MB")</label></th>
            <td><input type="file" id="book_pdf" name="book_pdf" accept="application/pdf" required></td>
        </tr>
    </table>

    <button type="button" id="submit-book-upload-form">Upload Book</i></button>
</form>

<!-- Script Section -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>
    $("#submit-book-upload-form").click(function(e) {
        e.preventDefault();

        var form = $("#book-upload-form");
        var actionUrl = "<?php echo admin_url('admin-ajax.php'); ?>";  // Use admin-ajax.php URL for the request

        // Check if all fields are filled
        var bookTitle = $("#book_title").val();
        var bookCategory = $("#book_category").val();
        var bookShortDesc = $("#book_short_desc").val();
        var bookLongDesc = $("#book_long_desc").val();
        var bookPdf = $("#book_pdf")[0].files[0];

        // Validate that all required fields are filled
        if (!bookTitle || !bookCategory || !bookShortDesc || !bookLongDesc || !bookPdf) {
            alert("Please fill in all the required fields.");
            return;
        }

        var formData = new FormData(form[0]);

        // File Validation Example (PDF and Image)
        var pdfFile = $("#book_pdf")[0].files[0];
        if (pdfFile && pdfFile.size > 10 * 1024 * 1024) {
            alert("PDF file is too large.");
            return;
        }

        var imgFile = $("#book_featured_image")[0].files[0];
        if (imgFile && !imgFile.type.startsWith("image/")) {
            alert("Please upload a valid image.");
            return;
        }

        // Show the rotating spinner after the submit button
        var spinnerHtml = `<div id='processing-spinner' class='spinner'></div>`;
        $("#submit-book-upload-form").after(spinnerHtml);

        $.ajax({
            type: "POST",
            url: actionUrl,
            data: formData,
            processData: false,
            contentType: false,
            success: function(data) {
                if (data.success) {
                    $("#book-upload-form").after("<div class='success-message'>" + data.message + "</div>");
                    // Reset the form after submission
                    form[0].reset();
                    // Clear the success message after a few seconds
                    setTimeout(function() {
                        $(".success-message").fadeOut();
                    }, 3000);
                } else {
                    $("#book-upload-form").after("<div class='error-message'>" + data.message + "</div>");
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred: ' + error);
            },
            complete: function() {
                // Remove the spinner after the request is complete
                $("#processing-spinner").remove();
            }
        });
    });
</script>
