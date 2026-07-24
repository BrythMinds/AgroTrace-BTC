// =============================================================
// AgroTrace BTC — interactions
// =============================================================
document.addEventListener('DOMContentLoaded', function () {

  var prefersReduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

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
  //  ANIMATIONS AU SCROLL — variantes reveal
  // =========================================================

  // .section-head : on alterne fade-up / fade-left / fade-right
  // en fonction de la parité de la section, pour casser la monotonie
  var sectionHeads = document.querySelectorAll('section .section-head');
  sectionHeads.forEach(function (el, i) {
    el.classList.add('reveal');
    // rien d'autre à faire — fade-up par défaut
    // (si tu veux alterner, décommente ci-dessous) :
    // var v = i % 3;
    // el.classList.remove('reveal');
    // el.classList.add(v === 0 ? 'reveal' : (v === 1 ? 'reveal-left' : 'reveal-right'));
  });

  // helper : étiquette plusieurs éléments en cascade (reveal + hover-lift + data-delay)
  function stagger(selector, variant) {
    var nodes = document.querySelectorAll(selector);
    nodes.forEach(function (node, i) {
      node.classList.add('hover-lift');
      node.classList.add(variant || 'reveal');
      node.setAttribute('data-delay', String(i + 1));
    });
  }

  // Cartes — alternance de variantes par groupe pour varier
  stagger('.problem-card', 'reveal-left');
  stagger('.step',         'reveal-zoom');
  stagger('.feature',      'reveal');          // fade-up classique
  stagger('.member',       'reveal-zoom');

  // stats : on les anime avec un léger fade-right échelonné
  stagger('.stat', 'reveal-right');

  // Blocs spéciaux
  document.querySelectorAll('.cert').forEach(function (el) { el.classList.add('reveal-zoom'); });
  document.querySelectorAll('.split > div').forEach(function (el, i) {
    el.classList.add(i === 0 ? 'reveal-left' : 'reveal-right');
  });
  document.querySelectorAll('.final-cta').forEach(function (el) { el.classList.add('reveal-zoom'); });

  // ---------- IntersectionObserver (one-shot) ----------
  if (!prefersReduce && 'IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-zoom')
      .forEach(function (el) { io.observe(el); });
  } else {
    document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-zoom')
      .forEach(function (el) { el.classList.add('is-visible'); });
  }

  // =========================================================
  //  SCROLL PROGRESS BAR
  // =========================================================
  var progressBar = document.querySelector('.scroll-progress');
  if (progressBar) {
    var ticking = false;
    function updateProgress() {
      var doc = document.documentElement;
      var max = (doc.scrollHeight - doc.clientHeight) || 1;
      var pct = Math.min(100, Math.max(0, (window.scrollY / max) * 100));
      progressBar.style.width = pct + '%';
      ticking = false;
    }
    window.addEventListener('scroll', function () {
      if (!ticking) {
        window.requestAnimationFrame(updateProgress);
        ticking = true;
      }
    }, { passive: true });
    updateProgress();
  }

  // =========================================================
  //  PARALLAXE HERO (le contenu descend plus lentement que le scroll)
  // =========================================================
  if (!prefersReduce) {
    var heroContent = document.querySelector('.hero-content');
    var hero = document.querySelector('.hero');
    if (heroContent && hero) {
      var pTicking = false;
      function updateParallax() {
        var rect = hero.getBoundingClientRect();
        // Tant que le hero est visible (rect.bottom > 0), on translate
        if (rect.bottom > 0) {
          var offset = Math.max(-200, Math.min(200, window.scrollY * 0.18));
          heroContent.style.transform = 'translateY(' + offset.toFixed(1) + 'px)';
        }
        pTicking = false;
      }
      window.addEventListener('scroll', function () {
        if (!pTicking) {
          window.requestAnimationFrame(updateParallax);
          pTicking = true;
        }
      }, { passive: true });
    }
  }

  // =========================================================
  //  CURSOR GLOW (hero)
  // =========================================================
  if (!prefersReduce) {
    var heroEl = document.querySelector('.hero');
    var heroGlow = document.querySelector('.hero-glow');
    if (heroEl && heroGlow && window.matchMedia('(min-width: 761px)').matches) {
      heroEl.addEventListener('mousemove', function (e) {
        var rect = heroEl.getBoundingClientRect();
        var x = ((e.clientX - rect.left) / rect.width)  * 100;
        var y = ((e.clientY - rect.top)  / rect.height) * 100;
        heroGlow.style.setProperty('--mx', x + '%');
        heroGlow.style.setProperty('--my', y + '%');
      });
      heroEl.addEventListener('mouseleave', function () {
        heroGlow.style.setProperty('--mx', '50%');
        heroGlow.style.setProperty('--my', '50%');
      });
    }
  }

  // =========================================================
  //  TYPEWRITER (eyebrow du hero)
  // =========================================================
  if (!prefersReduce) {
    var tw = document.querySelector('[data-typewriter]');
    if (tw) {
      var text = tw.textContent;
      tw.textContent = '';
      tw.classList.add('is-typing');
      var i = 0;
      var speed = 32; // ms par caractère
      function typeNext() {
        if (i <= text.length) {
          tw.textContent = text.slice(0, i);
          i++;
          setTimeout(typeNext, speed);
        } else {
          // clignote encore un peu puis enlève le curseur
          setTimeout(function () { tw.classList.remove('is-typing'); }, 1400);
        }
      }
      // Petit délai pour que l'animation hero ait démarré
      setTimeout(typeNext, 500);
    }
  }

  // =========================================================
  //  BOUTONS MAGNÉTIQUES (.btn-primary dans le hero et le final-cta)
  // =========================================================
  if (!prefersReduce) {
    document.querySelectorAll('.btn-primary').forEach(function (btn) {
      btn.addEventListener('mousemove', function (e) {
        var rect = btn.getBoundingClientRect();
        var x = e.clientX - (rect.left + rect.width  / 2);
        var y = e.clientY - (rect.top  + rect.height / 2);
        // intensité divisée par 6 pour rester subtil
        btn.style.transform = 'translate(' + (x / 6).toFixed(1) + 'px,' + (y / 6).toFixed(1) + 'px)';
      });
      btn.addEventListener('mouseleave', function () {
        btn.style.transform = '';
      });
    });
  }

  // =========================================================
  //  COMPTEURS ANIMÉS + PULSE FINAL
  // =========================================================
  var statNums = document.querySelectorAll('.stat .num');
  statNums.forEach(function (el) {
    var raw = el.textContent.trim();
    if (/^-?\d+(\.\d+)?$/.test(raw)) {
      var target = parseFloat(raw);
      el.setAttribute('data-target', String(target));
      el.textContent = '0';
    }
  });

  function animateCount(el) {
    var target   = parseFloat(el.getAttribute('data-target') || '0');
    var duration = 1400;
    var startTs  = null;
    function step(ts) {
      if (!startTs) startTs = ts;
      var p = Math.min((ts - startTs) / duration, 1);
      var eased = 1 - Math.pow(1 - p, 3);
      el.textContent = String(Math.floor(eased * target));
      if (p < 1) {
        requestAnimationFrame(step);
      } else {
        el.textContent = String(target);
        // Pulse final : on retire puis on remet la classe pour rejouer l'anim
        el.classList.remove('pulse');
        // force reflow
        void el.offsetWidth;
        el.classList.add('pulse');
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
    statNums.forEach(function (el) {
      var t = el.getAttribute('data-target');
      if (t) el.textContent = t;
    });
  }
});
