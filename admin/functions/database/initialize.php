<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once plugin_dir_path(__FILE__) . 'updates/insert-rows.php';
require_once plugin_dir_path(__FILE__) . 'updates/schedule/cron-management.php';

/**
 * Initializes the Elo rating table in the database.
 * This function creates a new table to store Elo ratings for posts within a specific context.
 * It checks if the table creation is successful and then attempts to populate it with default values.
 * The Elo rating system is a method for calculating the relative skill levels of players in zero-sum games such as chess.
 *
 * @return bool Returns true if the table is successfully created and populated, false otherwise.
 */
function elo_init()
{
    // Get enabled post types from the option
    $enabled_post_types = get_option('post_elo_enabled_post_types', []);
    $initialized_post_types = get_option('post_elo_initialized_post_types', []);

    // Create the local-storage directory if it doesn't exist
    $storage_directory = plugin_dir_path(dirname(__FILE__, 3)) . 'local-storage';
    if (!file_exists($storage_directory)) {
        mkdir(
            $storage_directory,
            0755, // Read, write and execute permissions
            true // Allow nested directories
        );
    }

    // Initialize the ELO rating table for each enabled post type
    foreach ($enabled_post_types as $post_type) {
        // Check if the post type has already been initialized
        if (in_array($post_type, $initialized_post_types)) {
            error_log("Post type '$post_type' is already initialized.");
            continue;
        }

        $count = wp_count_posts($post_type);
        if ($count->publish > 0) {
            initialize_elo_rating_table($post_type);
            schedule_database_sync_job($post_type);
            $initialized_post_types[] = $post_type;
        } else {
            error_log("No posts found for post type: $post_type");
        }
    }

    // Update the list of initialized post types
    update_option('post_elo_initialized_post_types', $initialized_post_types);
}


/**
 * Initializes the Elo rating table in the database.
 * This function creates a new table to store Elo ratings for posts within a specific context.
 * It checks if the table creation is successful and then attempts to populate it with default values.
 *
 * @return bool Returns true if the table is successfully created and populated, false otherwise.
 */
function initialize_elo_rating_table($post_type)
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'elo_rating_' . $post_type;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,  
        context_post_id bigint(20) UNSIGNED NOT NULL,
        recommended_post_id bigint(20) UNSIGNED NOT NULL, 
        recommended_post_elo mediumint(9) DEFAULT 1200 NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY context_recommended (context_post_id, recommended_post_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    if (!dbDelta($sql)) {
        // If table cant be generated
        error_log('Error creating Elo rating table.');
        return false;
    }

    if (!populate_elo_rating_table_with_default_values($post_type)) {
        // If rows cant be populated
        error_log('Error populating Elo rating table with default values.');
        return false;
    }

    return true;
}

/**
 * Populates the Elo rating table with default values.
 * This function calculates the initial Elo rating for each post based on shared categories and tags
 * with other posts. It inserts these values into the database and prepares a JSON file for each context.
 *
 * @return bool Returns true if the table is successfully populated and JSON files are created, false otherwise.
 */
