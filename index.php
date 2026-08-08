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

    <!-- Continue Watching Section (Populated via JS localStorage) -->
    <section class="container" id="me-continue-watching-section" style="display:none; margin-bottom:30px;">
        <div style="background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:18px 22px;">
            <h3 style="font-size:1.1rem; font-weight:800; color:#fff; margin-bottom:14px; display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-clock-rotate-left" style="color:var(--accent-gold);"></i> Continue Watching
            </h3>
            <div id="me-cw-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(180px, 1fr)); gap:14px;"></div>
        </div>
    </section>

    <!-- Alphabetical A-Z & Advanced Filter Bar -->
    <section class="alphabet-filter-section">
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
                    <option value="movies">Movies Only</option>
                    <option value="tvshows">TV Shows / Dramas</option>
                </select>
                <select id="me-filter-genre" class="alphabet-btn" style="padding:6px 12px; background:var(--bg-card); color:#fff; border:1px solid var(--border-color); font-size:0.85rem;">
                    <option value="">All Genres</option>
                </select>
                <select id="me-filter-country" class="alphabet-btn" style="padding:6px 12px; background:var(--bg-card); color:#fff; border:1px solid var(--border-color); font-size:0.85rem;">
                    <option value="">All Countries</option>
                </select>
                <select id="me-filter-year" class="alphabet-btn" style="padding:6px 12px; background:var(--bg-card); color:#fff; border:1px solid var(--border-color); font-size:0.85rem;">
                    <option value="">All Years</option>
                </select>
                <select id="me-filter-quality" class="alphabet-btn" style="padding:6px 12px; background:var(--bg-card); color:#fff; border:1px solid var(--border-color); font-size:0.85rem;">
                    <option value="">All Qualities</option>
                    <option value="4K">4K Ultra HD</option>
                    <option value="1080p">1080p Full HD</option>
                    <option value="720p">720p HD</option>
                </select>
                <button type="button" id="me-filter-reset" class="alphabet-btn" style="background:rgba(255,255,255,0.08); color:var(--text-muted);">
                    <i class="fa-solid fa-rotate-left"></i> Reset
                </button>
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

            // -------------------------------------------------------
            // Smart Country Matching: Korea, China, Asian Drama
            // -------------------------------------------------------

            // Korea variants (South Korea, North Korea, Korea etc.)
            $korea_country_slugs = array(
                'south-korea', 'korea', 'korean', 'north-korea',
                'republic-of-korea', 'dprk', 'hanguk'
            );

            // China / Chinese-speaking region variants
            $china_country_slugs = array(
                'china', 'chinese', 'hong-kong', 'taiwan',
                'peoples-republic-of-china', 'prc', 'mainland-china'
            );

            // Asian countries for the Asian Drama block
            $asia_country_slugs = array(
                // East Asia
                'south-korea', 'korea', 'korean', 'north-korea',
                'china', 'chinese', 'hong-kong', 'taiwan',
                'japan', 'japanese',
                // Southeast Asia
                'thailand', 'thai', 'vietnam', 'vietnamese',
                'philippines', 'filipino', 'indonesia', 'indonesian',
                'malaysia', 'malaysian', 'singapore',
                // South Asia
                'india', 'indian', 'pakistan', 'sri-lanka', 'bangladesh',
                // Other Asia
                'mongolia', 'myanmar', 'cambodia', 'laos',
            );

            if ($rule === 'category') {
                $query_args['tax_query'] = array(array('taxonomy' => 'movie_category', 'field' => 'slug', 'terms' => $val));

            } elseif ($rule === 'genre') {
                $query_args['tax_query'] = array(array('taxonomy' => 'genre', 'field' => 'slug', 'terms' => $val));

            } elseif ($rule === 'post_type') {
                // post_type rule: query only the specified CPT, no taxonomy filter
                $query_args['post_type'] = array(sanitize_key($val));
                // (no tax_query - shows all content of that post type)

            } elseif ($rule === 'country') {
                // Detect Korea or China block by their configured value
                $country_val_lower = strtolower(trim($val));

                if (strpos($country_val_lower, 'korea') !== false || $country_val_lower === 'korean') {
                    // Korean block: match ALL Korea variants
                    $query_args['tax_query'] = array(array(
                        'taxonomy' => 'country',
                        'field'    => 'slug',
                        'terms'    => $korea_country_slugs,
                        'operator' => 'IN',
                    ));
                } elseif (strpos($country_val_lower, 'chin') !== false || strpos($country_val_lower, 'hong') !== false || $country_val_lower === 'taiwan') {
                    // Chinese block: match China + HK + Taiwan
                    $query_args['tax_query'] = array(array(
                        'taxonomy' => 'country',
                        'field'    => 'slug',
                        'terms'    => $china_country_slugs,
                        'operator' => 'IN',
                    ));
                } else {
                    // Generic country block: single slug match
                    $query_args['tax_query'] = array(array(
                        'taxonomy' => 'country',
                        'field'    => 'slug',
                        'terms'    => sanitize_title($val),
                        'operator' => 'IN',
                    ));
                }

            } elseif ($rule === 'year') {
                $query_args['meta_query'] = array(array('key' => 'release_year', 'value' => $val, 'compare' => '='));
            }

            // Special rule for TV Shows block: query all tvshows CPT posts directly
            if ($id === 'tvshows' || ($rule === 'post_type' && $val === 'tvshows') || ($rule === 'category' && ($val === 'tvshows' || $val === 'tv-shows'))) {
                $query_args['post_type'] = array('tvshows');
                unset($query_args['tax_query']);
                $view_all_url = home_url('/tvshows/');
            }

            // Special rule for Movies block: query all movies CPT posts directly
            if ($id === 'movies' || ($rule === 'post_type' && $val === 'movies')) {
                $query_args['post_type'] = array('movies');
                unset($query_args['tax_query']);
                $view_all_url = home_url('/movies/');
            }

            // Special rule for Asian Drama block: query tvshows by ALL Asian countries
            if ($id === 'asiandrama') {
                $query_args['post_type'] = array('tvshows'); // Dramas only
                $query_args['tax_query'] = array(
                    'relation' => 'OR',
                    // Match Asian country taxonomy
                    array(
                        'taxonomy' => 'country',
                        'field'    => 'slug',
                        'terms'    => $asia_country_slugs,
                        'operator' => 'IN',
                    ),
                    // Also match Asian Drama category
                    array(
                        'taxonomy' => 'movie_category',
                        'field'    => 'slug',
                        'terms'    => array('asian-drama', 'asiandrama', 'asian-dramas', 'kdrama', 'cdrama', 'jdrama', 'thai-drama'),
                        'operator' => 'IN',
                    ),
                );
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
                    echo '<p style="color:var(--text-muted); padding:20px 0; font-size:0.9rem;"><i class="fa-solid fa-circle-info"></i> No content available for this category yet. Add posts with the matching country or genre tag.</p>';
                endif;
                ?>
            </div>
        </section>
        <?php endforeach; ?>

    </div>
</main>

<?php get_footer(); ?>
