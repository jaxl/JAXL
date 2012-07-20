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
    #. ``auth_type``
    #. ``host``
    #. ``port``
    #. ``bosh_url``
    #. ``log_path``
    #. ``log_level``
    #. ``fb_access_token``
    #. ``fb_app_key``
    #. ``force_tls``
    #. ``stream_context``

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
