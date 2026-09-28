/**
 * CampusEvents - Live Upcoming Event Countdown
 */

let countdownInterval = null;

function initEventCountdown() {
  const container = document.getElementById('upcoming-countdown-container');
  if (!container) return;

  const targetDateAttr = container.getAttribute('data-event-datetime');
  if (!targetDateAttr) {
    showNoEventsState(container);
    return;
  }

  startTimer(new Date(targetDateAttr).getTime(), container);
}

function startTimer(targetTime, container) {
  if (countdownInterval) clearInterval(countdownInterval);

  const daysEl = document.getElementById('cd-days');
  const hoursEl = document.getElementById('cd-hours');
  const minutesEl = document.getElementById('cd-minutes');
  const secondsEl = document.getElementById('cd-seconds');

  function update() {
    const now = new Date().getTime();
    const distance = targetTime - now;

    if (distance <= 0) {
      clearInterval(countdownInterval);
      // Fetch next event automatically
      fetchNextUpcomingEvent(container);
      return;
    }

    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((distance % (1000 * 60)) / 1000);

    if (daysEl) daysEl.textContent = String(days).padStart(2, '0');
    if (hoursEl) hoursEl.textContent = String(hours).padStart(2, '0');
    if (minutesEl) minutesEl.textContent = String(minutes).padStart(2, '0');
    if (secondsEl) secondsEl.textContent = String(seconds).padStart(2, '0');
  }

  update();
  countdownInterval = setInterval(update, 1000);
}

function fetchNextUpcomingEvent(container) {
  const baseUrl = document.body.getAttribute('data-base-url') || './';
  fetch(`${baseUrl}api/get-next-event.php`)
    .then(res => res.json())
    .then(data => {
      if (data.success && data.event) {
        renderUpcomingEvent(data.event, container);
      } else {
        showNoEventsState(container);
      }
    })
    .catch(() => {
      showNoEventsState(container);
    });
}

function renderUpcomingEvent(event, container) {
  const titleEl = document.getElementById('cd-event-title');
  const dateEl = document.getElementById('cd-event-date');
  const venueEl = document.getElementById('cd-event-venue');
  const linkEl = document.getElementById('cd-event-link');
  const baseUrl = document.body.getAttribute('data-base-url') || './';

  if (titleEl) titleEl.textContent = event.title;
  if (dateEl) dateEl.innerHTML = `<i class="fa-regular fa-calendar"></i> ${event.formatted_date} at ${event.formatted_time}`;
  if (venueEl) venueEl.innerHTML = `<i class="fa-solid fa-location-dot"></i> ${event.venue}`;
  if (linkEl) linkEl.href = `${baseUrl}event-details.php?id=${event.id}`;

  const eventDateTime = `${event.event_date}T${event.event_time}`;
  container.setAttribute('data-event-datetime', eventDateTime);
  startTimer(new Date(eventDateTime).getTime(), container);
}

function showNoEventsState(container) {
  container.innerHTML = `
    <div class="empty-state" style="padding: 2.5rem 1rem; color: #ffffff;">
      <i class="fa-solid fa-calendar-xmark" style="font-size: 2.5rem; color: #94a3b8; margin-bottom: 0.75rem;"></i>
      <h3 style="color: #ffffff; font-size: 1.25rem; margin-bottom: 0.4rem;">No upcoming events at the moment.</h3>
      <p style="color: #94a3b8; font-size: 0.9rem;">Check back soon for new campus festivals, workshops, and competitions!</p>
    </div>
  `;
}

document.addEventListener('DOMContentLoaded', initEventCountdown);
