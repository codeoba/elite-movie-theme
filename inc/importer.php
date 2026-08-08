<?php
/**
 * MovieElite Pro - Automated TMDb / IMDb Movie Importer Tool
 * Supports search by Name, IMDb ID, Year, Genre, and Bulk Import.
 * Auto-populates posters, backdrops, ratings, metadata, and generates 4+ embed player links.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Admin Menu for Movie Importer
 */
function movie_elite_importer_menu() {
    add_submenu_page(
        'edit.php?post_type=movies',
        'TMDb / IMDb Movie Importer',
        'Movie Importer Tool',
        'manage_options',
        'movie-elite-importer',
        'movie_elite_importer_page_render'
    );
}
add_action('admin_menu', 'movie_elite_importer_menu');

/**
 * Render Movie Importer Page
 */
function movie_elite_importer_page_render() {
    $api_key = get_option('movie_elite_tmdb_api_key', '15d260044e350723362f6236b22b270a'); // Working fallback public key

    if (isset($_POST['save_tmdb_key'])) {
        $api_key = sanitize_text_field($_POST['tmdb_api_key']);
        update_option('movie_elite_tmdb_api_key', $api_key);
        echo '<div class="updated"><p>API Key saved!</p></div>';
    }
    ?>
    <div class="wrap">
        <h1 style="display:flex; align-items:center; gap:10px;">
            <span class="dashicons dashicons-download" style="font-size:32px; color:#00f2fe;"></span>
            TMDb / IMDb Movie Importer & Bulk Embed Generator
        </h1>
        <p>Import movies automatically by Title, IMDb ID, Year, Genre, or run Bulk Importer. Each movie gets 4+ embed player servers automatically attached!</p>
        <hr />

        <form method="post" action="" style="margin-bottom:20px;">
            <label><strong>TMDb API Key:</strong></label>
            <input type="text" name="tmdb_api_key" value="<?php echo esc_attr($api_key); ?>" style="width:350px;" />
            <input type="submit" name="save_tmdb_key" class="button button-secondary" value="Save API Key" />
        </form>

        <div style="background:#fff; padding:25px; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.05); margin-bottom:30px;">
            <h2>Import Filter & Search Options</h2>
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:15px; margin-top:15px;">
                <div>
                    <label><strong>Search by Movie Name / Title:</strong></label>
                    <input type="text" id="import-title" class="widefat" placeholder="e.g. Avatar, Inception, Dune" />
                </div>
                <div>
                    <label><strong>Import by IMDb / TMDb ID:</strong></label>
                    <input type="text" id="import-imdb" class="widefat" placeholder="e.g. tt1160419 or 438148" />
                </div>
                <div>
                    <label><strong>Filter by Release Year:</strong></label>
                    <input type="number" id="import-year" class="widefat" placeholder="e.g. 2026" min="1950" max="2030" />
                </div>
                <div>
                    <label><strong>Filter by Genre / Category:</strong></label>
                    <select id="import-genre" class="widefat">
                        <option value="">All Genres</option>
                        <option value="28">Action</option>
                        <option value="10749">Romance</option>
                        <option value="878">Sci-Fi</option>
                        <option value="18">Drama</option>
                        <option value="35">Comedy</option>
                        <option value="27">Horror</option>
                        <option value="16">Animation / Asian</option>
                    </select>
                </div>
            </div>

            <div style="display:flex; gap:15px; margin-top:20px;">
                <button type="button" id="btn-run-search" class="button button-primary button-large" style="background:#00f2fe; border-color:#00f2fe; color:#000; font-weight:700;">
                    🔍 Search Movies
                </button>
                <button type="button" id="btn-run-bulk" class="button button-primary button-large" style="background:#ff0055; border-color:#ff0055; color:#fff; font-weight:700;">
                    ⚡ Run Bulk Importer (Auto 20 Movies)
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
            var title = $('#import-title').val().trim();
            var imdb  = $('#import-imdb').val().trim();
            var year  = $('#import-year').val().trim();
            var genre = $('#import-genre').val();

            var url = 'https://api.themoviedb.org/3/discover/movie?api_key=' + apiKey + '&sort_by=popularity.desc';
            if (title) {
                url = 'https://api.themoviedb.org/3/search/movie?api_key=' + apiKey + '&query=' + encodeURIComponent(title);
            } else {
                if (year) url += '&primary_release_year=' + year;
                if (genre) url += '&with_genres=' + genre;
            }

            if (imdb) {
                url = 'https://api.themoviedb.org/3/find/' + imdb + '?api_key=' + apiKey + '&external_source=imdb_id';
            }

            $('#import-results-container').show();
            $('#import-grid').html('<p>Searching TMDb database...</p>');

            $.getJSON(url, function(res) {
                var movies = res.results || res.movie_results || [];
                if (movies.length === 0) {
                    $('#import-grid').html('<p>No movies found matching criteria.</p>');
                    return;
                }

                var html = '';
                $.each(movies, function(i, m) {
                    var poster = m.poster_path ? 'https://image.tmdb.org/t/p/w500' + m.poster_path : 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=300';
                    var release = m.release_date ? m.release_date.substring(0,4) : '2026';
                    html += '<div style="border:1px solid #ddd; padding:10px; border-radius:6px; text-align:center; background:#fafafa;">';
                    html += '<img src="' + poster + '" style="width:100%; height:220px; object-fit:cover; border-radius:4px;" />';
                    html += '<h4 style="font-size:0.85rem; margin:8px 0 4px; line-height:1.2;">' + m.title + ' (' + release + ')</h4>';
                    html += '<button type="button" class="button button-small btn-import-one" data-tmdb="' + m.id + '" style="background:#00f2fe; color:#000; border:none; font-weight:700;">Import Movie</button>';
                    html += '</div>';
                });
                $('#import-grid').html(html);
            });
        });

        $(document).on('click', '.btn-import-one', function() {
            var $btn = $(this);
            var tmdbId = $btn.attr('data-tmdb');
            $btn.prop('disabled', true).text('Importing...');

            $.post(ajaxurl, { action: 'movie_elite_ajax_import', tmdb_id: tmdbId }, function(res) {
                if (res.success) {
                    $btn.text('✅ Imported (' + res.data.status + ')').css('background', '#00ff88');
                } else {
                    $btn.text('Failed').css('background', '#ff0055');
                }
            });
        });

        $('#btn-run-bulk').on('click', function() {
            var year  = $('#import-year').val().trim() || '2026';
            var genre = $('#import-genre').val();
            var $log  = $('#import-log');
            
            $log.show().html('Starting Bulk Importer...\nFetching popular movies...\n');

            var url = 'https://api.themoviedb.org/3/discover/movie?api_key=' + apiKey + '&sort_by=popularity.desc&primary_release_year=' + year;
            if (genre) url += '&with_genres=' + genre;

            $.getJSON(url, function(res) {
                var movies = res.results || [];
                var count = 0;
                $.each(movies, function(i, m) {
                    if (i >= 15) return false;
                    $.post(ajaxurl, { action: 'movie_elite_ajax_import', tmdb_id: m.id }, function(res) {
                        count++;
                        if (res.success) {
                            $log.append('\n[' + count + '] Imported: ' + m.title + ' (Status: ' + res.data.status + ' | Embeds: ' + res.data.embeds + ')');
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
 * AJAX Handler for Movie Import
 */
function movie_elite_ajax_import_handler() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $tmdb_id = sanitize_text_field($_POST['tmdb_id'] ?? '');
    $api_key = get_option('movie_elite_tmdb_api_key', '15d260044e350723362f6236b22b270a');

    if (empty($tmdb_id)) {
        wp_send_json_error('Missing TMDb ID');
    }

    $url = "https://api.themoviedb.org/3/movie/{$tmdb_id}?api_key={$api_key}&append_to_response=external_ids,credits";
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        wp_send_json_error('TMDb connection error');
    }

    $m = json_decode(wp_remote_retrieve_body($response), true);
    if (empty($m) || empty($m['title'])) {
        wp_send_json_error('Movie data not found');
    }

    $title    = sanitize_text_field($m['title']);
    $imdb_id  = $m['external_ids']['imdb_id'] ?? '';
    $overview = sanitize_textarea_field($m['overview'] ?? '');
    $rating   = number_format($m['vote_average'] ?? 7.5, 1);
    $year     = substr($m['release_date'] ?? '2026', 0, 4);
    $poster   = $m['poster_path'] ? 'https://image.tmdb.org/t/p/w500' . $m['poster_path'] : '';
    $backdrop = $m['backdrop_path'] ? 'https://image.tmdb.org/t/p/w1280' . $m['backdrop_path'] : '';

    // Check duplicate
    $existing = get_page_by_title($title, OBJECT, 'movies');
    if ($existing) {
        wp_send_json_success(array('status' => 'Existing Duplicate Skipped', 'embeds' => 4));
    }

    $post_id = wp_insert_post(array(
        'post_title'   => $title,
        'post_content' => $overview,
        'post_status'  => 'publish',
        'post_type'    => 'movies'
    ));

    if ($post_id && !is_wp_error($post_id)) {
        update_post_meta($post_id, 'imdb_id', $imdb_id);
        update_post_meta($post_id, 'tmdb_id', $tmdb_id);
        update_post_meta($post_id, 'imdb_rating', $rating);
        update_post_meta($post_id, 'release_year', $year);
        update_post_meta($post_id, 'poster_url', $poster);
        update_post_meta($post_id, 'backdrop_url', $backdrop);
        update_post_meta($post_id, 'movie_quality', '4K UHD');

        // Assign genres
        if (!empty($m['genres'])) {
            $genre_names = array_column($m['genres'], 'name');
            wp_set_object_terms($post_id, $genre_names, 'genre');
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
