/**
 * MovieElite Pro - Features Engine JS
 * Handles: Watchlist, Continue Watching, Star Ratings, Reviews, Advanced Filter, Countdown
 */
(function() {
'use strict';
var ME = window.meFeatures || {};
var AJAX = ME.ajaxurl || '/wp-admin/admin-ajax.php';

// ═══ WATCHLIST (localStorage) ═══
var WL_KEY = 'me_watchlist';
function getWatchlist() {
    try { return JSON.parse(localStorage.getItem(WL_KEY) || '[]'); } catch(e) { return []; }
}
function saveWatchlist(list) { localStorage.setItem(WL_KEY, JSON.stringify(list)); }
function isWatchlisted(id) { return getWatchlist().indexOf(String(id)) !== -1; }
function toggleWatchlist(id) {
    var list = getWatchlist();
    var sid  = String(id);
    var idx  = list.indexOf(sid);
    if (idx === -1) { list.push(sid); } else { list.splice(idx, 1); }
    saveWatchlist(list);
    return idx === -1; // true = added
}

// Render watchlist buttons state on page load
function initWatchlistButtons() {
    document.querySelectorAll('.me-wl-btn').forEach(function(btn) {
        var id = btn.getAttribute('data-id');
        if (isWatchlisted(id)) {
            btn.classList.add('active');
            btn.setAttribute('title', 'Remove from Watchlist');
        } else {
            btn.classList.remove('active');
            btn.setAttribute('title', 'Add to Watchlist');
        }
    });
}

document.addEventListener('click', function(e) {
    var btn = e.target.closest('.me-wl-btn');
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();
    var id  = btn.getAttribute('data-id');
    var added = toggleWatchlist(id);
    btn.classList.toggle('active', added);
    btn.setAttribute('title', added ? 'Remove from Watchlist' : 'Add to Watchlist');
    btn.style.transform = 'scale(1.3)';
    setTimeout(function() { btn.style.transform = ''; }, 300);
    renderWatchlistPage();
});

// Watchlist page rendering
function renderWatchlistPage() {
    var container = document.getElementById('me-watchlist-page');
    if (!container) return;
    var list = getWatchlist();
    if (list.length === 0) {
        container.innerHTML = '<div style="text-align:center;padding:60px 20px;color:var(--text-muted);"><i class="fa-solid fa-heart" style="font-size:3rem;opacity:0.2;margin-bottom:16px;display:block;"></i>Your watchlist is empty.<br>Click the ♥ on any movie to save it here.</div>';
        return;
    }
    container.innerHTML = '<div class="me-wl-loading" style="padding:30px;text-align:center;color:var(--text-muted);"><i class="fa-solid fa-spinner fa-spin"></i> Loading your watchlist...</div>';
    var fd = new FormData();
    fd.append('action','movie_elite_get_watchlist_cards');
    fd.append('nonce', ME.nonce || '');
    fd.append('ids',   JSON.stringify(list));
    fetch(AJAX, {method:'POST',body:fd})
        .then(function(r){ return r.json(); })
        .then(function(resp) {
            if (resp.success) { container.innerHTML = '<div class="movies-grid">' + resp.data.html + '</div>'; initWatchlistButtons(); }
        })
        .catch(function() { container.innerHTML = '<p style="color:var(--text-muted);padding:20px;">Could not load watchlist.</p>'; });
}

// ═══ CONTINUE WATCHING (localStorage) ═══
var CW_KEY = 'me_continue_watching';
function getCW() {
    try { return JSON.parse(localStorage.getItem(CW_KEY) || '[]'); } catch(e) { return []; }
}
function saveCWItem(data) {
    var list = getCW();
    var idx  = list.findIndex(function(i){ return String(i.id) === String(data.id); });
    if (idx !== -1) { list[idx] = data; } else { list.unshift(data); }
    list = list.slice(0, 12);
    localStorage.setItem(CW_KEY, JSON.stringify(list));
}

function initContinueWatching() {
    var iframe = document.getElementById('main-movie-iframe');
    if (iframe) {
        var postId  = document.body.getAttribute('data-post-id') || '';
        var postTitle = document.title || '';
        var poster  = document.querySelector('.single-poster-wrap img');
        var posterSrc = poster ? poster.src : '';
        var perma   = window.location.href;
        if (postId) {
            setTimeout(function() {
                saveCWItem({ id: postId, title: postTitle, poster: posterSrc, url: perma, ts: Date.now() });
            }, 3000);
        }
    }

    var cwSection = document.getElementById('me-continue-watching-section');
    if (!cwSection) return;
    var list = getCW();
    if (list.length === 0) { cwSection.style.display = 'none'; return; }
    cwSection.style.display = '';
    var grid = document.getElementById('me-cw-grid');
    if (!grid) return;
    grid.innerHTML = list.slice(0, 6).map(function(item) {
        var cleanTitle = item.title.replace(/ – .*/, '').replace(/ - .*/, '');
        return '<a href="' + item.url + '" class="me-cw-card">' +
            '<div class="me-cw-thumb"><img src="' + (item.poster||'') + '" alt="" loading="lazy" />' +
            '<div class="me-cw-play"><i class="fa-solid fa-play"></i></div></div>' +
            '<div class="me-cw-info"><p class="me-cw-title">' + cleanTitle + '</p>' +
            '<span class="me-cw-meta"><i class="fa-solid fa-rotate-right"></i> Continue Watching</span></div></a>';
    }).join('');
}

// ═══ STAR RATINGS ═══
function initStarRatings() {
    document.querySelectorAll('.me-stars-input').forEach(function(widget) {
        var stars  = widget.querySelectorAll('.me-star');
        var postId = widget.getAttribute('data-post');
        var nonce  = widget.getAttribute('data-nonce') || (ME.rateNonce || '');
        var rated  = localStorage.getItem('me_rated_' + postId);

        if (rated) {
            highlightStars(stars, parseInt(rated));
            widget.style.opacity = '0.7';
            widget.title = 'You rated this ' + rated + ' stars';
        }

        stars.forEach(function(star) {
            star.addEventListener('mouseenter', function() {
                if (localStorage.getItem('me_rated_' + postId)) return;
                highlightStars(stars, parseInt(this.getAttribute('data-val')));
            });
            star.addEventListener('mouseleave', function() {
                if (localStorage.getItem('me_rated_' + postId)) return;
                var saved = parseInt(localStorage.getItem('me_rated_' + postId) || '0');
                highlightStars(stars, saved);
            });
            star.addEventListener('click', function() {
                if (localStorage.getItem('me_rated_' + postId)) return;
                var val = parseInt(this.getAttribute('data-val'));
                if (!postId || !val) return;
                localStorage.setItem('me_rated_' + postId, val);
                var fd = new FormData();
                fd.append('action','movie_elite_rate');
                fd.append('nonce', nonce);
                fd.append('post_id', postId);
                fd.append('score', val);
                fetch(AJAX, {method:'POST',body:fd})
                    .then(function(r){ return r.json(); })
                    .then(function(resp) {
                        if (resp.success) {
                            var avgEl = document.getElementById('me-avg-' + postId);
                            if (avgEl) {
                                avgEl.querySelector('.me-avg-score').textContent = resp.data.avg;
                                avgEl.querySelector('.me-avg-count').textContent = resp.data.count + ' ratings';
                            }
                        }
                    });
                widget.style.opacity = '0.7';
                highlightStars(stars, val);
            });
        });
    });
}

function highlightStars(stars, val) {
    stars.forEach(function(s, i) {
        if (i < val) {
            s.classList.add('active');
        } else {
            s.classList.remove('active');
        }
    });
}

// ═══ REVIEW SUBMISSION ═══
function initReviews() {
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.me-btn-submit-review');
        if (!btn) return;
        var postId = btn.getAttribute('data-post');
        var nonce  = btn.getAttribute('data-nonce');
        var form   = document.getElementById('me-review-form-' + postId);
        if (!form) return;
        var author = form.querySelector('.me-rv-author').value.trim();
        var body   = form.querySelector('.me-rv-body').value.trim();
        var stars  = form.querySelectorAll('.me-review-stars .me-star.active');
        var scoreVal = stars.length || 5;
        var msg    = form.querySelector('.me-review-msg');
        if (!body) { alert('Please write something before submitting.'); return; }
        btn.disabled = true; btn.textContent = 'Posting...';
        var fd = new FormData();
        fd.append('action', 'movie_elite_review');
        fd.append('nonce', nonce);
        fd.append('post_id', postId);
        fd.append('author', author || 'Guest');
        fd.append('body', body);
        fd.append('score', scoreVal);
        fetch(AJAX, {method:'POST',body:fd})
            .then(function(r){ return r.json(); })
            .then(function(resp) {
                if (resp.success) {
                    msg.style.display = 'block';
                    msg.style.color = 'var(--accent-green)';
                    msg.textContent = '✓ Review posted! Thank you.';
                    form.querySelector('.me-rv-body').value = '';
                    form.querySelector('.me-rv-author').value = '';
                } else {
                    msg.style.display = 'block';
                    msg.style.color = '#ff4444';
                    msg.textContent = '✗ Error posting review.';
                }
                btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Post Review';
            });
    });
}

