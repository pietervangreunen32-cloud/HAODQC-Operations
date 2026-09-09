/**
 * BookFlow booking wizard. Group/party companions (each with their own
 * name and item picks, all under the lead customer's time slot) and a
 * waitlist offer when a chosen date has no open slots. When the shop
 * requires a deposit, the confirmation step links to WooCommerce's own
 * payment page for it. The shortlist "hand your picks to a friend" flow
 * lives in shortlist.js, not here.
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
		itemsLoaded: false,
		selectedItemIds: [],
		companions: [], // [{ name: '', itemIds: [] }]
		date: '',
		time: '',
		slots: [],
		slotsLoaded: false,
		waitlistOpen: false,
		waitlistJoined: false,
		depositUrl: '',
	};

	var uidCounter = 0;
	function uid( prefix ) {
		uidCounter += 1;
		return prefix + '-' + uidCounter;
	}

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
					throw new Error( data.message || cfg.i18n.genericError );
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

	var focusHeadingOnNextRender = true; // Also true for the very first paint.

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

		// Only move focus when the step itself changed — re-renders from
		// picking an item, opening the waitlist form, etc. stay on the
		// same step and must never steal focus back mid-interaction.
		if ( focusHeadingOnNextRender ) {
			focusHeadingOnNextRender = false;
			var heading = document.getElementById( 'bookflow-step-heading' );
			if ( heading ) {
				heading.focus();
			}
		}
	}

	/**
	 * The only way state.step should change — keeps the "move focus to
	 * the new step's heading" behavior (useful for screen-reader and
	 * keyboard users, since the whole panel is replaced on every step
	 * change) from having to be remembered at every call site.
	 */
	function goToStep( stepNumber ) {
		state.step = stepNumber;
		focusHeadingOnNextRender = true;
		render();
	}

	function stepHeading( text ) {
		return el( 'h3', { text: text, tabindex: '-1', id: 'bookflow-step-heading' } );
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

	/**
	 * Renders one photo grid of catalog items with toggleable selection —
	 * used both for the lead customer's picks and for each companion's.
	 */
	function renderCatalogGrid( selectedIds, onToggle ) {
		var grid = el( 'div', { class: 'bookflow-catalog-grid' } );
		state.items.forEach( function ( item ) {
			var selected = selectedIds.indexOf( item.id ) !== -1;
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
				onToggle( item.id );
			} );
			grid.appendChild( card );
		} );
		return grid;
	}

	function renderCatalogStep() {
		var wrap = el( 'div', { class: 'bookflow-step-panel' } );
		wrap.appendChild( stepHeading( cfg.i18n.chooseItems ) );

		if ( ! state.itemsLoaded ) {
			wrap.appendChild( el( 'p', { class: 'bookflow-notice', text: 'Loading…' } ) );
			apiGet( '/items' ).then( function ( items ) {
				state.items = items;
				state.itemsLoaded = true;
				render();
			} ).catch( function () {
				state.itemsLoaded = true; // Stop retrying forever; show the same "nothing to book" state a genuinely empty catalog would.
				render();
			} );
			return wrap;
		}

		if ( ! state.items.length ) {
			wrap.appendChild( el( 'p', { class: 'bookflow-notice', text: cfg.i18n.noItems } ) );
			return wrap;
		}

		wrap.appendChild(
			renderCatalogGrid( state.selectedItemIds, function ( itemId ) {
				var idx = state.selectedItemIds.indexOf( itemId );
				if ( idx === -1 ) {
					state.selectedItemIds.push( itemId );
				} else {
					state.selectedItemIds.splice( idx, 1 );
				}
				render();
			} )
		);

		var groupBookingsEnabled = !! ( cfg.features && cfg.features.groupBookings );

		if ( groupBookingsEnabled ) {
			var companionsWrap = el( 'div', { class: 'bookflow-companions' } );
			state.companions.forEach( function ( companion, index ) {
				var block = el( 'div', { class: 'bookflow-companion-block' } );

				var header = el( 'div', { class: 'bookflow-companion-header' } );
				var nameInput = el( 'input', {
					type: 'text',
					class: 'bookflow-companion-name',
					placeholder: cfg.i18n.companionName,
					'aria-label': cfg.i18n.companionName,
					value: companion.name,
				} );
				nameInput.addEventListener( 'input', function ( e ) {
					companion.name = e.target.value;
				} );
				header.appendChild( nameInput );

				var removeBtn = el( 'button', { type: 'button', class: 'bookflow-btn bookflow-btn-small', text: cfg.i18n.removeCompanion } );
				removeBtn.addEventListener( 'click', function () {
					state.companions.splice( index, 1 );
					render();
				} );
				header.appendChild( removeBtn );

				block.appendChild( header );
				block.appendChild(
					renderCatalogGrid( companion.itemIds, function ( itemId ) {
						var idx = companion.itemIds.indexOf( itemId );
						if ( idx === -1 ) {
							companion.itemIds.push( itemId );
						} else {
							companion.itemIds.splice( idx, 1 );
						}
						render();
					} )
				);

				companionsWrap.appendChild( block );
			} );
			wrap.appendChild( companionsWrap );

			var addCompanionBtn = el( 'button', { type: 'button', class: 'bookflow-btn', text: cfg.i18n.addCompanion } );
			addCompanionBtn.addEventListener( 'click', function () {
				state.companions.push( { name: '', itemIds: [] } );
				render();
			} );
			wrap.appendChild( addCompanionBtn );
		}

		var next = el( 'button', { type: 'button', class: 'bookflow-btn bookflow-btn-primary bookflow-btn-block', text: 'Continue' } );
		next.disabled = state.selectedItemIds.length === 0;
		next.addEventListener( 'click', function () {
			goToStep( 2 );
		} );
		wrap.appendChild( next );

		return wrap;
	}

	function renderDateTimeStep() {
		var wrap = el( 'div', { class: 'bookflow-step-panel' } );
		wrap.appendChild( stepHeading( cfg.i18n.chooseDateTime ) );

		var dateInputId = uid( 'bookflow-date' );
		var dateAttrs = { type: 'date', id: dateInputId, value: state.date, min: todayIso() };
		if ( cfg.bookingHorizon ) {
			var maxDate = new Date();
			maxDate.setDate( maxDate.getDate() + parseInt( cfg.bookingHorizon, 10 ) );
			dateAttrs.max = maxDate.toISOString().slice( 0, 10 );
		}
		var dateInput = el( 'input', dateAttrs );
		dateInput.addEventListener( 'change', function ( e ) {
			state.date = e.target.value;
			state.time = '';
			state.slots = [];
			state.slotsLoaded = false;
			state.waitlistOpen = false;
			state.waitlistJoined = false;
			render();
		} );
		wrap.appendChild( el( 'label', { text: 'Date', for: dateInputId } ) );
		wrap.appendChild( dateInput );

		if ( state.date ) {
			if ( ! state.slotsLoaded ) {
				wrap.appendChild( el( 'p', { class: 'bookflow-notice', text: 'Loading…' } ) );
				apiGet( '/availability?date=' + encodeURIComponent( state.date ) ).then( function ( slots ) {
					state.slots = slots;
					state.slotsLoaded = true;
					render();
				} ).catch( function () {
					state.slots = [];
					state.slotsLoaded = true;
					render();
				} );
			} else if ( ! state.slots.length ) {
				// An empty list (as opposed to a list of all-unavailable
				// slots) means the shop simply isn't open that day —
				// different from "fully booked," and not something a
				// waitlist offer makes sense for.
				wrap.appendChild( el( 'p', { class: 'bookflow-notice', text: cfg.i18n.shopClosed } ) );
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
					if ( cfg.features && cfg.features.waitlist ) {
						wrap.appendChild( renderWaitlistOffer() );
					}
				}
			}
		}

		var back = el( 'button', { type: 'button', class: 'bookflow-btn', text: '← Back' } );
		back.addEventListener( 'click', function () {
			goToStep( 1 );
		} );
		wrap.appendChild( back );

		var next = el( 'button', { type: 'button', class: 'bookflow-btn bookflow-btn-primary', text: 'Continue' } );
		next.disabled = ! state.time;
		next.addEventListener( 'click', function () {
			goToStep( 3 );
		} );
		wrap.appendChild( next );

		return wrap;
	}

	function renderWaitlistOffer() {
		var wrap = el( 'div', { class: 'bookflow-waitlist' } );

		if ( state.waitlistJoined ) {
			wrap.appendChild( el( 'p', { class: 'bookflow-notice', text: cfg.i18n.waitlistJoined } ) );
			return wrap;
		}

		if ( ! state.waitlistOpen ) {
			var openBtn = el( 'button', { type: 'button', class: 'bookflow-btn', text: cfg.i18n.joinWaitlist } );
			openBtn.addEventListener( 'click', function () {
				state.waitlistOpen = true;
				render();
			} );
			wrap.appendChild( openBtn );
			return wrap;
		}

		var form = el( 'form', { class: 'bookflow-waitlist-form' } );
		var nameInput = el( 'input', { type: 'text', placeholder: 'Full name', 'aria-label': 'Full name', required: 'required' } );
		var emailInput = el( 'input', { type: 'email', placeholder: 'Email', 'aria-label': 'Email', required: 'required' } );
		var phoneInput = el( 'input', { type: 'tel', placeholder: 'Phone', 'aria-label': 'Phone' } );
		var errorBox = el( 'p', { class: 'bookflow-error', style: 'display:none;' } );

		form.appendChild( el( 'h4', { text: cfg.i18n.waitlistTitle } ) );
		form.appendChild( nameInput );
		form.appendChild( emailInput );
		form.appendChild( phoneInput );
		form.appendChild( errorBox );

		var submit = el( 'button', { type: 'submit', class: 'bookflow-btn bookflow-btn-primary', text: cfg.i18n.joinWaitlist } );
		form.appendChild( submit );

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			errorBox.style.display = 'none';
			submit.disabled = true;

			apiPost( '/waitlist', {
				customer_name: nameInput.value,
				customer_email: emailInput.value,
				customer_phone: phoneInput.value,
				date: state.date,
			} )
				.then( function () {
					state.waitlistJoined = true;
					render();
				} )
				.catch( function ( err ) {
					errorBox.textContent = err.message || cfg.i18n.genericError;
					errorBox.style.display = 'block';
					submit.disabled = false;
				} );
		} );

		wrap.appendChild( form );
		return wrap;
	}

	function renderDetailsStep() {
		var wrap = el( 'div', { class: 'bookflow-step-panel' } );
		wrap.appendChild( stepHeading( cfg.i18n.yourDetails ) );

		var form = el( 'form', {} );

		var nameId = uid( 'bookflow-name' );
		var emailId = uid( 'bookflow-email' );
		var phoneId = uid( 'bookflow-phone' );
		var eventDateId = uid( 'bookflow-event-date' );

		var nameInput = el( 'input', { type: 'text', id: nameId, name: 'customer_name', required: 'required', placeholder: 'Full name' } );
		var emailInput = el( 'input', { type: 'email', id: emailId, name: 'customer_email', required: 'required', placeholder: 'Email' } );
		var phoneInput = el( 'input', { type: 'tel', id: phoneId, name: 'customer_phone', required: 'required', placeholder: 'Phone' } );
		var eventDateInput = el( 'input', { type: 'date', id: eventDateId, name: 'event_date', placeholder: 'Wedding/event date (optional)' } );
		var honeypot = el( 'input', { type: 'text', name: 'website', tabindex: '-1', autocomplete: 'off', style: 'position:absolute;left:-9999px;' } );

		[
			[ 'Full name', nameId, nameInput ],
			[ 'Email', emailId, emailInput ],
			[ 'Phone', phoneId, phoneInput ],
			[ 'Wedding/event date (optional)', eventDateId, eventDateInput ],
		].forEach( function ( group ) {
			form.appendChild( el( 'label', { text: group[ 0 ], for: group[ 1 ] } ) );
			form.appendChild( group[ 2 ] );
		} );
		form.appendChild( honeypot );

		if ( state.companions.length ) {
			var namedCompanions = state.companions.filter( function ( c ) { return c.name; } );
			if ( namedCompanions.length ) {
				var summary = el( 'p', { class: 'bookflow-notice' } );
				summary.textContent = 'Joining you: ' + namedCompanions.map( function ( c ) { return c.name; } ).join( ', ' );
				form.appendChild( summary );
			}
		}

		var errorBox = el( 'p', { class: 'bookflow-error', style: 'display:none;' } );
		form.appendChild( errorBox );

		var submit = el( 'button', { type: 'submit', class: 'bookflow-btn bookflow-btn-primary', text: 'Confirm booking' } );
		form.appendChild( submit );

		function showError( message ) {
			errorBox.textContent = message;
			errorBox.style.display = 'block';
			submit.disabled = false;
			submit.textContent = 'Confirm booking';
		}

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			errorBox.style.display = 'none';

			// A companion with items picked but no name would otherwise be
			// silently dropped by the server (it only accepts named
			// companions) — catch that here rather than losing their
			// selections without any explanation.
			var unnamedWithItems = state.companions.some( function ( c ) {
				return ! c.name && c.itemIds.length > 0;
			} );
			if ( unnamedWithItems ) {
				showError( cfg.i18n.companionNeedsName );
				return;
			}

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
				companions: state.companions
					.filter( function ( c ) {
						return c.name;
					} )
					.map( function ( c ) {
						return { name: c.name, item_ids: c.itemIds };
					} ),
				website: honeypot.value,
			} )
				.then( function ( data ) {
					state.depositUrl = data.deposit_url || '';
					goToStep( 4 );
				} )
				.catch( function ( err ) {
					showError( err.message || cfg.i18n.genericError );
				} );
		} );

		wrap.appendChild( form );

		var back = el( 'button', { type: 'button', class: 'bookflow-btn', text: '← Back' } );
		back.addEventListener( 'click', function () {
			goToStep( 2 );
		} );
		wrap.insertBefore( back, form );

		return wrap;
	}

	function renderConfirmedStep() {
		var wrap = el( 'div', { class: 'bookflow-step-panel bookflow-confirmed' } );
		wrap.appendChild( stepHeading( cfg.i18n.confirmed ) );
		wrap.appendChild( el( 'p', { text: 'A confirmation with a calendar invite has been sent to your email.' } ) );

		if ( state.depositUrl ) {
			wrap.appendChild( el( 'p', { text: 'A deposit is required to hold this booking.' } ) );
			var payLink = el( 'a', { href: state.depositUrl, class: 'bookflow-btn bookflow-btn-primary', text: 'Pay your deposit now' } );
			wrap.appendChild( payLink );
		}

		return wrap;
	}

	function todayIso() {
		var d = new Date();
		return d.toISOString().slice( 0, 10 );
	}

	render();
} )();
