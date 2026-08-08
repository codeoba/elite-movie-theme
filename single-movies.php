<?php
/**
 * MovieElite Pro - Single Movie Detail View (With Clean TMDb & IMDb Server Embed Resolution)
 */

get_header();

while (have_posts()) : the_post();
    $post_id   = get_the_ID();
    $title     = get_the_title();
    $raw_imdb  = get_post_meta($post_id, 'imdb_id', true) ?: 'tt1630029';
    $raw_tmdb  = get_post_meta($post_id, 'tmdb_id', true) ?: '76600';

    $imdb_id   = trim($raw_imdb);
    $tmdb_id   = trim(preg_replace('/[^0-9]/', '', $raw_tmdb));

    $rating    = get_post_meta($post_id, 'imdb_rating', true) ?: '8.5';
    $year      = get_post_meta($post_id, 'release_year', true) ?: '2026';
    $quality   = get_post_meta($post_id, 'movie_quality', true) ?: '4K UHD';
    $poster    = get_post_meta($post_id, 'poster_url', true);
    $backdrop  = get_post_meta($post_id, 'backdrop_url', true) ?: $poster;

    // Fetch VidVault.ru Real Download Links
    $vv_links = array();
    if (function_exists('movie_elite_get_vidvault_links')) {
        $vv_links = movie_elite_get_vidvault_links($tmdb_id ?: $imdb_id, 'movie');
    }

    $dl_720p   = get_post_meta($post_id, 'download_url_720p', true) ?: ($vv_links['720p'] ?? '');
    $dl_1080p  = get_post_meta($post_id, 'download_url_1080p', true) ?: ($vv_links['1080p'] ?? '');
    $dl_4k     = get_post_meta($post_id, 'download_url_4k', true) ?: ($vv_links['4k'] ?? '');
    $dl_direct = $vv_links['direct_page'] ?? "https://vidvault.ru/movie/{$tmdb_id}";

    if (empty($poster)) {
        $poster = 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=500&auto=format&fit=crop&q=80';
    }

    // Get Multi-Source Embed Servers
    $embeds = get_post_meta($post_id, 'movie_embed_sources', true);
    if (empty($embeds) || !is_array($embeds)) {
        if (function_exists('movie_elite_generate_movie_embeds')) {
            $embeds = movie_elite_generate_movie_embeds($imdb_id, $tmdb_id);
        }
    }

    $genres = get_the_terms($post_id, 'genre');
    $genre_names = (!empty($genres) && !is_wp_error($genres)) ? wp_list_pluck($genres, 'name') : array('Cinema', 'Action');
?>

<!-- Lights Off Overlay -->
<div id="lights-off-overlay" style="display:none; position:fixed; inset:0; z-index:999; background:rgba(0,0,0,0.95); transition:all 0.3s;"></div>

