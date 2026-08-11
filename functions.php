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

    register_taxonomy('actor', array('movies', 'tvshows'), array(
        'hierarchical'      => false,
        'labels'            => array('name' => 'Actors & Cast', 'singular_name' => 'Actor / Cast'),
        'show_ui'           => true,
        'show_admin_column' => true,
        'rewrite'           => array('slug' => 'actor'),
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
 * Dynamic Helpers for Release Years, Genres, Countries & Qualities
 */
function movie_elite_get_all_release_years() {
    global $wpdb;
    $years = $wpdb->get_col("
        SELECT DISTINCT meta_value 
        FROM {$wpdb->postmeta} 
        WHERE meta_key = 'release_year' 
          AND meta_value IS NOT NULL 
          AND meta_value != '' 
          AND meta_value REGEXP '^[0-9]{4}$'
        ORDER BY CAST(meta_value AS UNSIGNED) DESC
    ");
    if (empty($years)) {
        $current_yr = (int)date('Y');
        for ($y = $current_yr; $y >= 2000; $y--) {
            $years[] = (string)$y;
        }
    }
    return array_values(array_unique($years));
}

function movie_elite_get_all_genres() {
    $terms = get_terms(array(
        'taxonomy'   => 'genre',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC'
    ));
    $res = array();
    if (!is_wp_error($terms) && !empty($terms)) {
        foreach ($terms as $t) {
            $res[] = array('slug' => $t->slug, 'name' => $t->name);
        }
    }
    return $res;
}

function movie_elite_get_all_countries() {
    $terms = get_terms(array(
        'taxonomy'   => 'country',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC'
    ));
    $res = array();
    if (!is_wp_error($terms) && !empty($terms)) {
        foreach ($terms as $t) {
            $res[] = array('slug' => $t->slug, 'name' => $t->name);
        }
    }
    return $res;
}

function movie_elite_get_all_qualities() {
    global $wpdb;
    $qualities = $wpdb->get_col("
        SELECT DISTINCT meta_value 
        FROM {$wpdb->postmeta} 
        WHERE meta_key = 'movie_quality' 
          AND meta_value IS NOT NULL 
          AND meta_value != ''
        ORDER BY meta_value ASC
    ");
    if (empty($qualities)) {
        $qualities = array('4K', '1080p', '720p', 'HDRip', 'WEBRip', 'CAM');
    }
    return array_values(array_unique($qualities));
}

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
    wp_localize_script('movie-elite-features', 'meFeatures', array(
        'ajaxurl'   => admin_url('admin-ajax.php'),
        'nonce'     => wp_create_nonce('movie_elite_nonce'),
        'genres'    => movie_elite_get_all_genres(),
        'countries' => movie_elite_get_all_countries(),
        'years'     => movie_elite_get_all_release_years(),
        'qualities' => movie_elite_get_all_qualities(),
    ));
}
add_action('wp_enqueue_scripts', 'movie_elite_enqueue_scripts');

/**
 * AJAX Handler: Live Instant Search
 */
function movie_elite_ajax_live_search() {
    $keyword = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    if (strlen($keyword) < 2) {
        wp_send_json_success(array('results' => array()));
    }

    $args = array(
        'post_type'      => array('movies', 'tvshows'),
        'post_status'    => 'publish',
        'posts_per_page' => 8,
        's'              => $keyword,
    );

    $query = new WP_Query($args);
    $results = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $id = get_the_ID();
            $poster = get_post_meta($id, 'poster_url', true);
            if (empty($poster) && has_post_thumbnail()) {
                $poster = get_the_post_thumbnail_url($id, 'thumbnail');
            }
            if (empty($poster)) {
                $poster = 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=150';
            }

            $type_label = (get_post_type() === 'tvshows') ? 'TV Show' : 'Movie';
            $rating     = get_post_meta($id, 'imdb_rating', true) ?: '8.5';
            $year       = get_post_meta($id, 'release_year', true) ?: '2026';

            $results[] = array(
                'id'        => $id,
                'title'     => get_the_title(),
                'permalink' => get_permalink(),
                'poster'    => esc_url($poster),
                'type'      => $type_label,
                'rating'    => $rating,
                'year'      => $year,
            );
        }
        wp_reset_postdata();
    }

    wp_send_json_success(array('results' => $results));
}
add_action('wp_ajax_movie_elite_live_search', 'movie_elite_ajax_live_search');
add_action('wp_ajax_nopriv_movie_elite_live_search', 'movie_elite_ajax_live_search');

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
    $drama_status   = get_post_meta($post_id, '_drama_status', true);
    $drama_subtitle = get_post_meta($post_id, '_drama_subtitle', true);
    ?>
    <div class="movie-card" data-title="<?php echo esc_attr($title); ?>">
        <div class="card-poster">
            <a href="<?php echo esc_url($permalink); ?>">
                <img src="<?php echo esc_url($poster); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy" onerror="if(this.src.indexOf('wsrv.nl')===-1 &amp;&amp; this.src.indexOf('image.tmdb.org')!==-1){ this.src='https://wsrv.nl/?url='+encodeURIComponent(this.src); } else { this.onerror=null; this.src='https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=500&amp;auto=format&amp;fit=crop&amp;q=80'; }" />
                <span class="card-imdb-score"><i class="fa-solid fa-star"></i> <?php echo esc_html($rating); ?></span>
                <span class="card-quality-badge"><?php echo esc_html($quality); ?></span>
                <?php if (!empty($drama_status)) : ?>
                    <?php if (strcasecmp($drama_status, 'ongoing') === 0) : ?>
                        <span class="card-status-badge ongoing"><i class="fa-solid fa-spinner fa-spin-pulse"></i> ONGOING</span>
                    <?php else : ?>
                        <span class="card-status-badge completed"><i class="fa-solid fa-circle-check"></i> COMPLETED</span>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if (!empty($drama_subtitle) || get_post_type($post_id) === 'tvshows') : ?>
                    <span class="card-sub-badge"><i class="fa-solid fa-closed-captioning"></i> <?php echo esc_html($drama_subtitle ?: 'SUB'); ?></span>
                <?php endif; ?>
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

/**
 * Render Reusable Advanced Filter Bar Component
 */
function movie_elite_render_filter_bar($preselect_ptype = '') {
    $genres    = movie_elite_get_all_genres();
    $countries = movie_elite_get_all_countries();
    $years     = movie_elite_get_all_release_years();
    $qualities = movie_elite_get_all_qualities();
    ?>
    <section class="alphabet-filter-section" style="margin-bottom: 25px;">
        <div class="container">
            <div class="alphabet-bar" style="margin-bottom:14px;">
                <span class="alphabet-label"><i class="fa-solid fa-arrow-down-a-z"></i> BROWSE BY A-Z:</span>
                <div class="alphabet-links">
                    <button type="button" class="alphabet-btn active" data-letter="ALL">ALL</button>
                    <button type="button" class="alphabet-btn" data-letter="#">#</button>
                    <?php foreach (range('A', 'Z') as $char) : ?>
                        <button type="button" class="alphabet-btn" data-letter="<?php echo $char; ?>"><?php echo $char; ?></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Advanced Filter Accordion / Bar -->
            <div id="me-advanced-filter-bar" style="background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:14px 18px; display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
                <span style="font-size:0.85rem; font-weight:800; color:var(--accent-cyan); display:flex; align-items:center; gap:6px;">
                    <i class="fa-solid fa-filter"></i> FILTER:
                </span>
                <select id="me-filter-ptype" class="alphabet-btn" style="padding:6px 12px; background:var(--bg-card); color:#fff; border:1px solid var(--border-color); font-size:0.85rem;">
                    <option value="">All Types (Movies & Shows)</option>
                    <option value="movies" <?php selected($preselect_ptype, 'movies'); ?>>Movies Only</option>
                    <option value="tvshows" <?php selected($preselect_ptype, 'tvshows'); ?>>TV Shows / Dramas</option>
                </select>
                <select id="me-filter-genre" class="alphabet-btn" style="padding:6px 12px; background:var(--bg-card); color:#fff; border:1px solid var(--border-color); font-size:0.85rem;">
                    <option value="">All Genres</option>
                    <?php foreach ($genres as $g) : ?>
                        <option value="<?php echo esc_attr($g['slug']); ?>"><?php echo esc_html($g['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="me-filter-country" class="alphabet-btn" style="padding:6px 12px; background:var(--bg-card); color:#fff; border:1px solid var(--border-color); font-size:0.85rem;">
                    <option value="">All Countries</option>
                    <?php foreach ($countries as $c) : ?>
                        <option value="<?php echo esc_attr($c['slug']); ?>"><?php echo esc_html($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="me-filter-year" class="alphabet-btn" style="padding:6px 12px; background:var(--bg-card); color:#fff; border:1px solid var(--border-color); font-size:0.85rem;">
                    <option value="">All Years</option>
                    <?php foreach ($years as $y) : ?>
                        <option value="<?php echo esc_attr($y); ?>"><?php echo esc_html($y); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="me-filter-quality" class="alphabet-btn" style="padding:6px 12px; background:var(--bg-card); color:#fff; border:1px solid var(--border-color); font-size:0.85rem;">
                    <option value="">All Qualities</option>
                    <?php foreach ($qualities as $q) : ?>
                        <option value="<?php echo esc_attr($q); ?>"><?php echo esc_html($q); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="me-filter-reset" class="alphabet-btn" style="background:rgba(255,255,255,0.08); color:var(--text-muted);">
                    <i class="fa-solid fa-rotate-left"></i> Reset
                </button>
            </div>

            <!-- Filter Results Container -->
            <div id="me-filter-results-container" style="display:none; margin-top:24px; background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:20px;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:18px; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:12px;">
                    <h3 style="font-size:1.1rem; font-weight:800; color:var(--accent-cyan); display:flex; align-items:center; gap:8px; margin:0;">
                        <i class="fa-solid fa-sliders"></i> Filtered Results (<span id="me-filter-count">0</span>)
                    </h3>
                    <button type="button" id="me-filter-close" class="alphabet-btn" style="background:rgba(239,68,68,0.15); color:#ef4444; border:1px solid #ef4444; padding:6px 12px; font-size:0.82rem;">
                        <i class="fa-solid fa-xmark"></i> Clear Filter Results
                    </button>
                </div>
                <div id="me-filter-loading" style="display:none; justify-content:center; align-items:center; padding:40px; color:var(--accent-cyan); gap:10px; font-weight:700;">
                    <i class="fa-solid fa-spinner fa-spin fa-2x"></i> Searching catalog...
                </div>
                <div id="me-filter-results" class="movies-grid"></div>
            </div>
        </div>
    </section>
    <?php
}

/**
 * Filter frontend post titles to clean out any legacy 'Dramacool', 'Episode XX', or 'English SUB' text
 */
function movie_elite_clean_display_title($title, $id = 0) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return $title;
    }
    if (preg_match('/dramacool/i', $title) || preg_match('/english sub/i', $title) || preg_match('/episode\s*\d+/i', $title)) {
        if (function_exists('movie_elite_clean_dramacool_title')) {
            return movie_elite_clean_dramacool_title($title);
        } else {
            $title = preg_replace('/[\|–\-]?\s*dramacool(?:\.com|\.ch|\.ro|\.sr|\.ru|\.com\.ro)?/i', '', $title);
            $title = preg_replace('/\s*episode\s*\d+.*$/i', '', $title);
            $title = preg_replace('/\s*(?:English SUB|EngSub|SUB|RAW)\s*$/i', '', $title);
            return trim($title, " \t\n\r\0\x0B|-–");
        }
    }
    return $title;
}
add_filter('the_title', 'movie_elite_clean_display_title', 10, 2);

/**
 * Smart AI Recommendation Engine: Query related titles by shared Taxonomies (Genre, Country, Actor)
 */
function movie_elite_get_related_titles($post_id, $limit = 6) {
    $genres    = wp_get_post_terms($post_id, 'genre', array('fields' => 'ids'));
    $countries = wp_get_post_terms($post_id, 'country', array('fields' => 'ids'));
    $actors    = wp_get_post_terms($post_id, 'actor', array('fields' => 'ids'));

    $tax_query = array('relation' => 'OR');
    if (!empty($genres) && !is_wp_error($genres)) {
        $tax_query[] = array('taxonomy' => 'genre', 'field' => 'term_id', 'terms' => $genres);
    }
    if (!empty($countries) && !is_wp_error($countries)) {
        $tax_query[] = array('taxonomy' => 'country', 'field' => 'term_id', 'terms' => $countries);
    }
    if (!empty($actors) && !is_wp_error($actors)) {
        $tax_query[] = array('taxonomy' => 'actor', 'field' => 'term_id', 'terms' => $actors);
    }

    $args = array(
        'post_type'      => array('movies', 'tvshows'),
        'post_status'    => 'publish',
        'post__not_in'   => array($post_id),
        'posts_per_page' => $limit,
        'orderby'        => 'rand',
    );

    if (count($tax_query) > 1) {
        $args['tax_query'] = $tax_query;
    }

    return new WP_Query($args);
}

/**
 * Get clickable Actor Links for single movie & drama pages
 */
function movie_elite_get_actor_links($post_id) {
    $actors = get_the_terms($post_id, 'actor');
    if (empty($actors) || is_wp_error($actors)) {
        $raw_cast = get_post_meta($post_id, 'movie_cast', true);
        if (empty($raw_cast)) return 'N/A';
        $cast_array = explode(',', $raw_cast);
        $links = array();
        foreach ($cast_array as $c) {
            $c_name = trim($c);
            if (!empty($c_name)) {
                $term = get_term_by('name', $c_name, 'actor');
                if ($term) {
                    $links[] = '<a href="' . esc_url(get_term_link($term)) . '" style="color:var(--accent-cyan); text-decoration:none; font-weight:700;">' . esc_html($c_name) . '</a>';
                } else {
                    $links[] = esc_html($c_name);
                }
            }
        }
        return implode(', ', $links);
    }
    $links = array();
    foreach ($actors as $act) {
        $links[] = '<a href="' . esc_url(get_term_link($act)) . '" style="color:var(--accent-cyan); text-decoration:none; font-weight:700;">' . esc_html($act->name) . '</a>';
    }
    return implode(', ', $links);
}

/**
 * Render Mobile App-like Bottom Navigation Bar
 */
function movie_elite_render_mobile_bottom_nav() {
    $home_url    = esc_url(home_url('/'));
    $movies_url  = esc_url(get_post_type_archive_link('movies') ?: home_url('/?post_type=movies'));
    $tvshows_url = esc_url(get_post_type_archive_link('tvshows') ?: home_url('/?post_type=tvshows'));
    ?>
    <nav class="me-mobile-bottom-nav">
        <a href="<?php echo $home_url; ?>" class="me-mb-nav-item <?php echo is_front_page() ? 'active' : ''; ?>">
            <i class="fa-solid fa-house"></i>
            <span>Home</span>
        </a>
        <a href="<?php echo $movies_url; ?>" class="me-mb-nav-item <?php echo (is_post_type_archive('movies') || is_singular('movies')) ? 'active' : ''; ?>">
            <i class="fa-solid fa-film"></i>
            <span>Movies</span>
        </a>
        <a href="<?php echo $tvshows_url; ?>" class="me-mb-nav-item <?php echo (is_post_type_archive('tvshows') || is_singular('tvshows')) ? 'active' : ''; ?>">
            <i class="fa-solid fa-tv"></i>
            <span>Dramas</span>
        </a>
        <a href="<?php echo $home_url; ?>#me-advanced-filter-bar" class="me-mb-nav-item me-btn-mb-filter">
            <i class="fa-solid fa-filter"></i>
            <span>Filter</span>
        </a>
        <a href="<?php echo $home_url; ?>#me-watchlist-sec" class="me-mb-nav-item">
            <i class="fa-solid fa-heart"></i>
            <span>Saved</span>
        </a>
    </nav>
    <?php
}

/**
 * AJAX Handler: Ongoing Drama Episode Release Subscription ("Notify Me")
 */
function movie_elite_ajax_subscribe_episode_notify() {
    $email   = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    if (empty($email) || !is_email($email) || !$post_id) {
        wp_send_json_error(array('message' => 'Tafadhali ingiza barua pepe (email) sahihi.'));
    }

    $subs = get_post_meta($post_id, '_drama_subscribers', true) ?: array();
    if (!in_array($email, $subs)) {
        $subs[] = $email;
        update_post_meta($post_id, '_drama_subscribers', array_unique($subs));
    }

    wp_send_json_success(array('message' => '🎉 Umesajiliwa kikamilifu! Utapokea barua pepe pindi episode mpya inapotoka.'));
}
add_action('wp_ajax_movie_elite_subscribe_episode_notify', 'movie_elite_ajax_subscribe_episode_notify');
add_action('wp_ajax_nopriv_movie_elite_subscribe_episode_notify', 'movie_elite_ajax_subscribe_episode_notify');
