/* ═══════════════════════════════════════════
   L.A. COUTURE — AUTH SYSTEM
   API-backed auth with localStorage fallback.
   ═══════════════════════════════════════════ */

class AuthSystem {
  static getCurrentUser() {
    try {
      const u = localStorage.getItem('la_couture_current_user');
      return u ? JSON.parse(u) : null;
    } catch { return null; }
  }

  static isAuthenticated() {
    return !!this.getCurrentUser();
  }

  static async logout() {
    if (window.LAApi) {
      await LAApi.auth.logout().catch(() => {});
    } else {
      localStorage.removeItem('la_couture_current_user');
    }
    window.location.href = 'index.html';
  }

  static formatDate(dateString) {
    if (!dateString) return '—';
    const d = new Date(dateString);
    return d.toLocaleDateString('en-NG', { day: '2-digit', month: 'short', year: 'numeric' })
      + ' ' + d.toLocaleTimeString('en-NG', { hour: '2-digit', minute: '2-digit' });
  }
}

/* ═══════════════════════════════════════════
   FORM SECURITY UTILITIES
   ═══════════════════════════════════════════ */
class FormSecurity {
  static generateCSRF() {
    const token = Array.from(crypto.getRandomValues(new Uint8Array(24)))
      .map(b => b.toString(16).padStart(2, '0')).join('');
    sessionStorage.setItem('la_csrf', token);
    return token;
  }

  static getCSRF() {
    return sessionStorage.getItem('la_csrf') || FormSecurity.generateCSRF();
  }

  static validateCSRF(formToken) {
    const stored = sessionStorage.getItem('la_csrf');
    return stored && stored === formToken;
  }

  static sanitize(str) {
    if (typeof str !== 'string') return '';
    return str
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#x27;')
      .replace(/\//g, '&#x2F;')
      .trim()
      .substring(0, 2000);
  }

  static isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email);
  }

  static isValidPhone(phone) {
    if (!phone) return true;
    return /^[\d\s+\-()]{7,20}$/.test(phone);
  }

  static checkRateLimit(key, maxAttempts, windowMs) {
    const now = Date.now();
    let stored; try { stored = JSON.parse(localStorage.getItem('la_rl_' + key) || '[]'); } catch { stored = []; }
    const recent = stored.filter(t => now - t < windowMs);
    if (recent.length >= maxAttempts) {
      const oldest = Math.min(...recent);
      const waitSecs = Math.ceil((windowMs - (now - oldest)) / 1000);
      return { allowed: false, waitSecs };
    }
    recent.push(now);
    localStorage.setItem('la_rl_' + key, JSON.stringify(recent.slice(-maxAttempts)));
    return { allowed: true };
  }

  static injectCSRFField(form) {
    let field = form.querySelector('.csrf-field');
    if (!field) {
      field = document.createElement('input');
      field.type = 'hidden';
      field.name = '_csrf';
      field.className = 'csrf-field';
      form.appendChild(field);
    }
    field.value = FormSecurity.getCSRF();
  }

  static validateForm(form, rules) {
    const errors = {};
    const data = {};

    for (const [fieldId, rule] of Object.entries(rules)) {
      const el = form.querySelector('#' + fieldId) || form.querySelector('[name="' + fieldId + '"]');
      const raw = el ? el.value : '';
      const val = FormSecurity.sanitize(raw);

      if (el) {
        el.classList.remove('valid', 'invalid');
        const errEl = form.querySelector('#err-' + fieldId);
        if (errEl) { errEl.textContent = ''; errEl.classList.remove('show'); }
      }

      if (rule.required && !val) {
        errors[fieldId] = rule.requiredMsg || 'This field is required.';
      } else if (rule.email && val && !FormSecurity.isValidEmail(val)) {
        errors[fieldId] = 'Please enter a valid email address.';
      } else if (rule.phone && val && !FormSecurity.isValidPhone(val)) {
        errors[fieldId] = 'Please enter a valid phone number.';
      } else if (rule.minLen && val.length < rule.minLen) {
        errors[fieldId] = `Minimum ${rule.minLen} characters required.`;
      } else if (rule.maxLen && val.length > rule.maxLen) {
        errors[fieldId] = `Maximum ${rule.maxLen} characters allowed.`;
      }

      data[fieldId] = val;

      if (el) {
        el.classList.add(errors[fieldId] ? 'invalid' : (val ? 'valid' : ''));
        const errEl = form.querySelector('#err-' + fieldId);
        if (errEl && errors[fieldId]) {
          errEl.textContent = errors[fieldId];
          errEl.classList.add('show');
        }
      }
    }

    return { valid: Object.keys(errors).length === 0, errors, data };
  }

  static initForms() {
    document.querySelectorAll('form').forEach(form => {
      FormSecurity.injectCSRFField(form);
    });
  }
}

document.addEventListener('DOMContentLoaded', () => {
  FormSecurity.initForms();
});
