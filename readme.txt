============================================================
LERM WORDPRESS THEME
============================================================

A modern WordPress theme with a custom PHP application layer,
REST-driven frontend interactions, and a Vite-powered asset
pipeline.


------------------------------------------------------------
Overview
------------------------------------------------------------

Lerm is a blog-focused WordPress theme that combines classic
template rendering with a structured application layer under
`app/` and modular frontend code under `assets/resources/`.

It ships with:

- Bootstrap-based UI foundations
- REST-powered interactions for likes, views, comments, search,
  profile updates, and load-more archives
- Modular JavaScript components organized by feature
- Vite build output in `assets/dist/`
- Composer autoloading for PHP classes in `app/`


------------------------------------------------------------
Theme Info
------------------------------------------------------------

Name:           Lerm
Author:         Lerm
Theme URI:      http://github.com/lermnote
Author URI:     https://www.hanost.com
Requires WP:    4.7+
Tested up to:   6.4
Requires PHP:   7.4+
Version:        5.0.0
Text Domain:    lerm
License:        GPL-2.0-or-later


------------------------------------------------------------
Project Structure
------------------------------------------------------------

app/
  Core/            Theme bootstrapping, enqueue, setup
  Http/            REST routes, controllers, repositories
  View/            Template helpers and view rendering support
  Support/         Shared utility helpers

assets/
  resources/
    css/           Source styles
    js/
      components/  Frontend feature modules
      services/    Shared client-side service classes
      utils/       Small low-level helpers
  dist/            Built production assets

template-parts/    Reusable template fragments
templates/         Full-page template files
languages/         Translation files
vendor/            Composer dependencies


------------------------------------------------------------
Frontend Notes
------------------------------------------------------------

The JavaScript layer is organized around a simple flow:

  index.js
    -> components/index.js
    -> feature modules in components/

Low-level helpers such as event delegation and idle scheduling
live in `assets/resources/js/utils/`.

Current component modules include:

- archiveState.js
- calendar.js
- codeHighlight.js
- comments.js
- forms.js
- lazyImages.js
- likes.js
- loadMore.js
- navigation.js
- profile.js
- scrollAnimate.js
- scrollTop.js
- views.js


------------------------------------------------------------
Getting Started
------------------------------------------------------------

1. Install PHP dependencies

   composer install

2. Install frontend dependencies

   npm install

3. Build frontend assets

   npm run build

4. For development watching

   npm run dev


------------------------------------------------------------
NPM Scripts
------------------------------------------------------------

npm run dev
  Build in watch mode for active theme development.

npm run build
  Produce optimized frontend assets in `assets/dist/`.

npm run clean
  Remove built frontend assets.


------------------------------------------------------------
Development Workflow
------------------------------------------------------------

- Edit PHP theme logic inside `app/`, template files, and
  `functions.php` as needed.
- Edit styles and frontend behavior in `assets/resources/`.
- Rebuild assets before packaging or deployment.
- Keep REST routes and frontend route names aligned through the
  enqueue/localization layer.


------------------------------------------------------------
Distribution Checklist
------------------------------------------------------------

Before release, verify:

- Composer dependencies are installed
- `assets/dist/` is up to date
- Theme metadata in `style.css` is correct
- WordPress REST endpoints used by the frontend are reachable
- No local-only debug changes remain


------------------------------------------------------------
License
------------------------------------------------------------

Theme code is distributed under the GNU General Public License,
version 2 or later. Third-party packages keep their own
respective licenses.


------------------------------------------------------------
Maintainer Note
------------------------------------------------------------

This project uses both Composer and npm. If something looks
"correct" in source but not in the browser, rebuild the frontend
assets first.
