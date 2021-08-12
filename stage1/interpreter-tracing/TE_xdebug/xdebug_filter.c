/*
   +----------------------------------------------------------------------+
   | Xdebug                                                               |
   +----------------------------------------------------------------------+
   | Copyright (c) 2002-2018 Derick Rethans                               |
   +----------------------------------------------------------------------+
   | This source file is subject to version 1.01 of the Xdebug license,   |
   | that is bundled with this package in the file LICENSE, and is        |
   | available at through the world-wide-web at                           |
   | https://xdebug.org/license.php                                       |
   | If you did not receive a copy of the Xdebug license and are unable   |
   | to obtain it through the world-wide-web, please send a note to       |
   | derick@xdebug.org so we can mail you a copy immediately.             |
   +----------------------------------------------------------------------+
   | Authors: Derick Rethans <derick@xdebug.org>                          |
   +----------------------------------------------------------------------+
 */
#include "php_xdebug.h"
#include "xdebug_private.h"
#include "xdebug_filter.h"

ZEND_EXTERN_MODULE_GLOBALS(xdebug)

int xdebug_filter_is_valid(void)
{
	return 0;
}

int xdebug_is_stack_frame_filtered(int filter_type, function_stack_entry *fse)
{
	switch (filter_type) {
		case XDEBUG_FILTER_TRACING:
			return fse->filtered_tracing;

		case XDEBUG_FILTER_CODE_COVERAGE:
			return fse->filtered_code_coverage;
	}

	return 0;
}

int xdebug_is_top_stack_frame_filtered(int filter_type)
{
	function_stack_entry *fse;
	fse = XDEBUG_LLIST_VALP(XDEBUG_LLIST_TAIL(XG(stack)));
	return xdebug_is_stack_frame_filtered(filter_type, fse);
}

