nLingual
========

nLingual is a simple and flexible multilingual system for WordPress. It operates on a one language per post basis, allowing you to keep different languages separate, and for the custom fields and other metadata to be translated easily as well. It also comes with a host of general utilities that expand on the built-in localization functions. What's more, it gives you complete control over the languages the system uses, and allows for the synchronization of sister posts.

Features
--------

### 1 Post : 1 Translation

There are typically two ways of tackling multilingual in WordPress (shy of a multisite solution; but let's not talk about that). You can usually either have the translations separate as independent posts, or merged together as a single post. The latter can result in bugs and can potentially break stuff, and, depending on the solution, has very limited control; nLingual uses the former method.

By going this route, you have more control over each translation. If you wanted, you can have different authors for the different languages, or have custom fields be translated, or use different terms for them.

Having said that, there will of course be plenty of instances where you'll want some information to be kept the same across versions; nLingual has post synchronization for that ([more on that later](#post-data-synchronization)).

### Two Step Language Detection

When detecting the language your site should be displayed in, nLingual has 3 options:

- Subdomain method (e.g. `fr.mydomain.com`)
- Path Prefix method (e.g. `mydomain.com/fr/`)
- Plain old GET/POST arguments (e.g. `?lang=fr`) as well as detecting the visitors browser language.

This detection is run as soon as possible, however, this can be overridden should the actual post being loaded be of a different language (and redirect to the proper URL for SEO purposes).

As mentioned, nLingual will make sure to reload any text domains when the language is changed, so any localization will reflect the new language. (Note: this functionality is not guaranteed; it works by logging all text domains loaded AFTER the plugin itself is loaded, though steps have been taken to make sure just about any domain loading is caught).

### Easy Translation Creation and Management

Managing translations is available within each post, offering a list of existing posts in each language to select as the translated version. What's better, creating a new translation of the current post you're editing is easy: click the "Create new [language] [post type]" button to generate a new post in the selected language with all the data copied over and open for editing in a new tab.

For already existing posts, nLingual adds a Language select input to the Quick and Bulk edit interfaces on the editor screens.

### Post Data Synchronization

There will of course be some bits of data for posts that you'll want to keep the same between translations; perhaps the status, the page template, and/or the categories/tags. nLingual allows you to set which post columns, metadata fields, and terms are kept synchronized, and will update the sister posts when one translation is saved.

The synchronization rules are managed on a per post type basis, allowing you to have different sync settings between, say, posts and pages. (Note: Currently, there is no override on an individual post basis).

### Flexible Language Management

This is admitedly a feature few will need, but it's a godsend for those that do. When configuring what languages nLingual uses, you can define your own or modify existing ones. Each language in the system has 6 important fields:

1. System Name: the name use to refer to the language within the admin.
2. Native Name: the name in the actual language, for use mostly on the front end.
3. Short Name: a shorthand version of the native name, if applicable.
4. ISO Code: the official ISO 639-1 code for the language (two letters).
5. Slug: normally the same as the ISO, but can be different (used for identifying the language in URLs).
6. Locale: the locale to use with this language, used for loading the appropriate .mo files.

nLingual comes preloaded with a number of languages to choose from, and in most cases you won't need to customize any. For the sake of argument though, let's say you have a site that's written in English and both forms of Chinese (simplified and traditional). For a site like this to work, you'd need two copies of the Chinese language registered for the sytem to use, with slugs and locale names different from the expected "zh" one.

### Toolkit for Theme Development

Included in this plugin are a number of functions that will be quite useful for theme development.

#### Extra Localization Functions

WordPress itself comes with a number of short and sweet localization functions; `__`, `_e`, `_x`, `_ex`, etc. nLingual however adds a few of its own: `_f`, `_ef`, `_fx`, `_efx`, `_a`, and `_xa`. Details about these functions and how to use them can be found in `php/utilities.php`.

#### The nLingual Class

The nLingual class is a static class with a host of functions for getting and manipulating language and translation data in the system. Included are alias functions: `nLingual::get_post_lang` can be called through `nL_get_post_lang`. Details about these functions and how to use them can be found in `php/nLingual.class.php` and `php/nLingual.aliases.php`.
