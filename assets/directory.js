/* global employeeDir, jQuery */
/**
 * Employee Directory — front-end search and filter.
 *
 * Debounced AJAX search: fires 300 ms after the user stops typing,
 * or immediately on department change.
 */
( function ( $ ) {
	'use strict';

	var DEBOUNCE_MS = 300;

	var $results    = $( '#ed-results' );
	var $search     = $( '#ed-search' );
	var $department = $( '#ed-department' );
	var debounceTimer;

	/**
	 * Post current search + department to the AJAX endpoint,
	 * then replace #ed-results with the returned HTML.
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
			},
			success: function ( response ) {
				if ( response.success && typeof response.data.html === 'string' ) {
					$results.html( response.data.html );
				}
			},
			// On network/server error, leave existing results intact.
			complete: function () {
				$results.removeClass( 'is-loading' );
			},
		} );
	}

	// Debounce keystrokes so we don't fire on every character.
	$search.on( 'input', function () {
		clearTimeout( debounceTimer );
		debounceTimer = setTimeout( fetchResults, DEBOUNCE_MS );
	} );

	// Department change fires immediately — no debounce needed.
	$department.on( 'change', function () {
		clearTimeout( debounceTimer );
		fetchResults();
	} );

} ( jQuery ) );
