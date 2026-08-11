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
                        $trailer_url = get_post_meta($post_id, 'youtube_trailer_id', true) ?: get_post_meta($post_id, 'trailer_url', true);
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
                        <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap; margin-top:15px;">
                            <a href="<?php the_permalink(); ?>" class="btn-watch-slide">
                                <i class="fa-solid fa-play"></i> Watch Now
                            </a>
                            <?php if (!empty($trailer_url)) : ?>
                            <button type="button" class="btn-watch-slide btn-open-trailer" data-trailer="<?php echo esc_attr($trailer_url); ?>" style="background:rgba(255,255,255,0.12); color:#fff; border:1px solid var(--border-color); box-shadow:none;">
                                <i class="fa-brands fa-youtube" style="color:#ff0000;"></i> Watch Trailer
                            </button>
                            <?php endif; ?>
                        </div>
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

    <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        var rawHistory = localStorage.getItem('movie_elite_continue_watching');
        if (!rawHistory) return;
        try {
            var items = JSON.parse(rawHistory);
            if (!Array.isArray(items) || items.length === 0) return;
            var container = document.getElementById('me-cw-grid');
            var section = document.getElementById('me-continue-watching-section');
            if (!container || !section) return;

            var html = '';
            items.forEach(function(item) {
                var epText = item.totalEps > 1 ? 'Ep ' + (item.ep || 1) + ' / ' + item.totalEps : 'Movie';
                var pct = item.pct || 50;
                html += '<div style="background:var(--bg-primary); border:1px solid var(--border-color); border-radius:8px; overflow:hidden; display:flex; flex-direction:column; position:relative;">';
                html += '  <div style="height:120px; position:relative; background:#1e293b;">';
                html += '    <img src="' + (item.poster || '') + '" style="width:100%; height:100%; object-fit:cover;" alt="' + item.title + '" />';
                html += '    <span style="position:absolute; top:6px; right:6px; background:rgba(0,0,0,0.8); color:var(--accent-gold); font-size:0.65rem; font-weight:800; padding:2px 6px; border-radius:4px;">' + epText + '</span>';
                html += '    <div style="position:absolute; bottom:0; left:0; right:0; height:4px; background:rgba(255,255,255,0.2);">';
                html += '      <div style="height:100%; width:' + pct + '%; background:var(--accent-cyan);"></div>';
                html += '    </div>';
                html += '  </div>';
                html += '  <div style="padding:10px; display:flex; flex-direction:column; justify-content:space-between; flex:1;">';
                html += '    <h4 style="margin:0 0 8px 0; font-size:0.82rem; color:#fff; font-weight:700; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">' + item.title + '</h4>';
                html += '    <a href="' + item.permalink + '" style="padding:5px 8px; font-size:0.75rem; text-align:center; display:block; border-radius:4px; background:var(--accent-cyan); color:#000; font-weight:800; text-decoration:none;"><i class="fa-solid fa-play"></i> Resume</a>';
                html += '  </div>';
                html += '</div>';
            });

            container.innerHTML = html;
            section.style.display = 'block';
        } catch(e) {}
    });
    </script>

    <!-- Alphabetical A-Z & Advanced Filter Bar -->
    <?php if (function_exists('movie_elite_render_filter_bar')) movie_elite_render_filter_bar(''); ?>

    <!-- Dynamic Section Blocks -->
    <div class="container">
        <!-- Homepage Tabbed Content Switcher (Trending, Now Playing, Coming Soon, Top Rated) -->
        <?php if (function_exists('movie_elite_render_homepage_tabs_switcher')) movie_elite_render_homepage_tabs_switcher(); ?>

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
            $count = isset($blk['count']) ? max(1, intval($blk['count'])) : 10;

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
                'posts_per_page' => $count,
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

        <!-- Weekly Airing Schedule Calendar (For TV Shows & Asian Dramas) -->
        <?php if (function_exists('movie_elite_render_airing_schedule')) movie_elite_render_airing_schedule(); ?>

    </div>
</main>

<?php get_footer(); ?>
