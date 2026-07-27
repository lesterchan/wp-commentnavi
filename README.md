# WP-CommentNavi
Contributors: GamerZ  
Donate link: https://lesterchan.net/site/donation/  
Tags: comments, navigation, pagination, paging, pages  
Requires at least: 6.0  
Tested up to: 7.0  
Stable tag: 2.0.0  
Requires PHP: 7.4  
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds a more advanced paging navigation for your comments to your WordPress blog.

## Description
Replaces the plain *&larr; Older Comments | Newer Comments &rarr;* links with numbered page links.

Example: `Page 1 of 20: [1] 2 3 4 ... Last`

### Usage
1. Open `wp-content/themes/<YOUR THEME NAME>/comments.php`
2. Add: `<?php if ( function_exists( 'wp_commentnavi' ) ) { wp_commentnavi(); } ?>`
3. Go to *WP-Admin -> Settings -> CommentNavi* to configure it

The template tag reads figures that WordPress only populates inside `comments_template()`, so it belongs in `comments.php` and produces nothing anywhere else.

It also accepts an array of arguments:

```php
<?php
wp_commentnavi(
	array(
		'wrapper_tag'   => 'nav',
		'wrapper_class' => 'my-comment-nav',
		'options'       => array( 'num_pages' => 3 ),
	)
);
?>
```

Pass `'echo' => false` to get the markup back as a string instead of printing it.

### Changing the CSS

If you need to configure the CSS style of WP-CommentNavi, copy `commentnavi-css.css` from the plugin directory into your theme's directory and edit it there. Your changes then survive plugin updates. A copy in a child theme wins over one in the parent theme.

Alternatively, set *Use commentnavi-css.css* to **No** on the settings screen and put the styles in your theme's `style.css`.

### Changing Class Names

