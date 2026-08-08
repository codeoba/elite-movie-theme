<?php
/**
 * MovieElite Pro - Main Homepage Template
 */

get_header();

// Hero Slider Query (Fetch latest 5 Published items)
$hero_query = new WP_Query(array(
    'post_type'      => array('movies', 'tvshows'),
    'post_status'    => 'publish',
    'posts_per_page' => 5,
    'orderby'        => 'date',
    'order'          => 'DESC'
));
?>

<main class="main-content">
    
    <!-- Hero Slider Section -->
    <section class="hero-slider-section">
        <div class="container">
            <div class="slider-container">
                <?php
                if ($hero_query->have_posts()) :
                    $slide_index = 0;
                    while ($hero_query->have_posts()) : $hero_query->the_post();
                        $slide_index++;
                        $post_id  = get_the_ID();
                        $title    = get_the_title();
                        $rating   = get_post_meta($post_id, 'imdb_rating', true) ?: '8.5';
                        $year     = get_post_meta($post_id, 'release_year', true) ?: '2026';
                        $quality  = get_post_meta($post_id, 'movie_quality', true) ?: '4K UHD';
                        $backdrop = get_post_meta($post_id, 'backdrop_url', true) ?: get_post_meta($post_id, 'poster_url', true);
                        
                        if (empty($backdrop)) {
                            $backdrop = 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=1200&auto=format&fit=crop&q=80';
                        }

                        $genres = get_the_terms($post_id, 'genre');
                        $genre_names = (!empty($genres) && !is_wp_error($genres)) ? wp_list_pluck($genres, 'name') : array('Trending');
                ?>
                <div class="slide-item <?php echo ($slide_index === 1) ? 'active' : ''; ?>" style="background-image: url('<?php echo esc_url($backdrop); ?>');">
                    <div class="slide-overlay"></div>
                    <div class="slide-content">
                        <span class="slide-badge"><i class="fa-solid fa-fire"></i> <?php echo esc_html(implode(', ', $genre_names)); ?></span>
                        <h1 class="slide-title"><?php echo esc_html($title); ?></h1>
                        <div class="slide-meta">
                            <span class="imdb-pill"><i class="fa-solid fa-star"></i> IMDb <?php echo esc_html($rating); ?></span>
                            <span><i class="fa-solid fa-calendar-days"></i> <?php echo esc_html($year); ?></span>
                            <span><i class="fa-solid fa-video"></i> <?php echo esc_html($quality); ?></span>
                        </div>
                        <a href="<?php the_permalink(); ?>" class="btn-watch-slide">
                            <i class="fa-solid fa-play"></i> Watch Now
                        </a>
                    </div>
                </div>
                <?php
                    endwhile;
                    wp_reset_postdata();
                endif;
                ?>

                <!-- Slider Controls -->
                <div class="slider-controls">
                    <button type="button" class="btn-slide-prev" id="btn-hero-prev"><i class="fa-solid fa-chevron-left"></i></button>
                    <button type="button" class="btn-slide-next" id="btn-hero-next"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
            </div>
        </div>
    </section>

    <!-- Alphabetical A-Z Filter Bar -->
    <section class="alphabet-filter-section">
        <div class="container">
            <div class="alphabet-bar">
                <span class="alphabet-label"><i class="fa-solid fa-arrow-down-a-z"></i> BROWSE BY A-Z:</span>
                <div class="alphabet-links">
                    <button type="button" class="alphabet-btn active" data-letter="ALL">ALL</button>
                    <button type="button" class="alphabet-btn" data-letter="#">#</button>
                    <?php foreach (range('A', 'Z') as $char) : ?>
                        <button type="button" class="alphabet-btn" data-letter="<?php echo $char; ?>"><?php echo $char; ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Dynamic Section Blocks -->
    <div class="container">
        <?php
        $blocks = movie_elite_get_homepage_blocks();

        foreach ($blocks as $id => $blk) :
            if (($blk['status'] ?? 'active') !== 'active') {
                continue;
            }

            $rule  = $blk['rule'] ?? 'category';
            $val   = $blk['value'] ?? 'recommended';
            $title = $blk['name'] ?? 'Block';
            $icon  = $blk['icon'] ?? 'fa-film';

            // Determine direct View All URL
            $view_all_url = home_url('/movies/');
            if ($rule === 'category' && !empty($val)) {
                $view_all_url = home_url('/movie_category/' . sanitize_title($val) . '/');
            } elseif ($rule === 'genre' && !empty($val)) {
                $view_all_url = home_url('/genre/' . sanitize_title($val) . '/');
            } elseif ($rule === 'country' && !empty($val)) {
                $view_all_url = home_url('/country/' . sanitize_title($val) . '/');
            } elseif ($rule === 'year' && !empty($val)) {
                $view_all_url = home_url('/?release_year=' . urlencode($val));
            }

            // Specific overrides
            if ($id === 'tvshows') {
                $view_all_url = home_url('/tvshows/');
            } elseif ($id === 'movies') {
                $view_all_url = home_url('/movies/');
            }

            // Query Movies and TV Shows CPTs
            $query_args = array(
                'post_type'      => array('movies', 'tvshows'),
                'post_status'    => 'publish',
                'posts_per_page' => 10,
                'orderby'        => 'date',
                'order'          => 'DESC'
            );

            if ($rule === 'category') {
                $query_args['tax_query'] = array(array('taxonomy' => 'movie_category', 'field' => 'slug', 'terms' => $val));
            } elseif ($rule === 'genre') {
                $query_args['tax_query'] = array(array('taxonomy' => 'genre', 'field' => 'slug', 'terms' => $val));
            } elseif ($rule === 'country') {
                $query_args['tax_query'] = array(array('taxonomy' => 'country', 'field' => 'slug', 'terms' => $val));
            } elseif ($rule === 'year') {
                $query_args['meta_query'] = array(array('key' => 'release_year', 'value' => $val, 'compare' => '='));
            }
        ?>
        <section class="movie-section-block" id="block-<?php echo esc_attr($id); ?>">
            <div class="block-header">
                <div class="block-title-wrapper">
                    <div class="block-icon">
                        <i class="fa-solid <?php echo esc_attr($icon); ?>"></i>
                    </div>
                    <h2 class="block-title"><?php echo esc_html($title); ?></h2>
                </div>
                <a href="<?php echo esc_url($view_all_url); ?>" class="view-all-btn">
                    View All <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <!-- Movies & TV Shows Grid -->
            <div class="movies-grid">
                <?php
                $block_query = new WP_Query($query_args);

                if ($block_query->have_posts()) :
                    while ($block_query->have_posts()) : $block_query->the_post();
                        movie_elite_render_card_item();
                    endwhile;
                    wp_reset_postdata();
                else :
                    // Fallback query if term empty
                    $fallback_query = new WP_Query(array('post_type' => array('movies', 'tvshows'), 'post_status' => 'publish', 'posts_per_page' => 10, 'orderby' => 'rand'));
                    if ($fallback_query->have_posts()) :
                        while ($fallback_query->have_posts()) : $fallback_query->the_post();
                            movie_elite_render_card_item();
                        endwhile;
                        wp_reset_postdata();
                    endif;
                endif;
                ?>
            </div>
        </section>
        <?php endforeach; ?>

    </div>
</main>

<?php get_footer(); ?>
