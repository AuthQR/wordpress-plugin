jQuery(function($){
	
	var form = $("#loginform");
	form.wrapInner("<div class='authqr-form-part'></div>");
	$(".authqr-main").appendTo("#loginform");
	form.append("<div class='clear'></div>");

	$.authQR({
		image: $("#authqr"),
		code: $("#authqr_code"),
		callback: function(code){
			form.trigger("submit");
		}
	});

});