<?php

// Check if the file is being accessed directly
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


// Make sure the necessary variables are set
if (!isset($post_type)) {
    return; // Exit if variables not set
}

// Fetch the current limit from the options table or set a default value
$current_limit = get_option('batch_size' . $post_type, 15);

?>

<p>
    <label for="post_batch_size">Specify how many contexts can be updated at a time when deploying updates to the databse.</label>
    <input type="number" id="post_batch_size" style="margin-top: 13px; min-width: 100%;" name="post_batch_size" value="<?php echo esc_attr($current_limit); ?>" min="1" step="1" required>
</p>