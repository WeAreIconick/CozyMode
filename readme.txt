=== Cozy Mode ===
Contributors: cozymode
Tags: reading mode, typography, accessibility, readability, distraction-free
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Transform any WordPress post into a distraction-free reading experience with optimal typography settings powered by Readability.js.

== Description ==

Cozy Mode transforms any WordPress post or page into a distraction-free reading experience using research-backed typography specifications for optimal readability and comprehension.

**Key Features:**

* **Optimal Typography**: 66 characters per line, 1.5 line height, 18px font size baseline
* **Content Extraction**: Powered by Mozilla's Readability.js for clean content parsing
* **Accessibility First**: WCAG AAA compliant with keyboard navigation and screen reader support
* **Responsive Design**: Optimized typography across all device sizes
* **Dark Mode**: Toggle between light and dark themes
* **Font Controls**: Adjustable font size with user preference memory
* **Theme Compatibility**: Works with any WordPress theme
* **Gutenberg Support**: Full compatibility with block editor content

**Research-Backed Specifications:**

* Line length: 50-75 characters (66 optimal) for desktop, 30-50 for mobile
* Line height: 1.5-1.6 (150%-160% of font size) for optimal reading flow
* Font size: 18px baseline with responsive scaling (16px mobile, 20px large screens)
* Font family: Georgia serif for body text, system fonts for headings
* Paragraph spacing: 1.5em for clear content separation
* Color contrast: 7:1+ ratio (WCAG AAA compliance)

**How It Works:**

1. Click the "Enter Cozy Mode" button on any blog post
2. Readability.js automatically extracts the main content
3. Content displays in a modal overlay with optimal typography
4. Use controls to adjust font size and toggle dark mode
5. Preferences are saved in localStorage for future visits

**Accessibility Features:**

* Full keyboard navigation (Tab, Shift+Tab, Enter, Space, Escape)
* Focus trapping within the modal
* ARIA attributes for screen readers
* High contrast mode support
* Reduced motion support
* Screen reader text descriptions

== Installation ==

1. Upload the `cozy-mode` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Visit any blog post to see the "Enter Cozy Mode" button
4. Click the button to experience distraction-free reading

== Frequently Asked Questions ==

= Does this work with all themes? =

Yes! Cozy Mode works with any WordPress theme by overlaying content in a modal. It doesn't modify your theme's styling.

= What content types are supported? =

Cozy Mode works with:
* Standard WordPress blog posts
* Gutenberg block editor content
* Posts with custom fields and meta data

= Does it work on mobile devices? =

Absolutely! Cozy Mode is fully responsive with optimized typography for mobile devices (16px font size, adjusted padding).

= Can I customize the typography settings? =

The plugin uses research-backed defaults, but you can override CSS custom properties in your theme if needed. The settings are optimized for maximum readability.

= Does it work with page builders? =

Cozy Mode works with most page builders through Readability.js content extraction. It's fully compatible with Gutenberg and works well with Elementor, Divi, and other popular builders.

= Is it accessible? =

Yes! Cozy Mode is built with accessibility in mind:
* WCAG AAA compliant color contrast
* Full keyboard navigation
* Screen reader support
* Focus management
* ARIA attributes throughout

= Does it affect SEO? =

No, Cozy Mode doesn't affect SEO. It's purely a client-side reading enhancement that doesn't modify your content or affect search engine indexing.

= Can I disable it on certain posts? =

Currently, Cozy Mode appears on all blog posts. Future versions may include options to disable on specific content types.

= What browsers are supported? =

Cozy Mode works in all modern browsers that support:
* CSS Custom Properties (CSS Variables)
* ES6 JavaScript features
* Fetch API

This includes Chrome 49+, Firefox 31+, Safari 9.1+, and Edge 16+.

== Screenshots ==

1. The "Enter Cozy Mode" button appears on blog posts
2. Clicking opens a distraction-free reading experience
3. Typography optimized for readability with 66 characters per line
4. Dark mode toggle and font size controls
5. Responsive design works on all devices

== Changelog ==

= 1.0.0 =
* Initial release
* Research-backed typography specifications
* Readability.js integration for content extraction
* Full accessibility compliance (WCAG AAA)
* Responsive design for all devices
* Dark mode toggle
* Font size controls with localStorage preferences
* Keyboard navigation and focus management
* Theme compatibility
* Gutenberg support

== Upgrade Notice ==

= 1.0.0 =
Initial release of Cozy Mode with optimal typography and accessibility features.

== Technical Details ==

**Security Features:**
* Nonce verification for all interactions
* Input sanitization and output escaping
* No direct database queries
* Conditional asset loading

**Performance:**
* Assets only load on singular posts/pages
* Client-side content extraction (no server requests)
* Minimal JavaScript footprint
* CSS custom properties for efficient styling

**Compatibility:**
* WordPress 5.0+
* PHP 7.4+
* Modern browsers with ES6 support
* All WordPress themes
* Gutenberg block editor

== Support ==

For support, feature requests, or bug reports, please visit the plugin's GitHub repository or contact the developer.

== Credits ==

* Typography research based on Baymard Institute, Nielsen Norman Group, and W3C guidelines
* Content extraction powered by Mozilla's Readability.js
* Accessibility guidelines from WCAG 2.1 AA/AAA standards
* Font recommendations from Butterick's Practical Typography
