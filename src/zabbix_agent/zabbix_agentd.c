/* 
** ZABBIX
** Copyright (C) 2000-2005 SIA Zabbix
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
**/

#include "common.h"

#include "sysinfo.h"
#include "zabbix_agent.h"

#include "cfg.h"
#include "log.h"
#include "zbxconf.h"
#include "zbxgetopt.h"
#include "comms.h"
#include "mutexs.h"
#include "alias.h"

#include "stats.h"
#include "active.h"
#include "listener.h"

#include "symbols.h"

#if defined(ZABBIX_SERVICE)
#	include "service.h"
#elif defined(ZABBIX_DAEMON) /* ZABBIX_SERVICE */
#	include "daemon.h"
#endif /* ZABBIX_DAEMON */


char *progname = NULL;

/* Default config file location */
#ifdef _WINDOWS
	static char	DEFAULT_CONFIG_FILE[]	= "C:\\zabbix_agentd.conf";
#else /* not _WINDOWS */
	static char	DEFAULT_CONFIG_FILE[]	= "/etc/zabbix/zabbix_agentd.conf";
#endif /* _WINDOWS */

/* application TITLE */

char title_message[] = APPLICATION_NAME
#if defined(_WIN64)		
				" Win64"
#elif defined(WIN32)		
				" Win32"
#endif /* WIN32 */
#if defined(ZABBIX_SERVICE)	
				" (service)"
#elif defined(ZABBIX_DAEMON)	
				" (daemon)"
#endif /* ZABBIX_SERVICE */
	;
/* end of application TITLE */


/* application USAGE message */

char usage_message[] = 
	"[-Vhp]"
#if defined(_WINDOWS)
	" [-idsx]"
#endif /* _WINDOWS */
	" [-c <file>] [-t <metric>]";

/*end of application USAGE message */



/* application HELP message */

char *help_message[] = {
	"Options:",
	"",
	"  -c --config <file>  Specify configuration file",
	"  -h --help           give this help",
	"  -V --version        display version number",
	"  -p --print          print supported metrics and exit",
	"  -t --test <metric>  test specified metric and exit",
/*	"  -u --usage <metric> test specified metric and exit",	*/ /* !!! TODO - print metric usage !!! */

#if defined (_WINDOWS)

	"",
	"Functions:",
	"",
	"  -i --install        install ZABIX agent as service",
	"  -d --uninstall      uninstall ZABIX agent from service",
	
	"  -s --start          start ZABIX agent service",
	"  -x --stop           stop ZABIX agent service",

#endif /* _WINDOWS */

	0 /* end of text */
};

/* end of application HELP message */



/* COMMAND LINE OPTIONS */

/* long options */

static struct zbx_option longopts[] =
{
	{"config",	1,	0,	'c'},
	{"help",	0,	0,	'h'},
	{"version",	0,	0,	'V'},
	{"print",	0,	0,	'p'},
	{"test",	1,	0,	't'},

#if defined (_WINDOWS)

	{"install",	0,	0,	'i'},
	{"uninstall",	0,	0,	'd'},

	{"start",	0,	0,	's'},
	{"stop",	0,	0,	'x'},

#endif /* _WINDOWS */

	{0,0,0,0}
};

/* short options */

static char	shortopts[] = 
	"c:hVpt:"
#if defined (_WINDOWS)
	"idsx"
#endif /* _WINDOWS */
	;

/* end of COMMAND LINE OPTIONS*/



static char	*TEST_METRIC = NULL;

static ZBX_THREAD_HANDLE	*threads = NULL;

