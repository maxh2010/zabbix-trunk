/*
** ZABBIX
** Copyright (C) 2000-2007 SIA Zabbix
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
#include "log.h"
#include "zlog.h"
#include "threads.h"

#include "dbcache.h"
#include "ipc.h"
#include "mutexs.h"

#include "memalloc.h"
#include "strpool.h"

#include "zbxalgo.h"

static int	shm_id;

#define	LOCK_CACHE	zbx_mutex_lock(&config_lock)
#define	UNLOCK_CACHE	zbx_mutex_unlock(&config_lock)

/* *_ALLOC_STEP >= 1 */
#define HOST_ALLOC_STEP 	16
#define IPMIHOST_ALLOC_STEP	4
#define ITEM_ALLOC_STEP		256
#define SNMPITEM_ALLOC_STEP	64
#define IPMIITEM_ALLOC_STEP	64
#define FLEXITEM_ALLOC_STEP	64
#define TRAPITEM_ALLOC_STEP	64
#define LOGITEM_ALLOC_STEP	64
#define DBITEM_ALLOC_STEP	64
#define SSHITEM_ALLOC_STEP	64
#define TELNETITEM_ALLOC_STEP	64
#define CALCITEM_ALLOC_STEP	64

#define ZBX_DC_CONFIG struct zbx_dc_config
#define ZBX_DC_HOST struct zbx_dc_host
#define ZBX_DC_IPMIHOST struct zbx_dc_ipmihost
#define ZBX_DC_ITEM struct zbx_dc_item
#define ZBX_DC_SNMPITEM struct zbx_dc_snmpitem
#define ZBX_DC_IPMIITEM struct zbx_dc_ipmiitem
#define ZBX_DC_FLEXITEM struct zbx_dc_flexitem
#define ZBX_DC_TRAPITEM struct zbx_dc_trapitem
#define ZBX_DC_LOGITEM struct zbx_dc_logitem
#define ZBX_DC_DBITEM struct zbx_dc_dbitem
#define ZBX_DC_SSHITEM struct zbx_dc_sshitem
#define ZBX_DC_TELNETITEM struct zbx_dc_telnetitem
#define ZBX_DC_CALCITEM struct zbx_dc_calcitem

ZBX_DC_ITEM
{
	zbx_uint64_t	itemid;
	zbx_uint64_t	hostid;
	unsigned char 	poller_type;
	unsigned char 	poller_num;
	unsigned char 	type;
	unsigned char	data_type;
	unsigned char	value_type;
	const char	*key;			/* interned; key[ITEM_KEY_LEN_MAX];					*/
	int		delay;
	int		nextcheck;
	unsigned char	status;
};

ZBX_DC_SNMPITEM
{
	zbx_uint64_t	itemid;
	const char	*snmp_community;	/* interned; snmp_community[ITEM_SNMP_COMMUNITY_LEN_MAX];		*/
	const char	*snmp_oid;		/* interned; snmp_oid[ITEM_SNMP_OID_LEN_MAX];				*/
	unsigned short	snmp_port;
	const char	*snmpv3_securityname;	/* interned; snmpv3_securityname[ITEM_SNMPV3_SECURITYNAME_LEN_MAX];	*/
	unsigned char	snmpv3_securitylevel;
	const char	*snmpv3_authpassphrase;	/* interned; snmpv3_authpassphrase[ITEM_SNMPV3_AUTHPASSPHRASE_LEN_MAX];	*/
	const char	*snmpv3_privpassphrase;	/* interned; snmpv3_privpassphrase[ITEM_SNMPV3_PRIVPASSPHRASE_LEN_MAX];	*/
};

ZBX_DC_IPMIITEM
{
	zbx_uint64_t	itemid;
	const char	*ipmi_sensor;		/* interned; ipmi_sensor[ITEM_IPMI_SENSOR_LEN_MAX];			*/
};

ZBX_DC_FLEXITEM
{
	zbx_uint64_t	itemid;
	const char	*delay_flex;		/* interned; delay_flex[ITEM_DELAY_FLEX_LEN_MAX];			*/
};

ZBX_DC_TRAPITEM
{
	zbx_uint64_t	itemid;
	const char	*trapper_hosts;		/* interned; trapper_hosts[ITEM_TRAPPER_HOSTS_LEN_MAX];			*/
};

ZBX_DC_LOGITEM
{
	zbx_uint64_t	itemid;
	const char	*logtimefmt;		/* interned; logtimefmt[ITEM_LOGTIMEFMT_LEN_MAX];			*/
};

ZBX_DC_DBITEM
{
	zbx_uint64_t	itemid;
	const char	*params;		/* interned; params[ITEM_PARAMS_LEN_MAX];				*/
};

ZBX_DC_SSHITEM
{
	zbx_uint64_t	itemid;
	unsigned char	authtype;
	const char	*username;		/* interned; username[ITEM_USERNAME_LEN_MAX];				*/
	const char	*publickey;		/* interned; publickey[ITEM_PUBLICKEY_LEN_MAX];				*/
	const char	*privatekey;		/* interned; privatekey[ITEM_PRIVATEKEY_LEN_MAX];			*/
	const char	*password;		/* interned; password[ITEM_PASSWORD_LEN_MAX];				*/
	const char	*params;		/* interned; params[ITEM_PARAMS_LEN_MAX];				*/
};

ZBX_DC_TELNETITEM
{
	zbx_uint64_t	itemid;
	const char	*username;		/* interned; username[ITEM_USERNAME_LEN_MAX];				*/
	const char	*password;		/* interned; password[ITEM_PASSWORD_LEN_MAX];				*/
	const char	*params;		/* interned; params[ITEM_PARAMS_LEN_MAX];				*/
};

ZBX_DC_CALCITEM
{
	zbx_uint64_t	itemid;
	const char	*params;		/* interned; params[ITEM_PARAMS_LEN_MAX];				*/
};

ZBX_DC_HOST
{
	zbx_uint64_t	hostid;
	unsigned char 	poller_type;
	unsigned char 	poller_num;
	int		nextcheck;
	zbx_uint64_t	proxy_hostid;
	const char	*host;			/* interned; host[HOST_HOST_LEN_MAX];					*/
	unsigned char	useip;
	const char	*ip;			/* interned; ip[HOST_IP_LEN_MAX];					*/
	const char	*dns;			/* interned; dns[HOST_DNS_LEN_MAX];					*/
	unsigned short	port;
	unsigned char	maintenance_status;
	unsigned char	maintenance_type;
	int		maintenance_from;
	int		errors_from;
	unsigned char	available;
	int		disable_until;
	int		snmp_errors_from;
	unsigned char	snmp_available;
	int		snmp_disable_until;
	int		ipmi_errors_from;
	unsigned char	ipmi_available;
	int		ipmi_disable_until;
};

ZBX_DC_IPMIHOST
{
	zbx_uint64_t	hostid;
	const char	*ipmi_ip;		/* interned; ipmi_ip[HOST_ADDR_LEN_MAX];				*/
	unsigned short	ipmi_port;
	signed char	ipmi_authtype;
	unsigned char	ipmi_privilege;
	const char	*ipmi_username;		/* interned; ipmi_username[HOST_IPMI_USERNAME_LEN_MAX];			*/
	const char	*ipmi_password;		/* interned; ipmi_password[HOST_IPMI_PASSWORD_LEN_MAX];			*/
};

ZBX_DC_CONFIG
{
	int		items_alloc, snmpitems_alloc, ipmiitems_alloc,
			flexitems_alloc, trapitems_alloc, logitems_alloc,
			dbitems_alloc, sshitems_alloc, telnetitems_alloc,
			calcitems_alloc, hosts_alloc, ipmihosts_alloc;
	int		items_num, snmpitems_num, ipmiitems_num,
			flexitems_num, trapitems_num, logitems_num,
			dbitems_num, sshitems_num, telnetitems_num,
			calcitems_num, hosts_num, ipmihosts_num;
	int		idxitem01_alloc, idxitem02_alloc,
			idxhost01_alloc, idxhost02_alloc;
	int		idxitem01_num, idxitem02_num,
			idxhost01_num, idxhost02_num;
	ZBX_DC_ITEM	*items;
	ZBX_DC_SNMPITEM	*snmpitems;
	ZBX_DC_IPMIITEM	*ipmiitems;
	ZBX_DC_FLEXITEM	*flexitems;
	ZBX_DC_TRAPITEM	*trapitems;
	ZBX_DC_LOGITEM	*logitems;
	ZBX_DC_DBITEM	*dbitems;
	ZBX_DC_SSHITEM	*sshitems;
	ZBX_DC_TELNETITEM	*telnetitems;
	ZBX_DC_CALCITEM	*calcitems;
	int		*idxitem01;	/* hostid,key */
	int		*idxitem02;	/* poller_type,poller_num,nextcheck */
	ZBX_DC_HOST	*hosts;
	ZBX_DC_IPMIHOST	*ipmihosts;
	int		*idxhost01;	/* proxy_hostid,host */
	int		*idxhost02;	/* poller_type,poller_num,nextcheck */
	int		free_mem;
};

static ZBX_DC_CONFIG	*config = NULL;
static ZBX_MUTEX	config_lock;
static size_t		config_size;

/*
 * Returns type and number of poller for item
 * (for normal or IPMI pollers)
 */
static void	poller_by_item(zbx_uint64_t itemid, zbx_uint64_t hostid, zbx_uint64_t proxy_hostid,
		unsigned char item_type, const char *key, unsigned char *poller_type, unsigned char *poller_num)
{
	if (0 != proxy_hostid && (ITEM_TYPE_INTERNAL != item_type &&
				ITEM_TYPE_AGGREGATE != item_type &&
				ITEM_TYPE_CALCULATED != item_type))
	{
		*poller_type = (unsigned char)255;
		*poller_num = (unsigned char)255;
		return;
	}

	switch (item_type) {
	case ITEM_TYPE_SIMPLE:
		if (SUCCEED == cmp_key_id(key, SERVER_ICMPPING_KEY) ||
				SUCCEED == cmp_key_id(key, SERVER_ICMPPINGSEC_KEY) ||
				SUCCEED == cmp_key_id(key, SERVER_ICMPPINGLOSS_KEY))
		{
			if (0 == CONFIG_PINGER_FORKS)
				break;
			*poller_type = (unsigned char)ZBX_POLLER_TYPE_PINGER;
			*poller_num = (unsigned char)(hostid % CONFIG_PINGER_FORKS);
			return;
		}
	case ITEM_TYPE_ZABBIX:
	case ITEM_TYPE_SNMPv1:
	case ITEM_TYPE_SNMPv2c:
	case ITEM_TYPE_SNMPv3:
	case ITEM_TYPE_INTERNAL:
	case ITEM_TYPE_AGGREGATE:
	case ITEM_TYPE_EXTERNAL:
	case ITEM_TYPE_DB_MONITOR:
	case ITEM_TYPE_SSH:
	case ITEM_TYPE_TELNET:
	case ITEM_TYPE_CALCULATED:
		if (0 == CONFIG_POLLER_FORKS)
			break;
		*poller_type = (unsigned char)ZBX_POLLER_TYPE_NORMAL;
		*poller_num = (unsigned char)(itemid % CONFIG_POLLER_FORKS);
		return;
	case ITEM_TYPE_IPMI:
		if (0 == CONFIG_IPMIPOLLER_FORKS)
			break;
		*poller_type = (unsigned char)ZBX_POLLER_TYPE_IPMI;
		*poller_num = (unsigned char)(hostid % CONFIG_IPMIPOLLER_FORKS);
		return;
	}

	*poller_type = (unsigned char)255;
	*poller_num = (unsigned char)255;
}

/*
 * Returns type and number of poller for unreachable host
 * (for pollers for unreachable devices)
 *
 * errors - [IN] (errors_from || snmp_errors_from || ipmi_errors_from)
 */
static void	poller_by_host(zbx_uint64_t hostid, zbx_uint64_t proxy_hostid, int errors,
		unsigned char *poller_type, unsigned char *poller_num)
{
	if (0 != errors && 0 == proxy_hostid && 0 != CONFIG_UNREACHABLE_POLLER_FORKS)
	{
		*poller_type = (unsigned char)ZBX_POLLER_TYPE_UNREACHABLE;
		*poller_num = (unsigned char)(hostid % CONFIG_UNREACHABLE_POLLER_FORKS);
	}
	else
	{
		*poller_type = (unsigned char)255;
		*poller_num = (unsigned char)255;
	}
}

static int	DCget_idxhost01_nearestindex(zbx_uint64_t proxy_hostid, const char *host)
{
	int		first_index, last_index, index, res;
	ZBX_DC_HOST	*dc_host;

	if (config->idxhost01_num == 0)
		return 0;

	first_index = 0;
	last_index = config->idxhost01_num - 1;
	while (1)
	{
		index = first_index + (last_index - first_index) / 2;

		dc_host = &config->hosts[config->idxhost01[index]];
		res = (dc_host->host == host ? 0 : strcmp(dc_host->host, host));
		if (dc_host->proxy_hostid == proxy_hostid && 0 == res)
			return index;
		else if (last_index == first_index)
		{
			if (dc_host->proxy_hostid < proxy_hostid ||
					(dc_host->proxy_hostid == proxy_hostid && res < 0))
				index++;
			return index;
		}
		else if (dc_host->proxy_hostid < proxy_hostid ||
				(dc_host->proxy_hostid == proxy_hostid && res < 0))
			first_index = index + 1;
		else
			last_index = index;
	}
}

static int	DCget_unreachable_nextcheck(int disable_until, int snmp_disable_until, int ipmi_disable_until)
{
	int	nextcheck;

	nextcheck = disable_until;
	if (0 != snmp_disable_until && (0 == nextcheck || nextcheck > snmp_disable_until))
		nextcheck = snmp_disable_until;
	if (0 != ipmi_disable_until && (0 == nextcheck || nextcheck > ipmi_disable_until))
		nextcheck = ipmi_disable_until;

	return nextcheck;
}

static int	DCget_idxhost02_nearestindex(unsigned char poller_type, unsigned char poller_num, int nextcheck)
{
	int		first_index, last_index, index;
	ZBX_DC_HOST	*dc_host;

	if (config->idxhost02_num == 0)
		return 0;

	first_index = 0;
	last_index = config->idxhost02_num - 1;
	while (1)
	{
		index = first_index + (last_index - first_index) / 2;

		dc_host = &config->hosts[config->idxhost02[index]];
		if (dc_host->poller_type == poller_type && dc_host->poller_num == poller_num &&
				dc_host->nextcheck == nextcheck)
		{
			while (index > 0)
			{
				dc_host = &config->hosts[config->idxhost02[index - 1]];
				if (dc_host->poller_type != poller_type || dc_host->poller_num != poller_num ||
						dc_host->nextcheck != nextcheck)
					break;
				index--;
			}
			return index;
		}
		else if (last_index == first_index)
		{
			if (dc_host->poller_type < poller_type ||
					(dc_host->poller_type == poller_type &&
					 dc_host->poller_num < poller_num) ||
					(dc_host->poller_type == poller_type &&
					 dc_host->poller_num == poller_num && dc_host->nextcheck < nextcheck))
				index++;
			return index;
		}
		else if (dc_host->poller_type < poller_type ||
				(dc_host->poller_type == poller_type && dc_host->poller_num < poller_num) ||
				(dc_host->poller_type == poller_type && dc_host->poller_num == poller_num &&
				 dc_host->nextcheck < nextcheck))
			first_index = index + 1;
		else
			last_index = index;
	}
}

