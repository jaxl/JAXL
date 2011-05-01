<?php
/**
 * Jaxl (Jabber XMPP Library)
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
 *
 * @package jaxl
 * @subpackage xep
 * @author Abhinav Singh <me@abhinavsingh.com>
 * @copyright Abhinav Singh
 * @link http://code.google.com/p/jaxl
 */
    
    /**
     * XEP-xxxx : Google MailNotify
    */
    class JAXLMailNotify {

        public static $ns = 'google:mail:notify';

        public static function init($jaxl) {
            $jaxl->features[] = self::$ns;
            
            JAXLXml::addTag('iq', 'gMailResultTime', '//iq/mailbox/@result-time');
            JAXLXml::addTag('iq', 'gMailUrl', '//iq/mailbox/@url');
            JAXLXml::addTag('iq', 'gMailTotalMatched', '//iq/mailbox/@total-matched');
            JAXLXml::addTag('iq', 'gMailTotalEstimate', '//iq/mailbox/@total-estimate');

            JAXLXml::addTag('iq', 'gMailThreadTid', '//iq/mailbox/mail-thread-info/@tid');
            JAXLXml::addTag('iq', 'gMailThreadParticipation', '//iq/mailbox/mail-thread-info/@participation');
            JAXLXml::addTag('iq', 'gMailThreadMessages', '//iq/mailbox/mail-thread-info/@messages');
            JAXLXml::addTag('iq', 'gMailThreadDate', '//iq/mailbox/mail-thread-info/@date');
            JAXLXml::addTag('iq', 'gMailThreadUrl', '//iq/mailbox/mail-thread-info/@url');

            JAXLXml::addTag('iq', 'gMailLabels', '//iq/mailbox/mail-thread-info/labels');
            JAXLXml::addTag('iq', 'gMailSubject', '//iq/mailbox/mail-thread-info/subject');
            JAXLXml::addTag('iq', 'gMailSnippet', '//iq/mailbox/mail-thread-info/snippet');
        
            JAXLXml::addTag('iq', 'gMailSenderName', '//iq/mailbox/mail-thread-info/senders/@name');
            JAXLXml::addTag('iq', 'gMailSenderAddress', '//iq/mailbox/mail-thread-info/senders/@address');
            JAXLXml::addTag('iq', 'gMailSenderOriginator', '//iq/mailbox/mail-thread-info/senders/@originator');
            JAXLXml::addTag('iq', 'gMailSenderUnread', '//iq/mailbox/mail-thread-info/senders/@unread');

            JAXLXml::addTag('iq', 'gMailNotify', '//iq/new-mail/@xmlns');
        }

    }

?>
