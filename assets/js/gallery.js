/**
 * CampusEvents - Gallery Lightbox & Filtering Script
 */

document.addEventListener('DOMContentLoaded', () => {
  initGalleryLightbox();
  initGalleryFilter();
});

let currentGalleryIndex = 0;
let visibleGalleryItems = [];

function initGalleryLightbox() {
  const modal = document.getElementById('gallery-lightbox-modal');
  if (!modal) return;

  const lightboxImg = document.getElementById('lightbox-image');
  const lightboxCaption = document.getElementById('lightbox-caption');
  const lightboxMeta = document.getElementById('lightbox-meta');
  const closeBtn = document.querySelector('.lightbox-close');
  const prevBtn = document.querySelector('.lightbox-prev');
  const nextBtn = document.querySelector('.lightbox-next');

  updateVisibleItems();

  function openLightbox(index) {
    if (index < 0 || index >= visibleGalleryItems.length) return;
    currentGalleryIndex = index;
    const item = visibleGalleryItems[currentGalleryIndex];
    
    const imgSrc = item.getAttribute('data-full-img') || item.querySelector('img').src;
    const caption = item.getAttribute('data-caption') || '';
    const meta = item.getAttribute('data-meta') || '';

    lightboxImg.src = imgSrc;
    lightboxCaption.textContent = caption;
    if (lightboxMeta) lightboxMeta.textContent = meta;

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closeLightbox() {
    modal.classList.remove('active');
    document.body.style.overflow = '';
  }

  function showNext() {
    if (visibleGalleryItems.length === 0) return;
    currentGalleryIndex = (currentGalleryIndex + 1) % visibleGalleryItems.length;
    openLightbox(currentGalleryIndex);
  }

  function showPrev() {
    if (visibleGalleryItems.length === 0) return;
    currentGalleryIndex = (currentGalleryIndex - 1 + visibleGalleryItems.length) % visibleGalleryItems.length;
    openLightbox(currentGalleryIndex);
  }

  // Bind click on gallery items
  document.querySelectorAll('.gallery-item').forEach(item => {
    item.addEventListener('click', () => {
      updateVisibleItems();
      const idx = visibleGalleryItems.indexOf(item);
      if (idx !== -1) openLightbox(idx);
    });
  });

  if (closeBtn) closeBtn.addEventListener('click', closeLightbox);
  if (prevBtn) prevBtn.addEventListener('click', showPrev);
  if (nextBtn) nextBtn.addEventListener('click', showNext);

  modal.addEventListener('click', (e) => {
    if (e.target === modal) closeLightbox();
  });

  document.addEventListener('keydown', (e) => {
    if (!modal.classList.contains('active')) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowRight') showNext();
    if (e.key === 'ArrowLeft') showPrev();
  });
}

function updateVisibleItems() {
  visibleGalleryItems = Array.from(document.querySelectorAll('.gallery-item')).filter(
    item => window.getComputedStyle(item).display !== 'none'
  );
}

function initGalleryFilter() {
  const categoryPills = document.querySelectorAll('.gallery-pills .pill-btn');
  const items = document.querySelectorAll('.gallery-item');

  categoryPills.forEach(pill => {
    pill.addEventListener('click', () => {
      categoryPills.forEach(p => p.classList.remove('active'));
      pill.classList.add('active');
      const category = pill.getAttribute('data-category').toLowerCase();

      items.forEach(item => {
        const itemCat = (item.getAttribute('data-category') || '').toLowerCase();
        if (category === 'all' || itemCat === category) {
          item.style.display = 'block';
        } else {
          item.style.display = 'none';
        }
      });

      updateVisibleItems();
    });
  });
}
