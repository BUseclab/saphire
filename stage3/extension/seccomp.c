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
#include "php_ini.h"
#include "ext/standard/info.h"
#include "zend_builtin_functions.h"
#include "php_seccomp.h"
#include <seccomp.h>
#include <linux/filter.h>
#include <stdio.h>
#include <stdlib.h> 
#include "seccomp-bpf.h"
#include "uthash.h"


#define NUM_BUCKETS 50000
#define DELIM ' '

#ifndef SYS_SECCOMP
#define SYS_SECCOMP 1
#endif

#if defined(__i386__)
#define REG_SYSCALL REG_EAX
#define ARCH_NR AUDIT_ARCH_I386
#elif defined(__x86_64__)
#define REG_SYSCALL REG_RAX
#define ARCH_NR AUDIT_ARCH_X86_64
#else
#error "Platform does not support seccomp filter yet"
#define REG_SYSCALL 0
#define ARCH_NR 0
#endif

static int trapped = 0;

int filter[NUM_BUCKETS][333];
int filter_count[NUM_BUCKETS];
scmp_filter_ctx ctx;

#if PHP_MAJOR_VERSION < 7
void (*seccomp_old_execute_internal)(zend_execute_data *execute_data_ptr,
                                     struct _zend_fcall_info *fci,
                                     int return_value_used TSRMLS_DC);
void seccomp_execute_internal(zend_execute_data *execute_data_ptr,
                              struct _zend_fcall_info *fci,
                              int return_value_used TSRMLS_DC);
#else
void (*seccomp_old_execute_internal)(zend_execute_data *current_execute_data,
                                     zval *return_value);
void seccomp_execute_internal(zend_execute_data *current_execute_data,
                              zval *return_value);
#endif

static void seccomp_trap_handler(int signal, siginfo_t *info, void *vctx);
int seccomp_trap_install();

int seccomp_loaded = 0;

struct sock_filter_wrapper {
  struct sock_filter *filter;
  int len;
};

struct filter {
  int syscalls[333];
  char name[512];
  int len;
  UT_hash_handle hh;
};

struct filter *filters = NULL;

/* True global resources - no need for thread safety here */
static int le_seccomp;

PHP_INI_BEGIN()
		PHP_INI_ENTRY("seccomp.enable", "0", PHP_INI_ALL, NULL)
		PHP_INI_ENTRY("seccomp.profile_path", "", PHP_INI_ALL, NULL)
		PHP_INI_ENTRY("seccomp.db_path", "", PHP_INI_ALL, NULL)
		PHP_INI_ENTRY("seccomp.app_base", "", PHP_INI_ALL, NULL)
PHP_INI_END()

PHP_FUNCTION(confirm_seccomp_compiled) {
  char *arg = NULL;
  size_t arg_len, len;

  if (zend_parse_parameters(ZEND_NUM_ARGS(), "s", &arg, &arg_len) == FAILURE) {
    return;
  }
}

unsigned long hash(char *str) {
  unsigned long hash = 5381;
  int c;
  while ((c = *str++))
    hash = ((hash << 5) + hash) + c; /* hash * 33 + c */
  return hash % NUM_BUCKETS;
}

int create_seccomp_filter(char *syscalls, int row[]) {
  int i = 0;
  while (syscalls) {
    char *nums = strchr(syscalls, DELIM);
    if (nums) {
      nums[0] = '\0';
    }
    int scn = atoi(syscalls);
    row[i++] = scn;
    syscalls = nums + 1;
    if (!nums) {
      break;
    }
  }
  return i;
}

PHP_MINIT_FUNCTION(seccomp) {
  char *line = NULL;
  size_t len = 0;
  ssize_t read;

  seccomp_old_execute_internal = zend_execute_internal;
  zend_execute_internal = seccomp_execute_internal;
  REGISTER_INI_ENTRIES();

  if (!INI_STR("seccomp.enable")) {
    return SUCCESS;
  }
  FILE *fp = fopen(INI_STR("seccomp.profile_path"), "r");
  if (fp == NULL)
    return FAILURE;
  while ((read = getline(&line, &len, fp)) != -1) {
    char *nums = strchr(line, DELIM);
    if (!nums) {
      continue;
    }
    nums[0] = '\0';
    int h = hash(line);
    struct filter *s = (struct filter *)malloc(sizeof *s);
    s->len = create_seccomp_filter(nums + 1, s->syscalls);
    strcpy(s->name, line);
    HASH_ADD_STR(filters, name, s);

    // filter_count[h] += create_seccomp_filter(nums+1,
    // filter[h][filter_count[h]]);
  }

  if ((ctx = seccomp_init(SCMP_ACT_KILL)) == NULL) {
    zend_error(E_NOTICE, "unable to initialize libseccomp subsystem");
  }
  // Add the essentials
  seccomp_rule_add(ctx, SECCOMP_RET_KILL, SCMP_SYS(exit), 0);

  return SUCCESS;
}

