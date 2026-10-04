# MovieFlix

My second WordPress project: a **Netflix-style movie and TV streaming website**. It is built from two custom parts that I designed with AI tools: the **MovieFlix Core** plugin (the engine) and the **MovieFlix** theme (the look and pages).

**Live site:** _add your live link here_
**Guide (features, files, setup, testing):** https://ajay995182.github.io/movieflix/

> **Test project.** The demo content is for testing the website only. This is not a real streaming service and it does not stream the films named in the demo. See [Demo data](#demo-data).

## What was used

| Part | What it is | Who made it |
|---|---|---|
| WordPress 6.0+ / PHP 7.4+ | Base system | Ready-made software |
| **MovieFlix Core** plugin (v1.5.0) | Data, users, AJAX, admin tools, analytics | Custom code, designed with AI tools |
| **MovieFlix** theme (v2.0.4) | Pages, player UI, mobile layout | Custom code, designed with AI tools |
| hls.js 1.5.13 | HLS video playback, loaded from jsDelivr | Third-party library |

No third-party plugins are required. The theme only shows an "activate MovieFlix Core" message if the plugin is off.

## Features

- **Content:** movies, TV series with seasons and episodes, people (cast and directors), Live TV channels, and filters for genre, language, country, age rating, quality and year
- **Discovery:** home page with hero banner and rows, Movies and TV Shows grids, New & Popular, search with live poster suggestions, similar titles
- **Player:** MP4 and HLS (`.m3u8`), subtitles, audio tracks, skip intro, next-episode overlay, automatic progress saving
- **Accounts:** register, login, forgot password, optional Continue with Google, My List, Continue Watching, ratings and reviews, multiple profiles, Kids mode, optional parental PIN, optional members-only titles
- **Live TV:** channel grid with category chips, favourites, live player page
- **Mobile and PWA:** bottom navigation, two posters per row on phones, install banner, service worker, in-app notification bell
- **Admin:** settings, edit screens for titles, chunked video upload, demo import and remove, CSV / TMDB / bulk episode import, activity log, analytics

## Install

1. WordPress → Plugins → Add New → Upload Plugin → `plugin/movieflix-core-1.5.0.zip` → Activate.
2. Appearance → Themes → Add New → Upload Theme → `theme/movieflix-theme-2.0.4.zip` → Activate.
3. Settings → Permalinks → choose **Post name** → Save Changes.
4. MovieFlix admin → import the demo content (for testing).
5. Open the site and run the testing steps in the [guide](https://ajay995182.github.io/movieflix/).

Optional later: Google Client ID and Secret (Continue with Google), TMDB API key (import tool), HTTPS for PWA and notifications.

## Demo data

The demo import adds 38 movies, 8 series, 30 cast and director entries and 12 Live TV channels, so every screen has something to show.

- Many titles and names are **real Indian films and people**, used only to make the layout look like a real catalog.
- Poster and backdrop images for them are loaded from **TMDB**. The plugin also includes 13 placeholder SVG posters as fallbacks.
- The videos are a few **open test files** (Blender open movies such as Big Buck Bunny, and a public Mux HLS test stream), the same on many titles. They are **not** the actual films.
- Use **Remove demo** in admin before adding your own licensed content.

This product uses the TMDB API but is not endorsed or certified by TMDB.

## Repository layout

```
movieflix/
├── README.md
├── docs/index.html                     # guide page (GitHub Pages)
├── plugin/
│   ├── movieflix-core/                 # plugin source
│   └── movieflix-core-1.5.0.zip        # upload in WordPress
└── theme/
    ├── movieflix/                      # theme source
    └── movieflix-theme-2.0.4.zip       # upload in WordPress
```

## License

Plugin and theme: GPL-2.0-or-later. hls.js keeps its own licence.

---
Built by Ajay, student at GITAM University.
