-- Fan Hub Plus: clean MySQL 8 / MariaDB 10.5+ baseline.
-- Creates an isolated database; it never drops or empties an existing database/table.
-- Run once against a MySQL server with a database-creation-capable setup account.
CREATE DATABASE IF NOT EXISTS `fan_hub_plus`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `fan_hub_plus`;

CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(50) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `short_label` VARCHAR(50) NOT NULL,
  `description` TEXT NULL,
  `icon` VARCHAR(50) NOT NULL DEFAULT 'film',
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(254) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('user','admin') NOT NULL DEFAULT 'user',
  `avatar_url` VARCHAR(500) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role_created` (`role`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `media_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `external_id` VARCHAR(100) NOT NULL,
  `category_slug` VARCHAR(50) NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `original_title` VARCHAR(255) NULL,
  `year` SMALLINT UNSIGNED NULL,
  `score` DECIMAL(3,1) NOT NULL DEFAULT 0.0,
  `status` VARCHAR(50) NOT NULL DEFAULT 'Released',
  `duration_or_episodes` VARCHAR(100) NULL,
  `genres` VARCHAR(500) NULL,
  `synopsis` TEXT NULL,
  `poster_url` VARCHAR(1000) NULL,
  `backdrop_url` VARCHAR(1000) NULL,
  `trailer_key` VARCHAR(100) NULL,
  `trailer_url` VARCHAR(1000) NULL,
  `director_or_creator` VARCHAR(255) NULL,
  `studio_or_publisher` VARCHAR(255) NULL,
  `extra_meta` LONGTEXT NULL,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `is_trending` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_media_external_id` (`external_id`),
  KEY `idx_media_category_score` (`category_slug`,`score`),
  KEY `idx_media_type` (`type`),
  KEY `idx_media_score` (`score`),
  KEY `idx_media_year` (`year`),
  KEY `idx_media_featured_score` (`is_featured`,`score`),
  KEY `idx_media_trending` (`is_trending`),
  CONSTRAINT `fk_media_category` FOREIGN KEY (`category_slug`) REFERENCES `categories` (`slug`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `media_videos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `media_item_id` INT UNSIGNED NOT NULL,
  `episode_label` VARCHAR(150) NOT NULL,
  `file_name` VARCHAR(80) NOT NULL,
  `mime_type` VARCHAR(100) NOT NULL,
  `file_size` BIGINT UNSIGNED NOT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_media_videos_file_name` (`file_name`),
  KEY `idx_media_videos_playlist` (`media_item_id`,`sort_order`,`id`),
  CONSTRAINT `fk_media_videos_item` FOREIGN KEY (`media_item_id`) REFERENCES `media_items` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `watchlist` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `media_item_id` INT UNSIGNED NULL,
  `external_id` VARCHAR(100) NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `category` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `year` SMALLINT UNSIGNED NULL,
  `score` DECIMAL(3,1) NULL,
  `poster_url` VARCHAR(1000) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_watchlist_user_item` (`user_id`,`external_id`,`type`),
  KEY `idx_watchlist_category` (`category`),
  KEY `idx_watchlist_media_item` (`media_item_id`),
  CONSTRAINT `fk_watchlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_watchlist_media` FOREIGN KEY (`media_item_id`) REFERENCES `media_items` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `events` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `category` VARCHAR(50) NOT NULL,
  `event_type` VARCHAR(50) NOT NULL DEFAULT 'In-Person',
  `date_start` DATE NOT NULL,
  `date_end` DATE NULL,
  `time_info` VARCHAR(100) NOT NULL DEFAULT '10:00 AM - 6:00 PM',
  `venue` VARCHAR(255) NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `country` VARCHAR(100) NOT NULL,
  `latitude` DECIMAL(10,7) NOT NULL,
  `longitude` DECIMAL(10,7) NOT NULL,
  `description` TEXT NOT NULL,
  `organizer` VARCHAR(150) NULL,
  `banner_url` VARCHAR(1000) NULL,
  `badge_text` VARCHAR(50) NOT NULL DEFAULT 'CONVENTION',
  `ticket_info` VARCHAR(100) NOT NULL DEFAULT 'Free / Public',
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_event_title_start` (`title`,`date_start`),
  KEY `idx_events_category_date` (`category`,`date_start`),
  KEY `idx_events_city` (`city`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `merchandise` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `category` VARCHAR(50) NOT NULL,
  `franchise` VARCHAR(150) NOT NULL,
  `item_type` VARCHAR(100) NOT NULL,
  `image_url` VARCHAR(1000) NULL,
  `scale_or_size` VARCHAR(100) NOT NULL DEFAULT 'Standard',
  `material` VARCHAR(150) NOT NULL DEFAULT 'PVC, ABS',
  `manufacturer` VARCHAR(150) NOT NULL DEFAULT 'Bandai Spirits',
  `rarity_tier` VARCHAR(50) NOT NULL DEFAULT 'Collector Grade',
  `year_released` SMALLINT UNSIGNED NOT NULL DEFAULT 2024,
  `description` TEXT NOT NULL,
  `accession_no` VARCHAR(50) NOT NULL,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_merchandise_accession` (`accession_no`),
  KEY `idx_merchandise_category` (`category`),
  KEY `idx_merchandise_franchise` (`franchise`),
  KEY `idx_merchandise_rarity` (`rarity_tier`),
  KEY `idx_merchandise_featured` (`is_featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_activity` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL,
  `action` VARCHAR(40) NOT NULL,
  `target` VARCHAR(120) NULL,
  `detail` VARCHAR(255) NULL,
  `ip` VARCHAR(45) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activity_action_ip` (`action`,`ip`,`created_at`),
  KEY `idx_activity_action_target` (`action`,`target`,`created_at`),
  KEY `idx_activity_user` (`user_id`),
  CONSTRAINT `fk_admin_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `users` (`name`,`email`,`password_hash`,`role`) VALUES
('Site Administrator','ghazanfarmehmood131@gmail.com','$2y$10$4rnNBTSFi5mirZPJkZKTiOmFr9VAhMwR4x1pJVIbaGu54NXmioFN2','admin');

-- Stable application taxonomy: inserts are repeatable and do not overwrite edits.
INSERT IGNORE INTO `categories` (`slug`,`name`,`short_label`,`description`,`icon`,`sort_order`) VALUES
('anime','Anime','ANIME','Japanese animated productions, OVA releases, and serialized adaptations.','tv',1),
('gaming','Games','GAMES','Triple-A and independent interactive titles, RPGs, and action epics.','gamepad',2),
('movies','Movies','MOVIES','Feature films, archival cinema, auteur director showcases, and theatrical releases.','film',3),
('tvshows','TV Series','TV SERIES','Serialized television drama, limited series, and streaming originals.','tv',4),
('kpop','Korean Movies','KOREAN MOVIES','Concert feature films, world tours, visual showcases, and musical documentaries.','music',5),
('comics','Web Series','WEB SERIES','Western graphic novels, creator-owned series, superhero deconstructions, and noir.','book-open',6),
('manga','Japanese Comics','JAPANESE COMICS','Serialized Japanese graphic literature, seinen masterpieces, and tankōbon volumes.','book',7),
('software','Software','SOFTWARE','Desktop, mobile, and web applications, developer tools, and digital platforms.','code',9);

-- 1. ANIME
INSERT IGNORE INTO `media_items` (`external_id`, `category_slug`, `type`, `title`, `original_title`, `year`, `score`, `status`, `duration_or_episodes`, `genres`, `synopsis`, `poster_url`, `backdrop_url`, `trailer_key`, `trailer_url`, `director_or_creator`, `studio_or_publisher`, `extra_meta`, `is_featured`, `is_trending`) VALUES
('anime-5114', 'anime', 'anime', 'Fullmetal Alchemist: Brotherhood', 'Hagane no Renkinjutsushi: Fullmetal Alchemist', 2009, 9.1, 'Finished Airing', '64 Episodes', 'Action, Adventure, Drama, Fantasy', 'After a horrific alchemy experiment goes wrong in the Elric household, brothers Edward and Alphonse are left in a catastrophic new reality. Disregarding the alchemical restriction against human transmutation, the boys attempted to bring their recently deceased mother back to life.', 'https://cdn.myanimelist.net/images/anime/1208/94745l.jpg', 'https://cdn.myanimelist.net/images/anime/1208/94745l.jpg', '--IcmZkvL0Q', 'https://www.youtube.com/embed/--IcmZkvL0Q', 'Yasuhiro Irie', 'Bones', '{"cast": [{"name": "Edward Elric", "role": "Main"}, {"name": "Alphonse Elric", "role": "Main"}]}', 1, 1),

('anime-1', 'anime', 'anime', 'Cowboy Bebop', 'Cowboy Bebop', 1998, 8.8, 'Finished Airing', '26 Episodes', 'Action, Sci-Fi, Neo-Noir', 'Crime is timeless. By the year 2071, humanity has expanded across the galaxy, filling the surface of other planets to escape earth ruin. Spike Spiegel and Jet Black pursue bounties across space aboard the Bebop.', 'https://cdn.myanimelist.net/images/anime/4/19644l.jpg', 'https://cdn.myanimelist.net/images/anime/4/19644l.jpg', 'qig4KMGmmr8', 'https://www.youtube.com/embed/qig4KMGmmr8', 'Shinichirō Watanabe', 'Sunrise', '{"cast": [{"name": "Spike Spiegel", "role": "Main"}, {"name": "Faye Valentine", "role": "Main"}]}', 1, 0),

('anime-16498', 'anime', 'anime', 'Attack on Titan', 'Shingeki no Kyojin', 2013, 8.5, 'Finished Airing', '25 Episodes', 'Action, Suspense, Fantasy', 'Centuries ago, mankind was slaughtered to near extinction by monstrous humanoid creatures called Titans, forcing humans to hide in fear behind enormous concentric walls.', 'https://cdn.myanimelist.net/images/anime/10/47347l.jpg', 'https://cdn.myanimelist.net/images/anime/10/47347l.jpg', 'MGRm4IzK1SQ', 'https://www.youtube.com/embed/MGRm4IzK1SQ', 'Tetsuro Araki', 'Wit Studio', '{"cast": [{"name": "Eren Yeager", "role": "Main"}, {"name": "Levi Ackerman", "role": "Supporting"}]}', 0, 1),

('anime-199', 'anime', 'anime', 'Spirited Away', 'Sen to Chihiro no Kamikakushi', 2001, 8.9, 'Finished', '125 min', 'Adventure, Award Winning, Supernatural', 'Ten-year-old Chihiro Ogino and her parents stumble upon what appears to be an abandoned amusement park. Chihiro discovers a mystical bathhouse where spirits rest, governed by the witch Yubaba.', 'https://cdn.myanimelist.net/images/anime/6/79597l.jpg', 'https://cdn.myanimelist.net/images/anime/6/79597l.jpg', 'ByXuk9QqQkk', 'https://www.youtube.com/embed/ByXuk9QqQkk', 'Hayao Miyazaki', 'Studio Ghibli', '{"cast": [{"name": "Chihiro Ogino", "role": "Main"}, {"name": "Haku", "role": "Main"}]}', 0, 1);

-- 2. GAMING
INSERT IGNORE INTO `media_items` (`external_id`, `category_slug`, `type`, `title`, `original_title`, `year`, `score`, `status`, `duration_or_episodes`, `genres`, `synopsis`, `poster_url`, `backdrop_url`, `trailer_key`, `trailer_url`, `director_or_creator`, `studio_or_publisher`, `extra_meta`, `is_featured`, `is_trending`) VALUES
('game-elden-ring', 'gaming', 'gaming', 'Elden Ring', 'Elden Ring', 2022, 9.6, 'Available', 'PlayStation, Xbox, PC', 'Action RPG, Open World, Dark Fantasy', 'Rise, Tarnished, and be guided by grace to brandish the power of the Elden Ring and become an Elden Lord in the Lands Between. A vast world where open fields with a variety of situations and huge dungeons are seamlessly connected.', 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=600&q=80', 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=1200&q=80', 'E3Huy2cdih0', 'https://www.youtube.com/embed/E3Huy2cdih0', 'Hidetaka Miyazaki', 'Bandai Namco / FromSoftware', '{"platform": "PlayStation 5, Xbox Series X, PC", "cast": [{"name": "Hidetaka Miyazaki", "role": "Director"}, {"name": "George R.R. Martin", "role": "Mythos"}]}', 1, 1),

('game-cyberpunk-2077', 'gaming', 'gaming', 'Cyberpunk 2077: Phantom Liberty', 'Cyberpunk 2077', 2023, 9.1, 'Available', 'PC, PS5, Xbox Series X', 'Action RPG, Cyberpunk, Open World', 'Enter the shadowy world of espionage in Dogtown, a dangerous district of Night City. As cyber-enhanced mercenary V, team up with sleeper agent Solomon Reed to unravel a web of shattered loyalties and political machinations.', 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=600&q=80', 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=1200&q=80', 'kfXde_1qE0c', 'https://www.youtube.com/embed/kfXde_1qE0c', 'Gabe Amatangelo', 'CD PROJEKT RED', '{"platform": "PC, PlayStation 5, Xbox Series X", "cast": [{"name": "Keanu Reeves", "role": "Johnny Silverhand"}, {"name": "Idris Elba", "role": "Solomon Reed"}]}', 0, 1),

('game-ff7-rebirth', 'gaming', 'gaming', 'Final Fantasy VII Rebirth', 'Final Fantasy VII Rebirth', 2024, 9.3, 'Available', 'PlayStation 5', 'JRPG, Fantasy, Sci-Fi', 'The journey into the unknown continues. Cloud, Barret, Tifa, Aerith, and Red XIII escape the dystopian city of Midgar and venture into the wide world in pursuit of Sephiroth across expansive frontiers.', 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=600&q=80', 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=1200&q=80', '1h-u_0i6-uE', 'https://www.youtube.com/embed/1h-u_0i6-uE', 'Naoki Hamaguchi', 'Square Enix', '{"platform": "PlayStation 5", "cast": [{"name": "Cloud Strife", "role": "Protagonist"}, {"name": "Sephiroth", "role": "Antagonist"}]}', 0, 0),

('game-persona-5-royal', 'gaming', 'gaming', 'Persona 5 Royal', 'Persona 5 Royal', 2020, 9.5, 'Available', 'Multi-Platform', 'JRPG, Supernatural, Social Sim', 'Don the mask of Joker and join the Phantom Thieves of Hearts as they stage heists into the hearts of corrupt adults in modern Tokyo, stealing malice to reform society from within.', 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?w=600&q=80', 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?w=1200&q=80', 'SKpSpvFQYL8', 'https://www.youtube.com/embed/SKpSpvFQYL8', 'Katsura Hashino', 'Atlus / Sega', '{"platform": "PlayStation, Xbox, Nintendo Switch, PC", "cast": [{"name": "Joker", "role": "Leader"}, {"name": "Morgana", "role": "Guide"}]}', 0, 1);

-- 3. MOVIES
INSERT IGNORE INTO `media_items` (`external_id`, `category_slug`, `type`, `title`, `original_title`, `year`, `score`, `status`, `duration_or_episodes`, `genres`, `synopsis`, `poster_url`, `backdrop_url`, `trailer_key`, `trailer_url`, `director_or_creator`, `studio_or_publisher`, `extra_meta`, `is_featured`, `is_trending`) VALUES
('movie-693134', 'movies', 'movie', 'Dune: Part Two', 'Dune: Part Two', 2024, 8.3, 'Released', '166 min', 'Science Fiction, Adventure', 'Follow the mythic journey of Paul Atreides as he unites with Chani and the Fremen while on a warpath of revenge against the conspirators who destroyed his family, facing a choice between love and destiny.', 'https://image.tmdb.org/t/p/w500/1pdfLvkbY9ohJlCjQH2CZjjYVvJ.jpg', 'https://image.tmdb.org/t/p/original/xOMo8BRK7PfcJv9JCnx7s5200SV.jpg', 'Way9Dexny3w', 'https://www.youtube.com/embed/Way9Dexny3w', 'Denis Villeneuve', 'Warner Bros. Pictures / Legendary', '{"runtime": 166, "cast": [{"name": "Timothée Chalamet", "role": "Paul Atreides"}, {"name": "Zendaya", "role": "Chani"}]}', 1, 1),

('movie-872585', 'movies', 'movie', 'Oppenheimer', 'Oppenheimer', 2023, 8.1, 'Released', '180 min', 'Drama, History, Biography', 'The story of J. Robert Oppenheimer role in the development of the atomic bomb during World War II, examining the moral weight and political fallout of scientific discovery.', 'https://image.tmdb.org/t/p/w500/8Gxv8gSFCU0XGDykEGv7zR1n2ua.jpg', 'https://image.tmdb.org/t/p/original/fm6KqXpk3M2HVveGHudvK0w1Rj7.jpg', 'uYPbbksJxIg', 'https://www.youtube.com/embed/uYPbbksJxIg', 'Christopher Nolan', 'Universal Pictures / Syncopy', '{"runtime": 180, "cast": [{"name": "Cillian Murphy", "role": "J. Robert Oppenheimer"}, {"name": "Emily Blunt", "role": "Kitty Oppenheimer"}]}', 0, 1),

('movie-335984', 'movies', 'movie', 'Blade Runner 2049', 'Blade Runner 2049', 2017, 8.0, 'Released', '164 min', 'Science Fiction, Mystery, Thriller', 'Thirty years after the events of the first film, a new blade runner, LAPD Officer K, unearths a long-buried secret that has the potential to plunge what is left of society into chaos.', 'https://image.tmdb.org/t/p/w500/gajva2L0rPYkEWjzgFlBXCAVBE5.jpg', 'https://image.tmdb.org/t/p/original/ilRyAZw2juCU9nxBPqJ4dQyv8j0.jpg', 'gCcx85zbxz4', 'https://www.youtube.com/embed/gCcx85zbxz4', 'Denis Villeneuve', 'Warner Bros. / Columbia Pictures', '{"runtime": 164, "cast": [{"name": "Ryan Gosling", "role": "Officer K"}, {"name": "Harrison Ford", "role": "Rick Deckard"}]}', 0, 0),

('movie-496243', 'movies', 'movie', 'Parasite', 'Gisaengchung', 2019, 8.5, 'Released', '132 min', 'Thriller, Drama, Black Comedy', 'All unemployed, Ki-taek family takes peculiar interest in the wealthy and glamorous Parks for their livelihood until they get entangled in an unexpected incident.', 'https://image.tmdb.org/t/p/w500/7IiTTgloJzvGI1TAYymCfbfl3vT.jpg', 'https://image.tmdb.org/t/p/original/hiKmpnn79rq779gf09q.jpg', '5xH0R_fxnyQ', 'https://www.youtube.com/embed/5xH0R_fxnyQ', 'Bong Joon-ho', 'CJ Entertainment / Barunson E&A', '{"runtime": 132, "cast": [{"name": "Song Kang-ho", "role": "Kim Ki-taek"}, {"name": "Choi Woo-shik", "role": "Kim Ki-woo"}]}', 0, 1),

('movie-157336', 'movies', 'movie', 'Interstellar', 'Interstellar', 2014, 8.4, 'Released', '169 min', 'Adventure, Drama, Science Fiction', 'The adventures of a group of explorers who make use of a newly discovered wormhole to surpass the limitations on human space travel and conquer the vast distances involved in an interstellar voyage.', 'https://image.tmdb.org/t/p/w500/gEU2QniE6E77NI6lCU6MxlNBvIx.jpg', 'https://image.tmdb.org/t/p/original/gEU2QniE6E77NI6lCU6MxlNBvIx.jpg', 'zSWdZVtXT7E', 'https://www.youtube.com/embed/zSWdZVtXT7E', 'Christopher Nolan', 'Paramount / Syncopy', '{"runtime": 169, "cast": [{"name": "Matthew McConaughey", "role": "Joseph Cooper"}, {"name": "Anne Hathaway", "role": "Amelia Brand"}]}', 0, 1);

-- 4. TV SHOWS
INSERT IGNORE INTO `media_items` (`external_id`, `category_slug`, `type`, `title`, `original_title`, `year`, `score`, `status`, `duration_or_episodes`, `genres`, `synopsis`, `poster_url`, `backdrop_url`, `trailer_key`, `trailer_url`, `director_or_creator`, `studio_or_publisher`, `extra_meta`, `is_featured`, `is_trending`) VALUES
('tv-shogun-2024', 'tvshows', 'tvshows', 'Shōgun', 'Shōgun', 2024, 9.2, 'Completed Season', '10 Episodes', 'Historical Drama, War, Political', 'In Japan in the year 1600, at the dawn of a century-defining civil war, Lord Yoshii Toranaga is fighting for his life as his enemies on the Council of Regents unite against him, while a mysterious European ship arrives.', 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=600&q=80', 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=1200&q=80', 'yBb_X46o6uM', 'https://www.youtube.com/embed/yBb_X46o6uM', 'Rachel Kondo & Justin Marks', 'FX / Hulu', '{"seasons": 1, "network": "FX / Hulu", "cast": [{"name": "Hiroyuki Sanada", "role": "Lord Yoshii Toranaga"}, {"name": "Anna Sawai", "role": "Toda Mariko"}]}', 1, 1),

('tv-arcane-2021', 'tvshows', 'tvshows', 'Arcane', 'Arcane: League of Legends', 2021, 9.4, 'Renewed', '18 Episodes', 'Animation, Action, Sci-Fi', 'Amid the stark discord of twin cities Piltover and Zaun, two sisters fight on rival sides of a war between magic technologies and incompatible convictions.', 'https://images.unsplash.com/photo-1563089145-599997674d42?w=600&q=80', 'https://images.unsplash.com/photo-1563089145-599997674d42?w=1200&q=80', 'fXmAurh012s', 'https://www.youtube.com/embed/fXmAurh012s', 'Christian Linke & Alex Yee', 'Netflix / Riot Games / Fortiche', '{"seasons": 2, "network": "Netflix", "cast": [{"name": "Hailee Steinfeld", "role": "Vi"}, {"name": "Ella Purnell", "role": "Jinx"}]}', 0, 1),

('tv-breaking-bad', 'tvshows', 'tvshows', 'Breaking Bad', 'Breaking Bad', 2008, 9.5, 'Ended', '62 Episodes', 'Crime, Drama, Thriller', 'A chemistry teacher diagnosed with inoperable lung cancer turns to manufacturing and selling methamphetamine with a former student in order to secure his family financial future.', 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=600&q=80', 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=1200&q=80', 'HhesaQXLuRY', 'https://www.youtube.com/embed/HhesaQXLuRY', 'Vince Gilligan', 'AMC / Sony Pictures Television', '{"seasons": 5, "network": "AMC", "cast": [{"name": "Bryan Cranston", "role": "Walter White"}, {"name": "Aaron Paul", "role": "Jesse Pinkman"}]}', 0, 1);

-- 5. K-POP
INSERT IGNORE INTO `media_items` (`external_id`, `category_slug`, `type`, `title`, `original_title`, `year`, `score`, `status`, `duration_or_episodes`, `genres`, `synopsis`, `poster_url`, `backdrop_url`, `trailer_key`, `trailer_url`, `director_or_creator`, `studio_or_publisher`, `extra_meta`, `is_featured`, `is_trending`) VALUES
('kpop-bts-yet-to-come', 'kpop', 'kpop', 'BTS: Yet to Come in Cinemas', 'BTS: Yet to Come in Busan', 2023, 9.1, 'Released', '104 min', 'Concert Film, Music Documentary, Pop', 'A special cinematic cut of the massive Busan World Expo concert featuring RM, Jin, SUGA, j-hope, Jimin, V, and Jung Kook performing career-spanning hit songs with full stadium choreography.', 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=600&q=80', 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=1200&q=80', '3F3o04QzN4o', 'https://www.youtube.com/embed/3F3o04QzN4o', 'BIGHIT MUSIC', 'HYBE / CJ 4DPLEX', '{"agency": "BIGHIT MUSIC / HYBE", "artists": "RM, Jin, SUGA, j-hope, Jimin, V, Jung Kook", "cast": [{"name": "RM", "role": "Leader / Rapper"}, {"name": "Jung Kook", "role": "Main Vocalist"}]}', 1, 1),

('kpop-blackpink-light-up', 'kpop', 'kpop', 'BLACKPINK: Light Up the Sky', 'BLACKPINK: Light Up the Sky', 2020, 8.6, 'Released', '79 min', 'Documentary, Music, Pop', 'An all-access documentary detailing the dreams and trials behind BLACKPINK meteoric rise, from their grueling trainee years to their historic 2019 Coachella performance.', 'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?w=600&q=80', 'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?w=1200&q=80', '7t_U53bS_lU', 'https://www.youtube.com/embed/7t_U53bS_lU', 'Caroline Suh', 'YG Entertainment / Netflix', '{"agency": "YG Entertainment", "artists": "Jisoo, Jennie, Rosé, Lisa", "cast": [{"name": "Jennie Kim", "role": "Vocalist / Rapper"}, {"name": "Lisa Manobal", "role": "Main Dancer / Rapper"}]}', 0, 1),

('kpop-stray-kids-maniac', 'kpop', 'kpop', 'Stray Kids: World Tour MANIAC in Seoul', 'Stray Kids 2nd World Tour MANIAC', 2023, 8.9, 'Released', '170 min', 'Concert, Hip-Hop, Electronic', 'Self-producing powerhouse group Stray Kids unleash high-octane live production and complex acrobatics at the Olympic Gymnastics Arena in Seoul.', 'https://images.unsplash.com/photo-1501386761578-eac5c94b800a?w=600&q=80', 'https://images.unsplash.com/photo-1501386761578-eac5c94b800a?w=1200&q=80', 'OvioeS1ZZ7o', 'https://www.youtube.com/embed/OvioeS1ZZ7o', 'JYP Entertainment', 'JYP Entertainment', '{"agency": "JYP Entertainment", "artists": "Bang Chan, Lee Know, Changbin, Hyunjin, Han, Felix, Seungmin, I.N", "cast": [{"name": "Bang Chan", "role": "Leader / Producer"}, {"name": "Felix Lee", "role": "Dancer / Rapper"}]}', 0, 0);

-- 6. COMICS
INSERT IGNORE INTO `media_items` (`external_id`, `category_slug`, `type`, `title`, `original_title`, `year`, `score`, `status`, `duration_or_episodes`, `genres`, `synopsis`, `poster_url`, `backdrop_url`, `trailer_key`, `trailer_url`, `director_or_creator`, `studio_or_publisher`, `extra_meta`, `is_featured`, `is_trending`) VALUES
('comic-watchmen', 'comics', 'comics', 'Watchmen', 'Watchmen (12-Issue Graphic Novel)', 1986, 9.7, 'Completed', '12 Issues', 'Graphic Novel, Deconstruction, Dystopian, Mystery', 'Set in an alternate 1985 America where costumed superheroes exist and the Cold War is nearing midnight, the murder of a government-sponsored vigilante prompts a retired colleague to investigate a wide-ranging conspiracy.', 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=600&q=80', 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=1200&q=80', 'NUjMO_K5n1o', 'https://www.youtube.com/embed/NUjMO_K5n1o', 'Alan Moore', 'DC Comics', '{"writer": "Alan Moore", "artist": "Dave Gibbons", "issues": "12 Issues", "cast": [{"name": "Rorschach", "role": "Protagonist"}, {"name": "Dr. Manhattan", "role": "Superhuman"}]}', 1, 1),

('comic-sandman', 'comics', 'comics', 'The Sandman: Preludes & Nocturnes', 'The Sandman #1-8', 1989, 9.6, 'Completed', '75 Issues', 'Dark Fantasy, Mythology, Horror', 'Morpheus, the Lord of Dreams and one of the seven Endless, is captured and imprisoned for seventy years by an occult ritual. Upon escaping, he embarks on a quest to reclaim his lost totems of power: his helm, pouch of sand, and ruby.', 'https://images.unsplash.com/photo-1579783902614-a3fb3927b675?w=600&q=80', 'https://images.unsplash.com/photo-1579783902614-a3fb3927b675?w=1200&q=80', '83ClbRPRDXU', 'https://www.youtube.com/embed/83ClbRPRDXU', 'Neil Gaiman', 'DC Vertigo', '{"writer": "Neil Gaiman", "artist": "Sam Kieth & Mike Dringenberg", "issues": "75 Issues", "cast": [{"name": "Dream of the Endless", "role": "Protagonist"}, {"name": "Death", "role": "The Endless"}]}', 0, 1),

('comic-batman-year-one', 'comics', 'comics', 'Batman: Year One', 'Batman #404-407', 1987, 9.5, 'Completed', '4 Issues', 'Crime Noir, Vigilante, Psychological', 'A young Bruce Wayne returns to Gotham City after twelve years abroad, determined to fight crime while Lieutenant James Gordon navigates systemic corruption inside the Gotham Police Department.', 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?w=600&q=80', 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?w=1200&q=80', '5AZXm-v0o10', 'https://www.youtube.com/embed/5AZXm-v0o10', 'Frank Miller', 'DC Comics', '{"writer": "Frank Miller", "artist": "David Mazzucchelli", "issues": "4 Issues", "cast": [{"name": "Bruce Wayne", "role": "Batman"}, {"name": "James Gordon", "role": "Lieutenant"}]}', 0, 0);

-- 7. MANGA
INSERT IGNORE INTO `media_items` (`external_id`, `category_slug`, `type`, `title`, `original_title`, `year`, `score`, `status`, `duration_or_episodes`, `genres`, `synopsis`, `poster_url`, `backdrop_url`, `trailer_key`, `trailer_url`, `director_or_creator`, `studio_or_publisher`, `extra_meta`, `is_featured`, `is_trending`) VALUES
('manga-2', 'manga', 'manga', 'Berserk', 'Berserk', 1989, 9.5, 'Publishing', '42 Volumes', 'Action, Adventure, Dark Fantasy, Horror', 'Guts, a former mercenary known as the Black Swordsman, seeks vengeance against his former friend Griffith, who sacrificed their comrades to demonic godhood. Armed with the Dragonslayer blade, he cuts a bloody swath through demonic apostles.', 'https://images.unsplash.com/photo-1566492031773-4f4e44671857?w=600&q=80', 'https://images.unsplash.com/photo-1566492031773-4f4e44671857?w=1200&q=80', 'fHn8z3v6e7Q', 'https://www.youtube.com/embed/fHn8z3v6e7Q', 'Kentaro Miura', 'Young Animal / Hakusensha', '{"author": "Kentaro Miura", "magazine": "Young Animal", "volumes": 42, "cast": [{"name": "Guts", "role": "The Black Swordsman"}, {"name": "Griffith", "role": "White Hawk"}]}', 1, 1),

('manga-656', 'manga', 'manga', 'Vagabond', 'Vagabond', 1998, 9.3, 'On Hiatus', '37 Volumes', 'Action, Historical, Samurai, Philosophy', 'In 16th-century Japan, Shinmen Takezo evolves from a reckless, brutal youth into Miyamoto Musashi, the legendary sword saint of the Edo period, exploring the philosophical boundaries of strength and pacifism.', 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=600&q=80', 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=1200&q=80', 'c7qX3N4kY0A', 'https://www.youtube.com/embed/c7qX3N4kY0A', 'Takehiko Inoue', 'Morning / Kodansha', '{"author": "Takehiko Inoue", "magazine": "Morning", "volumes": 37, "cast": [{"name": "Miyamoto Musashi", "role": "Sword Master"}, {"name": "Sasaki Kojiro", "role": "Rival"}]}', 0, 1),

('manga-1', 'manga', 'manga', 'Monster', 'Monster', 1994, 9.2, 'Finished', '18 Volumes', 'Drama, Mystery, Psychological Thriller', 'Kenzou Tenma, an elite Japanese neurosurgeon practicing in Düsseldorf, chooses to operate on a critically injured boy instead of the town mayor. Years later, Tenma discovers the boy he saved grew into an emotionless sociopath.', 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=600&q=80', 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=1200&q=80', 'msT_5oQzL8o', 'https://www.youtube.com/embed/msT_5oQzL8o', 'Naoki Urasawa', 'Big Comic Original / Shogakukan', '{"author": "Naoki Urasawa", "magazine": "Big Comic Original", "volumes": 18, "cast": [{"name": "Dr. Kenzou Tenma", "role": "Protagonist"}, {"name": "Johan Liebert", "role": "The Monster"}]}', 0, 0);

-- Seed Events
-- -------------------------------------------------------
INSERT IGNORE INTO `events` (`title`, `category`, `event_type`, `date_start`, `date_end`, `time_info`, `venue`, `city`, `country`, `latitude`, `longitude`, `description`, `organizer`, `banner_url`, `badge_text`, `ticket_info`, `is_featured`) VALUES
('TwinCon Pop-Culture & Anime Summit', 'convention', 'In-Person', '2026-10-15', '2026-10-17', '11:00 AM - 8:00 PM', 'Pak-China Friendship Centre', 'Islamabad', 'Pakistan', 33.6938000, 73.0850000, 'Pakistan premier annual pop culture convention celebrating Japanese anime, manga, indie gaming tournaments, and artisanal cosplay contests with guest panels.', 'TwinCon Society', 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=1000&q=80', 'CONVENTION', 'Pass Required', 1),

('Karachi Comic Con & Cosplay Gala', 'cosplay', 'In-Person', '2026-11-06', '2026-11-08', '10:00 AM - 9:00 PM', 'Expo Centre Karachi', 'Karachi', 'Pakistan', 24.9080000, 67.0792000, 'A massive mega-event for fans across Sindh and Pakistan featuring gaming LAN parties, master armor cosplay crafts, comic book stalls, and artist alley showcases.', 'Karachi Pop Guild', 'https://images.unsplash.com/photo-1563089145-599997674d42?w=1000&q=80', 'SUMMIT', 'Tickets Available', 1),

('Lahore Anime & Esports Exhibition', 'gaming', 'In-Person', '2026-10-28', '2026-10-30', '12:00 PM - 10:00 PM', 'Alhamra Arts Council, Mall Road', 'Lahore', 'Pakistan', 31.5552000, 74.3299000, 'Competitive Tekken 8 and Street Fighter 6 arena championships, voice acting masterclasses, and curated anime film screenings under the stars.', 'Lahore Gamers Alliance', 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=1000&q=80', 'ESPORTS', 'Free Entry', 1),

('Tokyo Game Show (TGS 2026)', 'gaming', 'Hybrid', '2026-09-24', '2026-09-27', '09:30 AM - 5:30 PM', 'Makuhari Messe, Chiba', 'Tokyo', 'Japan', 35.6477000, 140.0347000, 'The epicenter of Japanese and global video games. Hands-on demos for unreleased PlayStation, Nintendo, and PC titles with worldwide livestream stages.', 'CESA Japan', 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=1000&q=80', 'EXPO', 'Public Days', 1),

('AnimeJapan 2026 Expo', 'anime', 'In-Person', '2026-10-21', '2026-10-23', '09:00 AM - 5:00 PM', 'Tokyo Big Sight Convention Hall', 'Tokyo', 'Japan', 35.6298000, 139.7944000, 'The world largest Japanese animation festival. Studio MAPPA, Ufotable, and Toei Animation announce seasonal premieres with exclusive voice actor panels.', 'AnimeJapan Exec Comm', 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=1000&q=80', 'ANIME EXPO', 'Badge Required', 1),

('San Diego Comic-Con (SDCC)', 'convention', 'In-Person', '2026-10-24', '2026-10-28', '09:00 AM - 7:00 PM', 'San Diego Convention Center', 'San Diego', 'USA', 32.7071000, -117.1611000, 'Hall H movie blockbuster trailers, star-studded panels, Eisner comic awards, and legendary collectible reveals that define global entertainment.', 'Comic-Con International', 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=1000&q=80', 'GLOBAL CON', 'Sold Out / Lottery', 1),

('London MCM Comic Con Autumn', 'convention', 'In-Person', '2026-10-31', '2026-11-02', '10:00 AM - 6:00 PM', 'ExCeL London Docklands', 'London', 'UK', 51.5074000, 0.0298000, 'The United Kingdom largest celebration of pop culture, superhero comics, cosplay masquarade championships, and anime voice-acting talent.', 'ReedPop UK', 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=1000&q=80', 'UK CON', 'Day Passes', 0),

('Otakon Asian Culture Festival', 'anime', 'In-Person', '2026-11-12', '2026-11-15', '09:00 AM - 11:00 PM', 'Walter E. Washington Convention Center', 'Washington DC', 'USA', 38.9042000, -77.0229000, 'Celebrating Asian music, animation, manga, cinema, and fashion. Features all-night anime video rooms, artist alley of 400 creators, and cosplay dance.', 'Otakorp Inc', 'https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?w=1000&q=80', 'CULTURE', 'All-Access Pass', 0),

('K-Pop World Fan Summit & Live Concert', 'kpop', 'Hybrid', '2026-11-20', '2026-11-22', '06:00 PM - 10:30 PM', 'Changwon Sports Complex / Global Stream', 'Changwon', 'South Korea', 35.2281000, 128.6811000, 'Global dance cover battle championship, lightstick synchronizations, cupsleeve fan exchange markets, and special stage broadcast to over 120 countries.', 'KBS World / Ministry of Culture', 'https://images.unsplash.com/photo-1501386761578-eac5c94b800a?w=1000&q=80', 'MUSIC & MEETUP', 'Free Stream / Arena Tickets', 1),

('Archival 35mm Anime Cinema Retrospective', 'cinema', 'In-Person', '2026-10-18', '2026-10-20', '04:00 PM - 11:00 PM', 'Cinematheque Francaise', 'Paris', 'France', 48.8373000, 2.3831000, 'Rare restored 35mm film screenings of Akira (1988), Perfect Blue (1997), and Princess Mononoke (1997) with live orchestral introductions and director retrospective talks.', 'Cinema Heritage Archives', 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?w=1000&q=80', 'CINEMA', 'Reserved Seating', 0),

('EVO Fighting Game World Championship', 'gaming', 'In-Person', '2026-11-18', '2026-11-21', '10:00 AM - 11:00 PM', 'Mandalay Bay Convention Hall', 'Las Vegas', 'USA', 36.0919000, -115.1761000, 'The premier global fighting game tournament for Street Fighter, Tekken, Guilty Gear, and King of Fighters with thousands of international competitors.', 'Sony Interactive / RTS', 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=1000&q=80', 'TOURNAMENT', 'Spectator Pass', 0),

('Gamescom Global Expo 2026', 'gaming', 'Hybrid', '2026-10-08', '2026-10-11', '09:00 AM - 8:00 PM', 'Koelnmesse Fairgrounds', 'Cologne', 'Germany', 50.9463000, 6.9806000, 'Europe largest trade fair and fan gathering for digital games, featuring Opening Night Live with Geoff Keighley and over 1,200 international exhibitors.', 'Game Bundesverband', 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=1000&q=80', 'GLOBAL EXPO', 'Public Passes', 0);

-- -------------------------------------------------------
-- Seed Merchandise Showcase (Non-Commercial Collector Catalog)
-- -------------------------------------------------------
INSERT IGNORE INTO `merchandise` (`name`, `category`, `franchise`, `item_type`, `image_url`, `scale_or_size`, `material`, `manufacturer`, `rarity_tier`, `year_released`, `description`, `accession_no`, `is_featured`) VALUES
('Tanjiro Kamado: Hinokami Kagura Deluxe Scale', 'toys', 'Demon Slayer: Kimetsu no Yaiba', '1/7 Scale PVC & ABS Figure', 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=800&q=80', '1/7 Scale (28cm Height)', 'High-Grade PVC, Translucent ABS Resin', 'Aniplex+ / Good Smile Company', 'Master Tier', 2024, 'Archival museum exhibit capturing Tanjiro Kamado unleashing the Hinokami Kagura Dance of the Fire God. Cast in crystal resin flames with dynamic smoke billowing pedestal.', 'ARC-MC-FIG01', 1),

('RX-78-2 Gundam: Perfect Grade Unleashed 1/60', 'toys', 'Mobile Suit Gundam', '1/60 Master Grade Model Kit', 'https://images.unsplash.com/photo-1563089145-599997674d42?w=800&q=80', '1/60 Scale (30cm Height)', 'Multi-colored Polystyrene, Etched Metal, LEDs', 'Bandai Spirits Hobby Division', 'Museum Artifact', 2023, 'The zenith of Gundam model engineering. Features a 4-layer internal skeleton, 59 points of articulation, photo-etched brass accents, and full cockpit LED lighting system.', 'ARC-MC-GUN02', 1),

('Son Goku: Ultra Instinct Master Stars Piece', 'toys', 'Dragon Ball Super', 'Non-Scale Collector Statue', 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=800&q=80', '32cm Height', 'Solid Cast Resin & PVC', 'Banpresto / Tamashii Nations', 'Collector Grade', 2024, 'Features metallic silver sculpted hair with subtle iridescence, textured torn gi trousers, and battle-weathered muscular anatomy sculpt.', 'ARC-MC-DBZ03', 1),

('Miles Morales Spider-Man: Into the Spider-Verse', 'toys', 'Marvel Comics / Sony Animation', '1/6 Sixth Scale Action Figure', 'https://images.unsplash.com/photo-1635863138275-d9b33299680b?w=800&q=80', '1/6 Scale (29cm Height)', 'Custom Tailored Spandex, Die-Cast Joint Core', 'Hot Toys Masterpiece Series', 'Rare', 2023, 'Includes interchangeable masked and unmasked head sculpts, spray paint canisters, stylized web effects, and magnetic wall-climbing display base.', 'ARC-MC-SPM04', 0),

('Survey Corps Scout Regiment Wool Cloak', 'clothing', 'Attack on Titan (Shingeki no Kyojin)', 'Artisanal Archival Apparel', 'https://images.unsplash.com/photo-1551488831-00ddcb6c6bd3?w=800&q=80', 'Unisex Oversized (Length 125cm)', '100% Melton Wool, Antique Brass Clasp', 'Atelier Eldia Heritage', 'Master Tier', 2024, 'Screen-accurate reproduction of the Survey Corps expedition cloak. Deep forest moss hue woven with heavy virgin wool, double-stitched Wings of Freedom crest in dual-tone thread.', 'ARC-MC-CLO01', 1),

('Samurai LED Reversible Bomber Jacket', 'clothing', 'Cyberpunk 2077', 'Technical Streetwear Apparel', 'https://images.unsplash.com/photo-1548883354-7622d03aca27?w=800&q=80', 'Size L (Tailored Fit)', 'Waterproof Ripstop Nylon, High-Density Fleece', 'CD PROJEKT RED Official Atelier', 'Collector Grade', 2023, 'Features illuminated cyan fiber-optic neck collar with USB rechargeable battery pack, embroidered flaming Samurai oni logo on the back, and reversible safety orange interior.', 'ARC-MC-JKT02', 1),

('Akatsuki Cloud Heavy Fleece Embroidered Hoodie', 'clothing', 'Naruto Shippuden', 'Heavyweight Streetwear', 'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?w=800&q=80', 'Oversized Boxy Cut (450 GSM)', '100% French Terry Cotton, Silk Embroidery', 'Tokyo Underground Archive', 'Rare', 2024, 'Pitch black heavy-weight fleece featuring the crimson and white Akatsuki storm clouds embroidered with over 45,000 dense stitches. High-cowl drawstring collar.', 'ARC-MC-HOD03', 0),

('Gojo Satoru Jujutsu High Formal Dark Blouson', 'clothing', 'Jujutsu Kaisen', 'Formal Anime Atelier Wear', 'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?w=800&q=80', 'Tailored Cut (Size M/L)', 'Suiting Twill, Satin Lining, Silver Studs', 'Jujutsu Tech Archive', 'Collector Grade', 2024, 'Sleek midnight black high-collar jacket replicating Satoru Gojo formal uniform. Includes embossed swirling vortex silver buttons and concealed silk eye-wrap pocket.', 'ARC-MC-JKT04', 0),

('Roronoa Zoro: Wado Ichimonji Hand-Forged Katana', 'collectibles', 'One Piece', '1:1 Scale Prop Replica Sword', 'https://images.unsplash.com/photo-1589254065878-42c9da997008?w=800&q=80', 'Overall Length 104cm (Blade 71cm)', '1060 Folded High-Carbon Steel, Ray Skin', 'Master Swordsmith Guild Kyoto', 'Museum Artifact', 2023, 'Authentic battle-ready recreation of Kuina beloved heirloom katana. True clay-tempered hamon wave line, ray-skin tsuka wrapped in pure white ito cord, polished white lacquer saya.', 'ARC-MC-SWD01', 1),

('Death Note: Shuffled Rulebook with Crow Quill', 'collectibles', 'Death Note', 'Prop Replica Display Artifact', 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=800&q=80', '19cm x 26cm Hardcover', 'Aged Calfskin Leather, Parchment, Natural Quill', 'Kira Archive Bureau', 'Master Tier', 2024, 'Textured leather ledger inscribed with silver foil lettering. Includes all 66 English/Japanese usage rules, recreated Shinigami Ryuk instructions, and genuine crow feather ink pen.', 'ARC-MC-BOK02', 1),

('The Legend of Zelda: Master Sword with Pedestal', 'collectibles', 'The Legend of Zelda: Tears of the Kingdom', 'Life-Size 1:1 Replica with Light-Up Stone', 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=800&q=80', '108cm Blade, 35cm Stone Pedestal', 'Stainless Steel, Cast Polystone Resin, LEDs', 'Hyrule Antiquities Guild', 'Museum Artifact', 2024, 'The legendary Blade of Evil Bane forged in gleaming steel with wing crossguard. Rests within a glowing Triforce pedestal with sound-responsive breathing pulse lighting.', 'ARC-MC-ZLD03', 1),

('Iron Man Mark 85 Die-Cast Arc Reactor Prop', 'collectibles', 'Marvel Cinematic Universe', '1:1 Movie Prop Replica Display', 'https://images.unsplash.com/photo-1563089145-599997674d42?w=800&q=80', 'Life Size (12cm Diameter with Glass Case)', 'Brushed Aluminum, Copper Coils, Acrylic Case', 'Stark Industries Heritage', 'Master Tier', 2023, 'Handcrafted recreation featuring laser-cut copper transformer coils, frosted diffusion acrylic, multi-mode pulse LED lighting, and display stand inscribed "Proof That Tony Stark Has A Heart".', 'ARC-MC-MCU04', 0),

('Evangelion Unit-01 Awakening Iridescent Foil Print', 'prints', 'Neon Genesis Evangelion', 'Archival Fine Art Print', 'https://images.unsplash.com/photo-1579783902614-a3fb3927b675?w=800&q=80', 'A2 (42cm x 59.4cm)', '350 GSM Heavy Cotton Foil Stock', 'Studio Khara Official Print Archive', 'Rare', 2024, 'Limited run of 500 hand-numbered museum prints capturing EVA-01 berserk awakening over Neo-Tokyo-3. Printed using 7-color pigment inks on rainbow prismatic foil backing.', 'ARC-MC-PRN01', 0),

('Akira: Kaneda & Neo-Tokyo Highway Canvas', 'prints', 'Akira (1988)', 'Museum Stretched Gallery Canvas', 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?w=800&q=80', '60cm x 90cm Gallery Wrapped (38mm frame)', '100% Unbleached Cotton Canvas, UV Pigments', 'Otomo Heritage Preservation', 'Collector Grade', 2024, 'Iconic retro-futuristic red motorcycle scene overlooking Neo-Tokyo under rainy halogen glow. Finished with a protective matte satin varnish against fading.', 'ARC-MC-PRN02', 0);

-- End of safe, repeatable baseline.