void xdebug_filter_register_constants(INIT_FUNC_ARGS)
{
	REGISTER_LONG_CONSTANT("XDEBUG_FILTER_TRACING", XDEBUG_FILTER_TRACING, CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("XDEBUG_FILTER_CODE_COVERAGE", XDEBUG_FILTER_CODE_COVERAGE, CONST_CS | CONST_PERSISTENT);

	REGISTER_LONG_CONSTANT("XDEBUG_FILTER_NONE", XDEBUG_FILTER_NONE, CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("XDEBUG_PATH_WHITELIST", XDEBUG_PATH_WHITELIST, CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("XDEBUG_PATH_BLACKLIST", XDEBUG_PATH_BLACKLIST, CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("XDEBUG_NAMESPACE_WHITELIST", XDEBUG_NAMESPACE_WHITELIST, CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("XDEBUG_NAMESPACE_BLACKLIST", XDEBUG_NAMESPACE_BLACKLIST, CONST_CS | CONST_PERSISTENT);
}

int xdebug_filter_match_path_whitelist(function_stack_entry *fse, long *filtered_flag, char *filter)
{
	if (strncasecmp(filter, fse->filename, strlen(filter)) == 0) {
		*filtered_flag = 0;
		return 1;
	}
	return 0;
}

int xdebug_filter_match_path_blacklist(function_stack_entry *fse, long *filtered_flag, char *filter)
{
	if (strncasecmp(filter, fse->filename, strlen(filter)) == 0) {
		*filtered_flag = 1;
		return 1;
	}
	return 0;
}

int xdebug_filter_match_namespace_whitelist(function_stack_entry *fse, long *filtered_flag, char *filter)
{
	if (!fse->function.class && strlen(filter) == 0) {
		*filtered_flag = 0;
		return 1;
	}
	if (fse->function.class && strlen(filter) > 0 && strncasecmp(filter, fse->function.class, strlen(filter)) == 0) {
		*filtered_flag = 0;
		return 1;
	}
	return 0;
}

int xdebug_filter_match_namespace_blacklist(function_stack_entry *fse, long *filtered_flag, char *filter)
{
	if (!fse->function.class && strlen(filter) == 0) {
		*filtered_flag = 1;
		return 1;
	}
	if (fse->function.class && strlen(filter) > 0 && strncasecmp(filter, fse->function.class, strlen(filter)) == 0) {
		*filtered_flag = 1;
		return 1;
	}
	return 0;
}


static void xdebug_filter_run_internal(function_stack_entry *fse, int group, long *filtered_flag, int type, xdebug_llist *filters)
{
	xdebug_llist_element *le;
	unsigned int          k;
	function_stack_entry  tmp_fse;
	int (*filter_to_run)(function_stack_entry *fse, long *filtered_flag, char *filter);

	le = XDEBUG_LLIST_HEAD(filters);

	switch (type) {
		case XDEBUG_PATH_WHITELIST:
			*filtered_flag = 1;
			if (group == XDEBUG_FILTER_CODE_COVERAGE && fse->function.type & XFUNC_INCLUDES) {
				tmp_fse.filename = fse->include_filename;
				fse = &tmp_fse;
			}

			filter_to_run = xdebug_filter_match_path_whitelist;
			break;

		case XDEBUG_PATH_BLACKLIST:
			*filtered_flag = 0;
			if (group == XDEBUG_FILTER_CODE_COVERAGE && fse->function.type & XFUNC_INCLUDES) {
				tmp_fse.filename = fse->include_filename;
				fse = &tmp_fse;
			}

			filter_to_run = xdebug_filter_match_path_blacklist;
			break;

		case XDEBUG_NAMESPACE_WHITELIST:
			*filtered_flag = 1;
			filter_to_run = xdebug_filter_match_namespace_whitelist;
			break;

		case XDEBUG_NAMESPACE_BLACKLIST:
			*filtered_flag = 0;
			filter_to_run = xdebug_filter_match_namespace_blacklist;
			break;

		default:
			/* Logically can't happen, but compilers can't detect that */
			return;
	}

	for (k = 0; k < filters->size; k++, le = XDEBUG_LLIST_NEXT(le)) {
		char *filter = XDEBUG_LLIST_VALP(le);

		/* If the filter matched once, we're done */
		if (filter_to_run(fse, filtered_flag, filter)) {
			break;
		}
	}
}

void xdebug_filter_run_tracing(function_stack_entry *fse)
{
	fse->filtered_tracing = 0;

	if (XG(filter_type_tracing) != XDEBUG_FILTER_NONE) {
		xdebug_filter_run_internal(fse, XDEBUG_FILTER_TRACING, &fse->filtered_tracing, XG(filter_type_tracing), XG(filters_tracing));
	}
}

void xdebug_filter_run_code_coverage(zend_op_array *op_array)
{
	op_array->reserved[XG(dead_code_analysis_tracker_offset)] = 0;
	
	if (XG(filter_type_code_coverage) != XDEBUG_FILTER_NONE) {
		function_stack_entry tmp_fse;

		tmp_fse.filename = STR_NAME_VAL(op_array->filename);
		xdebug_build_fname_from_oparray(&tmp_fse.function, op_array TSRMLS_CC);
		xdebug_filter_run_internal(&tmp_fse, XDEBUG_FILTER_CODE_COVERAGE, &tmp_fse.filtered_code_coverage, XG(filter_type_code_coverage), XG(filters_code_coverage));
		op_array->reserved[XG(code_coverage_filter_offset)] = (void*) tmp_fse.filtered_code_coverage;
	}
}

/* {{{ proto void xdebug_set_filter(int group, int type, array filters)
   This function configures filters for tracing and code coverage */
PHP_FUNCTION(xdebug_set_filter)
{
	zend_long      filter_group;
	zend_long      filter_type;
	xdebug_llist **filter_list;
	zval          *filters, *item;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "lla", &filter_group, &filter_type, &filters) == FAILURE) {
		return;
	}

	switch (filter_group) {
		case XDEBUG_FILTER_TRACING:
			filter_list = &XG(filters_tracing);
			XG(filter_type_tracing) = XDEBUG_FILTER_NONE;
			break;

		case XDEBUG_FILTER_CODE_COVERAGE:
			filter_list = &XG(filters_code_coverage);
			XG(filter_type_code_coverage) = XDEBUG_FILTER_NONE;
			if (filter_type == XDEBUG_NAMESPACE_WHITELIST || filter_type == XDEBUG_NAMESPACE_BLACKLIST) {
				php_error(E_WARNING, "The code coverage filter (XDEBUG_FILTER_CODE_COVERAGE) only supports the XDEBUG_PATH_WHITELIST, XDEBUG_PATH_BLACKLIST, and XDEBUG_FILTER_NONE filter types");
				return;
			}
			break;

		default:
			php_error(E_WARNING, "Filter group needs to be one of XDEBUG_FILTER_TRACING or XDEBUG_FILTER_CODE_COVERAGE");
			return;
	}

	if (
		filter_type == XDEBUG_PATH_WHITELIST ||
		filter_type == XDEBUG_PATH_BLACKLIST ||
		filter_type == XDEBUG_NAMESPACE_WHITELIST ||
		filter_type == XDEBUG_NAMESPACE_BLACKLIST ||
		filter_type == XDEBUG_FILTER_NONE
	) {
		switch (filter_group) {
			case XDEBUG_FILTER_TRACING:
				XG(filter_type_tracing) = filter_type;
				break;

			case XDEBUG_FILTER_CODE_COVERAGE:
				XG(filter_type_code_coverage) = filter_type;
				break;
		}
	} else {
		php_error(E_WARNING, "Filter type needs to be one of XDEBUG_PATH_WHITELIST, XDEBUG_PATH_BLACKLIST, XDEBUG_NAMESPACE_WHITELIST, XDEBUG_NAMESPACE_BLACKLIST, or XDEBUG_FILTER_NONE");
		return;
	}

	xdebug_llist_empty(*filter_list, NULL);

	if (filter_type == XDEBUG_FILTER_NONE) {
		return;
	}

	ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(filters), item) {
		zend_string *str = zval_get_string(item);
		char *filter = ZSTR_VAL(str);

		/* If we are a namespace filter, and the filter name starts with \, we
		 * need to strip the \ from the matcher */
		xdebug_llist_insert_next(*filter_list, XDEBUG_LLIST_TAIL(*filter_list), xdstrdup(filter[0] == '\\' ? &filter[1] : filter));

		zend_string_release(str);
	} ZEND_HASH_FOREACH_END();
}
/* }}} */
