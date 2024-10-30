jQuery( document ).ready( function( $ ){
	var minControl = $( '#_adk_qty_min' ),
		maxControl = $( '#_adk_qty_max' ),
		switchControl = $( '#_adk_qty_use_cat' );

	function useCategoryValues() {
		if( switchControl.is( ':checked' ) ) {
			if( ! minControl.val() && minControl.attr( 'data-cat-val' ) ) {
				if( typeof minControl[ 0 ].oldPlaceholder == 'undefined' ) {
					minControl[ 0 ].oldPlaceholder = minControl.attr( 'placeholder' );
				}
				minControl.attr( 'placeholder' , minControl.attr( 'data-cat-val' ) );
			}
			if( ! maxControl.val() && maxControl.attr( 'data-cat-val' ) ) {
				if( typeof maxControl[ 0 ].oldPlaceholder == 'undefined' ) {
					maxControl[ 0 ].oldPlaceholder = maxControl.attr( 'placeholder' );
				}
				maxControl.attr( 'placeholder' , maxControl.attr( 'data-cat-val' ) );
			}
		}
		else {
			if( typeof minControl[ 0 ].oldPlaceholder != 'undefined' ) {
				minControl.attr( 'placeholder' , minControl[ 0 ].oldPlaceholder );
			}
			if( typeof maxControl[ 0 ].placeholder != 'undefined' ) {
				maxControl.attr( 'placeholder' , maxControl[ 0 ].oldPlaceholder );
			}
		}
	}

	if( minControl.length && maxControl.length && switchControl.length ) {

		switchControl.on( 'change' , useCategoryValues );

		minControl.on( 'change' , useCategoryValues );

		maxControl.on( 'change' , useCategoryValues );

		useCategoryValues();
	}

} );