static zbx_task_t parse_commandline(int argc, char **argv)
{
	zbx_task_t	task	= ZBX_TASK_START;
	char	ch	= '\0';

	/* Parse the command-line. */
	while ((ch = zbx_getopt_long(argc, argv, shortopts, longopts, NULL)) != EOF)
		switch ((char) ch) {
		case 'c':
			CONFIG_FILE = strdup(zbx_optarg);
			break;
		case 'h':
			help();
			exit(-1);
			break;
		case 'V':
			version();
			exit(-1);
			break;
		case 'p':
			if(task == ZBX_TASK_START)
				task = ZBX_TASK_PRINT_SUPPORTED;
			break;
		case 't':
			if(task == ZBX_TASK_START) 
			{
				task = ZBX_TASK_TEST_METRIC;
				TEST_METRIC = strdup(zbx_optarg);
			}
			break;

#if defined (_WINDOWS)
		case 'i':
			task = ZBX_TASK_INSTALL_SERVICE;
			break;
		case 'd':
			task = ZBX_TASK_UNINSTALL_SERVICE;
			break;
		case 's':
			task = ZBX_TASK_START_SERVICE;
			break;
		case 'x':
			task = ZBX_TASK_STOP_SERVICE;
			break;

#endif /* _WINDOWS */

		default:
			task = ZBX_TASK_SHOW_USAGE;
			break;
	}

	if(CONFIG_FILE == NULL)
	{
		CONFIG_FILE = DEFAULT_CONFIG_FILE;
	}

	return task;
}

int MAIN_ZABBIX_ENTRY(void)
{
	ZBX_THREAD_ACTIVECHK_ARGS	activechk_args;

	int	i = 0;

	zbx_sock_t	listen_sock;

	zabbix_open_log(
#if ON	/* !!! normal case must be ON !!! */
		LOG_TYPE_FILE
#elif OFF	/* !!! normal case must be OFF !!! */
		LOG_TYPE_SYSLOG
#else	/* !!! for debug only, print log with zbx_error !!! */ 
		LOG_TYPE_UNDEFINED
#endif
		,
		CONFIG_LOG_LEVEL,
		CONFIG_LOG_FILE
		);

	zabbix_log(LOG_LEVEL_INFORMATION, "zabbix_agentd started. ZABBIX %s.", ZABBIX_VERSION);

	if( FAIL == zbx_tcp_listen(&listen_sock, CONFIG_LISTEN_IP, (unsigned short)CONFIG_LISTEN_PORT) )
	{
		zabbix_log(LOG_LEVEL_CRIT, "Listener failed with error: %s.", zbx_tcp_strerror());
		exit(1);
	}

	init_collector_data();

	/* --- START THREADS ---*/

	threads = calloc(CONFIG_ZABBIX_FORKS, sizeof(ZBX_THREAD_HANDLE));

	threads[i=0] = zbx_thread_start(collector_thread, NULL);

	/* start listeners */
	for(i++; i < CONFIG_ZABBIX_FORKS - ((0 == CONFIG_DISABLE_ACTIVE) ? 1 : 0); i++)
	{
		threads[i] = zbx_thread_start(listener_thread, &listen_sock);
	}

	/* start active chack */
	if(0 == CONFIG_DISABLE_ACTIVE)
	{
		activechk_args.host = CONFIG_HOSTS_ALLOWED;
		activechk_args.port = (unsigned short)CONFIG_SERVER_PORT;

		threads[i] = zbx_thread_start(active_checks_thread, &activechk_args);
	}

	/* Must be called after all child processes loading. */
	init_main_process();

	/* wait for all threads exiting */
	for(i = 0; i < CONFIG_ZABBIX_FORKS; i++)
	{
		zbx_thread_wait(threads[i]);

		zabbix_log( LOG_LEVEL_DEBUG, "%li: thread is terminated", threads[i]);
		ZBX_DO_EXIT();
	}

	free_collector_data();

	zbx_free(threads);

	zbx_on_exit();

	return SUCCEED;
}

void	zbx_on_exit()
{

#if !defined(_WINDOWS)
	
	int i = 0;

	if(threads != NULL)
	{
		for(i = 0; i<CONFIG_ZABBIX_FORKS ; i++)
		{
			if(threads[i]) {
				kill(threads[i],SIGTERM);
				threads[i] = (ZBX_THREAD_HANDLE)NULL;
			}
		}
	}
	
#endif /* not _WINDOWS */
	
	zabbix_log(LOG_LEVEL_DEBUG, "zbx_on_exit() called.");

#ifdef USE_PID_FILE

	daemon_stop();

#endif /* USE_PID_FILE */

	free_collector_data();
	alias_list_free();

	zbx_sleep(2); /* wait for all threads closing */
	
	zabbix_log(LOG_LEVEL_INFORMATION, "ZABBIX Agent stopped");
	zabbix_close_log();

	exit(SUCCEED);
}

