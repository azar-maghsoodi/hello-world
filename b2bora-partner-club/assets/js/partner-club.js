/**
 * B2Bora Partner Club - front-end dashboard behaviour.
 * Vanilla JS only, no framework, minimal footprint.
 */
( function () {
	'use strict';

	function onReady( fn ) {
		if ( document.readyState !== 'loading' ) {
			fn();
		} else {
			document.addEventListener( 'DOMContentLoaded', fn );
		}
	}

	function showNotice( container, message, isError ) {
		var notice = container.querySelector( '.b2bora-pc-notice' );
		if ( ! notice ) {
			return;
		}

		notice.textContent = message;
		notice.hidden = false;
		notice.classList.toggle( 'b2bora-pc-notice-error', !! isError );
	}

	function handleRedeem( event ) {
		var button = event.currentTarget;
		var dashboard = button.closest( '.b2bora-pc-dashboard' );

		if ( ! window.b2boraPartnerClub || ! dashboard ) {
			return;
		}

		var settings = window.b2boraPartnerClub;

		if ( ! window.confirm( settings.i18n.confirmRedeem ) ) {
			return;
		}

		var rewardId = button.getAttribute( 'data-reward-id' );
		button.disabled = true;

		var body = new URLSearchParams();
		body.set( 'action', 'b2bora_pc_redeem_reward' );
		body.set( 'nonce', settings.nonce );
		body.set( 'reward_id', rewardId );

		fetch( settings.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString(),
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( json ) {
				if ( json && json.success ) {
					showNotice( dashboard, json.data.message, false );
					window.setTimeout( function () {
						window.location.reload();
					}, 1200 );
				} else {
					var message = json && json.data && json.data.message ? json.data.message : settings.i18n.error;
					showNotice( dashboard, message, true );
					button.disabled = false;
				}
			} )
			.catch( function () {
				showNotice( dashboard, settings.i18n.error, true );
				button.disabled = false;
			} );
	}

	onReady( function () {
		var buttons = document.querySelectorAll( '.b2bora-pc-redeem-button:not(:disabled)' );
		buttons.forEach( function ( button ) {
			button.addEventListener( 'click', handleRedeem );
		} );
	} );
} )();
