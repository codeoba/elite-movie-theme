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

    var ptypeSelect   = document.getElementById('me-filter-ptype');
    var genreSelect   = document.getElementById('me-filter-genre');
    var countrySelect = document.getElementById('me-filter-country');
    var yearSelect    = document.getElementById('me-filter-year');
    var qualitySelect = document.getElementById('me-filter-quality');
    var resetBtn      = document.getElementById('me-filter-reset');

    var resultsContainer = document.getElementById('me-filter-results-container');
    var resultGrid       = document.getElementById('me-filter-results');
    var loadingEl        = document.getElementById('me-filter-loading');
    var countEl          = document.getElementById('me-filter-count');
    var closeBtn         = document.getElementById('me-filter-close');

    var currentLetter = 'ALL';
    var data = typeof meFeatures !== 'undefined' ? meFeatures : (typeof movie_elite_ajax !== 'undefined' ? movie_elite_ajax : {});

    // Dynamic dropdown populator if dropdown options are not populated by PHP
    if (genreSelect && data.genres && genreSelect.options.length <= 1) {
        data.genres.forEach(function(g) {
            genreSelect.appendChild(new Option(g.name, g.slug));
        });
    }
    if (countrySelect && data.countries && countrySelect.options.length <= 1) {
        data.countries.forEach(function(c) {
            countrySelect.appendChild(new Option(c.name, c.slug));
        });
    }
    if (yearSelect && data.years && yearSelect.options.length <= 1) {
        data.years.forEach(function(y) {
            yearSelect.appendChild(new Option(y, y));
        });
    }
    if (qualitySelect && data.qualities && qualitySelect.options.length <= 1) {
        data.qualities.forEach(function(q) {
            qualitySelect.appendChild(new Option(q, q));
        });
    }

    var filterTimeout = null;

    function doFilter(page) {
        page = page || 1;
        var ptype   = ptypeSelect   ? ptypeSelect.value   : '';
        var genre   = genreSelect   ? genreSelect.value   : '';
        var country = countrySelect ? countrySelect.value : '';
        var year    = yearSelect    ? yearSelect.value    : '';
        var quality = qualitySelect ? qualitySelect.value : '';

        // If no filter is selected and letter is ALL, hide results container
        if (!ptype && !genre && !country && !year && !quality && currentLetter === 'ALL') {
            if (resultsContainer) resultsContainer.style.display = 'none';
            return;
        }

        if (resultsContainer) resultsContainer.style.display = 'block';
        if (loadingEl) loadingEl.style.display = 'flex';
        if (resultGrid) resultGrid.style.opacity = '0.4';

        var ajaxUrl = data.ajaxurl || data.ajax_url || (typeof AJAX !== 'undefined' ? AJAX : '/wp-admin/admin-ajax.php');
        var fd = new FormData();
        fd.append('action',  'movie_elite_advanced_filter');
        fd.append('nonce',    data.nonce || '');
        fd.append('ptype',    ptype);
        fd.append('genre',    genre);
        fd.append('country',  country);
        fd.append('year',     year);
        fd.append('quality',  quality);
        fd.append('letter',   currentLetter);
        fd.append('paged',    page);

        fetch(ajaxUrl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                if (loadingEl) loadingEl.style.display = 'none';
                if (resultGrid) {
                    resultGrid.style.opacity = '1';
                    if (resp.success) {
                        resultGrid.innerHTML = resp.data.html;
                        if (countEl) countEl.textContent = resp.data.total || 0;
                    }
                }
                if (typeof initWatchlistButtons === 'function') {
                    initWatchlistButtons();
                }
            })
            .catch(function(err) {
                if (loadingEl) loadingEl.style.display = 'none';
                if (resultGrid) resultGrid.style.opacity = '1';
            });
    }

    // Bind dropdown change handlers
    filterBar.querySelectorAll('select').forEach(function(sel) {
        sel.addEventListener('change', function() {
            clearTimeout(filterTimeout);
            filterTimeout = setTimeout(function() { doFilter(1); }, 150);
        });
    });

    // Bind A-Z letter buttons
    document.querySelectorAll('.alphabet-btn[data-letter]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.alphabet-btn[data-letter]').forEach(function(b) { b.classList.remove('active'); });
            btn.classList.add('active');
            currentLetter = btn.getAttribute('data-letter') || 'ALL';
            doFilter(1);
        });
    });

    // Reset button handler
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            currentLetter = 'ALL';
            document.querySelectorAll('.alphabet-btn[data-letter]').forEach(function(b) { b.classList.remove('active'); });
            var firstLetterBtn = document.querySelector('.alphabet-btn[data-letter="ALL"]');
            if (firstLetterBtn) firstLetterBtn.classList.add('active');

            filterBar.querySelectorAll('select').forEach(function(s) { s.value = ''; });
            if (resultsContainer) resultsContainer.style.display = 'none';
        });
    }

    // Close results handler
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            currentLetter = 'ALL';
            filterBar.querySelectorAll('select').forEach(function(s) { s.value = ''; });
            if (resultsContainer) resultsContainer.style.display = 'none';
        });
    }
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

