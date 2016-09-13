Logging Interface
=================

``JAXLLogger`` provides all the logging facilities that we will ever require.
When logging to ``STDOUT`` it also colorizes the log message depending upon 
its severity level. When logging to a file it can also do periodic log 
rotation.

log levels
----------

    * ERROR (red)
    * WARNING (blue)
    * NOTICE (yellow)
    * INFO (green)
    * DEBUG (white)

global logging methods
----------------------

Following global methods for logging are available:

    * ``error($msg)``
    * ``warning($msg)``
    * ``notice($msg)``
    * ``info($msg)``
    * ``debug($msg)``
        
log/2
-----

All the above global logging methods internally use ``log($msg, $verbosity)`` 
to output colored log message on the terminal.
