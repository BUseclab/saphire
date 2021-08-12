dnl $Id$
dnl config.m4 for extension seccomp

dnl Comments in this file start with the string 'dnl'.
dnl Remove where necessary. This file will not work
dnl without editing.

dnl If your extension references something external, use with:

dnl PHP_ARG_WITH(seccomp, for seccomp support,
dnl Make sure that the comment is aligned:
dnl [  --with-seccomp             Include seccomp support])

dnl Otherwise use enable:

PHP_ARG_ENABLE(seccomp, whether to enable seccomp support,
[  --enable-seccomp           Enable seccomp support])
dnl Make sure that the comment is aligned:

if test "$PHP_SECCOMP" != "no"; then
  dnl Write more examples of tests here...
  dnl # get library FOO build options from pkg-config output
  dnl AC_PATH_PROG(PKG_CONFIG, pkg-config, no)
  dnl AC_MSG_CHECKING(for libfoo)
  dnl if test -x "$PKG_CONFIG" && $PKG_CONFIG --exists foo; then
  dnl   if $PKG_CONFIG foo --atleast-version 1.2.3; then
  dnl     LIBFOO_CFLAGS=`$PKG_CONFIG foo --cflags`
  dnl     LIBFOO_LIBDIR=`$PKG_CONFIG foo --libs`
  dnl     LIBFOO_VERSON=`$PKG_CONFIG foo --modversion`
  dnl     AC_MSG_RESULT(from pkgconfig: version $LIBFOO_VERSON)
  dnl   else
  dnl     AC_MSG_ERROR(system libfoo is too old: version 1.2.3 required)
  dnl   fi
  dnl else
  dnl   AC_MSG_ERROR(pkg-config not found)
  dnl fi
  dnl PHP_EVAL_LIBLINE($LIBFOO_LIBDIR, SECCOMP_SHARED_LIBADD)
  dnl PHP_EVAL_INCLINE($LIBFOO_CFLAGS)

  dnl # --with-seccomp -> check with-path
  dnl SEARCH_PATH="/usr/local /usr"     # you might want to change this
  dnl SEARCH_FOR="/include/seccomp.h"  # you most likely want to change this
  dnl if test -r $PHP_SECCOMP/$SEARCH_FOR; then # path given as parameter
  dnl   SECCOMP_DIR=$PHP_SECCOMP
  dnl else # search default path list
  dnl   AC_MSG_CHECKING([for seccomp files in default path])
  dnl   for i in $SEARCH_PATH ; do
  dnl     if test -r $i/$SEARCH_FOR; then
  dnl       SECCOMP_DIR=$i
  dnl       AC_MSG_RESULT(found in $i)
  dnl     fi
  dnl   done
  dnl fi
  dnl
  dnl if test -z "$SECCOMP_DIR"; then
  dnl   AC_MSG_RESULT([not found])
  dnl   AC_MSG_ERROR([Please reinstall the seccomp distribution])
  dnl fi

  dnl # --with-seccomp -> add include path
  dnl PHP_ADD_INCLUDE($SECCOMP_DIR/include)

  dnl # --with-seccomp -> check for lib and symbol presence
  LIBNAME=seccomp 
  LIBSYMBOL=seccomp_init 

   PHP_CHECK_LIBRARY($LIBNAME,$LIBSYMBOL,
   [
     PHP_ADD_LIBRARY_WITH_PATH($LIBNAME, $SECCOMP_DIR/$PHP_LIBDIR, SECCOMP_SHARED_LIBADD)
     AC_DEFINE(HAVE_SECCOMPLIB,1,[ ])
   ],[
     AC_MSG_ERROR([wrong seccomp lib version or lib not found])
   ],[
     -L$SECCOMP_DIR/$PHP_LIBDIR -lm -lseccomp
   ])
  LIBNAME=sqlite3 
  LIBSYMBOL=sqlite3_initialize

   PHP_CHECK_LIBRARY($LIBNAME,$LIBSYMBOL,
   [
     PHP_ADD_LIBRARY_WITH_PATH($LIBNAME, $SECCOMP_DIR/$PHP_LIBDIR, SECCOMP_SHARED_LIBADD)
     AC_DEFINE(HAVE_SECCOMPLIB,1,[ ])
   ],[
     AC_MSG_ERROR([wrong sqlite3 lib version or lib not found])
   ],[
     -L$SECCOMP_DIR/$PHP_LIBDIR -lm -lsqlite3
   ])
  dnl
  dnl PHP_SUBST(SECCOMP_SHARED_LIBADD)
  dnl
  PHP_SECCOMP_CFLAGS="-lseccomp -lsqlite3 -fno-strict-aliasing"

  PHP_NEW_EXTENSION(seccomp, seccomp.c, $ext_shared,, $PHP_SECCOMP_CFLAGS,,-DZEND_ENABLE_STATIC_TSRMLS_CACHE=1)
  PHP_SUBST(SECCOMP_SHARED_LIBADD)
fi