// ═══ LIVE INSTANT SEARCH ═══
function initLiveSearch() {
    var searchInput = document.getElementById('movie-search-input');
    var resultsBox  = document.getElementById('live-search-results');
    if (!searchInput || !resultsBox) return;

    var debounceTimer;
    searchInput.addEventListener('input', function() {
        var query = searchInput.value.trim();
        clearTimeout(debounceTimer);
        if (query.length < 2) {
            resultsBox.style.display = 'none';
            resultsBox.innerHTML = '';
            return;
        }

        debounceTimer = setTimeout(function() {
            fetch(AJAX + '?action=movie_elite_live_search&s=' + encodeURIComponent(query))
                .then(function(r) { return r.json(); })
                .then(function(resp) {
                    if (resp.success && resp.data.results && resp.data.results.length > 0) {
                        var html = '<div class="live-results-list">';
                        resp.data.results.forEach(function(item) {
                            html += '<a href="' + item.permalink + '" class="live-search-item">';
                            html += '<img src="' + item.poster + '" alt="' + item.title + '" />';
                            html += '<div class="live-item-info">';
                            html += '<span class="live-item-type">' + item.type + ' • ⭐ ' + item.rating + '</span>';
                            html += '<h5 class="live-item-title">' + item.title + ' (' + item.year + ')</h5>';
                            html += '</div></a>';
                        });
                        html += '</div>';
                        resultsBox.innerHTML = html;
                        resultsBox.style.display = 'block';
                    } else {
                        resultsBox.innerHTML = '<div style="padding:15px;text-align:center;color:var(--text-muted);font-size:0.85rem;">No results found for "' + query + '"</div>';
                        resultsBox.style.display = 'block';
                    }
                });
        }, 250);
    });

    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !resultsBox.contains(e.target)) {
            resultsBox.style.display = 'none';
        }
    });
}

// ═══ ACCENT THEME COLOR SWITCHER ═══
function initAccentSwitcher() {
    var savedColor = localStorage.getItem('me_accent_color');
    if (savedColor) {
        document.documentElement.style.setProperty('--accent-cyan', savedColor);
    }
    document.querySelectorAll('.accent-dot').forEach(function(dot) {
        dot.addEventListener('click', function() {
            var color = dot.getAttribute('data-color');
            if (color) {
                document.documentElement.style.setProperty('--accent-cyan', color);
                localStorage.setItem('me_accent_color', color);
            }
        });
    });
}

