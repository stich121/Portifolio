(() => {
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const header = document.querySelector('.site-header');
  const progress = document.querySelector('.scroll-progress');
  const menuButton = document.querySelector('.menu-toggle');
  const mobilePanel = document.querySelector('.mobile-panel');

  if (!document.querySelector('[data-final-hero]') && !reducedMotion && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
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

  const finalHero = document.querySelector('[data-final-hero]');

  if (finalHero) {
    const stage = finalHero.querySelector('.hero-final__stage');
    const fx = finalHero.querySelector('.hero-final__fx');
    const titleEl = finalHero.querySelector('.hero-final__title');
    const subtitleEl = finalHero.querySelector('.hero-final__subtitle');
    const innerEl = finalHero.querySelector('.hero-final__inner');
    const cursorDot = finalHero.querySelector('.hero-final__cursor');
    const topImage = new Image();
    const mask = document.createElement('canvas');
    const ctx = stage?.getContext('2d');
    const mctx = mask.getContext('2d');
    const fxctx = fx?.getContext('2d');
    const isTouch = window.matchMedia('(hover: none), (pointer: coarse)').matches;
    const canHover = !isTouch && window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    const titleText = finalHero.dataset.title || 'Matheus Dias';
    const words = (finalHero.dataset.subtitle || '').split('|').map(item => item.trim()).filter(Boolean);
    const pointer = { x: 0, y: 0, px: 0, py: 0, active: false, speed: 0 };
    const particles = [];
    const ripples = [];
    const letters = [];
    const chars = '!<>-_\\/[]{}=+*^?#________ABCDEF0123456789';
    let wordIndex = 0;
    let scrambling = false;
    let width = 0;
    let height = 0;
    let dpr = 1;
    let hintT = Math.random() * 100;
    let topReady = false;

    const setCanvasSize = canvas => {
      canvas.width = Math.max(1, Math.round(width * dpr));
      canvas.height = Math.max(1, Math.round(height * dpr));
      canvas.style.width = `${width}px`;
      canvas.style.height = `${height}px`;
    };

    const resizeFinalHero = () => {
      const rect = finalHero.getBoundingClientRect();
      width = Math.max(1, Math.round(rect.width));
      height = Math.max(1, Math.round(rect.height));
      dpr = Math.min(window.devicePixelRatio || 1, isTouch ? 1.5 : 2);
      [stage, fx, mask].forEach(setCanvasSize);
      ctx?.setTransform(dpr, 0, 0, dpr, 0, 0);
      mctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      fxctx?.setTransform(dpr, 0, 0, dpr, 0, 0);
      pointer.x = width / 2;
      pointer.y = height / 2;
      pointer.px = pointer.x;
      pointer.py = pointer.y;
    };

    const buildTitle = () => {
      if (!titleEl) return;
      titleEl.textContent = '';
      titleEl.setAttribute('aria-label', titleText);
      letters.length = 0;
      [...titleText].forEach(char => {
        const span = document.createElement('span');
        span.className = char === ' ' ? 'ltr space' : 'ltr';
        span.setAttribute('aria-hidden', 'true');
        span.dataset.char = char;
        span.innerHTML = char === ' ' ? '&nbsp;' : char;
        titleEl.appendChild(span);
        if (char !== ' ') letters.push(span);
      });
      requestAnimationFrame(() => {
        titleEl.querySelectorAll('.ltr').forEach((letter, index) => {
          letter.style.transitionDelay = `${index * 45}ms`;
          letter.classList.add('in');
        });
        setTimeout(() => titleEl.querySelectorAll('.ltr').forEach(letter => { letter.style.transitionDelay = '0ms'; }), 1500);
      });
    };

    const setWord = word => {
      if (subtitleEl) subtitleEl.innerHTML = `<span class="word">${word || ''}</span>`;
    };

    const rotateWord = () => {
      if (words.length < 2 || !subtitleEl) return;
      const current = subtitleEl.querySelector('.word');
      if (current) {
        current.style.opacity = '0';
        current.style.transform = 'translateY(8px)';
      }
      setTimeout(() => {
        wordIndex = (wordIndex + 1) % words.length;
        setWord(words[wordIndex]);
      }, 360);
    };

    const scrambleTitle = () => {
      if (scrambling || reducedMotion || !letters.length) return;
      scrambling = true;
      const targets = letters.map(letter => letter.dataset.char || '');
      let frame = 0;
      const total = 26;
      const tick = () => {
        letters.forEach((letter, index) => {
          const settle = total - index * 1.1;
          if (frame >= settle) letter.textContent = targets[index];
          else if (frame > settle - 8) letter.textContent = chars[(Math.random() * chars.length) | 0];
        });
        frame++;
        if (frame <= total + letters.length) requestAnimationFrame(tick);
        else {
          letters.forEach((letter, index) => { letter.textContent = targets[index]; });
          scrambling = false;
        }
      };
      tick();
    };

    const drawCover = (context, image) => {
      if (!image?.naturalWidth || !image?.naturalHeight) return;
      const imageRatio = image.naturalWidth / image.naturalHeight;
      const screenRatio = width / height;
      let drawWidth;
      let drawHeight;
      let x;
      let y;
      if (imageRatio > screenRatio) {
        drawHeight = height;
        drawWidth = height * imageRatio;
        x = (width - drawWidth) / 2;
        y = 0;
      } else {
        drawWidth = width;
        drawHeight = width / imageRatio;
        x = 0;
        y = (height - drawHeight) / 2;
      }
      context.drawImage(image, x, y, drawWidth, drawHeight);
    };

    const stampMask = (x, y) => {
      const radius = 271 / 2;
      const gradient = mctx.createRadialGradient(x, y, radius * .02, x, y, radius);
      gradient.addColorStop(0, 'rgba(0,0,0,1)');
      gradient.addColorStop(1, 'rgba(0,0,0,0)');
      mctx.globalCompositeOperation = 'source-over';
      mctx.fillStyle = gradient;
      for (let i = 0; i < 3; i++) {
        const angle = Math.random() * Math.PI * 2;
        const distance = Math.random() * radius * .3;
        mctx.beginPath();
        mctx.arc(x + Math.cos(angle) * distance, y + Math.sin(angle) * distance, radius * (.7 + Math.random() * .35), 0, Math.PI * 2);
        mctx.fill();
      }
    };

    const fadeMask = () => {
      mctx.globalCompositeOperation = 'destination-out';
      mctx.fillStyle = 'rgba(0,0,0,.02)';
      mctx.fillRect(0, 0, width, height);
      mctx.globalCompositeOperation = 'source-over';
    };

    const spawnParticles = (x, y, amount) => {
      if (reducedMotion) return;
      for (let i = 0; i < amount; i++) {
        const angle = Math.random() * Math.PI * 2;
        const speed = Math.random() * 1.6 + .3;
        particles.push({ x, y, vx: Math.cos(angle) * speed, vy: Math.sin(angle) * speed - .3, life: 1, r: Math.random() * 2.4 + .8 });
      }
    };

    const drawFx = () => {
      if (!fxctx) return;
      fxctx.clearRect(0, 0, width, height);
      for (let i = particles.length - 1; i >= 0; i--) {
        const particle = particles[i];
        particle.x += particle.vx;
        particle.y += particle.vy;
        particle.vy += .02;
        particle.life -= .02;
        if (particle.life <= 0) {
          particles.splice(i, 1);
          continue;
        }
        fxctx.globalAlpha = particle.life * .9;
        fxctx.fillStyle = '#fff';
        fxctx.beginPath();
        fxctx.arc(particle.x, particle.y, particle.r * particle.life, 0, Math.PI * 2);
        fxctx.fill();
      }
      fxctx.globalAlpha = 1;

      for (let i = ripples.length - 1; i >= 0; i--) {
        const ripple = ripples[i];
        ripple.radius += 5.6;
        ripple.life -= .028;
        if (ripple.life <= 0) {
          ripples.splice(i, 1);
          continue;
        }
        fxctx.strokeStyle = `rgba(255,255,255,${ripple.life * .45})`;
        fxctx.lineWidth = 1;
        fxctx.beginPath();
        fxctx.arc(ripple.x, ripple.y, ripple.radius, 0, Math.PI * 2);
        fxctx.stroke();
      }
    };

    const renderFinalHero = () => {
      if (!ctx || !topReady) {
        requestAnimationFrame(renderFinalHero);
        return;
      }

      fadeMask();
      const idle = !pointer.active;
      if (idle) {
        hintT += .012;
        const x = width / 2 + Math.cos(hintT) * Math.min(width * .18, 210);
        const y = height / 2 + Math.sin(hintT * 1.3) * Math.min(height * .14, 120);
        stampMask(x, y);
      } else {
        stampMask(pointer.x, pointer.y);
      }

      ctx.clearRect(0, 0, width, height);
      drawCover(ctx, topImage);
      ctx.globalCompositeOperation = 'destination-in';
      ctx.drawImage(mask, 0, 0, width, height);
      ctx.globalCompositeOperation = 'source-over';
      drawFx();
      requestAnimationFrame(renderFinalHero);
    };

    const setPointer = event => {
      const rect = finalHero.getBoundingClientRect();
      const x = Math.min(Math.max(event.clientX - rect.left, 0), width);
      const y = Math.min(Math.max(event.clientY - rect.top, 0), height);
      pointer.speed = Math.hypot(x - pointer.px, y - pointer.py);
      pointer.px = pointer.x;
      pointer.py = pointer.y;
      pointer.x = x;
      pointer.y = y;
      pointer.active = true;
      if (cursorDot && canHover) {
        cursorDot.style.left = `${event.clientX}px`;
        cursorDot.style.top = `${event.clientY}px`;
      }
      if (pointer.speed > 7) spawnParticles(x, y, 1);
    };

    buildTitle();
    setWord(words[0] || '');
    setInterval(rotateWord, 2600);
    titleEl?.addEventListener('pointerenter', scrambleTitle);
    finalHero.addEventListener('pointermove', setPointer, { passive: true });
    finalHero.addEventListener('pointerenter', event => {
      finalHero.classList.add('is-pointing');
      setPointer(event);
    }, { passive: true });
    finalHero.addEventListener('pointerleave', () => {
      pointer.active = false;
      finalHero.classList.remove('is-pointing');
    });
    finalHero.addEventListener('click', event => {
      const rect = finalHero.getBoundingClientRect();
      ripples.push({ x: event.clientX - rect.left, y: event.clientY - rect.top, radius: 12, life: 1 });
      scrambleTitle();
    });
    finalHero.addEventListener('pointerdown', event => {
      const rect = finalHero.getBoundingClientRect();
      ripples.push({ x: event.clientX - rect.left, y: event.clientY - rect.top, radius: 8, life: 1 });
    }, { passive: true });
    finalHero.querySelectorAll('a, button').forEach(element => {
      element.addEventListener('pointerenter', () => cursorDot?.classList.add('mag'));
      element.addEventListener('pointerleave', () => cursorDot?.classList.remove('mag'));
    });

    if (!reducedMotion && innerEl && canHover) {
      finalHero.addEventListener('pointermove', event => {
        const rect = finalHero.getBoundingClientRect();
        const rx = ((event.clientX - rect.left) / width - .5) * 2;
        const ry = ((event.clientY - rect.top) / height - .5) * 2;
        innerEl.style.transform = `translate(${rx * 8}px, ${ry * 8}px) rotateX(${-ry * 4}deg) rotateY(${rx * 4}deg)`;
      }, { passive: true });
      finalHero.addEventListener('pointerleave', () => { innerEl.style.transform = ''; });
    }

    window.addEventListener('resize', resizeFinalHero, { passive: true });
    resizeFinalHero();
    topImage.onload = () => {
      topReady = true;
      requestAnimationFrame(renderFinalHero);
    };
    topImage.src = finalHero.dataset.topSrc || 'hero-reveal-top.jpg';
  }
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

  if (!document.querySelector('[data-final-hero]') && !reducedMotion && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
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
