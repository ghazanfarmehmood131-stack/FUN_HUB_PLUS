/**
 * ARCHIVE Ledger - Bolne Wala Chatbot & Interactive Site Guide
 * Web Speech API Synthesis (Voice/Audio Out), Speech Recognition (Voice In),
 * Interactive On-Screen Walkthrough, and Roman Urdu/English Q&A.
 */

(function() {
  'use strict';

  // State
  let isOpen = false;
  let isVoiceEnabled = true;
  let isListening = false;
  let recognition = null;
  let currentTourStep = 0;
  let activeUtterance = null;

  // Pre-load audio voice preference
  try {
    const stored = localStorage.getItem('archive_chatbot_voice');
    if (stored !== null) {
      isVoiceEnabled = stored === 'true';
    }
  } catch (e) {}

  // Knowledge base for instant client-side responses if offline
  const offlineKnowledge = [
    {
      keywords: ['tour', 'chala', 'seekh', 'sikh', 'guide', 'walkthrough', 'naya', 'new user', 'kaise use'],
      reply: '🎓 <strong>Website Chalana Seekhein:</strong><br>Fan Hub Plus par aap 8 pop culture categories explore kar saktay hain. Homepage par search bar se titles dhoondein, cheezain save karne ke liye <strong>+ SAVED</strong> click karein aur fan meetups ke liye <strong>Events & Map</strong> dekhein.<br><br>Kya aap screen par live <strong>Interactive Walkthrough</strong> shuru karna chahte hain?',
      speech: 'Fan Hub Plus website mein khush aamdeed! Yeh anime, gaming aur cinema ka structured catalog hai. Homepage par search bar se titles dhoondein, watchlist mein cheezain save karein, aur events map par fan meetups dhoondein. Kya aap live tour shuru karna chahte hain?',
      action: 'offer_tour',
      suggestions: ['Start Live Tour', 'Events & Map Kahan Hai?', 'Watchlist Help']
    },
    {
      keywords: ['event', 'map', 'naxsha', 'calendar', 'convention', 'meetup', 'aas pass', 'near me'],
      reply: '🗺️ <strong>Events aur Interactive Map:</strong><br>Hamare <strong>Events & Map</strong> page par aap dunya bhar aur Pakistan ke conventions aur meetups dhoond saktay hain.<br>• Leaflet Dark Map par pins dekhein.<br>• Monthly Calendar view use karein.<br>• <strong>Near Me</strong> button daba kar apne shehar ke kareeb events check karein!<br><br><a href="events.php" class="btn btn-primary" style="padding:4px 10px; font-size:12px; display:inline-block; margin-top:4px;">Events Page Kholein ➔</a>',
      speech: 'Events aur Map page par aap apne aas-pass hone wale anime conventions, gaming tournaments, aur meetups dhoond saktay hain. Wahan live map aur calendar dono mojood hain.',
      suggestions: ['Events & Map Kholein', 'Pakistan Ke Events', 'Website Tour']
    },
    {
      keywords: ['watchlist', 'save', 'bookmark'],
      reply: '⭐ <strong>Personal Watchlist:</strong><br>Kisi bhi title card par <strong>+ SAVED</strong> button daba kar aap usay apni list mein mehfooz kar saktay hain. Header mein <strong>SAVED</strong> button click karke apni mukammal list dekhein!',
      speech: 'Aap kisi bhi item ko Watchlist mein mehfooz kar saktay hain card par save button daba kar. Yeh aap ke browser mein save rehta hai.',
      suggestions: ['Watchlist Page', 'Sign In Kaise Karein?']
    },
    {
      keywords: ['search', 'dhoond', 'find', 'lookup'],
      reply: '🔍 <strong>Search Kaise Karein:</strong><br>Homepage par mojood single search bar use karein, ya Search Page par jayein. Aap title, director ya studio ke naam se search kar saktay hain.',
      speech: 'Homepage par search bar ya Search Page use karke kisi bhi title ko dhoond sakte hain.',
      suggestions: ['Search Page', 'Top Rated Titles']
    }
  ];

  // Interactive Tour Steps
  const tourSteps = [
    {
      target: '.brand-wrapper',
      title: '1. FAN HUB PLUS KYA HAI?',
      text: 'Yeh Japanese animation, gaming, cinema, comics, aur software ka structured reference ledger hai. Yahan sabhi categories organize ki gayi hain.',
      speech: 'Fan Hub Plus anime, gaming, cinema aur pop culture ka structured catalog hai jahan aap tamam records explore kar saktay hain.'
    },
    {
      target: '#primaryNav',
      title: '2. TAMAM 8 CATEGORIES',
      text: 'Navigation bar se aap Anime, Gaming, Movies, TV Shows, K-Pop, Comics, Manga, aur Software mein se kisi bhi category par ja saktay hain.',
      speech: 'Yahan primary navigation bar se aap tamam aath mukhtalif categories explore kar saktay hain.'
    },
    {
      target: '#heroSearchInput',
      title: '3. SINGLE SEARCH BAR',
      text: 'Homepage ke search bar se kisi bhi title ko dhoondhein. Search results dekhne ke liye query submit karein.',
      speech: 'Homepage ke search bar mein title likh kar search karein.'
    },
    {
      target: 'a[href="events.php"]',
      title: '4. EVENTS AUR INTERACTIVE MAP',
      text: 'Yahan se aap apne aas-pass hone wale fan conventions, anime expos, gaming tournaments, aur meetups ko interactive map aur calendar par dhoond saktay hain.',
      speech: 'Events aur Map page par aap apne aas-pass hone wale anime conventions aur meetups ko live map aur calendar par dhoond saktay hain.'
    },
    {
      target: 'a[href="watchlist.php"]',
      title: '5. PERSONAL WATCHLIST',
      text: 'Kisi bhi card par + SAVED click karke apne pasandeeda records ko personal ledger mein save karein.',
      speech: 'Aap kisi bhi card par save click karke usay apni personal watchlist mein save kar saktay hain.'
    },
    {
      target: '#chatbotLauncher',
      title: '6. HELP CENTER',
      text: 'Main har waqt yahan mojood hoon! Agar koi bhi cheez samajh na aaye to mujh se bol kar ya likh kar sawal poochein.',
      speech: 'Main Help Center hoon. Kisi bhi waqt mujh se website ke baray mein sawal poochiye!'
    }
  ];

  /**
   * Inject Chatbot HTML Widget into Page
   */
  function injectChatbotMarkup() {
    if (document.getElementById('chatbotLauncher')) return;

    const html = `
      <!-- Launcher Trigger -->
      <div id="chatbotLauncher" class="chatbot-launcher" role="button" aria-label="Open Help Center" title="Open voice chat" tabindex="0">
        <div class="chatbot-avatar-badge">
          <span class="launcher-pulse-ring"></span>
          <span>🎙️</span>
        </div>
        <div class="launcher-text-box">
          <span class="launcher-title">HELP CENTER</span>
          <span class="launcher-sub">Website Guide &amp; Voice ➔</span>
        </div>
      </div>

      <!-- Chat Window -->
      <div id="chatbotWindow" class="chatbot-window" role="dialog" aria-modal="true" aria-labelledby="chatbotHeaderTitle">
        
        <!-- Header -->
        <div class="chatbot-header">
          <div class="chatbot-header-info">
            <div class="chatbot-status-icon">
              <span>🎙️</span>
              <span class="status-online-dot"></span>
            </div>
            <div>
              <h3 id="chatbotHeaderTitle" class="chatbot-name">HELP CENTER</h3>
              <span class="chatbot-role">Interactive Voice &amp; Website Guide</span>
            </div>
          </div>
          <div class="chatbot-header-actions">
            <button type="button" id="btnTourTrigger" class="btn-chat-tool" title="Start Website Walkthrough">
              🎓 TOUR
            </button>
            <button type="button" id="btnVoiceToggle" class="btn-chat-tool ${isVoiceEnabled ? 'voice-active' : ''}" title="Toggle Audio Voice">
              ${isVoiceEnabled ? '🔊 VOICE ON' : '🔇 MUTE'}
            </button>
            <button type="button" id="btnChatClose" class="btn-chat-close" aria-label="Close Chat">&times;</button>
          </div>
        </div>

        <!-- Speaking Visualizer Wave Bar -->
        <div id="speakingWaveBar" class="speaking-wave-bar">
          <div style="display:flex; align-items:center; gap:8px;">
            <div class="equalizer-waves">
              <span class="equalizer-bar"></span>
              <span class="equalizer-bar"></span>
              <span class="equalizer-bar"></span>
              <span class="equalizer-bar"></span>
              <span class="equalizer-bar"></span>
            </div>
            <span>Bol raha hoon (Speaking response)...</span>
          </div>
          <button type="button" id="btnStopAudio" class="btn-stop-audio">MUTE / STOP</button>
        </div>

        <!-- Messages Stream -->
        <div id="chatbotMessages" class="chatbot-messages">
          <div class="chat-bubble-row bot">
            <div class="chat-msg-avatar">🤖</div>
            <div class="chat-bubble-content">
              <div class="chat-bubble">
                Assalam-o-Alaikum! Main ARCHIVE website ka <strong>Help Center</strong> hoon.<br><br>
                Main aap ko website chalana sikha sakta hoon aur aap ke sawalon ke jawab bol kar de sakta hoon.<br><br>
                Neeche diye gaye buttons par click karein ya mic se sawal bolein!
              </div>
              <div class="chat-bubble-meta">
                <span>Abhi (Just now)</span>
                <button type="button" class="btn-speak-again" onclick="window.ARCHIVE_BOT.speak('Assalam-o-Alaikum! Main ARCHIVE website ka Help Center hoon. Main aap ko website chalana sikha sakta hoon aur aap ke sawalon ke jawab bol kar de sakta hoon.')">🔊 Suniye</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Suggestion Chips Bar -->
        <div id="chatbotSuggestions" class="chatbot-suggestions">
          <button type="button" class="suggestion-chip" data-msg="Website chalana sikhao">🎓 Website Tour Sikhao</button>
          <button type="button" class="suggestion-chip" data-msg="Events aur map kahan hain?">🗺️ Events &amp; Map</button>
          <button type="button" class="suggestion-chip" data-msg="Watchlist kaise kaam karti hai?">⭐ Watchlist Help</button>
          <button type="button" class="suggestion-chip" data-msg="Search kaise karein?">🔍 Search Help</button>
        </div>

        <!-- Input Bar -->
        <div class="chatbot-input-bar">
          <button type="button" id="btnChatMic" class="btn-chat-mic" title="Boliye (Click to speak your question)">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
              <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
              <line x1="12" y1="19" x2="12" y2="23"/>
              <line x1="8" y1="23" x2="16" y2="23"/>
            </svg>
          </button>
          <input 
            type="text" 
            id="chatTextInput" 
            class="chat-input" 
            placeholder="Apna sawal likhein ya bolein..." 
            autocomplete="off"
          />
          <button type="button" id="btnChatSend" class="btn-chat-send" title="Send Question">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="22" y1="2" x2="11" y2="13"/>
              <polygon points="22 2 15 22 11 13 2 9 22 2"/>
            </svg>
          </button>
        </div>

      </div>

      <!-- Tour Elements Container -->
      <div id="tourBackdrop" class="tour-overlay-backdrop" style="display:none;"></div>
      <div id="tourTooltipCard" class="tour-tooltip-card" style="display:none;">
        <div id="tourStepCounter" class="tour-step-counter">STEP 1 OF 7</div>
        <h4 id="tourTitle" class="tour-title">TITLE</h4>
        <div id="tourBody" class="tour-body">EXPLANATION</div>
        <div class="tour-actions">
          <button type="button" id="btnTourPrev" class="btn btn-secondary btn-xs">PREV</button>
          <button type="button" id="btnTourSkip" class="btn btn-secondary btn-xs">SKIP TOUR</button>
          <button type="button" id="btnTourNext" class="btn btn-primary btn-xs">NEXT ➔</button>
        </div>
      </div>
    `;

    const wrapper = document.createElement('div');
    wrapper.id = 'archiveChatbotContainer';
    wrapper.innerHTML = html;
    document.body.appendChild(wrapper);

    initSpeechRecognition();
    attachChatbotEvents();
  }

  /**
   * Web Speech Synthesis (Speech Out / Bolne Wala)
   */
  function speak(text) {
    if (!isVoiceEnabled || !('speechSynthesis' in window)) return;

    window.speechSynthesis.cancel();

    // Clean HTML tags and markdown
    const cleanText = text.replace(/<[^>]*>?/gm, '').replace(/[\*_#`]/g, '').trim();
    if (!cleanText) return;

    const utterance = new SpeechSynthesisUtterance(cleanText);
    utterance.rate = 0.95;
    utterance.pitch = 1.0;

    // Pick best voice if available
    const voices = window.speechSynthesis.getVoices();
    const urduVoice = voices.find(v => v.lang.startsWith('ur') || v.lang.startsWith('hi'));
    const engVoice = voices.find(v => v.lang.startsWith('en-US') || v.lang.startsWith('en-GB') || v.lang.startsWith('en'));
    if (urduVoice) {
      utterance.voice = urduVoice;
    } else if (engVoice) {
      utterance.voice = engVoice;
    }

    const waveBar = document.getElementById('speakingWaveBar');

    utterance.onstart = function() {
      if (waveBar) waveBar.classList.add('active');
    };

    utterance.onend = function() {
      if (waveBar) waveBar.classList.remove('active');
      activeUtterance = null;
    };

    utterance.onerror = function() {
      if (waveBar) waveBar.classList.remove('active');
      activeUtterance = null;
    };

    activeUtterance = utterance;
    window.speechSynthesis.speak(utterance);
  }

  function stopSpeaking() {
    if ('speechSynthesis' in window) {
      window.speechSynthesis.cancel();
    }
    const waveBar = document.getElementById('speakingWaveBar');
    if (waveBar) waveBar.classList.remove('active');
  }

  /**
   * Web Speech Recognition (Speech In / Mic)
   */
  function initSpeechRecognition() {
    const SpeechRec = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRec) {
      const micBtn = document.getElementById('btnChatMic');
      if (micBtn) {
        micBtn.title = 'Voice input not supported in this browser. Please type your message.';
        micBtn.style.opacity = '0.5';
      }
      return;
    }

    recognition = new SpeechRec();
    recognition.continuous = false;
    recognition.interimResults = false;
    recognition.lang = 'en-US'; // or 'ur-PK'

    recognition.onstart = function() {
      isListening = true;
      const micBtn = document.getElementById('btnChatMic');
      if (micBtn) micBtn.classList.add('listening');
    };

    recognition.onresult = function(event) {
      const transcript = event.results[0][0].transcript;
      const input = document.getElementById('chatTextInput');
      if (input) {
        input.value = transcript;
        handleSendMessage();
      }
    };

    recognition.onerror = function(err) {
      console.warn('Speech recognition error:', err);
      isListening = false;
      const micBtn = document.getElementById('btnChatMic');
      if (micBtn) micBtn.classList.remove('listening');
    };

    recognition.onend = function() {
      isListening = false;
      const micBtn = document.getElementById('btnChatMic');
      if (micBtn) micBtn.classList.remove('listening');
    };
  }

  /**
   * Send Message to Bot
   */
  async function handleSendMessage(customText) {
    const input = document.getElementById('chatTextInput');
    const text = customText || (input ? input.value.trim() : '');
    if (!text) return;

    if (input && !customText) {
      input.value = '';
    }

    // Append User Message
    appendMessage(text, 'user');

    // Show temporary thinking bubble
    const thinkingId = appendMessage('<span class="pulse">Thinking &amp; analyzing...</span>', 'bot');

    try {
      const res = await fetch('api/chatbot.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: text })
      });
      const data = await res.json();
      removeMessage(thinkingId);

      if (data && data.reply) {
        appendMessage(data.reply, 'bot', data.speech, data.suggestions);
        if (data.action === 'offer_tour' || text.toLowerCase().includes('start live tour')) {
          // If explicitly requested, start tour
          if (text.toLowerCase().includes('start live tour') || text.toLowerCase().includes('shuru')) {
            startTour();
          }
        }
      } else {
        fallbackOfflineResponse(text, thinkingId);
      }
    } catch (e) {
      removeMessage(thinkingId);
      fallbackOfflineResponse(text);
    }
  }

  function fallbackOfflineResponse(text) {
    const lower = text.toLowerCase();
    const match = offlineKnowledge.find(k => k.keywords.some(w => lower.includes(w)));
    if (match) {
      appendMessage(match.reply, 'bot', match.speech, match.suggestions);
      if (match.action === 'offer_tour' && (lower.includes('start') || lower.includes('tour'))) {
        startTour();
      }
    } else {
      appendMessage(
        "Aap ka sawal note kar liya gaya hai! Main ARCHIVE ka <strong>Help Center</strong> hoon. Aap mujh se website chalana seekh saktay hain, events dhoond saktay hain, ya categories browse kar saktay hain.",
        'bot',
        "Main Help Center hoon. Aap mujh se website chalana seekh saktay hain ya events aur categories ke baray mein pooch saktay hain.",
        ['Website Chalana Sikhao', 'Events & Map Kahan Hai?', 'Browse Categories']
      );
    }
  }

  function appendMessage(content, sender, speechText = null, suggestions = null) {
    const stream = document.getElementById('chatbotMessages');
    if (!stream) return null;

    const id = 'msg_' + Date.now() + '_' + Math.random().toString(36).substr(2, 4);
    const row = document.createElement('div');
    row.id = id;
    row.className = `chat-bubble-row ${sender}`;

    const avatar = sender === 'bot' ? '🤖' : '👤';
    const speechAttr = speechText ? encodeURIComponent(speechText) : encodeURIComponent(content.replace(/<[^>]*>?/gm, ''));

    let bubbleHtml = `
      <div class="chat-msg-avatar">${avatar}</div>
      <div class="chat-bubble-content">
        <div class="chat-bubble">${content}</div>
        <div class="chat-bubble-meta">
          <span>Abhi</span>
          ${sender === 'bot' ? `<button type="button" class="btn-speak-again" onclick="window.ARCHIVE_BOT.speak(decodeURIComponent('${speechAttr}'))">🔊 Suniye</button>` : ''}
        </div>
      </div>
    `;

    row.innerHTML = bubbleHtml;
    stream.appendChild(row);
    stream.scrollTop = stream.scrollHeight;

    // Update suggestions bar if provided
    if (suggestions && Array.isArray(suggestions) && suggestions.length > 0) {
      updateSuggestions(suggestions);
    }

    // Speak bot answer automatically if enabled
    if (sender === 'bot' && speechText) {
      speak(speechText);
    }

    return id;
  }

  function removeMessage(id) {
    const el = document.getElementById(id);
    if (el) el.remove();
  }

  function updateSuggestions(suggestions) {
    const bar = document.getElementById('chatbotSuggestions');
    if (!bar) return;
    bar.innerHTML = suggestions.map(s => `
      <button type="button" class="suggestion-chip" data-msg="${s}">${s}</button>
    `).join('');
  }

  /**
   * Interactive Website Walkthrough (Tour)
   */
  function startTour() {
    currentTourStep = 0;
    renderTourStep();
  }

  function renderTourStep() {
    const backdrop = document.getElementById('tourBackdrop');
    const tooltip = document.getElementById('tourTooltipCard');
    const counter = document.getElementById('tourStepCounter');
    const titleEl = document.getElementById('tourTitle');
    const bodyEl = document.getElementById('tourBody');
    const prevBtn = document.getElementById('btnTourPrev');
    const nextBtn = document.getElementById('btnTourNext');

    if (!backdrop || !tooltip) return;

    // Remove old highlights
    document.querySelectorAll('.tour-target-highlight').forEach(el => el.classList.remove('tour-target-highlight'));

    if (currentTourStep >= tourSteps.length) {
      endTour();
      appendMessage("🎉 <strong>Zabardast!</strong> Aap ne mukammal website tour complete kar liya hai! Ab aap asaani se titles browse kar saktay hain, events map par check kar saktay hain, aur categories browse kar saktay hain.", 'bot', "Mubarak ho! Aap ne website tour mukammal kar liya hai. Ab aap website araam se chala saktay hain.");
      return;
    }

    const step = tourSteps[currentTourStep];
    const targetEl = document.querySelector(step.target);

    backdrop.style.display = 'block';
    tooltip.style.display = 'block';

    if (counter) counter.textContent = `STEP ${currentTourStep + 1} OF ${tourSteps.length}`;
    if (titleEl) titleEl.textContent = step.title;
    if (bodyEl) bodyEl.innerHTML = step.text;
    if (prevBtn) prevBtn.style.visibility = currentTourStep === 0 ? 'hidden' : 'visible';
    if (nextBtn) nextBtn.textContent = (currentTourStep === tourSteps.length - 1) ? 'FINISH ✓' : 'NEXT ➔';

    if (targetEl) {
      targetEl.classList.add('tour-target-highlight');
      targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });

      // Position tooltip near target element
      const rect = targetEl.getBoundingClientRect();
      let top = rect.bottom + 12;
      let left = rect.left;

      if (top + 180 > window.innerHeight) {
        top = Math.max(10, rect.top - 200);
      }
      if (left + 320 > window.innerWidth) {
        left = Math.max(10, window.innerWidth - 340);
      }

      tooltip.style.top = `${top}px`;
      tooltip.style.left = `${left}px`;
    } else {
      // Center tooltip if target not on current page
      tooltip.style.top = '40%';
      tooltip.style.left = 'calc(50% - 160px)';
    }

    // Speak current step out loud
    speak(step.speech);
  }

  function nextTourStep() {
    currentTourStep++;
    renderTourStep();
  }

  function prevTourStep() {
    if (currentTourStep > 0) {
      currentTourStep--;
      renderTourStep();
    }
  }

  function endTour() {
    const backdrop = document.getElementById('tourBackdrop');
    const tooltip = document.getElementById('tourTooltipCard');
    if (backdrop) backdrop.style.display = 'none';
    if (tooltip) tooltip.style.display = 'none';
    document.querySelectorAll('.tour-target-highlight').forEach(el => el.classList.remove('tour-target-highlight'));
    stopSpeaking();
  }

  /**
   * Attach UI Event Handlers
   */
  function attachChatbotEvents() {
    const launcher = document.getElementById('chatbotLauncher');
    const windowEl = document.getElementById('chatbotWindow');
    const closeBtn = document.getElementById('btnChatClose');
    const voiceToggleBtn = document.getElementById('btnVoiceToggle');
    const stopAudioBtn = document.getElementById('btnStopAudio');
    const sendBtn = document.getElementById('btnChatSend');
    const micBtn = document.getElementById('btnChatMic');
    const textInput = document.getElementById('chatTextInput');
    const suggestionsBar = document.getElementById('chatbotSuggestions');
    const tourTriggerBtn = document.getElementById('btnTourTrigger');

    // Tour buttons
    const tourNextBtn = document.getElementById('btnTourNext');
    const tourPrevBtn = document.getElementById('btnTourPrev');
    const tourSkipBtn = document.getElementById('btnTourSkip');
    const tourBackdrop = document.getElementById('tourBackdrop');

    // Open/Close
    if (launcher) {
      launcher.addEventListener('click', () => {
        isOpen = !isOpen;
        if (windowEl) windowEl.classList.toggle('open', isOpen);
        if (isOpen && textInput) {
          setTimeout(() => textInput.focus(), 300);
        }
      });
      launcher.addEventListener('keydown', event => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          launcher.click();
        }
      });
    }

    if (closeBtn) {
      closeBtn.addEventListener('click', () => {
        isOpen = false;
        if (windowEl) windowEl.classList.remove('open');
        stopSpeaking();
      });
    }

    // Voice ON / OFF Toggle
    if (voiceToggleBtn) {
      voiceToggleBtn.addEventListener('click', () => {
        isVoiceEnabled = !isVoiceEnabled;
        try {
          localStorage.setItem('archive_chatbot_voice', isVoiceEnabled ? 'true' : 'false');
        } catch (e) {}

        if (isVoiceEnabled) {
          voiceToggleBtn.classList.add('voice-active');
          voiceToggleBtn.textContent = '🔊 VOICE ON';
          speak("Voice chalu kar di gayi hai.");
        } else {
          voiceToggleBtn.classList.remove('voice-active');
          voiceToggleBtn.textContent = '🔇 MUTE';
          stopSpeaking();
        }
      });
    }

    if (stopAudioBtn) {
      stopAudioBtn.addEventListener('click', stopSpeaking);
    }

    // Send on click & enter
    if (sendBtn) {
      sendBtn.addEventListener('click', () => handleSendMessage());
    }
    if (textInput) {
      textInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          handleSendMessage();
        }
      });
    }

    // Mic
    if (micBtn) {
      micBtn.addEventListener('click', () => {
        if (!recognition) {
          speak("Aap ke browser mein mic input support mojood nahi hai. Baraye meharbani type karein.");
          return;
        }
        if (isListening) {
          recognition.stop();
        } else {
          try {
            recognition.start();
          } catch (err) {
            console.warn(err);
          }
        }
      });
    }

    // Suggestion chips
    if (suggestionsBar) {
      suggestionsBar.addEventListener('click', (e) => {
        const btn = e.target.closest('.suggestion-chip');
        if (!btn) return;
        const msg = btn.dataset.msg;
        if (msg) {
          handleSendMessage(msg);
        }
      });
    }

    // Tour buttons
    if (tourTriggerBtn) {
      tourTriggerBtn.addEventListener('click', () => {
        startTour();
      });
    }
    if (tourNextBtn) tourNextBtn.addEventListener('click', nextTourStep);
    if (tourPrevBtn) tourPrevBtn.addEventListener('click', prevTourStep);
    if (tourSkipBtn) tourSkipBtn.addEventListener('click', endTour);
    if (tourBackdrop) tourBackdrop.addEventListener('click', endTour);
  }

  // Global interface
  window.ARCHIVE_BOT = {
    speak,
    stopSpeaking,
    startTour,
    open: function() {
      isOpen = true;
      const windowEl = document.getElementById('chatbotWindow');
      if (windowEl) windowEl.classList.add('open');
    }
  };

  // Auto-init on page load
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', injectChatbotMarkup);
  } else {
    injectChatbotMarkup();
  }

})();
