(() => {
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const header = document.querySelector('.site-header');
  const progress = document.querySelector('.scroll-progress');
  const menuButton = document.querySelector('.menu-toggle');
  const mobilePanel = document.querySelector('.mobile-panel');

  if (!reducedMotion && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
    const cursorGlow = document.createElement('div');
    cursorGlow.className = 'cursor-glow';
    cursorGlow.setAttribute('aria-hidden', 'true');
    document.body.prepend(cursorGlow);

    let pointerX = window.innerWidth / 2;
    let pointerY = window.innerHeight / 2;
    let glowX = pointerX;
    let glowY = pointerY;

    window.addEventListener('mousemove', event => {
      pointerX = event.clientX;
      pointerY = event.clientY;
    }, { passive: true });

    const moveGlow = () => {
      glowX += (pointerX - glowX) * .12;
      glowY += (pointerY - glowY) * .12;
      cursorGlow.style.transform = `translate3d(${glowX}px, ${glowY}px, 0) translate(-50%, -50%)`;
      requestAnimationFrame(moveGlow);
    };

    requestAnimationFrame(moveGlow);
  }
  const closeMenu = () => {
    document.body.classList.remove('menu-open');
    menuButton?.setAttribute('aria-expanded', 'false');
    mobilePanel?.setAttribute('aria-hidden', 'true');
  };

  menuButton?.addEventListener('click', () => {
    const isOpen = document.body.classList.toggle('menu-open');
    menuButton.setAttribute('aria-expanded', String(isOpen));
    mobilePanel?.setAttribute('aria-hidden', String(!isOpen));
  });

  mobilePanel?.querySelectorAll('a').forEach(link => link.addEventListener('click', closeMenu));
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') closeMenu();
  });

  let ticking = false;
  const updateScroll = () => {
    const y = window.scrollY;
    const scrollable = document.documentElement.scrollHeight - window.innerHeight;
    header?.classList.toggle('is-scrolled', y > 24);
    if (progress) progress.style.width = `${scrollable > 0 ? (y / scrollable) * 100 : 0}%`;
    ticking = false;
  };

  window.addEventListener('scroll', () => {
    if (!ticking) {
      requestAnimationFrame(updateScroll);
      ticking = true;
    }
  }, { passive: true });
  updateScroll();

  const revealItems = document.querySelectorAll('.reveal');
  if (reducedMotion || !('IntersectionObserver' in window)) {
    revealItems.forEach(item => item.classList.add('is-visible'));
  } else {
    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: .12, rootMargin: '0px 0px -45px' });
    revealItems.forEach(item => observer.observe(item));
  }

  document.querySelectorAll('.faq details').forEach(item => {
    item.addEventListener('toggle', () => {
      if (!item.open) return;
      document.querySelectorAll('.faq details[open]').forEach(other => {
        if (other !== item) other.removeAttribute('open');
      });
    });
  });

  const form = document.querySelector('#contact-form');
  form?.addEventListener('submit', event => {
    event.preventDefault();
    if (!form.reportValidity()) return;

    const data = new FormData(form);
    const message = [
      'Olá! Vim pelo site da Dev.Stich e gostaria de conversar sobre um projeto.',
      '',
      `Nome: ${data.get('nome')}`,
      `Contato: ${data.get('contato')}`,
      `Projeto: ${data.get('projeto')}`,
      `Ideia: ${data.get('mensagem') || 'Prefiro explicar na conversa.'}`
    ].join('\n');

    const button = form.querySelector('button[type="submit"]');
    if (button) button.textContent = 'Abrindo o WhatsApp…';
    const whatsappUrl = `https://wa.me/5531990825833?text=${encodeURIComponent(message)}`;
    window.open(whatsappUrl, '_blank', 'noopener,noreferrer');
    setTimeout(() => {
      if (button) button.textContent = 'Enviar pelo WhatsApp ↗';
    }, 1200);
  });

  const year = document.querySelector('[data-year]');
  if (year) year.textContent = new Date().getFullYear();

  if (!reducedMotion && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
    document.querySelectorAll('[data-magnetic]').forEach(element => {
      element.addEventListener('mousemove', event => {
        const rect = element.getBoundingClientRect();
        const x = (event.clientX - rect.left - rect.width / 2) * .12;
        const y = (event.clientY - rect.top - rect.height / 2) * .16;
        element.style.transform = `translate3d(${x}px, ${y}px, 0)`;
      });
      element.addEventListener('mouseleave', () => {
        element.style.transform = '';
      });
    });
  }
})();


