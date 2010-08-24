function call_user_func(cb) {
    var func;
    if(typeof cb === 'string') func = (typeof this[cb] === 'function') ? this[cb] : func = (new Function(null, 'return ' + cb))();
    else if(cb instanceof Array) func = ( typeof cb[0] == 'string' ) ? eval(cb[0]+"['"+cb[1]+"']") : func = cb[0][cb[1]];
    else if (typeof cb === 'function') func = cb;
    if(typeof func != 'function') throw new Error(func + ' is not a valid function');   
    var parameters = Array.prototype.slice.call(arguments, 1);
    return (typeof cb[0] === 'string') ? func.apply(eval(cb[0]), parameters) : (typeof cb[0] !== 'object') ? func.apply(null, parameters) : func.apply(cb[0], parameters);
}

var jaxl = {
    jid: false,
    polling: 0,
    pollUrl: false,
    lastPoll: false,
    pollRate: 500,
    now: false,
    connected: false,
    disconnecting: false,
    payloadHandler: false,
    connect: function(obj) {
        if(obj == null) obj = new Object;
        obj['jaxl'] = 'connect';
        jaxl.sendPayload(obj);
    },
    disconnect: function(obj) {
        if(obj == null) obj = new Object;
        obj['jaxl'] = 'disconnect';
        jaxl.sendPayload(obj);
    },
    ping: function(obj) {
        if(obj == null) obj = new Object;
        obj['jaxl'] = 'ping';
        jaxl.sendPayload(obj);
    },
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
        jaxl.now = new Date().getTime();
        if(jaxl.lastPoll == false) {
            jaxl.xhrPayload(obj);
        }
        else {
            diff = jaxl.now-jaxl.lastPoll;
            
            if(diff < jaxl.pollRate) {
                var xhr = function() { jaxl.xhrPayload(obj); };
                
                // TO-DO: Use a queue instead
                setTimeout(xhr, jaxl.pollRate);
            }
            else {
                jaxl.xhrPayload(obj);
            }
        }
    },
    xhrPayload: function(obj) {
        if((jaxl.polling != 0 || !jaxl.connected || jaxl.disconnecting) && obj['jaxl'] == 'ping') return false;
        
        $.ajax({
            type: 'POST',
            url: jaxl.pollUrl,
            dataType: 'json',
            data: jaxl.preparePayload(obj),
            beforeSend: function() {
                jaxl.lastPoll = new Date().getTime();
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
        if(payload.length == 0) { jaxl.ping(); }
        else { 
            for(key in payload) {
                if(key == null) { jaxl.ping(); }
                else if(payload[key].jaxl == 'jaxl') { jaxl.xhrPayload(payload[key]); }
                else { call_user_func(jaxl.payloadHandler, payload[key]); }
            }
        }
    },
    urldecode: function(msg) {
        return decodeURIComponent(msg.replace(/\+/g, '%20'));
    }
};
