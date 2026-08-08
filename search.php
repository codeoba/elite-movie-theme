<?php
/**
 * MovieElite Pro - Search Results Template
 * Renders matching Movies & TV Shows search query in responsive 3-column mobile layout.
 */

get_header();

$search_query = get_search_query();
?>

<main class="main-content" style="padding:30px 0 60px;">
    <div class="container">
        
        <!-- Search Header Banner -->
        <div style="background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:30px; margin-bottom:35px; box-shadow:0 15px 30px rgba(0,0,0,0.5); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:15px;">
            <div>
                <h1 style="color:#fff; font-size:1.8rem; font-weight:900; margin-bottom:6px; display:flex; align-items:center; gap:12px;">
                    <i class="fa-solid fa-magnifying-glass" style="color:var(--accent-cyan);"></i>
                    Search Results: "<?php echo esc_html($search_query); ?>"
                </h1>
                <p style="color:var(--text-muted); font-size:0.9rem; margin:0;">
                    Found catalog items matching your search query.
                </p>
            </div>
            
            <a href="<?php echo esc_url(home_url('/')); ?>" class="alphabet-btn" style="background:rgba(255,255,255,0.08); text-decoration:none; padding:8px 16px;">
                <i class="fa-solid fa-house"></i> Home
            </a>
        </div>

        <!-- Movies & TV Shows Grid (3 Columns on Mobile via CSS) -->
        <?php if (have_posts()) : ?>
            <div class="movies-grid" style="margin-bottom:40px;">
                <?php
                while (have_posts()) : the_post();
                    movie_elite_render_card_item();
                endwhile;
                ?>
            </div>

            <!-- Full Numeric Pagination Bar -->
            <div style="display:flex; justify-content:center; align-items:center; gap:8px; margin-top:30px; flex-wrap:wrap;">
                <?php
                echo paginate_links(array(
                    'prev_text' => '<i class="fa-solid fa-chevron-left"></i> Prev',
                    'next_text' => 'Next <i class="fa-solid fa-chevron-right"></i>',
                    'type'      => 'plain',
                    'before_page_number' => '<span class="alphabet-btn" style="min-width:36px;">',
                    'after_page_number'  => '</span>'
                ));
                ?>
            </div>
        <?php else : ?>
            <div style="text-align:center; padding:60px 20px; background:var(--bg-secondary); border-radius:var(--radius-lg); border:1px solid var(--border-color);">
                <i class="fa-solid fa-film" style="font-size:3rem; color:var(--text-muted); margin-bottom:15px; display:block;"></i>
                <h3 style="color:#fff; margin-bottom:10px;">No Results Found for "<?php echo esc_html($search_query); ?>"</h3>
                <p style="color:var(--text-muted); margin-bottom:20px;">Try searching for movie titles, actors, or genres using different keywords.</p>
                <a href="<?php echo esc_url(home_url('/')); ?>" class="btn-watch-slide" style="text-decoration:none; display:inline-flex;">
                    <i class="fa-solid fa-house"></i> Return to Homepage
                </a>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php get_footer(); ?>
