/**
 * Renders the welcome screen from the data embedded in the page (first
 * paint) and then polls the REST endpoint every 30 seconds so the display
 * moves on to the next appointment on its own — this is meant to run
 * unattended on a TV, with nobody there to hit refresh.
 */
( function () {
	'use strict';

	var root = document.getElementById( 'bookflow-welcome-screen' );
	if ( ! root ) {
		return;
	}

	var restUrl = root.getAttribute( 'data-rest-url' );
	var nonce   = root.getAttribute( 'data-nonce' );
	var POLL_MS = 30000;

	function el( tag, attrs, children ) {
		var node = document.createElement( tag );
		attrs = attrs || {};
		Object.keys( attrs ).forEach( function ( key ) {
			if ( 'class' === key ) {
				node.className = attrs[ key ];
			} else if ( 'text' === key ) {
				node.textContent = attrs[ key ];
			} else {
				node.setAttribute( key, attrs[ key ] );
			}
		} );
		( children || [] ).forEach( function ( child ) {
			if ( child ) {
				node.appendChild( child );
			}
		} );
		return node;
	}

	function render( data ) {
		root.innerHTML = '';

		if ( ! data.has_appointment ) {
			root.appendChild(
				el( 'div', { class: 'bookflow-idle' }, [
					el( 'h1', { text: 'Welcome to ' + data.shop_name } ),
				] )
			);
			return;
		}

		var names = [ data.first_name ].concat( data.companion_names || [] ).filter( Boolean );

		var wrap = el( 'div', { class: 'bookflow-welcome' } );

		wrap.appendChild( el( 'p', { class: 'bookflow-eyebrow', text: 'Welcome' } ) );
		wrap.appendChild( el( 'h1', { class: 'bookflow-names', text: names.join( ' & ' ) } ) );

		if ( data.countdown_days !== null && data.countdown_days !== undefined ) {
			var countdownText = 0 === data.countdown_days
				? "Today's the big day!"
				: data.countdown_days + ' day' + ( 1 === data.countdown_days ? '' : 's' ) + ' to go!';
			wrap.appendChild( el( 'p', { class: 'bookflow-countdown', text: countdownText } ) );
		}

		if ( data.items && data.items.length ) {
			var grid = el( 'div', { class: 'bookflow-item-grid' } );
			data.items.forEach( function ( item ) {
				var card = el( 'div', { class: 'bookflow-item-card' } );
				if ( item.image ) {
					card.appendChild( el( 'img', { src: item.image, alt: item.name } ) );
				}
				card.appendChild( el( 'span', { class: 'bookflow-item-name', text: item.name } ) );
				grid.appendChild( card );
			} );
			wrap.appendChild( grid );
		}

		root.appendChild( wrap );
	}

	function poll() {
		fetch( restUrl, { headers: { 'X-WP-Nonce': nonce } } )
			.then( function ( r ) {
				return r.json();
			} )
			.then( function ( data ) {
				render( data );
			} )
			.catch( function () {
				// Transient network hiccup on an unattended screen — keep
				// showing the last known-good state and try again next tick.
			} );
	}

	try {
		render( JSON.parse( root.getAttribute( 'data-initial' ) ) );
	} catch ( e ) {
		poll();
	}

	setInterval( poll, POLL_MS );
} )();