static int	DCget_idxitem01_nearestindex(zbx_uint64_t hostid, const char *key)
{
	int		first_index, last_index, index, res;
	ZBX_DC_ITEM	*dc_item;

	if (config->idxitem01_num == 0)
		return 0;

	first_index = 0;
	last_index = config->idxitem01_num - 1;
	while (1)
	{
		index = first_index + (last_index - first_index) / 2;

		dc_item = &config->items[config->idxitem01[index]];
		res = (dc_item->key == key ? 0 : strcmp(dc_item->key, key));
		if (dc_item->hostid == hostid && 0 == res)
			return index;
		else if (last_index == first_index)
		{
			if (dc_item->hostid < hostid || (dc_item->hostid == hostid && res < 0))
				index++;
			return index;
		}
		else if (dc_item->hostid < hostid || (dc_item->hostid == hostid && res < 0))
			first_index = index + 1;
		else
			last_index = index;
	}
}

static int	DCget_idxitem02_nearestindex(unsigned char poller_type, unsigned char poller_num, int nextcheck)
{
	int		first_index, last_index, index;
	ZBX_DC_ITEM	*dc_item;

	if (config->idxitem02_num == 0)
		return 0;

	first_index = 0;
	last_index = config->idxitem02_num - 1;
	while (1)
	{
		index = first_index + (last_index - first_index) / 2;

		dc_item = &config->items[config->idxitem02[index]];
		if (dc_item->poller_type == poller_type && dc_item->poller_num == poller_num &&
				dc_item->nextcheck == nextcheck)
		{
			while (index > 0)
			{
				dc_item = &config->items[config->idxitem02[index - 1]];
				if (dc_item->poller_type != poller_type || dc_item->poller_num != poller_num ||
						dc_item->nextcheck != nextcheck)
					break;
				index--;
			}
			return index;
		}
		else if (last_index == first_index)
		{
			if (dc_item->poller_type < poller_type ||
					(dc_item->poller_type == poller_type && dc_item->poller_num < poller_num) ||
					(dc_item->poller_type == poller_type && dc_item->poller_num == poller_num &&
						dc_item->nextcheck < nextcheck))
				index++;
			return index;
		}
		else if (dc_item->poller_type < poller_type ||
				(dc_item->poller_type == poller_type && dc_item->poller_num < poller_num) ||
				(dc_item->poller_type == poller_type && dc_item->poller_num == poller_num &&
					dc_item->nextcheck < nextcheck))
			first_index = index + 1;
		else
			last_index = index;
	}
}

static void	DCcheck_freemem(size_t sz)
{
	if (config->free_mem < sz)
	{
		zbx_error("ERROR: Configuration buffer is too small. Please increase CacheSize parameter.");
		exit(FAIL);
	}
	config->free_mem -= sz;
}

static void	DCremove_element(void *p, int *num, size_t sz, int index)
{
	(*num)--;
	p = (char *)p + index * sz;
	memmove(p, (char *)p + sz, sz * (*num - index));
}

static void	DCallocate_idxitem01(int *index, int remove_index);
static void	DCallocate_idxitem02(int *index, int remove_index);
static void	DCallocate_idxhost01(int *index, int remove_index);
static void	DCallocate_idxhost02(int *index, int remove_index);

static void	DCupdate_idxhost01(int host_index, zbx_uint64_t *old_proxy_hostid, const char *old_host,
		zbx_uint64_t *new_proxy_hostid, const char *new_host)
{
	int	index, remove_index = -1;

	if (NULL != old_proxy_hostid)	/* remove old index record */
	{
		if (NULL != new_proxy_hostid && *old_proxy_hostid == *new_proxy_hostid && old_host == new_host)
			return;

		index = DCget_idxhost01_nearestindex(*old_proxy_hostid, old_host);
		if (index < config->idxhost01_num && config->idxhost01[index] == host_index)
			remove_index = index;
	}

	if (NULL != new_proxy_hostid)
	{
		index = DCget_idxhost01_nearestindex(*new_proxy_hostid, new_host);
		DCallocate_idxhost01(&index, remove_index);

		config->idxhost01[index] = host_index;
	}
	else if (-1 != remove_index)
		DCremove_element(config->idxhost01, &config->idxhost01_num, sizeof(int), remove_index);
}

static void	DCupdate_idxhost02(int host_index, unsigned char *old_poller_type, unsigned char *old_poller_num,
		int *old_nextcheck, unsigned char *new_poller_type, unsigned char *new_poller_num,
		int *new_nextcheck)
{
	int	i, index, remove_index = -1;

	if (NULL != old_poller_type && 255 != *old_poller_type)	/* remove old index record */
	{
		if (NULL != new_poller_num && *old_poller_type == *new_poller_type &&
				*old_poller_num == *new_poller_num && *old_nextcheck == *new_nextcheck)
			return;

		index = DCget_idxhost02_nearestindex(*old_poller_type, *old_poller_num, *old_nextcheck);
		for (i = index; i < config->idxhost02_num; i++)
		{
			if (config->idxhost02[i] != host_index)
				continue;

			remove_index = i;
			break;
		}
	}

	if (NULL != new_poller_type && 255 != *new_poller_type)
	{
		index = DCget_idxhost02_nearestindex(*new_poller_type, *new_poller_num, *new_nextcheck);
		DCallocate_idxhost02(&index, remove_index);

		config->idxhost02[index] = host_index;
	}
	else if (-1 != remove_index)
		DCremove_element(config->idxhost02, &config->idxhost02_num, sizeof(int), remove_index);
}

static void	DCupdate_idxitem01(int item_index, zbx_uint64_t *old_hostid, const char *old_key,
		zbx_uint64_t *new_hostid, const char *new_key)
{
	int	index, remove_index = -1;

	if (NULL != old_hostid)	/* remove old index record */
	{
		if (NULL != new_hostid && *old_hostid == *new_hostid && old_key == new_key)
			return;

		index = DCget_idxitem01_nearestindex(*old_hostid, old_key);
		if (index < config->idxitem01_num && config->idxitem01[index] == item_index)
			remove_index = index;
	}

	if (NULL != new_hostid)
	{
		index = DCget_idxitem01_nearestindex(*new_hostid, new_key);
		DCallocate_idxitem01(&index, remove_index);

		config->idxitem01[index] = item_index;
	}
	else if (-1 != remove_index)
		DCremove_element(config->idxitem01, &config->idxitem01_num, sizeof(int), remove_index);
}

static void	DCupdate_idxitem02(int item_index, unsigned char *old_poller_type,
		unsigned char *old_poller_num, int *old_nextcheck, unsigned char *new_poller_type,
		unsigned char *new_poller_num, int *new_nextcheck)
{
	int	i, index, remove_index = -1;

	/* remove old index record */
	if (NULL != old_poller_type && 255 != *old_poller_type)
	{
		if (NULL != new_poller_type && *old_poller_type == *new_poller_type &&
				*old_poller_num == *new_poller_num && *old_nextcheck == *new_nextcheck)
			return;

		index = DCget_idxitem02_nearestindex(*old_poller_type, *old_poller_num, *old_nextcheck);
		for (i = index; i < config->idxitem02_num; i++)
		{
			if (config->idxitem02[i] != item_index)
				continue;

			remove_index = i;
			break;
		}
	}

	if (NULL != new_poller_type && 255 != *new_poller_type)
	{
		index = DCget_idxitem02_nearestindex(*new_poller_type, *new_poller_num, *new_nextcheck);
		DCallocate_idxitem02(&index, remove_index);

		config->idxitem02[index] = item_index;
	}
	else if (-1 != remove_index)
		DCremove_element(config->idxitem02, &config->idxitem02_num, sizeof(int), remove_index);
}

static void	DCallocate_item(int index)
{
	size_t	sz;

	if (config->items_num == config->items_alloc)
	{
		size_t	offset;
		char	*src;

		offset = sizeof(ZBX_DC_ITEM) * ITEM_ALLOC_STEP;

		DCcheck_freemem(offset);

		config->items_alloc += ITEM_ALLOC_STEP;

		src = (char *)config->snmpitems;
		if (0 != (sz = ((char *)config->idxhost02 - src) +
					sizeof(int) * config->idxhost02_num))
			memmove(src + offset, src, sz);

		config->snmpitems = (void *)((char *)config->snmpitems + offset);
		config->ipmiitems = (void *)((char *)config->ipmiitems + offset);
		config->flexitems = (void *)((char *)config->flexitems + offset);
		config->trapitems = (void *)((char *)config->trapitems + offset);
		config->logitems = (void *)((char *)config->logitems + offset);
		config->dbitems = (void *)((char *)config->dbitems + offset);
		config->sshitems = (void *)((char *)config->sshitems + offset);
		config->telnetitems = (void *)((char *)config->telnetitems + offset);
		config->calcitems = (void *)((char *)config->calcitems + offset);
		config->idxitem01 = (void *)((char *)config->idxitem01 + offset);
		config->idxitem02 = (void *)((char *)config->idxitem02 + offset);
		config->hosts = (void *)((char *)config->hosts + offset);
		config->ipmihosts = (void *)((char *)config->ipmihosts + offset);
		config->idxhost01 = (void *)((char *)config->idxhost01 + offset);
		config->idxhost02 = (void *)((char *)config->idxhost02 + offset);
	}

	if (0 != (sz = sizeof(ZBX_DC_ITEM) * (config->items_num - index)))
		memmove(&config->items[index + 1], &config->items[index], sz);
	config->items_num++;
}

static void	DCallocate_snmpitem(int index)
{
	size_t	sz;

	if (config->snmpitems_num == config->snmpitems_alloc)
	{
		size_t	offset;
		char	*src;

		offset = sizeof(ZBX_DC_SNMPITEM) * SNMPITEM_ALLOC_STEP;

		DCcheck_freemem(offset);

		config->snmpitems_alloc += SNMPITEM_ALLOC_STEP;

		src = (char *)config->ipmiitems;
		if (0 != (sz = ((char *)config->idxhost02 - src) +
					sizeof(int) * config->idxhost02_num))
			memmove(src + offset, src, sz);

		config->ipmiitems = (void *)((char *)config->ipmiitems + offset);
		config->flexitems = (void *)((char *)config->flexitems + offset);
		config->trapitems = (void *)((char *)config->trapitems + offset);
		config->logitems = (void *)((char *)config->logitems + offset);
		config->dbitems = (void *)((char *)config->dbitems + offset);
		config->sshitems = (void *)((char *)config->sshitems + offset);
		config->telnetitems = (void *)((char *)config->telnetitems + offset);
		config->calcitems = (void *)((char *)config->calcitems + offset);
		config->idxitem01 = (void *)((char *)config->idxitem01 + offset);
		config->idxitem02 = (void *)((char *)config->idxitem02 + offset);
		config->hosts = (void *)((char *)config->hosts + offset);
		config->ipmihosts = (void *)((char *)config->ipmihosts + offset);
		config->idxhost01 = (void *)((char *)config->idxhost01 + offset);
		config->idxhost02 = (void *)((char *)config->idxhost02 + offset);
	}

	if (0 != (sz = sizeof(ZBX_DC_SNMPITEM) * (config->snmpitems_num - index)))
		memmove(&config->snmpitems[index + 1], &config->snmpitems[index], sz);
	config->snmpitems_num++;
}

static void	DCallocate_ipmiitem(int index)
{
	size_t	sz;

	if (config->ipmiitems_num == config->ipmiitems_alloc)
	{
		size_t	offset;
		char	*src;

		offset = sizeof(ZBX_DC_IPMIITEM) * IPMIITEM_ALLOC_STEP;

		DCcheck_freemem(offset);

		config->ipmiitems_alloc += IPMIITEM_ALLOC_STEP;

		src = (char *)config->flexitems;
		if (0 != (sz = ((char *)config->idxhost02 - src) +
					sizeof(int) * config->idxhost02_num))
			memmove(src + offset, src, sz);

		config->flexitems = (void *)((char *)config->flexitems + offset);
		config->trapitems = (void *)((char *)config->trapitems + offset);
		config->logitems = (void *)((char *)config->logitems + offset);
		config->dbitems = (void *)((char *)config->dbitems + offset);
		config->sshitems = (void *)((char *)config->sshitems + offset);
		config->telnetitems = (void *)((char *)config->telnetitems + offset);
		config->calcitems = (void *)((char *)config->calcitems + offset);
		config->idxitem01 = (void *)((char *)config->idxitem01 + offset);
		config->idxitem02 = (void *)((char *)config->idxitem02 + offset);
		config->hosts = (void *)((char *)config->hosts + offset);
		config->ipmihosts = (void *)((char *)config->ipmihosts + offset);
		config->idxhost01 = (void *)((char *)config->idxhost01 + offset);
		config->idxhost02 = (void *)((char *)config->idxhost02 + offset);
	}

	if (0 != (sz = sizeof(ZBX_DC_IPMIITEM) * (config->ipmiitems_num - index)))
		memmove(&config->ipmiitems[index + 1], &config->ipmiitems[index], sz);
	config->ipmiitems_num++;
}

static void	DCallocate_flexitem(int index)
{
	size_t	sz;

	if (config->flexitems_num == config->flexitems_alloc)
	{
		size_t	offset;
		char	*src;

		offset = sizeof(ZBX_DC_FLEXITEM) * FLEXITEM_ALLOC_STEP;

		DCcheck_freemem(offset);

		config->flexitems_alloc += FLEXITEM_ALLOC_STEP;

		src = (char *)config->trapitems;
		if (0 != (sz = ((char *)config->idxhost02 - src) +
					sizeof(int) * config->idxhost02_num))
			memmove(src + offset, src, sz);

		config->trapitems = (void *)((char *)config->trapitems + offset);
		config->logitems = (void *)((char *)config->logitems + offset);
		config->dbitems = (void *)((char *)config->dbitems + offset);
		config->sshitems = (void *)((char *)config->sshitems + offset);
		config->telnetitems = (void *)((char *)config->telnetitems + offset);
		config->calcitems = (void *)((char *)config->calcitems + offset);
		config->idxitem01 = (void *)((char *)config->idxitem01 + offset);
		config->idxitem02 = (void *)((char *)config->idxitem02 + offset);
		config->hosts = (void *)((char *)config->hosts + offset);
		config->ipmihosts = (void *)((char *)config->ipmihosts + offset);
		config->idxhost01 = (void *)((char *)config->idxhost01 + offset);
		config->idxhost02 = (void *)((char *)config->idxhost02 + offset);
	}

	if (0 != (sz = sizeof(ZBX_DC_FLEXITEM) * (config->flexitems_num - index)))
		memmove(&config->flexitems[index + 1], &config->flexitems[index], sz);
	config->flexitems_num++;
}

