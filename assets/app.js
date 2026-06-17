import './stimulus_bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';


function setTheme(t) {
    document.documentElement.setAttribute('data-theme', t);
    document.querySelectorAll('.theme-btn').forEach(b => b.classList.toggle('active', b.dataset.t === t));
  }

  const el = document.getElementById('typing-line');
  const phrases = el ? JSON.parse(el.dataset.phrases) : [];
  let pi = 0, ci = 0, del = false;

  function tick() {
    const p = phrases[pi];
    if (!del) {
      el.innerHTML = p.slice(0, ci+1) + '<span class="cursor"></span>'; ci++;
      if (ci === p.length) { del = true; setTimeout(tick, 2400); return; }
    } else {
      el.innerHTML = p.slice(0, ci-1) + '<span class="cursor"></span>'; ci--;
      if (ci === 0) { del = false; pi = (pi+1) % phrases.length; }
    }
    setTimeout(tick, del ? 36 : 70);
  }
  setTimeout(tick, 700);

  const obs = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) e.target.style.opacity = 1; }), { threshold: 0.06 });
  document.querySelectorAll('section').forEach(s => { s.style.opacity = 0; s.style.transition = 'opacity 0.55s ease'; obs.observe(s); });