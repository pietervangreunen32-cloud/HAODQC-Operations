/**
 * BookFlow booking wizard — Phase 1: solo booking only (catalog select,
 * date/time, contact details, confirmation). Group/party companions and
 * the waitlist offer are added to this file in Phase 2.
 *
 * Plain vanilla JS by design: BookFlow ships with no build step, so any
 * shop can drop the plugin in without a Node/npm toolchain.
 */
( function () {
	'use strict';

	var cfg = window.BookFlowConfig || {};
	var root = document.getElementById( 'bookflow-app' );
	if ( ! root ) {
		return;
	}

	var state = {
		step: 1,
		items: [],
		selectedItemIds: [],
		date: '',
		time: '',
		slots: [],
	};

	function apiGet( path ) {
		return fetch( cfg.restUrl + path, {
			headers: { 'X-WP-Nonce': cfg.nonce },
		} ).then( function ( r ) {
			if ( ! r.ok ) {
				throw new Error( 'request_failed' );
			}
			return r.json();
		} );
	}

	function apiPost( path, body ) {
		return fetch( cfg.restUrl + path, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': cfg.nonce,
			},
			body: JSON.stringify( body ),
		} ).then( function ( r ) {
			return r.json().then( function ( data ) {
				if ( ! r.ok ) {
					var err = new Error( data.message || cfg.i18n.genericError );
					throw err;
				}
				return data;
			} );
		} );
	}

	function el( tag, attrs, children ) {
		var node = document.createElement( tag );
		attrs = attrs || {};
		Object.keys( attrs ).forEach( function ( key ) {
			if ( 'class' === key ) {
				node.className = attrs[ key ];
			} else if ( 'text' === key ) {
				node.textContent = attrs[ key ];
			} else if ( 'html' === key ) {
				node.innerHTML = attrs[ key ]; // Trusted, locally-built strings only.
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

	function render() {
		root.innerHTML = '';
		root.appendChild( renderStepper() );

		if ( 1 === state.step ) {
			root.appendChild( renderCatalogStep() );
		} else if ( 2 === state.step ) {
			root.appendChild( renderDateTimeStep() );
		} else if ( 3 === state.step ) {
			root.appendChild( renderDetailsStep() );
		} else if ( 4 === state.step ) {
			root.appendChild( renderConfirmedStep() );
		}
	}

	function renderStepper() {
		var labels = [ cfg.i18n.chooseItems, cfg.i18n.chooseDateTime, cfg.i18n.yourDetails, cfg.i18n.confirmed ];
		var wrap = el( 'ol', { class: 'bookflow-stepper' } );
		labels.forEach( function ( label, i ) {
			var stepNum = i + 1;
			var classes = 'bookflow-step';
			if ( stepNum === state.step ) {
				classes += ' is-active';
			} else if ( stepNum < state.step ) {
				classes += ' is-done';
			}
			wrap.appendChild( el( 'li', { class: classes, text: label } ) );
		} );
		return wrap;
	}

	function renderCatalogStep() {
		var wrap = el( 'div', { class: 'bookflow-step-panel' } );
		wrap.appendChild( el( 'h3', { text: cfg.i18n.chooseItems } ) );

		if ( ! state.items.length ) {
			wrap.appendChild( el( 'p', { text: '…' } ) );
			apiGet( '/items' ).then( function ( items ) {
				state.items = items;
				render();
			} );
			return wrap;
		}

		var grid = el( 'div', { class: 'bookflow-catalog-grid' } );
		state.items.forEach( function ( item ) {
			var selected = state.selectedItemIds.indexOf( item.id ) !== -1;
			var card = el( 'button', {
				type: 'button',
				class: 'bookflow-catalog-card' + ( selected ? ' is-selected' : '' ),
				'aria-pressed': selected ? 'true' : 'false',
			} );
			if ( item.image ) {
				card.appendChild( el( 'img', { src: item.image, alt: item.name, loading: 'lazy' } ) );
			}
			card.appendChild( el( 'span', { class: 'bookflow-catalog-name', text: item.name } ) );
			if ( item.size ) {
				card.appendChild( el( 'span', { class: 'bookflow-catalog-size', text: item.size } ) );
			}
			card.addEventListener( 'click', function () {
				var idx = state.selectedItemIds.indexOf( item.id );
				if ( idx === -1 ) {
					state.selectedItemIds.push( item.id );
				} else {
					state.selectedItemIds.splice( idx, 1 );
				}
				render();
			} );
			grid.appendChild( card );
		} );
		wrap.appendChild( grid );

		var next = el( 'button', { type: 'button', class: 'bookflow-btn bookflow-btn-primary', text: '→' } );
		next.disabled = state.selectedItemIds.length === 0;
		next.textContent = state.selectedItemIds.length === 0 ? 'Select at least one item to continue' : 'Continue';
		next.addEventListener( 'click', function () {
			state.step = 2;
			render();
		} );
		wrap.appendChild( next );

		return wrap;
	}

	function renderDateTimeStep() {
		var wrap = el( 'div', { class: 'bookflow-step-panel' } );
		wrap.appendChild( el( 'h3', { text: cfg.i18n.chooseDateTime } ) );

		var dateInput = el( 'input', { type: 'date', value: state.date, min: todayIso() } );
		dateInput.addEventListener( 'change', function ( e ) {
			state.date = e.target.value;
			state.time = '';
			state.slots = [];
			render();
		} );
		wrap.appendChild( el( 'label', { text: 'Date' } ) );
		wrap.appendChild( dateInput );

		if ( state.date ) {
			if ( ! state.slots.length ) {
				var loading = el( 'p', { text: '…' } );
				wrap.appendChild( loading );
				apiGet( '/availability?date=' + encodeURIComponent( state.date ) ).then( function ( slots ) {
					state.slots = slots;
					render();
				} );
			} else {
				var slotWrap = el( 'div', { class: 'bookflow-slots' } );
				var anyAvailable = false;
				state.slots.forEach( function ( slot ) {
					if ( slot.available ) {
						anyAvailable = true;
					}
					var btn = el( 'button', {
						type: 'button',
						class: 'bookflow-slot' + ( slot.time === state.time ? ' is-selected' : '' ),
						text: slot.time,
					} );
					btn.disabled = ! slot.available;
					btn.addEventListener( 'click', function () {
						state.time = slot.time;
						render();
					} );
					slotWrap.appendChild( btn );
				} );
				wrap.appendChild( slotWrap );

				if ( ! anyAvailable ) {
					wrap.appendChild( el( 'p', { class: 'bookflow-notice', text: cfg.i18n.noSlots } ) );
				}
			}
		}

		var back = el( 'button', { type: 'button', class: 'bookflow-btn', text: '← Back' } );
		back.addEventListener( 'click', function () {
			state.step = 1;
			render();
		} );
		wrap.appendChild( back );

		var next = el( 'button', { type: 'button', class: 'bookflow-btn bookflow-btn-primary', text: 'Continue' } );
		next.disabled = ! state.time;
		next.addEventListener( 'click', function () {
			state.step = 3;
			render();
		} );
		wrap.appendChild( next );

		return wrap;
	}

	function renderDetailsStep() {
		var wrap = el( 'div', { class: 'bookflow-step-panel' } );
		wrap.appendChild( el( 'h3', { text: cfg.i18n.yourDetails } ) );

		var form = el( 'form', {} );

		var nameInput = el( 'input', { type: 'text', name: 'customer_name', required: 'required', placeholder: 'Full name' } );
		var emailInput = el( 'input', { type: 'email', name: 'customer_email', required: 'required', placeholder: 'Email' } );
		var phoneInput = el( 'input', { type: 'tel', name: 'customer_phone', required: 'required', placeholder: 'Phone' } );
		var eventDateInput = el( 'input', { type: 'date', name: 'event_date', placeholder: 'Wedding/event date (optional)' } );
		var honeypot = el( 'input', { type: 'text', name: 'website', tabindex: '-1', autocomplete: 'off', style: 'position:absolute;left:-9999px;' } );

		[
			[ 'Full name', nameInput ],
			[ 'Email', emailInput ],
			[ 'Phone', phoneInput ],
			[ 'Wedding/event date (optional)', eventDateInput ],
		].forEach( function ( pair ) {
			form.appendChild( el( 'label', { text: pair[ 0 ] } ) );
			form.appendChild( pair[ 1 ] );
		} );
		form.appendChild( honeypot );

		var errorBox = el( 'p', { class: 'bookflow-error', style: 'display:none;' } );
		form.appendChild( errorBox );

		var submit = el( 'button', { type: 'submit', class: 'bookflow-btn bookflow-btn-primary', text: 'Confirm booking' } );
		form.appendChild( submit );

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			errorBox.style.display = 'none';
			submit.disabled = true;
			submit.textContent = 'Booking…';

			apiPost( '/appointments', {
				customer_name: nameInput.value,
				customer_email: emailInput.value,
				customer_phone: phoneInput.value,
				event_date: eventDateInput.value || null,
				date: state.date,
				time: state.time,
				item_ids: state.selectedItemIds,
				website: honeypot.value,
			} )
				.then( function () {
					state.step = 4;
					render();
				} )
				.catch( function ( err ) {
					errorBox.textContent = err.message || cfg.i18n.genericError;
					errorBox.style.display = 'block';
					submit.disabled = false;
					submit.textContent = 'Confirm booking';
				} );
		} );

		wrap.appendChild( form );

		var back = el( 'button', { type: 'button', class: 'bookflow-btn', text: '← Back' } );
		back.addEventListener( 'click', function () {
			state.step = 2;
			render();
		} );
		wrap.insertBefore( back, form );

		return wrap;
	}

	function renderConfirmedStep() {
		var wrap = el( 'div', { class: 'bookflow-step-panel bookflow-confirmed' } );
		wrap.appendChild( el( 'h3', { text: cfg.i18n.confirmed } ) );
		wrap.appendChild( el( 'p', { text: 'A confirmation with a calendar invite has been sent to your email.' } ) );
		return wrap;
	}

	function todayIso() {
		var d = new Date();
		return d.toISOString().slice( 0, 10 );
	}

	render();
} )();
