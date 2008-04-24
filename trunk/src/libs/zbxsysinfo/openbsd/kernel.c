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

int	KERNEL_MAXFILES(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	int	mib[2];
	size_t	len;
	int	maxfiles;

	assert(result);

	init_result(result);

	mib[0] = CTL_KERN;
	mib[1] = KERN_MAXFILES;

	len = sizeof(maxfiles);

	if (0 != sysctl(mib, 2, &maxfiles, &len, NULL, 0))
		return SYSINFO_RET_FAIL;

	SET_UI64_RESULT(result, maxfiles);

	return SYSINFO_RET_OK;
}

int	KERNEL_MAXPROC(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	int	mib[2];
	size_t	len;
	int	maxproc;

	assert(result);

	init_result(result);	
	
	mib[0] = CTL_KERN;
	mib[1] = KERN_MAXPROC;

	len = sizeof(maxproc);

	if (0 != sysctl(mib, 2, &maxproc, &len, NULL, 0))
		return SYSINFO_RET_FAIL;

	SET_UI64_RESULT(result, maxproc);

	return SYSINFO_RET_OK;
}
