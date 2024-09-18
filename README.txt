=== Menu du Jour ===
Contributors: Florian Gay
Tags: menu, restaurant, daily menu, shortcode, golunch
Requires at least: 4.6
Tested up to: 6.6
Requires PHP: 5.6
Stable tag: 1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

**Menu du Jour** allows you to display daily restaurant menus fetched from the GoLunch API directly on your WordPress site. Customize the appearance, date formats, and currency to match your site's style and your audience's preferences.

**Features:**

- Fetch and display menus from GoLunch API.
- Multiple display styles to choose from.
- Customizable date formats and currency symbols.
- Shortcode support: `[golunch_menus]`.
- Internationalization ready for translations.

**Note:** This plugin retrieves data from an external API (GoLunch). Ensure you have the correct Restaurant ID to display your menus.

== Installation ==

1. **Upload the Plugin:**
   - Upload the `menu-du-jour` folder to the `/wp-content/plugins/` directory.
   - Or install the plugin directly through the WordPress plugin screen.

2. **Activate the Plugin:**
   - Activate through the 'Plugins' screen in WordPress.

3. **Configure Settings:**
   - Navigate to **Settings > Menu du Jour** to set your preferences.
   - Enter your GoLunch Restaurant ID.
   - Choose your preferred display style, date format, and currency.

4. **Add Shortcode to Pages or Posts:**
   - Use the `[golunch_menus]` shortcode where you want the menus to appear.

== Frequently Asked Questions ==

= How do I obtain my GoLunch Restaurant ID? =

You can obtain your Restaurant ID from your GoLunch account dashboard or by contacting GoLunch support.

= Can I customize the appearance of the menus? =

Yes, you can choose from multiple predefined styles in the plugin settings. You can also modify the CSS files or override styles in your theme.

= The menus are not displaying. What should I do? =

- Ensure that you have entered the correct Restaurant ID.
- Check your internet connection and API availability.
- Look for error messages in your WordPress debug log.
- Make sure your server allows outbound HTTP requests.

= Is the plugin compatible with my theme? =

The plugin is designed to work with most themes. If you encounter styling issues, you may need to add custom CSS to adjust the appearance.

= Can I display menus for multiple restaurants? =

Currently, the plugin supports displaying menus for one restaurant at a time. You can change the Restaurant ID in the shortcode attributes if needed.

== Screenshots ==

1. **Plugin Settings Page:** Configure your preferences easily.
   - assets/screenshot-1.png

2. **Elegant Style Display:** A clean and modern look.
   - assets/screenshot-2.png

3. **Rustic Style Display:** For a more traditional feel.
   - assets/screenshot-3.png

== Changelog ==

= 1.1 =
* Initial release of Menu du Jour.
* Fetch and display daily menus from GoLunch API.
* Multiple display styles and customizable settings.

== Upgrade Notice ==

= 1.1 =
* First release of the plugin. Enjoy displaying your daily menus!

== Additional Information ==

**Support:**
If you need assistance, please visit the [support forums](https://wordpress.org/support/plugin/menu-du-jour/) or contact us.

**Contribute:**
We welcome contributions! Feel free to submit pull requests on our [GitHub repository](https://github.com/flowgrapher/menudujour).

**Translations:**
Help translate the plugin into more languages by contributing to the translation files.

== License ==

This plugin is licensed under the GNU General Public License v2.0 or later.