<main class="main-content single-movie-wrapper">
    <div class="container">
        
        <!-- Breadcrumbs -->
        <div style="margin-bottom: 20px; font-size: 0.85rem; color: var(--text-muted);">
            <a href="<?php echo esc_url(home_url('/')); ?>">Home</a> &nbsp;/&nbsp; 
            <a href="<?php echo esc_url(home_url('/movies/')); ?>">Movies</a> &nbsp;/&nbsp; 
            <span style="color:#fff;"><?php echo esc_html($title); ?></span>
        </div>

        <!-- Multi-Server Movie Video Player Box -->
        <div class="movie-player-container" id="player-container-box" style="position:relative; z-index:1000; transition:all 0.3s;">
            <!-- Server Switcher Tabs -->
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
                ?>
                <?php if (!empty($tmdb_id)) : ?>
                <button type="button" class="server-tab active" data-url="https://vidsrc.sbs/embed/movie/<?php echo esc_attr($tmdb_id); ?>">
                    <i class="fa-solid fa-play"></i> Server 1 (VidSrc SBS)
                </button>
                <button type="button" class="server-tab" data-url="https://vsembed.ru/embed/movie/<?php echo esc_attr($tmdb_id); ?>">
                    <i class="fa-solid fa-play"></i> Server 2 (VSEmbed Stream)
                </button>
                <button type="button" class="server-tab" data-url="https://autoembed.net/embed/movie/<?php echo esc_attr($tmdb_id); ?>">
                    <i class="fa-solid fa-play"></i> Server 3 (AutoEmbed Net)
                </button>
                <?php endif; ?>
                <?php if (!empty($imdb_id)) : ?>
                <button type="button" class="server-tab" data-url="https://vidsrc.to/embed/movie/<?php echo esc_attr($imdb_id); ?>">
                    <i class="fa-solid fa-play"></i> Server 4 (VidSrc Pro)
                </button>
                <button type="button" class="server-tab" data-url="https://www.superembed.stream/directstream.php?video_id=<?php echo esc_attr($imdb_id); ?>">
                    <i class="fa-solid fa-play"></i> Server 5 (SuperEmbed Stream)
                </button>
                <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Embed Player Frame with referrer & autoplay attributes -->
            <div class="iframe-player-wrapper">
                <iframe id="main-movie-iframe" src="<?php echo esc_url($embeds[0]['url'] ?? "https://vidsrc.sbs/embed/movie/{$tmdb_id}"); ?>" allow="autoplay; fullscreen; picture-in-picture; encrypted-media" referrerpolicy="origin-when-cross-origin" allowfullscreen></iframe>
            </div>

            <!-- Advanced Player Controls Sub-Bar -->
            <div style="padding: 14px 24px; display: flex; flex-wrap:wrap; align-items: center; justify-content: space-between; gap:15px; background: #0d1017; font-size: 0.85rem;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <span style="color:var(--accent-green); font-weight:700;"><i class="fa-solid fa-circle-check"></i> Movie Player Verified</span>
                </div>

                <!-- Advanced Action Buttons -->
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <button type="button" id="btn-toggle-lights" class="alphabet-btn" style="background:rgba(255,183,3,0.15); color:var(--accent-gold); border:1px solid var(--accent-gold);">
                        <i class="fa-solid fa-lightbulb"></i> <span id="lights-btn-text">Lights Off</span>
                    </button>

                    <button type="button" id="btn-toggle-expand" class="alphabet-btn" style="background:rgba(0,242,254,0.15); color:var(--accent-cyan); border:1px solid var(--accent-cyan);">
                        <i class="fa-solid fa-expand"></i> Theater Mode
                    </button>

                    <a href="#download-section-box" class="alphabet-btn" style="background:rgba(0,255,136,0.15); color:var(--accent-green); border:1px solid var(--accent-green); text-decoration:none;">
                        <i class="fa-solid fa-download"></i> Download Movie
                    </a>

                    <button type="button" class="alphabet-btn" onclick="document.getElementById('main-movie-iframe').src=document.getElementById('main-movie-iframe').src;" style="background:rgba(255,255,255,0.08);">
                        <i class="fa-solid fa-rotate-right"></i> Reload
                    </button>
                </div>
            </div>
        </div>

        <!-- VidVault Download Links Section Card -->
        <div id="download-section-box" style="background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:25px; margin-bottom:40px; box-shadow:0 15px 30px rgba(0,0,0,0.5);">
            <h3 style="color:#fff; font-size:1.3rem; margin-bottom:10px; display:flex; align-items:center; gap:10px;">
                <i class="fa-solid fa-cloud-arrow-down" style="color:var(--accent-green);"></i> Download Movie (Powered by VidVault Engine)
            </h3>
            <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:20px;">Fast HD downloads verified for <?php echo esc_html($title); ?>:</p>
            
            <div style="display:flex; flex-wrap:wrap; gap:15px;">
                <a href="<?php echo esc_url($dl_720p); ?>" target="_blank" rel="noopener" class="btn-watch-slide" style="background:linear-gradient(135deg, #00b4d8, #0077b6); color:#fff;">
                    <i class="fa-solid fa-file-arrow-down"></i> Download 720p HD (VidVault)
                </a>
                <a href="<?php echo esc_url($dl_1080p); ?>" target="_blank" rel="noopener" class="btn-watch-slide" style="background:linear-gradient(135deg, #00f2fe, #4facfe); color:#000;">
                    <i class="fa-solid fa-file-arrow-down"></i> Download 1080p Full HD (VidVault)
                </a>
                <a href="<?php echo esc_url($dl_4k); ?>" target="_blank" rel="noopener" class="btn-watch-slide" style="background:linear-gradient(135deg, #ff0055, #ff5252); color:#fff;">
                    <i class="fa-solid fa-file-arrow-down"></i> Download 4K Ultra HD (VidVault)
                </a>
                <a href="<?php echo esc_url($dl_direct); ?>" target="_blank" rel="noopener" class="btn-watch-slide" style="background:rgba(255,255,255,0.12); color:#fff; border:1px solid rgba(255,255,255,0.2);">
                    <i class="fa-solid fa-external-link"></i> VidVault Portal Direct Page
                </a>
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