static void	DCallocate_trapitem(int index)
{
	size_t	sz;

	if (config->trapitems_num == config->trapitems_alloc)
	{
		size_t	offset;
		char	*src;

		offset = sizeof(ZBX_DC_TRAPITEM) * TRAPITEM_ALLOC_STEP;

		DCcheck_freemem(offset);

		config->trapitems_alloc += TRAPITEM_ALLOC_STEP;

		src = (char *)config->logitems;
		if (0 != (sz = ((char *)config->idxhost02 - src) +
					sizeof(int) * config->idxhost02_num))
			memmove(src + offset, src, sz);
		
		config->logitems = (void *)((char *)config->logitems + offset);
		config->dbitems = (void *)((char *)config->dbitems + offset);
		config->sshitems = (void *)((char *)config->sshitems + offset);
		config->telnetitems = (void *)((char *)config->telnetitems + offset);
		config->calcitems = (void *)((char *)config->calcitems + offset);
		config->idxitem01 = (void *)((char *)config->idxitem01 + offset);
		config->idxitem02 = (void *)((char *)config->idxitem02 + offset);
		config->hosts = (void *)((char *)config->hosts + offset);
		config->ipmihosts = (void *)((char *)config->ipmihosts + offset);
		config->idxhost01 = (void *)((char *)config->idxhost01 + offset);
		config->idxhost02 = (void *)((char *)config->idxhost02 + offset);
	}

	if (0 != (sz = sizeof(ZBX_DC_TRAPITEM) * (config->trapitems_num - index)))
		memmove(&config->trapitems[index + 1], &config->trapitems[index], sz);
	config->trapitems_num++;
}

static void	DCallocate_logitem(int index)
{
	size_t	sz;

	if (config->logitems_num == config->logitems_alloc)
	{
		size_t	offset;
		char	*src;

		offset = sizeof(ZBX_DC_LOGITEM) * LOGITEM_ALLOC_STEP;

		DCcheck_freemem(offset);

		config->logitems_alloc += LOGITEM_ALLOC_STEP;

		src = (char *)config->dbitems;
		if (0 != (sz = ((char *)config->idxhost02 - src) +
					sizeof(int) * config->idxhost02_num))
			memmove(src + offset, src, sz);

		config->dbitems = (void *)((char *)config->dbitems + offset);
		config->sshitems = (void *)((char *)config->sshitems + offset);
		config->telnetitems = (void *)((char *)config->telnetitems + offset);
		config->calcitems = (void *)((char *)config->calcitems + offset);
		config->idxitem01 = (void *)((char *)config->idxitem01 + offset);
		config->idxitem02 = (void *)((char *)config->idxitem02 + offset);
		config->hosts = (void *)((char *)config->hosts + offset);
		config->ipmihosts = (void *)((char *)config->ipmihosts + offset);
		config->idxhost01 = (void *)((char *)config->idxhost01 + offset);
		config->idxhost02 = (void *)((char *)config->idxhost02 + offset);
	}

	if (0 != (sz = sizeof(ZBX_DC_LOGITEM) * (config->logitems_num - index)))
		memmove(&config->logitems[index + 1], &config->logitems[index], sz);
	config->logitems_num++;
}

static void	DCallocate_dbitem(int index)
{
	size_t	sz;

	if (config->dbitems_num == config->dbitems_alloc)
	{
		size_t	offset;
		char	*src;

		offset = sizeof(ZBX_DC_DBITEM) * DBITEM_ALLOC_STEP;

		DCcheck_freemem(offset);

		config->dbitems_alloc += DBITEM_ALLOC_STEP;

		src = (char *)config->sshitems;
		if (0 != (sz = ((char *)config->idxhost02 - src) +
					sizeof(int) * config->idxhost02_num))
			memmove(src + offset, src, sz);

		config->sshitems = (void *)((char *)config->sshitems + offset);
		config->telnetitems = (void *)((char *)config->telnetitems + offset);
		config->calcitems = (void *)((char *)config->calcitems + offset);
		config->idxitem01 = (void *)((char *)config->idxitem01 + offset);
		config->idxitem02 = (void *)((char *)config->idxitem02 + offset);
		config->hosts = (void *)((char *)config->hosts + offset);
		config->ipmihosts = (void *)((char *)config->ipmihosts + offset);
		config->idxhost01 = (void *)((char *)config->idxhost01 + offset);
		config->idxhost02 = (void *)((char *)config->idxhost02 + offset);
	}

	if (0 != (sz = sizeof(ZBX_DC_DBITEM) * (config->dbitems_num - index)))
		memmove(&config->dbitems[index + 1], &config->dbitems[index], sz);
	config->dbitems_num++;
}

static void	DCallocate_sshitem(int index)
{
	size_t	sz;

	if (config->sshitems_num == config->sshitems_alloc)
	{
		size_t	offset;
		char	*src;

		offset = sizeof(ZBX_DC_SSHITEM) * SSHITEM_ALLOC_STEP;

		DCcheck_freemem(offset);

		config->sshitems_alloc += SSHITEM_ALLOC_STEP;

		src = (char *)config->telnetitems;
		if (0 != (sz = ((char *)config->idxhost02 - src) +
					sizeof(int) * config->idxhost02_num))
			memmove(src + offset, src, sz);

		config->telnetitems = (void *)((char *)config->telnetitems + offset);
		config->calcitems = (void *)((char *)config->calcitems + offset);
		config->idxitem01 = (void *)((char *)config->idxitem01 + offset);
		config->idxitem02 = (void *)((char *)config->idxitem02 + offset);
		config->hosts = (void *)((char *)config->hosts + offset);
		config->ipmihosts = (void *)((char *)config->ipmihosts + offset);
		config->idxhost01 = (void *)((char *)config->idxhost01 + offset);
		config->idxhost02 = (void *)((char *)config->idxhost02 + offset);
	}

	if (0 != (sz = sizeof(ZBX_DC_SSHITEM) * (config->sshitems_num - index)))
		memmove(&config->sshitems[index + 1], &config->sshitems[index], sz);
	config->sshitems_num++;
}

static void	DCallocate_telnetitem(int index)
{
	size_t	sz;

	if (config->telnetitems_num == config->telnetitems_alloc)
	{
		size_t	offset;
		char	*src;

		offset = sizeof(ZBX_DC_TELNETITEM) * TELNETITEM_ALLOC_STEP;

		DCcheck_freemem(offset);

		config->telnetitems_alloc += TELNETITEM_ALLOC_STEP;

		src = (char *)config->calcitems;
		if (0 != (sz = ((char *)config->idxhost02 - src) +
					sizeof(int) * config->idxhost02_num))
			memmove(src + offset, src, sz);

		config->calcitems = (void *)((char *)config->calcitems + offset);
		config->idxitem01 = (void *)((char *)config->idxitem01 + offset);
		config->idxitem02 = (void *)((char *)config->idxitem02 + offset);
		config->hosts = (void *)((char *)config->hosts + offset);
		config->ipmihosts = (void *)((char *)config->ipmihosts + offset);
		config->idxhost01 = (void *)((char *)config->idxhost01 + offset);
		config->idxhost02 = (void *)((char *)config->idxhost02 + offset);
	}

	if (0 != (sz = sizeof(ZBX_DC_TELNETITEM) * (config->telnetitems_num - index)))
		memmove(&config->telnetitems[index + 1], &config->telnetitems[index], sz);
	config->telnetitems_num++;
}

static void	DCallocate_calcitem(int index)
{
	size_t	sz;

	if (config->calcitems_num == config->calcitems_alloc)
	{
		size_t	offset;
		char	*src;

		offset = sizeof(ZBX_DC_CALCITEM) * CALCITEM_ALLOC_STEP;

		DCcheck_freemem(offset);

		config->calcitems_alloc += CALCITEM_ALLOC_STEP;

		src = (char *)config->idxitem01;
		if (0 != (sz = ((char *)config->idxhost02 - src) +
					sizeof(int) * config->idxhost02_num))
			memmove(src + offset, src, sz);

		config->idxitem01 = (void *)((char *)config->idxitem01 + offset);
		config->idxitem02 = (void *)((char *)config->idxitem02 + offset);
		config->hosts = (void *)((char *)config->hosts + offset);
		config->ipmihosts = (void *)((char *)config->ipmihosts + offset);
		config->idxhost01 = (void *)((char *)config->idxhost01 + offset);
		config->idxhost02 = (void *)((char *)config->idxhost02 + offset);
	}

	if (0 != (sz = sizeof(ZBX_DC_CALCITEM) * (config->calcitems_num - index)))
		memmove(&config->calcitems[index + 1], &config->calcitems[index], sz);
	config->calcitems_num++;
}

static void	DCallocate_idxitem01(int *index, int remove_index)
{
	size_t	sz;
	char	*src, *dst;

	if (config->idxitem01_num == config->idxitem01_alloc)
	{
		size_t	offset;

		offset = sizeof(int) * ITEM_ALLOC_STEP;

		DCcheck_freemem(offset);

		config->idxitem01_alloc += ITEM_ALLOC_STEP;

		src = (char *)config->idxitem02;
		if (0 != (sz = ((char *)config->idxhost02 - src) +
					sizeof(int) * config->idxhost02_num))
			memmove(src + offset, src, sz);

		config->idxitem02 = (void *)((char *)config->idxitem02 + offset);
		config->hosts = (void *)((char *)config->hosts + offset);
		config->ipmihosts = (void *)((char *)config->ipmihosts + offset);
		config->idxhost01 = (void *)((char *)config->idxhost01 + offset);
		config->idxhost02 = (void *)((char *)config->idxhost02 + offset);
	}

	if (-1 == remove_index)
	{
		sz = sizeof(int) * (config->idxitem01_num - *index);
		src = (char *)&config->idxitem01[*index];
		dst = (char *)&config->idxitem01[*index + 1];
		config->idxitem01_num++;
	}
	else
	{
		if (*index > remove_index)
		{
			(*index)--;
			sz = sizeof(int) * (*index - remove_index);
			src = (char *)&config->idxitem01[remove_index + 1];
			dst = (char *)&config->idxitem01[remove_index];
		}
		else
		{
			sz = sizeof(int) * (remove_index - *index);
			src = (char *)&config->idxitem01[*index];
			dst = (char *)&config->idxitem01[*index + 1];
		}
	}

	if (0 != sz)
		memmove(dst, src, sz);
}

static void	DCallocate_idxitem02(int *index, int remove_index)
{
	size_t	sz;
	char	*src, *dst;

	if (config->idxitem02_num == config->idxitem02_alloc)
	{
		size_t	offset;

		offset = sizeof(int) * ITEM_ALLOC_STEP;

		DCcheck_freemem(offset);

		config->idxitem02_alloc += ITEM_ALLOC_STEP;

		src = (char *)config->hosts;
		if (0 != (sz = ((char *)config->idxhost02 - src) +
					sizeof(int) * config->idxhost02_num))
			memmove(src + offset, src, sz);

		config->hosts = (void *)((char *)config->hosts + offset);
		config->ipmihosts = (void *)((char *)config->ipmihosts + offset);
		config->idxhost01 = (void *)((char *)config->idxhost01 + offset);
		config->idxhost02 = (void *)((char *)config->idxhost02 + offset);
	}

	if (-1 == remove_index)
	{
		sz = sizeof(int) * (config->idxitem02_num - *index);
		src = (char *)&config->idxitem02[*index];
		dst = (char *)&config->idxitem02[*index + 1];
		config->idxitem02_num++;
	}
	else
	{
		if (*index > remove_index)
		{
			(*index)--;
			sz = sizeof(int) * (*index - remove_index);
			src = (char *)&config->idxitem02[remove_index + 1];
			dst = (char *)&config->idxitem02[remove_index];
		}
		else
		{
			sz = sizeof(int) * (remove_index - *index);
			src = (char *)&config->idxitem02[*index];
			dst = (char *)&config->idxitem02[*index + 1];
		}
	}

	if (0 != sz)
		memmove(dst, src, sz);
}

static void	DCallocate_host(int index)
{
	size_t	sz;

	if (config->hosts_num == config->hosts_alloc)
	{
		size_t	offset;
		char	*src;

		offset = sizeof(ZBX_DC_HOST) * HOST_ALLOC_STEP;

		DCcheck_freemem(offset);

		config->hosts_alloc += HOST_ALLOC_STEP;

		src = (char *)config->ipmihosts;
		if (0 != (sz = ((char *)config->idxhost02 - src) +
					sizeof(int) * config->idxhost02_num))
			memmove(src + offset, src, sz);

		config->ipmihosts = (void *)((char *)config->ipmihosts + offset);
		config->idxhost01 = (void *)((char *)config->idxhost01 + offset);
		config->idxhost02 = (void *)((char *)config->idxhost02 + offset);
	}

	if (0 != (sz = sizeof(ZBX_DC_HOST) * (config->hosts_num - index)))
		memmove(&config->hosts[index + 1], &config->hosts[index], sz);
	config->hosts_num++;
}

static void	DCallocate_ipmihost(int index)
{
	size_t	sz;

	if (config->ipmihosts_num == config->ipmihosts_alloc)
	{
		size_t	offset;
		char	*src;

		offset = sizeof(ZBX_DC_IPMIHOST) * IPMIHOST_ALLOC_STEP;

		DCcheck_freemem(offset);

		config->ipmihosts_alloc += IPMIHOST_ALLOC_STEP;

		src = (char *)config->idxhost01;
		if (0 != (sz = ((char *)config->idxhost02 - src) +
					sizeof(int) * config->idxhost02_num))
			memmove(src + offset, src, sz);

		config->idxhost01 = (void *)((char *)config->idxhost01 + offset);
		config->idxhost02 = (void *)((char *)config->idxhost02 + offset);
	}

	if (0 != (sz = sizeof(ZBX_DC_IPMIHOST) * (config->ipmihosts_num - index)))
		memmove(&config->ipmihosts[index + 1], &config->ipmihosts[index], sz);
	config->ipmihosts_num++;
}

static void	DCallocate_idxhost01(int *index, int remove_index)
{
	size_t	sz;
	char	*src, *dst;

	if (config->idxhost01_num == config->idxhost01_alloc)
	{
		size_t	offset;

		offset = sizeof(int) * HOST_ALLOC_STEP;

		DCcheck_freemem(offset);

		config->idxhost01_alloc += HOST_ALLOC_STEP;

		src = (char *)config->idxhost02;
		if (0 != (sz = sizeof(int) * config->idxhost02_num))
			memmove(src + offset, src, sz);

		config->idxhost02 = (void *)((char *)config->idxhost02 + offset);
	}

	if (-1 == remove_index)
	{
		sz = sizeof(int) * (config->idxhost01_num - *index);
		dst = (char *)&config->idxhost01[*index + 1];
		src = (char *)&config->idxhost01[*index];
		config->idxhost01_num++;
	}
	else
	{
		if (*index > remove_index)
		{
			(*index)--;
			sz = sizeof(int) * (*index - remove_index);
			src = (char *)&config->idxhost01[remove_index + 1];
			dst = (char *)&config->idxhost01[remove_index];
		}
		else
		{
			sz = sizeof(int) * (remove_index - *index);
			src = (char *)&config->idxhost01[*index];
			dst = (char *)&config->idxhost01[*index + 1];
		}
	}

	if (0 != sz)
		memmove(dst, src, sz);
}

