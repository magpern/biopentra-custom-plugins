/**
 * M10 Contact page — product-request prefill + Open Chat seam delegation.
 */
( function () {
	'use strict';

	function prefillProductRequest() {
		var params = new URLSearchParams( window.location.search );
		if ( params.get( 'prefill' ) !== 'product_request' ) {
			return;
		}

		var root = document.getElementById( 'contact-form' );
		if ( ! root ) {
			return;
		}

		var select = root.querySelector( 'select[name="reason_for_contact"]' );
		if ( ! select ) {
			return;
		}

		select.value = 'product_request';
		select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	}

	function openChat( event ) {
		if ( event ) {
			event.preventDefault();
		}

		if ( window.UniversalTelegramChat && typeof window.UniversalTelegramChat.open === 'function' ) {
			window.UniversalTelegramChat.open();
			return;
		}

		document.dispatchEvent( new CustomEvent( 'universal-telegram:open-chat' ) );
	}

	function bindOpenChatButtons() {
		var buttons = document.querySelectorAll( '[data-bp-m10-open-chat]' );
		buttons.forEach( function ( button ) {
			button.addEventListener( 'click', openChat );
		} );
	}

	function init() {
		prefillProductRequest();
		bindOpenChatButtons();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
