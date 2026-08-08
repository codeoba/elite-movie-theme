<?php
/**
 * MovieElite Pro - Main Frontpage Template
 */

get_header();
?>

<main class="main-content">
    <div class="container">

        <!-- 1. HERO SLIDER -->
        <section class="hero-slider-section">
            <div class="slider-container" id="movie-hero-slider">
                <?php
                $slider_query = new WP_Query(array(
                    'post_type'      => array('movies', 'tvshows'),
                    'post_status'    => 'publish',
                    'posts_per_page' => 5,
                    'orderby'        => 'date',
                    'order'          => 'DESC'
                ));

                $slide_index = 0;
                if ($slider_query->have_posts()) :
                    while ($slider_query->have_posts()) : $slider_query->the_post();
                        $slide_index++;
                        $title     = get_the_title();
                        $permalink = get_permalink();
                        $rating    = get_post_meta(get_the_ID(), 'imdb_rating', true) ?: '8.5';
                        $year      = get_post_meta(get_the_ID(), 'release_year', true) ?: '2026';
                        $poster    = get_post_meta(get_the_ID(), 'backdrop_url', true) ?: get_post_meta(get_the_ID(), 'poster_url', true);
                        $overview  = get_the_excerpt() ?: get_the_content();

                        if (empty($poster)) {
                            $poster = 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=1200&auto=format&fit=crop&q=80';
                        }
                ?>
                <div class="slide-item <?php echo ($slide_index === 1) ? 'active' : ''; ?>" style="background-image: url('<?php echo esc_url($poster); ?>');">
                    <div class="slide-overlay"></div>
                    <div class="slide-content">
                        <div class="slide-badge">
                            <i class="fa-solid fa-fire"></i> FEATURED BLOCKBUSTER
                        </div>
                        <h2 class="slide-title"><?php echo esc_html($title); ?></h2>
                        <div class="slide-meta">
                            <span class="imdb-pill"><i class="fa-solid fa-star"></i> IMDb <?php echo esc_html($rating); ?></span>
                            <span><i class="fa-solid fa-calendar-days"></i> <?php echo esc_html($year); ?></span>
                            <span><i class="fa-solid fa-film"></i> 4K Ultra HD</span>
                        </div>
                        <p style="color:var(--text-muted); font-size:0.92rem; margin-bottom:24px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                            <?php echo esc_html(wp_strip_all_tags($overview)); ?>
                        </p>
                        <a href="<?php echo esc_url($permalink); ?>" class="btn-watch-slide">
                            <i class="fa-solid fa-play"></i> WATCH NOW
                        </a>
                    </div>
                </div>
                <?php
                    endwhile;
                    wp_reset_postdata();
                endif;
                ?>

                <!-- Slider Nav Buttons -->
                <div class="slider-controls">
                    <button type="button" class="btn-slide-prev" id="btn-hero-prev"><i class="fa-solid fa-chevron-left"></i></button>
                    <button type="button" class="btn-slide-next" id="btn-hero-next"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
            </div>
        </section>

        <!-- 2. ALPHABETICAL FILTER BAR -->
        <section class="alphabet-filter-section">
            <div class="alphabet-bar">
                <span class="alphabet-label"><i class="fa-solid fa-filter" style="color:var(--accent-cyan);"></i> FILTER A-Z:</span>
                <div class="alphabet-links">
                    <button type="button" class="alphabet-btn active" data-letter="ALL">ALL</button>
                    <button type="button" class="alphabet-btn" data-letter="#">#</button>
                    <?php
                    foreach (range('A', 'Z') as $letter) {
                        echo '<button type="button" class="alphabet-btn" data-letter="' . $letter . '">' . $letter . '</button>';
                    }
                    ?>
                </div>
            </div>
        </section>

        <!-- 3. DYNAMIC CATEGORY BLOCKS -->
        <?php
        $blocks = function_exists('movie_elite_get_blocks_config') ? movie_elite_get_blocks_config() : array();

        foreach ($blocks as $id => $blk) :
            if (($blk['status'] ?? 'off') !== 'on') {
                continue; // Skip disabled blocks
            }

            $rule  = $blk['rule'] ?? 'category';
            $val   = $blk['value'] ?? 'recommended';
            $title = $blk['name'] ?? 'Block';
            $icon  = $blk['icon'] ?? 'fa-film';

            // Query both Movies and TV Shows CPTs
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
                <a href="<?php echo esc_url(home_url('/?s=' . urlencode($title))); ?>" class="view-all-btn">
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
