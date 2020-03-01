=== nLingual ===
Contributors: dougwollison
Tags: multilingual, language, bilingual, translation
Requires at least: 5.2
Tested up to: 5.3.2
Requires PHP: 5.6.20
Stable tag: 2.8.10
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A simple but flexible multilingual system. Features custom language management, post data synchronization and theme/plugin development utilities.

== Description ==

The nLingual system allows for flexible multilingual support and translation management for WordPress. The system handles translations on a per-post basis, and can be set to be synchronized so changes to certain details on one are copied to the others. It offers you control over what can be translated and how, with a number of utilities available for 3rd party themes and plugins to utilize.

**nLingual 2 offers more robust control of translation management, better extensibility, and fixes to numerous core issues with the previous incarnation.**

= Translation for Almost Anything =

When setting up, you have control over what content supports translation. Any UI-enabled post types or taxonomies will be available for enabling, along with any navigation menus or sidebar locations registered. In addition, nLingual includes a LocalizeThis API that can be enabled on nearly any text field found in the admin, allowing just about any option or meta field to support separate values in each language.

= Simple Translation Creation and Management =

Assigning a language and translations to a post can be done on either the post editor screen or the posts management screen via Quick Edit (language can also be set for multiple post via Bulk Edit). You can also easily create new translations for existing posts on the fly; select "New \[language\] \[post type\]", provide a translated title if you wish, and a new draft post will be created that is an exact copy of the original, ready for translation.

Translations are stored as independent posts, associated with their counterparts via a custom table. This allows you to translate the custom fields and other metadata associated with a post, and can assign them their own separate terms if desired. However, since there are plenty of occasions where you want the same information used between posts, nLingual offers *post synchronization*.

= Post Synchronization =

Each post type has it's own rules for what data is synchronized between translations. When changes are saved to a post, it's translations will be updated with to have the same data in the approved fields. This covers post data (e.g. date, status, and menu order), terms of specified taxonomies, and any meta fields you specify (e.g. the thumbnail image used, or a custom field value).

**Note: Currently, there is no per-post basis override for the synchronization rules**

= Free-form Language Management =

Admittedly, this is a feature few will need, but it's a godsend to those that do. When setting up the languages nLingual will use, you can define you own languages from scratch or based on numerous presets. Each language has a number of fields:

- System Name: the name to use when referring to the language within the admin.
- Native Name: the name of the language as it appears to native speakers on the site.
- Short Name: a shorthand version of the native name, if applicable.
- Locale: the language/country code to represent this language, as well identify the .mo file to load for text domains.
- ISO Code: the official ISO 639-1 code for the language (2 letters)
- Slug: the value to use when localizing a URL for the language (typically the same as the ISO code).
- Text Direction: the text direction the language should be rendered in (Left-to-right or right-to-left). Will override the one specified in the text domain files.
- Active State: wether or not to allow public access to content in the language.

= Flexible Language Detection/Switching =

When the public-facing side of the site is loaded, nLingual will attempt to detect what language to serve the page in, using the following process:

1. Use the language code in the `$_REQUEST` array for the specified key, if present.
2. Use the language code in either the subdomain or directory path, depending on method specified.
3. Use the browser's preferred language setting and find the closest match, falling back to the default language.

Once the language is set, it can be overridden by the language belonging to the requested post. This override is an configurable option.