static void	DCallocate_idxhost02(int *index, int remove_index)
{
	size_t	sz;
	char	*src, *dst;

	if (config->idxhost02_num == config->idxhost02_alloc)
	{
		size_t	offset;

		offset = sizeof(int) * HOST_ALLOC_STEP;

		DCcheck_freemem(offset);

		config->idxhost02_alloc += HOST_ALLOC_STEP;
	}

	if (-1 == remove_index)
	{
		sz = sizeof(int) * (config->idxhost02_num - *index);
		src = (char *)&config->idxhost02[*index];
		dst = (char *)&config->idxhost02[*index + 1];
		config->idxhost02_num++;
	}
	else
	{
		if (*index > remove_index)
		{
			(*index)--;
			sz = sizeof(int) * (*index - remove_index);
			src = (char *)&config->idxhost02[remove_index + 1];
			dst = (char *)&config->idxhost02[remove_index];
		}
		else
		{
			sz = sizeof(int) * (remove_index - *index);
			src = (char *)&config->idxhost02[*index];
			dst = (char *)&config->idxhost02[*index + 1];
		}
	}

	if (0 != sz)
		memmove(dst, src, sz);
}

static void	DCstrpool_replace(int found, const char **cur, const char *new)
{
	if (!found)
		*cur = zbx_strpool_intern(new);
	else if (0 != strcmp(new, *cur))
	{
		zbx_strpool_release(*cur);
		*cur = zbx_strpool_intern(new);
	}
}

static void	DCsync_items()
{
	const char	*__function_name = "DCsync_items";
	DB_RESULT	result;
	DB_ROW		row;
	ZBX_DC_ITEM	*item;
	ZBX_DC_SNMPITEM	*snmpitem;
	ZBX_DC_IPMIITEM	*ipmiitem;
	ZBX_DC_FLEXITEM	*flexitem;
	ZBX_DC_TRAPITEM	*trapitem;
	ZBX_DC_LOGITEM	*logitem;
	ZBX_DC_DBITEM	*dbitem;
	ZBX_DC_SSHITEM	*sshitem;
	ZBX_DC_TELNETITEM	*telnetitem;
	ZBX_DC_CALCITEM	*calcitem;
	zbx_uint64_t	itemid, proxy_hostid;
	int		i, found, delay;
	unsigned char	status;
	zbx_uint64_t	*ids = NULL;
	int		ids_allocated, ids_num = 0;
	time_t		now;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	ids_allocated = config->items_num ? config->items_num : ITEM_ALLOC_STEP;
	ids = zbx_malloc(ids, ids_allocated * sizeof(zbx_uint64_t));

	now = time(NULL);

	result = DBselect(
			"select i.itemid,i.hostid,h.proxy_hostid,i.type,i.data_type,i.value_type,i.key_,"
				"i.snmp_community,i.snmp_oid,i.snmp_port,i.snmpv3_securityname,"
				"i.snmpv3_securitylevel,i.snmpv3_authpassphrase,i.snmpv3_privpassphrase,"
				"i.ipmi_sensor,i.delay,i.delay_flex,i.trapper_hosts,i.logtimefmt,i.params,"
				"i.status,i.authtype,i.username,i.password,i.publickey,i.privatekey"
			" from items i,hosts h"
			" where i.hostid=h.hostid"
				" and h.status in (%d)"
				" and i.status in (%d,%d)"
				DB_NODE
			" order by i.itemid",
			HOST_STATUS_MONITORED,
			ITEM_STATUS_ACTIVE, ITEM_STATUS_NOTSUPPORTED,
			DBnode_local("i.itemid"));

	LOCK_CACHE;

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(itemid, row[0]);
		ZBX_STR2UINT64(proxy_hostid, row[2]);
		delay = atoi(row[15]);
		status = (unsigned char)atoi(row[20]);

		/* array of selected items */
		uint64_array_add(&ids, &ids_allocated, &ids_num, itemid, ITEM_ALLOC_STEP);

		i = get_nearestindex(config->items, sizeof(ZBX_DC_ITEM), config->items_num, itemid);
		found = (i < config->items_num && config->items[i].itemid == itemid);

		if (!found)
			DCallocate_item(i);

		item = &config->items[i];

		item->itemid = itemid;
		ZBX_STR2UINT64(item->hostid, row[1]);
		item->type = (unsigned char)atoi(row[3]);
		item->data_type = (unsigned char)atoi(row[4]);
		item->value_type = (unsigned char)atoi(row[5]);
		DCstrpool_replace(found, &item->key, row[6]);

		if (!found)
		{
			if (ITEM_STATUS_NOTSUPPORTED == status)
				item->nextcheck = calculate_item_nextcheck(itemid, item->type,
						CONFIG_REFRESH_UNSUPPORTED, NULL, now);
			else
				item->nextcheck = calculate_item_nextcheck(itemid, item->type,
						delay, row[16], now);
		}
		else
		{
			if (ITEM_STATUS_ACTIVE == status && (status != item->status || delay != item->delay))
				item->nextcheck = calculate_item_nextcheck(itemid, item->type,
						delay, row[16], now);
			else if (ITEM_STATUS_NOTSUPPORTED == status && status != item->status)
				item->nextcheck = calculate_item_nextcheck(itemid, item->type,
						CONFIG_REFRESH_UNSUPPORTED, NULL, now);
		}

		item->status = status;
		item->delay = delay;

		poller_by_item(itemid, item->hostid, proxy_hostid, item->type, item->key,
				&item->poller_type, &item->poller_num);

		/* SNMP items */

		i = get_nearestindex(config->snmpitems, sizeof(ZBX_DC_SNMPITEM),
				config->snmpitems_num, itemid);
		found = (i < config->snmpitems_num && config->snmpitems[i].itemid == itemid);

		if (ITEM_TYPE_SNMPv1 == item->type || ITEM_TYPE_SNMPv2c == item->type || ITEM_TYPE_SNMPv3 == item->type)
		{
			if (!found)
				DCallocate_snmpitem(i);

			snmpitem = &config->snmpitems[i];

			snmpitem->itemid = itemid;
			DCstrpool_replace(found, &snmpitem->snmp_community, row[7]);
			DCstrpool_replace(found, &snmpitem->snmp_oid, row[8]);
			snmpitem->snmp_port = (unsigned short)atoi(row[9]);
			DCstrpool_replace(found, &snmpitem->snmpv3_securityname, row[10]);
			snmpitem->snmpv3_securitylevel = (unsigned char)atoi(row[11]);
			DCstrpool_replace(found, &snmpitem->snmpv3_authpassphrase, row[12]);
			DCstrpool_replace(found, &snmpitem->snmpv3_privpassphrase, row[13]);

		}
		else if (found)
		{
			snmpitem = &config->snmpitems[i];

			/* remove snmp parameters for not snmp item */
			zbx_strpool_release(snmpitem->snmp_community);
			zbx_strpool_release(snmpitem->snmp_oid);
			zbx_strpool_release(snmpitem->snmpv3_securityname);
			zbx_strpool_release(snmpitem->snmpv3_authpassphrase);
			zbx_strpool_release(snmpitem->snmpv3_privpassphrase);

			DCremove_element(config->snmpitems, &config->snmpitems_num, sizeof(ZBX_DC_SNMPITEM), i);
		}

		/* IPMI items */

		i = get_nearestindex(config->ipmiitems, sizeof(ZBX_DC_IPMIITEM),
				config->ipmiitems_num, itemid);
		found = (i < config->ipmiitems_num && config->ipmiitems[i].itemid == itemid);

		if (ITEM_TYPE_IPMI == item->type)
		{
			if (!found)
				DCallocate_ipmiitem(i);

			ipmiitem = &config->ipmiitems[i];

			ipmiitem->itemid = itemid;
			DCstrpool_replace(found, &ipmiitem->ipmi_sensor, row[14]);
		}
		else if (found)
		{
			ipmiitem = &config->ipmiitems[i];

			/* remove ipmi parameters for not ipmi item */
			zbx_strpool_release(ipmiitem->ipmi_sensor);

			DCremove_element(config->ipmiitems, &config->ipmiitems_num, sizeof(ZBX_DC_IPMIITEM), i);
		}

		/* items with flexible intervals */

		i = get_nearestindex(config->flexitems, sizeof(ZBX_DC_FLEXITEM),
				config->flexitems_num, itemid);
		found = (i < config->flexitems_num && config->flexitems[i].itemid == itemid);

		if (SUCCEED != DBis_null(row[16]) && '\0' != *row[16])
		{
			if (!found)
				DCallocate_flexitem(i);

			flexitem = &config->flexitems[i];

			flexitem->itemid = itemid;
			DCstrpool_replace(found, &flexitem->delay_flex, row[16]);
		}
		else if (found)
		{
			flexitem = &config->flexitems[i];

			/* remove delay_flex parameter for not flexible item */
			zbx_strpool_release(flexitem->delay_flex);

			DCremove_element(config->flexitems, &config->flexitems_num, sizeof(ZBX_DC_FLEXITEM), i);
		}

		/* trapper items */

		i = get_nearestindex(config->trapitems, sizeof(ZBX_DC_TRAPITEM),
				config->trapitems_num, itemid);
		found = (i < config->trapitems_num && config->trapitems[i].itemid == itemid);

		if (ITEM_TYPE_TRAPPER == item->type && SUCCEED != DBis_null(row[17]) && '\0' != *row[17])
		{
			if (!found)
				DCallocate_trapitem(i);

			trapitem = &config->trapitems[i];

			trapitem->itemid = itemid;
			DCstrpool_replace(found, &trapitem->trapper_hosts, row[17]);
		}
		else if (found)
		{
			trapitem = &config->trapitems[i];

			/* remove trapper_hosts parameter */
			zbx_strpool_release(trapitem->trapper_hosts);

			DCremove_element(config->trapitems, &config->trapitems_num, sizeof(ZBX_DC_TRAPITEM), i);
		}

		/* log items */

		i = get_nearestindex(config->logitems, sizeof(ZBX_DC_LOGITEM),
				config->logitems_num, itemid);
		found = (i < config->logitems_num && config->logitems[i].itemid == itemid);

		if (ITEM_VALUE_TYPE_LOG == item->value_type && SUCCEED != DBis_null(row[18]) && '\0' != *row[18])
		{
			if (!found)
				DCallocate_logitem(i);

			logitem = &config->logitems[i];

			logitem->itemid = itemid;
			DCstrpool_replace(found, &logitem->logtimefmt, row[18]);
		}
		else if (found)
		{
			logitem = &config->logitems[i];

			/* remove logtimefmt parameter */
			zbx_strpool_release(logitem->logtimefmt);

			DCremove_element(config->logitems, &config->logitems_num, sizeof(ZBX_DC_LOGITEM), i);
		}

		/* db items */

		i = get_nearestindex(config->dbitems, sizeof(ZBX_DC_DBITEM),
				config->dbitems_num, itemid);
		found = (i < config->dbitems_num && config->dbitems[i].itemid == itemid);

		if (ITEM_TYPE_DB_MONITOR == item->type && SUCCEED != DBis_null(row[19]) && '\0' != *row[19])
		{
			if (!found)
				DCallocate_dbitem(i);

			dbitem = &config->dbitems[i];

			dbitem->itemid = itemid;
			DCstrpool_replace(found, &dbitem->params, row[19]);
		}
		else if (found)
		{
			dbitem = &config->dbitems[i];

			/* remove db item parameters */
			zbx_strpool_release(dbitem->params);

			DCremove_element(config->dbitems, &config->dbitems_num, sizeof(ZBX_DC_DBITEM), i);
		}

		/* SSH items */

		i = get_nearestindex(config->sshitems, sizeof(ZBX_DC_SSHITEM),
				config->sshitems_num, itemid);
		found = (i < config->sshitems_num && config->sshitems[i].itemid == itemid);

		if (ITEM_TYPE_SSH == item->type)
		{
			if (!found)
				DCallocate_sshitem(i);

			sshitem = &config->sshitems[i];

			sshitem->itemid = itemid;
			sshitem->authtype = (unsigned short)atoi(row[21]);
			DCstrpool_replace(found, &sshitem->username, row[22]);
			DCstrpool_replace(found, &sshitem->password, row[23]);
			DCstrpool_replace(found, &sshitem->publickey, row[24]);
			DCstrpool_replace(found, &sshitem->privatekey, row[25]);
			DCstrpool_replace(found, &sshitem->params, row[19]);
		}
		else if (found)
		{
			sshitem = &config->sshitems[i];

			/* remove SSH item parameters */
			zbx_strpool_release(sshitem->username);
			zbx_strpool_release(sshitem->password);
			zbx_strpool_release(sshitem->publickey);
			zbx_strpool_release(sshitem->privatekey);
			zbx_strpool_release(sshitem->params);

			DCremove_element(config->sshitems, &config->sshitems_num, sizeof(ZBX_DC_SSHITEM), i);
		}

		/* TELNET items */

		i = get_nearestindex(config->telnetitems, sizeof(ZBX_DC_TELNETITEM),
				config->telnetitems_num, itemid);
		found = (i < config->telnetitems_num && config->telnetitems[i].itemid == itemid);

		if (ITEM_TYPE_TELNET == item->type)
		{
			if (!found)
				DCallocate_telnetitem(i);

			telnetitem = &config->telnetitems[i];

			telnetitem->itemid = itemid;
			DCstrpool_replace(found, &telnetitem->username, row[22]);
			DCstrpool_replace(found, &telnetitem->password, row[23]);
			DCstrpool_replace(found, &telnetitem->params, row[19]);
		}
		else if (found)
		{
			telnetitem = &config->telnetitems[i];

			/* remove TELNET item parameters */
			zbx_strpool_release(telnetitem->username);
			zbx_strpool_release(telnetitem->password);
			zbx_strpool_release(telnetitem->params);

			DCremove_element(config->telnetitems, &config->telnetitems_num, sizeof(ZBX_DC_TELNETITEM), i);
		}

		/* CALCULATED items */

		i = get_nearestindex(config->calcitems, sizeof(ZBX_DC_CALCITEM),
				config->calcitems_num, itemid);
		found = (i < config->calcitems_num && config->calcitems[i].itemid == itemid);

		if (ITEM_TYPE_CALCULATED == item->type)
		{
			if (!found)
				DCallocate_calcitem(i);

			calcitem = &config->calcitems[i];

			calcitem->itemid = itemid;
			DCstrpool_replace(found, &calcitem->params, row[19]);
		}
		else if (found)
		{
			calcitem = &config->calcitems[i];

			/* remove CALCULATED item parameters */
			zbx_strpool_release(calcitem->params);

			DCremove_element(config->calcitems, &config->calcitems_num, sizeof(ZBX_DC_CALCITEM), i);
		}
	}

	/* remove deleted or disabled items from buffer */
	for (i = 0; i < config->items_num; i++)
		if (FAIL == uint64_array_exists(ids, ids_num, config->items[i].itemid))
		{
			zbx_strpool_release(config->items[i].key);
			DCremove_element(config->items, &config->items_num, sizeof(ZBX_DC_ITEM), i--);
		}

	/* create indexes */
	config->idxitem01_num = 0;
	config->idxitem02_num = 0;
	for (i = 0; i < config->items_num; i++)
	{
		item = &config->items[i];
		DCupdate_idxitem01(i, NULL, NULL, &item->hostid, item->key);
		DCupdate_idxitem02(i, NULL, NULL, NULL, &item->poller_type, &item->poller_num, &item->nextcheck);
	}

	/* remove deleted or disabled snmp items from buffer */
	for (i = 0; i < config->snmpitems_num; i++)
		if (FAIL == uint64_array_exists(ids, ids_num, config->snmpitems[i].itemid))
		{
			snmpitem = &config->snmpitems[i];

			zbx_strpool_release(snmpitem->snmp_community);
			zbx_strpool_release(snmpitem->snmp_oid);
			zbx_strpool_release(snmpitem->snmpv3_securityname);
			zbx_strpool_release(snmpitem->snmpv3_authpassphrase);
			zbx_strpool_release(snmpitem->snmpv3_privpassphrase);

			DCremove_element(config->snmpitems, &config->snmpitems_num, sizeof(ZBX_DC_SNMPITEM), i--);
		}

	/* remove deleted or disabled ipmi items from buffer */
	for (i = 0; i < config->ipmiitems_num; i++)
		if (FAIL == uint64_array_exists(ids, ids_num, config->ipmiitems[i].itemid))
		{
			zbx_strpool_release(config->ipmiitems[i].ipmi_sensor);
			DCremove_element(config->ipmiitems, &config->ipmiitems_num, sizeof(ZBX_DC_IPMIITEM), i--);
		}

	/* remove deleted or disabled flexible items from buffer */
	for (i = 0; i < config->flexitems_num; i++)
		if (FAIL == uint64_array_exists(ids, ids_num, config->flexitems[i].itemid))
		{
			zbx_strpool_release(config->flexitems[i].delay_flex);
			DCremove_element(config->flexitems, &config->flexitems_num, sizeof(ZBX_DC_FLEXITEM), i--);
		}

	/* remove deleted or disabled trapper items from buffer */
	for (i = 0; i < config->trapitems_num; i++)
		if (FAIL == uint64_array_exists(ids, ids_num, config->trapitems[i].itemid))
		{
			zbx_strpool_release(config->trapitems[i].trapper_hosts);
			DCremove_element(config->trapitems, &config->trapitems_num, sizeof(ZBX_DC_TRAPITEM), i--);
		}

	/* remove deleted or disabled log items from buffer */
	for (i = 0; i < config->logitems_num; i++)
		if (FAIL == uint64_array_exists(ids, ids_num, config->logitems[i].itemid))
		{
			zbx_strpool_release(config->logitems[i].logtimefmt);
			DCremove_element(config->logitems, &config->logitems_num, sizeof(ZBX_DC_LOGITEM), i--);
		}

	/* remove deleted or disabled db items from buffer */
	for (i = 0; i < config->dbitems_num; i++)
		if (FAIL == uint64_array_exists(ids, ids_num, config->dbitems[i].itemid))
		{
			zbx_strpool_release(config->dbitems[i].params);
			DCremove_element(config->dbitems, &config->dbitems_num, sizeof(ZBX_DC_DBITEM), i--);
		}

	/* remove deleted or disabled SSH items from buffer */
	for (i = 0; i < config->sshitems_num; i++)
		if (FAIL == uint64_array_exists(ids, ids_num, config->sshitems[i].itemid))
		{
			sshitem = &config->sshitems[i];

			zbx_strpool_release(sshitem->username);
			zbx_strpool_release(sshitem->password);
			zbx_strpool_release(sshitem->publickey);
			zbx_strpool_release(sshitem->privatekey);
			zbx_strpool_release(sshitem->params);

			DCremove_element(config->sshitems, &config->sshitems_num, sizeof(ZBX_DC_SSHITEM), i--);
		}

	/* remove deleted or disabled TELNET items from buffer */
	for (i = 0; i < config->telnetitems_num; i++)
		if (FAIL == uint64_array_exists(ids, ids_num, config->telnetitems[i].itemid))
		{
			telnetitem = &config->telnetitems[i];

			zbx_strpool_release(telnetitem->username);
			zbx_strpool_release(telnetitem->password);
			zbx_strpool_release(telnetitem->params);

			DCremove_element(config->telnetitems, &config->telnetitems_num, sizeof(ZBX_DC_TELNETITEM), i--);
		}

	/* remove deleted or disabled CALCULATED items from buffer */
	for (i = 0; i < config->calcitems_num; i++)
		if (FAIL == uint64_array_exists(ids, ids_num, config->calcitems[i].itemid))
		{
			zbx_strpool_release(config->calcitems[i].params);
			DCremove_element(config->calcitems, &config->calcitems_num, sizeof(ZBX_DC_CALCITEM), i--);
		}

	UNLOCK_CACHE;

	DBfree_result(result);

	zbx_free(ids);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

