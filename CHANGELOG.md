# Changelog

All notable changes to the AyeCode Font Awesome Settings will be documented in this file.

## [3.0.5-beta] - 2026-TBD
- `ayecode_get_icon()` dropped the `title` option when rendering an `<i>` tag, so a tooltip set on an icon (the review form's rating stars) only showed in SVG mode; the attribute is now set in every mode - FIXED
- The Loading Method field on the settings page shows the effective method, so a default set through the `wp-font-awesome-settings` filter (BlockStrap sets SVG) is what the page selects when nothing has been saved - CHANGED
- The Kit URL field showed as required in SVG mode, but SVG mode never used it for free icons (they come from the CDN; Pro icons use the API key); the field now appears only for the KIT method or SVG with Pro, where the admin loads it for Pro icons, and is no longer marked required - FIXED
- Every icon rendered read and parsed the plugin settings and parsed its identifier twice, about 9 ms for the 400 icons of a listing page; the settings are built once per request and each identifier is parsed once - CHANGED

## [3.0.4-beta] - 2026-09-30
- Inline SVG icons no longer include the XML declaration and are minified - FIXED

## [3.0.3-beta] - 2026-05-18
- Added `title` as a allowed param for `ayecode_icon()` - ADDED

## [3.0.2-beta] - 2026-04-22
- Changed to new AyeCode package autoloader standards - CHANGED

## [3.0.1-beta] - 2026-04-08
- Autoloader fix - CHANGED

## [3.0.0-beta] - 2026-04-07
* Settings page now uses AyeCode-UI - CHANGED
* Custom icons option added - ADDED
* Helper functions added  which should always be used ayecode_get_icon() and ayecode_icon() - ADDED
* Added our own icon picker - ADDED
