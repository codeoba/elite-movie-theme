<?php
/**
 * MovieElite Pro - Theme Footer & Trailer Modal Overlay
 */
?>

<!-- Trailer Modal Window -->
<div id="trailer-modal" class="modal-player-overlay" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.9); backdrop-filter:blur(20px); align-items:center; justify-content:center; padding:20px;">
    <div class="modal-player-wrapper" style="width:100%; max-width:900px; background:var(--bg-secondary); border-radius:16px; overflow:hidden; border:1px solid var(--border-color);">
        <div style="display:flex; justify-content:space-between; align-items:center; padding:16px 24px; background:#0d1017; border-bottom:1px solid var(--border-color);">
            <h3 style="margin:0; color:#fff; font-size:1.1rem;"><i class="fa-brands fa-youtube" style="color:#ff0000;"></i> Official Movie Trailer</h3>
            <button type="button" id="btn-close-trailer" style="background:none; border:none; color:var(--text-muted); font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        <div class="iframe-player-wrapper">
            <iframe id="trailer-iframe" src="" allowfullscreen></iframe>
        </div>
    </div>
</div>

<footer class="site-footer">
    <div class="container">
        <div style="display:flex; flex-wrap:wrap; justify-content:space-between; gap:30px; margin-bottom:30px;">
            <div style="max-width:420px;">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="site-logo">
                    <i class="fa-solid fa-clapperboard movie-pop-icon"></i> MOVIE<span>ELITE PRO</span>
                </a>
                <p style="color:var(--text-muted); font-size:0.88rem; margin-top:12px;">Watch full length movies, TV shows, K-Dramas, C-Dramas, and Asian dramas online in ultra 4K high definition quality with multi-source video servers.</p>
            </div>

            <div style="display:flex; gap:50px;">
                <div>
                    <h4 style="color:#fff; margin-bottom:12px; font-size:0.95rem;">Quick Navigation</h4>
                    <ul style="list-style:none; display:flex; flex-direction:column; gap:8px; font-size:0.85rem;">
                        <li><a href="#block-recommended">Recommended Movies</a></li>
                        <li><a href="#block-action">Action Movies</a></li>
                        <li><a href="#block-romance">Romance Movies</a></li>
                        <li><a href="#block-korean">Korean Movies & K-Dramas</a></li>
                        <li><a href="#block-chinese">Chinese Movies & C-Dramas</a></li>
                    </ul>
                </div>
                <div>
                    <h4 style="color:#fff; margin-bottom:12px; font-size:0.95rem;">Tools & Management</h4>
                    <ul style="list-style:none; display:flex; flex-direction:column; gap:8px; font-size:0.85rem;">
                        <li><a href="<?php echo esc_url(admin_url('edit.php?post_type=movies&page=movie-elite-importer')); ?>">Movie Importer Tool</a></li>
                        <li><a href="<?php echo esc_url(admin_url('edit.php?post_type=movies&page=movie-elite-embed-manager')); ?>">Embed Source Manager</a></li>
                        <li><a href="<?php echo esc_url(admin_url('edit.php?post_type=movies&page=movie-elite-block-manager')); ?>">Block Layout Manager</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="footer-bottom" style="text-align:center; padding-top:20px; border-top:1px solid var(--border-color);">
            <p style="margin:0 0 8px 0; color:#fff; font-size:0.92rem; font-weight:700;">
                &copy; <?php echo date('Y'); ?> MovieElite Pro. Created By <span style="background:linear-gradient(135deg, var(--accent-cyan), var(--accent-magenta)); -webkit-background-clip:text; -webkit-text-fill-color:transparent; font-weight:900;">CodeOba</span>
            </p>
            <div style="display:flex; align-items:center; justify-content:center; gap:20px; flex-wrap:wrap; color:var(--text-muted); font-size:0.83rem; margin-top:8px;">
                <span><i class="fa-solid fa-user" style="color:var(--accent-cyan);"></i> Mohamed Nurdin Mgaza</span>
                <span><i class="fa-solid fa-envelope" style="color:var(--accent-gold);"></i> <a href="mailto:codeoba@gmail.com" style="color:var(--text-muted); text-decoration:none;">codeoba@gmail.com</a></span>
                <span><i class="fa-solid fa-phone" style="color:var(--accent-green);"></i> <a href="tel:+255687001775" style="color:var(--text-muted); text-decoration:none;">+255687001775</a></span>
                <span><i class="fa-solid fa-location-dot" style="color:var(--accent-magenta);"></i> Tanzania, Dar es Salaam</span>
            </div>
        </div>
    </div>
</footer>

<?php
if (function_exists('movie_elite_render_mobile_bottom_nav')) {
    movie_elite_render_mobile_bottom_nav();
}
if (function_exists('movie_elite_render_spotlight_modal')) {
    movie_elite_render_spotlight_modal();
}
wp_footer();
?>
</body>
</html>
