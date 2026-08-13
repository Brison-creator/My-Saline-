/**
 * MySaline admin behaviour — media picker is available via wp.media if a future
 * field needs it. Currently keeps the ad editor tidy by toggling the image hint
 * when ad code is present.
 */
( function ( $ ) {
	'use strict';

	$( function () {
		var $code = $( '#_ms_ad_code' );
		if ( $code.length ) {
			var toggleHint = function () {
				var hasCode = $.trim( $code.val() ).length > 0;
				$( '.mysaline-field--url' ).css( 'opacity', hasCode ? 0.5 : 1 );
			};
			$code.on( 'input', toggleHint );
			toggleHint();
		}
	} );
}( jQuery ) );
