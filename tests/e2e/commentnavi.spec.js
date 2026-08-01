/**
 * WP-CommentNavi on the front end and on its settings screen.
 *
 * A comment pagination plugin needs three things to render at all, and a suite
 * missing any of them would assert that an empty page is still empty:
 *
 *   1. WordPress comment paging switched on -- it is off by default, so a post
 *      with a thousand comments still has exactly one page of them.
 *   2. More comments than fit on one of those pages.
 *   3. A theme that calls wp_commentnavi(), which is a template tag and which no
 *      bundled theme calls. That is tests/e2e/mu-plugins.
 *
 * All three are set up before the first test.
 */

const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

const SETTINGS_URL = '/wp-admin/options-general.php?page=wp-commentnavi';

/**
 * Five to a page, set by the fixture mu-plugin, matching the demo site.
 *
 * Twenty-five comments is five pages, which is also the default width of the
 * plugin's window -- so every page number is on screen and a test can assert the
 * whole set rather than a slice of it.
 */
const COMMENTS = 25;
const PER_PAGE = 5;
const PAGES = Math.ceil( COMMENTS / PER_PAGE );

/** The post every test comments on, created once. */
let post;

/**
 * The navigation the shim prints between the list and the form.
 *
 * @param {import('@playwright/test').Page} page Page under test.
 * @return {import('@playwright/test').Locator} The wp-commentnavi wrapper.
 */
function nav( page ) {
	return page.locator( '#wp-commentnavi-e2e .wp-commentnavi' );
}

/**
 * Put the settings back, so one test's edit is not the next one's fixture.
 *
 * @param {Object} requestUtils The e2e request helper.
 * @return {Promise<void>} Resolves once the row is gone.
 */
async function resetOptions( requestUtils ) {
	// The route belongs to the fixture mu-plugin, not the plugin: this settings
	// row is for an admin screen and correctly has no REST route of its own.
	await requestUtils.rest( {
		method: 'POST',
		path: '/wp-commentnavi-e2e/v1/reset',
	} );
}

