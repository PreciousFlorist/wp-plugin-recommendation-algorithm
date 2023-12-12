<?php

require_once plugin_dir_path(__FILE__) . 'updates/update-rows.php';


/**
 * Initializes the Elo rating table in the database.
 * This function creates a new table to store Elo ratings for posts within a specific context.
 * It checks if the table creation is successful and then attempts to populate it with default values.
 *
 * @return bool Returns true if the table is successfully created and populated, false otherwise.
 */
function initialize_elo_rating_table()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'elo_rating';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        -- 'id' is a unique identifier for each record in this table
        id mediumint(9) NOT NULL AUTO_INCREMENT,  
         -- 'context_post_id' refers to the ID of the post where recommendations are shown
         context_post_id bigint(20) UNSIGNED NOT NULL,
        -- 'recommended_post_id' refers to the ID of the recommended post
        recommended_post_id bigint(20) UNSIGNED NOT NULL, 
        -- 'recommended_post_elo' is the ELO rating of the recommended post in the context of the context post
        recommended_post_elo mediumint(9) DEFAULT 1200 NOT NULL,
        
        -- 'id' is the primary key ensuring each record is unique
        PRIMARY KEY  (id),
        -- Ensures a unique combination of context and recommended posts
        UNIQUE KEY context_recommended (context_post_id, recommended_post_id), 
        -- Ensures 'context_post_id' exists in the 'wp_posts' table
        FOREIGN KEY (context_post_id) REFERENCES wp_posts(ID), 
        -- Ensures 'recommended_post_id' exists in the 'wp_posts' table
        FOREIGN KEY (recommended_post_id) REFERENCES wp_posts(ID)
    ) $charset_collate;";


    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    if (!dbDelta($sql)) {
        // If table cant be generated
        error_log('Error creating Elo rating table.');
        return false;
    }

    if (!populate_elo_rating_table_with_default_values()) {
        // If rows cant be populated
        error_log('Error populating Elo rating table with default values.');
        return false;
    }

    return true;
}

/**
 * Populates the Elo rating table with default values.
 * This function calculates the initial Elo rating for each post based on shared categories and tags
 * with other posts and inserts these values into the database.
 *
 * @return bool Returns true if the table is successfully populated, false otherwise.
 */
function populate_elo_rating_table_with_default_values()
{
    global $wpdb;

    // Default Elo rating
    $default_elo_value = 1200;
    $category_bonus = 100;
    $tag_bonus = 50;
    // Query pagination
    $per_page = 15;
    $page = 1;



    do {

        $context_query = new WP_Query(array(
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
        ));

        wp_reset_postdata();
        if ($context_query->have_posts()) {
            $batch_update_data = [];
            while ($context_query->have_posts()) {
                $context_query->the_post();
                $context_post_id = get_the_ID();

                // Get all other posts as recommended candidates
                $recommended_query = new WP_Query(array(
                    'post_status' => 'publish',
                    'posts_per_page' => -1, // Fetch all posts
                    'post__not_in' => array($context_post_id), // Exclude the context post
                ));
                if ($recommended_query->have_posts()) {
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

                        // Store recommendation details
                        $batch_update_data[] = [
                            'context_post_id' => $context_post_id,
                            'recommended_post_id' => $recommended_post_id,
                            'elo_value' => $elo_value
                        ];
                    }
                }
                wp_reset_postdata(); // Reset post data for the recommended query
            }
        }


        // Batch update logic
        if (!empty($batch_update_data)) {
            if (!add_post_elo_rating($batch_update_data)) {
                error_log("Failed to batch update Elo ratings on page: $page");
                error_log("Data: " . print_r($batch_update_data, true)); // Log the data that failed to update
                return false; // Return false to indicate failure
            }
        } else {
            error_log("No data to update for page: $page");
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
    $query = $wpdb->prepare("SELECT object_id, term_taxonomy_id FROM {$wpdb->term_relationships} WHERE object_id IN ($post_ids_format)", $post_ids);
    $results = $wpdb->get_results($query, OBJECT_K);


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
    $query = $wpdb->prepare("SELECT tr.object_id, tt.term_taxonomy_id FROM {$wpdb->term_relationships} as tr INNER JOIN {$wpdb->term_taxonomy} as tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy = 'post_tag' AND tr.object_id IN ($post_ids_format)", $post_ids);
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
