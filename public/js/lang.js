(function () {
  const KEY = 'sigarda_lang'; // 'en' | 'id'
  const pairs = [
    ['hero-en','hero-id'],
    ['about-en','tentang'],
    ['features-en','fitur'],
    ['data-en','data-id'],
    ['privacy-en','privasi'],
    ['faq-en','faq-id'],
    ['contact-en','kontak']
  ];

  const enOnly = document.querySelectorAll('.en-only');
  const idOnly = document.querySelectorAll('.id-only');

  const btnEn = document.getElementById('btn-en');
  const btnId = document.getElementById('btn-id');

  function isEN(lang){ return (lang || '').toLowerCase() !== 'id'; }

  function applyLang(lang) {
    const useEN = isEN(lang);

    // Toggle all paired sections
    pairs.forEach(([en,id])=>{
      const enEl = document.getElementById(en);
      const idEl = document.getElementById(id);
      if (enEl) enEl.classList.toggle('d-none', !useEN);
      if (idEl) idEl.classList.toggle('d-none', useEN);
    });

    // Toggle navbar/footer text groups
    enOnly.forEach(el => el.classList.toggle('d-none', !useEN));
    idOnly.forEach(el => el.classList.toggle('d-none', useEN));

    // Button state
    if (btnEn && btnId) {
      btnEn.classList.toggle('btn-success', useEN);
      btnEn.classList.toggle('btn-outline-success', !useEN);
      btnEn.classList.toggle('active', useEN);

      btnId.classList.toggle('btn-success', !useEN);
      btnId.classList.toggle('btn-outline-secondary', useEN);
      btnId.classList.toggle('active', !useEN);
    }

    // Update <html lang>
    document.documentElement.setAttribute('lang', useEN ? 'en' : 'id');

    // Persist
    try { localStorage.setItem(KEY, useEN ? 'en' : 'id'); } catch(e) {}
  }

  // Resolve initial language: localStorage -> browser -> default EN
  function initialLang(){
    try { const saved = localStorage.getItem(KEY); if (saved) return saved; } catch(e) {}
    const nav = (navigator.language || navigator.userLanguage || 'en').toLowerCase();
    if (nav.startsWith('id')) return 'id';
    return 'en';
  }

  // Wire buttons
  if (btnEn) btnEn.addEventListener('click', ()=>applyLang('en'));
  if (btnId) btnId.addEventListener('click', ()=>applyLang('id'));

  // Apply on first load
  applyLang(initialLang());
})();
