jQuery(document).ready(function(){
	jQuery( "#kwayyhs-sortable" ).sortable({
		placeholder: "kwayyhs-ui-state-highlight",

		update: function(event, ui) {
				var fruitOrder = jQuery("#kwayyhs-sortable").sortable('toArray').toString();
				jQuery('#kwayyhs-sortorder').val(fruitOrder);
				//$.get('update-sort.cfm', {fruitOrder:fruitOrder});
			}
	});
	
	jQuery('.kwayyhs_changename').click(function(){
		jQuery(this).next().fadeIn();
		return false;
	});
	
	jQuery('a.kwayy-save-newname').click(function(){
		if( jQuery(this).prev().val() == '' ){
			originalname = jQuery(this).parent().parent().next().next().html();
			jQuery(this).prev().val(originalname);
			jQuery(this).parent().prev().prev().html(originalname);
		} else {
			jQuery(this).parent().prev().prev().html( jQuery(this).prev().val() );
		}
		jQuery(this).parent().fadeOut();
		return false;
	});
	
	jQuery('a.kwayy-cancel-newname').click(function(){
		jQuery(this).prev().prev().val( jQuery(this).parent().prev().prev().html() );
		jQuery(this).parent().fadeOut();
		return false;
	});
	
	
	
	
	//jQuery( "#kwayyhs-sortable" ).disableSelection();
});
