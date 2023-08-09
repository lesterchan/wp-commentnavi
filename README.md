# WP-CommentNavi
Contributors: GamerZ  
Donate link: http://lesterchan.net/site/donation/  
Tags: commentnavi, navi, navigation, wp-commentnavi, page  
Requires at least: 2.8  
Tested up to: 6.3  
Stable tag: trunk  

Adds a more advanced paging navigation for your comments to your WordPress blog.

## Description
Example: `Pages 1 of 20: [1] 2 3 4 ... Last`

### General Usage
1. Open `wp-content/themes/<YOUR THEME NAME>/comments.php`
2. Add: `<?php if(function_exists('wp_commentnavi')) { wp_commentnavi(); } ?>`
3. Go to `WP-Admin -> Settings-> CommentNavi` to configure WP-CommentNavi

* If you need to configure the CSS style of WP-CommentNavi, open and edit: `commentnavi-css.css`
* WP-CommentNavi will load `commentnavi-css.css` from your theme's directory if it exists.
* If it doesn't exists, it will just load the default 'commentnavi-css.css' that comes with WP-CommentNavi.
* This will allow you to upgrade WP-CommentNavi without worrying about overwriting your page navigation styles that you have create

### Build Status
[![Build Status](https://travis-ci.org/lesterchan/wp-commentnavi.svg?branch=master)](https://travis-ci.org/lesterchan/wp-commentnavi)

### Development
* [https://github.com/lesterchan/wp-commentnavi](https://github.com/lesterchan/wp-commentnavi "https://github.com/lesterchan/wp-commentnavi")

### Translations
* [http://dev.wp-plugins.org/browser/wp-commentnavi/i18n/](http://dev.wp-plugins.org/browser/wp-commentnavi/i18n/ "http://dev.wp-plugins.org/browser/wp-commentnavi/i18n/")

### Credits
* Plugin icon by [Freepik](http://www.freepik.com) from [Flaticon](http://www.flaticon.com)

### Donations
* I spent most of my free time creating, updating, maintaining and supporting these plugins, if you really love my plugins and could spare me a couple of bucks, I will really appreciate it. If not feel free to use it without any obligations.

## Changelog
### Version 1.12.2
* FIXED: XSS. esc_attr() on form values

### Version 1.12.1
* FIXED: XSS

### Version 1.12
* FIXED: commentnavi_textdomain instead of polls_textdomain. Props slightlydifferent.

### Version 1.11
* NEW: Supports WordPress Mutisite Network Activation
* NEW: Uses WordPress native uninstall.php

### Version 1.10 (01-06-2009)
* NEW: Works For WordPress 2.8
* NEW: Added "View All Comments" Link Function
* NEW: Added "first", "page" and "last" CSS Name To Link
* NEW: Use _n() Instead Of __ngettext() And _n_noop() Instead Of __ngettext_noop()
* FIXED: Removed "&amp;#8201;" Entity

### Version 1.00 (12-12-2008)
* NEW: Initial Release

## Screenshots

1. Admin - CommentNavi Options
2. Default CommentNavi Style

## Frequently Asked Questions

### To Display View All Comments Link
1. Open `wp-content/themes/<YOUR THEME NAME>/comments.php`
2. Add:
`<?php if(function_exists('wp_commentnavi_all_comments_link')) { wp_commentnavi_all_comments_link(); } ?>`
* The first value you pass in is the "View All Comments" link text
* Default: `wp_commentnavi_all_comments_link('View all comments');`
