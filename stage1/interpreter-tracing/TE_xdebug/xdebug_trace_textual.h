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
   | Authors:  Derick Rethans <derick@xdebug.org>                         |
   +----------------------------------------------------------------------+
 */
#ifndef XDEBUG_TRACE_TEXTUAL_H
#define XDEBUG_TRACE_TEXTUAL_H

#include "xdebug_tracing.h"

typedef struct _xdebug_trace_textual_context
{
	FILE *trace_file;
	char *trace_filename;
} xdebug_trace_textual_context;

extern xdebug_trace_handler_t xdebug_trace_handler_textual;
#endif