function populate_elo_rating_table_with_default_values($post_type)
{
    global $wpdb;

    // Store first post type ID for default values in admin settings
    $first_context_post_id = null;

    // Initialize local storage limit for total number of JSON values
    $local_storage_limit = get_option('local_storage_limit_' . $post_type);

    // Local storage directory
    $json_storage_path = plugin_dir_path(dirname(__FILE__, 3)) . 'local-storage/' . $post_type . '/';
    // Create the directory for the post type if it doesn't exist
    if (!file_exists($json_storage_path)) {
        mkdir($json_storage_path, 0755, true);
    }

    // Default Elo rating
    $default_elo_value = 1200;
    $category_bonus = 100;
    $tag_bonus = 50;
    // Query pagination
    $per_page = 15;
    $page = 1;

    do {

        $context_query = new WP_Query(array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
        ));

        if ($context_query->have_posts()) {
            $batch_update_sql = []; // SQL batch initalization

            while ($context_query->have_posts()) {
                $context_query->the_post();
                $context_post_id = get_the_ID();

                // Save the first context post ID for this post type
                if ($first_context_post_id === null) {
                    $first_context_post_id = $context_post_id;
                    error_log($first_context_post_id);
                    update_option('post_elo_default_context_id_' . $post_type, $first_context_post_id);
                }

                // Get all other posts as recommended candidates
                $recommended_query = new WP_Query(array(
                    'post_status' => 'publish',
                    'posts_per_page' => -1, // Fetch all posts
                    'post__not_in' => array($context_post_id), // Exclude the context post
                ));

                if ($recommended_query->have_posts()) {
                    $batch_update_json = []; // JSON batch initialization

                    while ($recommended_query->have_posts()) {
                        $recommended_query->the_post();
                        $recommended_post_id = get_the_ID();

                        // Calculate Elo value
                        $elo_value = $default_elo_value;

                        // Retrieve categories and tags for the current context and recommended posts
                        $context_categories = get_all_post_categories(array($context_post_id))[$context_post_id] ?? [];
                        $context_tags = get_all_post_tags(array($context_post_id))[$context_post_id] ?? [];

                        $recommended_categories = get_all_post_categories(array($recommended_post_id))[$recommended_post_id] ?? [];
                        $recommended_tags = get_all_post_tags(array($recommended_post_id))[$recommended_post_id] ?? [];

                        // Adjust Elo value based on shared categories and tags
                        $shared_categories = count(array_intersect_key($context_categories, $recommended_categories));
                        $shared_tags = count(array_intersect_key($context_tags, $recommended_tags));

                        $elo_value += $shared_categories * $category_bonus;
                        $elo_value += $shared_tags * $tag_bonus;

                        // Store recommendation details for batch update
                        $batch_update_sql[] = [
                            'context_post_id' => $context_post_id,
                            'recommended_post_id' => $recommended_post_id,
                            'elo_value' => $elo_value
                        ];

                        // Store recommendation details for JSON with recommended_post_id as key
                        $batch_update_json[$recommended_post_id] = ['elo_value' => $elo_value];
                    }

                    // Sort the recommendations by elo_value from highest to lowest
                    uasort($batch_update_json, function ($a, $b) {
                        return $b['elo_value'] <=> $a['elo_value'];
                    });

                    // Limit the number of entries to local_storage_limit
                    $batch_update_json = array_slice($batch_update_json, 0, $local_storage_limit, true);

                    // Write data to JSON file
                    if (!empty($batch_update_json)) {
                        $json_file_name = $json_storage_path . 'context-' . $context_post_id . '.json';
                        file_put_contents($json_file_name, json_encode($batch_update_json));
                    } else {
                        error_log("No data to update for page: $page");
                    }
                }
                wp_reset_postdata(); // Reset post data for the recommended query
            }
        }

        // Batch update SQL
        if (!empty($batch_update_sql)) {
            if (!insert_elo_ratings($batch_update_sql, $post_type)) {
                error_log("Failed to batch update Elo ratings on page: $page for post type: $post_type");
                error_log("Data: " . print_r($batch_update_sql, true));
                return false;
            }
        } else {
            error_log("No data to update for post type: $post_type on page: $page");
        }

        wp_reset_postdata(); // Reset post data for the context query

        $page++; // Move to the next page
    } while ($page <= $context_query->max_num_pages);

    return true;
}


/*------------------------------
# Helper functions
------------------------------*/

/**
 * Fetches all categories associated with a given set of post IDs.
 * This function retrieves the categories for each post ID in the provided array
 * and returns them in an associative array format.
 *
 * @param array $post_ids An array of post IDs.
 * @return array An associative array of categories keyed by post IDs.
 */
function get_all_post_categories($post_ids)
{
    global $wpdb;

    // Fetching category relationships (object_id is the post ID, term_taxonomy_id is the category ID)
    $post_ids_format = implode(',', array_fill(0, count($post_ids), '%d'));

    $query = $wpdb->prepare(
        "SELECT object_id, term_taxonomy_id
        FROM {$wpdb->term_relationships} 
        WHERE object_id 
        IN ($post_ids_format)",
        $post_ids
    );

    $results = $wpdb->get_results(
        $query,
        OBJECT_K // Output an associative array
    );


    if ($results === null) {
        error_log("Error fetching categories for posts: " . implode(", ", $post_ids));
        return [];
    } else {
        $categories = [];
        foreach ($results as $result) {
            // Associate each post ID with its tags
            // Set object id as the key for faster lookup
            $categories[$result->object_id][$result->term_taxonomy_id] = true;
        }
        return $categories;
    }
}

/**
 * Fetches all tags associated with a given set of post IDs.
 * This function retrieves the tags for each post ID in the provided array
 * and returns them in an associative array format.
 *
 * @param array $post_ids An array of post IDs.
 * @return array An associative array of tags keyed by post IDs.
 */
function get_all_post_tags($post_ids)
{
    global $wpdb;

    // Fetching tag relationships (object_id is the post ID, term_taxonomy_id is the tag ID)
    $post_ids_format = implode(',', array_fill(0, count($post_ids), '%d'));
    $query = $wpdb->prepare(
        "SELECT tr.object_id, tt.term_taxonomy_id
        FROM {$wpdb->term_relationships}
        as tr INNER JOIN {$wpdb->term_taxonomy}
        as tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        WHERE tt.taxonomy = 'post_tag'
        AND tr.object_id
        IN ($post_ids_format)",
        $post_ids
    );
    $results = $wpdb->get_results($query);

    if ($results === null) {
        error_log("Error fetching tags for posts: " . implode(", ", $post_ids));
        return [];
    } else {
        $tags = [];
        foreach ($results as $result) {
            // Associate each post ID with its tags
            // Set object id as the key for faster lookup
            $tags[$result->object_id][$result->term_taxonomy_id] = true;
        }
    }

    return $tags;
}