#ifndef ZABBIX_TEST

int	main(int argc, char **argv)
{
	int task = ZBX_TASK_START;

	progname = get_programm_name(argv[0]);

	task = parse_commandline(argc, argv);

	import_symbols();

	init_metrics(); /* Must be before load_config().  load_config - use metrics!!! */

	if( ZBX_TASK_START == task )
		load_config();

	load_user_parameters();

	switch(task)
	{

#if defined (_WINDOWS)
		case ZBX_TASK_INSTALL_SERVICE:
			exit(ZabbixCreateService(argv[0]));
			break;
		case ZBX_TASK_UNINSTALL_SERVICE:
			exit(ZabbixRemoveService());
			break;
		case ZBX_TASK_START_SERVICE:
			exit(ZabbixStartService());
			break;
		case ZBX_TASK_STOP_SERVICE:
			exit(ZabbixStopService());
			break;
#endif /* _WINDOWS */
		case ZBX_TASK_PRINT_SUPPORTED:
			test_parameters();
			exit(SUCCEED);
			break;
		case ZBX_TASK_TEST_METRIC:
			test_parameter(TEST_METRIC);
			exit(SUCCEED);
			break;
		case ZBX_TASK_SHOW_USAGE:
			usage();
			exit(FAIL);
			break;
	}

	START_MAIN_ZABBIX_ENTRY(CONFIG_ALLOW_ROOT);

	exit(SUCCEED);
}

#else /* ZABBIX_TEST */
/* #	define ENABLE_CHECK_MEMOTY 1 */

#	if defined(ENABLE_CHECK_MEMOTY)
#		include "crtdbg.h"

