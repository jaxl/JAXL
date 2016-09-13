Changes
=======

JAXL introduces changes that affects on backward compatibility:

v3.1.0

* JAXL now use autoloader from Composer, so no more `require_once 'jaxl.php'`.
* Sources moves to subfolder `src/JAXL`, class JAXLCtl goes from `jaxlctl`
  to `src/JAXL/jaxlctl.php`, class HTTPDispathRule goes from `http_dispatcher.php`
  to `src/JAXL/http_dispatch_rule.php`.
* Class names XEP_* drops underscores and changed to XEP* to conform PSR-2.
* All config parameters of JAXL sets on instantiation to their default values,
  previously used code like `isset(JAXL->cfg['some-parameter'])` not need
  `isset` anymore.
* Globally defined log functions drops their underscores and moves
  to JAXLLogger, _colorize renamed to JAXLLogger::cliLog.
* JAXLXml->childrens fixed to children.
* Some methods now in camel case format (i.e. JAXL->require_xep => JAXL->requireXep).
* All constants now in upper case format (i.e. JAXL::version => JAXL::VERSION).
* Renaming of methods that starts with _ prefix, they only used in private API
  and shouldn't affect you.
* JAXL_CWD not used anymore and changed to getcwd().
* Move JAXL_MULTI_CLIENT to "multi_client" config parameter.
* Globally defined NS_* now moves to XEPs constants. File xmpp_nss.php was
  renamed to xmpp.php, so use XMPP::NS_*.
* JAXL_ERROR and other log levels goes to JAXLLogger::ERROR constant and so on.
* HTTP_CRLF and other HTTP_* codes goes to HTTPServer::HTTP_* constants.
* JAXLEvent->reg is not public property anymore, but you can get
  it with JAXLEvent->getRegistry()
* In JAXLXml::construct first argument $name is required.
* JAXL->add_exception_handlers moves to JAXLException::addHandlers.
* If some of your applications watch for debug message that starts with
  "active read fds: " then you've warned about new message format
  "Watch: active read fds: " and "Unwatch: active read fds: ".
