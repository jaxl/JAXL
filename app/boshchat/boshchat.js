jQuery(function($) {
	$(document).ready(function() {
		$('#button input').click(function() {
			if($(this).val() == 'Connect') {
				$(this).val('Connecting...');
				obj = new Object;
				obj['jaxl'] = 'connect';
				obj['user'] = $('#uname input').val();
				obj['pass'] = $('#passwd input').val();
				jaxl.sendPayload(obj);
			}
			else if($(this).val() == 'Disconnect') {
				obj = new Object;
				obj['jaxl'] = 'disconnect';
				jaxl.sendPayload(obj);
			}
		});
		
		$('#write').focus(function() {
			$(this).val('');
			$(this).css('color', '#444');
		});
		
		$('#write').blur(function() {
			if($(this).val() == '') $(this).val('Type your message');
			$(this).css('color', '#AAA');
		});
		
		$('#write').keydown(function(e) {
			if(e.keyCode == 13 && jaxl.connected) {
				message = $.trim($(this).val());
				if(message.length == 0) return false;
				$(this).val('');
				obj = new Object;
				obj['jaxl'] = 'message';
				obj['message'] = message;
				jaxl.sendPayload(obj);
			}
		});
	});
});
