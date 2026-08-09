<?php
/**
 * MovieElite Pro - Theme Core Bootstrap & Functions
 */

if (!defined('ABSPATH')) {
    exit;
}

define('MOVIE_ELITE_VERSION', '1.0.0');
define('MOVIE_ELITE_DIR', get_template_directory());
define('MOVIE_ELITE_URI', get_template_directory_uri());

// Include Engine Modules
require_once MOVIE_ELITE_DIR . '/inc/meta-box.php';
require_once MOVIE_ELITE_DIR . '/inc/vidvault-downloader.php';
require_once MOVIE_ELITE_DIR . '/inc/embed-manager.php';
require_once MOVIE_ELITE_DIR . '/inc/embed-scraper.php';
require_once MOVIE_ELITE_DIR . '/inc/block-manager.php';
require_once MOVIE_ELITE_DIR . '/inc/importer.php';
require_once MOVIE_ELITE_DIR . '/inc/demo-data.php';
require_once MOVIE_ELITE_DIR . '/inc/dramacool-importer.php';
require_once MOVIE_ELITE_DIR . '/inc/features-engine.php';

/**
 * Theme Setup
 */
function movie_elite_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));

    register_nav_menus(array(
        'primary-menu' => __('Primary Navigation', 'movie-elite'),
        'footer-menu'  => __('Footer Navigation', 'movie-elite'),
    ));
}
add_action('after_setup_theme', 'movie_elite_setup');

/**
 * Register Separate Custom Post Types: `movies` & `tvshows`
 */
function movie_elite_register_cpts() {
    // 1. CPT: Movies (`movies`)
    register_post_type('movies', array(
        'labels'              => array(
            'name'               => 'Movies',
            'singular_name'      => 'Movie',
            'menu_name'          => '🍿 Movies',
            'add_new'            => 'Add New Movie',
            'add_new_item'       => 'Add New Movie',
            'edit_item'          => 'Edit Movie',
            'search_items'       => 'Search Movies',
        ),
        'public'              => true,
        'has_archive'         => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'query_var'           => true,
        'rewrite'             => array('slug' => 'movies'),
        'capability_type'     => 'post',
        'hierarchical'        => false,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-format-video',
        'supports'            => array('title', 'editor', 'thumbnail', 'excerpt'),
    ));

    // 2. CPT: TV Shows & Asian Dramas (`tvshows`)
    register_post_type('tvshows', array(
        'labels'              => array(
            'name'               => 'TV Shows & Dramas',
            'singular_name'      => 'TV Show / Drama',
            'menu_name'          => '📺 TV Shows & Dramas',
            'add_new'            => 'Add New TV Show / Drama',
            'add_new_item'       => 'Add New TV Show / Drama',
            'edit_item'          => 'Edit TV Show',
            'search_items'       => 'Search TV Shows',
        ),
        'public'              => true,
        'has_archive'         => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'query_var'           => true,
        'rewrite'             => array('slug' => 'tvshows'),
        'capability_type'     => 'post',
        'hierarchical'        => false,
        'menu_position'       => 6,
        'menu_icon'           => 'dashicons-video-alt2',
        'supports'            => array('title', 'editor', 'thumbnail', 'excerpt'),
    ));

    // Register Shared Taxonomies across both Movies and TV Shows
    register_taxonomy('movie_category', array('movies', 'tvshows'), array(
        'hierarchical'      => true,
        'labels'            => array('name' => 'Categories', 'singular_name' => 'Category'),
        'show_ui'           => true,
        'show_admin_column' => true,
        'rewrite'           => array('slug' => 'category'),
    ));

    register_taxonomy('genre', array('movies', 'tvshows'), array(
        'hierarchical'      => true,
        'labels'            => array('name' => 'Genres', 'singular_name' => 'Genre'),
        'show_ui'           => true,
        'show_admin_column' => true,
        'rewrite'           => array('slug' => 'genre'),
    ));

    register_taxonomy('country', array('movies', 'tvshows'), array(
        'hierarchical'      => true,
        'labels'            => array('name' => 'Countries', 'singular_name' => 'Country'),
        'show_ui'           => true,
        'show_admin_column' => true,
        'rewrite'           => array('slug' => 'country'),
    ));

    // Ensure core Categories exist
    $default_cats = array(
        'Recommended'   => 'recommended',
        'TV Shows'      => 'tv-shows',
        'Asian Dramas'  => 'asian-drama',
        'Korean Movies' => 'korean',
        'Chinese Movies'=> 'chinese',
        'Action Movies' => 'action',
        'Romance Movies'=> 'romance'
    );

    foreach ($default_cats as $name => $slug) {
        if (!term_exists($slug, 'movie_category')) {
            wp_insert_term($name, 'movie_category', array('slug' => $slug));
        }
    }
}
add_action('init', 'movie_elite_register_cpts');

/**
 * Modify main query for taxonomy and post type archives to include 'movies' & 'tvshows'
 */
