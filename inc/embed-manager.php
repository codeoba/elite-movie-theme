<?php
/**
 * MovieElite Pro - Embed Source Domain Manager
 * Provides custom admin controls to view, add, edit, or update embed player domain patterns.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get default embed servers list
 *
 * @return array Embed servers configuration
 */
function movie_elite_get_embed_servers() {
    $custom_servers = get_option('movie_elite_embed_servers', array());

    if (empty($custom_servers)) {
        $custom_servers = array(
            'server_1' => array(
                'id'       => 'server_1',
                'name'     => 'Server 1 (VidSrc Pro)',
                'pattern'  => 'https://vidsrc.to/embed/movie/{imdb_id}',
                'type'     => 'imdb',
                'status'   => 'active'
            ),
            'server_2' => array(
                'id'       => 'server_2',
                'name'     => 'Server 2 (SuperEmbed Stream)',
                'pattern'  => 'https://www.superembed.stream/directstream.php?video_id={imdb_id}',
                'type'     => 'imdb',
                'status'   => 'active'
            ),
            'server_3' => array(
                'id'       => 'server_3',
                'name'     => 'Server 3 (AutoEmbed Net)',
                'pattern'  => 'https://autoembed.net/embed/movie/{tmdb_id}',
                'type'     => 'tmdb',
                'status'   => 'active'
            ),
            'server_4' => array(
                'id'       => 'server_4',
                'name'     => 'Server 4 (2Embed Mirror)',
                'pattern'  => 'https://www.2embed.cc/embed/{imdb_id}',
                'type'     => 'imdb',
                'status'   => 'active'
            ),
            'server_5' => array(
                'id'       => 'server_5',
                'name'     => 'Server 5 (MovieAPI Fast)',
                'pattern'  => 'https://vidsrc.me/embed/movie?imdb={imdb_id}',
                'type'     => 'imdb',
                'status'   => 'active'
            )
        );
        update_option('movie_elite_embed_servers', $custom_servers);
    }

    return $custom_servers;
}

/**
 * Register Admin Menu for Embed Manager
 */
function movie_elite_embed_manager_menu() {
    add_submenu_page(
        'edit.php?post_type=movies',
        'Embed Server Manager',
        'Embed Source Manager',
        'manage_options',
        'movie-elite-embed-manager',
        'movie_elite_embed_manager_page_render'
    );
}
add_action('admin_menu', 'movie_elite_embed_manager_menu');

/**
 * Render Embed Source Manager Page
 */
function movie_elite_embed_manager_page_render() {
    if (isset($_POST['movie_elite_save_embeds']) && check_admin_referer('movie_elite_embed_nonce')) {
        $servers = movie_elite_get_embed_servers();

        if (isset($_POST['servers']) && is_array($_POST['servers'])) {
            foreach ($_POST['servers'] as $id => $data) {
                if (isset($servers[$id])) {
                    $servers[$id]['name']    = sanitize_text_field($data['name']);
                    $servers[$id]['pattern'] = esc_url_raw($data['pattern']);
                    $servers[$id]['type']    = sanitize_text_field($data['type']);
                    $servers[$id]['status']  = sanitize_text_field($data['status']);
                }
            }
        }

        // Check if adding new server
        if (!empty($_POST['new_server_name']) && !empty($_POST['new_server_pattern'])) {
            $new_id = 'server_' . (count($servers) + 1) . '_' . time();
            $servers[$new_id] = array(
                'id'       => $new_id,
                'name'     => sanitize_text_field($_POST['new_server_name']),
                'pattern'  => esc_url_raw($_POST['new_server_pattern']),
                'type'     => sanitize_text_field($_POST['new_server_type'] ?? 'imdb'),
                'status'   => 'active'
            );
        }

        update_option('movie_elite_embed_servers', $servers);
        echo '<div class="updated"><p>Embed player server domains updated successfully!</p></div>';
    }

    // Delete action
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['server_id']) && check_admin_referer('delete_server_' . $_GET['server_id'])) {
        $servers = movie_elite_get_embed_servers();
        unset($servers[$_GET['server_id']]);
        update_option('movie_elite_embed_servers', $servers);
        echo '<div class="updated"><p>Server deleted successfully!</p></div>';
    }

    $servers = movie_elite_get_embed_servers();
    ?>
    <div class="wrap">
        <h1 style="display:flex; align-items:center; gap:10px;">
            <span class="dashicons dashicons-video-alt3" style="font-size:32px; color:#00f2fe;"></span>
            Movie Player Embed Source Manager
        </h1>
        <p>Manage, edit, add, or update embed player domain patterns for all movies. (Updated with official <strong>https://www.superembed.stream/</strong> and <strong>https://autoembed.net/</strong> domains).</p>
        <hr />

        <form method="post" action="">
            <?php wp_nonce_field('movie_elite_embed_nonce'); ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width:180px;">Server Name</th>
                        <th>URL Pattern (Use {imdb_id} or {tmdb_id})</th>
                        <th style="width:120px;">ID Type</th>
                        <th style="width:120px;">Status</th>
                        <th style="width:100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($servers as $id => $srv) : ?>
                    <tr>
                        <td>
                            <input type="text" name="servers[<?php echo esc_attr($id); ?>][name]" value="<?php echo esc_attr($srv['name']); ?>" class="widefat" />
                        </td>
                        <td>
                            <input type="text" name="servers[<?php echo esc_attr($id); ?>][pattern]" value="<?php echo esc_attr($srv['pattern']); ?>" class="widefat code" />
                        </td>
                        <td>
                            <select name="servers[<?php echo esc_attr($id); ?>][type]">
                                <option value="imdb" <?php selected($srv['type'], 'imdb'); ?>>IMDb ID</option>
                                <option value="tmdb" <?php selected($srv['type'], 'tmdb'); ?>>TMDb ID</option>
                            </select>
                        </td>
                        <td>
                            <select name="servers[<?php echo esc_attr($id); ?>][status]">
                                <option value="active" <?php selected($srv['status'], 'active'); ?>>Active</option>
                                <option value="disabled" <?php selected($srv['status'], 'disabled'); ?>>Disabled</option>
                            </select>
                        </td>
                        <td>
                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('edit.php?post_type=movies&page=movie-elite-embed-manager&action=delete&server_id=' . $id), 'delete_server_' . $id)); ?>" class="button button-link-delete" onclick="return confirm('Are you sure you want to delete this server?');">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2 style="margin-top:30px;">Add New Embed Server Provider</h2>
            <div style="background:#fff; padding:20px; border-radius:8px; border:1px solid #ccd0d4; max-width:700px;">
                <p>
                    <label><strong>Server Name:</strong></label><br />
                    <input type="text" name="new_server_name" class="widefat" placeholder="e.g. Server 6 (SuperEmbed VIP Stream)" />
                </p>
                <p>
                    <label><strong>URL Pattern:</strong></label><br />
                    <input type="text" name="new_server_pattern" class="widefat code" placeholder="https://www.superembed.stream/directstream.php?video_id={imdb_id}" />
                    <span class="description">Must include <code>{imdb_id}</code> or <code>{tmdb_id}</code> placeholder.</span>
                </p>
                <p>
                    <label><strong>ID Type:</strong></label><br />
                    <select name="new_server_type">
                        <option value="imdb">IMDb ID (tt1234567)</option>
                        <option value="tmdb">TMDb ID (12345)</option>
                    </select>
                </p>
            </div>

            <p style="margin-top:20px;">
                <input type="submit" name="movie_elite_save_embeds" class="button button-primary button-large" value="Save Embed Server Settings" />
            </p>
        </form>
    </div>
    <?php
}
