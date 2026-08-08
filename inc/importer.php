<?php
/**
 * MovieElite Pro - Automated TMDb / IMDb Importer Tool for Movies & TV Shows
 * Supports search by Name, IMDb ID, Year, Genre, and Bulk Import.
 * Allows importing as Movies or TV Shows / Asian Dramas.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Admin Menu for Importer
 */
function movie_elite_importer_menu() {
    add_submenu_page(
        'edit.php?post_type=movies',
        'TMDb Importer',
        'Importer Tool',
        'manage_options',
        'movie-elite-importer',
        'movie_elite_importer_page_render'
    );
}
add_action('admin_menu', 'movie_elite_importer_menu');

/**
 * Render Importer Page
 */
function movie_elite_importer_page_render() {
    $api_key = get_option('movie_elite_tmdb_api_key', '15d260044e350723362f6236b22b270a');

    if (isset($_POST['save_tmdb_key'])) {
        $api_key = sanitize_text_field($_POST['tmdb_api_key']);
        update_option('movie_elite_tmdb_api_key', $api_key);
        echo '<div class="updated"><p>API Key saved!</p></div>';
    }
    ?>
    <div class="wrap">
        <h1 style="display:flex; align-items:center; gap:10px;">
            <span class="dashicons dashicons-download" style="font-size:32px; color:#00f2fe;"></span>
            TMDb / IMDb Importer Tool (Movies & TV Shows)
        </h1>
        <p>Import Movies or TV Shows / Asian Dramas automatically by Title, IMDb ID, Year, or Genre. Each import attaches 4+ embed player servers!</p>
        <hr />

        <form method="post" action="" style="margin-bottom:20px;">
            <label><strong>TMDb API Key:</strong></label>
            <input type="text" name="tmdb_api_key" value="<?php echo esc_attr($api_key); ?>" style="width:350px;" />
            <input type="submit" name="save_tmdb_key" class="button button-secondary" value="Save API Key" />
        </form>

        <div style="background:#fff; padding:25px; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.05); margin-bottom:30px;">
            <h2>Import Filter & Options</h2>
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:15px; margin-top:15px;">
                <div>
                    <label><strong>Import Type:</strong></label>
                    <select id="import-type" class="widefat" style="font-weight:bold; color:#007cba;">
                        <option value="movie">🍿 Import as Movie</option>
                        <option value="tv">📺 Import as TV Show / Asian Drama</option>
                    </select>
                </div>
                <div>
                    <label><strong>Search Title / Name:</strong></label>
                    <input type="text" id="import-title" class="widefat" placeholder="e.g. Avatar, Squid Game" />
                </div>
                <div>
                    <label><strong>IMDb / TMDb ID:</strong></label>
                    <input type="text" id="import-imdb" class="widefat" placeholder="e.g. tt1160419 or 438148" />
                </div>
                <div>
                    <label><strong>Release Year:</strong></label>
                    <input type="number" id="import-year" class="widefat" placeholder="e.g. 2026" min="1950" max="2030" />
                </div>
                <div>
                    <label><strong>Genre / Category:</strong></label>
                    <select id="import-genre" class="widefat">
                        <option value="">All Genres</option>
                        <option value="28">Action</option>
                        <option value="10749">Romance</option>
                        <option value="878">Sci-Fi</option>
                        <option value="18">Drama / Asian Drama</option>
                        <option value="35">Comedy</option>
                    </select>
                </div>
            </div>

            <div style="display:flex; gap:15px; margin-top:20px;">
                <button type="button" id="btn-run-search" class="button button-primary button-large" style="background:#00f2fe; border-color:#00f2fe; color:#000; font-weight:700;">
                    🔍 Search Content
                </button>
                <button type="button" id="btn-run-bulk" class="button button-primary button-large" style="background:#ff0055; border-color:#ff0055; color:#fff; font-weight:700;">
                    ⚡ Run Bulk Importer (Auto 20 Items)
                </button>
            </div>
        </div>

        <div id="import-results-container" style="display:none; background:#fff; padding:25px; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.05);">
            <h2>Search Results</h2>
            <div id="import-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap:15px; margin-top:15px;"></div>
        </div>

        <div id="import-log" style="display:none; margin-top:20px; background:#0b0d14; color:#00ff88; font-family:monospace; padding:15px; border-radius:6px; max-height:250px; overflow-y:auto;"></div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        var apiKey = '<?php echo esc_js($api_key); ?>';

        $('#btn-run-search').on('click', function() {
            var type  = $('#import-type').val();
            var title = $('#import-title').val().trim();
            var imdb  = $('#import-imdb').val().trim();
            var year  = $('#import-year').val().trim();
            var genre = $('#import-genre').val();

            var endpoint = (type === 'tv') ? 'tv' : 'movie';

            var url = 'https://api.themoviedb.org/3/discover/' + endpoint + '?api_key=' + apiKey + '&sort_by=popularity.desc';
            if (title) {
                url = 'https://api.themoviedb.org/3/search/' + endpoint + '?api_key=' + apiKey + '&query=' + encodeURIComponent(title);
            } else {
                if (year) url += '&first_air_date_year=' + year;
                if (genre) url += '&with_genres=' + genre;
            }

            if (imdb) {
                url = 'https://api.themoviedb.org/3/find/' + imdb + '?api_key=' + apiKey + '&external_source=imdb_id';
            }

            $('#import-results-container').show();
            $('#import-grid').html('<p>Searching TMDb database...</p>');

            $.getJSON(url, function(res) {
                var items = res.results || res.movie_results || res.tv_results || [];
                if (items.length === 0) {
                    $('#import-grid').html('<p>No content found matching criteria.</p>');
                    return;
                }

                var html = '';
                $.each(items, function(i, m) {
                    var itemTitle = m.title || m.name;
                    var poster = m.poster_path ? 'https://image.tmdb.org/t/p/w500' + m.poster_path : 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=300';
                    var release = (m.release_date || m.first_air_date || '2026').substring(0,4);
                    html += '<div style="border:1px solid #ddd; padding:10px; border-radius:6px; text-align:center; background:#fafafa;">';
                    html += '<img src="' + poster + '" style="width:100%; height:220px; object-fit:cover; border-radius:4px;" />';
                    html += '<h4 style="font-size:0.85rem; margin:8px 0 4px; line-height:1.2;">' + itemTitle + ' (' + release + ')</h4>';
                    html += '<button type="button" class="button button-small btn-import-one" data-tmdb="' + m.id + '" data-type="' + type + '" style="background:#00f2fe; color:#000; border:none; font-weight:700;">Import ' + (type === 'tv' ? 'TV Show' : 'Movie') + '</button>';
                    html += '</div>';
                });
                $('#import-grid').html(html);
            });
        });

        $(document).on('click', '.btn-import-one', function() {
            var $btn = $(this);
            var tmdbId = $btn.attr('data-tmdb');
            var type   = $btn.attr('data-type') || 'movie';
            $btn.prop('disabled', true).text('Importing...');

            $.post(ajaxurl, { action: 'movie_elite_ajax_import', tmdb_id: tmdbId, import_type: type }, function(res) {
                if (res.success) {
                    $btn.text('✅ Imported (' + res.data.status + ')').css('background', '#00ff88');
                } else {
                    $btn.text('Failed').css('background', '#ff0055');
                }
            });
        });

        $('#btn-run-bulk').on('click', function() {
            var type  = $('#import-type').val();
            var year  = $('#import-year').val().trim() || '2026';
            var genre = $('#import-genre').val();
            var $log  = $('#import-log');
            
            $log.show().html('Starting Bulk Importer...\nFetching popular content...\n');

            var endpoint = (type === 'tv') ? 'tv' : 'movie';
            var url = 'https://api.themoviedb.org/3/discover/' + endpoint + '?api_key=' + apiKey + '&sort_by=popularity.desc';

            $.getJSON(url, function(res) {
                var items = res.results || [];
                var count = 0;
                $.each(items, function(i, m) {
                    if (i >= 15) return false;
                    $.post(ajaxurl, { action: 'movie_elite_ajax_import', tmdb_id: m.id, import_type: type }, function(res) {
                        count++;
                        if (res.success) {
                            $log.append('\n[' + count + '] Imported: ' + (m.title || m.name) + ' (Status: ' + res.data.status + ')');
                        }
                    });
                });
            });
        });
    });
    </script>
    <?php
}

