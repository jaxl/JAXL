<?php
	
	/**
	 * Sample browser based MUC chat room application using Jaxl library
     * Usage: Symlink or copy whole Jaxl library folder inside your web folder
     *        Edit "BOSHCHAT_POLL_URL" and "BOSHCHAT_ROOM_JID" below to suit your environment
     *        Run this app file from the browser e.g. http://path/to/jaxl/app/boshMUChat.php
     *        View /var/log/jaxl.log for debug info
     * 
	 * Read more: http://jaxl.net/example/boshMUChat.php
	*/
    
	// Ajax poll url
	define('BOSHCHAT_POLL_URL', $_SERVER['PHP_SELF']);
	
	if(isset($_REQUEST['jaxl'])) { // Valid bosh request	    
        // Initialize Jaxl Library
        require_once '/usr/share/php/jaxl/core/jaxl.class.php';
	    $jaxl = new JAXL(array(
            'domain'=>'localhost',
            'port'=>5222,
            'boshHost'=>'localhost',
            'authType'=>'PLAIN',
            'logLevel'=>4
        ));
         	
		// Room jid which user join by default
		define('BOSHCHAT_ROOM_JID', 'room@muc.'.$jaxl->domain);
	
        // Include required XEP's
        $jaxl->requires(array(
            'JAXL0115', // Entity Capabilities
            'JAXL0092', // Software Version
            'JAXL0203', // Delayed Delivery
            'JAXL0202', // Entity Time
            'JAXL0206', // XMPP over Bosh
            'JAXL0045'  // Multi-User Chat
        ));
        
        // Sample Bosh MUC chat room application class
        class boshMUChat {
            
            public static function postAuth($payload, $jaxl) {
                list($nick, $domain, $res) = JAXLUtil::splitJid($jaxl->jid);
                $jaxl->JAXL0045('joinRoom', $jaxl->jid, BOSHCHAT_ROOM_JID.'/'.$nick, 0, 'seconds');
                $response = array('jaxl'=>'connected', 'jid'=>$jaxl->jid);
                $jaxl->JAXL0206('out', $response);
            }
            
            public static function postDisconnect($payload, $jaxl) {
                $response = array('jaxl'=>'disconnected');
                $jaxl->JAXL0206('out', $response);
            }
            
            public static function getMessage($payloads, $jaxl) {
                $html = '';
                foreach($payloads as $payload) {
                    // reject offline message
                    if($payload['offline'] != JAXL0203::$ns && $payload['type'] == 'groupchat') {
                        if(strlen($payload['body']) > 0) {
                            list($room, $domain, $nick) = JAXLUtil::splitJid($payload['from']);
                            $html .= '<div class="mssgIn">';
                            $html .= '<p class="from">'.$nick.'</p>';
                            $html .= '<p class="body">'.$payload['body'].'</p>';
                            $html .= '</div>';
                        }
                    }
                }
                
                if($html != '') {
                    $response = array('jaxl'=>'message', 'message'=>urlencode($html));
                    $jaxl->JAXL0206('out', $response);
                }
                
                return $payloads;
            }
            
            public static function getPresence($payloads, $jaxl) {
                $html = '';
                foreach($payloads as $payload) {
                    if(in_array($payload['type'], array('', 'available', 'unavailable'))) {
                        list($room, $domain, $nick) = JAXLUtil::splitJid($payload['from']);
                        $html .= '<div class="presIn">';
                        $html .= '<p class="from">'.$nick;
                        if($payload['type'] == 'unavailable') $html .= ' left the room</p>';
                        else $html .= ' joined the room</p>';
                        $html .= '</div>';
                    }
                }
                
                if($html != '') {
                    $response = array('jaxl'=>'presence', 'presence'=>urlencode($html));
                    $jaxl->JAXL0206('out', $response);
                }
                
                return $payloads;
            }
            
            public static function postEmptyBody($body, $jaxl) {
                $response = array('jaxl'=>'pinged');
                $jaxl->JAXL0206('out', $response);
            }

            public static function postAuthFailure($payload, $jaxl) {
                $response = array('jaxl'=>'authFailed');
                $jaxl->JAXL0206('out', $response);
            }
            
        }
        
        // Add callbacks on various event handlers
        $jaxl->addPlugin('jaxl_post_auth_failure', array('boshMUChat', 'postAuthFailure'));
        $jaxl->addPlugin('jaxl_post_auth', array('boshMUChat', 'postAuth'));
        $jaxl->addPlugin('jaxl_post_disconnect', array('boshMUChat', 'postDisconnect'));
        $jaxl->addPlugin('jaxl_get_empty_body', array('boshMUChat', 'postEmptyBody'));
        $jaxl->addPlugin('jaxl_get_message', array('boshMUChat', 'getMessage'));
        $jaxl->addPlugin('jaxl_get_presence', array('boshMUChat', 'getPresence'));
        
        // Handle incoming bosh request
        switch($_REQUEST['jaxl']) {
            case 'connect':
                $jaxl->user = $_POST['user'];
                $jaxl->pass = $_POST['pass'];
                $jaxl->startCore('bosh');
                break;
            case 'disconnect':
                $jaxl->JAXL0206('endStream');
                break;
            case 'message':
                $jaxl->sendMessage(BOSHCHAT_ROOM_JID, $_POST['message'], $jaxl->jid, 'groupchat');
                break;
            case 'ping':
                $jaxl->JAXL0206('ping');
                break;
            case 'jaxl':
                $jaxl->JAXL0206('jaxl', $_REQUEST['xml']);
                break;
            default:
                $response = array('jaxl'=>'400', 'desc'=>$_REQUEST['jaxl']." not implemented");
                $jaxl->JAXL0206('out', $response);
                break;
        }
    }
    else {
        // Serve application UI if $_REQUEST['jaxl'] is not set
    }
	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en-US" dir="ltr">
        <head profile="http://gmpg.org/xfn/11">
                <link rel="SHORTCUT ICON" href="http://im.jaxl.im/favicon.ico" type="image/x-icon">
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
                <title>Web MUC Chat Application using Jaxl Library</title>
                <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
                <script type="text/javascript" src="<?php echo dirname(BOSHCHAT_POLL_URL) ?>/../env/jaxl.js"></script>
                <script type="text/javascript">jaxl.pollUrl = "<?php echo BOSHCHAT_POLL_URL; ?>";</script>
		<style type="text/css">
