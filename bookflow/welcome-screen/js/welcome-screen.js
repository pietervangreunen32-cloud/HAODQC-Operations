/**
 * Renders the welcome screen from the data embedded in the page (first
 * paint) and then polls the REST endpoint every 30 seconds so the display
 * moves on to the next appointment on its own — this is meant to run
 * unattended on a TV, with nobody there to hit refresh.
 *
 * Every poll re-fetches, but a render only happens when the payload
 * actually changed — otherwise the entrance animation would restart
 * every 30 seconds, which reads as flickering rather than "elegant" on
 * a screen meant to sit still in a fitting room.
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
	var lastPayload = null;

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

	function buildScreen( data ) {
		if ( ! data.has_appointment ) {
			return el( 'div', { class: 'bookflow-idle bookflow-enter' }, [
				el( 'h1', { text: 'Welcome to ' + data.shop_name } ),
			] );
		}

		var names = [ data.first_name ].concat( data.companion_names || [] ).filter( Boolean );
		var wrap = el( 'div', { class: 'bookflow-welcome' } );
		var stagger = 0;

		var eyebrow = el( 'p', { class: 'bookflow-eyebrow bookflow-enter', text: 'Welcome' } );
		eyebrow.style.animationDelay = ( stagger++ * 120 ) + 'ms';
		wrap.appendChild( eyebrow );

		var names_el = el( 'h1', { class: 'bookflow-names bookflow-enter', text: names.join( ' & ' ) } );
		names_el.style.animationDelay = ( stagger++ * 120 ) + 'ms';
		wrap.appendChild( names_el );

		if ( data.countdown_days !== null && data.countdown_days !== undefined ) {
			var countdownText = 0 === data.countdown_days
				? "Today's the big day!"
				: data.countdown_days + ' day' + ( 1 === data.countdown_days ? '' : 's' ) + ' to go!';
			var countdown_el = el( 'p', { class: 'bookflow-countdown bookflow-enter', text: countdownText } );
			countdown_el.style.animationDelay = ( stagger++ * 120 ) + 'ms';
			wrap.appendChild( countdown_el );
		}

		if ( data.items && data.items.length ) {
			var grid = el( 'div', { class: 'bookflow-item-grid' } );
			data.items.forEach( function ( item ) {
				var card = el( 'div', { class: 'bookflow-item-card bookflow-enter' } );
				card.style.animationDelay = ( stagger++ * 120 ) + 'ms';
				if ( item.image ) {
					card.appendChild( el( 'img', { src: item.image, alt: item.name } ) );
				}
				card.appendChild( el( 'span', { class: 'bookflow-item-name', text: item.name } ) );
				grid.appendChild( card );
			} );
			wrap.appendChild( grid );
		}

		return wrap;
	}

	function render( data ) {
		root.innerHTML = '';
		root.appendChild( buildScreen( data ) );
	}

	/**
	 * Swaps in new content with a brief crossfade instead of an abrupt
	 * replace — the "elegant transition" the display is meant to feel
	 * like from across a fitting room, not a page reload.
	 */
	function transitionTo( data ) {
		root.classList.add( 'bookflow-leaving' );
		window.setTimeout( function () {
			render( data );
			root.classList.remove( 'bookflow-leaving' );
		}, 260 );
	}

	function poll() {
		fetch( restUrl, { headers: { 'X-WP-Nonce': nonce } } )
			.then( function ( r ) {
				return r.json();
			} )
			.then( function ( data ) {
				var payload = JSON.stringify( data );
				if ( payload === lastPayload ) {
					return; // Nothing changed — leave the current render (and its animation) alone.
				}
				var isFirstRender = null === lastPayload;
				lastPayload = payload;
				if ( isFirstRender ) {
					render( data );
				} else {
					transitionTo( data );
				}
			} )
			.catch( function () {
				// Transient network hiccup on an unattended screen — keep
				// showing the last known-good state and try again next tick.
			} );
	}

	try {
		var initial = JSON.parse( root.getAttribute( 'data-initial' ) );
		lastPayload = JSON.stringify( initial );
		render( initial );
	} catch ( e ) {
		poll();
	}

	setInterval( poll, POLL_MS );
} )();