static void	DCsync_hosts()
{
	const char	*__function_name = "DCsync_hosts";
	DB_RESULT	result;
	DB_ROW		row;
	ZBX_DC_HOST	*host;
	ZBX_DC_IPMIHOST	*ipmihost;
	zbx_uint64_t	hostid;
	int		i, found;
	zbx_uint64_t	*ids = NULL;
	int		ids_allocated, ids_num = 0;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	ids_allocated = config->hosts_num ? config->hosts_num : HOST_ALLOC_STEP;
	ids = zbx_malloc(ids, ids_allocated * sizeof(zbx_uint64_t));

	result = DBselect(
			"select hostid,proxy_hostid,host,useip,ip,dns,port,"
				"useipmi,ipmi_ip,ipmi_port,ipmi_authtype,ipmi_privilege,ipmi_username,"
				"ipmi_password,maintenance_status,maintenance_type,maintenance_from,"
				"errors_from,available,disable_until,snmp_errors_from,snmp_available,"
				"snmp_disable_until,ipmi_errors_from,ipmi_available,ipmi_disable_until"
			" from hosts"
			" where status in (%d)"
				DB_NODE
			" order by hostid",
			HOST_STATUS_MONITORED,
			DBnode_local("hostid"));

	LOCK_CACHE;

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(hostid, row[0]);

		/* array of selected hosts */
		uint64_array_add(&ids, &ids_allocated, &ids_num, hostid, HOST_ALLOC_STEP);

		i = get_nearestindex(config->hosts, sizeof(ZBX_DC_HOST), config->hosts_num, hostid);
		found = (i < config->hosts_num && config->hosts[i].hostid == hostid);

		if (!found)
			DCallocate_host(i);

		host = &config->hosts[i];

		host->hostid = hostid;
		ZBX_STR2UINT64(host->proxy_hostid, row[1]);
		DCstrpool_replace(found, &host->host, row[2]);
		host->useip = (unsigned char)atoi(row[3]);
		DCstrpool_replace(found, &host->ip, row[4]);
		DCstrpool_replace(found, &host->dns, row[5]);
		host->port = (unsigned short)atoi(row[6]);
		host->maintenance_status = (unsigned char)atoi(row[14]);
		host->maintenance_type = (unsigned char)atoi(row[15]);
		host->maintenance_from = atoi(row[16]);
		host->errors_from = atoi(row[17]);
		host->available = (unsigned char)atoi(row[18]);
		host->disable_until = atoi(row[19]);
		host->snmp_errors_from = atoi(row[20]);
		host->snmp_available = (unsigned char)atoi(row[21]);
		host->snmp_disable_until = atoi(row[22]);
		host->ipmi_errors_from = atoi(row[23]);
		host->ipmi_available = (unsigned char)atoi(row[24]);
		host->ipmi_disable_until = atoi(row[25]);

		if (!found)
		{
			host->nextcheck = DCget_unreachable_nextcheck(host->disable_until,
					host->snmp_disable_until, host->ipmi_disable_until);
		}

		poller_by_host(hostid, host->proxy_hostid,
				host->errors_from || host->snmp_errors_from || host->ipmi_errors_from,
				&host->poller_type, &host->poller_num);

		i = get_nearestindex(config->ipmihosts, sizeof(ZBX_DC_IPMIHOST),
				config->ipmihosts_num, hostid);
		found = (i < config->ipmihosts_num && config->ipmihosts[i].hostid == hostid);

		if (1 == atoi(row[7]))	/* useipmi */
		{
			if (!found)
				DCallocate_ipmihost(i);

			ipmihost = &config->ipmihosts[i];

			ipmihost->hostid = hostid;
			DCstrpool_replace(found, &ipmihost->ipmi_ip, row[8]);
			ipmihost->ipmi_port = (unsigned short)atoi(row[9]);
			ipmihost->ipmi_authtype = (signed char)atoi(row[10]);
			ipmihost->ipmi_privilege = (unsigned char)atoi(row[11]);
			DCstrpool_replace(found, &ipmihost->ipmi_username, row[12]);
			DCstrpool_replace(found, &ipmihost->ipmi_password, row[13]);
		}
		else if (found)
		{
			ipmihost = &config->ipmihosts[i];

			/* remove ipmi connection parameters for hosts without ipmi */
			zbx_strpool_release(ipmihost->ipmi_ip);
			zbx_strpool_release(ipmihost->ipmi_username);
			zbx_strpool_release(ipmihost->ipmi_password);

			DCremove_element(config->ipmihosts, &config->ipmihosts_num, sizeof(ZBX_DC_IPMIHOST), i);
		}
	}

	/* remove deleted or disabled hosts from buffer */
	for (i = 0; i < config->hosts_num; i++)
		if (FAIL == uint64_array_exists(ids, ids_num, config->hosts[i].hostid))
		{
			host = &config->hosts[i];

			zbx_strpool_release(host->host);
			zbx_strpool_release(host->ip);
			zbx_strpool_release(host->dns);

			DCremove_element(config->hosts, &config->hosts_num, sizeof(ZBX_DC_HOST), i--);
		}

	/* create indexes */
	config->idxhost01_num = 0;
	config->idxhost02_num = 0;
	for (i = 0; i < config->hosts_num; i++)
	{
		host = &config->hosts[i];
		DCupdate_idxhost01(i, NULL, NULL, &host->proxy_hostid, host->host);
		DCupdate_idxhost02(i, NULL, NULL, NULL, &host->poller_type, &host->poller_num, &host->nextcheck);
	}

	/* remove ipmi connection parameters for deleted or disabled hosts from buffer */
	for (i = 0; i < config->ipmihosts_num; i++)
		if (FAIL == uint64_array_exists(ids, ids_num, config->ipmihosts[i].hostid))
		{
			ipmihost = &config->ipmihosts[i];

			zbx_strpool_release(ipmihost->ipmi_ip);
			zbx_strpool_release(ipmihost->ipmi_username);
			zbx_strpool_release(ipmihost->ipmi_password);

			DCremove_element(config->ipmihosts, &config->ipmihosts_num, sizeof(ZBX_DC_IPMIHOST), i--);
		}

	UNLOCK_CACHE;

	DBfree_result(result);

	zbx_free(ids);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

/******************************************************************************
 *                                                                            *
 * Function: DCsync_configuration                                             *
 *                                                                            *
 * Purpose: Synchronize configuration data from database                      *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Aleksander Vladishev                                               *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
void	DCsync_configuration()
{
	const char	*__function_name = "DCsync_configuration";

	double			sec;
	const zbx_strpool_t	*strpool;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	sec = zbx_time();
	DCsync_items();
	DCsync_hosts();
	sec = zbx_time() - sec;

	strpool = zbx_strpool_info();

	zabbix_log(LOG_LEVEL_DEBUG, "%s() time       : " ZBX_FS_DBL " sec.", __function_name,
			sec);

	zabbix_log(LOG_LEVEL_DEBUG, "%s() items      : %d / %d", __function_name,
			config->items_num, config->items_alloc);
	zabbix_log(LOG_LEVEL_DEBUG, "%s() snmpitems  : %d / %d", __function_name,
			config->snmpitems_num, config->snmpitems_alloc);
	zabbix_log(LOG_LEVEL_DEBUG, "%s() ipmiitems  : %d / %d", __function_name,
			config->ipmiitems_num, config->ipmiitems_alloc);
	zabbix_log(LOG_LEVEL_DEBUG, "%s() flexitems  : %d / %d", __function_name,
			config->flexitems_num, config->flexitems_alloc);
	zabbix_log(LOG_LEVEL_DEBUG, "%s() trapitems  : %d / %d", __function_name,
			config->trapitems_num, config->trapitems_alloc);
	zabbix_log(LOG_LEVEL_DEBUG, "%s() logitems   : %d / %d", __function_name,
			config->logitems_num, config->logitems_alloc);
	zabbix_log(LOG_LEVEL_DEBUG, "%s() dbitems    : %d / %d", __function_name,
			config->dbitems_num, config->dbitems_alloc);
	zabbix_log(LOG_LEVEL_DEBUG, "%s() sshitems   : %d / %d", __function_name,
			config->sshitems_num, config->sshitems_alloc);
	zabbix_log(LOG_LEVEL_DEBUG, "%s() telnetitems: %d / %d", __function_name,
			config->telnetitems_num, config->telnetitems_alloc);
	zabbix_log(LOG_LEVEL_DEBUG, "%s() calcitems  : %d / %d", __function_name,
			config->calcitems_num, config->calcitems_alloc);
	zabbix_log(LOG_LEVEL_DEBUG, "%s() hosts      : %d / %d", __function_name,
			config->hosts_num, config->hosts_alloc);
	zabbix_log(LOG_LEVEL_DEBUG, "%s() ipmihosts  : %d / %d", __function_name,
			config->ipmihosts_num, config->ipmihosts_alloc);
	zabbix_log(LOG_LEVEL_DEBUG, "%s() idxitem01  : %d / %d", __function_name,
			config->idxitem01_num, config->idxitem01_alloc);
	zabbix_log(LOG_LEVEL_DEBUG, "%s() idxitem02  : %d / %d", __function_name,
			config->idxitem02_num, config->idxitem02_alloc);
	zabbix_log(LOG_LEVEL_DEBUG, "%s() idxhost01  : %d / %d", __function_name,
			config->idxhost01_num, config->idxhost01_alloc);
	zabbix_log(LOG_LEVEL_DEBUG, "%s() idxhost02  : %d / %d", __function_name,
			config->idxhost02_num, config->idxhost02_alloc);
	zabbix_log(LOG_LEVEL_DEBUG, "%s() configfree : " ZBX_FS_DBL "%%", __function_name,
			100 * ((double)config->free_mem / config_size));

	zabbix_log(LOG_LEVEL_DEBUG, "%s() strings    : %d (%d slots)", __function_name,
			strpool->hashset.num_data, strpool->hashset.num_slots);
	zabbix_log(LOG_LEVEL_DEBUG, "%s() strpoolfree: " ZBX_FS_DBL "%%", __function_name,
			100 * ((double)strpool->mem_info.free_size / strpool->mem_info.orig_size));

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

/******************************************************************************
 *                                                                            *
 * Function: init_configuration_cache                                         *
 *                                                                            *
 * Purpose: Allocate shared memory for configuration cache                    *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Aleksander Vladishev                                               *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
void	init_configuration_cache()
{
	const char	*__function_name = "init_configuration_cache";

	key_t	shm_key;
	size_t	sz;
	size_t	strpool_size;
	void	*ptr;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() size:%d", __function_name, CONFIG_DBCONFIG_SIZE);

	strpool_size = (size_t)(CONFIG_DBCONFIG_SIZE * 0.25);
	config_size = CONFIG_DBCONFIG_SIZE - strpool_size;

	sz = sizeof(ZBX_DC_CONFIG);

	if (config_size < sz)
	{
		zbx_error("Configuration buffer is too small. Please increase CacheSize parameter.");
		exit(FAIL);
	}

	if (-1 == (shm_key = zbx_ftok(CONFIG_FILE, (int)'k')))
	{
		zbx_error("Can't create IPC key for configuration cache");
		exit(FAIL);
	}

	if (-1 == (shm_id = zbx_shmget(shm_key, config_size)))
	{
		zabbix_log(LOG_LEVEL_CRIT, "Can't allocate shared memory for configuration cache");
		exit(FAIL);
	}

	ptr = shmat(shm_id, NULL, 0);

	if ((void*)(-1) == ptr)
	{
		zabbix_log(LOG_LEVEL_CRIT, "Can't attach shared memory for configuration cache. [%s]",
				strerror(errno));
		exit(FAIL);
	}

	if (ZBX_MUTEX_ERROR == zbx_mutex_create_force(&config_lock, ZBX_MUTEX_CONFIG))
	{
		zbx_error("Unable to create mutex for configuration cache");
		exit(FAIL);
	}

	config = ptr;
	memset(config, 0, sz);
	config->free_mem = config_size - sz;

	config->items = ptr + sz;
	config->snmpitems = ptr + sz;
	config->ipmiitems = ptr + sz;
	config->flexitems = ptr + sz;
	config->trapitems = ptr + sz;
	config->logitems = ptr + sz;
	config->dbitems = ptr + sz;
	config->sshitems = ptr + sz;
	config->telnetitems = ptr + sz;
	config->calcitems = ptr + sz;
	config->idxitem01 = ptr + sz;	/* hostid,key */
	config->idxitem02 = ptr + sz;	/* poller_type,poller_num,nextcheck */
	config->hosts = ptr + sz;
	config->ipmihosts = ptr + sz;
	config->idxhost01 = ptr + sz;	/* proxy_hostid,host */
	config->idxhost02 = ptr + sz;	/* poller_type,poller_num,nextcheck */

	zbx_strpool_create(strpool_size);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