test.describe( 'WP-CommentNavi', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		await requestUtils.deleteAllPosts();

		// Comment paging is switched on by the fixture mu-plugin, not from here:
		// page_comments, comments_per_page and default_comments_page are not in
		// core's REST settings allowlist, so setting them over the API changed
		// nothing at all and did so without complaining.

		post = await requestUtils.createPost( {
			title: 'A much discussed post',
			content: 'Body.',
			status: 'publish',
			comment_status: 'open',
		} );

		// Numbered, so a test can tell one page of comments from another -- and
		// created one at a time, which matters more than it looks. Sent in
		// parallel they land in the same second, so comment_date ties and the
		// order falls back to insertion order, which is whatever the server got
		// to first. "Comment number 01" then stops being the oldest comment and
		// every assertion about which page it is on becomes a coin toss.
		for ( let i = 0; i < COMMENTS; i++ ) {
			await requestUtils.rest( {
				method: 'POST',
				path: '/wp/v2/comments',
				data: {
					post: post.id,
					content: `Comment number ${ String( i + 1 ).padStart( 2, '0' ) }`,
					author_name: `Commenter ${ i + 1 }`,
					author_email: `commenter${ i + 1 }@example.com`,
					status: 'approved',
					date: new Date( Date.now() - ( COMMENTS - i ) * 60_000 )
						.toISOString()
						.slice( 0, 19 ),
				},
			} );
		}
	} );

	test.afterEach( async ( { requestUtils } ) => {
		await resetOptions( requestUtils );
	} );

	test( 'the fixture really is more than one page of comments', async ( { page } ) => {
		// The assertion the rest of the file leans on. Comment paging off, or too
		// few comments, and every test below would pass with the plugin removed.
		await page.goto( post.link );

		// li.comment alone: the default walker wraps each comment in an <li> and
		// an <article class="comment-body">, so a selector matching both counts
		// every comment twice.
		const shown = await page.locator( '.comment-list li.comment' ).count();

		expect( shown ).toBe( PER_PAGE );
		await expect( nav( page ) ).toBeVisible();
	} );

	test( 'shows a page number for every page, and marks the current one', async ( { page } ) => {
		await page.goto( post.link );

		const navigation = nav( page );

		await expect( navigation.locator( 'a.page, span.current' ) ).toHaveCount( PAGES );
		await expect( navigation.locator( 'span.current' ) ).toHaveText( '1' );
		await expect( navigation.locator( '[aria-current="page"]' ) ).toHaveCount( 1 );
	} );

	test( 'the pages text counts pages of comments', async ( { page } ) => {
		await page.goto( post.link );

		await expect( nav( page ).locator( '.pages' ) ).toHaveText( `Page 1 of ${ PAGES }` );
	} );

	test( 'clicking a page number shows a different set of comments', async ( { page } ) => {
		await page.goto( post.link );

		const comments = page.locator( '.comment-list li.comment' );

		// Oldest first, so page one starts at comment 01.
		await expect( comments.first() ).toContainText( 'Comment number 01' );

		await nav( page ).getByRole( 'link', { name: '2', exact: true } ).click();

		await expect( nav( page ).locator( 'span.current' ) ).toHaveText( '2' );

		// The second ten, not the first ten again under a different heading.
		// Scoped to the list rather than the page: a sidebar widget showing
		// recent comments would otherwise make this assertion about the theme.
		await expect( comments.first() ).toContainText(
			`Comment number ${ String( PER_PAGE + 1 ).padStart( 2, '0' ) }`
		);
		await expect( page.locator( '.comment-list' ) ).not.toContainText( 'Comment number 01' );
	} );

	test( 'previous and next appear only where they lead somewhere', async ( { page } ) => {
		await page.goto( post.link );

		// nextcommentslink, not nextpostslink: this plugin names its classes for
		// what it pages. The two are otherwise the same shape, which is exactly
		// how a test copied from its sibling passes by looking for nothing.
		await expect( nav( page ).locator( '.previouscommentslink' ) ).toHaveCount( 0 );
		await expect( nav( page ).locator( '.nextcommentslink' ) ).toHaveCount( 1 );

		await nav( page ).locator( '.nextcommentslink' ).click();
		await expect( nav( page ).locator( '.previouscommentslink' ) ).toHaveCount( 1 );

		// Walk to the end rather than clicking a fixed number of times: how many
		// pages there are is a function of the fixture and the per-page setting,
		// and a test that hardcodes two clicks quietly stops testing the last
		// page the moment either changes.
		for ( let i = 2; i < PAGES; i++ ) {
			await nav( page ).locator( '.nextcommentslink' ).click();
			await expect( nav( page ).locator( 'span.current' ) ).toHaveText( String( i + 1 ) );
		}

		await expect( nav( page ).locator( 'span.current' ) ).toHaveText( String( PAGES ) );
		await expect( nav( page ).locator( '.nextcommentslink' ) ).toHaveCount( 0 );
	} );

	test( 'the page link is a comment page, not a post page', async ( { page } ) => {
		await page.goto( post.link );

		const href = await nav( page )
			.getByRole( 'link', { name: '2', exact: true } )
			.getAttribute( 'href' );

		// /comment-page-2/ or ?cpage=2 -- either way it must be the comment
		// cursor. Linking to /page/2/ would page the posts and leave the comments
		// where they were, which is the bug this plugin exists next to.
		expect( href ).toMatch( /comment-page-2|cpage=2/ );
	} );

	test( 'the dropdown style renders a select instead of the numbers', async ( {
		page,
		admin,
	} ) => {
		await admin.visitAdminPage( 'options-general.php', 'page=wp-commentnavi' );
		await page.locator( '#wp-commentnavi-style' ).selectOption( '2' );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.locator( '.notice-success, .settings-error' ).first() ).toBeVisible();

		await page.goto( post.link );

		const dropdown = nav( page ).locator( 'select' );

		await expect( dropdown ).toBeVisible();
		await expect( dropdown.locator( 'option' ) ).toHaveCount( PAGES );
		await expect( dropdown.locator( 'option.current' ) ).toHaveText( '1' );
	} );

	test( 'nothing is rendered on a post with one page of comments', async ( {
		page,
		requestUtils,
	} ) => {
		const quiet = await requestUtils.createPost( {
			title: 'A quiet post',
			content: 'Body.',
			status: 'publish',
			comment_status: 'open',
		} );

		await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/comments',
			data: {
				post: quiet.id,
				content: 'The only comment',
				author_name: 'Someone',
				author_email: 'someone@example.com',
				status: 'approved',
			},
		} );

		await page.goto( quiet.link );

		// One page of comments is not a thing to paginate.
		await expect( nav( page ) ).toHaveCount( 0 );
	} );

	test( 'nothing is rendered on a post with no comments at all', async ( {
		page,
		requestUtils,
	} ) => {
		const silent = await requestUtils.createPost( {
			title: 'A silent post',
			content: 'Body.',
			status: 'publish',
			comment_status: 'open',
		} );

		await page.goto( silent.link );

		await expect( nav( page ) ).toHaveCount( 0 );
	} );
} );

