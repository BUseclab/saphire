/*
  +----------------------------------------------------------------------+
  | PHP Version 7                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2018 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_01.txt                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Author:                                                              |
  +----------------------------------------------------------------------+
*/

/* $Id$ */

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "link.h"
#include "dlfcn.h"
#include "php_ini.h"
#include "ext/standard/info.h"
#include "php_enum.h"
#include <execinfo.h>

/* If you declare any globals in php_enum.h uncomment this:
ZEND_DECLARE_MODULE_GLOBALS(enum)
*/

/* True global resources - no need for thread safety here */
static int le_enum;

/* {{{ PHP_INI
 */
/* Remove comments and fill if you need to have entries in php.ini
PHP_INI_BEGIN()
    STD_PHP_INI_ENTRY("enum.global_value",      "42", PHP_INI_ALL, OnUpdateLong, global_value, zend_enum_globals, enum_globals)
    STD_PHP_INI_ENTRY("enum.global_string", "foobar", PHP_INI_ALL, OnUpdateString, global_string, zend_enum_globals, enum_globals)
PHP_INI_END()
*/
/* }}} */

/* Remove the following function when you have successfully modified config.m4
   so that your module can be compiled into PHP, it exists only for testing
   purposes. */

/* Every user-visible function in PHP should document itself in the source */
/* {{{ proto string confirm_enum_compiled(string arg)
   Return a string to confirm that the module is compiled in */
PHP_FUNCTION(confirm_enum_compiled)
{
	char *arg = NULL;
	size_t arg_len, len;
	zend_string *strg;

	if (zend_parse_parameters(ZEND_NUM_ARGS(), "s", &arg, &arg_len) == FAILURE) {
		return;
	}

	strg = strpprintf(0, "Congratulations! You have successfully modified ext/%.78s/config.m4. Module %.78s is now compiled into PHP.", "enum", arg);

	RETURN_STR(strg);
}
/* }}} */
/* The previous line is meant for vim and emacs, so it can correctly fold and
   unfold functions in source code. See the corresponding marks just before
   function definition, where the functions purpose is also documented. Please
   follow this convention for the convenience of others editing your code.
*/


/* {{{ php_enum_init_globals
 */
/* Uncomment this function if you have INI entries
static void php_enum_init_globals(zend_enum_globals *enum_globals)
{
	enum_globals->global_value = 0;
	enum_globals->global_string = NULL;
}
*/
/* }}} */

/* {{{ PHP_MINIT_FUNCTION
 */
PHP_MINIT_FUNCTION(enum)
{
	/* If you have INI entries, uncomment these lines
	REGISTER_INI_ENTRIES();
	*/

	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MSHUTDOWN_FUNCTION
 */
PHP_MSHUTDOWN_FUNCTION(enum)
{
	/* uncomment this line if you have INI entries
	UNREGISTER_INI_ENTRIES();
	*/
	return SUCCESS;
}
/* }}} */

/* Remove if there's nothing to do at request start */
/* {{{ PHP_RINIT_FUNCTION
 */
PHP_RINIT_FUNCTION(enum)
{
#if defined(COMPILE_DL_ENUM) && defined(ZTS)
	ZEND_TSRMLS_CACHE_UPDATE();
#endif
}
/* }}} */

static int enum_function_name(zval *zv, int num_args, va_list args, zend_hash_key *hash_key) /* {{{ */
{
	zend_function *func = Z_PTR_P(zv);
	Dl_info structure;

	struct link_map extra;
	/* zval *internal_ar = va_arg(args, zval *), */
	/*      *user_ar     = va_arg(args, zval *); */
	/* zend_bool *exclude_disabled = va_arg(args, zend_bool *); */

	if (hash_key->key == NULL || ZSTR_VAL(hash_key->key)[0] == 0) {
		return 0;
	}
	dladdr1(func->internal_function.handler, &structure, &extra, RTLD_DL_LINKMAP);

	fprintf(stdout, "%50s\t%x\n", ZSTR_VAL(func->common.function_name),func->internal_function.handler-0x55554000);


		/* fprintf(stdout, "Defined Function: %x\n", func->internal_function.handler); */
	return 0;
}

static int enum_class_name(zval *zv, int num_args, va_list args, zend_hash_key *hash_key) /* {{{ */
{
	zend_class_entry *ce = (zend_class_entry *)Z_PTR_P(zv);

	if ((hash_key->key && ZSTR_VAL(hash_key->key)[0] != 0) && (ce->ce_flags & (ZEND_ACC_INTERFACE | ZEND_ACC_TRAIT))==0)
	{
		fprintf(stdout, "CLASS\t %s\n", ZSTR_VAL(ce->name));
		zend_hash_apply_with_arguments(&ce->function_table, enum_function_name, 3);
	}
		

	return ZEND_HASH_APPLY_KEEP;
}

PHP_FUNCTION(enum_func_details)
{
	HashPosition pos;
	zval *data;

	zend_hash_apply_with_arguments(EG(function_table), enum_function_name, 3);
	
	zend_hash_apply_with_arguments(EG(class_table), enum_class_name, 3);

	return SUCCESS;
}

/* Remove if there's nothing to do at request end */
/* {{{ PHP_RSHUTDOWN_FUNCTION
 */
PHP_RSHUTDOWN_FUNCTION(enum)
{
	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MINFO_FUNCTION
 */
PHP_MINFO_FUNCTION(enum)
{
	php_info_print_table_start();
	php_info_print_table_header(2, "enum support", "enabled");
	php_info_print_table_end();

	/* Remove comments if you have entries in php.ini
	DISPLAY_INI_ENTRIES();
	*/
}
/* }}} */

/* {{{ enum_functions[]
 *
 * Every user visible function must have an entry in enum_functions[].
 */
const zend_function_entry enum_functions[] = {
	PHP_FE(confirm_enum_compiled,	NULL)		/* For testing, remove later. */
	PHP_FE(enum_func_details,	NULL)		/* For testing, remove later. */
	PHP_FE_END	/* Must be the last line in enum_functions[] */
};
/* }}} */

/* {{{ enum_module_entry
 */
zend_module_entry enum_module_entry = {
	STANDARD_MODULE_HEADER,
	"enum",
	enum_functions,
	PHP_MINIT(enum),
	PHP_MSHUTDOWN(enum),
	PHP_RINIT(enum),		/* Replace with NULL if there's nothing to do at request start */
	PHP_RSHUTDOWN(enum),	/* Replace with NULL if there's nothing to do at request end */
	PHP_MINFO(enum),
	PHP_ENUM_VERSION,
	STANDARD_MODULE_PROPERTIES
};
/* }}} */

#ifdef COMPILE_DL_ENUM
#ifdef ZTS
ZEND_TSRMLS_CACHE_DEFINE()
#endif
ZEND_GET_MODULE(enum)
#endif

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