// ═══ OFFICIAL TRAILER MODAL ═══
function initTrailerModal() {
    var modalBox = document.getElementById('trailer-modal');
    var iframeBox = document.getElementById('trailer-iframe-box');
    var closeBtn = document.getElementById('btn-close-trailer');
    if (!modalBox || !iframeBox) return;

    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-open-trailer');
        if (!btn) return;
        var trailerUrl = btn.getAttribute('data-trailer');
        if (!trailerUrl) return;

        var embedUrl = trailerUrl;
        if (trailerUrl.indexOf('youtube.com') !== -1 || trailerUrl.indexOf('youtu.be') !== -1) {
            var match = trailerUrl.match(/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})/);
            if (match && match[1]) {
                embedUrl = 'https://www.youtube.com/embed/' + match[1] + '?autoplay=1';
            }
        } else if (trailerUrl.length === 11) {
            embedUrl = 'https://www.youtube.com/embed/' + trailerUrl + '?autoplay=1';
        }

        iframeBox.innerHTML = '<iframe src="' + embedUrl + '" allow="autoplay; fullscreen" allowfullscreen style="width:100%;height:100%;border:0;"></iframe>';
        modalBox.style.display = 'flex';
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            modalBox.style.display = 'none';
            iframeBox.innerHTML = '';
        });
    }

    modalBox.addEventListener('click', function(e) {
        if (e.target === modalBox) {
            modalBox.style.display = 'none';
            iframeBox.innerHTML = '';
        }
    });
}

// ═══ REPORT BROKEN PLAYER ═══
function initReportBrokenPlayer() {
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-report-broken');
        if (!btn) return;
        var postId = btn.getAttribute('data-id');
        var activeTab = document.querySelector('.server-tab.active');
        var serverName = activeTab ? activeTab.textContent.trim() : 'Server 1';

        if (!confirm('Report broken video player (' + serverName + ') to site moderators?')) return;

        var fd = new FormData();
        fd.append('action', 'movie_elite_report_broken_link');
        fd.append('nonce', ME.nonce || '');
        fd.append('post_id', postId);
        fd.append('server_name', serverName);

        fetch(AJAX, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                alert(resp.data.message || 'Report submitted successfully!');
                btn.style.background = 'rgba(0,255,136,0.15)';
                btn.style.color = '#00ff88';
                btn.innerHTML = '<i class="fa-solid fa-check"></i> Reported';
            });
    });
}

// ═══ AIRING SCHEDULE CALENDAR ═══
function initAiringSchedule() {
    document.querySelectorAll('.airing-day-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            var targetDay = tab.getAttribute('data-day');
            document.querySelectorAll('.airing-day-tab').forEach(function(t) { t.classList.remove('active'); });
            tab.classList.add('active');

            document.querySelectorAll('.airing-show-card').forEach(function(card) {
                var cardDay = card.getAttribute('data-day');
                card.style.display = (cardDay === targetDay) ? 'flex' : 'none';
            });
        });
    });
}

// ═══ AUTO NEXT EPISODE & ONE-TAP COPY ═══
function initEpisodeAndCopyControls() {
    // Next Episode Button
    document.addEventListener('click', function(e) {
        var nextBtn = e.target.closest('.btn-next-episode');
        if (!nextBtn) return;
        var activeEp = document.querySelector('.btn-episode-select.active');
        if (activeEp) {
            var nextEp = activeEp.nextElementSibling;
            if (nextEp && nextEp.classList.contains('btn-episode-select')) {
                nextEp.click();
            } else {
                alert('You are already on the latest episode!');
            }
        }
    });

    // Copy Link Button
    document.addEventListener('click', function(e) {
        var copyBtn = e.target.closest('.btn-copy-link');
        if (!copyBtn) return;
        var url = copyBtn.getAttribute('data-url') || window.location.href;
        navigator.clipboard.writeText(url).then(function() {
            var textSpan = copyBtn.querySelector('.copy-text');
            if (textSpan) {
                var oldText = textSpan.textContent;
                textSpan.textContent = 'Copied!';
                setTimeout(function() { textSpan.textContent = oldText; }, 2000);
            }
        });
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
    initLiveSearch();
    initAccentSwitcher();
    initTrailerModal();
    initReportBrokenPlayer();
    initAiringSchedule();
    initEpisodeAndCopyControls();
});

})();