body { color:#444; background-color:#F7F7F7; font:62.5% "lucida grande","lucida sans unicode",helvetica,arial,sans-serif; }
label, input { margin-bottom:5px; }
#read { width:700px; height:250px; overflow-x:hidden; overflow-y:auto; background-color:#FFF; border:1px solid #E7E7E7; display:none; }
#read .mssgIn, #read .presIn { text-align:left; margin:5px; padding:0px 5px; border-bottom:1px solid #EEE; }
#read .presIn { background-color:#F7F7F7; font-size:11px; font-weight:normal; }
#read .mssgIn p.from, #read .presIn p.from { padding:0px; margin:0px; font-size:13px; }
#read .mssgIn p.from { font-weight:bold; }
#read .mssgIn p.body { padding:0px; margin:0px; font-size:12px; }
#write { width:698px; border:1px solid #E7E7E7; background-color:#FFF; height:20px; padding:1px; font-size:13px; color:#AAA; display:none; }
		</style>
		<script type="text/javascript">
var boshMUChat = {
    payloadHandler: function(payload) {
        if(payload.jaxl == 'authFailed') {
            jaxl.connected = false;
            $('#button input').val('Connect');
        }
        else if(payload.jaxl == 'connected') {
            jaxl.connected = true;
            jaxl.jid = payload.jid;

            $('#uname').css('display', 'none');
            $('#passwd').css('display', 'none');
            $('#button input').val('Disconnect');
            $('#read').css('display', 'block');
            $('#write').css('display', 'block');

            jaxl.ping();
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
        else if(payload.jaxl == 'message') {
            boshMUChat.appendMessage(jaxl.urldecode(payload.message));
            jaxl.ping();
        }
        else if(payload.jaxl == 'presence') {
            boshMUChat.appendMessage(jaxl.urldecode(payload.presence));
            jaxl.ping();
        }
        else if(payload.jaxl == 'pinged') {
            jaxl.ping();
        }
    },
	appendMessage: function(message) {
		$('#read').append(message);
		$('#read').animate({ scrollTop: $('#read').attr('scrollHeight') }, 300);
	},
	prepareMessage: function(jid, message) {
		html = '';
		html += '<div class="mssgIn">';
		html += '<p class="from">'+jid+'</p>';
		html += '<p class="body">'+message+'</div>';
		html += '</div>';
		return html;
	}
};

jQuery(function($) {
        $(document).ready(function() {
                jaxl.payloadHandler = new Array('boshMUChat', 'payloadHandler');

                $('#button input').click(function() {
                        if($(this).val() == 'Connect') {
                                $(this).val('Connecting...');

                                // prepare connect object
                                obj = new Object;
                                obj['user'] = $('#uname input').val();
                                obj['pass'] = $('#passwd input').val();

                                jaxl.connect(obj);
                        }
                        else if($(this).val() == 'Disconnect') {
                                $(this).val('Disconnecting...');
                                jaxl.disconnect();
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
		</script>
	</head>
        <body>
                <center>
                        <h1>Web MUC Chat Application using Jaxl Library</h1>
                        <div id="uname">
                                <label>Username:</label>
                                <input type="text" value=""/>
                        </div>
                        <div id="passwd">
                                <label>Password:</label>
                                <input type="password" value=""/>
                        </div>
                        <div id="read"></div>
                        <input type="text" value="Type your message" id="write"></input>
                        <div id="button">
                                <label></label>
                                <input type="button" value="Connect"/>
                        </div>
                </center>
        </body>
</html>