In addition, the language can temporarily be switched to another by 3rd party theme or plugin code, similar to switching blogs in a multisite installation. When the language is switched, all text domain files will be reloaded in the desired language (the originals cached for when it's restored), so any gettext translations will reflect the current language.

= Extensibility and 3rd Party Development =

In addition to numerous hooks to modify the functionality of nLingual, this plugin also includes some useful gettext utilities: `_f`, `_ef`, `_fx`, `_efx`, `_a`, and `_xa`, all of which are documented in `includes/functions-gettext.php`.

= Backwards Compatibility =

Although nLingual 2 has be rewritten from scratch, most if not all of the functions and filters are still available via the backwards compatibility feature, which is automatically enabled upon upgrading. However, any code that directly queries the database using the old nLingual language and translation tables will need to be updated to reflect the new structure.

== Installation ==

1. Upload the contents of `nlingual.tar.gz` to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Register the language to use under Translations > Languages
4. Select what content you want to support translation/localization under Translations > Localizables
5. Define your desired synchronization rules under Translations > Synchronizer
6. Configure your translation options under Translations.
7. Start assigning languages and translations to your content.

== Changelog ==

**Details on each release can be found [on the GitHub releases page](https://github.com/dougwollison/nlingual/releases) for this project.**

= 2.8.10 =
Improved blog switching support. Minor synchronizer fixes.

= 2.8.9.3 =
Admin javascript now transpiled/minified for better browser compatibility.

= 2.8.9.2 =
Fixed issue with version 1 compatibility tool `nL_get_post_lang` returning object instead of slug.

= 2.8.9 =
Fixed issue with meta values not being localized, and internal WordPress URLs being blindly localized.

= 2.8.8.1 =
Hotfix: minor but annoying error with URL previews on settings screen.

= 2.8.8 =
Fixed issue with Create new translation button not working on subdirectory installs.

= 2.8.7 =
Fixed issue preventing translations from keeping the original's publish date.

= 2.8.6 =
Fixed issue causing specifically post field clone rules to be ignored.

= 2.8.5 =
Fixed issue causing post clone/sync rules to be ignored.

= 2.8.4 =
Fixed issue causing stacking of the prefix on localized URLs.

= 2.8.3 =
Fixed handling of sites running on subdirectories, fixed issue with paginated urls.

= 2.8.2 =
Fixed issue with localizing URLs for sites on subdirectories.

= 2.8.1 =
Minor adjustments to meta box registration, bug fixes.

= 2.8.0 =
Simplified Language & Translations meta box interface, revised post cloning process, bug fixes with upgrading, language setting, and testing.

= 2.7.0 =
Language detection fixes, added detection and rewriting to backend, including the login page.

= 2.6.0 =
Additional system support, improved translation interface, and the usual bug fixes.

= 2.5.0 =
Added support for WooCommerce pages and endpoints when localizing the current URL.

= 2.4.0 =
Tweaked handling of inactive languages, deprecated font patching option, added TinyMCE support to LocalizeThis tool.

= 2.3.3 =
Fixed multisite-specific database query problems.

= 2.3.2 =
Fixed typo causing terms to be erased when trying to sync.

= 2.3.1 =
Code cleanup and bug fixes for re-hooking and edge-cases.

= 2.3.0 =
Fixed install/uninstall issues.

= 2.2.0 =
Improvements to query handling, synchronization, URLs, and liaison functionality.

= 2.1.0 =
Fixed issues with post synchronization and handling of `language_is_required` option.

= 2.0.1 =
Fixed overlooked bug with Registry's option whitelist testing.

= 2.0.0 =
Complete and utter overhaul. Improved API, UI, UX, AWES, and OME.

= 1.3.1 =
Minor restructuring, prepping for 2.0 release.

= 1.3.0 =
Minor improvements, mostly restructuring to match WordPress standards better; also fixed issues with QuickStart compatibility hooks.

= 1.2.4 =
Minor bug fix to sync rules saving when no postdata fields are checked off. Also added adjustments to allow support for has_nav_menu() on the front end.

= 1.2.3 =
Coding improvements and language table adjustments (props vianney).

= 1.2.2 =
Bug fix with languages table creation (props vianney)

= 1.2.1 =
Bug fixes and ACF compatibility improvements.

= 1.2.0 =
Admin-only feature, numerous bug fixes, doc updates; a lot of stuff.

= 1.1.4 =
Fixed how uninstall.php deletes nLingual related options.

= 1.1.3 =
Fixed issue with metadata synchronization. Also added more commenting on PHP files.

= 1.1.2 =
Updated version number in main plugin file (was still at 1.0). Hehe, oops.

= 1.1.1 =
Added skip current language feature to lang_links (also minor commenting updates).

= 1.1.0 =
Added other_lang methods.

= 1.0.1 =
Fixed associate_posts and added translation management to quickedit.

= 1.0 =
Initial public release.
