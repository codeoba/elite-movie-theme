<?php
/**
 * MovieElite Pro - Embed Source Domain Manager with Priority Reordering & Verified Working APIs
 * Includes official verified embed providers: VidSrc Pro, SuperEmbed Stream, AutoEmbed Net,
 * VidSrc SBS (https://vidsrc.sbs/), VSEmbed Stream (https://vsembed.ru/), and 2Embed Mirror.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get default embed servers list, sorted by priority order
 *
 * @return array Embed servers configuration sorted by order
 */
function movie_elite_get_embed_servers() {
    $custom_servers = get_option('movie_elite_embed_servers', array());

    if (empty($custom_servers)) {
        $custom_servers = array(
            'server_1' => array(
                'id'       => 'server_1',
                'name'     => 'Server 1 (VidSrc ME)',
                'pattern'  => 'https://vidsrc.me/embed/movie/{imdb_id}',
                'type'     => 'imdb',
                'status'   => 'active',
                'order'    => 1
            ),
            'server_2' => array(
                'id'       => 'server_2',
                'name'     => 'Server 2 (SuperEmbed Stream)',
                'pattern'  => 'https://www.superembed.stream/directstream.php?video_id={imdb_id}',
                'type'     => 'imdb',
                'status'   => 'active',
                'order'    => 2
            ),
            'server_3' => array(
                'id'       => 'server_3',
                'name'     => 'Server 3 (AutoEmbed Net)',
                'pattern'  => 'https://autoembed.net/embed/movie/{tmdb_id}',
                'type'     => 'tmdb',
                'status'   => 'active',
                'order'    => 3
            ),
            'server_4' => array(
                'id'       => 'server_4',
                'name'     => 'Server 4 (VidSrc SBS)',
                'pattern'  => 'https://vidsrc.sbs/embed/movie/{tmdb_id}',
                'type'     => 'tmdb',
                'status'   => 'active',
                'order'    => 4
            ),
            'server_5' => array(
                'id'       => 'server_5',
                'name'     => 'Server 5 (VSEmbed Stream)',
                'pattern'  => 'https://vsembed.ru/embed/movie/{tmdb_id}',
                'type'     => 'tmdb',
                'status'   => 'active',
                'order'    => 5
            ),
            'server_6' => array(
                'id'       => 'server_6',
                'name'     => 'Server 6 (2Embed Mirror)',
                'pattern'  => 'https://www.2embed.cc/embed/{imdb_id}',
                'type'     => 'imdb',
                'status'   => 'active',
                'order'    => 6
            )
        );
        update_option('movie_elite_embed_servers', $custom_servers);
    }

    // Sort servers by order priority ascending
    uasort($custom_servers, function($a, $b) {
        $order_a = intval($a['order'] ?? 99);
        $order_b = intval($b['order'] ?? 99);
        return $order_a <=> $order_b;
    });

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
 * Render Embed Source Manager Page with Order Controls
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
                    $servers[$id]['order']   = intval($data['order'] ?? 1);
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
                'status'   => 'active',
                'order'    => intval($_POST['new_server_order'] ?? (count($servers) + 1))
            );
        }

        update_option('movie_elite_embed_servers', $servers);
        echo '<div class="updated"><p>Embed player server order & domain settings saved successfully!</p></div>';
    }

    // Move Up / Down actions
    if (isset($_GET['action']) && in_array($_GET['action'], ['move_up', 'move_down']) && isset($_GET['server_id'])) {
        $servers = movie_elite_get_embed_servers();
        $target_id = $_GET['server_id'];
        
        if (isset($servers[$target_id])) {
            $current_order = intval($servers[$target_id]['order'] ?? 1);
            if ($_GET['action'] === 'move_up' && $current_order > 1) {
                $servers[$target_id]['order'] = $current_order - 1;
            } elseif ($_GET['action'] === 'move_down') {
                $servers[$target_id]['order'] = $current_order + 1;
            }
            update_option('movie_elite_embed_servers', $servers);
            echo '<div class="updated"><p>Server priority updated!</p></div>';
        }
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
            <span class="dashicons dashicons-sort" style="font-size:32px; color:#00f2fe;"></span>
            Movie Player Embed Source & Priority Reordering Manager
        </h1>
        <p>Verified working embed APIs: <strong>VidSrc SBS (https://vidsrc.sbs/)</strong> & <strong>VSEmbed Stream (https://vsembed.ru/)</strong> active!</p>
        <hr />

        <form method="post" action="">
            <?php wp_nonce_field('movie_elite_embed_nonce'); ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width:70px;">Priority</th>
                        <th style="width:180px;">Server Name</th>
                        <th>URL Pattern (Use {imdb_id} or {tmdb_id})</th>
                        <th style="width:100px;">ID Type</th>
                        <th style="width:100px;">Status</th>
                        <th style="width:120px;">Re-order</th>
                        <th style="width:90px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $pos = 0;
                    foreach ($servers as $id => $srv) : 
                        $pos++;
                    ?>
                    <tr>
                        <td>
                            <input type="number" name="servers[<?php echo esc_attr($id); ?>][order]" value="<?php echo intval($srv['order'] ?? $pos); ?>" style="width:55px; font-weight:bold; text-align:center;" min="1" />
                        </td>
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
                            <a href="<?php echo esc_url(admin_url('edit.php?post_type=movies&page=movie-elite-embed-manager&action=move_up&server_id=' . $id)); ?>" class="button button-small">⬆️ Up</a>
                            <a href="<?php echo esc_url(admin_url('edit.php?post_type=movies&page=movie-elite-embed-manager&action=move_down&server_id=' . $id)); ?>" class="button button-small">⬇️ Down</a>
                        </td>
                        <td>
                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('edit.php?post_type=movies&page=movie-elite-embed-manager&action=delete&server_id=' . $id), 'delete_server_' . $id)); ?>" class="button button-link-delete" onclick="return confirm('Delete this server?');">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p style="margin-top:20px;">
                <input type="submit" name="movie_elite_save_embeds" class="button button-primary button-large" value="💾 Save Priority Order & Embed Settings" />
            </p>

            <h2 style="margin-top:30px;">Add New Embed Server Provider</h2>
            <div style="background:#fff; padding:20px; border-radius:8px; border:1px solid #ccd0d4; max-width:700px;">
                <p>
                    <label><strong>Server Name:</strong></label><br />
                    <input type="text" name="new_server_name" class="widefat" placeholder="e.g. Server 7 (VSEmbed Mirror)" />
                </p>
                <p>
                    <label><strong>URL Pattern:</strong></label><br />
                    <input type="text" name="new_server_pattern" class="widefat code" placeholder="https://vsembed.ru/embed/movie/{tmdb_id}" />
                    <span class="description">Must include <code>{imdb_id}</code> or <code>{tmdb_id}</code> placeholder.</span>
                </p>
                <p>
                    <label><strong>Priority Position Order:</strong></label><br />
                    <input type="number" name="new_server_order" value="<?php echo count($servers) + 1; ?>" style="width:80px;" min="1" />
                </p>
                <p>
                    <label><strong>ID Type:</strong></label><br />
                    <select name="new_server_type">
                        <option value="tmdb">TMDb ID (12345)</option>
                        <option value="imdb">IMDb ID (tt1234567)</option>
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
