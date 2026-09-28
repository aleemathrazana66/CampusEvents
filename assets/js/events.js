/**
 * CampusEvents - Events Filter & Search Script
 */

document.addEventListener('DOMContentLoaded', () => {
  initEventsFilter();
});

function initEventsFilter() {
  const searchInput = document.getElementById('event-search-input');
  const categoryPills = document.querySelectorAll('.category-pills .pill-btn');
  const timeframeTabs = document.querySelectorAll('.timeframe-tabs .pill-btn');
  const eventCards = document.querySelectorAll('.events-grid .event-card');
  const emptyMessage = document.getElementById('events-empty-state');

  let activeCategory = 'all';
  let activeTimeframe = 'all';
  let searchQuery = '';

  function filterCards() {
    let visibleCount = 0;

    eventCards.forEach(card => {
      const title = (card.getAttribute('data-title') || '').toLowerCase();
      const category = (card.getAttribute('data-category') || '').toLowerCase();
      const timeframe = (card.getAttribute('data-timeframe') || '').toLowerCase();

      const matchesSearch = !searchQuery || title.includes(searchQuery);
      const matchesCategory = activeCategory === 'all' || category === activeCategory;
      const matchesTimeframe = activeTimeframe === 'all' || timeframe === activeTimeframe;

      if (matchesSearch && matchesCategory && matchesTimeframe) {
        card.style.display = 'flex';
        card.style.animation = 'fadeIn 0.3s ease';
        visibleCount++;
      } else {
        card.style.display = 'none';
      }
    });

    if (emptyMessage) {
      emptyMessage.style.display = visibleCount === 0 ? 'block' : 'none';
    }
  }

  // Search input handler
  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      searchQuery = e.target.value.trim().toLowerCase();
      filterCards();
    });
  }

  // Category pill click handler
  categoryPills.forEach(pill => {
    pill.addEventListener('click', () => {
      categoryPills.forEach(p => p.classList.remove('active'));
      pill.classList.add('active');
      activeCategory = pill.getAttribute('data-category').toLowerCase();
      filterCards();
    });
  });

  // Timeframe tab click handler (upcoming / past)
  timeframeTabs.forEach(tab => {
    tab.addEventListener('click', () => {
      timeframeTabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      activeTimeframe = tab.getAttribute('data-timeframe').toLowerCase();
      filterCards();
    });
  });
}
