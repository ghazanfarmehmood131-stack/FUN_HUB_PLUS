<?php
/**
 * ARCHIVE AI Assistant / Bolne Wala Chatbot API
 * Answers user questions in Roman Urdu & English, teaches site navigation,
 * explains features, and returns clean speech text for Web Speech API TTS.
 */
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../config.php';

// Accept POST json or GET
$input = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? $_GET['message'] ?? '');

if (empty($message)) {
    echo json_encode([
        'success' => false,
        'reply' => 'Assalam-o-Alaikum! Main ARCHIVE Help Center hoon. Aap mujh se website ke baray mein kuch bhi pooch saktay hain!',
        'speech' => 'Assalam-o-Alaikum! Main ARCHIVE Help Center hoon. Aap mujh se website ke baray mein kuch bhi pooch saktay hain!'
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$lower = strtolower($message);

// Knowledge base processor
$reply = '';
$speech = '';
$action = null; // e.g. 'start_tour', 'navigate_events'
$suggestions = [];

// 1. Interactive Tour / Website chalana sikhana
if (preg_match('/(tour|chala|seekh|sikh|guide|walkthrough|shuru|start|naye|new user|kese chal|kaise chal)/i', $lower)) {
    $reply = "🎓 <strong>Website Chalana Seekhein (Interactive Tour):</strong><br><br>"
           . "Fan Hub Plus ek structured pop culture catalog hai jahan aap 8 mukhtalif categories explore kar saktay hain:<br>"
           . "1️⃣ <strong>Browse & Categories:</strong> Anime, Gaming, Movies, TV, K-Pop, Comics, Manga, aur Software.<br>"
           . "2️⃣ <strong>Search:</strong> Keyboard pe <code>/</code> daba kar ya Search bar se kisi bhi title ko dhoondein.<br>"
           . "3️⃣ <strong>Watchlist:</strong> Kisi bhi card pe <strong>+ SAVED</strong> click karein taake wo aap ki personal list mein save ho jaye.<br>"
           . "4️⃣ <strong>Events & Map:</strong> Aas-pass ke fan conventions, anime expos aur meetups dhoondnay ke liye <a href='events.php' class='chat-link'>Events & Map</a> dekhein.<br>"
           . "Kya aap chahte hain ke main abhi screen par live <strong>Interactive Walkthrough</strong> shuru karoon?";
    $speech = "Fan Hub Plus website mein khush aamdeed! Yeh 8 pop culture categories ka catalog hai. Aap search bar se titles dhoond saktay hain, watchlist mein cheezain save kar saktay hain, aur events map par conventions dekh saktay hain. Kya aap live tour shuru karna chahte hain?";
    $action = 'offer_tour';
    $suggestions = ['Start Live Tour', 'Events & Map Kahan Hai?', 'Watchlist Kaise Kaam Karti Hai?'];

// 3. Events & Map / Conventions / Meetups / Calendar
} elseif (preg_match('/(event|map|naxsha|calendar|convention|meetup|program|aas pass|near me|location|expo|comic con|twincon)/i', $lower)) {
    $reply = "🗺️ <strong>Events aur Interactive Map:</strong><br><br>"
           . "Hamare <strong>Events & Map</strong> page par aap dunya bhar aur Pakistan ke tamam fan programs dhoond saktay hain:<br>"
           . "• 📍 <strong>Interactive Dark Map:</strong> OpenStreetMap aur Leaflet ke zariye live map par pins check karein.<br>"
           . "• 📅 <strong>Interactive Calendar:</strong> Mahinay ke hisaab se date grid aur programs ki timeline dekhein.<br>"
           . "• 🎯 <strong>Aas-Pass Ke Events (Near Me):</strong> 'Near Me' button daba kar apne shehar (e.g. Islamabad, Karachi, Lahore, Tokyo waghera) ke kareeb programs dhoondein.<br>"
           . "• 🔔 <strong>RSVP & Add to Calendar:</strong> Apne pasandeeda event ko bookmark karein ya Google Calendar mein add karein.<br><br>"
           . "<a href='events.php' class='btn btn-primary' style='padding: 6px 14px; font-size: 13px; display: inline-block; margin-top: 5px;'>Events & Map Page Kholein ➔</a>";
    $speech = "Events aur Map page par aap apne aas-pass hone wale anime conventions, gaming tournaments, aur cosplay meetups dhoond saktay hain. Wahan live map aur calendar dono mojood hain.";
    $action = 'show_events_preview';
    $suggestions = ['Events & Map Kholein', 'Pakistan Mein Kaunse Events Hain?', 'Website Kaise Use Karein?'];

// 4. Watchlist
} elseif (preg_match('/(watchlist|save|bookmark|list|mehfooz)/i', $lower)) {
    $reply = "⭐ <strong>Personal Watchlist Ledger:</strong><br><br>"
           . "Aap kisi bhi anime, game, ya movie ke card par <strong>+ SAVED</strong> button daba kar usay apni Watchlist mein shamil kar saktay hain.<br>"
           . "• Watchlist browser mein foran save ho jati hai.<br>"
           . "• Agar aap Sign In karein to aap ka data MySQL database mein permanently secure rehta hai.<br>"
           . "• Header mein <strong>SAVED</strong> button click karke apni mukammal list dekh saktay hain!";
    $speech = "Aap kisi bhi item ko Watchlist mein save kar saktay hain card par save button daba kar. Yeh aap ke browser aur account dono mein mehfooz rehta hai.";
    $suggestions = ['Watchlist Kholein', 'Sign In Kaise Karein?', 'Browse All Items'];

// 5. Search
} elseif (preg_match('/(search|dhoond|khol|find|lookup|dhond)/i', $lower)) {
    $reply = "🔍 <strong>Search Kaise Karein:</strong><br><br>"
           . "• Homepage par mojood search bar use karein, ya <a href='search.php' class='chat-link'>Search Page</a> par jayein.<br>"
           . "• Homepage par maujood single search bar use karein ya <a href='search.php' class='chat-link'>Search Page</a> par jayein.<br>"
           . "• Aap title, director, category ya studio ke naam se search kar saktay hain (e.g. <em>Elden Ring</em>, <em>Frieren</em>, <em>Dune</em>, <em>Shogun</em>).";
    $speech = "Homepage par search bar ya search page use karein. Aap title, director ya franchise ke naam se dhoond saktay hain.";
    $suggestions = ['Search Page Kholein', 'Top Rated Titles', 'Events Dhoondein'];

// 6. Account / Login / Sign In
} elseif (preg_match('/(login|sign in|account|register|password|id)/i', $lower)) {
    $reply = "👤 <strong>User Account & Sign In:</strong><br><br>"
           . "• Header mein <strong>SIGN IN</strong> button par click karein.<br>"
           . "• Agar account nahi hai to sign-up form se apna personal account banayein.<br>"
           . "• Admin account sirf website owner trusted server terminal se create karta hai; koi shared demo password nahi hai.<br>"
           . "• Sign in karne ke baad aap ki Watchlist account ke saath sync rehti hai.";
    $speech = "Header mein Sign In button par click karein. Naya user account sign-up form se banayein. Administrator account website owner trusted terminal se set karta hai; shared demo password nahi hai.";
    $suggestions = ['Sign In Page Kholein', 'Watchlist Kaise Banayein?', 'Website Tour'];

// 7. Categories / Specific genres
} elseif (preg_match('/(anime|gaming|game|movie|film|tv|kpop|comic|manga|software)/i', $lower)) {
    $reply = "📚 <strong>ARCHIVE Categories:</strong><br><br>"
           . "Hum 8 specialized pop culture categories maintain kartay hain:<br>"
           . "1. <strong>Anime:</strong> Japanese serialized animations & OVAs<br>"
           . "2. <strong>Gaming:</strong> Triple-A & indie interactive epics<br>"
           . "3. <strong>Movies:</strong> Feature films & archival cinema<br>"
           . "4. <strong>TV Shows:</strong> Prestige serialized drama<br>"
           . "5. <strong>K-Pop:</strong> Concert films & world tour documentaries<br>"
           . "6. <strong>Comics:</strong> Graphic novels & creator-owned works<br>"
           . "7. <strong>Manga:</strong> Japanese serialized graphic literature<br>"
           . "8. <strong>Software:</strong> Applications, developer tools, platform support, and system requirements<br><br>"
           . "<a href='browse.php' class='btn btn-secondary' style='padding: 6px 14px; font-size: 13px; display: inline-block;'>Browse All Categories ➔</a>";
    $speech = "Hum aath mukhtalif categories maintain kartay hain: Anime, Gaming, Movies, TV shows, K-Pop, Comics, Manga, aur Software. Aap Browse page par ja kar sab explore kar saktay hain.";
    $suggestions = ['Browse Page Kholein', 'Events & Map'];

// Default greeting / response
} else {
    $reply = "Aap ka sawal mil gaya! Main ARCHIVE ka <strong>Help Center</strong> hoon.<br><br>"
           . "Aap mujh se ye cheezain pooch saktay hain:<br>"
           . "• 🎓 <em>'Website chalana sikhao'</em> (Interactive Walkthrough Tour)<br>"
           . "• 🗺️ <em>'Events aur map kahan hain?'</em> (Fan conventions & meetups)<br>"
           . "• ⭐ <em>'Watchlist kaise use karein?'</em><br>"
           . "• 🔍 <em>'Search kaise kaam karta hai?'</em><br><br>"
           . "Kisi bhi option par click karein ya apna sawal likhein!";
    $speech = "Main ARCHIVE Help Center hoon. Aap mujh se website chalana seekh saktay hain ya events aur conventions dhoond saktay hain.";
    $suggestions = ['Website Chalana Sikhao (Tour)', 'Events & Map Kahan Hai?', 'Watchlist Help'];
}

echo json_encode([
    'success' => true,
    'reply' => $reply,
    'speech' => $speech,
    'action' => $action,
    'suggestions' => $suggestions
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
