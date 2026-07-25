document.addEventListener('DOMContentLoaded', function () {

  // ---------- Menu mobile (hamburger) ----------
  var navToggle = document.querySelector('.nav-toggle');
  var navLinks = document.querySelector('.navlinks');

  if (navToggle && navLinks) {
    navToggle.addEventListener('click', function () {
      var isOpen = navLinks.classList.toggle('is-open');
      navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    navLinks.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        navLinks.classList.remove('is-open');
        navToggle.setAttribute('aria-expanded', 'false');
      });
    });
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
    }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

    revealEls.forEach(function (el) { observer.observe(el); });
  } else {
    // Pas de support IntersectionObserver : on affiche tout directement
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

  var SATS_PER_FCFA = 6;          // taux indicatif de la plateforme
  var SATS_PER_BTC = 100000000;
  var currentUnit = 'fcfa';        // 'fcfa' ou 'sats'

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

    var feeSats = sats * 0.02;                // frais de service 2%
    var fundFcfa = amountFcfa * 0.03;         // fonds d'indemnisation 3%
    var totalToRepay = amountFcfa * 1.08;     // formule réelle de l'app (target * 1.08)
    var tranche = totalToRepay / 3;           // remboursement en 3 tranches

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
    // La barre 70/30 reste fixe (modèle de partage, pas fonction du montant)
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

});