test.describe( 'The WP-CommentNavi settings screen', () => {
	test.afterEach( async ( { requestUtils } ) => {
		await resetOptions( requestUtils );
	} );

	test( 'sits under Settings, with its text and display fields', async ( { page, admin } ) => {
		await admin.visitAdminPage( 'options-general.php' );

		const entry = page.locator(
			'#adminmenu a[href="options-general.php?page=wp-commentnavi"]'
		);
		await expect( entry ).toHaveCount( 1 );

		await entry.click();

		await expect( page.locator( '#wp-commentnavi-pages_text' ) ).toBeVisible();
		await expect( page.locator( '#wp-commentnavi-num_pages' ) ).toBeVisible();
	} );

	test( 'a changed text template shows up under the comments', async ( { page, admin } ) => {
		await admin.visitAdminPage( 'options-general.php', 'page=wp-commentnavi' );

		await page
			.locator( '#wp-commentnavi-pages_text' )
			.fill( 'Batch %CURRENT_PAGE% of %TOTAL_PAGES%' );

		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.locator( '.notice-success, .settings-error' ).first() ).toBeVisible();

		await page.goto( post.link );

		await expect( nav( page ).locator( '.pages' ) ).toHaveText( `Batch 1 of ${ PAGES }` );
	} );

	test( 'the stylesheet is enqueued, and the setting turns it off', async ( { page, admin } ) => {
		await page.goto( post.link );

		await expect(
			page.locator( 'link[rel="stylesheet"][href*="wp-commentnavi"]' )
		).toHaveCount( 1 );

		await admin.visitAdminPage( 'options-general.php', 'page=wp-commentnavi' );

		await page.locator( '#wp-commentnavi-use_commentnavi_css-0' ).check();
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.locator( '.notice-success, .settings-error' ).first() ).toBeVisible();

		await page.goto( post.link );

		await expect(
			page.locator( 'link[rel="stylesheet"][href*="wp-commentnavi"]' )
		).toHaveCount( 0 );
	} );

	test( 'a user without manage_options cannot reach the screen', async ( {
		browser,
		baseURL,
		requestUtils,
	} ) => {
		const username = 'commentnavi_editor';

		const existing = await requestUtils.rest( {
			path: '/wp/v2/users',
			params: { search: username, context: 'edit' },
		} );

		if ( ! existing.length ) {
			await requestUtils.rest( {
				method: 'POST',
				path: '/wp/v2/users',
				data: {
					username,
					email: `${ username }@example.com`,
					password: 'correct-horse-battery-staple',
					roles: [ 'editor' ],
				},
			} );
		}

		const context = await browser.newContext( { storageState: undefined } );
		const other = await context.newPage();

		await other.goto( `${ baseURL }/wp-login.php` );
		await other.locator( '#user_login' ).fill( username );
		await other.locator( '#user_pass' ).fill( 'correct-horse-battery-staple' );
		await other.locator( '#wp-submit' ).click();
		await expect( other.locator( '#wpadminbar' ) ).toBeVisible();

		await other.goto( `${ baseURL }${ SETTINGS_URL }` );

		await expect( other.locator( 'body' ) ).toContainText(
			/do not have sufficient permissions|not allowed to access this page/
		);

		await context.close();
	} );
} );
