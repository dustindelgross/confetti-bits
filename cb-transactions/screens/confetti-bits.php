<?php 
function cb_screen_view() {
	
	if ( cb_is_confetti_bits_component() ) {
		
	bp_core_load_template( 'members/single/home' );
		
    }
	
}