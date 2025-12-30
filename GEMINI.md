# Brave Theme Context for Gemini

## Project Overview
**Brave** is a Typecho theme designed specifically for couples. It features a romantic aesthetic and specialized functionality like a relationship timer, a "Love List" (bucket list), and a "Blessing Board" (guestbook).

**Key Technologies:**
- **Backend:** PHP (Typecho Theme Framework)
- **Frontend:** HTML5, CSS3, JavaScript (jQuery)
- **Frameworks:** Bootstrap 4.6.2
- **Libraries:** PJAX (for smooth page transitions), NProgress (loading bar)

**Architecture:**
The theme follows standard Typecho theme structure but modularizes core logic and base templates:
- **`core/`**: Contains helper classes (`App.php`) and shortcode parsers. Handles security logic (XSS filtering, CSP generation).
- **`base/`**: Holds partial templates (`head.php`, `nav.php`, `footer.php`) and static assets (`vendor/`, `style.css`, `main.js`).
- **Root**: Contains page templates (`index.php`, `post.php`, `loveListPage.php`) and configuration (`functions.php`).

## Building and Running

Since this is a PHP theme for Typecho, there is no "build" process.

**Installation & Usage:**
1.  **Deploy:** Place the entire `Brave` directory into the Typecho themes folder: `/usr/themes/`.
2.  **Activate:** Log in to the Typecho Admin Console -> Appearance -> Activate "Brave".
3.  **Configure:** Go to "Change Settings" to set:
    - Couple names and avatars.
    - Relationship start date (for the timer).
    - Background images.
    - Security settings (CSP, SRI).

## Development Conventions

**Code Style:**
- **Indentation:** 4 spaces for PHP/HTML/JS.
- **Security First:**
    - Always use `App::escapeHtml`, `App::escapeUrlAttribute`, etc., for outputting user data.
    - Maintain CSP compatibility in `base/head.php`.
    - Do not use `window.onload` or inline scripts without checking CSP compliance.
- **Shortcodes:** Custom shortcode logic is in `core/shortcodes.php`. Use the `[loveList]` syntax for the checklist page.

**Key Files:**
- `functions.php`: Defines theme configuration options (Typecho admin settings).
- `core/App.php`: **Critical**. Contains the security sanitization logic, URL normalization, and shortcode handling.
- `base/main.js`: Frontend logic, including the relationship timer and PJAX event handlers.
- `loveListPage.php`: Template for the "Love List" page, parsing specific shortcodes.

**Testing:**
- Verify the relationship timer works with various date formats (or empty dates).
- Check that PJAX transitions do not break event listeners.
- Validate that the Content Security Policy (CSP) does not block valid assets (console errors).

## Interaction Guidelines

- **Language:** All communication with the user must be in **Chinese (Simplified)**.
- **Post-Modification Workflow:** After completing any code modifications, the agent must provide:
  1. A brief summary of recommendations or next steps.
  2. A detailed **Git Commit Message** formatted for direct use.