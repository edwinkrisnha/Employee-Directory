/* global employeeDir, employeeDirPage, employeeDirLocked, jQuery */
/**
 * Employee Directory — front-end search, filter, sort, pagination, A–Z nav, and view toggle.
 *
 * - Debounced AJAX search: fires 300 ms after the user stops typing.
 * - Immediate AJAX on department / sort change.
 * - AJAX pagination: numbered page buttons update results without a page reload.
 * - A–Z jump nav: filters results to employees whose name starts with the selected letter.
 *   Clears the text search (mutual exclusion). AJAX-based so pagination stays correct.
 * - Three-state view toggle: grid (default) / list / vertical — persisted to localStorage.
 * - Sort dropdown: A→Z / Z→A / Newest join / Department — persisted to localStorage.
 * - Copy email: icon button next to each email address copies it to the clipboard.
 */
( function ( $ ) {
	'use strict';

	var DEBOUNCE_MS  = 300;
	var LS_VIEW_KEY  = 'ed_view';
	var LS_SORT_KEY  = 'ed_sort';

	var $results    = $( '#ed-results' );
	var $pagination = $( '#ed-pagination' );
	var $search     = $( '#ed-search' );
	var $department = $( '#ed-department' );
	var $sort       = $( '#ed-sort' );
	var $viewBtns = $('.ed-view-btn');
	var debounceTimer;

	// Pagination state — seeded from PHP via wp_localize_script.
	var currentPage = ( window.employeeDirPage && employeeDirPage.currentPage )
		? parseInt( employeeDirPage.currentPage, 10 )
		: 1;

	// A–Z letter state.
	var currentLetter = '';

	// View state: 'grid' | 'list' | 'vertical'
	var currentView = 'grid';

	// -------------------------------------------------------------------------
	// View helpers
	// -------------------------------------------------------------------------

	function applyView(view) {
		currentView = view;

		$results
			.toggleClass('ed-results--list', view === 'list')
			.toggleClass('ed-results--vertical', view === 'vertical');

		$viewBtns.each(function () {
			var isActive = $(this).data('view') === view;
			$(this).toggleClass('is-active', isActive)
						.attr('aria-pressed', isActive);
		});
	}

	// Restore saved preference on load.
	try {
		applyView( localStorage.getItem( LS_VIEW_KEY ) || 'grid' );
	} catch ( e ) {
		applyView( 'grid' );
	}

	$(document).on('click', '.ed-view-btn', function () {
		var view = $(this).data('view');
		applyView(view);
		try { localStorage.setItem(LS_VIEW_KEY, view); } catch (e) {}
	});

	// -------------------------------------------------------------------------
	// Sort
	// -------------------------------------------------------------------------

	// Restore saved sort preference on load, then set the select value.
	( function () {
		try {
			var saved = localStorage.getItem( LS_SORT_KEY );
			if ( saved && $sort.length ) {
				$sort.val( saved );
			}
		} catch ( e ) { /* ignore */ }
	}() );

	$sort.on( 'change', function () {
		currentPage = 1;
		clearTimeout( debounceTimer );
		try { localStorage.setItem( LS_SORT_KEY, $sort.val() ); } catch ( e ) { /* ignore */ }
		fetchResults();
	} );

	// -------------------------------------------------------------------------
	// A–Z jump navigation
	// -------------------------------------------------------------------------

	function applyLetter( letter ) {
		currentLetter = letter;
		// Update active state on all letter buttons.
		$( '.ed-az-nav__link' ).each( function () {
			var btnLetter = $( this ).data( 'letter' );
			$( this ).toggleClass( 'is-active', btnLetter === letter && letter !== '' );
		} );
		// "All" button is visually active only when nothing is selected.
		$( '.ed-az-nav__all' ).toggleClass( 'is-active', letter === '' );
	}

	// Delegated click on A–Z buttons (works even if nav is outside the AJAX-replaced region).
	$( document ).on( 'click', '.ed-az-nav__link', function ( e ) {
		e.preventDefault();
		var clicked = String( $( this ).data( 'letter' ) );

		// Toggle off if the same letter is clicked again.
		var next = ( clicked === currentLetter ) ? '' : clicked;

		// Clicking a letter clears the text search (mutual exclusion).
		if ( next !== '' ) {
			$search.val( '' );
		}

		applyLetter( next );
		currentPage = 1;
		clearTimeout( debounceTimer );
		fetchResults();
	} );

	// -------------------------------------------------------------------------
	// AJAX fetch
	// -------------------------------------------------------------------------

	/**
	 * Resolve the department to send: locked value takes precedence over the dropdown.
	 */
	function getDepartment() {
		if ( window.employeeDirLocked && employeeDirLocked.department ) {
			return employeeDirLocked.department;
		}
		return $department.length ? $department.val() : '';
	}

	/**
	 * Post current search + department + sort + letter + page to the AJAX endpoint,
	 * then replace #ed-results and #ed-pagination with the returned HTML.
	 */
	function fetchResults() {
		$results.addClass( 'is-loading' );

		var data = {
			action:     employeeDir.action,
			nonce:      employeeDir.nonce,
			search:     currentLetter ? '' : $search.val(),
			department: getDepartment(),
			sort:       $sort.length ? $sort.val() : 'name_asc',
			letter:     currentLetter,
			paged:      currentPage,
		};

		// Pass locked per_page and role if set by shortcode.
		if ( window.employeeDirLocked ) {
			if ( employeeDirLocked.perPage ) {
				data.per_page = employeeDirLocked.perPage;
			}
			if ( employeeDirLocked.role ) {
				data.role = employeeDirLocked.role;
			}
		}

		$.ajax( {
			url:    employeeDir.ajaxUrl,
			method: 'POST',
			data:   data,
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
		// Typing clears any active A–Z filter.
		if ( currentLetter ) {
			applyLetter( '' );
		}
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

	// Delegate clicks on copy-email buttons (works after AJAX DOM replacement).
	$( document ).on( 'click', '.ed-copy-email', function () {
		var $btn  = $( this );
		var email = $btn.data( 'email' );
		if ( navigator.clipboard ) {
			navigator.clipboard.writeText( email ).then( function () {
				$btn.addClass( 'is-copied' );
				setTimeout( function () { $btn.removeClass( 'is-copied' ); }, 1500 );
			} );
		}
	} );

} ( jQuery ) );
