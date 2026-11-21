'use strict';
// =====================================================================
// Motion. Everything here is enhancement: with reduced motion (or no JS)
// the page is complete and still. html.motion is set in <head>.
// Loaded after site.js and shares its globals ($$, sbar, sbtn, fq).
// =====================================================================
(() => {
  const root = document.documentElement;
  const motion = root.classList.contains('motion');
  window.__motion = true;

  /** Wrap each word of el's text in <span class="w"> (keeps links and Tamil clusters intact). */
  const splitWords = el => {
    let i = 0;
    const walk = node => [...node.childNodes].forEach(n => {
      if (n.nodeType === 1) return walk(n);
      if (n.nodeType !== 3 || !n.textContent.trim()) return;
      const frag = document.createDocumentFragment();
      n.textContent.split(/(\s+)/).forEach(part => {
        if (!part) return;
        if (/^\s+$/.test(part)) return frag.append(part);
        const s = document.createElement('span');
        s.className = 'w'; s.style.setProperty('--i', i++); s.textContent = part;
        frag.append(s);
      });
      n.replaceWith(frag);
    });
    walk(el);
    return i;
  };

  // ---- 1. Page-load moment: the page's main headline sets in, then its supporting text
  if (motion) {
    $$('.phead, .article-head').forEach(stage => {
      const h = stage.querySelector('h1');
      if (!h) { stage.classList.add('set-done'); return; }
      const original = h.innerHTML;
      const n = splitWords(h);
      // a timer, not rAF: animation frames can be held back while a page transition runs
      setTimeout(() => {
        h.classList.add('set');
        setTimeout(() => stage.classList.add('set-done'), Math.min(n, 10) * 55 + 250);
        // Hand the headline back as plain text so underlines and selection behave normally
        setTimeout(() => { h.innerHTML = original; h.classList.toggle('set', false); }, n * 55 + 1100);
      }, 40);
    });
  }

  // ---- 2. The blood helpline as a counter + the band's pulse line
  const helpNum = document.querySelector('.blood-band .blood-number');
  if (helpNum) {
    const label = helpNum.querySelector('span');
    const textNodes = [...helpNum.childNodes].filter(n => n.nodeType === 3);
    const text = textNodes.map(n => n.textContent).join('').trim();
    helpNum.setAttribute('aria-label', (label ? label.textContent + ' ' : '') + text);
    textNodes.forEach(n => n.replaceWith(''));
    const odo = document.createElement('span');
    odo.className = 'odo'; odo.setAttribute('aria-hidden', 'true');
    let k = 0;
    for (const ch of text) {
      if (!/\d/.test(ch)) { odo.append(ch === ' ' ? ' ' : ch); continue; }
      const d = document.createElement('span'); d.className = 'odo-d';
      const strip = document.createElement('span');
      // two full turns of 0–9, then land on the digit: reads as a counter, not a slot machine
      for (let r = 0; r < 2; r++) for (let x = 0; x <= 9; x++) { const c = document.createElement('span'); c.textContent = x; strip.append(c); }
      const c = document.createElement('span'); c.textContent = ch; strip.append(c);
      strip.style.setProperty('--d', 20); d.style.setProperty('--i', k++);
      d.append(strip); odo.append(d);
    }
    helpNum.append(odo);
    helpNum.closest('.blood-band').insertAdjacentHTML('beforeend',
      '<svg class="pulse" viewBox="0 0 1200 56" preserveAspectRatio="none" aria-hidden="true"><path pathLength="1" fill="none" stroke="currentColor" stroke-width="1.5" vector-effect="non-scaling-stroke" d="M0 40H520l14-14 12 26 16-50 14 44 10-6h614"/></svg>');
  }

  // count-up for the activity index numbers
  const countUp = el => {
    const to = parseInt(el.textContent, 10); if (!to) return;
    const t0 = performance.now(), dur = 900;
    const tick = t => { const p = Math.min(1, (t - t0) / dur); el.textContent = Math.round(to * (1 - Math.pow(1 - p, 3))); if (p < 1) requestAnimationFrame(tick); };
    el.textContent = '0'; requestAnimationFrame(tick);
  };

  // ---- 3. Scroll reveals: rules draw, photographs develop, the number rolls
  const revealSel = '.lead-img, .rule-h, .sect-head, .tile-img, .report-img, .feature img, .principals .portrait, .blood-band, .topics';
  const reveal = el => {
    el.classList.add('in');
    if (el.matches('.blood-band')) helpNum?.classList.add('in');
  };
  if (motion && 'IntersectionObserver' in window) {
    const io = new IntersectionObserver(entries => {
      let i = 0;
      entries.forEach(en => {
        if (!en.isIntersecting) return;
        en.target.style.setProperty('--i', i++);
        reveal(en.target);
        if (en.target.matches('.topics')) $$('span', en.target).forEach(countUp);
        io.unobserve(en.target);
      });
    }, { rootMargin: '0px 0px -12% 0px', threshold: 0.05 });
    $$(revealSel).forEach(el => io.observe(el));
  } else {
    $$(revealSel).forEach(reveal);
  }

  // ---- 4. Scroll-linked: creed darkens word by word; articles get a reading bar; masthead behaviour
  const creed = document.querySelector('.creed blockquote p');
  const readbar = document.querySelector('.readbar');
  const article = document.querySelector('.article-body');
  const words = motion && creed ? (splitWords(creed), $$('.w', creed)) : [];

  const mast = document.querySelector('.mast');
  const nav = mast?.querySelector('.nav');
  const desk = window.matchMedia('(min-width: 1024px)');
  const searchOpen = () => typeof sbar !== 'undefined' && sbar && !sbar.hidden;
  const measure = () => {
    if (!mast || !nav) return;
    root.style.setProperty('--mast-hide', (mast.offsetHeight - nav.offsetHeight - (searchOpen() ? sbar.offsetHeight : 0)) + 'px');
  };
  measure();
  window.addEventListener('resize', measure);
  document.fonts?.ready.then(measure);
  if (typeof sbtn !== 'undefined') sbtn?.addEventListener('click', measure);

  let lastY = scrollY, ticking = false;
  const onScroll = () => {
    ticking = false;
    const y = scrollY, vh = innerHeight;
    if (mast) {
      mast.classList.toggle('stuck', desk.matches && y > mast.offsetHeight);
      if (!desk.matches && !searchOpen() && y > lastY + 4 && y > 160) mast.classList.add('away');
      if (y < lastY - 4 || y < 160 || desk.matches) mast.classList.toggle('away', false);
    }
    if (words.length) {
      const r = creed.getBoundingClientRect();
      const p = Math.min(1, Math.max(0, (vh * 0.9 - r.top) / (vh * 0.45)));
      const lit = Math.round(p * words.length);
      words.forEach((w, i) => w.classList.toggle('lit', i < lit));
    }
    if (readbar && article) {
      const r = article.getBoundingClientRect();
      readbar.style.setProperty('--p', Math.min(1, Math.max(0, (vh * 0.4 - r.top) / r.height)).toFixed(4));
    }
    lastY = y;
  };
  window.addEventListener('scroll', () => { if (!ticking) { ticking = true; requestAnimationFrame(onScroll); } }, { passive: true });
  onScroll();

  // ---- 5. Finder results arrive in order
  const list = document.getElementById('finderList');
  if (list) new MutationObserver(() => $$('li', list).forEach((li, i) => li.style.setProperty('--i', Math.min(i, 12)))).observe(list, { childList: true });

  // ---- 6. Page transitions: the clicked story's photo becomes the article's photo
  document.addEventListener('click', e => {
    const a = e.target.closest('a[href^="/news/"]');
    if (!a || e.metaKey || e.ctrlKey || e.shiftKey || e.button) return;
    const img = a.closest('.tile, .river-item')?.querySelector('img');
    if (!img) return;
    // only one element may carry the name: on an article page, release the current photo first
    $$('.vt-hero').forEach(h => (h.style.viewTransitionName = 'none'));
    img.style.viewTransitionName = 'hero';
  });
  // coming back with the browser's back button: clear the borrowed name
  window.addEventListener('pageshow', () => $$('img[style*="view-transition-name"]').forEach(i => (i.style.viewTransitionName = '')));

  // ---- 7. Home hero: five field photos. Advances every 7s; holds while hovered or focused,
  //      when the tab is hidden, or after the pause button. Never advances alone with reduced motion.
  const hero = document.querySelector('.hero');
  if (hero) {
    const imgs = $$('.hero-img', hero), texts = $$('.hero-text', hero), tabs = $$('[role=tab]', hero);
    const pauseBtn = hero.querySelector('.hero-pause');
    const DUR = 7000;
    let cur = 0, timer = null, held = false, userPaused = !motion;
    hero.style.setProperty('--hero-dur', DUR / 1000 + 's');
    const running = () => !userPaused && !held && document.visibilityState === 'visible';
    const schedule = () => {
      clearTimeout(timer);
      hero.classList.toggle('paused', !running());
      if (running()) timer = setTimeout(() => show(cur + 1), DUR);
    };
    const show = i => {
      cur = (i + imgs.length) % imgs.length;
      imgs.forEach((im, k) => { im.classList.toggle('on', k === cur); if (k === cur) im.loading = 'eager'; });
      texts.forEach((t, k) => (t.hidden = k !== cur));
      tabs.forEach((t, k) => t.setAttribute('aria-selected', String(k === cur)));
      imgs[(cur + 1) % imgs.length].loading = 'eager';   // warm the next photo
      schedule();
    };
    tabs.forEach((t, k) => t.addEventListener('click', () => show(k)));
    hero.addEventListener('keydown', e => {
      if (!e.target.closest('[role=tablist]') || !['ArrowRight', 'ArrowLeft'].includes(e.key)) return;
      show(cur + ((e.key === 'ArrowRight') !== (document.dir === 'rtl') ? 1 : -1)); tabs[cur].focus(); e.preventDefault();
    });
    const hold = v => () => { held = v; schedule(); };
    hero.addEventListener('pointerenter', hold(true)); hero.addEventListener('pointerleave', hold(false));
    hero.addEventListener('focusin', hold(true)); hero.addEventListener('focusout', hold(false));
    document.addEventListener('visibilitychange', schedule);
    const syncPause = () => {
      pauseBtn.setAttribute('aria-pressed', String(userPaused));
      pauseBtn.setAttribute('aria-label', userPaused ? (window.T?.play || 'Play') : (window.T?.pause || 'Pause'));
      pauseBtn.innerHTML = `<svg class="i" aria-hidden="true"><use href="#i-${userPaused ? 'play' : 'pause'}"/></svg>`;
    };
    pauseBtn?.addEventListener('click', () => { userPaused = !userPaused; syncPause(); schedule(); });
    // swipe on phones
    let x0 = null;
    hero.addEventListener('touchstart', e => (x0 = e.touches[0].clientX), { passive: true });
    hero.addEventListener('touchend', e => {
      if (x0 === null) return;
      const dx = e.changedTouches[0].clientX - x0; x0 = null;
      if (Math.abs(dx) > 50) show(cur + ((dx < 0) !== (document.dir === 'rtl') ? 1 : -1));
    });
    if (pauseBtn) syncPause();
    schedule();
  }

  // ---- 8. Photo reel buttons scroll by most of a screenful
  $$('[data-reel]').forEach(b => b.addEventListener('click', () => {
    const track = b.closest('.reel').querySelector('.reel-track');
    track.scrollBy({ left: Number(b.dataset.reel) * (document.dir === 'rtl' ? -1 : 1) * track.clientWidth * 0.8, behavior: motion ? 'smooth' : 'auto' });
  }));
})();
