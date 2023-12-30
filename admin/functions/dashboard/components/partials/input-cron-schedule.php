<?php

// Check if the file is being accessed directly
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


// Make sure the necessary variables are set
if (!isset($post_type)) {
    return; // Exit if variables not setdelete_registered_options
}


// Retrieve data needed for the form
$interval_setting = 'db_sync_interval_setting_' . $post_type;
$current_interval = get_option($interval_setting, 'hourly');

?>

<p>
    <label for="<?= $interval_setting ?>">Specify how frequently you would like to fetch the top rated posts from the database.</label>
    <select name="<?= $interval_setting ?>" style="min-width: 100%; margin-top: 13px;">
        <option value="hourly" <?= $current_interval === 'hourly' ? 'selected' : '' ?>>Hourly</option>
        <option value="twicedaily" <?= $current_interval === 'twicedaily' ? 'selected' : '' ?>>Twice Daily</option>
        <option value="daily" <?= $current_interval === 'daily' ? 'selected' : '' ?>>Daily</option>
    </select>
</p>