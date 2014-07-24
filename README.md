# WP-CommentNavi
Contributors: GamerZ  
Donate link: http://lesterchan.net/site/donation/  
Tags: commentnavi, navi, navigation, wp-commentnavi, page  
Requires at least: 2.8  
Tested up to: 3.9  
Stable tag: trunk  

Adds a more advanced paging navigation for your comments to your WordPress 2.8 and above blog.

## Description
Example: `Pages 1 of 20: [1] 2 3 4 ... Last`

### Build Status
[![Build Status](https://travis-ci.org/lesterchan/wp-commentnavi.svg?branch=master)](https://travis-ci.org/lesterchan/wp-commentnavi)

### Development
* [https://github.com/lesterchan/wp-commentnavi](https://github.com/lesterchan/wp-commentnavi "https://github.com/lesterchan/wp-commentnavi")

### Translations
* [http://dev.wp-plugins.org/browser/wp-commentnavi/i18n/](http://dev.wp-plugins.org/browser/wp-commentnavi/i18n/ "http://dev.wp-plugins.org/browser/wp-commentnavi/i18n/")

### Donations
* I spent most of my free time creating, updating, maintaining and supporting these plugins, if you really love my plugins and could spare me a couple of bucks, I will really appericiate it. If not feel free to use it without any obligations.

## Changelog

### Version 1.10 (01-06-2009)
* NEW: Works For WordPress 2.8
* NEW: Added "View All Comments" Link Function
* NEW: Added "first", "page" and "last" CSS Name To Link
* NEW: Use _n() Instead Of __ngettext() And _n_noop() Instead Of __ngettext_noop()
* FIXED: Removed "&amp;#8201;" Entity

### Version 1.00 (12-12-2008)
* NEW: Initial Release

## Installation

1. Open `wp-content/plugins` Folder
2. Put: `Folder: wp-commentnavi`
3. Activate `WP-CommentNavi` Plugin
4. Go to `WP-Admin -> Settings-> CommentNavi` to configure WP-CommentNavi

### General Usage
1. Open `wp-content/themes/<YOUR THEME NAME>/comments.php`
2. Add:
`<?php if(function_exists('wp_commentnavi')) { wp_commentnavi(); } ?>`
* If you need to configure the CSS style of WP-CommentNavi, open and edit: `commentnavi-css.css`

### Note
* WP-CommentNavi will load `commentnavi-css.css` from your theme's directory if it exists.
 * If it doesn't exists, it will just load the default 'commentnavi-css.css' that comes with WP-CommentNavi.
 * This will allow you to upgrade WP-CommentNavi without worrying about overwriting your page navigation styles that you have created.

## Upgrading

1. Deactivate `WP-CommentNavi` Plugin
2. Open `wp-content/plugins` Folder
3. Put/Overwrite: `Folder: wp-commentnavi`
4. Activate `WP-CommentNavi` Plugin

## Upgrade Notice

N/A

## Screenshots

1. Admin
2. First Page
3. Last Page

## Frequently Asked Questions

### To Display View All Comments Link
1. Open `wp-content/themes/<YOUR THEME NAME>/comments.php`
2. Add:
`<?php if(function_exists('wp_commentnavi_all_comments_link')) { wp_commentnavi_all_comments_link(); } ?>`
* The first value you pass in is the "View All Comments" link text
* Default: `wp_commentnavi_all_comments_link('View all comments');`
