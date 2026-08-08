<?php
/**
 * MovieElite Pro - Universal Archive & Taxonomy Listing Template
 * Handles Movies CPT Archive, TV Shows CPT Archive, Categories, Genres, Countries, Years & Search Results
 * Displays responsive 3-column mobile layout with full pagination!
 */

get_header();

// Determine Page Title & Subtitle
$archive_title    = 'Browse Catalog';
$archive_subtitle = 'Explore all titles available in this collection';
$archive_icon     = 'fa-film';

if (is_post_type_archive('movies')) {
    $archive_title    = '🍿 All Movies';
    $archive_subtitle = 'Browse our complete collection of HD & 4K movies';
    $archive_icon     = 'fa-film';
} elseif (is_post_type_archive('tvshows')) {
    $archive_title    = '📺 All TV Shows & Dramas';
    $archive_subtitle = 'Browse seasons and episodes of top-rated TV series';
    $archive_icon     = 'fa-tv';
} elseif (is_tax('movie_category')) {
    $term = get_queried_object();
    $archive_title    = '⭐ Category: ' . ($term->name ?? 'Collection');
    $archive_subtitle = 'All titles listed under ' . ($term->name ?? 'category');
    $archive_icon     = 'fa-layer-group';
} elseif (is_tax('genre')) {
    $term = get_queried_object();
    $archive_title    = '🎭 Genre: ' . ($term->name ?? 'Genre');
    $archive_subtitle = 'All titles listed under ' . ($term->name ?? 'genre');
    $archive_icon     = 'fa-masks-theater';
} elseif (is_tax('country')) {
    $term = get_queried_object();
    $archive_title    = '🌍 Country: ' . ($term->name ?? 'Country');
    $archive_subtitle = 'All titles produced in ' . ($term->name ?? 'country');
    $archive_icon     = 'fa-globe';
} elseif (is_search()) {
    $archive_title    = '🔍 Search Results for: "' . esc_html(get_search_query()) . '"';
    $archive_subtitle = 'Found titles matching your search query';
    $archive_icon     = 'fa-magnifying-glass';
} elseif (isset($_GET['release_year'])) {
    $year_val = sanitize_text_field($_GET['release_year']);
    $archive_title    = '📅 Release Year: ' . esc_html($year_val);
    $archive_subtitle = 'Titles released in the year ' . esc_html($year_val);
    $archive_icon     = 'fa-calendar-days';
}
?>

<main class="main-content" style="padding:30px 0 60px;">
    <div class="container">
        
        <!-- Archive Header Banner -->
        <div style="background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:30px; margin-bottom:35px; box-shadow:0 15px 30px rgba(0,0,0,0.5); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:15px;">
            <div>
                <h1 style="color:#fff; font-size:1.8rem; font-weight:900; margin-bottom:6px; display:flex; align-items:center; gap:12px;">
                    <i class="fa-solid <?php echo esc_attr($archive_icon); ?>" style="color:var(--accent-cyan);"></i>
                    <?php echo esc_html($archive_title); ?>
                </h1>
                <p style="color:var(--text-muted); font-size:0.9rem; margin:0;"><?php echo esc_html($archive_subtitle); ?></p>
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
                <h3 style="color:#fff; margin-bottom:10px;">No Titles Found</h3>
                <p style="color:var(--text-muted); margin-bottom:20px;">We couldn't find any titles matching this collection right now.</p>
                <a href="<?php echo esc_url(home_url('/')); ?>" class="btn-watch-slide" style="text-decoration:none; display:inline-flex;">
                    <i class="fa-solid fa-house"></i> Return to Homepage
                </a>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php get_footer(); ?>
