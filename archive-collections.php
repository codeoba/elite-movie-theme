<?php
/**
 * MovieElite Pro - Collections Archive View
 */
get_header();
?>

<main class="main-content">
    <div class="container" style="padding-top:30px; padding-bottom:50px;">
        <div style="margin-bottom:30px;">
            <h1 style="font-size:2rem; font-weight:900; color:#fff; display:flex; align-items:center; gap:12px;">
                <i class="fa-solid fa-layer-group" style="color:var(--accent-cyan);"></i> Movie Collections & Franchises
            </h1>
            <p style="color:var(--text-muted); font-size:0.95rem;">Browse all curated movie collections, sagas, and franchises.</p>
        </div>

        <div class="movies-grid">
            <?php
            if (have_posts()) :
                while (have_posts()) : the_post();
                    $cid     = get_the_ID();
                    $ctitle  = get_the_title();
                    $cperma  = get_permalink();
                    $cposter = get_post_meta($cid, 'poster_url', true) ?: 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=500&auto=format&fit=crop&q=80';
                    $cmovies = get_post_meta($cid, '_collection_movie_ids', true);
                    $count   = is_array($cmovies) ? count($cmovies) : 0;
            ?>
            <div class="movie-card">
                <div class="card-poster">
                    <a href="<?php echo esc_url($cperma); ?>" style="display:block;width:100%;height:100%;">
                        <img src="<?php echo esc_url($cposter); ?>" alt="<?php echo esc_attr($ctitle); ?>" loading="lazy" />
                        <div class="card-quality-badge" style="background:var(--accent-cyan); color:#000; font-weight:800;"><?php echo $count; ?> TITLES</div>
                    </a>
                </div>
                <div class="card-details">
                    <div class="card-genre">COLLECTION</div>
                    <h3 class="card-title">
                        <a href="<?php echo esc_url($cperma); ?>"><?php echo esc_html($ctitle); ?></a>
                    </h3>
                </div>
            </div>
            <?php
                endwhile;
            else :
                echo '<p style="color:var(--text-muted); grid-column:1/-1;">No collections found.</p>';
            endif;
            ?>
        </div>
    </div>
</main>

<?php get_footer(); ?>