PHP_MSHUTDOWN_FUNCTION(seccomp) {
  UNREGISTER_INI_ENTRIES();
  return SUCCESS;
}

PHP_RINIT_FUNCTION(seccomp) {
#if defined(COMPILE_DL_SECCOMP) && defined(ZTS)
  ZEND_TSRMLS_CACHE_UPDATE();
#endif
  return SUCCESS;
}

PHP_RSHUTDOWN_FUNCTION(seccomp) { return SUCCESS; }

PHP_MINFO_FUNCTION(seccomp) {
  php_info_print_table_start();
  php_info_print_table_header(2, "seccomp support", "enabled");
  php_info_print_table_end();
}

const zend_function_entry seccomp_functions[] = {
    PHP_FE(confirm_seccomp_compiled, NULL) /* For testing, remove later. */
    PHP_FE_END /* Must be the last line in seccomp_functions[] */
};

zend_module_entry seccomp_module_entry = {
    STANDARD_MODULE_HEADER,
    "seccomp",
    seccomp_functions,
    PHP_MINIT(seccomp),
    PHP_MSHUTDOWN(seccomp),
    PHP_RINIT(seccomp), /* Replace with NULL if there's nothing to do at request
                           start */
    PHP_RSHUTDOWN(seccomp), /* Replace with NULL if there's nothing to do at
                               request end */
    PHP_MINFO(seccomp),
    PHP_SECCOMP_VERSION,
    STANDARD_MODULE_PROPERTIES};

int load_seccomp_profile() {
  const char *script_path;
  HashTable *_SERVER;
  _SERVER = Z_ARRVAL(PG(http_globals)[TRACK_VARS_SERVER]);
  if(!_SERVER)
	  script_path = zend_get_executed_filename();
  else {
	  zval *data = zend_hash_str_find(_SERVER, "SCRIPT_FILENAME", sizeof("SCRIPT_FILENAME")-1);
	  script_path= Z_STRVAL_P(data);
  }
  if (strlen(script_path) < strlen(INI_STR("seccomp.app_base"))) {
    return -1;
  }
  char *relative_path = script_path + strlen(INI_STR("seccomp.app_base"));


  int h = hash(relative_path);

  int sc_rc;

  if (!INI_INT("seccomp.enable"))
    return -1;

  struct filter *s = (struct filter *)malloc(sizeof *s);
  HASH_FIND_STR(filters, relative_path, s);
  // FPM 7.1-specific syscalls
  seccomp_rule_add(ctx, SCMP_ACT_ALLOW, 100, 0);
  seccomp_rule_add(ctx, SCMP_ACT_ALLOW, 80, 0);
  seccomp_rule_add(ctx, SCMP_ACT_ALLOW, 38, 0);
  seccomp_rule_add(ctx, SCMP_ACT_ALLOW, 273, 0);
  seccomp_rule_add(ctx, SCMP_ACT_ALLOW, 48, 0);
  if (s) {
    for (int i = 0; i < s->len; i++) {
      seccomp_rule_add(ctx, SCMP_ACT_ALLOW, s->syscalls[i], 0);
    }
  }

  sc_rc = seccomp_load(ctx);

  return sc_rc;
}

void seccomp_execute_internal(zend_execute_data *current_execute_data,
                              zval *return_value) {
  if (!seccomp_loaded && INI_STR("seccomp.enable")) {
    load_seccomp_profile();
    seccomp_loaded = 1;
  } else {
    zend_execute_internal = seccomp_old_execute_internal;
  }
  if (seccomp_old_execute_internal) {
    seccomp_old_execute_internal(current_execute_data, return_value TSRMLS_CC);
  } else {
    execute_internal(current_execute_data, return_value TSRMLS_CC);
  }
}

#ifdef COMPILE_DL_SECCOMP
#ifdef ZTS
ZEND_TSRMLS_CACHE_DEFINE()
#endif
ZEND_GET_MODULE(seccomp)
#endif

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
