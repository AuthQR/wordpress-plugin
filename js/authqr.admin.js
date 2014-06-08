jQuery(function($){

	$.authQR({
		image: $("#authqr_img"),
		code: $("#authqr_code"),
		callback: function(code){
			$("#authqr_status").html( $("#authqr_status").data('status-ok') );
		}
	});

});