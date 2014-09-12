Changes
=======

JAXL introduces changes that affect compatibility:

v3.1.0

* PHP version >= 5.3: namespaces and anonymous functions.
* All methods now in camel case format (i.e. JAXL->require_xep => JAXL->requireXep).
* All constants now in upper case format (i.e. JAXL::version => JAXL::VERSION).
* Renaming of methods that starts with _ prefix, they only used in private API and shouldn't affect you.
