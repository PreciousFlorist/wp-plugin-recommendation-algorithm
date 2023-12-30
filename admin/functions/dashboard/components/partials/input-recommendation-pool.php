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
$current_limit = get_option('local_storage_limit_' . $post_type, 20);

?>

<p>
    <label for="recommendation_limit">Set the Limit for the number of Recommendations in Local Storage.</label>
    <input type="number" id="recommendation_limit" style="margin-top: 13px; min-width: 100%;" name="recommendation_limit" value="<?php echo esc_attr($current_limit); ?>" min="1" step="1" required>
</p>