<?php
/**
 * MovieElite Pro - Homepage Block Manager & Custom Rules Engine
 * Provides custom admin controls to toggle blocks ON/OFF and build custom category/genre/year/country blocks.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get Default Homepage Block Configurations
 *
 * @return array Block configuration settings
 */
function movie_elite_get_blocks_config() {
    $defaults = array(
        'recommended' => array('id' => 'recommended', 'name' => 'Recommended Movies', 'status' => 'on', 'rule' => 'category', 'value' => 'recommended', 'icon' => 'fa-fire'),
        'action'      => array('id' => 'action',      'name' => 'Action Movies',      'status' => 'on', 'rule' => 'genre',    'value' => 'action',      'icon' => 'fa-gun'),
        'romance'     => array('id' => 'romance',     'name' => 'Romance Movies',     'status' => 'on', 'rule' => 'genre',    'value' => 'romance',     'icon' => 'fa-heart'),
        'korean'      => array('id' => 'korean',      'name' => 'Korean Movies',      'status' => 'on', 'rule' => 'country',  'value' => 'south-korea', 'icon' => 'fa-film'),
        'chinese'     => array('id' => 'chinese',     'name' => 'Chinese Movies',     'status' => 'on', 'rule' => 'country',  'value' => 'china',       'icon' => 'fa-dragon'),
        'tvshows'     => array('id' => 'tvshows',     'name' => 'TV Shows & Series',  'status' => 'on', 'rule' => 'category', 'value' => 'tv-shows',    'icon' => 'fa-tv'),
        'asiandrama'  => array('id' => 'asiandrama',  'name' => 'Asian Dramas',       'status' => 'on', 'rule' => 'category', 'value' => 'asian-drama', 'icon' => 'fa-clapperboard'),
        'custom_1'    => array('id' => 'custom_1',    'name' => 'Custom Block 1',     'status' => 'off','rule' => 'genre',    'value' => 'sci-fi',      'icon' => 'fa-cube'),
        'custom_2'    => array('id' => 'custom_2',    'name' => 'Custom Block 2',     'status' => 'off','rule' => 'year',     'value' => '2026',        'icon' => 'fa-star'),
    );

    $saved = get_option('movie_elite_blocks_config', array());
    return wp_parse_args($saved, $defaults);
}

/**
 * Register Admin Menu for Block Manager
 */
function movie_elite_block_manager_menu() {
    add_submenu_page(
        'edit.php?post_type=movies',
        'Homepage Block Manager',
        'Homepage Block Manager',
        'manage_options',
        'movie-elite-block-manager',
        'movie_elite_block_manager_page_render'
    );
}
add_action('admin_menu', 'movie_elite_block_manager_menu');

/**
 * Render Block Manager Admin Page
 */
function movie_elite_block_manager_page_render() {
    if (isset($_POST['movie_elite_save_blocks']) && check_admin_referer('movie_elite_blocks_nonce')) {
        $blocks = movie_elite_get_blocks_config();

        if (isset($_POST['blocks']) && is_array($_POST['blocks'])) {
            foreach ($_POST['blocks'] as $id => $data) {
                if (isset($blocks[$id])) {
                    $blocks[$id]['name']   = sanitize_text_field($data['name']);
                    $blocks[$id]['status'] = sanitize_text_field($data['status']);
                    $blocks[$id]['rule']   = sanitize_text_field($data['rule']);
                    $blocks[$id]['value']  = sanitize_text_field($data['value']);
                    $blocks[$id]['icon']   = sanitize_text_field($data['icon'] ?? 'fa-film');
                }
            }
        }

        update_option('movie_elite_blocks_config', $blocks);
        echo '<div class="updated"><p>Homepage block layout settings updated successfully!</p></div>';
    }

    $blocks = movie_elite_get_blocks_config();
    ?>
    <div class="wrap">
        <h1 style="display:flex; align-items:center; gap:10px;">
            <span class="dashicons dashicons-layout" style="font-size:32px; color:#00f2fe;"></span>
            Homepage Block Manager & Custom Rules Builder
        </h1>
        <p>Switch ON or OFF any homepage category block, or create custom blocks filtered by Category, Genre, Year, or Country rules.</p>
        <hr />

        <form method="post" action="">
            <?php wp_nonce_field('movie_elite_blocks_nonce'); ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width:160px;">Block Title</th>
                        <th style="width:100px;">Status</th>
                        <th style="width:140px;">Filter Rule</th>
                        <th>Target Value (Slug / Year)</th>
                        <th style="width:120px;">Icon Class</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($blocks as $id => $blk) : ?>
                    <tr>
                        <td>
                            <strong><input type="text" name="blocks[<?php echo esc_attr($id); ?>][name]" value="<?php echo esc_attr($blk['name']); ?>" class="widefat" /></strong>
                        </td>
                        <td>
                            <select name="blocks[<?php echo esc_attr($id); ?>][status]">
                                <option value="on" <?php selected($blk['status'], 'on'); ?>>🟢 ON</option>
                                <option value="off" <?php selected($blk['status'], 'off'); ?>>🔴 OFF</option>
                            </select>
                        </td>
                        <td>
                            <select name="blocks[<?php echo esc_attr($id); ?>][rule]">
                                <option value="category" <?php selected($blk['rule'], 'category'); ?>>Category</option>
                                <option value="genre" <?php selected($blk['rule'], 'genre'); ?>>Genre</option>
                                <option value="country" <?php selected($blk['rule'], 'country'); ?>>Country</option>
                                <option value="year" <?php selected($blk['rule'], 'year'); ?>>Year</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" name="blocks[<?php echo esc_attr($id); ?>][value]" value="<?php echo esc_attr($blk['value']); ?>" class="widefat" placeholder="e.g. recommended, action, south-korea, 2026" />
                        </td>
                        <td>
                            <input type="text" name="blocks[<?php echo esc_attr($id); ?>][icon]" value="<?php echo esc_attr($blk['icon'] ?? 'fa-film'); ?>" class="widefat" />
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p style="margin-top:20px;">
                <input type="submit" name="movie_elite_save_blocks" class="button button-primary button-large" value="Save Homepage Block Layout" />
            </p>
        </form>
    </div>
    <?php
}
