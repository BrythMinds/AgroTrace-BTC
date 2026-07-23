// Menu mobile (hamburger)
document.addEventListener('DOMContentLoaded', function () {
  var toggle = document.querySelector('.nav-toggle');
  var links = document.querySelector('.navlinks');

  if (toggle && links) {
    toggle.addEventListener('click', function () {
      var isOpen = links.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    // Ferme le menu après avoir cliqué un lien (mobile)
    links.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        links.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
      });
    });
  }

  // Fallback : si une photo d'équipe est manquante ou ne charge pas,
  // affiche les initiales à la place (défini via data-initials sur .avatar)
  document.querySelectorAll('.avatar img').forEach(function (img) {
    img.addEventListener('error', function () {
      img.style.display = 'none';
      var initials = img.closest('.avatar').querySelector('.initials');
      if (initials) initials.style.display = 'flex';
    });
  });
});
