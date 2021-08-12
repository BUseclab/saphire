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

#include <stdarg.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <locale.h>

#include "php.h"
#include "ext/standard/php_string.h"

#include "xdebug_mm.h"
#include "xdebug_str.h"

void xdebug_str_add(xdebug_str *xs, const char *str, int f)
{
	int l = strlen(str);

	if (xs->l + l > xs->a - 1) {
		xs->d = xdrealloc(xs->d, xs->a + l + XDEBUG_STR_PREALLOC);
		xs->a = xs->a + l + XDEBUG_STR_PREALLOC;
	}
	if (!xs->l) {
		xs->d[0] = '\0';
	}
	memcpy(xs->d + xs->l, str, l);
	xs->d[xs->l + l] = '\0';
	xs->l = xs->l + l;
	if (f) {
		xdfree((char*) str);
	}
}

void xdebug_str_addl(xdebug_str *xs, const char *str, int le, int f)
{
	if (xs->l + le > xs->a - 1) {
		xs->d = xdrealloc(xs->d, xs->a + le + XDEBUG_STR_PREALLOC);
		xs->a = xs->a + le + XDEBUG_STR_PREALLOC;
	}
	if (!xs->l) {
		xs->d[0] = '\0';
	}
	memcpy(xs->d + xs->l, str, le);
	xs->d[xs->l + le] = '\0';
	xs->l = xs->l + le;

	if (f) {
		xdfree((char*) str);
	}
}

void xdebug_str_add_str(xdebug_str *xs, const xdebug_str *str)
{
	if (xs->l + str->l > xs->a - 1) {
		xs->d = xdrealloc(xs->d, xs->a + str->l + XDEBUG_STR_PREALLOC);
		xs->a = xs->a + str->l + XDEBUG_STR_PREALLOC;
	}
	if (!xs->l) {
		xs->d[0] = '\0';
	}
	memcpy(xs->d + xs->l, str->d, str->l);
	xs->d[xs->l + str->l] = '\0';
	xs->l = xs->l + str->l;
}

void xdebug_str_addc(xdebug_str *xs, char letter)
{
	if (xs->l + 1 > xs->a - 1) {
		xs->d = xdrealloc(xs->d, xs->a + 1 + XDEBUG_STR_PREALLOC);
		xs->a = xs->a + 1 + XDEBUG_STR_PREALLOC;
	}
	if (!xs->l) {
		xs->d[0] = '\0';
	}
	xs->d[xs->l] = letter;
	xs->d[xs->l + 1] = '\0';
	xs->l = xs->l + 1;
}

void xdebug_str_chop(xdebug_str *xs, int c)
{
	if (c > xs->l) {
		/* Do nothing if the chop amount is larger than the buffer size */
	} else {
		xs->l -= c;
		xs->d[xs->l] = '\0';
	}
}

xdebug_str *xdebug_str_new(void)
{
	xdebug_str *tmp = xdmalloc(sizeof(xdebug_str));
	
	tmp->l = 0;
	tmp->a = 0;
	tmp->d = NULL;

	return tmp;
}

xdebug_str *xdebug_str_create(char *c, size_t len)
{
	xdebug_str *tmp = xdebug_str_new();

	tmp->l = tmp->a = len;
	tmp->a++;
	tmp->d = xdmalloc(tmp->a);
	memcpy(tmp->d, c, tmp->l);
	tmp->d[tmp->l] = '\0';

	return tmp;
}

xdebug_str *xdebug_str_create_from_char(char *c)
{
	return xdebug_str_create(c, strlen(c));
}

xdebug_str *xdebug_str_copy(xdebug_str *orig)
{
	xdebug_str *tmp = xdebug_str_new();

	tmp->l = tmp->a = orig->l;
	tmp->a++;
	tmp->d = xdmalloc(tmp->a);
	memcpy(tmp->d, orig->d, tmp->l);
	tmp->d[orig->l] = '\0';

	return tmp;
}

void xdebug_str_destroy(xdebug_str *s)
{
	if (s->d) {
		xdfree(s->d);
	}
}

void xdebug_str_free(xdebug_str *s)
{
	xdebug_str_destroy(s);
	xdfree(s);
}

char *xdebug_sprintf(const char* fmt, ...)
{
	char   *new_str;
	int     size = 1;
	va_list args;

	new_str = (char *) xdmalloc(size);

	for (;;) {
		int n;

		va_start(args, fmt);
		n = vsnprintf(new_str, size, fmt, args);
		va_end(args);

		if (n > -1 && n < size) {
			break;
		}
		if (n < 0) {
			size *= 2;
		} else {
			size = n + 1;
		}
		new_str = (char *) xdrealloc(new_str, size);
	}

	return new_str;
}

/**
 * Duplicate zend_strndup in core to avoid mismatches
 * in C-runtime libraries when xdebug and core are built
 * with different run-time libraries.
 */
char *xdebug_strndup(const char *s, int length)
{
	char *p;

	p = (char *) xdmalloc(length + 1);
	if (p == NULL) {
		return p;
	}
	if (length) {
		memcpy(p, s, length);
	}
	p[length] = 0;
	return p;
}
