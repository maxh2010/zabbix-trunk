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
#include "stats.h"

int	SYSTEM_CPU_UTIL(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	char cpuname[MAX_STRING_LEN];
	char type[MAX_STRING_LEN];
	char mode[MAX_STRING_LEN];
	
        assert(result);

        init_result(result);
	
        if(num_param(param) > 3)
        {
                return SYSINFO_RET_FAIL;
        }

        if(get_param(param, 1, cpuname, sizeof(cpuname)) != 0)
        {
                return SYSINFO_RET_FAIL;
        }
	if(cpuname[0] == '\0')
	{
		/* default parameter */
		zbx_snprintf(cpuname, sizeof(cpuname), "all");
	}
	if(strncmp(cpuname, "all", sizeof(cpuname)))
	{
		return SYSINFO_RET_FAIL;
	}
	
	if(get_param(param, 2, type, sizeof(type)) != 0)
        {
                type[0] = '\0';
        }
        if(type[0] == '\0')
	{
		/* default parameter */
		zbx_snprintf(type, sizeof(type), "user");
	}
	
	if(get_param(param, 3, mode, sizeof(mode)) != 0)
        {
                mode[0] = '\0';
        }
	
        if(mode[0] == '\0')
	{
		/* default parameter */
		zbx_snprintf(mode, sizeof(mode), "avg1");
	}

	if(NULL == collector)
	{
		SET_MSG_RESULT(result, strdup("Collector is not started!"));
		return SYSINFO_RET_OK;
	}

	if( 0 == strcmp(type,"idle"))
	{
		if( 0 == strcmp(mode,"avg1"))		SET_DBL_RESULT(result, collector->cpus.idle1)
		else if( 0 == strcmp(mode,"avg5"))	SET_DBL_RESULT(result, collector->cpus.idle5)
		else if( 0 == strcmp(mode,"avg15"))	SET_DBL_RESULT(result, collector->cpus.idle15)
		else return SYSINFO_RET_FAIL;

	}
	else if( 0 == strcmp(type,"nice"))
	{
		if( 0 == strcmp(mode,"avg1")) 		SET_DBL_RESULT(result, collector->cpus.nice1)
		else if( 0 == strcmp(mode,"avg5")) 	SET_DBL_RESULT(result, collector->cpus.nice5)
		else if( 0 == strcmp(mode,"avg15"))	SET_DBL_RESULT(result, collector->cpus.nice15)
		else return SYSINFO_RET_FAIL;

	}
	else if( 0 == strcmp(type,"user"))
	{
		if( 0 == strcmp(mode,"avg1")) 		SET_DBL_RESULT(result, collector->cpus.user1)
		else if( 0 == strcmp(mode,"avg5")) 	SET_DBL_RESULT(result, collector->cpus.user5)
		else if( 0 == strcmp(mode,"avg15"))	SET_DBL_RESULT(result, collector->cpus.user15)
		else return SYSINFO_RET_FAIL;
	}
	else if( 0 == strcmp(type,"system"))
	{
		if( 0 == strcmp(mode,"avg1")) 		SET_DBL_RESULT(result, collector->cpus.system1)
		else if( 0 == strcmp(mode,"avg5")) 	SET_DBL_RESULT(result, collector->cpus.system5)
		else if( 0 == strcmp(mode,"avg15"))	SET_DBL_RESULT(result, collector->cpus.system15)
		else return SYSINFO_RET_FAIL;
	}
	else
	{
		return SYSINFO_RET_FAIL;
	}

	return SYSINFO_RET_OK;
}

