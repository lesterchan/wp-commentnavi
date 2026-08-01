# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

WP-CommentNavi follows `_standards/STANDARDS.md` in the parent folder, which is
the contract for all nineteen plugins in the collection. Where this file and
that one disagree, that one wins.

## What it is

One template tag, `wp_commentnavi()`, giving paged comments numbered links
instead of "« Older Comments". A settings screen under Settings shapes the
output. It is wp-pagenavi's sibling and shares its `Core` / `Call` / `Options` /
`Settings` split — changes to one usually belong in the other.

## Data

`wp_commentnavi_options` and `wp_commentnavi_version`. The migration folds in
`commentnavi_options`, which the **released** version ships, so this rename is
user-facing. §2.1 names wp-commentnavi as one of the three plugins gaining its
*first* migration for that reason.

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

## Tests

**Read `tests/helper-comments-template.php` before touching the integration
tests.** The bundled test theme ships no `comments.php`, so
`comments_template()` falls back to `theme-compat/comments.php`, which opens
with `_deprecated_file()` — a failure under the WP test suite. The fixture is an
intentionally empty file pointed at by the `comments_template` filter. Declaring
the deprecation instead would tie the suite to whichever theme it runs under.

**This plugin is one of the two that produced 23 of 35 failures in the first
PHPUnit sweep**, and the cause is worth knowing (§7.2.1):
`WP_UnitTestCase_Base::tear_down()` nulls `$wp_stylesheet_path`, and
`comments_template()` reads it straight into `trailingslashit()` instead of
re-deriving it as `locate_template()` does — handing `null` to `rtrim()`. The
fix is `wp_set_template_globals()` in `set_up()` (commit `ece428d`): restore the
precondition the harness broke, do not work around it in the plugin.

A related correction: a test asserted the `cpage=` substring in the built link,
which is really asserting "this install has plain permalinks". It now compares
against the link core builds (commit `be464f8`).

E2E fixtures page **five at a time**. `page_comments`, `comments_per_page` and
`default_comments_page` are **not** in core's REST settings allowlist —
`requestUtils.updateSiteSettings()` accepts them and silently changes nothing,
so set them from the fixture.

**Known gap** (`_standards/RESUME.md`): the capability test in
`tests/e2e/commentnavi.spec.js` passes with the plugin deactivated, because it
cannot tell "capability works" from "page missing". It needs a companion
assertion that an admin *can* reach the screen. §7.5 forbids the one-sided form.
