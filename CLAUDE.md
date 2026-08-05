# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What it is

One template tag, `wp_commentnavi()`, giving paged comments numbered links
instead of "« Older Comments". A settings screen under Settings shapes the
output. WP-PageNavi is its sibling and shares the same `Core` / `Call` /
`Options` / `Settings` split — a change to one is usually a change to both.

## Data

Two option rows: `wp_commentnavi_options` for the settings and
`wp_commentnavi_version` for the `plugin` and `db` upgrade markers. **The
markers are a row of their own and must stay that way.** A marker kept inside
the settings array has to be rescued from the stored value on every save,
because the settings form never posts one.

`WP_CommentNavi_Options::maybe_upgrade()` folds in `commentnavi_options`, the
row every release up to 1.12.2 wrote. That row is out in the world, so this
migration runs on real installs rather than on a rename nobody shipped.

## Traps

* **The tag only works inside `comments.php`.** Both figures come from
  `$wp_query->max_num_comment_pages` and the `cpage` query var, which core
  populates only inside `comments_template()`. Called anywhere else it renders
  nothing, and that is correct behaviour, not a bug.
* **`get_url()` deliberately does not pass `$max_page` to
  `get_comments_pagenum_link()`.** Passing it changes which page counts as the
  default view when `default_comments_page` is `newest`, which would rewrite the
  URL of the last comment page on every existing site.
* **`max()` guards the zero case in `get_pagination_args()`.**
  `max_num_comment_pages` is 0 on a post with no comments, and before 2.0.0 that
  zero reached the label as "Page 1 of 0".
* **`wp_commentnavi_all_comments_link()` and the `comment-all` query variable
  are gone, and that is a security fix, not a cleanup.** Any visitor could
  append `?comment-all=1` to any URL and make WordPress load up to 9999 comments
  in one query. A theme still calling the function fatals — it is the first line
  of the Upgrade Notice for that reason.
* **`anchor()` exists to escape at the sink.** Before 2.0.0 the `title`
  attribute was built by string concatenation with no escaping, so a double
  quote in a navigation text option closed the attribute and everything after it
  became markup.
* **The text options go through kses on render as well as on save**, covering
  values passed via the `options` argument or written to the row directly by
  WP-CLI or another plugin. Not redundant.
* **The stylesheet override lookup was broken before 2.0.0 and the fix is
  subtle**: it looked in `TEMPLATEPATH` (parent theme) but enqueued from
  `get_stylesheet_directory_uri()` (child theme). Under a child theme a
  parent-theme override produced a URL with nothing behind it and a child-theme
  override was never found. Each candidate is now tested and enqueued from the
  same place. The filename also changed from `commentnavi-css.css` to
  `wp-commentnavi.css`, so existing theme copies are silently ignored until
  renamed.
* The stylesheet inherits theme colours and exposes
  `--wp-commentnavi-border-color` / `--wp-commentnavi-border-color-current`. It
  used to paint blue links on white over any theme, dark ones included.
* The settings page slug no longer embeds the plugin's folder name — anyone who
  installed it under a different directory could not open the screen at all.

## Migrations, and why they are tested through a browser

**Activation hooks do not fire when a plugin is updated.** A site that updates
from the Plugins screen never calls `activate()`, so `maybe_upgrade()` also
hangs off `admin_init` — the hook every real upgrade goes through.

That difference is what `tests/e2e/upgrade.spec.js` exists for, and it is worth
understanding before changing either the migration or that file:

* **On the admin path `register_setting()` has already run**, so the sanitize
  callback is attached to the settings row and every write the migration makes
  goes through it. Under WP-CLI it is not attached at all. **A migration test
  that never registers the setting is testing WP-CLI**, not the path real sites
  take.
* **Read the row raw when the question is "was it written".**
  `WP_CommentNavi_Options::get()` merges the defaults over whatever is stored,
  so it answers identically for a row holding the defaults and for no row at
  all — which is exactly the state a migration that read, deleted and never
  wrote leaves behind.
* **Seed the shipped defaults, not customised values.** A customised fixture
  cannot see that failure: its migrated result differs from the defaults, so the
  write lands whatever the read before it did.
* `write()` passes an explicit default to `get_option()` so an absent row can be
  told from a defaulted one and `add_option()`ed. This plugin passes no
  `default` to `register_setting()` today, so the trap is not armed here; the
  helper is written that way so adding one later cannot quietly break the
  migration.

## Tests

`bin/test.sh` runs PHPUnit, `bin/test-multisite.sh` the network pass, and
`bin/test-e2e.sh` the Playwright suite. **Run them rather than trusting a note
about their last result** — CI is the authority, and this file cannot be.

**Read `tests/helper-comments-template.php` before touching the integration
tests.** The bundled test theme ships no `comments.php`, so `comments_template()`
falls back to `theme-compat/comments.php`, which opens with `_deprecated_file()`
— a failure under the WP test suite. The fixture is an intentionally empty file
pointed at by the `comments_template` filter. Declaring the deprecation instead
would tie the suite to whichever theme it runs under.

**`WP_UnitTestCase_Base::tear_down()` nulls `$wp_stylesheet_path`**, and
`comments_template()` reads it straight into `trailingslashit()` instead of
re-deriving it as `locate_template()` does — handing `null` to `rtrim()`. That
one cause produced most of the failures in this plugin's first full PHPUnit run.
The fix is `wp_set_template_globals()` in `set_up()`: restore the precondition
the harness broke, do not work around it in the plugin.

A related correction worth not undoing: a test asserted the `cpage=` substring
in the built link, which is really asserting "this install has plain
permalinks". It compares against the link core builds instead.

E2E fixtures page **five comments at a time**, not the WordPress default of ten
— more pages for the same fixture, and the number of pages is the only thing
this plugin is about. `page_comments`, `comments_per_page` and
`default_comments_page` are **not** in core's REST settings allowlist:
`requestUtils.updateSiteSettings()` accepts them and silently changes nothing,
so the fixture mu-plugin sets them with filters instead.

**A capability test must assert both directions.** "An editor cannot reach the
settings screen" passes with the plugin deactivated, because then the screen
does not exist and core refuses everybody. The test pairs it with an
administrator reaching the same screen, so the pair can only pass when the
plugin is present *and* gating.
