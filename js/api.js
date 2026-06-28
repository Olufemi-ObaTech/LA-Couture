/* ═══════════════════════════════════════════════════
   L.A. COUTURE — API CLIENT
   Bridges frontend to Laravel backend on Railway.
   Falls back to localStorage when backend is unreachable.
   ═══════════════════════════════════════════════════ */

// Priority: local dev → meta tag override → hardcoded Railway URL
const _isLocal = window.location.hostname === '127.0.0.1' || window.location.hostname === 'localhost';
const _metaApi  = document.querySelector('meta[name="api-base"]')?.content;
const API_BASE  = _isLocal
  ? 'http://127.0.0.1:8000/api'
  : (_metaApi || 'https://la-couture-production.up.railway.app/api');

const LAApi = (() => {
  function getToken() {
    return sessionStorage.getItem('la_token') || localStorage.getItem('la_token');
  }

  function setToken(token, remember = false) {
    sessionStorage.setItem('la_token', token);
    if (remember) localStorage.setItem('la_token', token);
  }

  function clearToken() {
    sessionStorage.removeItem('la_token');
    localStorage.removeItem('la_token');
  }

  async function request(method, path, body = null, requiresAuth = false) {
    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    };

    const token = getToken();
    if (token) headers['Authorization'] = 'Bearer ' + token;

    const opts = { method, headers, credentials: 'include' };
    if (body && method !== 'GET') opts.body = JSON.stringify(body);

    try {
      const resp = await fetch(API_BASE + path, opts);

      if (resp.status === 401) {
        clearToken();
        if (requiresAuth) {
          const u = localStorage.getItem('la_couture_current_user');
          const role = u ? (JSON.parse(u).role || '') : '';
          localStorage.removeItem('la_couture_current_user');
          window.location.href = role === 'admin' ? 'admin-login.html' : 'client-login.html';
        }
        return { ok: false, status: 401, data: { message: 'Session expired. Please log in again.' } };
      }

      const data = resp.headers.get('content-type')?.includes('application/json')
        ? await resp.json()
        : { message: 'Server error' };

      return { ok: resp.ok, status: resp.status, data };
    } catch (err) {
      console.warn('[LAApi] Network error:', err.message);
      return { ok: false, status: 0, data: { message: 'Cannot reach server. Check your connection.' } };
    }
  }

  /* ── Auth ─────────────────────────────────── */
  const auth = {
    async adminLogin(email, password) {
      const res = await request('POST', '/auth/admin/login', { email, password });
      if (res.ok) {
        setToken(res.data.token, true);
        localStorage.setItem('la_couture_current_user', JSON.stringify({
          ...res.data.user, loginTime: new Date().toISOString()
        }));
      }
      return res;
    },

    async clientLogin(email, password) {
      const res = await request('POST', '/auth/client/login', { email, password });
      if (res.ok) {
        setToken(res.data.token, true);
        localStorage.setItem('la_couture_current_user', JSON.stringify({
          ...res.data.user, loginTime: new Date().toISOString()
        }));
      }
      return res;
    },

    async register(data) {
      return request('POST', '/auth/register', data);
    },

    async logout() {
      const res = await request('POST', '/auth/logout', null, true);
      clearToken();
      localStorage.removeItem('la_couture_current_user');
      return res;
    },

    async me() {
      return request('GET', '/auth/me', null, true);
    },

    currentUser() {
      const u = localStorage.getItem('la_couture_current_user');
      return u ? JSON.parse(u) : null;
    },

    isAuthenticated() {
      return !!getToken() && !!this.currentUser();
    },
  };

  /* ── Products ─────────────────────────────── */
  const products = {
    async list(category = null) {
      const qs = category ? `?category=${encodeURIComponent(category)}` : '';
      return request('GET', '/products' + qs);
    },
  };

  /* ── Orders ───────────────────────────────── */
  const orders = {
    async create(items, clientData = {}) {
      return request('POST', '/orders', { items, ...clientData }, true);
    },

    async list() {
      return request('GET', '/orders', null, true);
    },

    async get(id) {
      return request('GET', `/orders/${id}`, null, true);
    },
  };

  /* ── Enquiries ────────────────────────────── */
  const enquiries = {
    async list() {
      return request('GET', '/enquiries', null, true);
    },

    async create(data) {
      return request('POST', '/enquiries', data, true);
    },

    async get(id) {
      return request('GET', `/enquiries/${id}`, null, true);
    },

    async sendMessage(id, message) {
      return request('POST', `/enquiries/${id}/messages`, { message }, true);
    },
  };

  /* ── Contact ──────────────────────────────── */
  const contact = {
    async submit(data) {
      return request('POST', '/contact', data);
    },
  };

  /* ── Admin ────────────────────────────────── */
  const admin = {
    async stats() {
      return request('GET', '/admin/stats', null, true);
    },

    async clients() {
      return request('GET', '/admin/clients', null, true);
    },

    async approveClient(id) {
      return request('POST', `/admin/clients/${id}/approve`, {}, true);
    },

    async rejectClient(id, reason) {
      return request('POST', `/admin/clients/${id}/reject`, { reason }, true);
    },

    async orderStatus(id, status) {
      return request('PUT', `/admin/orders/${id}/status`, { status }, true);
    },

    async enquiryStatus(id, status) {
      return request('PUT', `/admin/enquiries/${id}/status`, { status }, true);
    },

    async contactForms() {
      return request('GET', '/admin/contact-forms', null, true);
    },

    async contactFormStatus(id, status) {
      return request('PUT', `/admin/contact-forms/${id}/status`, { status }, true);
    },

    async createProduct(data) {
      return request('POST', '/admin/products', data, true);
    },

    async updateProduct(id, data) {
      return request('PUT', `/admin/products/${id}`, data, true);
    },

    async deleteProduct(id) {
      return request('DELETE', `/admin/products/${id}`, null, true);
    },
  };

  return { auth, products, orders, enquiries, contact, admin };
})();

window.LAApi = LAApi;

/* ── Live Clock — runs on every page ─────── */
(function () {
  const TZ = 'Africa/Lagos';
  function tick() {
    const d = document.getElementById('clock-date');
    const t = document.getElementById('clock-time');
    if (!d && !t) return;
    const now = new Date();
    if (d) d.textContent = now.toLocaleDateString('en-NG', {
      weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', timeZone: TZ,
    });
    if (t) t.textContent = now.toLocaleTimeString('en-NG', {
      hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true, timeZone: TZ,
    }) + ' WAT';
  }
  document.addEventListener('DOMContentLoaded', function () { tick(); setInterval(tick, 1000); });
})();
