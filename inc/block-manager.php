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
        'recommended' => array('id' => 'recommended', 'name' => 'Recommended Movies', 'status' => 'active', 'rule' => 'category',   'value' => 'recommended', 'icon' => 'fa-fire'),
        'movies'      => array('id' => 'movies',      'name' => 'Movies',             'status' => 'active', 'rule' => 'post_type', 'value' => 'movies',      'icon' => 'fa-clapperboard'),
        'action'      => array('id' => 'action',      'name' => 'Action Movies',      'status' => 'active', 'rule' => 'genre',      'value' => 'action',      'icon' => 'fa-gun'),
        'romance'     => array('id' => 'romance',     'name' => 'Romance Movies',     'status' => 'active', 'rule' => 'genre',      'value' => 'romance',     'icon' => 'fa-heart'),
        'korean'      => array('id' => 'korean',      'name' => 'Korean Movies',      'status' => 'active', 'rule' => 'country',    'value' => 'korea',       'icon' => 'fa-film'),
        'chinese'     => array('id' => 'chinese',     'name' => 'Chinese Movies',     'status' => 'active', 'rule' => 'country',    'value' => 'china',       'icon' => 'fa-dragon'),
        'tvshows'     => array('id' => 'tvshows',     'name' => 'TV Shows & Series',  'status' => 'active', 'rule' => 'category',   'value' => 'tvshows',     'icon' => 'fa-tv'),
        'asiandrama'  => array('id' => 'asiandrama',  'name' => 'Asian Dramas',       'status' => 'active', 'rule' => 'category',   'value' => 'asian-drama', 'icon' => 'fa-masks-theater'),
        'custom_1'    => array('id' => 'custom_1',    'name' => 'Custom Block 1',     'status' => 'off',    'rule' => 'genre',      'value' => 'sci-fi',      'icon' => 'fa-cube'),
        'custom_2'    => array('id' => 'custom_2',    'name' => 'Custom Block 2',     'status' => 'off',    'rule' => 'year',       'value' => '2026',        'icon' => 'fa-star'),
    );

    $saved = get_option('movie_elite_blocks_config', array());
    $result = wp_parse_args($saved, $defaults);

    // Normalize active / on status
    foreach ($result as &$blk) {
        if (($blk['status'] ?? '') === 'on') {
            $blk['status'] = 'active';
        }
    }
    unset($blk);

    return $result;
}

/**
 * Alias function for movie_elite_get_blocks_config
 */
function movie_elite_get_homepage_blocks() {
    return movie_elite_get_blocks_config();
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
                    $blocks[$id]['icon']   = sanitize_text_field($data['icon']);
                }
            }
            update_option('movie_elite_blocks_config', $blocks);
            echo '<div class="updated notice"><p>Homepage block layout settings saved successfully!</p></div>';
        }
    }

    $blocks = movie_elite_get_blocks_config();
?>
<div class="wrap">
    <h1><span class="dashicons dashicons-layout"></span> Homepage Block Layout Manager</h1>
    <p>Configure which section blocks appear on the main homepage, their display order, taxonomy filter rules, and active/inactive status.</p>

    <form method="post" action="">
        <?php wp_nonce_field('movie_elite_blocks_nonce'); ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 140px;">Block ID</th>
                    <th>Display Title</th>
                    <th style="width: 120px;">Status</th>
                    <th style="width: 140px;">Filter Rule</th>
                    <th>Filter Value / Slug</th>
                    <th style="width: 140px;">FontAwesome Icon</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($blocks as $id => $blk) : ?>
                <tr>
                    <td><strong><?php echo esc_html($id); ?></strong></td>
                    <td>
                        <input type="text" name="blocks[<?php echo esc_attr($id); ?>][name]" value="<?php echo esc_attr($blk['name']); ?>" class="widefat" />
                    </td>
                    <td>
                        <select name="blocks[<?php echo esc_attr($id); ?>][status]">
                            <option value="active" <?php selected($blk['status'], 'active'); ?>>Active (ON)</option>
                            <option value="off" <?php selected($blk['status'], 'off'); ?>>Disabled (OFF)</option>
                        </select>
                    </td>
                    <td>
                        <select name="blocks[<?php echo esc_attr($id); ?>][rule]">
                            <option value="category"  <?php selected($blk['rule'], 'category');  ?>>Category</option>
                            <option value="genre"     <?php selected($blk['rule'], 'genre');     ?>>Genre</option>
                            <option value="country"   <?php selected($blk['rule'], 'country');   ?>>Country</option>
                            <option value="year"      <?php selected($blk['rule'], 'year');      ?>>Release Year</option>
                            <option value="post_type" <?php selected($blk['rule'], 'post_type'); ?>>Post Type (All)</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="blocks[<?php echo esc_attr($id); ?>][value]" value="<?php echo esc_attr($blk['value']); ?>" class="widefat" placeholder="e.g. action, korea, 2026" />
                    </td>
                    <td>
                        <input type="text" name="blocks[<?php echo esc_attr($id); ?>][icon]" value="<?php echo esc_attr($blk['icon']); ?>" class="widefat" placeholder="fa-film" />
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p class="submit">
            <input type="submit" name="movie_elite_save_blocks" class="button button-primary button-large" value="Save Homepage Layout" />
        </p>
    </form>
</div>
<?php
}