function movie_elite_archive_query_modifier($query) {
    if (!is_admin() && $query->is_main_query()) {
        if ($query->is_post_type_archive('tvshows')) {
            $query->set('post_type', array('tvshows'));
            $query->set('posts_per_page', 24);
        } elseif ($query->is_post_type_archive('movies')) {
            $query->set('post_type', array('movies'));
            $query->set('posts_per_page', 24);
        } elseif ($query->is_tax('movie_category') || $query->is_tax('genre') || $query->is_tax('country')) {
            $query->set('post_type', array('movies', 'tvshows'));
            $query->set('posts_per_page', 24);
        }
    }
}
add_action('pre_get_posts', 'movie_elite_archive_query_modifier');

/**
 * Enqueue Scripts & Styles
 */
function movie_elite_enqueue_scripts() {
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap', array(), null);
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0');
    wp_enqueue_style('movie-elite-style', get_stylesheet_uri(), array(), MOVIE_ELITE_VERSION);

    wp_enqueue_script('movie-elite-js', MOVIE_ELITE_URI . '/assets/js/theme.js', array('jquery'), MOVIE_ELITE_VERSION, true);
    wp_localize_script('movie-elite-js', 'movie_elite_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('movie_elite_nonce')
    ));

    wp_enqueue_script('movie-elite-features', MOVIE_ELITE_URI . '/js/features.js', array(), MOVIE_ELITE_VERSION, true);
}
add_action('wp_enqueue_scripts', 'movie_elite_enqueue_scripts');

/**
 * Render Card Component Helper
 */
function movie_elite_render_card_item() {
    $post_id   = get_the_ID();
    $title     = get_the_title();
    $permalink = get_permalink();
    $rating    = get_post_meta($post_id, 'imdb_rating', true) ?: '8.5';
    $year      = get_post_meta($post_id, 'release_year', true) ?: '2026';
    $quality   = get_post_meta($post_id, 'movie_quality', true) ?: 'HD';
    $poster    = get_post_meta($post_id, 'poster_url', true);
    $views     = function_exists('movie_elite_get_views') ? movie_elite_get_views($post_id) : 'New';

    if (empty($poster) && has_post_thumbnail()) {
        $poster = get_the_post_thumbnail_url($post_id, 'medium');
    }
    if (empty($poster)) {
        $poster = 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=500&auto=format&fit=crop&q=80';
    }

    $genres = get_the_terms($post_id, 'genre');
    $genre_text = (!empty($genres) && !is_wp_error($genres)) ? $genres[0]->name : 'Cinema';
    ?>
    <div class="movie-card" data-title="<?php echo esc_attr($title); ?>">
        <div class="card-poster">
            <a href="<?php echo esc_url($permalink); ?>">
                <img src="<?php echo esc_url($poster); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy" />
                <span class="card-imdb-score"><i class="fa-solid fa-star"></i> <?php echo esc_html($rating); ?></span>
                <span class="card-quality-badge"><?php echo esc_html($quality); ?></span>
                <div class="card-play-overlay">
                    <div class="play-circle-btn"><i class="fa-solid fa-play"></i></div>
                </div>
            </a>
            <button type="button" class="me-wl-btn" data-id="<?php echo $post_id; ?>" title="Add to Watchlist">
                <i class="fa-solid fa-heart"></i>
            </button>
        </div>
        <div class="card-details">
            <div class="card-genre"><?php echo esc_html($genre_text); ?></div>
            <h3 class="card-title">
                <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a>
            </h3>
            <div class="card-meta">
                <span><i class="fa-solid fa-calendar-days"></i> <?php echo esc_html($year); ?></span>
                <span><i class="fa-solid fa-eye"></i> <?php echo esc_html($views); ?></span>
            </div>
        </div>
    </div>
    <?php
}

/**
 * AJAX Handler: Alphabetical Filter
 */
function movie_elite_ajax_alphabet_filter_handler() {
    check_ajax_referer('movie_elite_nonce', 'nonce');
    $letter = sanitize_text_field($_POST['letter'] ?? '');

    $args = array(
        'post_type'      => array('movies', 'tvshows'),
        'post_status'    => 'publish',
        'posts_per_page' => 20,
        'orderby'        => 'title',
        'order'          => 'ASC'
    );

    if ($letter !== 'ALL' && !empty($letter)) {
        add_filter('posts_where', function($where, $query) use ($letter) {
            global $wpdb;
            $where .= $wpdb->prepare(" AND {$wpdb->posts}.post_title LIKE %s", $wpdb->esc_like($letter) . '%');
            return $where;
        }, 10, 2);
    }

    $query = new WP_Query($args);
    ob_start();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            movie_elite_render_card_item();
        }
        wp_reset_postdata();
    } else {
        echo '<div style="grid-column:1/-1; padding:30px; text-align:center; color:var(--text-muted);">No items found for letter ' . esc_html($letter) . '</div>';
    }

    wp_send_json_success(ob_get_clean());
}
add_action('wp_ajax_movie_elite_alphabet_filter', 'movie_elite_ajax_alphabet_filter_handler');
add_action('wp_ajax_nopriv_movie_elite_alphabet_filter', 'movie_elite_ajax_alphabet_filter_handler');
