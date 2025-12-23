# Repository Guidelines

## Project Structure & Module Organization

- `functions.php`: Theme init + admin settings; loads core utilities and controls security-related defaults.
- `core/`: Theme logic helpers.
  - `core/App.php`: Output sanitizers and the Love List shortcode renderer.
  - `core/shortcodes.php`: Shortcode engine (WordPress-derived with Typecho-safe fallbacks).
- Templates:
  - `index.php`, `post.php`: Main list/post templates.
  - `indexPage.php`, `loveListPage.php`, `commentPage.php`: Custom page templates.
- `base/`: Layout partials and front-end assets (`head.php`, `nav.php`, `footer.php`, `style.css`, `main.js`).
- `base/vendor/`: Pinned local copies of front-end libraries (jQuery/Bootstrap/pjax/nprogress). This is the default asset source.
- `docs/`: User and security documentation (`docs/USAGE.md`, `docs/SECURITY.md`).
- `svg/`: UI icons.

## Build, Test, and Development Commands

This repository is a Typecho theme and has no build pipeline.

- Install locally: copy the theme directory to `usr/themes/Brave/`, then enable it in Typecho Admin → Appearance.
- Verify settings: Admin → Theme Settings (notably `assetsSource`, comment security options, and CSP/SRI toggles).
- After edits: refresh pages and clear Typecho/cache-plugin caches if applicable.

## Coding Style & Naming Conventions

- Keep diffs small and avoid mass reformatting (some files include mixed CRLF/LF).
- Preserve the `__TYPECHO_ROOT_DIR__` guard pattern in PHP entry templates/partials.
- Treat all user-controlled output as unsafe by default:
  - Prefer `core/App.php` helpers (`sanitizeCommentHtml`, `sanitizeLoveListTitle`, `normalizeUrl`) over direct string concatenation.

## Testing Guidelines

No automated test suite is configured. Manually validate:

- Comment submission + rendering (`commentPage.php`), with `commentAllowImg` on/off.
- Love List shortcode (`loveListPage.php`), with `loveListTitleAllowHtml` on/off.
- Asset loading in both modes: `assetsSource=local` and `assetsSource=cdn` (SRI/CSP enabled by default in CDN mode).

## Commit & Pull Request Guidelines

- Commit history commonly uses prefixes like `feat:`, `fix:`, `opt:` with short Chinese descriptions; follow the same convention and add a brief body for security-impacting changes.
- PRs should include: change summary, Typecho verification steps, any setting default changes, and screenshots for UI changes.
- Update `docs/SECURITY.md` whenever you change sanitization, CSP/SRI, or dependency loading.

