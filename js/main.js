/* ═══════════════════════════════════════════
   L.A. COUTURE — MAIN JS
   ═══════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', () => {

  /* ── Scroll Reveal ─────────────────────── */
  const revealEls = document.querySelectorAll('.reveal');
  if (revealEls.length) {
    const revealObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            revealObserver.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
    );
    revealEls.forEach((el) => revealObserver.observe(el));
  }

  /* ── Hamburger / Mobile Dropdown ────────── */
  const hamburger = document.querySelector('.hamburger');
  const drawer    = document.getElementById('navDrawer') || document.querySelector('.nav-drawer');
  const overlay   = document.getElementById('navOverlay') || document.querySelector('.nav-overlay');

  function openDrawer() {
    if (!drawer) return;
    drawer.classList.add('open');
    if (overlay) overlay.classList.add('open');
    if (hamburger) {
      hamburger.classList.add('is-open');
      hamburger.setAttribute('aria-expanded', 'true');
    }
    // Do NOT lock body scroll — dropdown is inside nav, page still scrollable
  }

  function closeDrawer() {
    if (!drawer) return;
    drawer.classList.remove('open');
    if (overlay) overlay.classList.remove('open');
    if (hamburger) {
      hamburger.classList.remove('is-open');
      hamburger.setAttribute('aria-expanded', 'false');
    }
  }

  function isDrawerOpen() {
    return drawer ? drawer.classList.contains('open') : false;
  }

  if (hamburger) {
    hamburger.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      isDrawerOpen() ? closeDrawer() : openDrawer();
    });
  }

  // Tap the overlay to close
  if (overlay) {
    overlay.addEventListener('click', (e) => {
      e.stopPropagation();
      closeDrawer();
    });
  }

  // Click anywhere outside nav to close
  document.addEventListener('click', (e) => {
    if (!isDrawerOpen()) return;
    const nav = document.querySelector('.nav');
    if (nav && !nav.contains(e.target) && !overlay?.contains(e.target)) {
      closeDrawer();
    }
  });

  // Close when a nav link inside drawer is tapped
  if (drawer) {
    drawer.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', () => {
        closeDrawer();
      });
    });
  }

  // Close on Escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && isDrawerOpen()) closeDrawer();
  });

  /* ── Filter Buttons (Collections & Gallery) */
  document.querySelectorAll('.products-filter').forEach((filterBar) => {
    filterBar.querySelectorAll('.filter-btn').forEach((btn) => {
      btn.addEventListener('click', () => {
        filterBar.querySelectorAll('.filter-btn').forEach((b) => b.classList.remove('active'));
        btn.classList.add('active');

        const filterValue = btn.dataset.filter || btn.dataset.gfilter || 'all';
        const grid = filterBar.nextElementSibling;
        if (!grid) return;

        grid.querySelectorAll('[data-cat], [data-gfilter]').forEach((item) => {
          const val = item.dataset.cat || item.dataset.gfilter;
          item.style.display = (filterValue === 'all' || val === filterValue) ? '' : 'none';
        });
      });
    });
  });

  /* ── Gallery Lightbox (index page) ──────── */
  const lightbox    = document.querySelector('.lightbox');
  const lightboxImg = lightbox?.querySelector('.lightbox-img');

  if (lightbox && lightboxImg) {
    const lbClose = lightbox.querySelector('.lightbox-close');

    const closeLightbox = () => {
      lightbox.classList.remove('active', 'open');
      lightboxImg.src = '';
      document.body.style.overflow = '';
    };

    document.querySelectorAll('.gallery-item').forEach((item) => {
      item.addEventListener('click', (e) => {
        e.preventDefault();
        const img = item.querySelector('img');
        const src = item.dataset.src || img?.src || '';
        if (!src) return;
        lightboxImg.src = src;
        lightboxImg.alt = img?.alt || '';
        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
      });
    });

    lbClose?.addEventListener('click', closeLightbox);
    lightbox.addEventListener('click', (e) => { if (e.target === lightbox) closeLightbox(); });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && lightbox.classList.contains('active')) closeLightbox();
    });
  }

  /* ── FAQ Accordion ───────────────────────── */
  document.querySelectorAll('.faq-item h4').forEach((heading) => {
    const body = heading.nextElementSibling;
    if (!body) return;
    body.classList.add('hidden');                  // collapsed by default
    heading.addEventListener('click', () => {
      const parent = heading.closest('.faq-item');
      const isOpen = !body.classList.contains('hidden');
      // Collapse all
      document.querySelectorAll('.faq-item').forEach((fi) => {
        fi.classList.remove('open');
        fi.querySelector('p')?.classList.add('hidden');
      });
      if (!isOpen) {
        body.classList.remove('hidden');
        parent?.classList.add('open');
      }
    });
  });

  /* ── Newsletter Form ─────────────────────── */
  const newsletterForm = document.querySelector('#newsletter-form');
  newsletterForm?.addEventListener('submit', (e) => {
    e.preventDefault();
    const input = newsletterForm.querySelector('input[type="email"]');
    if (!input?.value.trim()) return;
    const msg = document.querySelector('#newsletter-msg');
    if (msg) {
      msg.textContent = "Thanks for joining — we'll be in touch soon.";
      msg.className = 'newsletter-msg success';
    }
    newsletterForm.reset();
  });

  /* ── Contact Form fallback ───────────────── */
  const contactForm = document.querySelector('#contact-form');
  if (contactForm && !contactForm.dataset.handled) {
    contactForm.dataset.handled = 'main';
    contactForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const msg = document.querySelector('#form-msg');
      if (msg) {
        msg.textContent = 'Thank you! We will respond within 24 hours.';
        msg.className = 'form-msg success';
      }
      contactForm.reset();
    });
  }

  /* ── Active nav link highlight ───────────── */
  const currentPage = window.location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.nav-links a, .nav-drawer a').forEach((link) => {
    const href = link.getAttribute('href');
    if (href && (href === currentPage || href.startsWith(currentPage.replace('.html', '')))) {
      link.classList.add('active');
    }
  });

  /* ── Logged-in user nav pill ─────────────── */
  (function initUserNav() {
    if (typeof AuthSystem === 'undefined') return;
    const user = AuthSystem.getCurrentUser();
    if (!user) return;

    const isAdmin     = user.role === 'admin';
    const dashUrl     = isAdmin ? 'admin-dashboard.html' : 'client-dashboard.html';
    const esc         = typeof escapeHtml === 'function' ? escapeHtml : (s => String(s || ''));
    const rawName     = user.name || user.email || (isAdmin ? 'Admin' : 'Client');
    const rawInitials = (user.name || user.email || 'U')
                         .split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
    const initials    = esc(rawInitials);
    const displayName = esc(rawName);
    const roleLabel   = isAdmin ? 'Administrator' : 'Client';

    // Build pill HTML
    const pillHTML = `
      <div class="user-pill" id="userPill" role="button" aria-haspopup="true" aria-expanded="false" tabindex="0">
        <div class="user-pill-avatar">${initials}</div>
        <span class="user-pill-name">${esc(rawName.split(' ')[0])}</span>
        <svg class="user-pill-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
        <div class="user-pill-dropdown" role="menu">
          <div class="user-pill-header">
            <div class="user-pill-avatar lg">${initials}</div>
            <div>
              <div class="user-pill-full-name">${displayName}</div>
              <div class="user-pill-role">${roleLabel}</div>
            </div>
          </div>
          <a href="${dashUrl}" class="user-pill-item" role="menuitem">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Dashboard
          </a>
          <button class="user-pill-item user-pill-logout" role="menuitem" onclick="AuthSystem.logout()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Sign Out
          </button>
        </div>
      </div>`;

    // Replace .auth-nav desktop
    const authNav = document.querySelector('.auth-nav');
    if (authNav) authNav.outerHTML = pillHTML;

    // Replace .drawer-auth mobile
    const drawerAuth = document.querySelector('.drawer-auth');
    if (drawerAuth) {
      drawerAuth.innerHTML = `
        <div class="drawer-user">
          <div class="drawer-user-avatar">${initials}</div>
          <div>
            <div class="drawer-user-name">${displayName}</div>
            <div class="drawer-user-role">${roleLabel}</div>
          </div>
        </div>
        <a href="${dashUrl}">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
          Dashboard
        </a>
        <button onclick="AuthSystem.logout()" style="background:none;border:none;cursor:pointer;color:rgba(248,244,239,0.6);font-size:0.9rem;padding:10px 0;text-align:left;width:100%;display:flex;align-items:center;gap:10px;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          Sign Out
        </button>`;
    }

    // Dropdown toggle
    document.addEventListener('click', (e) => {
      const pill = document.getElementById('userPill');
      if (!pill) return;
      if (pill.contains(e.target)) {
        const open = pill.getAttribute('aria-expanded') === 'true';
        pill.setAttribute('aria-expanded', String(!open));
        pill.classList.toggle('open', !open);
      } else {
        pill.setAttribute('aria-expanded', 'false');
        pill.classList.remove('open');
      }
    });

    // Keyboard support
    document.getElementById('userPill')?.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        const pill = document.getElementById('userPill');
        const open = pill.getAttribute('aria-expanded') === 'true';
        pill.setAttribute('aria-expanded', String(!open));
        pill.classList.toggle('open', !open);
      }
    });
  })();

});
