/**
 * Opens WordPress's own media library for choosing (or uploading) the
 * welcome screen's background photo — BookFlow → Welcome Screen only.
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var chooseBtn = document.getElementById( 'bookflow-choose-bg' );
		var removeBtn = document.getElementById( 'bookflow-remove-bg' );
		var input = document.getElementById( 'bookflow-bg-image-id' );
		var preview = document.getElementById( 'bookflow-bg-preview' );

		if ( ! chooseBtn || ! window.wp || ! window.wp.media ) {
			return;
		}

		var frame = null;

		chooseBtn.addEventListener( 'click', function ( e ) {
			e.preventDefault();

			if ( frame ) {
				frame.open();
				return;
			}

			frame = wp.media( {
				title: chooseBtn.getAttribute( 'data-title' ) || 'Choose a background photo',
				button: { text: chooseBtn.getAttribute( 'data-button-text' ) || 'Use this photo' },
				library: { type: 'image' },
				multiple: false,
			} );

			frame.on( 'select', function () {
				var attachment = frame.state().get( 'selection' ).first().toJSON();
				input.value = attachment.id;
				if ( preview ) {
					preview.src = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
					preview.hidden = false;
				}
				if ( removeBtn ) {
					removeBtn.hidden = false;
				}
			} );

			frame.open();
		} );

		if ( removeBtn ) {
			removeBtn.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				input.value = '0';
				if ( preview ) {
					preview.hidden = true;
					preview.removeAttribute( 'src' );
				}
				removeBtn.hidden = true;
			} );
		}
	} );
} )();
