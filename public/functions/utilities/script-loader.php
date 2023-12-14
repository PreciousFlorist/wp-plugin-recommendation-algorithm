<?php

function enqueue_elo_script()
{
    wp_enqueue_script('elo-interaction', plugins_url('../../js/elo-interactions.js', __FILE__), array(), null, true);

    wp_localize_script('elo-interaction', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_elo_script');