int	SYSTEM_CPU_LOAD1(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
#ifdef HAVE_GETLOADAVG
	double	load[3];

	assert(result);

        init_result(result);
		
	if(getloadavg(load, 3))
	{
		SET_DBL_RESULT(result, load[0]);
		return SYSINFO_RET_OK;
	}
	else
	{
		return SYSINFO_RET_FAIL;	
	}
#else
#ifdef	HAVE_SYS_PSTAT_H
	struct	pst_dynamic dyn;

	assert(result);

        init_result(result);

	if (pstat_getdynamic(&dyn, sizeof(dyn), 1, 0) == -1)
	{
		return SYSINFO_RET_FAIL;
	}
	else
	{
		SET_DBL_RESULT(result, dyn.psd_avg_1_min);
		return SYSINFO_RET_OK;
	}
#else
#ifdef HAVE_PROC_LOADAVG
	return	getPROC("/proc/loadavg",1,1, flags, result);
#else
#ifdef HAVE_KSTAT_H
	static kstat_ctl_t *kc = NULL;
	kstat_t *ks;
	kstat_named_t *kn;

	assert(result);

        init_result(result);
	
	if (!kc && !(kc = kstat_open()))
	{
		return SYSINFO_RET_FAIL;
	}
	if (!(ks = kstat_lookup(kc, "unix", 0, "system_misc")) ||
		kstat_read(kc, ks, 0) == -1 ||
		!(kn = kstat_data_lookup(ks,"avenrun_1min")))
	{
		return SYSINFO_RET_FAIL;
	}
	SET_DBL_RESULT(result, ((double)kn->value.ul)/256.0);
	return SYSINFO_RET_OK;
#else
	assert(result);

        init_result(result);
	return	SYSINFO_RET_FAIL;
#endif
#endif
#endif
#endif
}

int	SYSTEM_CPU_LOAD5(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
#ifdef HAVE_GETLOADAVG
	double	load[3];

	assert(result);

        init_result(result);
		
	if(getloadavg(load, 3))
	{
		SET_DBL_RESULT(result, load[1]);
		return SYSINFO_RET_OK;
	}
	else
	{
		return SYSINFO_RET_FAIL;	
	}
#else
#ifdef	HAVE_SYS_PSTAT_H
	struct	pst_dynamic dyn;

	assert(result);

        init_result(result);
	
	if (pstat_getdynamic(&dyn, sizeof(dyn), 1, 0) == -1)
	{
		return SYSINFO_RET_FAIL;
	}
	else
	{
		SET_DBL_RESULT(result, dyn.psd_avg_5_min);
		return SYSINFO_RET_OK;
	}
#else
#ifdef	HAVE_PROC_LOADAVG
	return	getPROC("/proc/loadavg",1,2, flags, result);
#else
#ifdef HAVE_KSTAT_H
	static kstat_ctl_t *kc = NULL;
	kstat_t *ks;
	kstat_named_t *kn;

	assert(result);

        init_result(result);
		
	if (!kc && !(kc = kstat_open()))
	{
		return SYSINFO_RET_FAIL;
	}
	if (!(ks = kstat_lookup(kc, "unix", 0, "system_misc")) ||
		kstat_read(kc, ks, 0) == -1 ||
		!(kn = kstat_data_lookup(ks,"avenrun_5min")))
	{
		return SYSINFO_RET_FAIL;
	}
	SET_DBL_RESULT(result, ((double)kn->value.ul)/256.0);
	return SYSINFO_RET_OK;
#else
	assert(result);

        init_result(result);
	return	SYSINFO_RET_FAIL;
#endif
#endif
#endif
#endif
}

