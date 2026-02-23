/* global employeeDir, employeeDirPage, jQuery */
/**
 * Employee Directory — front-end search, filter, pagination, and view toggle.
 *
 * - Debounced AJAX search: fires 300 ms after the user stops typing.
 * - Immediate AJAX on department change.
 * - AJAX pagination: numbered page buttons update results without a page reload.
 * - Three-state view toggle: grid (default) / list / vertical — persisted to localStorage.
 * - Copy email: clipboard button next to each employee's email address.
 */
( function ( $ ) {
	'use strict';

	var DEBOUNCE_MS = 300;
	var LS_VIEW_KEY = 'ed_view';

	var $results    = $( '#ed-results' );
	var $pagination = $( '#ed-pagination' );
	var $search     = $( '#ed-search' );
	var $department = $( '#ed-department' );
	var $toggle     = $( '#ed-view-toggle' );
	var $vtoggle    = $( '#ed-vertical-toggle' );
	var debounceTimer;

	// Pagination state — seeded from PHP via wp_localize_script.
	var currentPage = ( window.employeeDirPage && employeeDirPage.currentPage )
		? parseInt( employeeDirPage.currentPage, 10 )
		: 1;

	// View state: 'grid' | 'list' | 'vertical'
	var currentView = 'grid';

	// -------------------------------------------------------------------------
	// View helpers
	// -------------------------------------------------------------------------

	function applyView( view ) {
		currentView = view;
		$results.toggleClass( 'ed-results--list',     view === 'list' );
		$results.toggleClass( 'ed-results--vertical', view === 'vertical' );

		$toggle.attr(  'aria-pressed', String( view === 'list' ) );
		$toggle.attr(  'aria-label',   view === 'list' ? 'Switch to grid view'     : 'Switch to list view' );
		$toggle.text(  view === 'list' ? 'Grid view' : 'List view' );

		$vtoggle.attr( 'aria-pressed', String( view === 'vertical' ) );
		$vtoggle.attr( 'aria-label',   view === 'vertical' ? 'Switch to grid view' : 'Switch to vertical view' );
		$vtoggle.text( view === 'vertical' ? 'Grid view' : 'Vertical view' );
	}

	// Restore saved preference on load.
	try {
		applyView( localStorage.getItem( LS_VIEW_KEY ) || 'grid' );
	} catch ( e ) {
		applyView( 'grid' );
	}

	// List/grid toggle — toggles between 'list' and 'grid'.
	$toggle.on( 'click', function () {
		var next = currentView === 'list' ? 'grid' : 'list';
		applyView( next );
		try { localStorage.setItem( LS_VIEW_KEY, next ); } catch ( e ) { /* ignore */ }
	} );

	// Vertical toggle — toggles between 'vertical' and 'grid'.
	$vtoggle.on( 'click', function () {
		var next = currentView === 'vertical' ? 'grid' : 'vertical';
		applyView( next );
		try { localStorage.setItem( LS_VIEW_KEY, next ); } catch ( e ) { /* ignore */ }
	} );

	// -------------------------------------------------------------------------
	// AJAX fetch
	// -------------------------------------------------------------------------

	/**
	 * Post current search + department + page to the AJAX endpoint,
	 * then replace #ed-results and #ed-pagination with the returned HTML.
	 */
	function fetchResults() {
		$results.addClass( 'is-loading' );

		$.ajax( {
			url:    employeeDir.ajaxUrl,
			method: 'POST',
			data: {
				action:     employeeDir.action,
				nonce:      employeeDir.nonce,
				search:     $search.val(),
				department: $department.length ? $department.val() : '',
				paged:      currentPage,
			},
			success: function ( response ) {
				if ( ! response.success ) {
					return;
				}
				if ( typeof response.data.html === 'string' ) {
					$results.html( response.data.html );
					// Re-apply the active view classes after DOM replacement.
					applyView( currentView );
				}
				if ( typeof response.data.pagination === 'string' ) {
					$pagination.replaceWith( response.data.pagination );
					// Re-cache the element reference after DOM replacement.
					$pagination = $( '#ed-pagination' );
				}
			},
			// On network/server error, leave existing results intact.
			complete: function () {
				$results.removeClass( 'is-loading' );
			},
		} );
	}

	// -------------------------------------------------------------------------
	// Search + filter
	// -------------------------------------------------------------------------

	// Debounce keystrokes so we don't fire on every character.
	$search.on( 'input', function () {
		currentPage = 1;
		clearTimeout( debounceTimer );
		debounceTimer = setTimeout( fetchResults, DEBOUNCE_MS );
	} );

	// Department change fires immediately — no debounce needed.
	$department.on( 'change', function () {
		currentPage = 1;
		clearTimeout( debounceTimer );
		fetchResults();
	} );

	// -------------------------------------------------------------------------
	// Pagination
	// -------------------------------------------------------------------------

	// Delegate clicks on pagination buttons (works after DOM replacement).
	$( document ).on( 'click', '.ed-pagination__btn', function () {
		var $btn = $( this );
		if ( $btn.is( ':disabled' ) || $btn.hasClass( 'is-current' ) ) {
			return;
		}
		var page = parseInt( $btn.data( 'page' ), 10 );
		if ( ! isNaN( page ) && page >= 1 ) {
			currentPage = page;
			fetchResults();
			// Scroll back to the top of the results for usability.
			$( 'html, body' ).animate(
				{ scrollTop: $results.offset().top - 20 },
				200
			);
		}
	} );

	// -------------------------------------------------------------------------
	// Copy email
	// -------------------------------------------------------------------------

	$( document ).on( 'click', '.ed-copy-email', function () {
		var email = $( this ).data( 'email' );
		if ( navigator.clipboard && email ) {
			navigator.clipboard.writeText( email );
		}
	} );

} ( jQuery ) );
