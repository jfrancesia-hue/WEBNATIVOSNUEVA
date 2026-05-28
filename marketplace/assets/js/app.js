// Interacciones del lado del cliente: navbar scroll, reveals, modal, filtros auto-submit y chatbot.
(() => {
  // --- Navbar transparente -> sólido al scrollear ---
  const navbar = document.getElementById('navbar');
  const onScroll = () => navbar.classList.toggle('scrolled', window.scrollY > 50);
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  // --- Mobile menu ---
  const mobileToggle = document.getElementById('mobile-toggle');
  const mobileMenu = document.getElementById('mobile-menu');
  mobileToggle?.addEventListener('click', () => mobileMenu.classList.toggle('hidden'));

  // --- Reveal on scroll ---
  const revealEls = document.querySelectorAll('.reveal');
  const io = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.classList.add('is-visible');
        io.unobserve(e.target);
      }
    });
  }, { threshold: 0.15 });
  revealEls.forEach(el => io.observe(el));

  // --- Auto-submit del formulario de filtros al togglear ---
  document.querySelectorAll('.filter-input').forEach(input => {
    input.addEventListener('change', () => input.form?.submit());
  });

  // --- Modal de producto ---
  const modal = document.getElementById('product-modal');
  const modalBody = document.getElementById('product-modal-body');

  const openModal = async (id) => {
    modalBody.innerHTML = '<div class="p-8 text-gray-400">Cargando…</div>';
    modal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    try {
      const r = await fetch(`api/product.php?id=${encodeURIComponent(id)}`);
      modalBody.innerHTML = await r.text();
    } catch {
      modalBody.innerHTML = '<div class="p-8 text-red-400">Error al cargar el producto.</div>';
    }
  };

  const closeModal = () => {
    modal.classList.remove('is-open');
    document.body.style.overflow = '';
  };

  document.querySelectorAll('.product-card, .open-product').forEach(el => {
    el.addEventListener('click', (e) => {
      const id = el.dataset.productId;
      if (!id) return;
      // Evitar doble disparo cuando hay un botón dentro de la card
      if (e.target.closest('button') && el.classList.contains('product-card') && e.target.closest('.open-product')) return;
      openModal(id);
    });
  });

  modal.querySelectorAll('[data-close-modal]').forEach(el => el.addEventListener('click', closeModal));
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

  // --- Chatbot ---
  const chatToggle = document.getElementById('chat-toggle');
  const chatWindow = document.getElementById('chat-window');
  const chatClose = document.getElementById('chat-close');
  const chatForm = document.getElementById('chat-form');
  const chatInput = document.getElementById('chat-input');
  const chatMessages = document.getElementById('chat-messages');

  const history = [
    { role: 'model', text: '¡Hola! Soy el asistente de Nativos Launchpad. ¿En qué puedo ayudarte hoy?' },
  ];

  const renderMessage = (msg) => {
    const wrap = document.createElement('div');
    wrap.className = `flex ${msg.role === 'user' ? 'justify-end' : 'justify-start'}`;
    const bubble = document.createElement('div');
    bubble.className =
      'max-w-[80%] p-3 rounded-2xl text-sm ' +
      (msg.role === 'user'
        ? 'bg-gold-400 text-black rounded-tr-none'
        : 'bg-white/5 text-gray-300 rounded-tl-none border border-white/10');
    bubble.textContent = msg.text;
    wrap.appendChild(bubble);
    chatMessages.appendChild(wrap);
    chatMessages.scrollTop = chatMessages.scrollHeight;
  };

  const renderLoading = () => {
    const wrap = document.createElement('div');
    wrap.id = 'chat-loading';
    wrap.className = 'flex justify-start';
    wrap.innerHTML = '<div class="bg-white/5 p-3 rounded-2xl rounded-tl-none border border-white/10"><span class="inline-block w-2 h-2 bg-gold-400 rounded-full animate-spin"></span></div>';
    chatMessages.appendChild(wrap);
    chatMessages.scrollTop = chatMessages.scrollHeight;
  };

  history.forEach(renderMessage);

  chatToggle.addEventListener('click', () => chatWindow.classList.add('is-open'));
  chatClose.addEventListener('click', () => chatWindow.classList.remove('is-open'));

  chatForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const text = chatInput.value.trim();
    if (!text) return;
    const userMsg = { role: 'user', text };
    renderMessage(userMsg);
    chatInput.value = '';
    chatInput.disabled = true;
    renderLoading();

    try {
      const res = await fetch('api/chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ history, message: text }),
      });
      const data = await res.json();
      document.getElementById('chat-loading')?.remove();
      const botText = data.text || data.error || 'Lo siento, no puedo responder en este momento.';
      const botMsg = { role: 'model', text: botText };
      renderMessage(botMsg);
      history.push(userMsg, botMsg);
    } catch {
      document.getElementById('chat-loading')?.remove();
      renderMessage({ role: 'model', text: 'Error de conexión. Intenta de nuevo más tarde.' });
    } finally {
      chatInput.disabled = false;
      chatInput.focus();
    }
  });
})();