These [filters](https://developer.wordpress.org/plugins/hooks/filters/) change the class names assigned to the navigation elements:

* `wp_commentnavi_class_pages`
* `wp_commentnavi_class_first`
* `wp_commentnavi_class_previouscommentslink`
* `wp_commentnavi_class_extend`
* `wp_commentnavi_class_smaller`
* `wp_commentnavi_class_page`
* `wp_commentnavi_class_current`
* `wp_commentnavi_class_larger`
* `wp_commentnavi_class_nextcommentslink`
* `wp_commentnavi_class_last`

### Other Filters

* `wp_commentnavi` — the complete markup, before it is printed
* `wp_commentnavi_allowed_html` — the tags allowed inside the navigation text settings

### Development
* [https://github.com/lesterchan/wp-commentnavi](https://github.com/lesterchan/wp-commentnavi "https://github.com/lesterchan/wp-commentnavi")

### Credits
* Plugin icon by [Freepik](https://www.freepik.com) from [Flaticon](https://www.flaticon.com)

### Donations
* I spent most of my free time creating, updating, maintaining and supporting these plugins, if you really love my plugins and could spare me a couple of bucks, I will really appreciate it. If not feel free to use it without any obligations.

## Changelog
### 2.0.0
* NEW: Restructured into `includes/` following the Plugin Handbook layout. The plugin is now four classes behind the same template tags
* NEW: The settings page is built with the WordPress Settings API
* NEW: Requires WordPress 6.0 and PHP 7.4
* NEW: Larger page numbers, as in WP-PageNavi. Off by default, so upgrading does not change how your comment navigation looks — set *Number Of Larger Page Numbers To Show* to turn it on
* NEW: A *Use commentnavi-css.css* setting, so the stylesheet can be turned off without editing anything
* NEW: Ten `wp_commentnavi_class_*` filters for the element class names, a `wp_commentnavi` filter for the whole markup, and `wp_commentnavi_allowed_html` for the tags permitted in the navigation text
* NEW: `wp_commentnavi()` accepts an array of arguments, including `'echo' => false` to return the markup instead of printing it
* IMPORTANT: The "View all comments" feature has been removed — the `comment-all` query variable and `wp_commentnavi_all_comments_link()`. It hooked `pre_get_posts` with no `is_main_query()` or `is_singular()` guard and forced `comments_per_page` to 9999, so any visitor could trigger an unbounded comment query on any URL. If your theme calls `wp_commentnavi_all_comments_link()` you must remove that call. See the FAQ
* IMPORTANT: The settings page moved to *Settings -> CommentNavi* at `options-general.php?page=commentnavi`. The old address embedded the plugin's directory name, which made the screen unreachable for anyone who had installed the plugin under a different one. Update your bookmarks
* FIXED: Network activation and multisite uninstall were fatal errors. Both called `wp_get_sites()`, which WordPress removed in 5.1
* FIXED: Multisite uninstall stopped at the hundredth site, leaving the settings behind everywhere after it, and restored the current blog once instead of once per site
* FIXED: XSS. The first, last and numbered links put the navigation text straight into a `title` attribute with no escaping, so a double quote in a setting closed the attribute and everything after it became markup
* FIXED: An option row saved by an older version raised a PHP notice on the front end for every setting added since. Defaults are now merged on read
* FIXED: The "Text For Previous ..." box displayed the "Text For Next ..." value, so the two could never be given different text through the settings screen
* FIXED: Saving the settings added a backslash before every apostrophe, visibly, on the site itself. `addslashes()` was applied on top of the slashes WordPress already adds to `$_POST`
* FIXED: A `commentnavi-css.css` in your theme was looked for in the parent theme but loaded from the child theme, so under a child theme neither location worked
* FIXED: The plugin directory name was hardcoded, so the stylesheet 404d if the plugin was installed under any other directory name
* FIXED: A post with no comments reported "Page 1 of 0" when *Always Show Comment Navigation* was on
* FIXED: The drop-down style pointed its form at an admin URL, and `case 2;` used a semicolon, which PHP 8.5 deprecates
* FIXED: The stylesheet is enqueued on `wp_enqueue_scripts` rather than `wp_print_styles`, and its version tracks the plugin version instead of being pinned to 1.10
* FIXED: `commentnavi-css.css` no longer carries a dead `.wp-commentnavi-all-comments-link` rule for the removed feature. No style declaration changed
* NOTE: Four settings labels were renamed from "Text For First/Last/Next/Previous Comment" to "... Page", because those links go to a page of comments rather than to a comment. Renaming a string resets it on translate.wordpress.org, so those four labels will show in English in translated locales until someone retranslates them. Every other existing label kept its original wording precisely to avoid that

### 1.12.2
* FIXED: XSS. esc_attr() on form values

### 1.12.1
* FIXED: XSS

### 1.12
* FIXED: commentnavi_textdomain instead of polls_textdomain. Props slightlydifferent.

### 1.11
* NEW: Supports WordPress Mutisite Network Activation
* NEW: Uses WordPress native uninstall.php

### 1.10
* NEW: Works For WordPress 2.8
* NEW: Added "View All Comments" Link Function
* NEW: Added "first", "page" and "last" CSS Name To Link
* NEW: Use _n() Instead Of __ngettext() And _n_noop() Instead Of __ngettext_noop()
* FIXED: Removed "&amp;#8201;" Entity

### 1.00
* NEW: Initial Release

## Screenshots

1. Admin - CommentNavi Options
2. Default CommentNavi Style

## Frequently Asked Questions

### My theme calls wp_commentnavi_all_comments_link() and the site is broken
That function was removed in 2.0.0, along with the `comment-all` query variable it depended on. Open `wp-content/themes/<YOUR THEME NAME>/comments.php` and delete the line that calls it.

It was removed because the query variable was not guarded. Any visitor could append `?comment-all=1` to any URL on the site and force WordPress to load up to 9999 comments in a single query, on every request, with no rate limiting — the hook ran on `pre_get_posts` without checking `is_main_query()` or even `is_singular()`.

If you want to keep offering a way to read every comment at once, raise *Number Of Pages To Show*, or set the comment paging options under *Settings -> Discussion* to suit.

### Where did the settings page go?
It is at *Settings -> CommentNavi*, which is now `options-general.php?page=commentnavi`.

The old address was `options-general.php?page=wp-commentnavi/commentnavi-options.php`, which had the plugin's directory name inside it. Anyone who installed the plugin under a different directory name — a rename, a deploy tool, a bundled copy — got a settings screen that could not be opened at all.

### The navigation disappeared after I put an SVG icon in the previous or next text
That is fixed in 2.0.0. `wp_kses_post()`, which the settings screen used to filter with, deletes an `<svg>` element rather than cleaning it. That left the link text empty, and a link with no text is dropped altogether — so the whole link vanished, not just the icon.

The navigation text is now filtered against `wp_kses_post()`'s list plus the inline SVG elements. Use the `wp_commentnavi_allowed_html` filter to adjust that list.

### My comment navigation suddenly shows extra numbers like 10, 20, 30
Those are larger page numbers, and they are off by default, so upgrading will not have caused this. Set *Number Of Larger Page Numbers To Show* to `0` under *Settings -> CommentNavi* to turn them off.

### Nothing renders at all
`wp_commentnavi()` reads `$wp_query->max_num_comment_pages`, which WordPress only sets inside `comments_template()`. Call it from your theme's `comments.php`, not from `single.php`, a sidebar or a widget.

Also check that *Break comments into pages* is enabled under *Settings -> Discussion*. With comment paging off there is only ever one page, and the navigation hides itself unless *Always Show Comment Navigation* is set to Yes.
