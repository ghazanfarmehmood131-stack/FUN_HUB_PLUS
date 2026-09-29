/**
 * ARCHIVE Ledger - Events & Map Interactive Script
 * Powers Leaflet Dark Map, Interactive Calendar, Geolocation Radar, and RSVP.
 */

(function() {
  'use strict';

  let map = null;
  let markersLayer = null;
  let userMarker = null;
  let allEvents = [];
  let currentCategory = 'all';
  let searchQuery = '';
  let selectedDate = null;
  let currentMonthDate = new Date(2026, 9, 1); // October 2026

  // DOM Elements
  const mapElement = document.getElementById('eventsMap');
  const searchInput = document.getElementById('eventSearchInput');
  const categoriesBar = document.getElementById('eventCategoriesBar');
  const listContainer = document.getElementById('eventsListContainer');
  const countBadge = document.getElementById('eventsCountBadge');
  const emptyState = document.getElementById('eventsEmptyState');
  const layoutContainer = document.getElementById('eventsLayoutContainer');
  const viewButtons = document.querySelectorAll('.view-mode-btn');
  const geoLocateBtn = document.getElementById('geoLocateBtn');
  const geoStatusText = document.getElementById('geoStatusText');
  const resetZoomBtn = document.getElementById('resetMapZoomBtn');
  const prevMonthBtn = document.getElementById('prevMonthBtn');
  const nextMonthBtn = document.getElementById('nextMonthBtn');
  const monthTitle = document.getElementById('calendarMonthTitle');
  const daysGrid = document.getElementById('calendarDaysGrid');
  const clearDateBtn = document.getElementById('clearDateFilterBtn');
  const resetFiltersBtn = document.getElementById('resetEventsFilterBtn');

  // Modal elements
  const detailModal = document.getElementById('eventDetailModal');
  const modalContent = document.getElementById('eventModalContent');
  const closeModalBtn = document.getElementById('closeEventModal');

  // Category Icon & Color Mapping
  const categoryConfig = {
    convention: { color: '#b43d31', icon: '🏛️' },
    anime:      { color: '#e67e22', icon: '🎌' },
    gaming:     { color: '#3498db', icon: '🎮' },
    cosplay:    { color: '#9b59b6', icon: '🛡️' },
    cinema:     { color: '#e74c3c', icon: '🎬' },
    kpop:       { color: '#e84393', icon: '🎵' },
    default:    { color: '#b43d31', icon: '📍' }
  };

  /**
   * Initialize Leaflet Map
   */
  function initMap() {
    if (!mapElement || typeof L === 'undefined') return;

    // Default center (Global overview tilted toward Asia/Europe)
    map = L.map('eventsMap', {
      center: [30.0, 45.0],
      zoom: 3,
      zoomControl: true,
      scrollWheelZoom: true
    });

    // Dark Matter Tiles (High contrast, dark charcoal)
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>',
      subdomains: 'abcd',
      maxZoom: 19
    }).addTo(map);

    markersLayer = L.layerGroup().addTo(map);
  }

  /**
   * Fetch Events from Backend
   */
  async function loadEventsData() {
    try {
      const response = await fetch('api/events.php');
      const data = await response.json();
      if (data && data.success && Array.isArray(data.data)) {
        allEvents = data.data;
      }
    } catch (e) {
      console.warn('API error, using DOM events data:', e);
      // Fallback: extract from DOM articles
      allEvents = extractEventsFromDom();
    }

    renderMapMarkers();
    renderCalendar();
    applyFilters();
  }

  function extractEventsFromDom() {
    const articles = document.querySelectorAll('.event-card');
    const list = [];
    articles.forEach(el => {
      const id = parseInt(el.dataset.eventId, 10);
      const cat = el.dataset.category || 'convention';
      const title = el.querySelector('.event-card-title')?.textContent.trim() || '';
      const desc = el.querySelector('.event-card-desc')?.textContent.trim() || '';
      const dateText = el.querySelector('.event-date-text')?.textContent.trim() || '';
      const venueText = el.querySelector('.event-venue-text')?.textContent.trim() || '';
      const banner = el.querySelector('.event-card-img')?.src || '';
      list.push({
        id, category: cat, title, description: desc,
        date_start: '2026-10-15', venue: venueText, banner_url: banner,
        latitude: 33.69, longitude: 73.08
      });
    });
    return list;
  }

  /**
   * Render Map Markers
   */
  function renderMapMarkers(filtered = allEvents) {
    if (!map || !markersLayer) return;

    markersLayer.clearLayers();
    const bounds = [];

    filtered.forEach(ev => {
      const lat = parseFloat(ev.latitude);
      const lng = parseFloat(ev.longitude);
      if (isNaN(lat) || isNaN(lng)) return;

      const conf = categoryConfig[ev.category] || categoryConfig.default;

      // Custom HTML Marker
      const customIcon = L.divIcon({
        className: 'custom-map-pin',
        html: `
          <div class="custom-leaflet-marker" style="background-color: ${conf.color};">
            <span>${conf.icon}</span>
          </div>
        `,
        iconSize: [32, 32],
        iconAnchor: [16, 32],
        popupAnchor: [0, -30]
      });

      const popupHtml = `
        <div class="map-popup-card">
          <img src="${ev.banner_url || ''}" alt="${ev.title}" class="map-popup-img" onerror="this.style.display='none'">
          <div class="map-popup-badge">${(ev.badge_text || ev.category).toUpperCase()}</div>
          <h4 class="map-popup-title">${ev.title}</h4>
          <div class="map-popup-meta">
            📅 ${ev.date_start} | 📍 ${ev.city}, ${ev.country}
          </div>
          <p style="font-size:12px; color:var(--text-body); margin:0 0 10px; line-height:1.4;">
            ${(ev.description || '').substring(0, 100)}...
          </p>
          <div style="display:flex; gap:6px;">
            <button type="button" class="btn btn-primary btn-xs" onclick="window.ARCHIVE_EVENTS.openDetail(${ev.id})">Tafseelat (Details)</button>
            <button type="button" class="btn btn-secondary btn-xs" onclick="window.ARCHIVE_EVENTS.toggleRsvp(${ev.id}, this)">★ Interested</button>
          </div>
        </div>
      `;

      const marker = L.marker([lat, lng], { icon: customIcon }).bindPopup(popupHtml);
      marker.eventId = ev.id;
      markersLayer.addLayer(marker);
      bounds.push([lat, lng]);
    });

    if (userMarker) {
      bounds.push(userMarker.getLatLng());
    }

    if (bounds.length > 0 && mapElement.offsetParent !== null) {
      try {
        map.fitBounds(bounds, { padding: [40, 40], maxZoom: 13 });
      } catch (err) {}
    }
  }

  /**
   * Filter & Search Handling
   */
  function applyFilters() {
    const term = (searchInput?.value || '').toLowerCase().trim();

    const filtered = allEvents.filter(ev => {
      // Category match
      if (currentCategory !== 'all' && ev.category !== currentCategory) {
        return false;
      }
      // Date match
      if (selectedDate) {
        if (ev.date_start !== selectedDate && ev.date_end !== selectedDate) {
          // If range, check if selectedDate is within start and end
          if (ev.date_end) {
            if (selectedDate < ev.date_start || selectedDate > ev.date_end) {
              return false;
            }
          } else {
            return false;
          }
        }
      }
      // Search term
      if (term) {
        const text = `${ev.title} ${ev.venue} ${ev.city} ${ev.country} ${ev.description} ${ev.organizer}`.toLowerCase();
        if (!text.includes(term)) return false;
      }
      return true;
    });

    // Update count
    if (countBadge) {
      countBadge.textContent = `${filtered.length} FOUND`;
    }

    // Render cards
    renderCards(filtered);

    // Update map markers
    renderMapMarkers(filtered);
  }

  /**
   * Render Event Cards in HTML
   */
  function renderCards(eventsToRender) {
    if (!listContainer) return;

    if (eventsToRender.length === 0) {
      listContainer.innerHTML = '';
      if (emptyState) emptyState.style.display = 'block';
      return;
    }

    if (emptyState) emptyState.style.display = 'none';

    listContainer.innerHTML = eventsToRender.map(ev => {
      const isRsvpd = Boolean(ev.is_rsvpd);
      const conf = categoryConfig[ev.category] || categoryConfig.default;
      const gcalUrl = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${encodeURIComponent(ev.title)}&dates=${ev.date_start.replace(/-/g, '')}/${(ev.date_end || ev.date_start).replace(/-/g, '')}&details=${encodeURIComponent(ev.description)}&location=${encodeURIComponent(ev.venue + ', ' + ev.city)}`;

      return `
        <article class="event-card" data-event-id="${ev.id}" data-category="${ev.category}" data-type="${ev.event_type}">
          <div class="event-card-media">
            <img src="${ev.banner_url || ''}" alt="${ev.title}" loading="lazy" class="event-card-img" onerror="this.src='data:image/svg+xml;charset=UTF-8,<svg xmlns=\\'http://www.w3.org/2000/svg\\' width=\\'400\\' height=\\'200\\' fill=\\'%232a2826\\'><rect width=\\'400\\' height=\\'200\\'/></svg>'">
            <span class="event-badge-tag">${ev.badge_text || ev.category.toUpperCase()}</span>
            <span class="event-type-tag ${(ev.event_type || '').toLowerCase()}">${ev.event_type || 'In-Person'}</span>
          </div>
          <div class="event-card-body">
            <div class="event-date-row">
              <span class="event-date-icon">📅</span>
              <time class="event-date-text">${ev.date_start} ${ev.date_end && ev.date_end !== ev.date_start ? '➔ ' + ev.date_end : ''}</time>
              ${ev.distanceKm ? `<span class="badge badge-accent" style="margin-left:auto;">📍 ${ev.distanceKm} KM AWAY</span>` : ''}
            </div>
            <h4 class="event-card-title">${ev.title}</h4>
            <div class="event-venue-row">
              <span class="event-pin-icon">📍</span>
              <span class="event-venue-text">${ev.venue}, <strong>${ev.city}, ${ev.country}</strong></span>
            </div>
            <p class="event-card-desc">${ev.description}</p>
            
            <div class="event-card-meta">
              <span class="event-ticket-badge">${ev.ticket_info || 'Public'}</span>
              <span class="event-time-badge">${ev.time_info || '10:00 AM - 6:00 PM'}</span>
            </div>

            <div class="event-card-actions">
              <button type="button" class="btn btn-secondary btn-sm" onclick="window.ARCHIVE_EVENTS.focusOnMap(${ev.id})">
                SHOW ON MAP
              </button>
              <button type="button" class="btn btn-secondary btn-sm btn-rsvp ${isRsvpd ? 'active' : ''}" onclick="window.ARCHIVE_EVENTS.toggleRsvp(${ev.id}, this)">
                ${isRsvpd ? '★ INTERESTED' : '+ INTERESTED'}
              </button>
              <button type="button" class="btn btn-secondary btn-sm" onclick="window.ARCHIVE_EVENTS.openDetail(${ev.id})">
                DETAILS
              </button>
              <a href="${gcalUrl}" target="_blank" rel="noopener" class="btn-cal-export" title="Add to Google Calendar">
                + G-CAL
              </a>
            </div>
          </div>
        </article>
      `;
    }).join('');
  }

  /**
   * Interactive Monthly Calendar Renderer
   */
  function renderCalendar() {
    if (!daysGrid || !monthTitle) return;

    const year = currentMonthDate.getFullYear();
    const month = currentMonthDate.getMonth();

    const monthNames = [
      'JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE',
      'JULY', 'AUGUST', 'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER'
    ];

    monthTitle.textContent = `${monthNames[month]} ${year}`;

    // First day of month (0-6) & total days
    const firstDay = new Date(year, month, 1).getDay();
    const totalDays = new Date(year, month + 1, 0).getDate();

    // Map events in this month
    const eventDaysMap = {};
    allEvents.forEach(ev => {
      const d = new Date(ev.date_start);
      if (d.getFullYear() === year && d.getMonth() === month) {
        const dayNum = d.getDate();
        if (!eventDaysMap[dayNum]) eventDaysMap[dayNum] = [];
        eventDaysMap[dayNum].push(ev);
      }
    });

    let html = '';

    // Empty lead cells
    for (let i = 0; i < firstDay; i++) {
      html += '<div class="cal-day-cell empty-day"></div>';
    }

    const today = new Date();
    const isCurrentMonth = today.getFullYear() === year && today.getMonth() === month;
    const currentDayNum = today.getDate();

    for (let day = 1; day <= totalDays; day++) {
      const monthStr = String(month + 1).padStart(2, '0');
      const dayStr = String(day).padStart(2, '0');
      const dateKey = `${year}-${monthStr}-${dayStr}`;

      const hasEvent = Boolean(eventDaysMap[day]);
      const isToday = isCurrentMonth && day === currentDayNum;
      const isSelected = selectedDate === dateKey;

      const classes = ['cal-day-cell'];
      if (hasEvent) classes.push('has-event');
      if (isToday) classes.push('is-today');
      if (isSelected) classes.push('is-selected');

      const count = hasEvent ? eventDaysMap[day].length : 0;
      const titleAttr = hasEvent ? `${count} event(s) on ${dateKey}` : dateKey;

      html += `
        <div class="${classes.join(' ')}" data-date="${dateKey}" title="${titleAttr}">
          <span>${day}</span>
        </div>
      `;
    }

    daysGrid.innerHTML = html;

    // Attach click handlers to calendar days
    daysGrid.querySelectorAll('.cal-day-cell:not(.empty-day)').forEach(cell => {
      cell.addEventListener('click', function() {
        const d = this.dataset.date;
        if (selectedDate === d) {
          selectedDate = null;
          if (clearDateBtn) clearDateBtn.style.display = 'none';
        } else {
          selectedDate = d;
          if (clearDateBtn) {
            clearDateBtn.style.display = 'inline-block';
            clearDateBtn.textContent = `Clear Date (${d})`;
          }
        }
        renderCalendar();
        applyFilters();
      });
    });
  }

  /**
   * Geolocation / "Near Me" Finder
   */
  function handleGeoLocation() {
    if (!navigator.geolocation) {
      if (geoStatusText) geoStatusText.textContent = 'Geolocation is not supported by your browser.';
      return;
    }

    if (geoStatusText) {
      geoStatusText.textContent = 'Locating your coordinates...';
      geoLocateBtn.classList.add('loading');
    }

    navigator.geolocation.getCurrentPosition(
      (pos) => {
        const uLat = pos.coords.latitude;
        const uLng = pos.coords.longitude;

        if (geoStatusText) {
          geoStatusText.innerHTML = `📍 Location detected: <strong>${uLat.toFixed(2)}°, ${uLng.toFixed(2)}°</strong> — Sorting nearest events`;
        }
        if (geoLocateBtn) geoLocateBtn.classList.remove('loading');

        // Add user marker
        if (map) {
          if (userMarker) map.removeLayer(userMarker);
          const userIcon = L.divIcon({
            className: 'custom-user-marker',
            html: `
              <div class="custom-leaflet-marker user-marker" title="Your Location">
                <span>👤</span>
              </div>
            `,
            iconSize: [34, 34],
            iconAnchor: [17, 17]
          });
          userMarker = L.marker([uLat, uLng], { icon: userIcon }).addTo(map)
            .bindPopup('<strong>Aap Ki Location (You are here)</strong>')
            .openPopup();
        }

        // Calculate distance to each event using Haversine formula
        allEvents.forEach(ev => {
          const eLat = parseFloat(ev.latitude);
          const eLng = parseFloat(ev.longitude);
          if (!isNaN(eLat) && !isNaN(eLng)) {
            ev.distanceKm = Math.round(calculateDistance(uLat, uLng, eLat, eLng));
          } else {
            ev.distanceKm = 99999;
          }
        });

        // Sort events by proximity
        allEvents.sort((a, b) => (a.distanceKm || 99999) - (b.distanceKm || 99999));

        applyFilters();
      },
      (err) => {
        console.warn('Geolocation error:', err);
        if (geoStatusText) {
          geoStatusText.textContent = 'Could not access location. Showing regional Pakistan & global highlights.';
        }
        if (geoLocateBtn) geoLocateBtn.classList.remove('loading');
      },
      { timeout: 10000, enableHighAccuracy: true }
    );
  }

  function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371; // Earth radius in km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a =
      Math.sin(dLat/2) * Math.sin(dLat/2) +
      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
      Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
  }

  /**
   * RSVP / Interested Toggle
   */
  async function toggleRsvp(eventId, btnElement) {
    try {
      const response = await fetch('api/events.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ event_id: eventId, action: 'rsvp' })
      });
      const res = await response.json();
      if (res && res.success) {
        if (btnElement) {
          if (res.rsvpd) {
            btnElement.classList.add('active');
            btnElement.textContent = '★ INTERESTED';
          } else {
            btnElement.classList.remove('active');
            btnElement.textContent = '+ INTERESTED';
          }
        }
        // Update local object
        const item = allEvents.find(e => e.id === eventId);
        if (item) item.is_rsvpd = res.rsvpd;
      }
    } catch (e) {
      console.warn('RSVP failed, toggling locally:', e);
      if (btnElement) {
        btnElement.classList.toggle('active');
        btnElement.textContent = btnElement.classList.contains('active') ? '★ INTERESTED' : '+ INTERESTED';
      }
    }
  }

  /**
   * Focus Map on specific event
   */
  function focusOnMap(eventId) {
    const ev = allEvents.find(e => e.id === eventId);
    if (!ev || !map) return;

    const lat = parseFloat(ev.latitude);
    const lng = parseFloat(ev.longitude);

    if (!isNaN(lat) && !isNaN(lng)) {
      // If mobile or scrolled past, smooth scroll to map
      if (window.innerWidth < 992) {
        mapElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }

      map.flyTo([lat, lng], 13, { duration: 1.2 });

      // Open popup
      markersLayer.eachLayer(layer => {
        if (layer.eventId === eventId) {
          setTimeout(() => layer.openPopup(), 600);
        }
      });
    }
  }

  /**
   * Open Event Detail Modal
   */
  function openDetail(eventId) {
    const ev = allEvents.find(e => e.id === eventId);
    if (!ev || !detailModal || !modalContent) return;

    modalContent.innerHTML = `
      <div style="margin-bottom:16px;">
        <img src="${ev.banner_url || ''}" alt="${ev.title}" style="width:100%; max-height:280px; object-fit:cover; border-radius:6px; margin-bottom:14px;">
        <div style="display:flex; gap:8px; margin-bottom:8px;">
          <span class="badge badge-accent">${(ev.badge_text || ev.category).toUpperCase()}</span>
          <span class="badge badge-default">${ev.event_type}</span>
          <span class="badge badge-default">${ev.ticket_info}</span>
        </div>
        <h2 style="font-family:var(--font-display); font-size:26px; color:var(--text-bright); margin:6px 0 10px;">${ev.title}</h2>
        <div style="font-size:14px; color:var(--text-dim); margin-bottom:14px; line-height:1.6;">
          📅 <strong>Dates:</strong> ${ev.date_start} ${ev.date_end ? 'to ' + ev.date_end : ''}<br>
          ⏰ <strong>Timing:</strong> ${ev.time_info || '10:00 AM - 6:00 PM'}<br>
          📍 <strong>Venue:</strong> ${ev.venue}, ${ev.city}, ${ev.country}<br>
          🏢 <strong>Organizer:</strong> ${ev.organizer || 'Official Guild'}
        </div>
        <div style="font-size:15px; color:var(--text-body); line-height:1.6; margin-bottom:20px; border-top:1px solid var(--border-subtle); padding-top:14px;">
          ${ev.description}
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
          <a href="https://maps.google.com/?q=${encodeURIComponent(ev.venue + ' ' + ev.city)}" target="_blank" rel="noopener" class="btn btn-primary">
            GOOGLE MAPS DIRECTIONS ➔
          </a>
          <button type="button" class="btn btn-secondary" onclick="window.ARCHIVE_EVENTS.toggleRsvp(${ev.id}, this); window.ARCHIVE_EVENTS.closeDetail();">
            ${ev.is_rsvpd ? '★ SAVED IN INTERESTED' : '+ ADD TO INTERESTED'}
          </button>
        </div>
      </div>
    `;

    detailModal.classList.add('active');
  }

  function closeDetail() {
    if (detailModal) detailModal.classList.remove('active');
  }

  // Global exports for inline button clicks
  window.ARCHIVE_EVENTS = {
    openDetail,
    closeDetail,
    focusOnMap,
    toggleRsvp
  };

  /**
   * Event Listeners Setup
   */
  document.addEventListener('DOMContentLoaded', () => {
    initMap();
    loadEventsData();

    // Search input
    if (searchInput) {
      searchInput.addEventListener('input', () => {
        applyFilters();
      });
    }

    // Category filter pills
    if (categoriesBar) {
      categoriesBar.addEventListener('click', (e) => {
        const btn = e.target.closest('.event-filter-pill');
        if (!btn) return;
        categoriesBar.querySelectorAll('.event-filter-pill').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentCategory = btn.dataset.category || 'all';
        applyFilters();
      });
    }

    // View switcher
    viewButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        viewButtons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const view = btn.dataset.view;
        if (layoutContainer) {
          layoutContainer.dataset.currentView = view;
        }
        if (map) {
          setTimeout(() => map.invalidateSize(), 200);
        }
      });
    });

    // Geolocation
    if (geoLocateBtn) {
      geoLocateBtn.addEventListener('click', handleGeoLocation);
    }

    // Reset Zoom
    if (resetZoomBtn && map) {
      resetZoomBtn.addEventListener('click', () => {
        map.setView([30.0, 45.0], 3);
      });
    }

    // Calendar Navigation
    if (prevMonthBtn) {
      prevMonthBtn.addEventListener('click', () => {
        currentMonthDate.setMonth(currentMonthDate.getMonth() - 1);
        renderCalendar();
      });
    }
    if (nextMonthBtn) {
      nextMonthBtn.addEventListener('click', () => {
        currentMonthDate.setMonth(currentMonthDate.getMonth() + 1);
        renderCalendar();
      });
    }
    if (clearDateBtn) {
      clearDateBtn.addEventListener('click', () => {
        selectedDate = null;
        clearDateBtn.style.display = 'none';
        renderCalendar();
        applyFilters();
      });
    }
    if (resetFiltersBtn) {
      resetFiltersBtn.addEventListener('click', () => {
        if (searchInput) searchInput.value = '';
        currentCategory = 'all';
        selectedDate = null;
        if (categoriesBar) {
          categoriesBar.querySelectorAll('.event-filter-pill').forEach(b => {
            b.classList.toggle('active', b.dataset.category === 'all');
          });
        }
        if (clearDateBtn) clearDateBtn.style.display = 'none';
        renderCalendar();
        applyFilters();
      });
    }

    // Modal Close
    if (closeModalBtn) {
      closeModalBtn.addEventListener('click', closeDetail);
    }
    if (detailModal) {
      detailModal.addEventListener('click', (e) => {
        if (e.target === detailModal) closeDetail();
      });
    }
  });

})();
