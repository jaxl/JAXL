<?php
	
	/**
	 * Sample browser based one-to-one chat application using Jaxl library
     * Usage: Symlink or copy whole Jaxl library folder inside your web folder
     *        Edit "BOSHCHAT_POLL_URL" and "BOSHCHAT_ADMIN_JID" below to suit your environment
     *        Run this app file from the browser e.g. http://path/to/jaxl/app/boshchat.php
     *        View /var/log/jaxl.log for debug info
     * 
	 * Read more: http://jaxl.net/example/boshchat.php
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
            'authType'=>'DIGEST-MD5',
            'logLevel'=>4
        ));
        
		// Admin jid who will receive all messages sent using this application ui
		define('BOSHCHAT_ADMIN_JID', 'admin@'.$jaxl->domain);
        
        // Include required XEP's
        $jaxl->requires(array(
            'JAXL0115', // Entity Capabilities
            'JAXL0085', // Chat State Notification
            'JAXL0092', // Software Version
            'JAXL0203', // Delayed Delivery
            'JAXL0202', // Entity Time
            'JAXL0206'  // XMPP over Bosh
        ));
        
        // Sample Bosh chat application class
        class boshchat {
            
            public static function postAuth($payload, $jaxl) {
                $response = array('jaxl'=>'connected', 'jid'=>$jaxl->jid);
                $jaxl->JAXL0206('out', $response);
            }
            
            public static function postRosterUpdate($payload, $jaxl) {
                $response = array('jaxl'=>'rosterList', 'roster'=>$jaxl->roster);
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
                    if($payload['offline'] != JAXL0203::$ns && $payload['type'] == 'chat') {
                        if(strlen($payload['body']) > 0) {
                            $html .= '<div class="mssgIn">';
                            $html .= '<p class="from">'.$payload['from'].'</p>';
                            $html .= '<p class="body">'.$payload['body'].'</p>';
                            $html .= '</div>';
                        }
                        else if(isset($payload['chatState']) && in_array($payload['chatState'], JAXL0085::$chatStates)) {
                            $html .= '<div class="presIn">';
                            $html .= '<p class="from">'.$payload['from'].' chat state '.$payload['chatState'].'</p>';
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
                    if($payload['type'] == '' || in_array($payload['type'], array('available', 'unavailable'))) {
                        $html .= '<div class="presIn">';
                        $html .= '<p class="from">'.$payload['from'];
                        if($payload['type'] == 'unavailable') $html .= ' is now offline</p>';
                        else $html .= ' is now online</p>';
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

            public static function postCurlErr($payload, $jaxl) {
                if($_REQUEST['jaxl'] == 'disconnect') self::postDisconnect($payload, $jaxl);
                else $jaxl->JAXL0206('out', array('jaxl'=>'curlError', 'code'=>$payload['errno'], 'msg'=>$payload['errmsg']));
            }
            
        }
        
        // Add callbacks on various event handlers
        $jaxl->addPlugin('jaxl_post_auth_failure', array('boshchat', 'postAuthFailure'));
        $jaxl->addPlugin('jaxl_post_auth', array('boshchat', 'postAuth'));
        $jaxl->addPlugin('jaxl_post_disconnect', array('boshchat', 'postDisconnect'));
        $jaxl->addPlugin('jaxl_get_empty_body', array('boshchat', 'postEmptyBody'));
        $jaxl->addPlugin('jaxl_get_bosh_curl_error', array('boshchat', 'postCurlErr'));
        $jaxl->addPlugin('jaxl_get_message', array('boshchat', 'getMessage'));
        $jaxl->addPlugin('jaxl_get_presence', array('boshchat', 'getPresence'));
        $jaxl->addPlugin('jaxl_post_roster_update', array('boshchat', 'postRosterUpdate'));

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
            case 'getRosterList':
                $jaxl->getRosterList();
                break;
            case 'setStatus':
                $jaxl->setStatus(FALSE, FALSE, FALSE, TRUE);
                break;
            case 'message':
                $jaxl->sendMessage(BOSHCHAT_ADMIN_JID, $_POST['message']);
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
	if(!isset($_REQUEST['jaxl'])) {
        // Serve application UI if $_REQUEST['jaxl'] is not set
    }

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en-US" dir="ltr">
        <head profile="http://gmpg.org/xfn/11">
                <link rel="SHORTCUT ICON" href="http://im.jaxl.im/favicon.ico" type="image/x-icon">
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
                <title>Web Chat Application using Jaxl Library</title>
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
var boshchat = {
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

                obj = new Object;
                obj['jaxl'] = 'getRosterList';
                jaxl.sendPayload(obj);
        }
        else if(payload.jaxl == 'rosterList') {
                obj = new Object;
                obj['jaxl'] = 'setStatus';
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
        else if(payload.jaxl == 'message') {
			boshchat.appendMessage(jaxl.urldecode(payload.message));
            jaxl.ping();
        }
        else if(payload.jaxl == 'presence') {
			boshchat.appendMessage(jaxl.urldecode(payload.presence));
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
        jaxl.payloadHandler = new Array('boshchat', 'payloadHandler');

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
        
                boshchat.appendMessage(boshchat.prepareMessage(jaxl.jid, message));
        
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
                        <h1>Web Chat Application using Jaxl Library</h1>
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
