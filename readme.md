=== WordPress Preloader ===
Contributors:      irfanbhat
Tags:              preloader, loading screen, logo, blur, backdrop-filter
Requires at least: 5.5
Tested up to:      6.5
Requires PHP:      7.4
Stable tag:        1.2.0
License:           GPL-2.0+
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

WordPress Preloader adds a lightweight frosted-glass loading overlay to your site. Display your logo, spinner ring, and optional progress bar while the page loads.

== Description ==

WordPress Preloader creates a stylish full-screen loading screen using CSS `backdrop-filter` blur and tint. The blurred site content shows through while the preloader graphic animates, giving visitors a polished loading experience.

**Features**

* Upload a custom logo with the WordPress media library
* Set logo width in pixels
* Choose overlay tint color and opacity
* Adjust blur strength from 0 to 40px
* Pick an accent color for spinner and progress bar elements
* Toggle the spinner ring on or off
* Toggle the progress bar on or off
* Set a minimum display duration to avoid rapid flashes
* Configure fade-out duration for a smooth exit
* Disable the preloader on mobile devices
* No external dependencies — pure CSS and vanilla JavaScript

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu
3. Go to **Settings › Logo Preloader** to configure the appearance and behavior
4. If your theme does not already include it, add `<?php wp_body_open(); ?>` immediately after the opening `<body>` tag in `header.php`

== Frequently Asked Questions ==

= My theme does not support `wp_body_open()`. What should I do? =

Add `<?php wp_body_open(); ?>` right after the opening `<body>` tag in your theme's `header.php`. This hook is required for the preloader to appear on the front end.

= Can I disable the preloader on mobile devices? =

Yes. Use the mobile toggle on the settings page to disable the preloader for mobile visitors.

= How do I customize the overlay and blur effect? =

Use the admin settings to choose an overlay tint color, adjust opacity, and set blur strength between 0 and 40px.

== Changelog ==

= 1.2.0 =
* Added frosted-glass backdrop blur effect
* Added blur strength slider (0–40px)
* Added overlay opacity slider
* Added overlay tint color picker
* Added accent color support
* Improved admin live preview

= 1.0.0 =
* Initial release
