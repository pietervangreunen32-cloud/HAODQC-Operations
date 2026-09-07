/**
 * Small admin UI helpers — confirmation prompts for destructive actions and
 * lightweight tab switching. No build step needed; kept intentionally plain.
 */
( function ( $ ) {
	'use strict';

	$( function () {
		$( '.rl-confirm' ).on( 'click', function ( e ) {
			var message = $( this ).data( 'confirm' ) || 'Are you sure?';
			if ( ! window.confirm( message ) ) {
				e.preventDefault();
			}
		} );

		$( '.rl-tabs' ).each( function () {
			var $tabs = $( this );
			var $links = $tabs.find( '.rl-tab-link' );
			var $panels = $( '.rl-tab-panel' );

			$links.on( 'click', function ( e ) {
				e.preventDefault();
				var target = $( this ).data( 'tab' );

				$links.removeClass( 'active' );
				$( this ).addClass( 'active' );

				$panels.hide();
				$panels.filter( '[data-tab="' + target + '"]' ).show();
			} );
		} );

		// Settings screen: only show the API key field for the AI provider currently selected.
		var $aiProvider = $( '#ai_provider' );
		if ( $aiProvider.length ) {
			var $aiKeyRows = $( '[data-ai-key-for]' );

			var syncAiKeyRows = function () {
				var selected = $aiProvider.val();
				$aiKeyRows.each( function () {
					var $row = $( this );
					$row.toggle( $row.data( 'ai-key-for' ) === selected );
				} );
			};

			syncAiKeyRows();
			$aiProvider.on( 'change', syncAiKeyRows );
		}
	} );
} )( jQuery );
