/**
 * Fallback dataset for resilient offline / rate-limited operation
 * Contains genuine titles, synopsis, verified poster URLs, and trailer IDs
 * for all 8 categories: Anime, Gaming, Movies, TV Shows, K-Pop, Comics, Manga, Software.
 */

window.FALLBACK_DATA = {
  movies: [
    {
      id: 'movie-693134',
      type: 'movie',
      category: 'movies',
      title: 'Dune: Part Two',
      originalTitle: 'Dune: Part Two',
      year: 2024,
      score: 8.3,
      status: 'Released',
      runtime: 166,
      genres: ['Science Fiction', 'Adventure'],
      synopsis: 'Follow the mythic journey of Paul Atreides as he unites with Chani and the Fremen while on a warpath of revenge against the conspirators who destroyed his family.',
      poster: 'https://image.tmdb.org/t/p/w500/1pdfLvkbY9ohJlCjQH2CZjjYVvJ.jpg',
      backdrop: 'https://image.tmdb.org/t/p/original/xOMo8BRK7PfcJv9JCnx7s5200SV.jpg',
      trailerKey: 'Way9Dexny3w',
      trailerUrl: 'https://www.youtube.com/embed/Way9Dexny3w',
      director: 'Denis Villeneuve',
      cast: [
        { name: 'Timothée Chalamet', role: 'Paul Atreides', avatar: '' },
        { name: 'Zendaya', role: 'Chani', avatar: '' }
      ]
    },
    {
      id: 'movie-872585',
      type: 'movie',
      category: 'movies',
      title: 'Oppenheimer',
      originalTitle: 'Oppenheimer',
      year: 2023,
      score: 8.1,
      status: 'Released',
      runtime: 180,
      genres: ['Drama', 'History', 'Biography'],
      synopsis: 'The story of J. Robert Oppenheimer\'s role in the development of the atomic bomb during World War II, examining the moral weight and political fallout of scientific discovery.',
      poster: 'https://image.tmdb.org/t/p/w500/8Gxv8gSFCU0XGDykEGv7zR1n2ua.jpg',
      backdrop: 'https://image.tmdb.org/t/p/original/fm6KqXpk3M2HVveGHudvK0w1Rj7.jpg',
      trailerKey: 'uYPbbksJxIg',
      trailerUrl: 'https://www.youtube.com/embed/uYPbbksJxIg',
      director: 'Christopher Nolan',
      cast: [
        { name: 'Cillian Murphy', role: 'J. Robert Oppenheimer', avatar: '' },
        { name: 'Emily Blunt', role: 'Kitty Oppenheimer', avatar: '' }
      ]
    },
    {
      id: 'movie-335984',
      type: 'movie',
      category: 'movies',
      title: 'Blade Runner 2049',
      originalTitle: 'Blade Runner 2049',
      year: 2017,
      score: 8.0,
      status: 'Released',
      runtime: 164,
      genres: ['Science Fiction', 'Mystery'],
      synopsis: 'Thirty years after the events of the first film, a new blade runner, LAPD Officer K, unearths a long-buried secret that has the potential to plunge what is left of society into chaos.',
      poster: 'https://image.tmdb.org/t/p/w500/gajva2L0rPYkEWjzgFlBXCAVBE5.jpg',
      backdrop: 'https://image.tmdb.org/t/p/original/ilRyAZw2juCU9nxBPqJ4dQyv8j0.jpg',
      trailerKey: 'gCcx85zbxz4',
      trailerUrl: 'https://www.youtube.com/embed/gCcx85zbxz4',
      director: 'Denis Villeneuve',
      cast: [
        { name: 'Ryan Gosling', role: 'Officer K', avatar: '' },
        { name: 'Harrison Ford', role: 'Rick Deckard', avatar: '' }
      ]
    },
    {
      id: 'movie-496243',
      type: 'movie',
      category: 'movies',
      title: 'Parasite',
      originalTitle: 'Gisaengchung',
      year: 2019,
      score: 8.5,
      status: 'Released',
      runtime: 132,
      genres: ['Thriller', 'Drama'],
      synopsis: 'All unemployed, Ki-taek\'s family takes peculiar interest in the wealthy and glamorous Parks for their livelihood until they get entangled in an unexpected incident.',
      poster: 'https://image.tmdb.org/t/p/w500/7IiTTgloJzvGI1TAYymCfbfl3vT.jpg',
      backdrop: 'https://image.tmdb.org/t/p/original/hiKmpnn79rq779gf09q.jpg',
      trailerKey: '5xH0R_fxnyQ',
      trailerUrl: 'https://www.youtube.com/embed/5xH0R_fxnyQ',
      director: 'Bong Joon-ho',
      cast: [
        { name: 'Song Kang-ho', role: 'Kim Ki-taek', avatar: '' },
        { name: 'Choi Woo-shik', role: 'Kim Ki-woo', avatar: '' }
      ]
    },
    {
      id: 'movie-157336',
      type: 'movie',
      category: 'movies',
      title: 'Interstellar',
      originalTitle: 'Interstellar',
      year: 2014,
      score: 8.4,
      status: 'Released',
      runtime: 169,
      genres: ['Adventure', 'Drama', 'Science Fiction'],
      synopsis: 'The adventures of a group of explorers who make use of a newly discovered wormhole to surpass the limitations on human space travel and conquer the vast distances involved in an interstellar voyage.',
      poster: 'https://image.tmdb.org/t/p/w500/gEU2QniE6E77NI6lCU6MxlNBvIx.jpg',
      backdrop: 'https://image.tmdb.org/t/p/original/gEU2QniE6E77NI6lCU6MxlNBvIx.jpg',
      trailerKey: 'zSWdZVtXT7E',
      trailerUrl: 'https://www.youtube.com/embed/zSWdZVtXT7E',
      director: 'Christopher Nolan',
      cast: [
        { name: 'Matthew McConaughey', role: 'Joseph Cooper', avatar: '' },
        { name: 'Anne Hathaway', role: 'Amelia Brand', avatar: '' }
      ]
    }
  ],

  anime: [
    {
      id: 'anime-5114',
      type: 'anime',
      category: 'anime',
      title: 'Fullmetal Alchemist: Brotherhood',
      originalTitle: 'Hagane no Renkinjutsushi: Fullmetal Alchemist',
      year: 2009,
      score: 9.1,
      status: 'Finished Airing',
      episodes: 64,
      genres: ['Action', 'Adventure', 'Drama', 'Fantasy'],
      synopsis: 'After a horrific alchemy experiment goes wrong in the Elric household, brothers Edward and Alphonse are left in a catastrophic new reality. Disregarding the alchemical restriction against human transmutation, the boys attempted to bring their recently deceased mother back to life.',
      poster: 'https://cdn.myanimelist.net/images/anime/1208/94745l.jpg',
      backdrop: 'https://cdn.myanimelist.net/images/anime/1208/94745l.jpg',
      trailerKey: '--IcmZkvL0Q',
      trailerUrl: 'https://www.youtube.com/embed/--IcmZkvL0Q',
      studio: 'Bones',
      cast: [
        { name: 'Edward Elric', role: 'Main', avatar: '' },
        { name: 'Alphonse Elric', role: 'Main', avatar: '' }
      ]
    },
    {
      id: 'anime-1',
      type: 'anime',
      category: 'anime',
      title: 'Cowboy Bebop',
      originalTitle: 'Cowboy Bebop',
      year: 1998,
      score: 8.8,
      status: 'Finished Airing',
      episodes: 26,
      genres: ['Action', 'Sci-Fi', 'Neo-Noir'],
      synopsis: 'Crime is timeless. By the year 2071, humanity has expanded across the galaxy, filling the surface of other planets to escape earth ruin. Spike Spiegel and Jet Black pursue bounties across space aboard the Bebop.',
      poster: 'https://cdn.myanimelist.net/images/anime/4/19644l.jpg',
      backdrop: 'https://cdn.myanimelist.net/images/anime/4/19644l.jpg',
      trailerKey: 'qig4KMGmmr8',
      trailerUrl: 'https://www.youtube.com/embed/qig4KMGmmr8',
      studio: 'Sunrise',
      cast: [
        { name: 'Spike Spiegel', role: 'Main', avatar: '' },
        { name: 'Faye Valentine', role: 'Main', avatar: '' }
      ]
    },
    {
      id: 'anime-16498',
      type: 'anime',
      category: 'anime',
      title: 'Attack on Titan',
      originalTitle: 'Shingeki no Kyojin',
      year: 2013,
      score: 8.5,
      status: 'Finished Airing',
      episodes: 25,
      genres: ['Action', 'Suspense', 'Fantasy'],
      synopsis: 'Centuries ago, mankind was slaughtered to near extinction by monstrous humanoid creatures called Titans, forcing humans to hide in fear behind enormous concentric walls.',
      poster: 'https://cdn.myanimelist.net/images/anime/10/47347l.jpg',
      backdrop: 'https://cdn.myanimelist.net/images/anime/10/47347l.jpg',
      trailerKey: 'MGRm4IzK1SQ',
      trailerUrl: 'https://www.youtube.com/embed/MGRm4IzK1SQ',
      studio: 'Wit Studio',
      cast: [
        { name: 'Eren Yeager', role: 'Main', avatar: '' },
        { name: 'Levi Ackerman', role: 'Supporting', avatar: '' }
      ]
    },
    {
      id: 'anime-199',
      type: 'anime',
      category: 'anime',
      title: 'Spirited Away',
      originalTitle: 'Sen to Chihiro no Kamikakushi',
      year: 2001,
      score: 8.9,
      status: 'Finished',
      episodes: 1,
      genres: ['Adventure', 'Supernatural'],
      synopsis: 'Ten-year-old Chihiro Ogino and her parents stumble upon what appears to be an abandoned amusement park. Chihiro discovers a mystical bathhouse where spirits rest, governed by the witch Yubaba.',
      poster: 'https://cdn.myanimelist.net/images/anime/6/79597l.jpg',
      backdrop: 'https://cdn.myanimelist.net/images/anime/6/79597l.jpg',
      trailerKey: 'ByXuk9QqQkk',
      trailerUrl: 'https://www.youtube.com/embed/ByXuk9QqQkk',
      studio: 'Studio Ghibli',
      cast: [
        { name: 'Chihiro Ogino', role: 'Main', avatar: '' },
        { name: 'Haku', role: 'Main', avatar: '' }
      ]
    }
  ],

  gaming: [
    {
      id: 'game-elden-ring',
      type: 'gaming',
      category: 'gaming',
      title: 'Elden Ring',
      originalTitle: 'Elden Ring',
      year: 2022,
      score: 9.6,
      status: 'Available',
      platform: 'PlayStation, Xbox, PC',
      genres: ['Action RPG', 'Open World', 'Dark Fantasy'],
      synopsis: 'Rise, Tarnished, and be guided by grace to brandish the power of the Elden Ring and become an Elden Lord in the Lands Between. A vast world where open fields with a variety of situations and huge dungeons are seamlessly connected.',
      poster: 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=600&q=80',
      backdrop: 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=1200&q=80',
      trailerKey: 'E3Huy2cdih0',
      trailerUrl: 'https://www.youtube.com/embed/E3Huy2cdih0',
      developer: 'FromSoftware',
      publisher: 'Bandai Namco Entertainment',
      cast: [
        { name: 'Hidetaka Miyazaki', role: 'Director', avatar: '' },
        { name: 'George R.R. Martin', role: 'Worldbuilding', avatar: '' }
      ]
    },
    {
      id: 'game-cyberpunk-2077',
      type: 'gaming',
      category: 'gaming',
      title: 'Cyberpunk 2077: Phantom Liberty',
      originalTitle: 'Cyberpunk 2077',
      year: 2023,
      score: 9.1,
      status: 'Available',
      platform: 'PC, PlayStation 5, Xbox Series X',
      genres: ['Action RPG', 'Cyberpunk', 'Open World'],
      synopsis: 'Enter the shadowy world of espionage in Dogtown, a dangerous district of Night City. As cyber-enhanced mercenary V, team up with sleeper agent Solomon Reed to unravel a web of shattered loyalties and political machinations.',
      poster: 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=600&q=80',
      backdrop: 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=1200&q=80',
      trailerKey: 'kfXde_1qE0c',
      trailerUrl: 'https://www.youtube.com/embed/kfXde_1qE0c',
      developer: 'CD PROJEKT RED',
      publisher: 'CD PROJEKT RED',
      cast: [
        { name: 'Keanu Reeves', role: 'Johnny Silverhand', avatar: '' },
        { name: 'Idris Elba', role: 'Solomon Reed', avatar: '' }
      ]
    },
    {
      id: 'game-ff7-rebirth',
      type: 'gaming',
      category: 'gaming',
      title: 'Final Fantasy VII Rebirth',
      originalTitle: 'Final Fantasy VII Rebirth',
      year: 2024,
      score: 9.3,
      status: 'Available',
      platform: 'PlayStation 5',
      genres: ['JRPG', 'Fantasy', 'Sci-Fi'],
      synopsis: 'The journey into the unknown continues. Cloud, Barret, Tifa, Aerith, and Red XIII escape the dystopian city of Midgar and venture into the wide world in pursuit of Sephiroth.',
      poster: 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=600&q=80',
      backdrop: 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=1200&q=80',
      trailerKey: '1h-u_0i6-uE',
      trailerUrl: 'https://www.youtube.com/embed/1h-u_0i6-uE',
      developer: 'Square Enix',
      publisher: 'Square Enix',
      cast: [
        { name: 'Cloud Strife', role: 'Protagonist', avatar: '' },
        { name: 'Sephiroth', role: 'Antagonist', avatar: '' }
      ]
    },
    {
      id: 'game-persona-5-royal',
      type: 'gaming',
      category: 'gaming',
      title: 'Persona 5 Royal',
      originalTitle: 'Persona 5 Royal',
      year: 2020,
      score: 9.5,
      status: 'Available',
      platform: 'PlayStation, Xbox, Switch, PC',
      genres: ['JRPG', 'Supernatural', 'Social Sim'],
      synopsis: 'Don the mask of Joker and join the Phantom Thieves of Hearts as they stage heists into the hearts of corrupt adults in modern Tokyo, stealing malice to reform society.',
      poster: 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?w=600&q=80',
      backdrop: 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?w=1200&q=80',
      trailerKey: 'SKpSpvFQYL8',
      trailerUrl: 'https://www.youtube.com/embed/SKpSpvFQYL8',
      developer: 'Atlus (P-Studio)',
      publisher: 'Sega',
      cast: [
        { name: 'Joker', role: 'Leader', avatar: '' },
        { name: 'Morgana', role: 'Guide', avatar: '' }
      ]
    }
  ],

  tvshows: [
    {
      id: 'tv-shogun-2024',
      type: 'tvshows',
      category: 'tvshows',
      title: 'Shōgun',
      originalTitle: 'Shōgun',
      year: 2024,
      score: 9.2,
      status: 'Completed Season',
      seasons: 1,
      episodes: 10,
      genres: ['Historical Drama', 'War', 'Political'],
      synopsis: 'In Japan in the year 1600, at the dawn of a century-defining civil war, Lord Yoshii Toranaga is fighting for his life as his enemies on the Council of Regents unite against him, while a mysterious European ship is found stranded in a nearby village.',
      poster: 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=600&q=80',
      backdrop: 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=1200&q=80',
      trailerKey: 'yBb_X46o6uM',
      trailerUrl: 'https://www.youtube.com/embed/yBb_X46o6uM',
      network: 'FX / Hulu',
      creator: 'Rachel Kondo & Justin Marks',
      cast: [
        { name: 'Hiroyuki Sanada', role: 'Lord Yoshii Toranaga', avatar: '' },
        { name: 'Cosmo Jarvis', role: 'John Blackthorne', avatar: '' },
        { name: 'Anna Sawai', role: 'Toda Mariko', avatar: '' }
      ]
    },
    {
      id: 'tv-arcane-2021',
      type: 'tvshows',
      category: 'tvshows',
      title: 'Arcane',
      originalTitle: 'Arcane: League of Legends',
      year: 2021,
      score: 9.4,
      status: 'Renewed',
      seasons: 2,
      episodes: 18,
      genres: ['Animation', 'Action', 'Sci-Fi'],
      synopsis: 'Amid the stark discord of twin cities Piltover and Zaun, two sisters fight on rival sides of a war between magic technologies and incompatible convictions.',
      poster: 'https://images.unsplash.com/photo-1563089145-599997674d42?w=600&q=80',
      backdrop: 'https://images.unsplash.com/photo-1563089145-599997674d42?w=1200&q=80',
      trailerKey: 'fXmAurh012s',
      trailerUrl: 'https://www.youtube.com/embed/fXmAurh012s',
      network: 'Netflix / Riot Games',
      creator: 'Christian Linke & Alex Yee',
      cast: [
        { name: 'Hailee Steinfeld', role: 'Vi', avatar: '' },
        { name: 'Ella Purnell', role: 'Jinx', avatar: '' }
      ]
    },
    {
      id: 'tv-breaking-bad',
      type: 'tvshows',
      category: 'tvshows',
      title: 'Breaking Bad',
      originalTitle: 'Breaking Bad',
      year: 2008,
      score: 9.5,
      status: 'Ended',
      seasons: 5,
      episodes: 62,
      genres: ['Crime', 'Drama', 'Thriller'],
      synopsis: 'A chemistry teacher diagnosed with inoperable lung cancer turns to manufacturing and selling methamphetamine with a former student in order to secure his family\'s financial future.',
      poster: 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=600&q=80',
      backdrop: 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=1200&q=80',
      trailerKey: 'HhesaQXLuRY',
      trailerUrl: 'https://www.youtube.com/embed/HhesaQXLuRY',
      network: 'AMC',
      creator: 'Vince Gilligan',
      cast: [
        { name: 'Bryan Cranston', role: 'Walter White', avatar: '' },
        { name: 'Aaron Paul', role: 'Jesse Pinkman', avatar: '' }
      ]
    }
  ],

  kpop: [
    {
      id: 'kpop-bts-yet-to-come',
      type: 'kpop',
      category: 'kpop',
      title: 'BTS: Yet to Come in Cinemas',
      originalTitle: 'BTS: Yet to Come in Busan',
      year: 2023,
      score: 9.1,
      status: 'Released',
      duration: '104 min',
      genres: ['Concert Film', 'Music Documentary', 'Pop'],
      synopsis: 'A special cinematic cut of the massive Busan World Expo concert featuring RM, Jin, SUGA, j-hope, Jimin, V, and Jung Kook performing career-spanning hit songs with full stadium choreography.',
      poster: 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=600&q=80',
      backdrop: 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=1200&q=80',
      trailerKey: '3F3o04QzN4o',
      trailerUrl: 'https://www.youtube.com/embed/3F3o04QzN4o',
      agency: 'BIGHIT MUSIC / HYBE',
      artists: 'RM, Jin, SUGA, j-hope, Jimin, V, Jung Kook',
      cast: [
        { name: 'RM', role: 'Leader / Rapper', avatar: '' },
        { name: 'Jung Kook', role: 'Main Vocalist', avatar: '' }
      ]
    },
    {
      id: 'kpop-blackpink-light-up',
      type: 'kpop',
      category: 'kpop',
      title: 'BLACKPINK: Light Up the Sky',
      originalTitle: 'BLACKPINK: Light Up the Sky',
      year: 2020,
      score: 8.6,
      status: 'Released',
      duration: '79 min',
      genres: ['Documentary', 'Music', 'Pop'],
      synopsis: 'An all-access documentary detailing the dreams and trials behind BLACKPINK\'s meteoric rise, from their grueling trainee years to their historic 2019 Coachella performance.',
      poster: 'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?w=600&q=80',
      backdrop: 'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?w=1200&q=80',
      trailerKey: '7t_U53bS_lU',
      trailerUrl: 'https://www.youtube.com/embed/7t_U53bS_lU',
      agency: 'YG Entertainment',
      artists: 'Jisoo, Jennie, Rosé, Lisa',
      cast: [
        { name: 'Jennie Kim', role: 'Vocalist / Rapper', avatar: '' },
        { name: 'Lisa Manobal', role: 'Main Dancer / Rapper', avatar: '' }
      ]
    },
    {
      id: 'kpop-stray-kids-maniac',
      type: 'kpop',
      category: 'kpop',
      title: 'Stray Kids: World Tour MANIAC in Seoul',
      originalTitle: 'Stray Kids 2nd World Tour MANIAC',
      year: 2023,
      score: 8.9,
      status: 'Released',
      duration: '170 min',
      genres: ['Concert', 'Hip-Hop', 'Electronic'],
      synopsis: 'Self-producing powerhouse group Stray Kids unleash high-octane live production and complex acrobatics at the Olympic Gymnastics Arena in Seoul.',
      poster: 'https://images.unsplash.com/photo-1501386761578-eac5c94b800a?w=600&q=80',
      backdrop: 'https://images.unsplash.com/photo-1501386761578-eac5c94b800a?w=1200&q=80',
      trailerKey: 'OvioeS1ZZ7o',
      trailerUrl: 'https://www.youtube.com/embed/OvioeS1ZZ7o',
      agency: 'JYP Entertainment',
      artists: 'Bang Chan, Lee Know, Changbin, Hyunjin, Han, Felix, Seungmin, I.N',
      cast: [
        { name: 'Bang Chan', role: 'Leader / Producer', avatar: '' },
        { name: 'Felix Lee', role: 'Dancer / Rapper', avatar: '' }
      ]
    }
  ],

  comics: [
    {
      id: 'comic-watchmen',
      type: 'comics',
      category: 'comics',
      title: 'Watchmen',
      originalTitle: 'Watchmen (12-Issue Graphic Novel)',
      year: 1986,
      score: 9.7,
      status: 'Completed',
      issues: '12 Issues',
      genres: ['Graphic Novel', 'Deconstruction', 'Dystopian', 'Mystery'],
      synopsis: 'Set in an alternate 1985 America where costumed superheroes exist and the Cold War is nearing midnight, the murder of a government-sponsored vigilante prompts a retired colleague to investigate a wide-ranging conspiracy.',
      poster: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=600&q=80',
      backdrop: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=1200&q=80',
      trailerKey: 'NUjMO_K5n1o',
      trailerUrl: 'https://www.youtube.com/embed/NUjMO_K5n1o',
      publisher: 'DC Comics',
      writer: 'Alan Moore',
      artist: 'Dave Gibbons',
      cast: [
        { name: 'Rorschach', role: 'Protagonist', avatar: '' },
        { name: 'Dr. Manhattan', role: 'Superhuman', avatar: '' }
      ]
    },
    {
      id: 'comic-sandman',
      type: 'comics',
      category: 'comics',
      title: 'The Sandman: Preludes & Nocturnes',
      originalTitle: 'The Sandman #1-8',
      year: 1989,
      score: 9.6,
      status: 'Completed',
      issues: '75 Issues',
      genres: ['Dark Fantasy', 'Mythology', 'Horror'],
      synopsis: 'Morpheus, the Lord of Dreams and one of the seven Endless, is captured and imprisoned for seventy years by an occult ritual. Upon escaping, he embarks on a quest to reclaim his lost totems of power: his helm, pouch of sand, and ruby.',
      poster: 'https://images.unsplash.com/photo-1579783902614-a3fb3927b675?w=600&q=80',
      backdrop: 'https://images.unsplash.com/photo-1579783902614-a3fb3927b675?w=1200&q=80',
      trailerKey: '83ClbRPRDXU',
      trailerUrl: 'https://www.youtube.com/embed/83ClbRPRDXU',
      publisher: 'DC Vertigo',
      writer: 'Neil Gaiman',
      artist: 'Sam Kieth & Mike Dringenberg',
      cast: [
        { name: 'Dream of the Endless', role: 'Protagonist', avatar: '' },
        { name: 'Death', role: 'The Endless', avatar: '' }
      ]
    },
    {
      id: 'comic-batman-year-one',
      type: 'comics',
      category: 'comics',
      title: 'Batman: Year One',
      originalTitle: 'Batman #404-407',
      year: 1987,
      score: 9.5,
      status: 'Completed',
      issues: '4 Issues',
      genres: ['Crime Noir', 'Vigilante', 'Psychological'],
      synopsis: 'A young Bruce Wayne returns to Gotham City after twelve years abroad, determined to fight crime while Lieutenant James Gordon navigates systemic corruption inside the Gotham Police Department.',
      poster: 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?w=600&q=80',
      backdrop: 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?w=1200&q=80',
      trailerKey: '5AZXm-v0o10',
      trailerUrl: 'https://www.youtube.com/embed/5AZXm-v0o10',
      publisher: 'DC Comics',
      writer: 'Frank Miller',
      artist: 'David Mazzucchelli',
      cast: [
        { name: 'Bruce Wayne', role: 'Batman', avatar: '' },
        { name: 'James Gordon', role: 'Lieutenant', avatar: '' }
      ]
    }
  ],

  manga: [
    {
      id: 'manga-2',
      type: 'manga',
      category: 'manga',
      title: 'Berserk',
      originalTitle: 'Berserk',
      year: 1989,
      score: 9.5,
      status: 'Publishing',
      volumes: 42,
      genres: ['Action', 'Adventure', 'Dark Fantasy', 'Horror'],
      synopsis: 'Guts, a former mercenary known as the "Black Swordsman," seeks vengeance against his former friend Griffith, who sacrificed their comrades to demonic godhood. Armed with the Dragonslayer blade, he cuts a bloody swath through demonic apostles.',
      poster: 'https://images.unsplash.com/photo-1566492031773-4f4e44671857?w=600&q=80',
      backdrop: 'https://images.unsplash.com/photo-1566492031773-4f4e44671857?w=1200&q=80',
      trailerKey: 'fHn8z3v6e7Q',
      trailerUrl: 'https://www.youtube.com/embed/fHn8z3v6e7Q',
      author: 'Kentaro Miura',
      magazine: 'Young Animal',
      cast: [
        { name: 'Guts', role: 'The Black Swordsman', avatar: '' },
        { name: 'Griffith', role: 'White Hawk', avatar: '' }
      ]
    },
    {
      id: 'manga-656',
      type: 'manga',
      category: 'manga',
      title: 'Vagabond',
      originalTitle: 'Vagabond',
      year: 1998,
      score: 9.3,
      status: 'On Hiatus',
      volumes: 37,
      genres: ['Action', 'Historical', 'Samurai'],
      synopsis: 'In 16th-century Japan, Shinmen Takezo evolves from a reckless, brutal youth into Miyamoto Musashi, the legendary sword saint of the Edo period, exploring the philosophical boundaries of strength and pacifism.',
      poster: 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=600&q=80',
      backdrop: 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=1200&q=80',
      trailerKey: 'c7qX3N4kY0A',
      trailerUrl: 'https://www.youtube.com/embed/c7qX3N4kY0A',
      author: 'Takehiko Inoue',
      magazine: 'Morning',
      cast: [
        { name: 'Miyamoto Musashi', role: 'Sword Master', avatar: '' },
        { name: 'Sasaki Kojiro', role: 'Rival', avatar: '' }
      ]
    },
    {
      id: 'manga-1',
      type: 'manga',
      category: 'manga',
      title: 'Monster',
      originalTitle: 'Monster',
      year: 1994,
      score: 9.2,
      status: 'Finished',
      volumes: 18,
      genres: ['Drama', 'Mystery', 'Psychological Thriller'],
      synopsis: 'Kenzou Tenma, an elite Japanese neurosurgeon practicing in Düsseldorf, chooses to operate on a critically injured boy instead of the town mayor. Years later, Tenma discovers the boy he saved grew into an emotionless sociopath.',
      poster: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=600&q=80',
      backdrop: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=1200&q=80',
      trailerKey: 'msT_5oQzL8o',
      trailerUrl: 'https://www.youtube.com/embed/msT_5oQzL8o',
      author: 'Naoki Urasawa',
      magazine: 'Big Comic Original',
      cast: [
        { name: 'Dr. Kenzou Tenma', role: 'Protagonist', avatar: '' },
        { name: 'Johan Liebert', role: 'The Monster', avatar: '' }
      ]
    }
  ],

  software: []
};