/******************************************************************************
 *                                                                            *
 * Function: free_configuration_cache                                         *
 *                                                                            *
 * Purpose: Free memory allocated for configuration cache                     *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
void	free_configuration_cache()
{
	const char	*__function_name = "free_configuration_cache";

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	LOCK_CACHE;

	if (-1 == shmctl(shm_id, IPC_RMID, 0))
	{
		zabbix_log(LOG_LEVEL_WARNING, "Can't remove shared memory"
				" for configuration cache. [%s]",
				strerror(errno));
	}

	config = NULL;

	zbx_strpool_destroy();

	UNLOCK_CACHE;

	zbx_mutex_destroy(&config_lock);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

static void	DCget_host(DC_HOST *dst_host, ZBX_DC_HOST *src_host)
{
	int		index;
	ZBX_DC_IPMIHOST	*ipmihost;

	dst_host->hostid = src_host->hostid;
	dst_host->proxy_hostid = src_host->proxy_hostid;
	strscpy(dst_host->host, src_host->host);
	dst_host->useip = src_host->useip;
	strscpy(dst_host->ip, src_host->ip);
	strscpy(dst_host->dns, src_host->dns);
	dst_host->port = src_host->port;
	dst_host->maintenance_status = src_host->maintenance_status;
	dst_host->maintenance_type = src_host->maintenance_type;
	dst_host->maintenance_from = src_host->maintenance_from;
	dst_host->errors_from = src_host->errors_from;
	dst_host->available = src_host->available;
	dst_host->disable_until = src_host->disable_until;
	dst_host->snmp_errors_from = src_host->snmp_errors_from;
	dst_host->snmp_available = src_host->snmp_available;
	dst_host->snmp_disable_until = src_host->snmp_disable_until;
	dst_host->ipmi_errors_from = src_host->ipmi_errors_from;
	dst_host->ipmi_available = src_host->ipmi_available;
	dst_host->ipmi_disable_until = src_host->ipmi_disable_until;

	index = get_nearestindex(config->ipmihosts, sizeof(ZBX_DC_IPMIHOST),
			config->ipmihosts_num, src_host->hostid);
	if (index < config->ipmihosts_num && config->ipmihosts[index].hostid == src_host->hostid)
	{
		ipmihost = &config->ipmihosts[index];
		strscpy(dst_host->ipmi_ip_orig, ipmihost->ipmi_ip);
		dst_host->ipmi_ip = NULL;
		dst_host->ipmi_port = ipmihost->ipmi_port;
		dst_host->ipmi_authtype = ipmihost->ipmi_authtype;
		dst_host->ipmi_privilege = ipmihost->ipmi_privilege;
		strscpy(dst_host->ipmi_username, ipmihost->ipmi_username);
		strscpy(dst_host->ipmi_password, ipmihost->ipmi_password);
	}
}

/******************************************************************************
 *                                                                            *
 * Function: DCget_host_by_name                                               *
 *                                                                            *
 * Purpose: Locate host in configuration cache                                *
 *                                                                            *
 * Parameters: host - [OUT] pointer to DC_HOST structure                      *
 *             proxy_hostid - [IN] proxy hostid                               *
 *             host - [IN] host name                                          *
 *                                                                            *
 * Return value: SUCCEED if record located and FAIL otherwise                 *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments: !!! configuration cache must be locked !!!                       *
 *                                                                            *
 ******************************************************************************/
