<?php
/* Jaxl (Jabber XMPP Library)
 *
 * Copyright (c) 2009-2010, Abhinav Singh <me@abhinavsingh.com>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Abhinav Singh nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRIC
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */
    
    /*
     * XEP-0133: Service Administration
    */
    class JAXL0133 {
        
        public static $ns;
        public static $node = 'http://jabber.org/protocol/admin';   
        protected static $buffer = array();
        
        public static function init($jaxl) {
            // include required XEP's
            jaxl_require(array(
                'JAXL0004', // Data Forms
                'JAXL0050'  // Ad-Hoc Commands
            ), $jaxl);
            
            $jaxl->features[] = self::$ns;
        }
        
        protected static function requestForm($to, $from, $type, $jaxl) {
            $callback = array('JAXL0133', 'handleForm');
            return JAXL0050::executeCommand($to, $from, self::$node."#".$type, $callback, $jaxl);
        }
        
        public static function handleForm($payload) {
            $id = $payload['iq']['@']['id'];
            $domain = $payload['iq']['@']['from'];
            $node = $payload['iq']['#']['command'][0]['@']['node'];
            $sid = $payload['iq']['#']['command'][0]['@']['sessionid'];
            $status = $payload['iq']['#']['command'][0]['@']['status'];

            if($status == "completed") {
                $callback = self::$buffer[self::$buffer[$id]]['callback'];
                unset(self::$buffer[self::$buffer[$id]]);
                unset(self::$buffer[$id]);
                call_user_func($callback, $payload);
            }
            else if($status == "executing") {
                $fields = JAXL0004::getFormField($payload['iq']['#']['command'][0]['#']['x'][0]['#']['field']);
                foreach($fields as $key => $field) {
                    switch($field['var']) {
                        case 'accountjids':
                            $fields[$key]['value'] = self::$buffer[$id]['user']['jid'].'@'.$domain;
                            break;
                        case 'accountjid':
                            $fields[$key]['value'] = self::$buffer[$id]['user']['jid'].'@'.$domain;
                            break;
                        case 'password':
                            $fields[$key]['value'] = self::$buffer[$id]['user']['pass'];
                            break;
                        case 'password-verify':
                            $fields[$key]['value'] = self::$buffer[$id]['user']['pass'];
                            break;
                        case 'email':
                            $fields[$key]['value'] = self::$buffer[$id]['user']['email'];
                            break;
                        case 'given_name':
                            $fields[$key]['value'] = self::$buffer[$id]['user']['fname'];
                            break;
                        case 'surname':
                            $fields[$key]['value'] = self::$buffer[$id]['user']['lname'];
                            break;
                    }
                }
                $payload = JAXL0004::setFormField($fields, false, false, 'submit');
                self::$buffer[self::submitForm($domain, false, $payload, $node, $sid)] = $id;
            }
            else {
                JAXLog::log("Unhandled form status type...");
            }
        }
        
        protected static function submitForm($to, $from, $payload, $node, $sid, $jaxl) {
            $payload = '<command xmlns="http://jabber.org/protocol/commands" node="'.$node.'" sessionid="'.$sid.'">'.$payload.'</command>';
            return XMPPSend::iq($jaxl, 'set', $payload, $to, $from, array('JAXL0133', 'handleForm'));
        }
        
        public static function addUser($user, $domain, $callback, $jaxl) {
            $id = self::requestForm($domain, false, 'add-user', $jaxl);
            self::$buffer[$id] = array('user'=>$user, 'callback'=>$callback);
            unset($user); unset($domain); unset($callback);
            return true;
        }
        
        public static function deleteUser($user, $domain, $callback, $jaxl) {
            $id = self::requestForm($domain, false, 'delete-user', $jaxl);
            self::$buffer[$id] = array('user'=>$user, 'callback'=>$callback);
            unset($user); unset($domain); unset($callback);
            return true;
        }

        public static function disableUser($user, $domain, $callback, $jaxl) {
            $id = self::requestForm($domain, false, 'disable-user', $jaxl);
            self::$buffer[$id] = array('user'=>$user, 'callback'=>$callback);
            unset($user); unset($domain); unset($callback);
            return true;
        }

        public static function reEnableUser($user, $domain, $callback, $jaxl) {
            $id = self::requestForm($domain, false, 'reenable-user', $jaxl);
            self::$buffer[$id] = array('user'=>$user, 'callback'=>$callback);
            unset($user); unset($domain); unset($callback);
            return true;
        }

        public static function endUserSession($user, $domain, $callback, $jaxl) {
            $id = self::requestForm($domain, false, 'end-user-session', $jaxl);
            self::$buffer[$id] = array('user'=>$user, 'callback'=>$callback);
            unset($user); unset($domain); unset($callback);
            return true;
        }
        
        public static function getUserPassword($user, $domain, $callback, $jaxl) {
            $id = self::requestForm($domain, false, 'get-user-password', $jaxl);
            self::$buffer[$id] = array('user'=>$user, 'callback'=>$callback);
            unset($user); unset($domain); unset($callback);
            return true;
        }
        
        public static function changeUserPassword($user, $domain, $callback, $jaxl) {
            $id = self::requestForm($domain, false, 'change-user-password', $jaxl);
            self::$buffer[$id] = array('user'=>$user, 'callback'=>$callback);
            unset($user); unset($domain); unset($callback);
            return true;
        }

        public static function getUserRoster($user, $domain, $callback, $jaxl) {
            $id = self::requestForm($domain, false, 'get-user-roster', $jaxl);
            self::$buffer[$id] = array('user'=>$user, 'callback'=>$callback);
            unset($user); unset($domain); unset($callback);
            return true;
        }

        public static function getUserLastLoginTime($user, $domain, $callback, $jaxl) {
            $id = self::requestForm($domain, false, 'get-user-lastlogin', $jaxl);
            self::$buffer[$id] = array('user'=>$user, 'callback'=>$callback);
            unset($user); unset($domain); unset($callback);
            return true;
        }

        public static function getUserStatistics($user, $domain, $callback, $jaxl) {
            $id = self::requestForm($domain, false, 'user-stats', $jaxl);
            self::$buffer[$id] = array('user'=>$user, 'callback'=>$callback);
            unset($user); unset($domain); unset($callback);
            return true;
        }

        public static function editBlacklist($user, $domain, $callback, $jaxl) {
            $id = self::requestForm($domain, false, 'edit-blacklist', $jaxl);
            self::$buffer[$id] = array('user'=>$user, 'callback'=>$callback);
            unset($user); unset($domain); unset($callback);
            return true;
        }

        public static function editWhitelist($user, $domain, $callback, $jaxl) {
            $id = self::requestForm($domain, false, 'edit-whitelist', $jaxl);
            self::$buffer[$id] = array('user'=>$user, 'callback'=>$callback);
            unset($user); unset($domain); unset($callback);
            return true;
        }

        // 1 step
        public static function getUserCount($user, $domain, $callback, $type, $jaxl) {
            switch($type) {
                case 'registered':
                    $type = 'get-registered-users-num';
                    break;
                case 'disabled':
                    $type = 'get-disabled-users-num';
                    break;
                case 'online':
                    $type = 'get-online-users-num';
                    break;
                case 'active':
                    $type = 'get-active-users-num';
                    break;
                case 'idle':
                    $type = 'get-idle-users-num';
                    break;
                default:
                    return false;
            }
            
            $id = self::requestForm($domain, false, $type, $jaxl);
            self::$buffer[$id] = array('user'=>$user, 'callback'=>$callback);
            unset($user); unset($domain); unset($callback);
            return true;
        }

        public static function getUserList($user, $domain, $callback, $type, $jaxl) {
            switch($type) {
                case 'registered':
                    $type = 'get-registered-users-list';
                    break;
                case 'disabled':
                    $type = 'get-disabled-users-list';
                    break;
                case 'online':
                    $type = 'get-online-users-list';
                    break;
                case 'active':
                    $type = 'get-active-users';
                    break;
                case 'idle':
                    $type = 'get-idle-users';
                    break;
                default:
                    return false;
            }
            
            $id = self::requestForm($domain, false, $type, $jaxl);
            self::$buffer[$id] = array('user'=>$user, 'callback'=>$callback);
            unset($user); unset($domain); unset($callback);
            return true;
        }
        
        public static function sendAnnouncementToActiveUsers($user, $domain, $callback, $jaxl) {
            $id = self::requestForm($domain, false, 'announce', $jaxl);
            self::$buffer[$id] = array('user'=>$user, 'callback'=>$callback);
            unset($user); unset($domain); unset($callback);
            return true;
        }

        public static function setMOTD($user, $domain, $callback, $jaxl) {
            $id = self::requestForm($domain, false, 'set-motd', $jaxl);
            self::$buffer[$id] = array('user'=>$user, 'callback'=>$callback);
            unset($user); unset($domain); unset($callback);
            return true;
        }

        public static function editMOTD($user, $domain, $callback, $jaxl) {
            $id = self::requestForm($domain, false, 'edit-motd', $jaxl);
            self::$buffer[$id] = array('user'=>$user, 'callback'=>$callback);
            unset($user); unset($domain); unset($callback);
            return true;
        }

        // 1 step
        public static function deleteMOTD($user, $domain, $callback, $jaxl) {
            $id = self::requestForm($domain, false, 'delete-motd', $jaxl);
            self::$buffer[$id] = array('user'=>$user, 'callback'=>$callback);
            unset($user); unset($domain); unset($callback);
            return true;
        }

        public static function setWelcomeMessage($user, $domain, $callback, $jaxl) {
            $id = self::requestForm($domain, false, 'set-welcome', $jaxl);
            self::$buffer[$id] = array('user'=>$user, 'callback'=>$callback);
            unset($user); unset($domain); unset($callback);
            return true;
        }

        // 1 step
        public static function deleteWelcomeMessage($user, $domain, $callback, $jaxl) {
            $id = self::requestForm($domain, false, 'delete-welcome', $jaxl);
            self::$buffer[$id] = array('user'=>$user, 'callback'=>$callback);
            unset($user); unset($domain); unset($callback);
            return true;
        }

        public static function editAdminList($user, $domain, $callback, $jaxl) {
            $id = self::requestForm($domain, false, 'edit-admin', $jaxl);
            self::$buffer[$id] = array('user'=>$user, 'callback'=>$callback);
            unset($user); unset($domain); unset($callback);
            return true;
        }

        public static function restartService($user, $domain, $callback, $jaxl) {
            $id = self::requestForm($domain, false, 'restart', $jaxl);
            self::$buffer[$id] = array('user'=>$user, 'callback'=>$callback);
            unset($user); unset($domain); unset($callback);
            return true;
        }

        public static function shutdownService($user, $domain, $callback, $jaxl) {
            $id = self::requestForm($domain, false, 'shutdown', $jaxl);
            self::$buffer[$id] = array('user'=>$user, 'callback'=>$callback);
            unset($user); unset($domain); unset($callback);
            return true;
        }

    }

?>