// ═══ ADVANCED FILTER ═══
function initAdvancedFilter() {
    var filterBar = document.getElementById('me-advanced-filter-bar');
    if (!filterBar) return;

    var genreSelect   = document.getElementById('me-filter-genre');
    var countrySelect = document.getElementById('me-filter-country');
    var yearSelect    = document.getElementById('me-filter-year');

    if (genreSelect && ME.genres && genreSelect.options.length <= 1) {
        ME.genres.forEach(function(g) {
            var opt = new Option(g.name, g.slug);
            genreSelect.appendChild(opt);
        });
    }
    if (countrySelect && ME.countries && countrySelect.options.length <= 1) {
        ME.countries.forEach(function(c) {
            var opt = new Option(c.name, c.slug);
            countrySelect.appendChild(opt);
        });
    }
    if (yearSelect && ME.years && yearSelect.options.length <= 1) {
        ME.years.forEach(function(y) {
            var opt = new Option(y, y);
            yearSelect.appendChild(opt);
        });
    }

    var resultGrid = document.getElementById('me-filter-results');
    var loadingEl  = document.getElementById('me-filter-loading');
    var filterTimeout = null;

    function doFilter(page) {
        page = page || 1;
        if (loadingEl) loadingEl.style.display = 'flex';
        if (resultGrid) resultGrid.style.opacity = '0.4';
        var fd = new FormData();
        fd.append('action', 'movie_elite_advanced_filter');
        fd.append('nonce',   ME.nonce || '');
        fd.append('genre',   genreSelect   ? genreSelect.value   : '');
        fd.append('country', countrySelect ? countrySelect.value : '');
        fd.append('year',    yearSelect    ? yearSelect.value    : '');
        fd.append('quality', document.getElementById('me-filter-quality') ? document.getElementById('me-filter-quality').value : '');
        fd.append('ptype',   document.getElementById('me-filter-ptype')   ? document.getElementById('me-filter-ptype').value   : '');
        fd.append('paged',   page);
        fetch(AJAX, {method:'POST',body:fd})
            .then(function(r){ return r.json(); })
            .then(function(resp) {
                if (loadingEl) loadingEl.style.display = 'none';
                if (resultGrid) {
                    resultGrid.style.opacity = '1';
                    if (resp.success) { resultGrid.innerHTML = resp.data.html; }
                }
                initWatchlistButtons();
            })
            .catch(function() {
                if (loadingEl) loadingEl.style.display = 'none';
                if (resultGrid) resultGrid.style.opacity = '1';
            });
    }

    filterBar.querySelectorAll('select').forEach(function(sel) {
        sel.addEventListener('change', function() {
            clearTimeout(filterTimeout);
            filterTimeout = setTimeout(function() { doFilter(1); }, 200);
        });
    });

    var resetBtn = document.getElementById('me-filter-reset');
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            filterBar.querySelectorAll('select').forEach(function(s){ s.value = ''; });
            doFilter(1);
        });
    }

    doFilter(1);
}

// ═══ COMING SOON COUNTDOWN ═══
function initCountdowns() {
    document.querySelectorAll('.me-countdown').forEach(function(el) {
        var targetStr = el.getAttribute('data-date');
        if (!targetStr) return;
        var target = new Date(targetStr).getTime();
        if (isNaN(target)) { el.textContent = 'Coming Soon'; return; }
        function tick() {
            var now  = Date.now();
            var diff = target - now;
            if (diff <= 0) { el.textContent = 'Out Now!'; return; }
            var d = Math.floor(diff / 86400000);
            var h = Math.floor((diff % 86400000) / 3600000);
            var m = Math.floor((diff % 3600000)  / 60000);
            var s = Math.floor((diff % 60000)    / 1000);
            el.innerHTML = '<span class="cd-unit"><b>' + d + '</b>d</span> <span class="cd-unit"><b>' + h + '</b>h</span> <span class="cd-unit"><b>' + m + '</b>m</span> <span class="cd-unit"><b>' + s + '</b>s</span>';
        }
        tick();
        setInterval(tick, 1000);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    initWatchlistButtons();
    initContinueWatching();
    initStarRatings();
    initReviews();
    initAdvancedFilter();
    initCountdowns();
    renderWatchlistPage();
});

})();
