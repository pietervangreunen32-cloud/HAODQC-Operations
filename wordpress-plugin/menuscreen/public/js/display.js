( function () {
	'use strict';

	var POLL_INTERVAL_MS = 20000;
	var root = document.getElementById( 'menuscreen-root' );
	if ( ! root ) {
		return;
	}

	var restUrl = root.getAttribute( 'data-rest-url' );
	var cacheKey = 'menuscreen:display:' + location.pathname;
	var initialData = JSON.parse( root.getAttribute( 'data-initial' ) );

	function readCache() {
		try {
			var raw = localStorage.getItem( cacheKey );
			return raw ? JSON.parse( raw ) : null;
		} catch ( e ) {
			return null;
		}
	}

	function writeCache( data ) {
		try {
			localStorage.setItem( cacheKey, JSON.stringify( data ) );
		} catch ( e ) {
			// Storage can be unavailable (private mode, quota) — safe to ignore.
		}
	}

	function escapeHtml( value ) {
		var div = document.createElement( 'div' );
		div.textContent = value == null ? '' : String( value );
		return div.innerHTML;
	}

	function formatPrice( price, currency ) {
		try {
			return new Intl.NumberFormat( undefined, { style: 'currency', currency: currency || 'USD' } ).format( price );
		} catch ( e ) {
			return ( currency || '$' ) + Number( price ).toFixed( 2 );
		}
	}

	function render( data ) {
		document.title = data.name + ' — Menu';
		document.body.className = 'menuscreen-display menuscreen-theme-' + data.theme + ' menuscreen-orientation-' + data.orientation;

		var html = '';

		html += '<div class="menuscreen-header">';
		if ( data.logoUrl ) {
			html += '<img class="menuscreen-logo" src="' + escapeHtml( data.logoUrl ) + '" alt="" />';
		}
		html += '<h1 class="menuscreen-business-name">' + escapeHtml( data.name ) + '</h1>';
		html += '<span class="menuscreen-status-dot" id="menuscreen-status-dot"></span>';
		html += '</div>';

		if ( data.specialActive && data.specialText ) {
			html += '<div class="menuscreen-special">⭐ Today\'s Special: ' + escapeHtml( data.specialText ) + '</div>';
		}

		html += '<div class="menuscreen-categories">';
		( data.categories || [] ).forEach( function ( category ) {
			html += '<section class="menuscreen-category">';
			html += '<h2 class="menuscreen-category-title">' + escapeHtml( category.name ) + '</h2>';

			if ( ! category.items || ! category.items.length ) {
				html += '<p class="menuscreen-empty-category">Nothing in this category yet.</p>';
			} else {
				category.items.forEach( function ( item ) {
					html += '<div class="menuscreen-menu-item' + ( item.soldOut ? ' is-sold-out' : '' ) + '">';
					if ( item.photoUrl ) {
						html += '<img class="menuscreen-item-photo" src="' + escapeHtml( item.photoUrl ) + '" alt="" />';
					}
					html += '<div class="menuscreen-item-body">';
					html += '<div class="menuscreen-item-name-row">';
					html += '<span class="menuscreen-item-name">' + escapeHtml( item.name ) + '</span>';
					if ( item.soldOut ) {
						html += '<span class="menuscreen-sold-out-badge">SOLD OUT</span>';
					}
					html += '</div>';
					if ( item.description ) {
						html += '<p class="menuscreen-item-desc">' + escapeHtml( item.description ) + '</p>';
					}
					html += '</div>';
					html += '<span class="menuscreen-item-price">' + escapeHtml( formatPrice( item.price, data.currency ) ) + '</span>';
					html += '</div>';
				} );
			}
			html += '</section>';
		} );
		html += '</div>';

		root.innerHTML = html;
	}

	function setStatus( online ) {
		var dot = document.getElementById( 'menuscreen-status-dot' );
		if ( dot ) {
			dot.classList.toggle( 'is-offline', ! online );
			dot.title = online ? 'Live' : 'Reconnecting… showing last known menu';
		}
	}

	// Prefer a locally cached copy over the server-rendered one only if it's
	// actually newer (e.g. this exact page was served from a cache during a
	// brief outage and the browser already had a fresher poll result saved).
	var cached = readCache();
	var current = ( cached && cached.updatedAt > initialData.updatedAt ) ? cached : initialData;
	render( current );
	writeCache( current );

	function poll() {
		var xhr = new XMLHttpRequest();
		xhr.open( 'GET', restUrl, true );
		xhr.timeout = 10000;
		xhr.onload = function () {
			if ( xhr.status >= 200 && xhr.status < 300 ) {
				try {
					var fresh = JSON.parse( xhr.responseText );
					render( fresh );
					writeCache( fresh );
					setStatus( true );
				} catch ( e ) {
					setStatus( false );
				}
			} else {
				setStatus( false );
			}
		};
		xhr.onerror = function () {
			setStatus( false );
		};
		xhr.ontimeout = function () {
			setStatus( false );
		};
		xhr.send();
	}

	setInterval( poll, POLL_INTERVAL_MS );
} )();
