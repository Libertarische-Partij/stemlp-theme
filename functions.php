<?php
add_action( 'init', function() {
    // Get all registered patterns
    $patterns = WP_Block_Patterns_Registry::get_instance()->get_all_registered();
    unregister_block_pattern( 'twentytwentyfive/footer-centered' );
    unregister_block_pattern( 'twentytwentyfive/footer-columns' );
    unregister_block_pattern( 'twentytwentyfive/footer-newsletter' );
    unregister_block_pattern( 'twentytwentyfive/footer-social' );
    unregister_block_pattern( 'twentytwentyfive/header-large-title' );
    unregister_block_pattern( 'twentytwentyfive/vertical-header' );
}, 15 );

function twentytwentyfive_child_enqueue_styles() {
    // Load parent theme CSS
    wp_enqueue_style(
        'twentytwentyfive-style',
        get_template_directory_uri() . '/style.css'
    );

    // Load child theme CSS
    wp_enqueue_style(
        'stemlp-style',
        get_stylesheet_directory_uri() . '/style.css',
        array('twentytwentyfive-style')
    );
}

add_action('wp_enqueue_scripts', 'twentytwentyfive_child_enqueue_styles');
