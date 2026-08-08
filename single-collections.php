<?php
/**
 * MovieElite Pro - Single Collection Detail View
 */
get_header();

while (have_posts()) : the_post();
    $post_id = get_the_ID();
    $title   = get_the_title();
    $poster  = get_post_meta($post_id, 'poster_url', true);
    $backdrop= get_post_meta($post_id, 'backdrop_url', true) ?: $poster;
    $movie_ids = get_post_meta($post_id, '_collection_movie_ids', true);
    if (!is_array($movie_ids)) $movie_ids = array();
?>

<main class="main-content">
    <div class="container" style="padding-top:20px; padding-bottom:40px;">

        <!-- Collection Hero Banner -->
        <div style="position:relative; border-radius:var(--radius-lg); overflow:hidden; margin-bottom:30px; min-height:280px; background-size:cover; background-position:center top; background-image:url('<?php echo esc_url($backdrop); ?>');">
            <div style="position:absolute; inset:0; background:linear-gradient(0deg, var(--bg-primary) 0%, rgba(26,29,46,0.7) 100%);"></div>
            <div style="position:relative; z-index:2; padding:40px; display:flex; gap:24px; align-items:flex-end;">
                <?php if ($poster) : ?>
                <div style="width:140px; flex-shrink:0; border-radius:var(--radius-md); overflow:hidden; box-shadow:0 10px 25px rgba(0,0,0,0.5);">
                    <img src="<?php echo esc_url($poster); ?>" alt="<?php echo esc_attr($title); ?>" style="width:100%; display:block;" />
                </div>
                <?php endif; ?>
                <div>
                    <span class="slide-badge" style="background:rgba(0,212,255,0.2); border-color:var(--accent-cyan); color:var(--accent-cyan);"><i class="fa-solid fa-layer-group"></i> MOVIE COLLECTION</span>
                    <h1 style="font-size:2.2rem; font-weight:900; color:#fff; margin:10px 0;"><?php echo esc_html($title); ?></h1>
                    <p style="color:var(--text-muted); font-size:0.95rem; max-width:700px;"><?php echo get_the_excerpt(); ?></p>
                    <span style="font-weight:700; color:var(--accent-gold); font-size:0.88rem;"><i class="fa-solid fa-film"></i> <?php echo count($movie_ids); ?> Titles included</span>
                </div>
            </div>
        </div>

        <div style="margin-bottom:30px; color:var(--text-muted); line-height:1.7;">
            <?php the_content(); ?>
        </div>

        <!-- Collection Movies Grid -->
        <h2 style="font-size:1.4rem; font-weight:800; color:#fff; margin-bottom:20px; display:flex; align-items:center; gap:10px;">
            <i class="fa-solid fa-clapperboard" style="color:var(--accent-cyan);"></i> Movies in this Collection
        </h2>

        <?php
        if (!empty($movie_ids)) {
            $cq = new WP_Query(array(
                'post_type'      => array('movies', 'tvshows'),
                'post__in'       => array_map('intval', $movie_ids),
                'posts_per_page' => -1,
                'orderby'        => 'post__in'
            ));
            if ($cq->have_posts()) {
                echo '<div class="movies-grid">';
                while ($cq->have_posts()) {
                    $cq->the_post();
                    movie_elite_render_card_item();
                }
                echo '</div>';
                wp_reset_postdata();
            }
        } else {
            echo '<p style="color:var(--text-muted); padding:30px 0;">No movies added to this collection yet.</p>';
        }
        ?>

    </div>
</main>

<?php
endwhile;
get_footer();
