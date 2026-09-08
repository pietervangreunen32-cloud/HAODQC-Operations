( function () {
	'use strict';

	var POLL_INTERVAL_MS = 20000;
	var FEATURE_INTERVAL_MS = 7500;
	var CHROME_HIDE_MS = 4200;
	var MODE_STORAGE_KEY = 'menuscreen_display_mode';

	var root = document.getElementById( 'menuscreen-root' );
	if ( ! root ) {
		return;
	}

	var restUrl = root.getAttribute( 'data-rest-url' );
	var cacheKey = 'menuscreen:display:' + location.pathname;
	var initialData = JSON.parse( root.getAttribute( 'data-initial' ) );

	var state = {
		mode: readMode(),
		featureIndex: 0,
	};
	var featureTimer = null;
	var chromeTimer = null;
	var lastData = null;

	function readMode() {
		try {
			var stored = localStorage.getItem( MODE_STORAGE_KEY );
			return ( stored === 'feature' || stored === 'board' || stored === 'combos' ) ? stored : 'board';
		} catch ( e ) {
			return 'board';
		}
	}

	function writeMode( mode ) {
		try {
			localStorage.setItem( MODE_STORAGE_KEY, mode );
		} catch ( e ) {
			// Storage can be unavailable (private mode, quota) — safe to ignore.
		}
	}

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

	function heatIcons( level ) {
		var out = '';
		for ( var i = 0; i < ( level || 0 ); i++ ) {
			out += '🔥';
		}
		return out;
	}

	function ensureGoogleFont( query ) {
		var id = 'menuscreen-custom-font';
		var existing = document.getElementById( id );
		var href = 'https://fonts.googleapis.com/css2?family=' + query + '&display=swap';
		if ( existing ) {
			if ( existing.getAttribute( 'href' ) !== href ) {
				existing.setAttribute( 'href', href );
			}
			return;
		}
		var link = document.createElement( 'link' );
		link.id = id;
		link.rel = 'stylesheet';
		link.href = href;
		document.head.appendChild( link );
	}

	/** All non-sold-out items, flattened across categories. */
	function allActiveItems( data ) {
		var out = [];
		( data.categories || [] ).forEach( function ( category ) {
			( category.items || [] ).forEach( function ( item ) {
				if ( ! item.soldOut ) {
					out.push( item );
				}
			} );
		} );
		return out;
	}

	/** Featured items for spotlight mode — hero-flagged items if any exist, else everything. */
	function featureItems( data ) {
		var all = allActiveItems( data );
		var heroes = all.filter( function ( item ) {
			return !! item.hero;
		} );
		return heroes.length ? heroes : all;
	}

	function renderControls( data ) {
		var hasCombos = data.combos && data.combos.length > 0;
		var html = '<div class="menuscreen-controls">';
		html += '<button type="button" class="menuscreen-ctrl-btn menuscreen-ctrl-small" data-action="prev" aria-label="Previous">&lsaquo;</button>';
		html += '<button type="button" class="menuscreen-ctrl-btn menuscreen-ctrl-small" data-action="next" aria-label="Next">&rsaquo;</button>';
		html += '<button type="button" class="menuscreen-ctrl-btn' + ( 'feature' === state.mode ? ' is-active' : '' ) + '" data-mode="feature">Feature</button>';
		html += '<button type="button" class="menuscreen-ctrl-btn' + ( 'board' === state.mode ? ' is-active' : '' ) + '" data-mode="board">Board</button>';
		if ( hasCombos ) {
			html += '<button type="button" class="menuscreen-ctrl-btn' + ( 'combos' === state.mode ? ' is-active' : '' ) + '" data-mode="combos">Combos</button>';
		}
		html += '<span class="menuscreen-mode-note">' + escapeHtml( state.mode.charAt( 0 ).toUpperCase() + state.mode.slice( 1 ) ) + '</span>';
		html += '</div>';
		return html;
	}

	function renderFeatureSlide( data ) {
		var items = featureItems( data );
		if ( ! items.length ) {
			return '<div class="menuscreen-feature-slide"><p class="menuscreen-empty-category">Nothing to feature yet.</p></div>';
		}
		if ( state.featureIndex >= items.length ) {
			state.featureIndex = 0;
		}
		var item = items[ state.featureIndex ];
		var html = '<div class="menuscreen-feature-slide">';
		html += '<div class="menuscreen-feature-photo">';
		if ( item.photoUrl ) {
			html += '<img src="' + escapeHtml( item.photoUrl ) + '" alt="" />';
		}
		html += '</div>';
		html += '<div class="menuscreen-feature-copy">';
		if ( item.tag ) {
			html += '<span class="menuscreen-badge menuscreen-feature-badge">' + escapeHtml( item.tag ) + '</span>';
		}
		html += '<h1 class="menuscreen-feature-name">' + escapeHtml( item.name ) + '</h1>';
		if ( item.description ) {
			html += '<p class="menuscreen-feature-desc">' + escapeHtml( item.description ) + '</p>';
		}
		if ( item.heat ) {
			html += '<div class="menuscreen-feature-heat">' + heatIcons( item.heat ) + '</div>';
		}
		if ( item.diet && item.diet.length ) {
			html += '<div class="menuscreen-feature-diet">' + item.diet.map( function ( tag ) {
				return '<span class="menuscreen-badge menuscreen-diet-badge">' + escapeHtml( tag ) + '</span>';
			} ).join( '' ) + '</div>';
		}
		html += '<div class="menuscreen-feature-price-row">';
		html += '<div class="menuscreen-feature-sauce">';
		if ( item.sauce ) {
			html += 'Served with: <strong>' + escapeHtml( item.sauce ) + '</strong><br>';
		}
		html += ( item.pieces && item.pieces > 1 ? item.pieces + '-piece box' : 'portion' );
		html += '</div>';
		html += '<div class="menuscreen-feature-price">' + escapeHtml( formatPrice( item.price, data.currency ) ) + '</div>';
		html += '</div>';
		html += '</div>';
		html += '</div>';
		return html;
	}

	function renderBoard( data ) {
		var html = '<div class="menuscreen-categories menuscreen-board">';
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
					if ( item.diet && item.diet.length ) {
						html += '<div class="menuscreen-item-diet">' + item.diet.map( function ( tag ) {
							return '<span class="menuscreen-badge menuscreen-diet-badge">' + escapeHtml( tag ) + '</span>';
						} ).join( '' ) + '</div>';
					}
					html += '</div>';
					html += '<span class="menuscreen-item-price">' + escapeHtml( formatPrice( item.price, data.currency ) ) + '</span>';
					html += '</div>';
				} );
			}
			html += '</section>';
		} );

		if ( data.combos && data.combos.length ) {
			html += '<section class="menuscreen-category menuscreen-combo-column">';
			html += '<h2 class="menuscreen-category-title">Combos</h2>';
			data.combos.forEach( function ( combo ) {
				html += '<div class="menuscreen-menu-item">';
				if ( combo.photoUrl ) {
					html += '<img class="menuscreen-item-photo" src="' + escapeHtml( combo.photoUrl ) + '" alt="" />';
				}
				html += '<div class="menuscreen-item-body">';
				html += '<div class="menuscreen-item-name-row"><span class="menuscreen-item-name">' + escapeHtml( combo.name ) + '</span></div>';
				if ( combo.description ) {
					html += '<p class="menuscreen-item-desc">' + escapeHtml( combo.description ) + '</p>';
				}
				html += '</div>';
				html += '<span class="menuscreen-item-price">' + escapeHtml( formatPrice( combo.price, data.currency ) ) + '</span>';
				html += '</div>';
			} );
			html += '</section>';
		}

		html += '</div>';
		return html;
	}

	function renderCombosSlide( data ) {
		var html = '<div class="menuscreen-combo-slide">';
		html += '<div class="menuscreen-combo-intro">';
		html += '<span class="menuscreen-badge">Combos &amp; Upsells</span>';
		html += '<h1>Make it a Combo</h1>';
		html += '<p>More crunch, more value.</p>';
		html += '</div>';
		html += '<div class="menuscreen-combo-list">';
		( data.combos || [] ).forEach( function ( combo ) {
			html += '<div class="menuscreen-combo-item">';
			html += '<div class="menuscreen-combo-item-info">';
			if ( combo.photoUrl ) {
				html += '<img class="menuscreen-item-photo" src="' + escapeHtml( combo.photoUrl ) + '" alt="" />';
			}
			html += '<div><strong>' + escapeHtml( combo.name ) + '</strong>';
			if ( combo.description ) {
				html += '<span>' + escapeHtml( combo.description ) + '</span>';
			}
			html += '</div>';
			html += '</div>';
			html += '<b>' + escapeHtml( formatPrice( combo.price, data.currency ) ) + '</b>';
			html += '</div>';
		} );
		html += '</div>';
		html += '</div>';
		return html;
	}

	function renderStage( data ) {
		if ( 'feature' === state.mode ) {
			return renderFeatureSlide( data );
		}
		if ( 'combos' === state.mode && data.combos && data.combos.length ) {
			return renderCombosSlide( data );
		}
		return renderBoard( data );
	}

	function render( data ) {
		lastData = data;
		document.title = data.name + ' — Menu';
		document.body.className = 'menuscreen-display menuscreen-theme-' + data.theme + ' menuscreen-orientation-' + data.orientation;

		if ( 'custom' === data.theme && data.custom ) {
			document.body.style.setProperty( '--menuscreen-primary', data.custom.primaryColor );
			document.body.style.setProperty( '--menuscreen-background', data.custom.backgroundColor );
			document.body.style.setProperty( '--menuscreen-text', data.custom.textColor );
			document.body.style.setProperty( '--menuscreen-font', data.custom.fontFamily );
			ensureGoogleFont( data.custom.googleFontQuery );
		}

		var html = '';

		html += '<div class="menuscreen-header">';
		if ( data.logoUrl ) {
			html += '<img class="menuscreen-logo" src="' + escapeHtml( data.logoUrl ) + '" alt="" />';
		}
		html += '<h1 class="menuscreen-business-name">' + escapeHtml( data.name ) + '</h1>';
		html += renderControls( data );
		html += '<span class="menuscreen-status-dot" id="menuscreen-status-dot"></span>';
		html += '</div>';

		if ( data.specialActive && data.specialText ) {
			html += '<div class="menuscreen-special">⭐ Today\'s Special: ' + escapeHtml( data.specialText ) + '</div>';
		}

		html += '<div class="menuscreen-stage">' + renderStage( data ) + '</div>';

		if ( data.tickerText ) {
			html += '<footer class="menuscreen-ticker"><span>' + escapeHtml( data.tickerText ) + '</span></footer>';
		}

		root.innerHTML = html;
		syncFeatureTimer( data );
	}

	function syncFeatureTimer( data ) {
		if ( featureTimer ) {
			clearInterval( featureTimer );
			featureTimer = null;
		}
		if ( 'feature' !== state.mode ) {
			return;
		}
		featureTimer = setInterval( function () {
			var items = featureItems( data );
			if ( items.length ) {
				state.featureIndex = ( state.featureIndex + 1 ) % items.length;
			}
			render( data );
		}, FEATURE_INTERVAL_MS );
	}

	function setMode( mode ) {
		state.mode = mode;
		state.featureIndex = 0;
		writeMode( mode );
		if ( lastData ) {
			render( lastData );
		}
	}

	function goNext() {
		if ( 'feature' === state.mode && lastData ) {
			var items = featureItems( lastData );
			if ( items.length ) {
				state.featureIndex = ( state.featureIndex + 1 ) % items.length;
				render( lastData );
			}
		}
	}

	function goPrev() {
		if ( 'feature' === state.mode && lastData ) {
			var items = featureItems( lastData );
			if ( items.length ) {
				state.featureIndex = ( state.featureIndex - 1 + items.length ) % items.length;
				render( lastData );
			}
		}
	}

	document.addEventListener( 'click', function ( event ) {
		var modeBtn = event.target.closest ? event.target.closest( '[data-mode]' ) : null;
		if ( modeBtn ) {
			setMode( modeBtn.getAttribute( 'data-mode' ) );
			return;
		}
		var actionBtn = event.target.closest ? event.target.closest( '[data-action]' ) : null;
		if ( actionBtn ) {
			var action = actionBtn.getAttribute( 'data-action' );
			if ( 'next' === action ) {
				goNext();
			} else if ( 'prev' === action ) {
				goPrev();
			}
		}
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( 'ArrowRight' === event.key ) {
			goNext();
		} else if ( 'ArrowLeft' === event.key ) {
			goPrev();
		}
	} );

	function setStatus( online ) {
		var dot = document.getElementById( 'menuscreen-status-dot' );
		if ( dot ) {
			dot.classList.toggle( 'is-offline', ! online );
			dot.title = online ? 'Live' : 'Reconnecting… showing last known menu';
		}
	}

	function showChrome() {
		document.body.classList.remove( 'menuscreen-chrome-hidden' );
		if ( chromeTimer ) {
			clearTimeout( chromeTimer );
		}
		if ( lastData && lastData.autoHideControls ) {
			chromeTimer = setTimeout( function () {
				document.body.classList.add( 'menuscreen-chrome-hidden' );
			}, CHROME_HIDE_MS );
		}
	}

	[ 'mousemove', 'mousedown', 'touchstart', 'keydown' ].forEach( function ( evt ) {
		document.addEventListener( evt, showChrome, { passive: true } );
	} );

	// Prefer a locally cached copy over the server-rendered one only if it's
	// actually newer (e.g. this exact page was served from a cache during a
	// brief outage and the browser already had a fresher poll result saved).
	var cached = readCache();
	var current = ( cached && cached.updatedAt > initialData.updatedAt ) ? cached : initialData;
	render( current );
	writeCache( current );
	showChrome();

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
