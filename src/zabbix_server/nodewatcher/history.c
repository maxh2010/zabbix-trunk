/* 
** ZABBIX
** Copyright (C) 2000-2006 SIA Zabbix
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

#include "config.h"

#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <sys/socket.h>
#include <netinet/in.h>

#include <sys/wait.h>

#include <string.h>

#ifdef HAVE_NETDB_H
	#include <netdb.h>
#endif

/* Required for getpwuid */
#include <pwd.h>

#include <signal.h>
#include <errno.h>

#include <time.h>

#include "common.h"
#include "cfg.h"
#include "db.h"
#include "log.h"
#include "zlog.h"

#include "nodecomms.h"
#include "history.h"

extern	int	CONFIG_NODEID;

/******************************************************************************
 *                                                                            *
 * Function: process_node_history_str                                         *
 *                                                                            *
 * Purpose: process new history_str data                                      *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value: SUCCESS - processed succesfully                              * 
 *               FAIL - an error occured                                      *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static int process_node_history_str(int nodeid, int master_nodeid)
{
	DB_RESULT	result;
	DB_ROW		row;
	char		*data;
	char		sql[MAX_STRING_LEN];
	int		found = 0;
	int		offset = 0;
	int		allocated = 1024*1024;

	zbx_uint64_t	id;

	zabbix_log( LOG_LEVEL_DEBUG, "In process_node_history_str(nodeid:%d, master_nodeid:%d",nodeid, master_nodeid);
	/* Begin work */

	data = malloc(allocated);
	memset(data,0,allocated);

//	i = zbx_snprintf(data,DATA_MAX,"History|%d|%d\n", CONFIG_NODEID, nodeid);
//	p+=i;
	zbx_snprintf_alloc(&data, &allocated, &offset, 128, "History|%d|%d", CONFIG_NODEID, nodeid);

	zbx_snprintf(sql,sizeof(sql),"select id,itemid,clock,value from history_str_sync where nodeid=%d order by id", nodeid);

	result = DBselectN(sql, 10000);
	while((row=DBfetch(result)))
	{
		ZBX_STR2UINT64(id,row[0])
		found = 1;
//		i = zbx_snprintf(p,DATA_MAX,"%d|%s|%s|%s\n", ZBX_TABLE_HISTORY_STR,row[1],row[2],row[3]);
//		p+=i;
//		if(p>data+DATA_MAX-1) break;
		zbx_snprintf_alloc(&data, &allocated, &offset, 1024, "\n%d|%s|%s|%s",
			       ZBX_TABLE_HISTORY_STR,row[1],row[2],row[3]);
	}
	if(found == 1)
	{
//		zabbix_log( LOG_LEVEL_WARNING, "Sending [%s]",data);
		if(send_to_node(master_nodeid, nodeid, data) == SUCCEED)
		{
//			zabbix_log( LOG_LEVEL_WARNING, "Updating nodes.history_lastid");
			DBexecute("update nodes set history_str_lastid=" ZBX_FS_UI64 " where nodeid=%d", id, nodeid);
			DBexecute("delete from history_str_sync where nodeid=%d and id<=" ZBX_FS_UI64, nodeid, id);
		}
		else
		{
			zabbix_log( LOG_LEVEL_DEBUG, "Not updating nodes.history_str_lastid");
		}
	}
	DBfree_result(result);
	free(data);

	return SUCCEED;
}

/******************************************************************************
 *                                                                            *
 * Function: process_node_history_uint                                        *
 *                                                                            *
 * Purpose: process new history_uint data                                     *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value: SUCCESS - processed succesfully                              * 
 *               FAIL - an error occured                                      *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static int process_node_history_uint(int nodeid, int master_nodeid)
{
	DB_RESULT	result;
	DB_ROW		row;
	char		*data;
	char		sql[MAX_STRING_LEN];
	int		found = 0;
	int		offset = 0;
	int		allocated = 1024*1024;

	int start, end;

	zbx_uint64_t	id;

	zabbix_log( LOG_LEVEL_DEBUG, "In process_node_history_uint(nodeid:%d, master_nodeid:%d)",nodeid, master_nodeid);
	/* Begin work */

	start = time(NULL);

	data = malloc(allocated);
	memset(data,0,allocated);

//	p = data;
//	i = zbx_snprintf(data,DATA_MAX,"History|%d|%d\n", CONFIG_NODEID, nodeid);
//	p+=i;

	zbx_snprintf_alloc(&data, &allocated, &offset, 128, "History|%d|%d\n", CONFIG_NODEID, nodeid);

	zbx_snprintf(sql,sizeof(sql),"select id,itemid,clock,value from history_uint_sync where nodeid=%d order by id", nodeid);

	result = DBselectN(sql, 100000);
	while((row=DBfetch(result)))
	{
		ZBX_STR2UINT64(id,row[0])
		found = 1;
//		i = zbx_snprintf(p,DATA_MAX,"%d|%s|%s|%s\n", ZBX_TABLE_HISTORY_UINT,row[1],row[2],row[3]);
//		p+=i;
//		if(p>data+DATA_MAX-1) break;
		zbx_snprintf_alloc(&data, &allocated, &offset, 128, "%d|%s|%s|%s\n",
			       ZBX_TABLE_HISTORY_UINT,row[1],row[2],row[3]);
	}
	if(found == 1)
	{
//		zabbix_log( LOG_LEVEL_WARNING, "Sending [%s]",data);
		if(send_to_node(master_nodeid, nodeid, data) == SUCCEED)
		{
//			zabbix_log( LOG_LEVEL_WARNING, "Updating nodes.history_lastid");
			DBexecute("update nodes set history_uint_lastid=" ZBX_FS_UI64 " where nodeid=%d", id, nodeid);
			DBexecute("delete from history_uint_sync where nodeid=%d and id<=" ZBX_FS_UI64, nodeid, id);
		}
		else
		{
			zabbix_log( LOG_LEVEL_DEBUG, "Not updating nodes.history_uint_lastid");
		}
	}
	DBfree_result(result);
	free(data);

	end = time(NULL);

	zabbix_log( LOG_LEVEL_DEBUG, "Spent %d seconds in process_node_history_uint",end-start);

	return SUCCEED;
}