#		define REINIT_CHECK_MEMORY(a) \
			_CrtMemCheckpoint(& ## a ## oldMemState)

#		define INIT_CHECK_MEMORY(a) \
			char a ## DumpMessage[0xFFFF]; \
			_CrtMemState  a ## oldMemState, a ## newMemState, a ## diffMemState; \
			REINIT_CHECK_MEMORY(a)

#		define CHECK_MEMORY(a, fncname, msg) \
			_CrtMemCheckpoint(& ## a ## newMemState); \
			if(_CrtMemDifference(& ## a ## diffMemState, & ## a ## oldMemState, & ## a ## newMemState)) \
			{ \
				zbx_snprintf(a ## DumpMessage, sizeof(a ## DumpMessage), \
					"%s\n" \
					"free:  %10li bytes in %10li blocks\n" \
					"normal:%10li bytes in %10li blocks\n" \
					"CRT:   %10li bytes in %10li blocks\n" \
					"ignore:%10li bytes in %10li blocks\n" \
					"client:%10li bytes in %10li blocks\n" \
					"max:   %10li bytes in %10li blocks", \
					 \
					fncname ": (" #a ") Memory changed! (" msg ")\n", \
					 \
					(long) a ## diffMemState.lSizes[_FREE_BLOCK], \
					(long) a ## diffMemState.lCounts[_FREE_BLOCK], \
					 \
					(long) a ## diffMemState.lSizes[_NORMAL_BLOCK], \
					(long) a ## diffMemState.lCounts[_NORMAL_BLOCK], \
					 \
					(long) a ## diffMemState.lSizes[_CRT_BLOCK], \
					(long) a ## diffMemState.lCounts[_CRT_BLOCK], \
					 \
					(long) a ## diffMemState.lSizes[_IGNORE_BLOCK], \
					(long) a ## diffMemState.lCounts[_IGNORE_BLOCK], \
					 \
					(long) a ## diffMemState.lSizes[_CLIENT_BLOCK], \
					(long) a ## diffMemState.lCounts[_CLIENT_BLOCK], \
					 \
					(long) a ## diffMemState.lSizes[_MAX_BLOCKS], \
					(long) a ## diffMemState.lCounts[_MAX_BLOCKS]); \
				 SDI2("%s", a ## DumpMessage); \
			} \
			else \
			{ \
				SDI("NO leak"); \
			}
#	else
#		define INIT_CHECK_MEMORY(a) ((void)0)
#		define CHECK_MEMORY(a, fncname, msg) ((void)0)
#	endif

#if defined(_WINDOWS)
#	include "messages.h"
#endif /* _WINDOWS */

int main()
{
#if ON
	#include "eventlog.h"

	int i = 0;
	long			lastlogsize = 0;
	char			*source = NULL;
	unsigned short	severity;
	char			*message;
	char			*request;
	unsigned long	timestamp = 111;

	char
		host_dec[MAX_STRING_LEN],
		key_dec[MAX_STRING_LEN],
		value_dec[MAX_STRING_LEN],
		lastlogsize_dec[MAX_STRING_LEN],
		timestamp_dec[MAX_STRING_LEN],
		source_dec[MAX_STRING_LEN],
		severity_dec[MAX_STRING_LEN];

INIT_CHECK_MEMORY(main);

	while(0 == process_eventlog("system",&lastlogsize,&timestamp,&source,&severity,&message))
	{
/*	SDI2("lastlogsize:[%li]", lastlogsize);
	SDI2("timestamp: [%li]", timestamp);
	SDI2("source:     [%s]", source);
	SDI2("severity:   [%u]", severity);
	SDI2("message:    [%s]", message);*/

	request = comms_create_request("real_host", "key.xxx1", message, &lastlogsize, &timestamp, source, &severity);
//	SDI2("request: [%s]", request);


	comms_parse_response(request,host_dec,key_dec,value_dec,lastlogsize_dec,timestamp_dec,source_dec,severity_dec,sizeof(host_dec)-1);
/*	SDI2("host_dec:         [%s]", host_dec);
	SDI2("key_dec:          [%s]", key_dec);
	SDI2("value_dec:        [%s]", value_dec);
	SDI2("lastlogsize_dec:  [%s]", lastlogsize_dec);
	SDI2("timestamp_dec:    [%s]", timestamp_dec);
	SDI2("source_dec:       [%s]", source_dec);
	SDI2("severity_dec:     [%s]", severity_dec);*/
SDI(source);
	if( strcmp(message,value_dec) ) SDI2("ERRROR on: %li", lastlogsize);
	zbx_free(request);
	zbx_free(source);
	zbx_free(message);
	//if(i++>100) 
	//	break;
	}

CHECK_MEMORY(main, "main","end");
SDI("===========END=============");

#elif OFF
	AGENT_RESULT    result;
	
	init_result(&result);

	SET_UI64_RESULT(&result, 123456789123456789);
	printf("UI: '" ZBX_FS_UI64 "'\n", result.ui64);

	printf("UI_TO_DBL: '" ZBX_FS_DBL "'\n", *GET_DBL_RESULT(&result));

	UNSET_RESULT_EXCLUDING(&result, AR_UINT64);
	printf("UI_TO_STR: '%s'\n", *GET_STR_RESULT(&result));

	UNSET_RESULT_EXCLUDING(&result, AR_UINT64);
	printf("UI_TO_TEXT: '%s'\n", *GET_TEXT_RESULT(&result));

	UNSET_RESULT_EXCLUDING(&result, AR_UINT64);
	printf("UI_TO_UI64: '" ZBX_FS_UI64 "'\n", *GET_UI64_RESULT(&result));

#elif OFF
	int res, val;

	if(FAIL == zbx_sock_init())
	{
		return 1;
	}


	res = check_ntp("142.3.100.15",123,&val);

	zbx_error("check_ntp result '%i' value '%i'", res, val);

#elif OFF

	zbx_error("%s",strerror_from_module(MSG_ZABBIX_MESSAGE, NULL));

#elif OFF
	
	char buffer[100*1024];

	get_http_page("www.zabbix.com", "", 80, buffer, 100*1024);

	printf("Back [%d] [%s]\n", strlen(buffer), buffer);
	
#elif OFF

	char s[] = "ABCDEFGH";
	char p[] = "D(.){0,}E";
	int len=2;

	printf("String: \t %s\n", s);
	printf("Pattern:\t %s\n", p);
	printf("Result: \t [%s] [%d]\n", zbx_regexp_match(s, p, &len), len);
/*
#elif OFF or ON

Place your test code HERE!!!
*/

#endif /* 0 */

	return 0;
}

#endif /* not ZABBIX_TEST */

