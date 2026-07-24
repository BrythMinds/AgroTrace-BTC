// =============================================================
// AgroTrace BTC — interactions
// =============================================================
document.addEventListener('DOMContentLoaded', function () {

  // ---------- Menu mobile (hamburger) ----------
  var toggle = document.querySelector('.nav-toggle');
  var links  = document.querySelector('.navlinks');
  if (toggle && links) {
    toggle.addEventListener('click', function () {
      var isOpen = links.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
    links.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        links.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
      });
    });
  }

  // ---------- Fallback : initiales si photo d'équipe ne charge pas ----------
  document.querySelectorAll('.avatar img').forEach(function (img) {
    img.addEventListener('error', function () {
      img.style.display = 'none';
      var initials = img.closest('.avatar').querySelector('.initials');
      if (initials) initials.style.display = 'flex';
    });
  });

  // =========================================================
  //  ANIMATIONS AU SCROLL (fade + translateY)
  //  + ajout de la classe .hover-lift sur les cartes
  // =========================================================
  var prefersReduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // 1) On étiquette les éléments à animer
  // Sections : on anime leur .section-head
  document.querySelectorAll('section .section-head').forEach(function (el) {
    el.classList.add('reveal');
  });

  // Cartes à animer (cascadées) — on leur met .reveal + .hover-lift + data-delay
  function stagger(selector, base) {
    var nodes = document.querySelectorAll(selector);
    nodes.forEach(function (node, i) {
      node.classList.add('reveal', 'hover-lift');
      node.setAttribute('data-delay', String(i + 1));
    });
  }
  stagger('.problem-card', 1);
  stagger('.step', 1);
  stagger('.feature', 1);
  stagger('.member', 1);
  stagger('.stat', 1);

  // Bloc "cert" (pourquoi bitcoin) et "split" (pour qui)
  document.querySelectorAll('.cert, .split > div, .final-cta').forEach(function (el) {
    el.classList.add('reveal');
  });

  // 2) L'IntersectionObserver déclenche .is-visible
  if (!prefersReduce && 'IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          // On arrête d'observer une fois visible (one-shot)
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    document.querySelectorAll('.reveal').forEach(function (el) {
      io.observe(el);
    });
  } else {
    // Pas d'animation : on affiche tout
    document.querySelectorAll('.reveal').forEach(function (el) {
      el.classList.add('is-visible');
    });
  }

  // =========================================================
  //  COMPTEURS ANIMÉS (section .stats)
  //  Anime les nombres : 12, 3
  //  Les valeurs non-numériques (70/30, 1er) restent telles quelles.
  // =========================================================
  var statNums = document.querySelectorAll('.stat .num');
  statNums.forEach(function (el) {
    var raw = el.textContent.trim();
    // On ne tente l'animation que pour les valeurs purement numériques
    if (/^-?\d+(\.\d+)?$/.test(raw)) {
      var target = parseFloat(raw);
      el.setAttribute('data-target', String(target));
      el.textContent = '0';
    }
  });

  function animateCount(el) {
    var target   = parseFloat(el.getAttribute('data-target') || '0');
    var duration = 1400; // ms
    var startTs  = null;
    function step(ts) {
      if (!startTs) startTs = ts;
      var p = Math.min((ts - startTs) / duration, 1);
      // ease-out cubic
      var eased = 1 - Math.pow(1 - p, 3);
      var val = Math.floor(eased * target);
      el.textContent = val;
      if (p < 1) {
        requestAnimationFrame(step);
      } else {
        // valeur finale exacte
        el.textContent = String(target);
      }
    }
    requestAnimationFrame(step);
  }

  if (statNums.length && 'IntersectionObserver' in window && !prefersReduce) {
    var countIO = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          var el = entry.target;
          if (el.getAttribute('data-target') && !el.dataset.counted) {
            el.dataset.counted = '1';
            animateCount(el);
          }
          countIO.unobserve(el);
        }
      });
    }, { threshold: 0.4 });

    statNums.forEach(function (el) {
      if (el.getAttribute('data-target')) countIO.observe(el);
    });
  } else {
    // Fallback : on remet les valeurs d'origine
    statNums.forEach(function (el) {
      var t = el.getAttribute('data-target');
      if (t) el.textContent = t;
    });
  }
});