/******************************************************************************
 *                                                                            *
 * Function: process_node_history                                             *
 *                                                                            *
 * Purpose: process new history data                                          *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value: SUCCESS - processed succesfully                              * 
 *               FAIL - an error occured                                      *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static int process_node_history(int nodeid, int master_nodeid)
{
	DB_RESULT	result;
	DB_ROW		row;
	char		*data;
	char		sql[MAX_STRING_LEN];
	int		found = 0;
	int		offset = 0;
	int		allocated = 1024*1024;

	int		start, end;

	zbx_uint64_t	id;

	zabbix_log( LOG_LEVEL_DEBUG, "In process_node_history(nodeid:%d, master_nodeid:%d",nodeid, master_nodeid);
	/* Begin work */
	start = time(NULL);

	data = malloc(allocated);
	memset(data,0,allocated);

//	p = data;
//	i = zbx_snprintf(data,DATA_MAX,"History|%d|%d\n", CONFIG_NODEID, nodeid);
//	p+=i;
	zbx_snprintf_alloc(&data, &allocated, &offset, 128, "History|%d|%d\n", CONFIG_NODEID, nodeid);

	zbx_snprintf(sql,sizeof(sql),"select id,itemid,clock,value from history_sync where nodeid=%d order by id", nodeid);

	result = DBselectN(sql, 100000);
	while((row=DBfetch(result)))
	{
		ZBX_STR2UINT64(id,row[0])
		found = 1;
//		i = zbx_snprintf(p,DATA_MAX,"%d|%s|%s|%s\n", ZBX_TABLE_HISTORY,row[1],row[2],row[3]);
//		p+=i;
//		if(p>data+DATA_MAX-1) break;
		zbx_snprintf_alloc(&data, &allocated, &offset, 128, "%d|%s|%s|%s\n",
			       ZBX_TABLE_HISTORY,row[1],row[2],row[3]);
	}
	if(found == 1)
	{
		zabbix_log( LOG_LEVEL_DEBUG, "Sending [%s]",data);
		if(send_to_node(master_nodeid, nodeid, data) == SUCCEED)
		{
//			zabbix_log( LOG_LEVEL_WARNING, "Updating nodes.history_lastid=" ZBX_FS_UI64, id);
			DBexecute("update nodes set history_lastid=" ZBX_FS_UI64 " where nodeid=%d", id, nodeid);
			DBexecute("delete from history_sync where nodeid=%d and id<=" ZBX_FS_UI64, nodeid, id);
		}
		else
		{
			zabbix_log( LOG_LEVEL_DEBUG, "Not updating nodes.history_lastid");
		}
	}
	DBfree_result(result);
	free(data);

	end = time(NULL);

	zabbix_log( LOG_LEVEL_DEBUG, "Spent %d seconds in process_node_history",end-start);

	return SUCCEED;
}

/******************************************************************************
 *                                                                            *
 * Function: process_node                                                     *
 *                                                                            *
 * Purpose: process all history tables for this node                          *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value: SUCCESS - processed succesfully                              * 
 *               FAIL - an error occured                                      *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
static void process_node(int nodeid, int master_nodeid)
{
	zabbix_log( LOG_LEVEL_DEBUG, "In process_node(local:%d, master_nodeid:" ZBX_FS_UI64 ")",nodeid, master_nodeid);

	process_node_history(nodeid, master_nodeid);
	process_node_history_uint(nodeid, master_nodeid);
	process_node_history_str(nodeid, master_nodeid);
/*	process_node_history_log(nodeid, master_nodeid);
	process_node_trends(nodeid, master_nodeid);*/
}

/******************************************************************************
 *                                                                            *
 * Function: main_eventsender                                                 *
 *                                                                            *
 * Purpose: periodically sends new events to master node                      *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value:                                                              * 
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments: never returns                                                    *
 *                                                                            *
 ******************************************************************************/
void main_historysender()
{
	DB_RESULT	result;
	DB_ROW		row;
	zbx_uint64_t	lastid;
	int		nodeid;
	int		master_nodeid;

	zabbix_log( LOG_LEVEL_DEBUG, "In main_historysender()");

	master_nodeid = get_master_node(CONFIG_NODEID);

	if(master_nodeid == 0)		return;

	result = DBselect("select nodeid from nodes");

	while((row = DBfetch(result)))
	{
		nodeid=atoi(row[0]);
		ZBX_STR2UINT64(lastid,row[1])

		process_node(nodeid, master_nodeid);
	}

	DBfree_result(result);

	zabbix_log( LOG_LEVEL_DEBUG, "In main_historysender() END");
}
