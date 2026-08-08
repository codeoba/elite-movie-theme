<?php
/**
 * MovieElite Pro - Single Movie Detail & Multi-Source Player Template
 */

get_header();

while (have_posts()) : the_post();
    $post_id   = get_the_ID();
    $title     = get_the_title();
    $imdb_id   = get_post_meta($post_id, 'imdb_id', true) ?: 'tt1630029';
    $tmdb_id   = get_post_meta($post_id, 'tmdb_id', true) ?: '76600';
    $rating    = get_post_meta($post_id, 'imdb_rating', true) ?: '8.5';
    $year      = get_post_meta($post_id, 'release_year', true) ?: '2026';
    $quality   = get_post_meta($post_id, 'movie_quality', true) ?: '4K UHD';
    $poster    = get_post_meta($post_id, 'poster_url', true);
    $backdrop  = get_post_meta($post_id, 'backdrop_url', true) ?: $poster;

    if (empty($poster)) {
        $poster = 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=500&auto=format&fit=crop&q=80';
    }

    // Get Multi-Source Embed Servers (4+ Servers)
    $embeds = get_post_meta($post_id, 'movie_embed_sources', true);
    if (empty($embeds) && function_exists('movie_elite_generate_movie_embeds')) {
        $embeds = movie_elite_generate_movie_embeds($imdb_id, $tmdb_id);
    }

    $genres = get_the_terms($post_id, 'genre');
    $genre_names = (!empty($genres) && !is_wp_error($genres)) ? wp_list_pluck($genres, 'name') : array('Cinema', 'Action');
?>