int	SYSTEM_CPU_LOAD15(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
#ifdef HAVE_GETLOADAVG
	double	load[3];

	assert(result);

        init_result(result);

	if(getloadavg(load, 3))
	{
		SET_DBL_RESULT(result, load[2]); 
		return SYSINFO_RET_OK;
	}
	else
	{
		return SYSINFO_RET_FAIL;	
	}
#else
#ifdef	HAVE_SYS_PSTAT_H
	struct	pst_dynamic dyn;

	assert(result);

        init_result(result);
		
	if (pstat_getdynamic(&dyn, sizeof(dyn), 1, 0) == -1)
	{
		return SYSINFO_RET_FAIL;
	}
	else
	{
		SET_DBL_RESULT(result, dyn.psd_avg_15_min); 
		return SYSINFO_RET_OK;
	}
#else
#ifdef	HAVE_PROC_LOADAVG
	return	getPROC("/proc/loadavg",1,3, flags, result);
#else
#ifdef HAVE_KSTAT_H
	static kstat_ctl_t *kc = NULL;
	kstat_t *ks;
	kstat_named_t *kn;

	assert(result);

        init_result(result);
		
	if (!kc && !(kc = kstat_open()))
	{
		return SYSINFO_RET_FAIL;
	}
	if (!(ks = kstat_lookup(kc, "unix", 0, "system_misc")) ||
		kstat_read(kc, ks, 0) == -1 ||
		!(kn = kstat_data_lookup(ks,"avenrun_15min")))
	{
		return SYSINFO_RET_FAIL;
	}
	SET_DBL_RESULT(result, ((double)kn->value.ul)/256.0); 
	return SYSINFO_RET_OK;
#else
	assert(result);

        init_result(result);
	return	SYSINFO_RET_FAIL;
#endif
#endif
#endif
#endif
}

int	SYSTEM_CPU_LOAD(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{

#define CPU_FNCLIST struct cpu_fnclist_s
CPU_FNCLIST
{
	char *mode;
	int (*function)();
};

	CPU_FNCLIST fl[] = 
	{
		{"avg1" ,	SYSTEM_CPU_LOAD1},
		{"avg5" ,	SYSTEM_CPU_LOAD5},
		{"avg15",	SYSTEM_CPU_LOAD15},
		{0,		0}
	};

	char cpuname[MAX_STRING_LEN];
	char mode[MAX_STRING_LEN];
	int i;
	
        assert(result);

        init_result(result);
	
        if(num_param(param) > 2)
        {
                return SYSINFO_RET_FAIL;
        }

        if(get_param(param, 1, cpuname, sizeof(cpuname)) != 0)
        {
                return SYSINFO_RET_FAIL;
        }
	if(cpuname[0] == '\0')
	{
		/* default parameter */
		zbx_snprintf(cpuname, sizeof(cpuname), "all");
	}
	if(strncmp(cpuname, "all", sizeof(cpuname)))
	{
		return SYSINFO_RET_FAIL;
	}
	
	if(get_param(param, 2, mode, sizeof(mode)) != 0)
        {
                mode[0] = '\0';
        }
        if(mode[0] == '\0')
	{
		/* default parameter */
		zbx_snprintf(mode, sizeof(mode), "avg1");
	}
	for(i=0; fl[i].mode!=0; i++)
	{
		if(strncmp(mode, fl[i].mode, sizeof(mode))==0)
		{
			return (fl[i].function)(cmd, param, flags, result);
		}
	}
	
	return SYSINFO_RET_FAIL;
}

int     SYSTEM_CPU_SWITCHES(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
        assert(result);

        init_result(result);
	
	return SYSINFO_RET_FAIL;
}

int     SYSTEM_CPU_INTR(const char *cmd, const char *param, unsigned flags, AGENT_RESULT *result)
{
	int ret = SYSINFO_RET_FAIL;
	char line[MAX_STRING_LEN];

	char name[MAX_STRING_LEN];
	zbx_uint64_t value = 0;
	
	FILE *f;

        assert(result);

        init_result(result);

	if(NULL != ( f = fopen("/proc/stat","r") ))
	{
		while(fgets(line,sizeof(line),f) != NULL)
		{
			if(sscanf(line,"%s\t" ZBX_FS_UI64 "\n", name, &value) != 2) 
				continue;
		
			if(strncmp(name, "intr", sizeof(name)) == 0)
			{
				SET_UI64_RESULT(result, value);
				ret = SYSINFO_RET_OK;
				break;
			}
		}
		zbx_fclose(f);
	}

	return ret;
}

