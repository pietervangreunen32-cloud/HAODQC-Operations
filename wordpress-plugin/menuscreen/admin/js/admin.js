/* global jQuery, MenuScreenAdmin, qrcode */
( function ( $ ) {
	'use strict';

	function ajaxPost( action, data ) {
		return $.post(
			MenuScreenAdmin.ajaxUrl,
			$.extend( { action: action, nonce: MenuScreenAdmin.nonce }, data )
		);
	}

	function initSoldOutToggle() {
		$( document ).on( 'click', '.menuscreen-toggle-sold-out', function () {
			var $button = $( this );
			var itemId = $button.data( 'item-id' );
			var soldOut = $button.data( 'sold-out' ) === 1 || $button.data( 'sold-out' ) === '1';
			var nextState = ! soldOut;

			$button.prop( 'disabled', true );

			ajaxPost( 'menuscreen_toggle_sold_out', { item_id: itemId, sold_out: nextState ? 1 : 0 } )
				.done( function ( response ) {
					if ( ! response || ! response.success ) {
						return;
					}
					var $item = $button.closest( '.menuscreen-item' );
					$item.toggleClass( 'is-sold-out', nextState );
					$button.data( 'sold-out', nextState ? '1' : '0' );
					$button.text( nextState ? 'Mark available' : 'Sold out' );
					$item.find( '.menuscreen-badge:not(.menuscreen-badge--draft)' ).remove();
					if ( nextState ) {
						$item.find( '.menuscreen-item-name' ).append( '<span class="menuscreen-badge">SOLD OUT</span>' );
					}
				} )
				.always( function () {
					$button.prop( 'disabled', false );
				} );
		} );
	}

	function initDeleteConfirm() {
		$( document ).on( 'click', '.menuscreen-delete-link', function ( event ) {
			if ( ! window.confirm( 'Move this item to the trash?' ) ) {
				event.preventDefault();
			}
		} );
	}

	function initSortable() {
		var $categories = $( '#menuscreen-categories' );
		if ( $categories.length ) {
			$categories.sortable( {
				handle: '> .menuscreen-category-header .menuscreen-drag-handle',
				axis: 'y',
				update: function () {
					var termIds = $categories.children( '.menuscreen-category' )
						.map( function () {
							return $( this ).data( 'term-id' );
						} )
						.get();
					ajaxPost( 'menuscreen_reorder_categories', { term_ids: termIds } );
				},
			} );
		}

		$( '.menuscreen-items' ).each( function () {
			var $list = $( this );
			$list.sortable( {
				handle: '.menuscreen-drag-handle',
				items: '> .menuscreen-item',
				update: function () {
					var itemIds = $list.children( '.menuscreen-item' )
						.map( function () {
							return $( this ).data( 'item-id' );
						} )
						.get();
					ajaxPost( 'menuscreen_reorder_items', { item_ids: itemIds } );
				},
			} );
		} );
	}

	function initAddCategory() {
		$( '#menuscreen-add-category' ).on( 'click', function () {
			var $button = $( this );
			var $input = $( '#menuscreen-new-category-name' );
			var name = $.trim( $input.val() );
			if ( ! name ) {
				return;
			}

			$button.prop( 'disabled', true );
			ajaxPost( 'menuscreen_add_category', { name: name } )
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
		$( '#menuscreen-copy-link' ).on( 'click', function () {
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
		$( '#menuscreen-logo-select' ).on( 'click', function ( event ) {
			event.preventDefault();
			if ( frame ) {
				frame.open();
				return;
			}
			frame = wp.media( { title: 'Choose a logo', multiple: false, library: { type: 'image' } } );
			frame.on( 'select', function () {
				var attachment = frame.state().get( 'selection' ).first().toJSON();
				$( '#menuscreen-logo-id' ).val( attachment.id );
				$( '#menuscreen-logo-preview' ).attr( 'src', attachment.url ).show();
				$( '#menuscreen-logo-remove' ).show();
			} );
			frame.open();
		} );

		$( '#menuscreen-logo-remove' ).on( 'click', function () {
			$( '#menuscreen-logo-id' ).val( '0' );
			$( '#menuscreen-logo-preview' ).hide();
			$( this ).hide();
		} );
	}

	function initQrCode() {
		var $container = $( '#menuscreen-qr' );
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