<main class="main-content single-movie-wrapper">
    <div class="container">
        
        <!-- Breadcrumbs -->
        <div style="margin-bottom: 20px; font-size: 0.85rem; color: var(--text-muted);">
            <a href="<?php echo esc_url(home_url('/')); ?>">Home</a> &nbsp;/&nbsp; 
            <a href="<?php echo esc_url(home_url('/?s=' . urlencode($genre_names[0]))); ?>"><?php echo esc_html($genre_names[0]); ?></a> &nbsp;/&nbsp; 
            <span style="color:#fff;"><?php echo esc_html($title); ?></span>
        </div>

        <!-- Multi-Server Video Player -->
        <div class="movie-player-container">
            <!-- Server Switcher Tabs (4+ Servers) -->
            <div class="server-switcher-bar">
                <span class="server-label"><i class="fa-solid fa-server" style="color:var(--accent-cyan);"></i> SELECT SERVER:</span>
                <?php
                if (!empty($embeds) && is_array($embeds)) :
                    $server_num = 0;
                    foreach ($embeds as $srv) :
                        $server_num++;
                        $active = ($server_num === 1) ? 'active' : '';
                        $srv_url = esc_url($srv['url']);
                        $srv_name = esc_html($srv['name'] ?? "Server {$server_num}");
                ?>
                <button type="button" class="server-tab <?php echo $active; ?>" data-url="<?php echo $srv_url; ?>">
                    <i class="fa-solid fa-play"></i> <?php echo $srv_name; ?>
                </button>
                <?php
                    endforeach;
                else :
                    // Fallback Server list with official SuperEmbed.stream and AutoEmbed.net domains
                    $fallback_url = "https://vidsrc.to/embed/movie/{$imdb_id}";
                ?>
                <button type="button" class="server-tab active" data-url="<?php echo esc_url($fallback_url); ?>">
                    <i class="fa-solid fa-play"></i> Server 1 (VidSrc Pro)
                </button>
                <button type="button" class="server-tab" data-url="https://www.superembed.stream/directstream.php?video_id=<?php echo esc_attr($imdb_id); ?>">
                    <i class="fa-solid fa-play"></i> Server 2 (SuperEmbed Stream)
                </button>
                <button type="button" class="server-tab" data-url="https://autoembed.net/embed/movie/<?php echo esc_attr($tmdb_id); ?>">
                    <i class="fa-solid fa-play"></i> Server 3 (AutoEmbed Net)
                </button>
                <button type="button" class="server-tab" data-url="https://www.2embed.cc/embed/<?php echo esc_attr($imdb_id); ?>">
                    <i class="fa-solid fa-play"></i> Server 4 (2Embed Mirror)
                </button>
                <?php endif; ?>
            </div>

            <!-- Embed Player Frame -->
            <div class="iframe-player-wrapper">
                <iframe id="main-movie-iframe" src="<?php echo esc_url($embeds[0]['url'] ?? "https://vidsrc.to/embed/movie/{$imdb_id}"); ?>" allowfullscreen></iframe>
            </div>

            <!-- Player Sub-Bar -->
            <div style="padding: 14px 24px; display: flex; align-items: center; justify-content: space-between; background: #0d1017; font-size: 0.85rem; color: var(--text-muted);">
                <div>
                    <span style="color:var(--accent-green); font-weight:700;"><i class="fa-solid fa-circle-check"></i> Active Multi-Source Player (SuperEmbed.stream Verified)</span>
                </div>
                <div style="display:flex; gap:12px;">
                    <button type="button" class="alphabet-btn" onclick="document.getElementById('main-movie-iframe').src=document.getElementById('main-movie-iframe').src;" style="background:rgba(255,255,255,0.08);"><i class="fa-solid fa-rotate-right"></i> Reload</button>
                </div>
            </div>
        </div>

        <!-- Movie Details & Metadata -->
        <div style="display:grid; grid-template-columns: 280px 1fr; gap:35px;">
            <!-- Poster Sidebar -->
            <div>
                <div style="position:relative; border-radius:var(--radius-md); overflow:hidden; border:1px solid var(--border-color); box-shadow:0 15px 30px rgba(0,0,0,0.5);">
                    <img src="<?php echo esc_url($poster); ?>" alt="<?php echo esc_attr($title); ?>" style="width:100%; display:block;" />
                </div>
            </div>

            <!-- Info Details -->
            <div>
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px;">
                    <span class="slide-badge" style="margin:0;"><i class="fa-solid fa-film"></i> <?php echo esc_html(implode(', ', $genre_names)); ?></span>
                    <span style="background:var(--accent-gold); color:#000; font-weight:900; padding:2px 8px; border-radius:4px; font-size:0.8rem;"><i class="fa-solid fa-star"></i> IMDb <?php echo esc_html($rating); ?></span>
                </div>

                <h1 style="font-size: 2.2rem; font-weight: 900; color: #fff; margin-bottom: 12px; line-height: 1.2;"><?php echo esc_html($title); ?></h1>
                
                <div style="display:flex; align-items:center; gap:20px; color:var(--text-muted); font-size:0.9rem; margin-bottom:20px;">
                    <span><i class="fa-solid fa-calendar-days" style="color:var(--accent-cyan);"></i> Release: <?php echo esc_html($year); ?></span>
                    <span><i class="fa-solid fa-clock" style="color:var(--accent-cyan);"></i> Duration: 2h 18m</span>
                    <span><i class="fa-solid fa-video" style="color:var(--accent-green);"></i> Quality: <?php echo esc_html($quality); ?></span>
                </div>

                <h3 style="color:#fff; font-size:1.2rem; margin-bottom:10px;">Storyline / Overview</h3>
                <div style="background:var(--bg-card); padding:20px; border-radius:var(--radius-md); border:1px solid var(--border-color); color:var(--text-muted); font-size:0.95rem; line-height:1.7; margin-bottom:30px;">
                    <?php the_content(); ?>
                </div>

                <!-- Related Movies -->
                <h3 style="color:#fff; font-size:1.3rem; margin-bottom:15px;">You May Also Like</h3>
                <div class="movies-grid" style="grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));">
                    <?php
                    $related_query = new WP_Query(array(
                        'post_type'      => 'movies',
                        'post_status'    => 'publish',
                        'posts_per_page' => 4,
                        'post__not_in'   => array($post_id),
                        'orderby'        => 'rand'
                    ));

                    if ($related_query->have_posts()) :
                        while ($related_query->have_posts()) : $related_query->the_post();
                            movie_elite_render_card_item();
                        endwhile;
                        wp_reset_postdata();
                    endif;
                    ?>
                </div>
            </div>
        </div>

    </div>
</main>

<?php
endwhile;
get_footer();
?>
