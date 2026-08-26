/* global jQuery, TruckScreenAdmin, qrcode */
( function ( $ ) {
	'use strict';

	function ajaxPost( action, data ) {
		return $.post(
			TruckScreenAdmin.ajaxUrl,
			$.extend( { action: action, nonce: TruckScreenAdmin.nonce }, data )
		);
	}

	function initSoldOutToggle() {
		$( document ).on( 'click', '.truckscreen-toggle-sold-out', function () {
			var $button = $( this );
			var itemId = $button.data( 'item-id' );
			var soldOut = $button.data( 'sold-out' ) === 1 || $button.data( 'sold-out' ) === '1';
			var nextState = ! soldOut;

			$button.prop( 'disabled', true );

			ajaxPost( 'truckscreen_toggle_sold_out', { item_id: itemId, sold_out: nextState ? 1 : 0 } )
				.done( function ( response ) {
					if ( ! response || ! response.success ) {
						return;
					}
					var $item = $button.closest( '.truckscreen-item' );
					$item.toggleClass( 'is-sold-out', nextState );
					$button.data( 'sold-out', nextState ? '1' : '0' );
					$button.text( nextState ? 'Mark available' : 'Sold out' );
					$item.find( '.truckscreen-badge:not(.truckscreen-badge--draft)' ).remove();
					if ( nextState ) {
						$item.find( '.truckscreen-item-name' ).append( '<span class="truckscreen-badge">SOLD OUT</span>' );
					}
				} )
				.always( function () {
					$button.prop( 'disabled', false );
				} );
		} );
	}

	function initDeleteConfirm() {
		$( document ).on( 'click', '.truckscreen-delete-link', function ( event ) {
			if ( ! window.confirm( 'Move this item to the trash?' ) ) {
				event.preventDefault();
			}
		} );
	}

	function initSortable() {
		var $categories = $( '#truckscreen-categories' );
		if ( $categories.length ) {
			$categories.sortable( {
				handle: '> .truckscreen-category-header .truckscreen-drag-handle',
				axis: 'y',
				update: function () {
					var termIds = $categories.children( '.truckscreen-category' )
						.map( function () {
							return $( this ).data( 'term-id' );
						} )
						.get();
					ajaxPost( 'truckscreen_reorder_categories', { term_ids: termIds } );
				},
			} );
		}

		$( '.truckscreen-items' ).each( function () {
			var $list = $( this );
			$list.sortable( {
				handle: '.truckscreen-drag-handle',
				items: '> .truckscreen-item',
				update: function () {
					var itemIds = $list.children( '.truckscreen-item' )
						.map( function () {
							return $( this ).data( 'item-id' );
						} )
						.get();
					ajaxPost( 'truckscreen_reorder_items', { item_ids: itemIds } );
				},
			} );
		} );
	}

	function initAddCategory() {
		$( '#truckscreen-add-category' ).on( 'click', function () {
			var $button = $( this );
			var $input = $( '#truckscreen-new-category-name' );
			var name = $.trim( $input.val() );
			if ( ! name ) {
				return;
			}

			$button.prop( 'disabled', true );
			ajaxPost( 'truckscreen_add_category', { name: name } )
				.done( function ( response ) {
					if ( response && response.success ) {
						window.location.reload();
					} else {
						window.alert( ( response && response.data && response.data.message ) || 'Could not add category.' );
					}
				} )
				.always( function () {
					$button.prop( 'disabled', false );
				} );
		} );
	}

	function initCopyLink() {
		$( '#truckscreen-copy-link' ).on( 'click', function () {
			var $button = $( this );
			var url = $button.data( 'url' );
			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				navigator.clipboard.writeText( url ).then( function () {
					var original = $button.text();
					$button.text( 'Copied!' );
					setTimeout( function () {
						$button.text( original );
					}, 1500 );
				} );
			}
		} );
	}

	function initLogoUploader() {
		var frame;
		$( '#truckscreen-logo-select' ).on( 'click', function ( event ) {
			event.preventDefault();
			if ( frame ) {
				frame.open();
				return;
			}
			frame = wp.media( { title: 'Choose a logo', multiple: false, library: { type: 'image' } } );
			frame.on( 'select', function () {
				var attachment = frame.state().get( 'selection' ).first().toJSON();
				$( '#truckscreen-logo-id' ).val( attachment.id );
				$( '#truckscreen-logo-preview' ).attr( 'src', attachment.url ).show();
				$( '#truckscreen-logo-remove' ).show();
			} );
			frame.open();
		} );

		$( '#truckscreen-logo-remove' ).on( 'click', function () {
			$( '#truckscreen-logo-id' ).val( '0' );
			$( '#truckscreen-logo-preview' ).hide();
			$( this ).hide();
		} );
	}

	function initQrCode() {
		var $container = $( '#truckscreen-qr' );
		if ( ! $container.length || typeof qrcode === 'undefined' ) {
			return;
		}
		var url = $container.data( 'url' );
		var qr = qrcode( 0, 'M' ); // type 0 = auto-detect smallest size, M = medium error correction.
		qr.addData( url );
		qr.make();
		$container.html( qr.createSvgTag( { cellSize: 5, margin: 4 } ) );
	}

	$( function () {
		initSoldOutToggle();
		initDeleteConfirm();
		initSortable();
		initAddCategory();
		initCopyLink();
		initLogoUploader();
		initQrCode();
	} );
} )( jQuery );
