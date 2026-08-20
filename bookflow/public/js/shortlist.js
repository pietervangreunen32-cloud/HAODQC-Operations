/**
 * BookFlow shareable shortlist (Phase 2). Two modes on one page:
 *  - Browse mode (default): the full catalog with a heart toggle on each
 *    item. Favorites are kept in localStorage (no account needed) and can
 *    be turned into a shareable link.
 *  - Shared mode (?bookflow_shortlist=KEY in the URL): a read-only view of
 *    someone else's shared list, for a partner/parent/friend clicking a
 *    link — no hearting controls shown.
 */
( function () {
	'use strict';

	var cfg = window.BookFlowShortlistConfig || {};
	var root = document.getElementById( 'bookflow-shortlist-app' );
	if ( ! root ) {
		return;
	}

	var STORAGE_KEY = 'bookflow_shortlist_favorites';
	var sharedKey = new URLSearchParams( window.location.search ).get( 'bookflow_shortlist' );

	function apiGet( path ) {
		return fetch( cfg.restUrl + path, { headers: { 'X-WP-Nonce': cfg.nonce } } ).then( function ( r ) {
			if ( ! r.ok ) {
				throw new Error( cfg.i18n.genericError );
			}
			return r.json();
		} );
	}

	function apiPost( path, body ) {
		return fetch( cfg.restUrl + path, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
			body: JSON.stringify( body ),
		} ).then( function ( r ) {
			return r.json().then( function ( data ) {
				if ( ! r.ok ) {
					throw new Error( data.message || cfg.i18n.genericError );
				}
				return data;
			} );
		} );
	}

	function getFavorites() {
		try {
			return JSON.parse( window.localStorage.getItem( STORAGE_KEY ) || '[]' );
		} catch ( e ) {
			return [];
		}
	}

	function setFavorites( ids ) {
		window.localStorage.setItem( STORAGE_KEY, JSON.stringify( ids ) );
	}

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

	function renderItemCard( item, opts ) {
		var card = el( 'div', { class: 'bookflow-sl-card' } );
		if ( item.image ) {
			card.appendChild( el( 'img', { src: item.image, alt: item.name, loading: 'lazy' } ) );
		}
		card.appendChild( el( 'span', { class: 'bookflow-sl-name', text: item.name } ) );
		if ( item.size ) {
			card.appendChild( el( 'span', { class: 'bookflow-sl-size', text: item.size } ) );
		}
		if ( opts && opts.heartable ) {
			var isFav = opts.favorites.indexOf( item.id ) !== -1;
			var heart = el( 'button', {
				type: 'button',
				class: 'bookflow-sl-heart' + ( isFav ? ' is-active' : '' ),
				'aria-label': isFav ? cfg.i18n.unheart : cfg.i18n.heart,
				text: isFav ? '♥' : '♡',
			} );
			heart.addEventListener( 'click', function () {
				opts.onToggle( item.id );
			} );
			card.appendChild( heart );
		}
		return card;
	}

	function renderSharedView() {
		root.innerHTML = '';
		root.appendChild( el( 'h2', { text: cfg.i18n.shared } ) );

		apiGet( '/shortlists/' + encodeURIComponent( sharedKey ) )
			.then( function ( data ) {
				var grid = el( 'div', { class: 'bookflow-sl-grid' } );
				data.items.forEach( function ( item ) {
					grid.appendChild( renderItemCard( item, { heartable: false } ) );
				} );
				root.appendChild( grid );
			} )
			.catch( function ( err ) {
				root.appendChild( el( 'p', { class: 'bookflow-sl-error', text: err.message } ) );
			} );
	}

	function renderBrowseView() {
		var favorites = getFavorites();

		root.innerHTML = '';
		root.appendChild( el( 'h2', { text: cfg.i18n.title } ) );

		var status = el( 'p', { class: 'bookflow-sl-status', style: 'display:none;' } );

		apiGet( '/items' ).then( function ( items ) {
			var grid = el( 'div', { class: 'bookflow-sl-grid' } );

			if ( ! items.length ) {
				root.appendChild( el( 'p', { text: cfg.i18n.empty } ) );
				return;
			}

			items.forEach( function ( item ) {
				grid.appendChild(
					renderItemCard( item, {
						heartable: true,
						favorites: favorites,
						onToggle: function ( id ) {
							var idx = favorites.indexOf( id );
							if ( idx === -1 ) {
								favorites.push( id );
							} else {
								favorites.splice( idx, 1 );
							}
							setFavorites( favorites );
							renderBrowseView();
						},
					} )
				);
			} );
			root.appendChild( grid );

			var shareBar = el( 'div', { class: 'bookflow-sl-sharebar' } );
			var shareBtn = el( 'button', { type: 'button', class: 'bookflow-btn bookflow-btn-primary', text: cfg.i18n.shareButton } );
			shareBtn.disabled = favorites.length === 0;
			shareBtn.addEventListener( 'click', function () {
				apiPost( '/shortlists', { item_ids: favorites } )
					.then( function ( data ) {
						var link = el( 'input', { type: 'text', readonly: 'readonly', value: data.share_url, class: 'bookflow-sl-link' } );
						var copyBtn = el( 'button', { type: 'button', class: 'bookflow-btn', text: 'Copy' } );
						copyBtn.addEventListener( 'click', function () {
							link.select();
							if ( navigator.clipboard ) {
								navigator.clipboard.writeText( data.share_url );
							} else {
								document.execCommand( 'copy' );
							}
							copyBtn.textContent = cfg.i18n.shareCopied;
						} );
						status.style.display = 'block';
						status.innerHTML = '';
						status.appendChild( link );
						status.appendChild( copyBtn );
					} )
					.catch( function ( err ) {
						status.style.display = 'block';
						status.textContent = err.message;
					} );
			} );
			shareBar.appendChild( shareBtn );
			shareBar.appendChild( status );
			root.appendChild( shareBar );
		} );
	}

	if ( sharedKey ) {
		renderSharedView();
	} else {
		renderBrowseView();
	}
} )();
