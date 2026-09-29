<?php
/**
 * ARCHIVE Ledger - Events & Interactive Map Page
 * Fans can discover conventions, anime expos, gaming tournaments, and meetups
 * near them using an interactive dark map, monthly calendar, and proximity filters.
 */
require_once __DIR__ . '/config.php';

$pageTitle  = 'Events & Map - Fan Conventions & Meetups - ARCHIVE';
$activeNav  = 'events';
$pageTag    = 'EVENTS 05';
$pageScript = 'js/events.js';

$events = getAllEvents();
$featuredEvents = getFeaturedEvents(3);

include __DIR__ . '/includes/header.php';
?>

<!-- Leaflet Dark Map Styles -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<link rel="stylesheet" href="css/events.css">

<main class="site-main">
  <!-- Events Hero Header -->
  <section class="events-hero">
    <div class="container">
      <div class="events-hero-inner">
        <div class="events-hero-text">
          <div class="hero-kicker">FAN GATHERINGS, CONVENTIONS &amp; REGIONAL SUMMITS</div>
          <h1 class="events-headline">
            EVENTS &amp; <span>INTERACTIVE MAP</span>
          </h1>
          <p class="events-intro">
            Apne aas-pass hone wale anime conventions, comic cons, esports tournaments, cinema screenings aur cosplay meetups dhoondein. Explore via our interactive dark map, monthly calendar, or proximity radar.
          </p>
          <div class="events-hero-tags">
            <span class="badge badge-accent">12+ CONVENTIONS INDEXED</span>
            <span class="badge badge-default">PAKISTAN &amp; GLOBAL</span>
            <span class="badge badge-default">REAL-TIME RADAR READY</span>
          </div>
        </div>
        
        <div class="events-hero-quickbar">
          <button type="button" id="geoLocateBtn" class="btn btn-primary btn-near-me">
            <svg class="icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="3"/>
              <path d="M12 2v3m0 14v3M2 12h3m14 0h3"/>
            </svg>
            <span>APNE AAS-PASS DHOONDEIN (NEAR ME)</span>
          </button>
          <div id="geoStatusText" class="geo-status-indicator">Click to detect nearest fan conventions</div>
        </div>
      </div>
    </div>
  </section>

  <!-- Controls Toolbar -->
  <section class="events-toolbar-section">
    <div class="container">
      <div class="events-toolbar-card">
        
        <!-- Search & Filter Controls -->
        <div class="events-search-wrap">
          <div class="events-search-box">
            <svg class="icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input 
              type="text" 
              id="eventSearchInput" 
              class="events-input" 
              placeholder="Search by event, city (Islamabad, Karachi, Tokyo, London)..."
              autocomplete="off"
            >
          </div>
        </div>

        <!-- Category Filter Pills -->
        <div class="events-categories-bar" id="eventCategoriesBar">
          <button type="button" class="event-filter-pill active" data-category="all">All Events</button>
          <button type="button" class="event-filter-pill" data-category="convention">Conventions</button>
          <button type="button" class="event-filter-pill" data-category="anime">Anime &amp; Manga</button>
          <button type="button" class="event-filter-pill" data-category="gaming">Gaming &amp; Esports</button>
          <button type="button" class="event-filter-pill" data-category="cosplay">Cosplay Summits</button>
          <button type="button" class="event-filter-pill" data-category="cinema">Cinema Screenings</button>
          <button type="button" class="event-filter-pill" data-category="kpop">K-Pop Fan Meets</button>
        </div>

        <!-- View Mode Switcher -->
        <div class="events-view-modes">
          <div class="view-btn-group" role="group" aria-label="View toggle">
            <button type="button" class="view-mode-btn active" data-view="split" title="Map & Calendar Split View">
              <svg class="icon" viewBox="0 0 24 24" width="16" height="16"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="3" x2="12" y2="21"/></svg>
              <span>SPLIT VIEW</span>
            </button>
            <button type="button" class="view-mode-btn" data-view="map" title="Interactive Full Map">
              <svg class="icon" viewBox="0 0 24 24" width="16" height="16"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>
              <span>MAP ONLY</span>
            </button>
            <button type="button" class="view-mode-btn" data-view="calendar" title="Monthly Calendar">
              <svg class="icon" viewBox="0 0 24 24" width="16" height="16"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              <span>CALENDAR</span>
            </button>
            <button type="button" class="view-mode-btn" data-view="list" title="Card Agenda List">
              <svg class="icon" viewBox="0 0 24 24" width="16" height="16"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
              <span>LIST</span>
            </button>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- Main Events Interactive Grid -->
  <section class="events-main-section">
    <div class="container">
      
      <!-- Split View Layout -->
      <div class="events-layout-container" id="eventsLayoutContainer" data-current-view="split">
        
        <!-- Left Panel: Interactive Map -->
        <div class="events-map-wrapper" id="eventsMapWrapper">
          <div class="map-header-bar">
            <div class="map-header-title">
              <span class="live-dot pulse"></span>
              <span>LIVE INTERACTIVE RADAR (LEAFLET DARK)</span>
            </div>
            <div class="map-controls-aux">
              <button type="button" id="resetMapZoomBtn" class="btn btn-secondary btn-xs" title="Reset map view to global">
                RESET WORLD VIEW
              </button>
            </div>
          </div>
          <div id="eventsMap" class="events-map-canvas">
            <noscript>
              <div style="padding: 40px; text-align: center; color: var(--text-dim);">
                Please enable JavaScript to interact with the Live Event Map.
              </div>
            </noscript>
          </div>
          <div class="map-legend">
            <span><span class="legend-color legend-red"></span> Conventions</span>
            <span><span class="legend-color legend-orange"></span> Anime &amp; Manga</span>
            <span><span class="legend-color legend-blue"></span> Gaming &amp; Esports</span>
            <span><span class="legend-color legend-purple"></span> Cosplay &amp; Cinema</span>
          </div>
        </div>

        <!-- Right Panel: Calendar & Event Feed -->
        <div class="events-side-wrapper" id="eventsSideWrapper">
          
          <!-- Interactive Monthly Calendar Widget -->
          <div class="events-calendar-widget" id="calendarWidget">
            <div class="calendar-header">
              <button type="button" id="prevMonthBtn" class="cal-nav-btn" aria-label="Previous month">&larr;</button>
              <h2 id="calendarMonthTitle" class="calendar-month-heading">OCTOBER 2026</h2>
              <button type="button" id="nextMonthBtn" class="cal-nav-btn" aria-label="Next month">&rarr;</button>
            </div>
            <div class="calendar-weekdays">
              <span>SUN</span><span>MON</span><span>TUE</span><span>WED</span><span>THU</span><span>FRI</span><span>SAT</span>
            </div>
            <div id="calendarDaysGrid" class="calendar-days-grid">
              <!-- Dynamically rendered by events.js -->
            </div>
            <div class="calendar-footer-bar">
              <span id="calendarSelectionNote">Click any highlighted day to filter events</span>
              <button type="button" id="clearDateFilterBtn" class="btn-clear-date" style="display:none;">View All Dates</button>
            </div>
          </div>

          <!-- Events Agenda Stream -->
          <div class="events-stream-wrapper">
            <div class="events-stream-header">
              <h3 class="stream-title">UPCOMING PROGRAMS &amp; CONVENTIONS</h3>
              <span id="eventsCountBadge" class="stream-count"><?= count($events) ?> FOUND</span>
            </div>

            <div id="eventsListContainer" class="events-cards-grid">
              <!-- Server-rendered fallback cards for instant display and SEO -->
              <?php foreach ($events as $event): ?>
                <article class="event-card" data-event-id="<?= (int)$event['id'] ?>" data-category="<?= e($event['category']) ?>" data-type="<?= e($event['event_type']) ?>">
                  <div class="event-card-media">
                    <img src="<?= e($event['banner_url'] ?? 'data:image/svg+xml;charset=UTF-8,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="200" fill="%232a2826"><rect width="400" height="200"/></svg>') ?>" alt="<?= e($event['title']) ?>" loading="lazy" class="event-card-img">
                    <span class="event-badge-tag"><?= e($event['badge_text']) ?></span>
                    <span class="event-type-tag <?= strtolower($event['event_type']) ?>"><?= e($event['event_type']) ?></span>
                  </div>
                  <div class="event-card-body">
                    <div class="event-date-row">
                      <span class="event-date-icon">📅</span>
                      <time class="event-date-text">
                        <?= date('M d, Y', strtotime($event['date_start'])) ?>
                        <?php if (!empty($event['date_end']) && $event['date_end'] !== $event['date_start']): ?>
                          - <?= date('M d, Y', strtotime($event['date_end'])) ?>
                        <?php endif; ?>
                      </time>
                    </div>
                    <h4 class="event-card-title"><?= e($event['title']) ?></h4>
                    <div class="event-venue-row">
                      <span class="event-pin-icon">📍</span>
                      <span class="event-venue-text"><?= e($event['venue']) ?>, <strong><?= e($event['city']) ?>, <?= e($event['country']) ?></strong></span>
                    </div>
                    <p class="event-card-desc"><?= e($event['description']) ?></p>
                    
                    <div class="event-card-meta">
                      <span class="event-ticket-badge"><?= e($event['ticket_info'] ?? 'Free') ?></span>
                      <span class="event-time-badge"><?= e($event['time_info'] ?? 'All Day') ?></span>
                    </div>

                    <div class="event-card-actions">
                      <button type="button" class="btn btn-secondary btn-sm btn-focus-map" data-id="<?= (int)$event['id'] ?>">
                        SHOW ON MAP
                      </button>
                      <button type="button" class="btn btn-secondary btn-sm btn-rsvp <?= !empty($event['is_rsvpd']) ? 'active' : '' ?>" data-id="<?= (int)$event['id'] ?>">
                        <?= !empty($event['is_rsvpd']) ? '★ INTERESTED' : '+ INTERESTED' ?>
                      </button>
                      <a href="https://calendar.google.com/calendar/render?action=TEMPLATE&text=<?= urlencode($event['title']) ?>&dates=<?= date('Ymd', strtotime($event['date_start'])) ?>/<?= date('Ymd', strtotime($event['date_end'] ?? $event['date_start'] . ' +1 day')) ?>&details=<?= urlencode($event['description']) ?>&location=<?= urlencode($event['venue'] . ', ' . $event['city']) ?>" target="_blank" rel="noopener" class="btn-cal-export" title="Add to Google Calendar">
                        + G-CAL
                      </a>
                    </div>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>

            <!-- Empty Results Placeholder -->
            <div id="eventsEmptyState" class="events-empty-state" style="display: none;">
              <div class="empty-icon">🗺️</div>
              <h4>No events match your current filter or location.</h4>
              <p>Try resetting the search bar, clearing date filters, or switching to "All Events".</p>
              <button type="button" id="resetEventsFilterBtn" class="btn btn-primary" style="margin-top: 12px;">RESET ALL FILTERS</button>
            </div>

          </div>

        </div>

      </div>

    </div>
  </section>

</main>

<!-- Event Details Lightbox Modal -->
<div id="eventDetailModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalEventTitle">
  <div class="modal-box modal-box-lg">
    <div class="modal-header">
      <h3 id="modalEventTitle" class="modal-title">EVENT DETAILS</h3>
      <button type="button" id="closeEventModal" class="modal-close-btn">ESC / CLOSE</button>
    </div>
    <div id="eventModalContent" class="modal-body-scroll">
      <!-- Dynamic populated -->
    </div>
  </div>
</div>

<!-- Leaflet Library -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<?php include __DIR__ . '/includes/footer.php'; ?>
