<?php
// Check if the file is being accessed directly
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function form_fetch_recommendations($stored_context_post_id, $stored_num_recommendations, $current_limit, $post_type)
{
?>
    <form method="post" action="">
        <?php wp_nonce_field('post_elo_get_recommendations_action', 'post_elo_get_recommendations_nonce_field'); ?>

        <div style="display: flex; gap: 10px; align-items: end;">

            <div style="display: flex; flex-direction: column;">
                <label for="context_post_id" style="display: flex; flex-direction: column;  margin-bottom: 13px; width: 286px;">Context ID:</label>
                <input type="number" id="context_post_id" name="context_post_id" placeholder="Context Post ID" required value="<?= esc_attr($stored_context_post_id); ?>" />
            </div>

            <div style="display: flex; flex-direction: column;">
                <label for="num_recommendations" style="display: flex; flex-direction: column; margin-bottom: 13px; width: 286px;">Number of Recommendations:</label>
                <input type="number" id="num_recommendations" name="num_recommendations" placeholder="Number of Recommendations" min="2" max="<?= $current_limit ?>" required value="<?= esc_attr($stored_num_recommendations); ?>" />
            </div>

            <input type="hidden" name="post_type" value="<?= esc_attr($post_type); ?>" />

            <input type="submit" name="get_top_recommendations" value="Get Recommendations" class="button-secondary" />
        </div>

    </form>

<?php
}