static int	DCget_host_by_name(DC_HOST *host, zbx_uint64_t proxy_hostid, const char *hostname)
{
	int		index, res = FAIL;
	ZBX_DC_HOST	*dc_host;

	index = DCget_idxhost01_nearestindex(proxy_hostid, hostname);
	if (index == config->idxhost01_num)
		goto error;

	dc_host = &config->hosts[config->idxhost01[index]];
	if (dc_host->proxy_hostid != proxy_hostid || 0 != strcmp(dc_host->host, hostname))
		goto error;

	DCget_host(host, dc_host);

	res = SUCCEED;
error:
	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: DCget_dc_host                                                    *
 *                                                                            *
 * Purpose: Locate host record in configuration cache by hostid               *
 *                                                                            *
 * Parameters: hostid - [IN] hostid                                           *
 *                                                                            *
 * Return value: pointer to ZBX_DC_HOST structure or NULL otherwise           *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments: !!! configuration cache must be locked !!!                       *
 *                                                                            *
 ******************************************************************************/
static ZBX_DC_HOST	*DCget_dc_host(zbx_uint64_t hostid)
{
	int		index;
	ZBX_DC_HOST	*dc_host;

	index = get_nearestindex(config->hosts, sizeof(ZBX_DC_HOST), config->hosts_num, hostid);
	if (index == config->hosts_num)
		return NULL;

	dc_host = &config->hosts[index];
	if (dc_host->hostid != hostid)
		return NULL;

	return dc_host;
}

/******************************************************************************
 *                                                                            *
 * Function: DCget_host_by_hostid                                             *
 *                                                                            *
 * Purpose: Locate host in configuration cache                                *
 *                                                                            *
 * Parameters: host - [OUT] pointer to DC_HOST structure                      *
 *             hostid - [IN] host ID from database                            *
 *                                                                            *
 * Return value: SUCCEED if record located and FAIL otherwise                 *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments: !!! configuration cache must be locked !!!                       *
 *                                                                            *
 ******************************************************************************/
int	DCget_host_by_hostid(DC_HOST *host, zbx_uint64_t hostid)
{
	int		res = FAIL;
	ZBX_DC_HOST	*dc_host;

	LOCK_CACHE;

	if (NULL == (dc_host = DCget_dc_host(hostid)))
		goto unlock;

	DCget_host(host, dc_host);

	res = SUCCEED;
unlock:
	UNLOCK_CACHE;

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: DCget_dc_flexitem                                                *
 *                                                                            *
 * Purpose: Locate item record in configuration cache by itemid               *
 *                                                                            *
 * Parameters: itemid - [IN] itemid                                           *
 *                                                                            *
 * Return value: pointer to ZBX_DC_FLEXITEM structure or NULL otherwise       *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments: !!! configuration cache must be locked !!!                       *
 *                                                                            *
 ******************************************************************************/
static ZBX_DC_FLEXITEM	*DCget_dc_flexitem(zbx_uint64_t itemid)
{
	int		index;
	ZBX_DC_FLEXITEM	*dc_flexitem;

	index = get_nearestindex(config->flexitems, sizeof(ZBX_DC_FLEXITEM), config->flexitems_num, itemid);
	if (index == config->flexitems_num)
		return NULL;

	dc_flexitem = &config->flexitems[index];
	if (dc_flexitem->itemid != itemid)
		return NULL;

	return dc_flexitem;
}

static void	DCget_item(DC_ITEM *dst_item, ZBX_DC_ITEM *src_item)
{
	int		index;
	ZBX_DC_LOGITEM	*logitem;
	ZBX_DC_SNMPITEM	*snmpitem;
	ZBX_DC_TRAPITEM	*trapitem;
	ZBX_DC_IPMIITEM	*ipmiitem;
	ZBX_DC_DBITEM	*dbitem;
	ZBX_DC_FLEXITEM	*dc_flexitem;
	ZBX_DC_SSHITEM	*sshitem;
	ZBX_DC_TELNETITEM	*telnetitem;
	ZBX_DC_CALCITEM	*calcitem;

	dst_item->itemid = src_item->itemid;
	dst_item->type = src_item->type;
	dst_item->data_type = src_item->data_type;
	dst_item->value_type = src_item->value_type;
	strscpy(dst_item->key_orig, src_item->key);
	dst_item->key = NULL;
	dst_item->delay = src_item->delay;
	dst_item->nextcheck = src_item->nextcheck;
	dst_item->status = src_item->status;
	*dst_item->trapper_hosts = '\0';
	*dst_item->logtimefmt = '\0';
	*dst_item->delay_flex = '\0';

	if (ITEM_VALUE_TYPE_LOG == dst_item->value_type)
	{
		index = get_nearestindex(config->logitems, sizeof(ZBX_DC_LOGITEM),
				config->logitems_num, src_item->itemid);
		if (index < config->logitems_num && config->logitems[index].itemid == src_item->itemid)
		{
			logitem = &config->logitems[index];
			strscpy(dst_item->logtimefmt, logitem->logtimefmt);
		}
	}

	switch (src_item->type) {
	case ITEM_TYPE_SNMPv1:
	case ITEM_TYPE_SNMPv2c:
	case ITEM_TYPE_SNMPv3:
		index = get_nearestindex(config->snmpitems, sizeof(ZBX_DC_SNMPITEM),
				config->snmpitems_num, src_item->itemid);
		if (index < config->snmpitems_num && config->snmpitems[index].itemid == src_item->itemid)
		{
			snmpitem = &config->snmpitems[index];
			strscpy(dst_item->snmp_community, snmpitem->snmp_community);
			strscpy(dst_item->snmp_oid, snmpitem->snmp_oid);
			dst_item->snmp_port = snmpitem->snmp_port;
			strscpy(dst_item->snmpv3_securityname, snmpitem->snmpv3_securityname);
			dst_item->snmpv3_securitylevel = snmpitem->snmpv3_securitylevel;
			strscpy(dst_item->snmpv3_authpassphrase, snmpitem->snmpv3_authpassphrase);
			strscpy(dst_item->snmpv3_privpassphrase, snmpitem->snmpv3_privpassphrase);
		}
		break;
	case ITEM_TYPE_TRAPPER:
		index = get_nearestindex(config->trapitems, sizeof(ZBX_DC_TRAPITEM),
				config->trapitems_num, src_item->itemid);
		if (index < config->trapitems_num && config->trapitems[index].itemid == src_item->itemid)
		{
			trapitem = &config->trapitems[index];
			strscpy(dst_item->trapper_hosts, trapitem->trapper_hosts);
		}
		break;
	case ITEM_TYPE_IPMI:
		index = get_nearestindex(config->ipmiitems, sizeof(ZBX_DC_IPMIITEM),
				config->ipmiitems_num, src_item->itemid);
		if (index < config->ipmiitems_num && config->ipmiitems[index].itemid == src_item->itemid)
		{
			ipmiitem = &config->ipmiitems[index];
			strscpy(dst_item->ipmi_sensor, ipmiitem->ipmi_sensor);
		}
		break;
	case ITEM_TYPE_DB_MONITOR:
		index = get_nearestindex(config->dbitems, sizeof(ZBX_DC_DBITEM),
				config->dbitems_num, src_item->itemid);
		if (index < config->dbitems_num && config->dbitems[index].itemid == src_item->itemid)
		{
			dbitem = &config->dbitems[index];
			strscpy(dst_item->params_orig, dbitem->params);
			dst_item->params = NULL;
		}
		break;
	case ITEM_TYPE_SSH:
		index = get_nearestindex(config->sshitems, sizeof(ZBX_DC_SSHITEM),
				config->sshitems_num, src_item->itemid);
		if (index < config->sshitems_num && config->sshitems[index].itemid == src_item->itemid)
		{
			sshitem = &config->sshitems[index];
			dst_item->authtype = sshitem->authtype;
			strscpy(dst_item->username_orig, sshitem->username);
			strscpy(dst_item->publickey_orig, sshitem->publickey);
			strscpy(dst_item->privatekey_orig, sshitem->privatekey);
			strscpy(dst_item->password_orig, sshitem->password);
			strscpy(dst_item->params_orig, sshitem->params);
			dst_item->username = NULL;
			dst_item->publickey = NULL;
			dst_item->privatekey = NULL;
			dst_item->password = NULL;
			dst_item->params = NULL;
		}
		break;
	case ITEM_TYPE_TELNET:
		index = get_nearestindex(config->telnetitems, sizeof(ZBX_DC_TELNETITEM),
				config->telnetitems_num, src_item->itemid);
		if (index < config->telnetitems_num && config->telnetitems[index].itemid == src_item->itemid)
		{
			telnetitem = &config->telnetitems[index];
			strscpy(dst_item->username_orig, telnetitem->username);
			strscpy(dst_item->password_orig, telnetitem->password);
			strscpy(dst_item->params_orig, telnetitem->params);
			dst_item->username = NULL;
			dst_item->password = NULL;
			dst_item->params = NULL;
		}
		break;
	case ITEM_TYPE_CALCULATED:
		index = get_nearestindex(config->calcitems, sizeof(ZBX_DC_CALCITEM),
				config->calcitems_num, src_item->itemid);
		if (index < config->calcitems_num && config->calcitems[index].itemid == src_item->itemid)
		{
			calcitem = &config->calcitems[index];
			strscpy(dst_item->params_orig, calcitem->params);
			dst_item->params = NULL;
		}
		break;
	default:
		/* nothing to do */;
	}

	if (NULL != (dc_flexitem = DCget_dc_flexitem(src_item->itemid)))
		strscpy(dst_item->delay_flex, dc_flexitem->delay_flex);
}

/******************************************************************************
 *                                                                            *
 * Function: DCconfig_get_item_by_key                                         *
 *                                                                            *
 * Purpose: Locate item in configuration cache                                *
 *                                                                            *
 * Parameters: item - [OUT] pointer to DC_ITEM structure                      *
 *             hostid - [IN] host ID                                          *
 *             key - [IN] item key                                            *
 *                                                                            *
 * Return value: SUCCEED if record located and FAIL otherwise                 *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
int	DCconfig_get_item_by_key(DC_ITEM *item, zbx_uint64_t proxy_hostid, const char *hostname, const char *key)
{
	int		index, res = FAIL;
	ZBX_DC_ITEM	*dc_item;

	LOCK_CACHE;

	if (SUCCEED != DCget_host_by_name(&item->host, proxy_hostid, hostname))
		goto unlock;

	index = DCget_idxitem01_nearestindex(item->host.hostid, key);
	if (index == config->idxitem01_num)
		goto unlock;

	dc_item = &config->items[config->idxitem01[index]];
	if (dc_item->hostid != item->host.hostid || 0 != strcmp(dc_item->key, key))
		goto unlock;

	DCget_item(item, dc_item);

	res = SUCCEED;
unlock:
	UNLOCK_CACHE;

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: DCconfig_get_item_by_itemid                                      *
 *                                                                            *
 * Purpose: Get item with specified ID                                        *
 *                                                                            *
 * Parameters: item - [OUT] pointer to DC_ITEM structure                      *
 *             itemid - [IN] item ID                                          *
 *                                                                            *
 * Return value: SUCCEED if item found, otherwise FAIL                        *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
int	DCconfig_get_item_by_itemid(DC_ITEM *item, zbx_uint64_t itemid)
{
	int		index, res = FAIL;
	ZBX_DC_ITEM	*dc_item;
	ZBX_DC_HOST	*dc_host;

	LOCK_CACHE;

	index = get_nearestindex(config->items, sizeof(ZBX_DC_ITEM), config->items_num, itemid);
	if (index == config->items_num)
		goto unlock;

	dc_item = &config->items[index];
	if (dc_item->itemid != itemid)
		goto unlock;

	if (NULL == (dc_host = DCget_dc_host(dc_item->hostid)))
		goto unlock;

	DCget_host(&item->host, dc_host);
	DCget_item(item, dc_item);

	res = SUCCEED;
unlock:
	UNLOCK_CACHE;

	return res;
}

/******************************************************************************
 *                                                                            *
 * Function: DCconfig_get_normal_poller_items                                 *
 *                                                                            *
 * Purpose: Get array of items for selected poller                            *
 *                                                                            *
 * Parameters: poller_type - [IN] poller type (ZBX_POLLER_TYPE_...)           *
 *             poller_num - [IN] poller number (0...n)                        *
 *             now - [IN] current time                                        *
 *             items - [OUT] array of items                                   *
 *             max_items - [IN] elements in items array                       *
 *                                                                            *
 * Return value: number of items in items array                               *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments: !!! Don't forget sync code with DCconfig_get_poller_nextcheck !!!*
 *                                                                            *
 ******************************************************************************/
static int	DCconfig_get_normal_poller_items(unsigned char poller_type, unsigned char poller_num, int now,
		DC_ITEM *items, int max_items)
{
	const char	*__function_name = "DCconfig_get_poller_items";
	int		i, index, num = 0;
	ZBX_DC_ITEM	*dc_item;
	ZBX_DC_HOST	*dc_host;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() poller_type:%d poller_num:%d", __function_name,
			(int)poller_type, (int)poller_num);

	LOCK_CACHE;

	index = DCget_idxitem02_nearestindex(poller_type, poller_num, 0);
	for (i = index; i < config->idxitem02_num; i++)
	{
		dc_item = &config->items[config->idxitem02[i]];
		if (dc_item->poller_type != poller_type || dc_item->poller_num != poller_num)
			break;

		if (dc_item->nextcheck > now)
			break;

		if (CONFIG_REFRESH_UNSUPPORTED == 0 && ITEM_STATUS_NOTSUPPORTED == dc_item->status)
			continue;

		if (0 == strcmp(dc_item->key, SERVER_STATUS_KEY) ||
				0 == strcmp(dc_item->key, SERVER_ZABBIXLOG_KEY))
			continue;

		if (NULL == (dc_host = DCget_dc_host(dc_item->hostid)))
			continue;

		if (HOST_MAINTENANCE_STATUS_ON == dc_host->maintenance_status &&
				MAINTENANCE_TYPE_NODATA == dc_host->maintenance_type)
			continue;

		switch (dc_item->type) {
		case ITEM_TYPE_ZABBIX:
			if (0 != dc_host->errors_from)
				continue;
			break;
		case ITEM_TYPE_SNMPv1:
		case ITEM_TYPE_SNMPv2c:
		case ITEM_TYPE_SNMPv3:
			if (0 != dc_host->snmp_errors_from)
				continue;
			break;
		case ITEM_TYPE_IPMI:
			if (0 != dc_host->ipmi_errors_from)
				continue;
			break;
		default:
			/* nothing to do */;
		}

		DCget_host(&items[num].host, dc_host);
		DCget_item(&items[num], dc_item);

		num++;

		if (0 == --max_items)
			break;
	}

	UNLOCK_CACHE;

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%d", __function_name, num);

	return num;
}

/******************************************************************************
 *                                                                            *
 * Function: DCconfig_get_unreachable_poller_items                            *
 *                                                                            *
 * Purpose: Get array of items for selected poller for unreachable devices    *
 *                                                                            *
 * Parameters: poller_type - [IN] poller type (ZBX_POLLER_TYPE_UNREACHABLE)   *
 *             poller_num - [IN] poller number (0...n)                        *
 *             now - [IN] current time                                        *
 *             items - [OUT] array of items                                   *
 *             max_items - [IN] elements in items array                       *
 *                                                                            *
 * Return value: number of items in items array                               *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments: !!! Don't forget sync code with DCconfig_get_poller_items !!!    *
 *                                                                            *
 ******************************************************************************/
static int	DCconfig_get_unreachable_poller_items(unsigned char poller_type, unsigned char poller_num, int now,
		DC_ITEM *items, int max_items)
{
	const char	*__function_name = "DCconfig_get_unreachable_poller_items";
	int		i, j, index, num = 0;
	ZBX_DC_ITEM	*dc_item;
	ZBX_DC_HOST	*dc_host;
	int		item[3];

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() poller_type:%d poller_num:%d", __function_name,
			(int)poller_type, (int)poller_num);

	LOCK_CACHE;

	index = DCget_idxhost02_nearestindex(poller_type, poller_num, 0);
	for (i = index; i < config->idxhost02_num; i++)
	{
		dc_host = &config->hosts[config->idxhost02[i]];
		if (dc_host->poller_type != poller_type || dc_host->poller_num != poller_num)
			break;

		if (dc_host->nextcheck > now)
			break;

		if (HOST_MAINTENANCE_STATUS_ON == dc_host->maintenance_status &&
				MAINTENANCE_TYPE_NODATA == dc_host->maintenance_type)
			continue;

		item[0] = (0 != dc_host->errors_from && dc_host->disable_until <= now);
		item[1] = (0 != dc_host->snmp_errors_from && dc_host->snmp_disable_until <= now);
		item[2] = (0 != dc_host->ipmi_errors_from && dc_host->ipmi_disable_until <= now);

		index = DCget_idxitem01_nearestindex(dc_host->hostid, "");
		for (j = index; j < config->idxitem01_num; j++)
		{
			dc_item = &config->items[config->idxitem01[j]];
			if (dc_item->hostid != dc_host->hostid)
				break;

			if (CONFIG_REFRESH_UNSUPPORTED == 0 &&
					ITEM_STATUS_NOTSUPPORTED == dc_item->status)
				continue;

			if (0 == strcmp(dc_item->key, SERVER_STATUS_KEY) ||
					0 == strcmp(dc_item->key, SERVER_ZABBIXLOG_KEY))
				continue;

			switch (dc_item->type) {
			case ITEM_TYPE_ZABBIX:
				if (0 == item[0])
					break;
				item[0] = 0;
				goto copy_item;
			case ITEM_TYPE_SNMPv1:
			case ITEM_TYPE_SNMPv2c:
			case ITEM_TYPE_SNMPv3:
				if (0 == item[1])
					break;
				item[1] = 0;
				goto copy_item;
			case ITEM_TYPE_IPMI:
				if (0 == item[2])
					break;
				item[2] = 0;
				goto copy_item;
			default:
				/* nothing to do */;
			}
			continue;
copy_item:
			DCget_host(&items[num].host, dc_host);
			DCget_item(&items[num], dc_item);

			num++;

			if (0 == --max_items)
				break;

			if (0 == item[0] && 0 == item[1] && 0 == item[2])
				break;
		}

		if (0 == max_items)
			break;
	}

	UNLOCK_CACHE;

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%d", __function_name, num);

	return num;
}

int	DCconfig_get_poller_items(unsigned char poller_type, unsigned char poller_num, int now,
		DC_ITEM *items, int max_items)
{
	switch (poller_type) {
	case ZBX_POLLER_TYPE_NORMAL:
	case ZBX_POLLER_TYPE_PINGER:
	case ZBX_POLLER_TYPE_IPMI:
		return DCconfig_get_normal_poller_items(poller_type, poller_num, now, items, max_items);
	case ZBX_POLLER_TYPE_UNREACHABLE:
		return DCconfig_get_unreachable_poller_items(poller_type, poller_num, now, items, max_items);
	default:
		return 0;
	}
}

/******************************************************************************
 *                                                                            *
 * Function: DCconfig_get_normal_poller_nextcheck                             *
 *                                                                            *
 * Purpose: Get nextcheck for selected poller                                 *
 *                                                                            *
 * Parameters: poller_type - [IN] poller type (ZBX_POLLER_TYPE_...)           *
 *             poller_num - [IN] poller number (0...n)                        *
 *             now - [IN] current time                                        *
 *                                                                            *
 * Return value: nextcheck or FAIL if no items for selected poller            *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments: !!! Don't forget sync code with DCconfig_get_poller_items !!!    *
 *                                                                            *
 ******************************************************************************/
int	DCconfig_get_normal_poller_nextcheck(unsigned char poller_type, unsigned char poller_num, int now)
{
	const char	*__function_name = "DCconfig_get_normal_poller_nextcheck";
	int		i, index, nextcheck = FAIL;
	ZBX_DC_ITEM	*dc_item;
	ZBX_DC_HOST	*dc_host;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() poller_type:%d poller_num:%d", __function_name,
			(int)poller_type, (int)poller_num);

	LOCK_CACHE;

	index = DCget_idxitem02_nearestindex(poller_type, poller_num, 0);
	for (i = index; i < config->idxitem02_num; i++)
	{
		dc_item = &config->items[config->idxitem02[i]];
		if (dc_item->poller_type != poller_type || dc_item->poller_num != poller_num)
			break;

		if (CONFIG_REFRESH_UNSUPPORTED == 0 && ITEM_STATUS_NOTSUPPORTED == dc_item->status)
			continue;

		if (0 == strcmp(dc_item->key, SERVER_STATUS_KEY) ||
				0 == strcmp(dc_item->key, SERVER_ZABBIXLOG_KEY))
			continue;

		if (NULL == (dc_host = DCget_dc_host(dc_item->hostid)))
			continue;

		if (HOST_MAINTENANCE_STATUS_ON == dc_host->maintenance_status &&
				MAINTENANCE_TYPE_NODATA == dc_host->maintenance_type)
			continue;

		switch (dc_item->type) {
		case ITEM_TYPE_ZABBIX:
			if (0 != dc_host->errors_from)
				continue;
			break;
		case ITEM_TYPE_SNMPv1:
		case ITEM_TYPE_SNMPv2c:
		case ITEM_TYPE_SNMPv3:
			if (0 != dc_host->snmp_errors_from)
				continue;
			break;
		case ITEM_TYPE_IPMI:
			if (0 != dc_host->ipmi_errors_from)
				continue;
			break;
		default:
			/* nothing to do */;
		}

		nextcheck = dc_item->nextcheck;
		break;
	}

	UNLOCK_CACHE;

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%d", __function_name, nextcheck);

	return nextcheck;
}

int	DCconfig_get_poller_nextcheck(unsigned char poller_type, unsigned char poller_num, int now)
{
	switch (poller_type) {
	case ZBX_POLLER_TYPE_NORMAL:
	case ZBX_POLLER_TYPE_PINGER:
	case ZBX_POLLER_TYPE_IPMI:
		return DCconfig_get_normal_poller_nextcheck(poller_type, poller_num, now);
	case ZBX_POLLER_TYPE_UNREACHABLE:
		return now + POLLER_DELAY;
	default:
		return FAIL;
	}
}

