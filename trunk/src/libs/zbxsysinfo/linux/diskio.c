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

#include "config.h"

#include "common.h"
#include "sysinfo.h"

int	DISKREADOPS1(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	char	key[MAX_STRING_LEN];

	snprintf(key,sizeof(key)-1,"disk_read_ops1[%s]",param);

	return	get_stat(key, flags, result);
}

int	DISKREADOPS5(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	char	key[MAX_STRING_LEN];

	snprintf(key,sizeof(key)-1,"disk_read_ops5[%s]",param);

	return	get_stat(key, flags, result);
}

int	DISKREADOPS15(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	char	key[MAX_STRING_LEN];

	snprintf(key,sizeof(key)-1,"disk_read_ops15[%s]",param);

	return	get_stat(key, flags, result);
}

int	DISKREADBLKS1(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	char	key[MAX_STRING_LEN];

	snprintf(key,sizeof(key)-1,"disk_read_blks1[%s]",param);

	return	get_stat(key, flags, result);
}

int	DISKREADBLKS5(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	char	key[MAX_STRING_LEN];

	snprintf(key,sizeof(key)-1,"disk_read_blks5[%s]",param);

	return	get_stat(key, flags, result);
}

int	DISKREADBLKS15(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	char	key[MAX_STRING_LEN];

	snprintf(key,sizeof(key)-1,"disk_read_blks15[%s]",param);

	return	get_stat(key, flags, result);
}

int	DISKWRITEOPS1(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	char	key[MAX_STRING_LEN];

	snprintf(key,sizeof(key)-1,"disk_write_ops1[%s]",param);

	return	get_stat(key, flags, result);
}

int	DISKWRITEOPS5(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	char	key[MAX_STRING_LEN];

	snprintf(key,sizeof(key)-1,"disk_write_ops5[%s]",param);

	return	get_stat(key, flags, result);
}

int	DISKWRITEOPS15(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	char	key[MAX_STRING_LEN];

	snprintf(key,sizeof(key)-1,"disk_write_ops15[%s]",param);

	return	get_stat(key, flags, result);
}

int	DISKWRITEBLKS1(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	char	key[MAX_STRING_LEN];

	snprintf(key,sizeof(key)-1,"disk_write_blks1[%s]",param);

	return	get_stat(key, flags, result);
}

int	DISKWRITEBLKS5(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	char	key[MAX_STRING_LEN];

	snprintf(key,sizeof(key)-1,"disk_write_blks5[%s]",param);

	return	get_stat(key, flags, result);
}

int	DISKWRITEBLKS15(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	char	key[MAX_STRING_LEN];

	snprintf(key,sizeof(key)-1,"disk_write_blks15[%s]",param);

	return	get_stat(key, flags, result);
}

int	DISK_IO(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
#ifdef	HAVE_PROC
	return	getPROC("/proc/stat",2,2, flags, result);
#else
	return	SYSINFO_RET_FAIL;
#endif
}

int	DISK_RIO(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
#ifdef	HAVE_PROC
	return	getPROC("/proc/stat",3,2, flags, result);
#else
	return	SYSINFO_RET_FAIL;
#endif
}

int	DISK_WIO(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
#ifdef	HAVE_PROC
	return	getPROC("/proc/stat",4,2, flags, result);
#else
	return	SYSINFO_RET_FAIL;
#endif
}

int	DISK_RBLK(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
#ifdef	HAVE_PROC
	return	getPROC("/proc/stat",5,2, flags, result);
#else
	return	SYSINFO_RET_FAIL;
#endif
}

int	DISK_WBLK(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
#ifdef	HAVE_PROC
	return	getPROC("/proc/stat",6,2, flags, result);
#else
	return	SYSINFO_RET_FAIL;
#endif
}

int	OLD_IO(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	char    key[MAX_STRING_LEN];
	int 	ret;

	assert(result);

        memset(result, 0, sizeof(AGENT_RESULT));

        if(num_param(param) > 1)
        {
                return SYSINFO_RET_FAIL;
        }

        if(get_param(param, 1, key, MAX_STRING_LEN) != 0)
        {
                return SYSINFO_RET_FAIL;
        }

	if(strcmp(key,"disk_io") == 0)
	{
		ret = DISK_IO(cmd, param, flags, result);
	}
	else if(strcmp(key,"disk_rio") == 0)
	{
		ret = DISK_RIO(cmd, param, flags, result);
	}
	else if(strcmp(key,"disk_wio") == 0)
	{
		ret = DISK_WIO(cmd, param, flags, result);
	}
    	else if(strcmp(key,"disk_rblk") == 0)
	{
		ret = DISK_RBLK(cmd, param, flags, result);
	}
    	else if(strcmp(key,"disk_wblk") == 0)
	{
		ret = DISK_WBLK(cmd, param, flags, result);
	}
	else
	{
		ret = SYSINFO_RET_FAIL;
	}
    
	return ret;
}

