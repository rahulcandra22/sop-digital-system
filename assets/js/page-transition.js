/**
 * PAGE TRANSITION (halus) — Sinergi Nusantara Integrasi
 * assets/js/page-transition.js
 *
 * Efek: soft overlay fade + orb lembut + loader card berlogo
 * Tidak ada blob goo, tidak ada partikel mencolok, tidak ada glitch
 */
(function () {
    'use strict';

    var leaving  = false;
    var progVal  = 0;
    var progTimer = null;

    var LOGO_SRC = '../assets/images/logo.png';

    /* ─────────────────────────────────
       INJECT DOM
    ───────────────────────────────── */
    function injectDOM() {
        if (document.getElementById('pt-overlay')) return;

        /* Overlay */
        var ov = document.createElement('div');
        ov.id = 'pt-overlay';
        document.body.appendChild(ov);

        /* Loader */
        var loader = document.createElement('div');
        loader.id = 'pt-loader';
        loader.innerHTML =
            '<div class="pt-card">' +
            '  <div class="pt-corner pt-corner-tl"></div>' +
            '  <div class="pt-corner pt-corner-tr"></div>' +
            '  <div class="pt-corner pt-corner-bl"></div>' +
            '  <div class="pt-corner pt-corner-br"></div>' +
            '  <img class="pt-logo-img" src="' + LOGO_SRC + '" alt="Sinergi Nusantara">' +
            '  <div class="pt-divider"></div>' +
            '  <div class="pt-spinner-row">' +
            '    <div class="pt-text-col">' +
            '      <div class="pt-text-main">Sedang memuat' +
            '        <span class="d">.</span><span class="d d2">.</span><span class="d d3">.</span>' +
            '      </div>' +
            '      <div class="pt-text-sub">Mohon ditunggu sebentar...</div>' +
            '    </div>' +
            '  </div>' +
            '  <div class="pt-progress-wrap">' +
            '    <div class="pt-progress-fill" id="pt-prog"></div>' +
            '  </div>' +
            '</div>';
        document.body.appendChild(loader);
    }

    /* ─────────────────────────────────
       PROGRESS
    ───────────────────────────────── */
    function startProgress() {
        progVal = 0;
        clearInterval(progTimer);
        var bar = document.getElementById('pt-prog');
        if (bar) bar.style.width = '0%';

        progTimer = setInterval(function () {
            progVal += (95 - progVal) * .018 + .18;
            if (bar) bar.style.width = Math.min(progVal, 95) + '%';
        }, 40);
    }

    function finishProgress() {
        clearInterval(progTimer);
        var bar = document.getElementById('pt-prog');
        if (!bar) return;
        bar.style.transition = 'width .2s ease';
        bar.style.width = '100%';
        setTimeout(function () {
            bar.style.transition = '';
            bar.style.width = '0%';
        }, 350);
    }

    /* ─────────────────────────────────
       LEAVE
    ───────────────────────────────── */
    function playLeave(href) {
        if (leaving) return;
        leaving = true;

        var ov  = document.getElementById('pt-overlay');
        var ldr = document.getElementById('pt-loader');

        /* 1. Fade-in overlay */
        if (ov) ov.classList.add('pt-show');

        /* 2. Show loader after overlay starts fading in */
        setTimeout(function () {
            if (ldr) ldr.classList.add('pt-visible');
            startProgress();
        }, 120);

        /* 3. Navigate */
        setTimeout(function () {
            window.location.href = href;
        }, 1500);
    }

    /* ─────────────────────────────────
       ENTER
    ───────────────────────────────── */
    function playEnter() {
        leaving = false;
        var ov  = document.getElementById('pt-overlay');
        var ldr = document.getElementById('pt-loader');

        finishProgress();

        /* Hide loader */
        if (ldr) ldr.classList.remove('pt-visible');

        /* Fade out overlay */
        if (ov && ov.classList.contains('pt-show')) {
            ov.style.transition = 'opacity .35s ease';
            ov.classList.remove('pt-show');
            setTimeout(function () { ov.style.transition = ''; }, 400);
        }
    }

    /* ─────────────────────────────────
       RIPPLE
    ───────────────────────────────── */
    function addRipple(link, e) {
        var rect = link.getBoundingClientRect();
        var r    = document.createElement('div');
        r.className = 'pt-ripple';
        var sz = Math.max(rect.width, rect.height);
        r.style.cssText =
            'width:' + sz + 'px;height:' + sz + 'px;' +
            'left:' + (e.clientX - rect.left - sz / 2) + 'px;' +
            'top:'  + (e.clientY - rect.top  - sz / 2) + 'px;';
        link.appendChild(r);
        setTimeout(function () { if (r.parentNode) r.parentNode.removeChild(r); }, 700);
    }

    /* ─────────────────────────────────
       BIND LINKS
    ───────────────────────────────── */
    function bindLinks() {
        var nodes = document.querySelectorAll(
            '.sidebar-menu a, .topbar a[href], .top-action-btn[href]'
        );

        nodes.forEach(function (link) {
            var href = link.getAttribute('href');
            if (!href || href === '#' ||
                href.startsWith('javascript') ||
                href.startsWith('mailto') ||
                href.startsWith('tel') ||
                link.getAttribute('target') === '_blank') return;

            /* Ripple on mousedown */
            link.addEventListener('mousedown', function (e) {
                addRipple(this, e);
            });

            /* Intercept navigation */
            link.addEventListener('click', function (e) {
                var dest = this.getAttribute('href');
                /* Skip sama halaman */
                var curPage  = window.location.pathname.split('/').pop();
                var destPage = dest.split('?')[0].split('/').pop();
                if (curPage === destPage) return;

                e.preventDefault();
                playLeave(dest);
            });
        });
    }

    /* ─────────────────────────────────
       INIT
    ───────────────────────────────── */
    function init() {
        injectDOM();
        playEnter();
        bindLinks();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    /* Back/forward browser */
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) {
            leaving = false;
            var ov = document.getElementById('pt-overlay');
            if (ov) { ov.classList.remove('pt-show'); ov.style.opacity = ''; }
            playEnter();
        }
    });

})();