<?php
/**
 * MovieElite Pro - Actor & Celebrity Profile Taxonomy Template
 */

get_header();
$term = get_queried_object();
$actor_name = $term ? $term->name : 'Actor Profile';
$actor_desc = $term ? $term->description : '';
?>

<main class="main-content taxonomy-actor-wrapper">
    <div class="container">
        
        <!-- Breadcrumbs -->
        <div style="margin-bottom: 20px; font-size: 0.85rem; color: var(--text-muted);">
            <a href="<?php echo esc_url(home_url('/')); ?>">Home</a> &nbsp;/&nbsp; 
            <span>Actor Profile</span> &nbsp;/&nbsp; 
            <span style="color:#fff;"><?php echo esc_html($actor_name); ?></span>
        </div>

        <!-- Actor Header Profile Banner -->
        <div style="background: linear-gradient(135deg, #1f2334, #292d3f); border: 1px solid var(--border-color); padding: 30px; border-radius: var(--radius-lg); margin-bottom: 35px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); display: flex; align-items: center; gap: 25px; flex-wrap: wrap;">
            <div style="width: 90px; height: 90px; border-radius: 50%; background: linear-gradient(135deg, var(--accent-cyan), var(--accent-blue)); color: #000; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; flex-shrink: 0; box-shadow: 0 0 25px rgba(0, 212, 255, 0.4);">
                <i class="fa-solid fa-user-ninja"></i>
            </div>
            <div>
                <span style="font-size: 0.78rem; font-weight: 800; text-transform: uppercase; color: var(--accent-cyan); letter-spacing: 0.6px;"><i class="fa-solid fa-star"></i> Celebrity / Cast Hub</span>
                <h1 style="font-size: 2.2rem; font-weight: 900; color: #fff; margin: 4px 0 10px 0;"><?php echo esc_html($actor_name); ?></h1>
                <?php if (!empty($actor_desc)) : ?>
                <p style="color: var(--text-muted); margin: 0; max-width: 750px; font-size: 0.92rem; line-height: 1.6;"><?php echo esc_html($actor_desc); ?></p>
                <?php else : ?>
                <p style="color: var(--text-muted); margin: 0; font-size: 0.9rem;">Explore all movies and TV series starring <?php echo esc_html($actor_name); ?>.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Movies & TV Shows Grid -->
        <div class="movie-section-block">
            <div class="block-header">
                <div class="block-title-wrapper">
                    <h2 class="block-title"><i class="fa-solid fa-clapperboard" style="color:var(--accent-cyan);"></i> Filmography & Titles</h2>
                </div>
            </div>

            <?php if (have_posts()) : ?>
                <div class="movies-grid">
                    <?php
                    while (have_posts()) : the_post();
                        movie_elite_render_card_item();
                    endwhile;
                    ?>
                </div>

                <!-- Pagination -->
                <div style="margin-top: 35px; text-align: center;">
                    <?php
                    the_posts_pagination(array(
                        'prev_text' => '◀ Prev',
                        'next_text' => 'Next ▶',
                        'class'     => 'pagination-bar'
                    ));
                    ?>
                </div>
            <?php else : ?>
                <div style="background: var(--bg-card); padding: 40px; text-align: center; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                    <p style="color: var(--text-muted); font-size: 1.1rem;">No movies or TV shows found for this actor yet.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<?php get_footer(); ?>
