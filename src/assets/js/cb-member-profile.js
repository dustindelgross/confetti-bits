jQuery(document).ready( function($){
	$(document).ajaxComplete( function(){
		let p = $('.confetti-captain').parents('.activity-avatar');
		let c = p.children('.confetti-captain-badge-small');
		let l = c.length;
		if ( l < 1 ) {
			p.append('<div class="confetti-captain-badge-small"><div>');
		}
	});
});
