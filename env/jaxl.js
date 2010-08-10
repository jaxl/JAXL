var jaxl = {
	jid: false,
	polling: 0,
	connected: false,
	disconnecting: false,
	pollUrl: 'http://dev.xmpp/jaxl.php',
	preparePayload: function(obj) {
		var json = "{";
		for(key in obj) {
			if(json == "{") json += "'"+key+"':'"+obj[key]+"'";
			else json += ", '"+key+"':'"+obj[key]+"'";
		}
		json += "}";
		return eval("("+json+")");
	},
	sendPayload: function(obj) {
		if((jaxl.polling != 0 || !jaxl.connected || jaxl.disconnecting) && obj['jaxl'] == 'ping') return false;
		$.ajax({
			type: 'POST',
			url: jaxl.pollUrl,
			dataType: 'json',
			data: jaxl.preparePayload(obj),
			beforeSend: function() {
				if(obj['jaxl'] == 'disconnect') {
					jaxl.disconnecting = true;
				}
				jaxl.polling++;
			},
			success: function(payload) {
				jaxl.polling--;
				jaxl.handlePayload(payload);
			},
			complete: function() {},
			error: function() { jaxl.polling--; }
		});
	},
	handlePayload: function(payload) {
		if(payload.jaxl == 'connected') {
			jaxl.connected = true;
			jaxl.jid = payload.jid;
			$('#uname').css('display', 'none');
			$('#passwd').css('display', 'none');
			$('#button input').val('Disconnect');
			$('#read').css('display', 'block');
			$('#write').css('display', 'block');
			obj = new Object;
			obj['jaxl'] = 'ping';
			jaxl.sendPayload(obj);
		}
		else if(payload.jaxl == 'disconnected') {
			jaxl.connected = false;
			jaxl.disconnecting = false;
			$('#read').css('display', 'none');
			$('#write').css('display', 'none');
			$('#uname').css('display', 'block');
			$('#passwd').css('display', 'block');
			$('#button input').val('Connect');
			console.log('disconnected');
		}
		else if(payload.jaxl == 'pinged') {
			obj = new Object;
			obj['jaxl'] = 'ping';
			jaxl.sendPayload(obj);
		}
	}
};
