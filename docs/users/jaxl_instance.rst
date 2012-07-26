.. _jaxl-instance:

JAXL Instance
=============
``JAXL`` instance configure/manage other :ref:`sub-packages <jaxl-instance>`.
It provides an event based callback methodology on various underlying object. Whenever required
``JAXL`` instance will itself perform the configured defaults.

Constructor options
-------------------

    #. ``jid``
    #. ``pass``
    #. ``resource``
    
        If not passed Jaxl will use a random resource value
        
    #. ``auth_type``
    
        DIGEST-MD5, PLAIN (default), CRAM-MD5, ANONYMOUS, X-FACEBOOK-PLATFORM
    
    #. ``host``
    #. ``port``
    #. ``bosh_url``
    #. ``log_path``
    #. ``log_level``
    
        ``JAXL_ERROR``, ``JAXL_WARNING``, ``JAXL_NOTICE``, ``JAXL_INFO`` (default), ``JAXL_DEBUG``
        
    #. ``fb_access_token``
    
        required when using X-FACEBOOK-PLATFORM auth mechanism
        
    #. ``fb_app_key``
    
        required when using X-FACEBOOK-PLATFORM auth mechanism
        
    #. ``force_tls``
    #. ``stream_context``
    #. ``priv_dir``
    
        Jaxl creates 4 directories names ``log``, ``tmp``, ``run`` and ``sock`` inside a private directory
        which defaults to ``JAXL_CWD.'/.jaxl'``. If this option is passed, it will overwrite default private
        directory.
        
        .. note::
        
            Jaxl currently doesn't check upon the permissions of passed ``priv_dir``. Make sure Jaxl library 
            have sufficient permissions to create above mentioned directories.

Available Event Callbacks
-------------------------

    #. ``on_connect``
    #. ``on_connect_error``
    #. ``on_stream_start``
    #. ``on_stream_features``
    #. ``on_auth_success``
    #. ``on_auth_failure``
    #. ``on_presence_stanza``
    #. ``on_{$type}_message``
    #. ``on_stanza_id_{$id}``
    #. ``on_{$name}_stanza``
    #. ``on_disconnect``

start() method options
----------------------

    #. ``--with-debug-shell``
    #. ``--with-unix-sock``