/******************************************************************************
 *                                                                            *
 * Function: DCconfig_get_items                                               *
 *                                                                            *
 * Purpose: Get array of items with specified key                             *
 *                                                                            *
 * Parameters: hostid - [IN] host ID (0 - keys from all hosts)                *
 *             key - [IN] key name                                            *
 *             items - [OUT] pointer to array of DC_ITEM structures           *
 *                                                                            *
 * Return value: number of items                                              *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
int	DCconfig_get_items(zbx_uint64_t hostid, const char *key, DC_ITEM **items)
{
	const char	*__function_name = "DCconfig_get_items";
	int		i, j, index = 0, num = 0, alloc = 4;
	ZBX_DC_ITEM	*dc_item;
	ZBX_DC_HOST	*dc_host;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() hostid:" ZBX_FS_UI64 " key:'%s'",
			__function_name, hostid, key);

	*items = zbx_malloc(*items, alloc * sizeof(DC_ITEM));

	LOCK_CACHE;

	if (0 != hostid)
		index = get_nearestindex(config->hosts, sizeof(ZBX_DC_HOST), config->hosts_num, hostid);
	for (i = index; i < config->hosts_num; i++)
	{
		dc_host = &config->hosts[i];
		if (0 != hostid && dc_host->hostid != hostid)
			break;

		if (0 != dc_host->proxy_hostid)
			continue;

		if (HOST_MAINTENANCE_STATUS_OFF != dc_host->maintenance_status ||
				MAINTENANCE_TYPE_NORMAL != dc_host->maintenance_type)
			continue;

		index = DCget_idxitem01_nearestindex(dc_host->hostid, key);
		for (j = index; j < config->idxitem01_num; j++)
		{
			dc_item = &config->items[config->idxitem01[j]];
			if (dc_item->hostid != dc_host->hostid)
				break;

			if (0 != strcmp(dc_item->key, key))
				break;

			if (CONFIG_REFRESH_UNSUPPORTED == 0 &&
					ITEM_STATUS_NOTSUPPORTED == dc_item->status)
				continue;

			if (num == alloc)
			{
				alloc += 4;
				*items = zbx_realloc(*items, alloc * sizeof(DC_ITEM));
			}

			DCget_host(&(*items)[num].host, dc_host);
			DCget_item(&(*items)[num], dc_item);
			num++;
		}
	}

	UNLOCK_CACHE;

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%d", __function_name, num);

	return num;
}

void	DCconfig_update_item(zbx_uint64_t itemid, unsigned char status, int now)
{
	int		index, dc_nextcheck;
	ZBX_DC_ITEM	*dc_item;
	ZBX_DC_FLEXITEM	*dc_flexitem;

	LOCK_CACHE;

	index = get_nearestindex(config->items, sizeof(ZBX_DC_ITEM), config->items_num, itemid);
	if (index == config->items_num)
		goto unlock;

	dc_item = &config->items[index];
	if (dc_item->itemid != itemid)
		goto unlock;

	if (ITEM_STATUS_NOTSUPPORTED == status)
		dc_nextcheck = calculate_item_nextcheck(dc_item->itemid, dc_item->type,
				CONFIG_REFRESH_UNSUPPORTED, NULL, now);
	else
	{
		dc_flexitem = DCget_dc_flexitem(itemid);;
		dc_nextcheck = calculate_item_nextcheck(dc_item->itemid, dc_item->type,
				dc_item->delay, dc_flexitem ? dc_flexitem->delay_flex : NULL, now);
	}

	DCupdate_idxitem02(index, &dc_item->poller_type, &dc_item->poller_num, &dc_item->nextcheck,
			&dc_item->poller_type, &dc_item->poller_num, &dc_nextcheck);

	dc_item->nextcheck = dc_nextcheck;
	dc_item->status = status;
unlock:
	UNLOCK_CACHE;
}

int	DCconfig_activate_host(DC_ITEM *item)
{
	ZBX_DC_HOST	*dc_host;
	int		index, res = FAIL;
	int		dc_errors_from, dc_snmp_errors_from, dc_ipmi_errors_from, *errors_from;
	int		dc_disable_until, dc_snmp_disable_until, dc_ipmi_disable_until, *disable_until;
	unsigned char	dc_available, dc_snmp_available, dc_ipmi_available, *available;
	unsigned char	dc_poller_type, dc_poller_num;
	int		dc_nextcheck;

	LOCK_CACHE;

	index = get_nearestindex(config->hosts, sizeof(ZBX_DC_HOST), config->hosts_num, item->host.hostid);
	if (index == config->hosts_num)
		goto unlock;

	dc_host = &config->hosts[index];
	if (dc_host->hostid != item->host.hostid)
		goto unlock;

	dc_errors_from = dc_host->errors_from;
	dc_available = dc_host->available;
	dc_disable_until = dc_host->disable_until;
	dc_snmp_errors_from = dc_host->snmp_errors_from;
	dc_snmp_available = dc_host->snmp_available;
	dc_snmp_disable_until = dc_host->snmp_disable_until;
	dc_ipmi_errors_from = dc_host->ipmi_errors_from;
	dc_ipmi_available = dc_host->ipmi_available;
	dc_ipmi_disable_until = dc_host->ipmi_disable_until;

	switch (item->type) {
	case ITEM_TYPE_ZABBIX:
		item->host.errors_from = dc_host->errors_from;
		item->host.available = dc_host->available;
		item->host.disable_until = dc_host->disable_until;

		errors_from = &dc_errors_from;
		available = &dc_available;
		disable_until = &dc_disable_until;
		break;
	case ITEM_TYPE_SNMPv1:
	case ITEM_TYPE_SNMPv2c:
	case ITEM_TYPE_SNMPv3:
		item->host.snmp_errors_from = dc_host->snmp_errors_from;
		item->host.snmp_available = dc_host->snmp_available;
		item->host.snmp_disable_until = dc_host->snmp_disable_until;

		errors_from = &dc_snmp_errors_from;
		available = &dc_snmp_available;
		disable_until = &dc_snmp_disable_until;
		break;
	case ITEM_TYPE_IPMI:
		item->host.ipmi_errors_from = dc_host->ipmi_errors_from;
		item->host.ipmi_available = dc_host->ipmi_available;
		item->host.ipmi_disable_until = dc_host->ipmi_disable_until;

		errors_from = &dc_ipmi_errors_from;
		available = &dc_ipmi_available;
		disable_until = &dc_ipmi_disable_until;
		break;
	default:
		goto unlock;
	}

	if (0 == *errors_from && HOST_AVAILABLE_TRUE == *available)
		goto unlock;

	*errors_from = 0;
	*available = HOST_AVAILABLE_TRUE;
	*disable_until = 0;

	poller_by_host(dc_host->hostid, dc_host->proxy_hostid, dc_errors_from || dc_snmp_errors_from ||
			dc_ipmi_errors_from, &dc_poller_type, &dc_poller_num);
	dc_nextcheck = DCget_unreachable_nextcheck(dc_disable_until, dc_snmp_disable_until,
			dc_ipmi_disable_until);

	DCupdate_idxhost02(index, &dc_host->poller_type, &dc_host->poller_num, &dc_host->nextcheck,
			&dc_poller_type, &dc_poller_num, &dc_nextcheck);

	switch (item->type) {
	case ITEM_TYPE_ZABBIX:
		dc_host->errors_from = dc_errors_from;
		dc_host->available = dc_available;
		dc_host->disable_until = dc_disable_until;
		break;
	case ITEM_TYPE_SNMPv1:
	case ITEM_TYPE_SNMPv2c:
	case ITEM_TYPE_SNMPv3:
		dc_host->snmp_errors_from = dc_snmp_errors_from;
		dc_host->snmp_available = dc_snmp_available;
		dc_host->snmp_disable_until = dc_snmp_disable_until;
		break;
	case ITEM_TYPE_IPMI:
		dc_host->ipmi_errors_from = dc_ipmi_errors_from;
		dc_host->ipmi_available = dc_ipmi_available;
		dc_host->ipmi_disable_until = dc_ipmi_disable_until;
		break;
	default:
		goto unlock;
	}

	dc_host->poller_type = dc_poller_type;
	dc_host->poller_num = dc_poller_num;
	dc_host->nextcheck = dc_nextcheck;

	res = SUCCEED;
unlock:
	UNLOCK_CACHE;

	return res;
}

int	DCconfig_deactivate_host(DC_ITEM *item, int now)
{
	ZBX_DC_HOST	*dc_host;
	int		index, res = FAIL;
	int		dc_errors_from, dc_snmp_errors_from, dc_ipmi_errors_from, *errors_from;
	int		dc_disable_until, dc_snmp_disable_until, dc_ipmi_disable_until, *disable_until;
	unsigned char	dc_available, dc_snmp_available, dc_ipmi_available, *available;
	unsigned char	dc_poller_type, dc_poller_num;
	int		dc_nextcheck;

	LOCK_CACHE;

	index = get_nearestindex(config->hosts, sizeof(ZBX_DC_HOST), config->hosts_num, item->host.hostid);
	if (index == config->hosts_num)
		goto unlock;

	dc_host = &config->hosts[index];
	if (dc_host->hostid != item->host.hostid)
		goto unlock;

	dc_errors_from = dc_host->errors_from;
	dc_available = dc_host->available;
	dc_disable_until = dc_host->disable_until;
	dc_snmp_errors_from = dc_host->snmp_errors_from;
	dc_snmp_available = dc_host->snmp_available;
	dc_snmp_disable_until = dc_host->snmp_disable_until;
	dc_ipmi_errors_from = dc_host->ipmi_errors_from;
	dc_ipmi_available = dc_host->ipmi_available;
	dc_ipmi_disable_until = dc_host->ipmi_disable_until;

	switch (item->type) {
	case ITEM_TYPE_ZABBIX:
		item->host.errors_from = dc_host->errors_from;
		item->host.available = dc_host->available;
		item->host.disable_until = dc_host->disable_until;

		errors_from = &dc_errors_from;
		available = &dc_available;
		disable_until = &dc_disable_until;
		break;
	case ITEM_TYPE_SNMPv1:
	case ITEM_TYPE_SNMPv2c:
	case ITEM_TYPE_SNMPv3:
		item->host.snmp_errors_from = dc_host->snmp_errors_from;
		item->host.snmp_available = dc_host->snmp_available;
		item->host.snmp_disable_until = dc_host->snmp_disable_until;

		errors_from = &dc_snmp_errors_from;
		available = &dc_snmp_available;
		disable_until = &dc_snmp_disable_until;
		break;
	case ITEM_TYPE_IPMI:
		item->host.ipmi_errors_from = dc_host->ipmi_errors_from;
		item->host.ipmi_available = dc_host->ipmi_available;
		item->host.ipmi_disable_until = dc_host->ipmi_disable_until;

		errors_from = &dc_ipmi_errors_from;
		available = &dc_ipmi_available;
		disable_until = &dc_ipmi_disable_until;
		break;
	default:
		goto unlock;
	}

	/* First error */
	if (*errors_from == 0)
	{
		*errors_from = now;
		*disable_until = now + CONFIG_UNREACHABLE_DELAY;
	}
	else
	{
		if (now - *errors_from <= CONFIG_UNREACHABLE_PERIOD)
		{
			/* Still unavailable, but won't change status to UNAVAILABLE yet */
			*disable_until = now + CONFIG_UNREACHABLE_DELAY;
		}
		else
		{
			*disable_until = now + CONFIG_UNAVAILABLE_DELAY;
			*available = HOST_AVAILABLE_FALSE;
		}
	}

	poller_by_host(dc_host->hostid, dc_host->proxy_hostid, dc_errors_from || dc_snmp_errors_from ||
			dc_ipmi_errors_from, &dc_poller_type, &dc_poller_num);
	dc_nextcheck = DCget_unreachable_nextcheck(dc_disable_until, dc_snmp_disable_until,
			dc_ipmi_disable_until);

	DCupdate_idxhost02(index, &dc_host->poller_type, &dc_host->poller_num, &dc_host->nextcheck,
			&dc_poller_type, &dc_poller_num, &dc_nextcheck);

	switch (item->type) {
	case ITEM_TYPE_ZABBIX:
		dc_host->errors_from = dc_errors_from;
		dc_host->available = dc_available;
		dc_host->disable_until = dc_disable_until;
		break;
	case ITEM_TYPE_SNMPv1:
	case ITEM_TYPE_SNMPv2c:
	case ITEM_TYPE_SNMPv3:
		dc_host->snmp_errors_from = dc_snmp_errors_from;
		dc_host->snmp_available = dc_snmp_available;
		dc_host->snmp_disable_until = dc_snmp_disable_until;
		break;
	case ITEM_TYPE_IPMI:
		dc_host->ipmi_errors_from = dc_ipmi_errors_from;
		dc_host->ipmi_available = dc_ipmi_available;
		dc_host->ipmi_disable_until = dc_ipmi_disable_until;
		break;
	default:
		goto unlock;
	}

	dc_host->poller_type = dc_poller_type;
	dc_host->poller_num = dc_poller_num;
	dc_host->nextcheck = dc_nextcheck;

	res = SUCCEED;
unlock:
	UNLOCK_CACHE;

	return res;
}

void	DCreset_item_nextcheck(zbx_uint64_t itemid)
{
	int		index, dc_nextcheck;
	ZBX_DC_ITEM	*dc_item;

	LOCK_CACHE;

	index = get_nearestindex(config->items, sizeof(ZBX_DC_ITEM), config->items_num, itemid);
	if (index == config->items_num)
		goto unlock;

	dc_item = &config->items[index];
	if (dc_item->itemid != itemid)
		goto unlock;

	dc_nextcheck = time(NULL);

	DCupdate_idxitem02(index, &dc_item->poller_type, &dc_item->poller_num, &dc_item->nextcheck,
			&dc_item->poller_type, &dc_item->poller_num, &dc_nextcheck);

	dc_item->nextcheck = dc_nextcheck;
unlock:
	UNLOCK_CACHE;
}

/******************************************************************************
 *                                                                            *
 * Function: DCconfig_set_maintenance                                         *
 *                                                                            *
 * Purpose: Set host maintenance status                                       *
 *                                                                            *
 * Parameters: hostid - [IN] host ID (0 - keys from all hosts)                *
 *             key - [IN] key name                                            *
 *             items - [OUT] pointer to array of DC_ITEM structures           *
 *                                                                            *
 * Return value: number of items                                              *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
void	DCconfig_set_maintenance(zbx_uint64_t hostid, int maintenance_status,
		int maintenance_type, int maintenance_from)
{
	int		index;
	ZBX_DC_HOST	*dc_host;

	LOCK_CACHE;

	index = get_nearestindex(config->hosts, sizeof(ZBX_DC_HOST), config->hosts_num, hostid);
	if (index == config->hosts_num)
		goto unlock;

	dc_host = &config->hosts[index];
	if (dc_host->hostid != hostid)
		goto unlock;

	if (HOST_MAINTENANCE_STATUS_OFF == dc_host->maintenance_status ||
			HOST_MAINTENANCE_STATUS_OFF == maintenance_status)
		dc_host->maintenance_from = maintenance_from;
	dc_host->maintenance_status = maintenance_status;
	dc_host->maintenance_type = maintenance_type;
unlock:
	UNLOCK_CACHE;
}

/******************************************************************************
 *                                                                            *
 * Function: DCconfig_get_stats                                               *
 *                                                                            *
 * Purpose: get statistics of the database cache                              *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Aleksander Vladishev                                               *
 *                                                                            *
 * Comments:                                                                  *
 *                                                                            *
 ******************************************************************************/
void	*DCconfig_get_stats(int request)
{
	static zbx_uint64_t	value_uint;
	static double		value_double;

	const zbx_mem_info_t	*strpool_mem;

	strpool_mem = &zbx_strpool_info()->mem_info;

	switch (request)
	{
	case ZBX_CONFSTATS_BUFFER_TOTAL:
		value_uint = config_size + strpool_mem->orig_size;
		return &value_uint;
	case ZBX_CONFSTATS_BUFFER_USED:
		value_uint = (config_size + strpool_mem->orig_size) -
				(config->free_mem + strpool_mem->free_size);
		return &value_uint;
	case ZBX_CONFSTATS_BUFFER_FREE:
		value_uint = config->free_mem + strpool_mem->free_size;
		return &value_uint;
	case ZBX_CONFSTATS_BUFFER_PFREE:
		value_double = 100.0 * ((double)(config->free_mem + strpool_mem->free_size) /
						(config_size + strpool_mem->orig_size));
		return &value_double;
	default:
		return NULL;
	}
}
