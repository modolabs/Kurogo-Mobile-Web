* make paths more sane, such that it does not uses DOCUMENT_ROOT to include libraries and other
required files

* LIBDIR is defined in 2 different files mobi_web_constants.php and mobi_lib_constants.php,
rework this so that LIBDIR is only defined one place probably mobi_web_constants.php