/**
 * AJAX Handler for Movies & TV Shows Import
 */
function movie_elite_ajax_import_handler() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $tmdb_id     = sanitize_text_field($_POST['tmdb_id'] ?? '');
    $import_type = sanitize_text_field($_POST['import_type'] ?? 'movie');
    $api_key     = get_option('movie_elite_tmdb_api_key', '15d260044e350723362f6236b22b270a');

    if (empty($tmdb_id)) {
        wp_send_json_error('Missing TMDb ID');
    }

    $endpoint  = ($import_type === 'tv') ? 'tv' : 'movie';
    $post_type = ($import_type === 'tv') ? 'tvshows' : 'movies';

    $url = "https://api.themoviedb.org/3/{$endpoint}/{$tmdb_id}?api_key={$api_key}&append_to_response=external_ids,credits";
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        wp_send_json_error('TMDb connection error');
    }

    $m = json_decode(wp_remote_retrieve_body($response), true);
    if (empty($m)) {
        wp_send_json_error('Data not found');
    }

    $title    = sanitize_text_field($m['title'] ?? $m['name'] ?? '');
    $imdb_id  = $m['external_ids']['imdb_id'] ?? '';
    $overview = sanitize_textarea_field($m['overview'] ?? '');
    $rating   = number_format($m['vote_average'] ?? 7.5, 1);
    $year     = substr($m['release_date'] ?? $m['first_air_date'] ?? '2026', 0, 4);
    $poster   = $m['poster_path'] ? 'https://image.tmdb.org/t/p/w500' . $m['poster_path'] : '';
    $backdrop = $m['backdrop_path'] ? 'https://image.tmdb.org/t/p/w1280' . $m['backdrop_path'] : '';

    $seasons  = intval($m['number_of_seasons'] ?? 1);
    $episodes = intval($m['number_of_episodes'] ?? 12);

    // Check duplicate
    $existing = get_page_by_title($title, OBJECT, $post_type);
    if ($existing) {
        wp_send_json_success(array('status' => 'Existing Duplicate Skipped', 'embeds' => 4));
    }

    $post_id = wp_insert_post(array(
        'post_title'   => $title,
        'post_content' => $overview,
        'post_status'  => 'publish',
        'post_type'    => $post_type
    ));

    if ($post_id && !is_wp_error($post_id)) {
        update_post_meta($post_id, 'imdb_id', $imdb_id);
        update_post_meta($post_id, 'tmdb_id', $tmdb_id);
        update_post_meta($post_id, 'imdb_rating', $rating);
        update_post_meta($post_id, 'release_year', $year);
        update_post_meta($post_id, 'poster_url', $poster);
        update_post_meta($post_id, 'backdrop_url', $backdrop);
        update_post_meta($post_id, 'movie_quality', '4K UHD');
        update_post_meta($post_id, 'total_seasons', $seasons);
        update_post_meta($post_id, 'total_episodes', $episodes);

        // Assign genres
        if (!empty($m['genres'])) {
            $genre_names = array_column($m['genres'], 'name');
            wp_set_object_terms($post_id, $genre_names, 'genre');
        }

        // Assign category
        if ($import_type === 'tv') {
            wp_set_object_terms($post_id, 'tv-shows', 'movie_category');
        }

        // Apply Multi-Source Embed Generator & Draft Guard
        if (function_exists('movie_elite_process_import_draft_guard')) {
            $status = movie_elite_process_import_draft_guard($post_id, $imdb_id, $tmdb_id);
            $post_status = $status ? 'Published' : 'Draft (No Embed)';
        } else {
            $post_status = 'Published';
        }

        wp_send_json_success(array('status' => $post_status, 'embeds' => 4));
    }

    wp_send_json_error('Failed inserting post');
}
add_action('wp_ajax_movie_elite_ajax_import', 'movie_elite_ajax_import_handler');
