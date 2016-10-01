nLingual
===

The nLingual system allows for flexible multilingual support and translation management for WordPress. The system handles translations on a per-post basis, and can be set to be synchronized so changes to certain details on one are copied to the others. It offers you control over what can be translated and how, with a number of utilities available for 3rd party themes and plugins to utilize.

**nLingual 2 offers more robust control of translation management, better extensibility, and fixes to numerous core issues with the previous incarnation.**

Translation for Almost Anything
---

When setting up, you have control over what content supports translation. Any UI-enabled post types or taxonomies will be available for enabling, along with any navigation menus or sidebar locations registered. In addition, nLingual includes a LocalizeThis API that can be enabled on nearly any text field found in the admin, allowing just about any option or meta field to support separate values in each language.

Simple Translation Creation and Management
---

Assigning a language and translations to a post can be done on either the post editor screen or the posts management screen via Quick Edit (language can also be set for multiple post via Bulk Edit). You can also easily create new translations for existing posts on the fly; select "New \[language\] \[post type\]", provide a translated title if you wish, and a new draft post will be created that is an exact copy of the original, ready for translation.

Translations are stored as independent posts, associated with their counterparts via a custom table. This allows you to translate the custom fields and other metadata associated with a post, and can assign them their own separate terms if desired. However, since there are plenty of occasions where you want the same information used between posts, nLingual offers *post synchronization*.

Post Synchronization
---

Each post type has it's own rules for what data is synchronized between translations. When changes are saved to a post, it's translations will be updated with to have the same data in the approved fields. This covers post data (e.g. date, status, and menu order), terms of specified taxonomies, and any meta fields you specify (e.g. the thumbnail image used, or a custom field value).

**Note: Currently, there is no per-post basis override for the synchronization rules**

Free-form Language Management
---

Admittedly, this is a feature few will need, but it's a godsend to those that do. When setting up the languages nLingual will use, you can define you own languages from scratch or based on numerous presets. Each language has a number of fields:

- System Name: the name to use when referring to the language within the admin.
- Native Name: the name of the language as it appears to native speakers on the site.
- Short Name: a shorthand version of the native name, if applicable.
- Locale: the language/country code to represent this language, as well identify the .mo file to load for text domains.
- ISO Code: the official ISO 639-1 code for the language (2 letters)
- Slug: the value to use when localizing a URL for the language (typically the same as the ISO code).
- Text Direction: the text direction the language should be rendered in (Left-to-right or right-to-left). Will override the one specified in the text domain files.
- Active State: wether or not to allow public access to content in the language.

Flexible Language Detection/Switching
---

When the public-facing side of the site is loaded, nLingual will attempt to detect what language to serve the page in, using the following process:

1. Use the language code in the `$_REQUEST` array for the specified key, if present.
2. Use the language code in either the subdomain or directory path, depending on method specified.
3. Use the browser's preferred language setting and find the closest match, falling back to the default language.

Once the language is set, it can be overridden by the language belonging to the requested post. This override is an configurable option.

In addition, the language can temporarily be switched to another by 3rd party theme or plugin code, similar to switching blogs in a multisite installation. When the language is switched, all text domain files will be reloaded in the desired language (the originals cached for when it's restored), so any gettext translations will reflect the current language.

Extensibility and 3rd Party Development
---

In addition to numerous hooks to modify the functionality of nLingual, this plugin also includes some useful gettext utilities: `_f`, `_ef`, `_fx`, `_efx`, `_a`, and `_xa`, all of which are documented in `includes/functions-gettext.php`.

Backwards Compatibility
---

Although nLingual 2 has be rewritten from scratch, most if not all of the functions and filters are still available via the backwards compatibility feature, which is automatically enabled upon upgrading. However, any code that directly queries the database using the old nLingual language and translation tables will need to be updated to reflect the new structure.
