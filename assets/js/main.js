document.addEventListener('DOMContentLoaded', function () {

  // ---------- Menu mobile (hamburger) ----------
  var navToggle = document.querySelector('.nav-toggle');
  var navLinks = document.querySelector('.navlinks');
  var body = document.body;

  if (navToggle && navLinks) {
    navToggle.addEventListener('click', function () {
      var isOpen = navLinks.classList.toggle('is-open');
      navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      // Empêche le défilement du body quand le menu mobile est ouvert
      if (isOpen) {
        body.style.overflow = 'hidden';
      } else {
        body.style.overflow = '';
      }
    });

    // Ferme le menu au clic sur un lien
    navLinks.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        closeMobileMenu();
      });
    });

    // Ferme le menu au clic en dehors
    document.addEventListener('click', function (e) {
      if (!navLinks.classList.contains('is-open')) return;
      if (navLinks.contains(e.target) || navToggle.contains(e.target)) return;
      closeMobileMenu();
    });

    // Ferme le menu avec Escape
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && navLinks.classList.contains('is-open')) {
        closeMobileMenu();
        navToggle.focus();
      }
    });

    // Ferme le menu mobile si on redimensionne au-dessus du breakpoint
    var resizeTimer;
    window.addEventListener('resize', function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function () {
        if (window.innerWidth > 760 && navLinks.classList.contains('is-open')) {
          closeMobileMenu();
        }
      }, 150);
    });
  }

  function closeMobileMenu() {
    if (!navLinks || !navToggle) return;
    navLinks.classList.remove('is-open');
    navToggle.setAttribute('aria-expanded', 'false');
    body.style.overflow = '';
  }

  // ---------- Fallback photo équipe manquante -> initiales ----------
  document.querySelectorAll('.avatar img').forEach(function (img) {
    img.addEventListener('error', function () {
      img.style.display = 'none';
      var initials = img.closest('.avatar').querySelector('.initials');
      if (initials) initials.style.display = 'flex';
    });
  });

  // ---------- Animations au scroll (reveal) ----------
  var revealEls = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window && revealEls.length) {
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    revealEls.forEach(function (el) { observer.observe(el); });
  } else {
    revealEls.forEach(function (el) { el.classList.add('is-visible'); });
  }

  // ---------- Toggle mode clair / sombre ----------
  var themeToggle = document.getElementById('themeToggle');
  var themeIcon = document.getElementById('themeIcon');
  var root = document.documentElement;

  function updateThemeIcon() {
    var current = root.getAttribute('data-theme');
    if (themeIcon) {
      themeIcon.className = current === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
    }
  }
  updateThemeIcon();

  if (themeToggle) {
    themeToggle.addEventListener('click', function () {
      var current = root.getAttribute('data-theme');
      var next = current === 'dark' ? 'light' : 'dark';
      root.setAttribute('data-theme', next);
      try { localStorage.setItem('agrotrace-theme', next); } catch (e) {}
      updateThemeIcon();
    });
  }

  // ---------- Simulateur d'investissement ----------
  var simInput = document.getElementById('simAmountInput');
  var simAmountDisplay = document.getElementById('simAmountDisplay');
  var simInputLabel = document.getElementById('simInputLabel');
  var simInputSuffix = document.getElementById('simInputSuffix');
  var simEquivLabel = document.getElementById('simEquivLabel');
  var simEquivValue = document.getElementById('simEquivValue');
  var simBtcValue = document.getElementById('simBtcValue');
  var simFee = document.getElementById('simFee');
  var simFund = document.getElementById('simFund');
  var simTranche = document.getElementById('simTranche');
  var simCoopBar = document.getElementById('simCoopBar');
  var simInvBar = document.getElementById('simInvBar');
  var presets = document.querySelectorAll('.simulator-preset');
  var currencyTabs = document.querySelectorAll('.currency-tab');

  var SATS_PER_FCFA = 6;
  var SATS_PER_BTC = 100000000;
  var currentUnit = 'fcfa';

  var presetValues = {
    fcfa: [10000, 50000, 150000, 300000],
    sats: [60000, 300000, 900000, 1800000]
  };

  function fmt(n) {
    return Math.round(n).toLocaleString('fr-FR');
  }

  function fmtBtc(sats) {
    var btc = sats / SATS_PER_BTC;
    var str = btc.toFixed(8).replace(/0+$/, '').replace(/\.$/, '');
    return (str === '' ? '0' : str) + ' BTC';
  }

  function updatePresetButtons() {
    var values = presetValues[currentUnit];
    presets.forEach(function (btn, i) {
      btn.dataset.amount = values[i];
      btn.textContent = fmt(values[i]);
    });
  }

  function updateSimulator(rawAmount) {
    if (!rawAmount || rawAmount < 0) rawAmount = 0;

    var amountFcfa, sats;
    if (currentUnit === 'fcfa') {
      amountFcfa = rawAmount;
      sats = amountFcfa * SATS_PER_FCFA;
    } else {
      sats = rawAmount;
      amountFcfa = sats / SATS_PER_FCFA;
    }

    var feeSats = sats * 0.02;
    var fundFcfa = amountFcfa * 0.03;
    var totalToRepay = amountFcfa * 1.08;
    var tranche = totalToRepay / 3;

    if (currentUnit === 'fcfa') {
      if (simAmountDisplay) simAmountDisplay.textContent = fmt(amountFcfa) + ' FCFA';
      if (simEquivLabel) simEquivLabel.textContent = 'Équivalent en sats (taux indicatif ×6)';
      if (simEquivValue) simEquivValue.textContent = fmt(sats) + ' sats';
    } else {
      if (simAmountDisplay) simAmountDisplay.textContent = fmt(sats) + ' sats';
      if (simEquivLabel) simEquivLabel.textContent = 'Équivalent en FCFA (XOF), taux indicatif';
      if (simEquivValue) simEquivValue.textContent = fmt(amountFcfa) + ' FCFA';
    }

    if (simBtcValue) simBtcValue.textContent = fmtBtc(sats);
    if (simFee) simFee.textContent = fmt(feeSats) + ' sats';
    if (simFund) simFund.textContent = fmt(fundFcfa) + ' FCFA';
    if (simTranche) simTranche.textContent = fmt(tranche) + ' FCFA';
    if (simCoopBar) simCoopBar.style.width = '70%';
    if (simInvBar) simInvBar.style.width = '30%';
  }

  function setActivePreset(amount) {
    presets.forEach(function (btn) {
      btn.classList.toggle('active', parseInt(btn.dataset.amount, 10) === amount);
    });
  }

  function switchUnit(unit) {
    currentUnit = unit;
    currencyTabs.forEach(function (tab) {
      var isActive = tab.dataset.currency === unit;
      tab.classList.toggle('active', isActive);
      tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });

    if (unit === 'fcfa') {
      if (simInputLabel) simInputLabel.textContent = 'Montant à investir';
      if (simInputSuffix) simInputSuffix.textContent = 'FCFA';
      if (simInput) { simInput.step = 1000; simInput.value = 50000; simInput.placeholder = 'Ex. 50000'; }
    } else {
      if (simInputLabel) simInputLabel.textContent = 'Montant à investir (en sats)';
      if (simInputSuffix) simInputSuffix.textContent = 'sats';
      if (simInput) { simInput.step = 1000; simInput.value = 300000; simInput.placeholder = 'Ex. 300000'; }
    }

    updatePresetButtons();
    updateSimulator(parseInt(simInput.value, 10) || 0);
    setActivePreset(parseInt(simInput.value, 10) || 0);
  }

  if (simInput) {
    updatePresetButtons();
    updateSimulator(parseInt(simInput.value, 10) || 0);

    simInput.addEventListener('input', function () {
      var amount = parseInt(simInput.value, 10) || 0;
      updateSimulator(amount);
      setActivePreset(amount);
    });

    presets.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var amount = parseInt(btn.dataset.amount, 10);
        simInput.value = amount;
        updateSimulator(amount);
        setActivePreset(amount);
      });
    });

    currencyTabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        switchUnit(tab.dataset.currency);
      });
    });
  }

  // ---------- Smooth scroll avec compensation de la nav sticky ----------
  document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
    anchor.addEventListener('click', function (e) {
      var targetId = this.getAttribute('href');
      if (targetId === '#' || targetId.length < 2) return;
      var target = document.querySelector(targetId);
      if (!target) return;
      e.preventDefault();
      var navHeight = 64;
      if (window.innerWidth >= 761) navHeight = 68;
      var targetPosition = target.getBoundingClientRect().top + window.pageYOffset - navHeight - 12;
      window.scrollTo({ top: targetPosition, behavior: 'smooth' });
    });
  });

  // ---------- GitHub stars en temps réel (cache + fetch live) ----------
  var GH_REPO = 'BrythMinds/AgroTrace-BTC';
  var CACHE_KEY = 'agrotrace-gh-stars-v1';
  var starsEl = document.getElementById('gh-stars');
  var metaEl  = document.getElementById('gh-meta');
  var cardEl  = document.getElementById('gh-card');

  if (starsEl && cardEl) {
    function timeAgo(iso) {
      var d = (Date.now() - new Date(iso).getTime()) / 1000;
      if (d < 60)        return "à l'instant";
      if (d < 3600)      return 'il y a ' + Math.floor(d / 60) + ' min';
      if (d < 86400)     return 'il y a ' + Math.floor(d / 3600) + ' h';
      if (d < 2592000)   return 'il y a ' + Math.floor(d / 86400) + ' j';
      if (d < 31536000)  return 'il y a ' + Math.floor(d / 2592000) + ' mois';
      return 'il y a ' + Math.floor(d / 31536000) + ' an(s)';
    }

    function fmtStars(n) {
      if (n >= 1000) return (n / 1000).toFixed(n >= 10000 ? 0 : 1).replace(/\.0$/, '') + 'k';
      return String(n);
    }

    function renderGh(data) {
      if (starsEl) starsEl.textContent = fmtStars(data.stars || 0);
      if (metaEl) {
        var parts = ['BrythMinds / AgroTrace-BTC'];
        if (data.language)   parts.push(data.language);
        if (data.license)    parts.push(data.license);
        if (data.updatedAgo) parts.push('maj ' + data.updatedAgo);
        metaEl.textContent = parts.join(' · ');
      }
    }

    // 1) Affichage immédiat depuis le cache localStorage
    var cached = null;
    try {
      var raw = localStorage.getItem(CACHE_KEY);
      if (raw) {
        var parsed = JSON.parse(raw);
        if (parsed && parsed.data) {
          cached = parsed.data;
          renderGh(cached);
        }
      }
    } catch (e) { /* cache indisponible */ }

    // 2) Fetch live (toujours, pour rafraîchir en arrière-plan)
    fetch('https://api.github.com/repos/' + GH_REPO)
      .then(function (r) {
        // Distingue rate limit (GitHub répond 403 quand X-RateLimit-Remaining=0)
        // d'une vraie erreur réseau ou d'un repo inexistant.
        if (r.status === 403 || r.status === 429) {
          return { rateLimited: true };
        }
        if (!r.ok) {
          return null;
        }
        return r.json();
      })
      .then(function (j) {
        if (!j) {
          if (!cached && starsEl) starsEl.textContent = '—';
          return;
        }
        if (j.rateLimited) {
          // Quota GitHub atteint (60 req/h sans auth) : on garde le cache s'il existe,
          // sinon on affiche un tiret — sans spammer l'API au prochain refresh.
          if (!cached && starsEl) starsEl.textContent = '—';
          return;
        }
        var data = {
          stars:      j.stargazers_count || 0,
          language:   j.language || '',
          license:    j.license && j.license.spdx_id ? j.license.spdx_id : '',
          updatedAgo: j.pushed_at ? timeAgo(j.pushed_at) : ''
        };
        renderGh(data);
        try { localStorage.setItem(CACHE_KEY, JSON.stringify({ ts: Date.now(), data: data })); } catch (e) {}
      })
      .catch(function () {
        if (!cached && starsEl) starsEl.textContent = '—';
      });
  }

  // ---------- Bouton "retour en haut" + barre de progression ----------
  var backToTop = document.getElementById('backToTop');
  var scrollProgress = document.getElementById('scrollProgress');

  // Une seule écoute scroll, un seul rAF, qui pilote les deux indicateurs
  if (backToTop || scrollProgress) {
    var THRESHOLD = 400; // px scrollés avant l'apparition du bouton
    var ticking = false;

    function updateScrollIndicators() {
      var doc = document.documentElement;
      var scrollTop = window.pageYOffset || doc.scrollTop;
      var scrollHeight = doc.scrollHeight - doc.clientHeight;
      var ratio = scrollHeight > 0 ? Math.min(Math.max(scrollTop / scrollHeight, 0), 1) : 0;

      if (backToTop) {
        if (scrollTop > THRESHOLD) {
          backToTop.classList.add('is-visible');
        } else {
          backToTop.classList.remove('is-visible');
        }
      }

      if (scrollProgress) {
        // ScaleX animé par CSS via la transition ; on update la valeur cible ici.
        scrollProgress.style.transform = 'scaleX(' + ratio + ')';
      }

      ticking = false;
    }

    window.addEventListener('scroll', function () {
      if (!ticking) {
        window.requestAnimationFrame(updateScrollIndicators);
        ticking = true;
      }
    }, { passive: true });

    // État initial (utile si la page est chargée déjà scrollée, ex. back/forward cache)
    updateScrollIndicators();

    // Recalcule au resize (la hauteur scrollable change avec la largeur)
    var resizeTimer;
    window.addEventListener('resize', function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(updateScrollIndicators, 100);
    });

    if (backToTop) {
      backToTop.addEventListener('click', function () {
        // Respecte scroll-behavior:smooth déjà défini sur <html>
        try {
          window.scrollTo({ top: 0, behavior: 'smooth' });
        } catch (e) {
          window.scrollTo(0, 0);
        }
      });
    }
  }

});
