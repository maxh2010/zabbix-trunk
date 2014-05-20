/*
** Zabbix
** Copyright (C) 2001-2014 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

#include "common.h"
#include "db.h"
#include "log.h"
#include "sysinfo.h"
#include "zbxdbupgrade.h"
#include "dbupgrade.h"

/*
 * 2.4 development database patches
 */

#ifndef HAVE_SQLITE3

extern unsigned char daemon_type;

static int	DBpatch_2030000(void)
{
	return SUCCEED;
}

static int	DBpatch_2030001(void)
{
	const ZBX_FIELD	field = {"every", "1", NULL, NULL, 0, ZBX_TYPE_INT, ZBX_NOTNULL, 0};

	return DBset_default("timeperiods", &field);
}

static int	DBpatch_2030002(void)
{
	const ZBX_TABLE table =
			{"trigger_discovery_tmp", "", 0,
				{
					{"triggerid", NULL, NULL, NULL, 0, ZBX_TYPE_ID, ZBX_NOTNULL, 0},
					{"parent_triggerid", NULL, NULL, NULL, 0, ZBX_TYPE_ID, ZBX_NOTNULL, 0},
					{0}
				},
				NULL
			};

	return DBcreate_table(&table);
}

static int	DBpatch_2030003(void)
{
	if (ZBX_DB_OK <= DBexecute(
			"insert into trigger_discovery_tmp (select triggerid,parent_triggerid from trigger_discovery)"))
	{
		return SUCCEED;
	}

	return FAIL;
}

static int	DBpatch_2030004(void)
{
	return DBdrop_table("trigger_discovery");
}

static int	DBpatch_2030005(void)
{
	const ZBX_TABLE table =
			{"trigger_discovery", "triggerid", 0,
				{
					{"triggerid", NULL, NULL, NULL, 0, ZBX_TYPE_ID, ZBX_NOTNULL, 0},
					{"parent_triggerid", NULL, NULL, NULL, 0, ZBX_TYPE_ID, ZBX_NOTNULL, 0},
					{0}
				},
				NULL
			};

	return DBcreate_table(&table);
}

static int	DBpatch_2030006(void)
{
	return DBcreate_index("trigger_discovery", "trigger_discovery_1", "parent_triggerid", 0);
}

static int	DBpatch_2030007(void)
{
	const ZBX_FIELD	field = {"triggerid", NULL, "triggers", "triggerid", 0, 0, 0, ZBX_FK_CASCADE_DELETE};

	return DBadd_foreign_key("trigger_discovery", 1, &field);
}

static int	DBpatch_2030008(void)
{
	const ZBX_FIELD	field = {"parent_triggerid", NULL, "triggers", "triggerid", 0, 0, 0, 0};

	return DBadd_foreign_key("trigger_discovery", 2, &field);
}

static int	DBpatch_2030009(void)
{
	if (ZBX_DB_OK <= DBexecute(
			"insert into trigger_discovery (select triggerid,parent_triggerid from trigger_discovery_tmp)"))
	{
		return SUCCEED;
	}

	return FAIL;
}

static int	DBpatch_2030010(void)
{
	return DBdrop_table("trigger_discovery_tmp");
}

static int	DBpatch_2030011(void)
{
	const ZBX_FIELD	field = {"application", "", NULL, NULL, 255, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0};

	return DBadd_field("sysmaps_elements", &field);
}

static int	DBpatch_2030012(void)
{
	const ZBX_TABLE table =
			{"graph_discovery_tmp", "", 0,
				{
					{"graphid", NULL, NULL, NULL, 0, ZBX_TYPE_ID, ZBX_NOTNULL, 0},
					{"parent_graphid", NULL, NULL, NULL, 0, ZBX_TYPE_ID, ZBX_NOTNULL, 0},
					{0}
				},
				NULL
			};

	return DBcreate_table(&table);
}

static int	DBpatch_2030013(void)
{
	if (ZBX_DB_OK <= DBexecute(
			"insert into graph_discovery_tmp (select graphid,parent_graphid from graph_discovery)"))
	{
		return SUCCEED;
	}

	return FAIL;
}

static int	DBpatch_2030014(void)
{
	return DBdrop_table("graph_discovery");
}

static int	DBpatch_2030015(void)
{
	const ZBX_TABLE table =
			{"graph_discovery", "graphid", 0,
				{
					{"graphid", NULL, NULL, NULL, 0, ZBX_TYPE_ID, ZBX_NOTNULL, 0},
					{"parent_graphid", NULL, NULL, NULL, 0, ZBX_TYPE_ID, ZBX_NOTNULL, 0},
					{0}
				},
				NULL
			};

	return DBcreate_table(&table);
}

static int	DBpatch_2030016(void)
{
	return DBcreate_index("graph_discovery", "graph_discovery_1", "parent_graphid", 0);
}

static int	DBpatch_2030017(void)
{
	const ZBX_FIELD	field = {"graphid", NULL, "graphs", "graphid", 0, 0, 0, ZBX_FK_CASCADE_DELETE};

	return DBadd_foreign_key("graph_discovery", 1, &field);
}

static int	DBpatch_2030018(void)
{
	const ZBX_FIELD	field = {"parent_graphid", NULL, "graphs", "graphid", 0, 0, 0, 0};

	return DBadd_foreign_key("graph_discovery", 2, &field);
}

static int	DBpatch_2030019(void)
{
	if (ZBX_DB_OK <= DBexecute(
			"insert into graph_discovery (select graphid,parent_graphid from graph_discovery_tmp)"))
	{
		return SUCCEED;
	}

	return FAIL;
}

static int	DBpatch_2030020(void)
{
	return DBdrop_table("graph_discovery_tmp");
}

static int	DBpatch_2030021(void)
{
	const ZBX_TABLE	table =
			{"item_condition", "item_conditionid", 0,
				{
					{"item_conditionid", NULL, NULL, NULL, 0, ZBX_TYPE_ID, ZBX_NOTNULL, 0},
					{"itemid", NULL, NULL, NULL, 0, ZBX_TYPE_ID, ZBX_NOTNULL, 0},
					{"operator", "8", NULL, NULL, 0, ZBX_TYPE_INT, ZBX_NOTNULL, 0},
					{"macro", "", NULL, NULL, 64, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0},
					{"value", "", NULL, NULL, 255, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0},
					{NULL}
				}
			};

	return DBcreate_table(&table);
}

static int	DBpatch_2030022(void)
{
	return DBcreate_index("item_condition", "item_condition_1", "itemid", 0);
}

static int	DBpatch_2030023(void)
{
	const ZBX_FIELD	field = {"itemid", NULL, "items", "itemid", 0, 0, 0, ZBX_FK_CASCADE_DELETE};

	return DBadd_foreign_key("item_condition", 1, &field);
}

static int	DBpatch_2030024(void)
{
	DB_RESULT	result;
	DB_ROW		row;
	char		*value, *macro_esc, *value_esc;
	int		ret = FAIL, rc;

	/* 1 - ZBX_FLAG_DISCOVERY_RULE*/
	if (NULL == (result = DBselect("select itemid,filter from items where filter<>'' and flags=1")))
		return FAIL;

	while (NULL != (row = DBfetch(result)))
	{
		if (NULL == (value = strchr(row[1], ':')) || 0 == strcmp(row[1], ":"))
			continue;

		*value++ = '\0';

		macro_esc = DBdyn_escape_string(row[1]);
		value_esc = DBdyn_escape_string(value);

		rc = DBexecute("insert into item_condition"
				" (item_conditionid,itemid,macro,value)"
				" values (%s,%s,'%s','%s')",
				row[0], row[0],  macro_esc, value_esc);

		zbx_free(value_esc);
		zbx_free(macro_esc);

		if (ZBX_DB_OK > rc)
			goto out;
	}

	ret = SUCCEED;
out:
	DBfree_result(result);

	return ret;
}

static int	DBpatch_2030025(void)
{
	const ZBX_FIELD field = {"evaltype", "0", NULL, NULL, 0, ZBX_TYPE_INT, ZBX_NOTNULL, 0};

	return DBadd_field("items", &field);
}

static int	DBpatch_2030026(void)
{
	return DBdrop_field("items", "filter");
}

static int	DBpatch_2030027(void)
{
	const ZBX_FIELD	field = {"formula", "", NULL, NULL, 255, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0};

	return DBset_default("items", &field);
}

static int	DBpatch_2030028(void)
{
	if (ZBX_DB_OK > DBexecute("update items set formula='' where flags=%d", ZBX_FLAG_DISCOVERY_RULE))
		return FAIL;

	return SUCCEED;
}

static int	DBpatch_2030029(void)
{
	/* 7 - SCREEN_SORT_TRIGGERS_STATUS_ASC */
	/* 9 - SCREEN_SORT_TRIGGERS_RETRIES_LEFT_ASC (no more supported) */
	if (ZBX_DB_OK > DBexecute("update screens_items set sort_triggers=7 where sort_triggers=9"))
		return FAIL;

	return SUCCEED;
}

static int	DBpatch_2030030(void)
{
	/* 8 - SCREEN_SORT_TRIGGERS_STATUS_DESC */
	/* 10 - SCREEN_SORT_TRIGGERS_RETRIES_LEFT_DESC (no more supported) */
	if (ZBX_DB_OK > DBexecute("update screens_items set sort_triggers=8 where sort_triggers=10"))
		return FAIL;

	return SUCCEED;
}

static int	DBpatch_2030031(void)
{
	/* 16 - CONDITION_TYPE_MAINTENANCE */
	if (ZBX_DB_OK > DBexecute("update conditions set value='' where conditiontype=16"))
		return FAIL;

	return SUCCEED;
}

static int	DBpatch_2030032(void)
{
	const ZBX_FIELD	field = {"description", "", NULL, NULL, 0, ZBX_TYPE_SHORTTEXT, ZBX_NOTNULL, 0};

	return DBadd_field("hosts", &field);
}

static int	DBpatch_2030033(void)
{
	return DBdrop_table("history_sync");
}

static int	DBpatch_2030034(void)
{
	return DBdrop_table("history_uint_sync");
}

static int	DBpatch_2030035(void)
{
	return DBdrop_table("history_str_sync");
}

static int	DBpatch_2030036(void)
{
	return DBdrop_table("node_cksum");
}

static int	DBpatch_2030037(void)
{
	const ZBX_TABLE table =
			{"ids_tmp", "", 0,
				{
					{"table_name", "", NULL, NULL, 64, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0},
					{"field_name", "", NULL, NULL, 64, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0},
					{"nextid", NULL, NULL, NULL, 0, ZBX_TYPE_ID, ZBX_NOTNULL, 0},
					{0}
				},
				NULL
			};

	if (ZBX_DAEMON_TYPE_SERVER == daemon_type)
		return SUCCEED;

	return DBcreate_table(&table);
}

static int	DBpatch_2030038(void)
{
	if (ZBX_DAEMON_TYPE_SERVER == daemon_type)
		return SUCCEED;

	if (ZBX_DB_OK <= DBexecute(
			"insert into ids_tmp ("
				"select table_name,field_name,nextid"
				" from ids"
				" where nodeid=0"
				" and ("
					"(table_name='proxy_history' and field_name='history_lastid')"
					" or (table_name='proxy_dhistory' and field_name='dhistory_lastid')"
					" or (table_name='proxy_autoreg_host' and field_name='autoreg_host_lastid')"
				")"
			")"))
	{
		return SUCCEED;
	}

	return FAIL;
}

static int	DBpatch_2030039(void)
{
	return DBdrop_table("ids");
}

static int	DBpatch_2030040(void)
{
	const ZBX_TABLE table =
			{"ids", "table_name,field_name", 0,
				{
					{"table_name", "", NULL, NULL, 64, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0},
					{"field_name", "", NULL, NULL, 64, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0},
					{"nextid", NULL, NULL, NULL, 0, ZBX_TYPE_ID, ZBX_NOTNULL, 0},
					{0}
				},
				NULL
			};

	return DBcreate_table(&table);
}

static int	DBpatch_2030041(void)
{
	if (ZBX_DAEMON_TYPE_SERVER == daemon_type)
		return SUCCEED;

	if (ZBX_DB_OK <= DBexecute(
			"insert into ids (select table_name,field_name,nextid from ids_tmp)"))
	{
		return SUCCEED;
	}

	return FAIL;
}

static int	DBpatch_2030042(void)
{
	if (ZBX_DAEMON_TYPE_SERVER == daemon_type)
		return SUCCEED;

	return DBdrop_table("ids_tmp");
}

static int	DBpatch_2030043(void)
{
	const char	*sql =
			"delete from profiles"
			" where idx in ("
				"'web.nodes.php.sort','web.nodes.php.sortorder','web.nodes.switch_node',"
				"'web.nodes.selected','web.popup_right.nodeid.last'"
			")";

	if (ZBX_DB_OK <= DBexecute("%s", sql))
		return SUCCEED;

	return FAIL;
}

static int	DBpatch_2030044(void)
{
	/* 21 - AUDIT_RESOURCE_NODE */
	const char	*sql = "delete from auditlog where resourcetype=21";

	if (ZBX_DB_OK <= DBexecute("%s", sql))
		return SUCCEED;

	return FAIL;
}

static int	DBpatch_2030045(void)
{
	/* 17 - CONDITION_TYPE_NODE */
	const char	*sql = "delete from conditions where conditiontype=17";

	if (ZBX_DB_OK <= DBexecute("%s", sql))
		return SUCCEED;

	return FAIL;
}

static int	dm_rename_slave_data(const char *table_name, const char *key_name, const char *field_name,
		int field_length)
{
	DB_RESULT	result;
	DB_ROW		row;
	int		local_nodeid = 0, nodeid, globalmacro;
	zbx_uint64_t	id, min, max;
	char		*name = NULL, *name_esc;
	size_t		name_alloc = 0, name_offset;

	/* 1 - ZBX_NODE_LOCAL */
	if (NULL == (result = DBselect("select nodeid from nodes where nodetype=1")))
		return FAIL;

	if (NULL != (row = DBfetch(result)))
		local_nodeid = atoi(row[0]);
	DBfree_result(result);

	if (0 == local_nodeid)
		return SUCCEED;

	globalmacro = (0 == strcmp(table_name, "globalmacro"));

	min = local_nodeid * __UINT64_C(100000000000000);
	max = min + __UINT64_C(100000000000000) - 1;

	if (NULL == (result = DBselect(
			"select %s,%s"
			" from %s"
			" where not %s between " ZBX_FS_UI64 " and " ZBX_FS_UI64
			" order by %s",
			key_name, field_name, table_name, key_name, min, max, key_name)))
	{
		return FAIL;
	}

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(id, row[0]);
		nodeid = (int)(id / __UINT64_C(100000000000000));

		name_offset = 0;

		if (0 == globalmacro)
			zbx_snprintf_alloc(&name, &name_alloc, &name_offset, "N%d_%s", nodeid, row[1]);
		else
			zbx_snprintf_alloc(&name, &name_alloc, &name_offset, "{$N%d_%s", nodeid, row[1] + 2);

		name_esc = DBdyn_escape_string_len(name, field_length);

		if (ZBX_DB_OK > DBexecute("update %s set %s='%s' where %s=" ZBX_FS_UI64,
				table_name, field_name, name_esc, key_name, id))
		{
			zbx_free(name_esc);
			break;
		}

		zbx_free(name_esc);
	}
	DBfree_result(result);

	zbx_free(name);

	return SUCCEED;
}

static int	check_data_uniqueness(const char *table_name, const char *field_name)
{
	DB_RESULT	result;
	DB_ROW		row;
	int		ret = SUCCEED;

	if (NULL == (result = DBselect("select %s from %s group by %s having count(*)>1",
			field_name, table_name, field_name)))
	{
		return FAIL;
	}

	while (NULL != (row = DBfetch(result)))
	{
		zabbix_log(LOG_LEVEL_CRIT, "Duplicate data \"%s\" for field \"%s\" is found in table \"%s\"."
				" Remove it manually and restart the process.", row[0], field_name, table_name);
		ret = FAIL;
	}
	DBfree_result(result);

	return ret;
}

static int	DBpatch_2030046(void)
{
	return dm_rename_slave_data("actions", "actionid", "name", 255);
}

static int	DBpatch_2030047(void)
{
	return dm_rename_slave_data("drules", "druleid", "name", 255);
}

static int	DBpatch_2030048(void)
{
	return dm_rename_slave_data("globalmacro", "globalmacroid", "macro", 64);
}

static int	DBpatch_2030049(void)
{
	return dm_rename_slave_data("groups", "groupid", "name", 64);
}

static int	DBpatch_2030050(void)
{
	return dm_rename_slave_data("hosts", "hostid", "host", 64);
}

static int	DBpatch_2030051(void)
{
	return dm_rename_slave_data("hosts", "hostid", "name", 64);
}

static int	DBpatch_2030052(void)
{
	return dm_rename_slave_data("icon_map", "iconmapid", "name", 64);
}

static int	DBpatch_2030053(void)
{
	return dm_rename_slave_data("images", "imageid", "name", 64);
}

static int	DBpatch_2030054(void)
{
	return dm_rename_slave_data("maintenances", "maintenanceid", "name", 128);
}

static int	DBpatch_2030055(void)
{
	return dm_rename_slave_data("media_type", "mediatypeid", "description", 100);
}

static int	DBpatch_2030056(void)
{
	return dm_rename_slave_data("regexps", "regexpid", "name", 128);
}

static int	DBpatch_2030057(void)
{
	return dm_rename_slave_data("screens", "screenid", "name", 255);
}

static int	DBpatch_2030058(void)
{
	return dm_rename_slave_data("scripts", "scriptid", "name", 255);
}

static int	DBpatch_2030059(void)
{
	return dm_rename_slave_data("services", "serviceid", "name", 128);
}

static int	DBpatch_2030060(void)
{
	return dm_rename_slave_data("slideshows", "slideshowid", "name", 255);
}

static int	DBpatch_2030061(void)
{
	return dm_rename_slave_data("sysmaps", "sysmapid", "name", 128);
}

static int	DBpatch_2030062(void)
{
	return dm_rename_slave_data("usrgrp", "usrgrpid", "name", 64);
}

static int	DBpatch_2030063(void)
{
	return dm_rename_slave_data("users", "userid", "alias", 100);
}

static int	DBpatch_2030064(void)
{
	return dm_rename_slave_data("valuemaps", "valuemapid", "name", 64);
}

static int	DBpatch_2030065(void)
{
	DB_RESULT	result;
	DB_ROW		row;
	int		local_nodeid = 0;
	zbx_uint64_t	min, max;

	/* 1 - ZBX_NODE_LOCAL */
	if (NULL == (result = DBselect("select nodeid from nodes where nodetype=1")))
		return FAIL;

	if (NULL != (row = DBfetch(result)))
		local_nodeid = atoi(row[0]);
	DBfree_result(result);

	if (0 == local_nodeid)
		return SUCCEED;

	min = local_nodeid * __UINT64_C(100000000000000);
	max = min + __UINT64_C(100000000000000) - 1;

	if (ZBX_DB_OK <= DBexecute(
			"delete from config where not configid between " ZBX_FS_UI64 " and " ZBX_FS_UI64, min, max))
	{
		return SUCCEED;
	}

	return FAIL;
}

static int	DBpatch_2030066(void)
{
	return DBdrop_table("nodes");
}

static int	DBpatch_2030067(void)
{
	if (SUCCEED != check_data_uniqueness("actions", "name"))
		return FAIL;

	return DBcreate_index("actions", "actions_2", "name", 1);
}

static int	DBpatch_2030068(void)
{
	if (SUCCEED != check_data_uniqueness("drules", "name"))
		return FAIL;

	return DBcreate_index("drules", "drules_2", "name", 1);
}

static int	DBpatch_2030069(void)
{
	return DBdrop_index("globalmacro", "globalmacro_1");
}

static int	DBpatch_2030070(void)
{
	if (SUCCEED != check_data_uniqueness("globalmacro", "macro"))
		return FAIL;

	return DBcreate_index("globalmacro", "globalmacro_1", "macro", 1);
}

static int	DBpatch_2030071(void)
{
	return DBdrop_index("graph_theme", "graph_theme_1");
}

static int	DBpatch_2030072(void)
{
	if (SUCCEED != check_data_uniqueness("graph_theme", "description"))
		return FAIL;

	return DBcreate_index("graph_theme", "graph_theme_1", "description", 1);
}

static int	DBpatch_2030073(void)
{
	return DBdrop_index("icon_map", "icon_map_1");
}

static int	DBpatch_2030074(void)
{
	if (SUCCEED != check_data_uniqueness("icon_map", "name"))
		return FAIL;

	return DBcreate_index("icon_map", "icon_map_1", "name", 1);
}

static int	DBpatch_2030075(void)
{
	return DBdrop_index("images", "images_1");
}

static int	DBpatch_2030076(void)
{
	if (SUCCEED != check_data_uniqueness("images", "name"))
		return FAIL;

	return DBcreate_index("images", "images_1", "name", 1);
}

static int	DBpatch_2030077(void)
{
	if (SUCCEED != check_data_uniqueness("maintenances", "name"))
		return FAIL;

	return DBcreate_index("maintenances", "maintenances_2", "name", 1);
}

static int	DBpatch_2030078(void)
{
	if (SUCCEED != check_data_uniqueness("media_type", "description"))
		return FAIL;

	return DBcreate_index("media_type", "media_type_1", "description", 1);
}

static int	DBpatch_2030079(void)
{
	return DBdrop_index("regexps", "regexps_1");
}

static int	DBpatch_2030080(void)
{
	if (SUCCEED != check_data_uniqueness("regexps", "name"))
		return FAIL;

	return DBcreate_index("regexps", "regexps_1", "name", 1);
}

static int	DBpatch_2030081(void)
{
	if (SUCCEED != check_data_uniqueness("scripts", "name"))
		return FAIL;

	return DBcreate_index("scripts", "scripts_3", "name", 1);
}

static int	DBpatch_2030083(void)
{
	if (SUCCEED != check_data_uniqueness("slideshows", "name"))
		return FAIL;

	return DBcreate_index("slideshows", "slideshows_1", "name", 1);
}

static int	DBpatch_2030084(void)
{
	return DBdrop_index("sysmaps", "sysmaps_1");
}

static int	DBpatch_2030085(void)
{
	if (SUCCEED != check_data_uniqueness("sysmaps", "name"))
		return FAIL;

	return DBcreate_index("sysmaps", "sysmaps_1", "name", 1);
}

static int	DBpatch_2030086(void)
{
	return DBdrop_index("usrgrp", "usrgrp_1");
}

static int	DBpatch_2030087(void)
{
	if (SUCCEED != check_data_uniqueness("usrgrp", "name"))
		return FAIL;

	return DBcreate_index("usrgrp", "usrgrp_1", "name", 1);
}

static int	DBpatch_2030088(void)
{
	return DBdrop_index("users", "users_1");
}

static int	DBpatch_2030089(void)
{
	if (SUCCEED != check_data_uniqueness("users", "alias"))
		return FAIL;

	return DBcreate_index("users", "users_1", "alias", 1);
}

static int	DBpatch_2030090(void)
{
	return DBdrop_index("valuemaps", "valuemaps_1");
}

static int	DBpatch_2030091(void)
{
	if (SUCCEED != check_data_uniqueness("valuemaps", "name"))
		return FAIL;

	return DBcreate_index("valuemaps", "valuemaps_1", "name", 1);
}

static int      DBpatch_2030092(void)
{
	const ZBX_FIELD field = {"timeout", "15", NULL, NULL, 0, ZBX_TYPE_INT, ZBX_NOTNULL, 0};

	return DBset_default("httpstep", &field);
}

static int	DBpatch_2030093(void)
{
	const ZBX_FIELD	field = {"error", "", NULL, NULL, 2048, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0};

	return DBmodify_field_type("items", &field);
}
#endif

DBPATCH_START(2030)

/* version, duplicates flag, mandatory flag */

DBPATCH_ADD(2030000, 0, 1)
DBPATCH_ADD(2030001, 0, 1)
DBPATCH_ADD(2030002, 0, 1)
DBPATCH_ADD(2030003, 0, 1)
DBPATCH_ADD(2030004, 0, 1)
DBPATCH_ADD(2030005, 0, 1)
DBPATCH_ADD(2030006, 0, 1)
DBPATCH_ADD(2030007, 0, 1)
DBPATCH_ADD(2030008, 0, 1)
DBPATCH_ADD(2030009, 0, 1)
DBPATCH_ADD(2030010, 0, 1)
DBPATCH_ADD(2030011, 0, 1)
DBPATCH_ADD(2030012, 0, 1)
DBPATCH_ADD(2030013, 0, 1)
DBPATCH_ADD(2030014, 0, 1)
DBPATCH_ADD(2030015, 0, 1)
DBPATCH_ADD(2030016, 0, 1)
DBPATCH_ADD(2030017, 0, 1)
DBPATCH_ADD(2030018, 0, 1)
DBPATCH_ADD(2030019, 0, 1)
DBPATCH_ADD(2030020, 0, 1)
DBPATCH_ADD(2030021, 0, 1)
DBPATCH_ADD(2030022, 0, 1)
DBPATCH_ADD(2030023, 0, 1)
DBPATCH_ADD(2030024, 0, 1)
DBPATCH_ADD(2030025, 0, 1)
DBPATCH_ADD(2030026, 0, 1)
DBPATCH_ADD(2030027, 0, 1)
DBPATCH_ADD(2030028, 0, 1)
DBPATCH_ADD(2030029, 0, 1)
DBPATCH_ADD(2030030, 0, 1)
DBPATCH_ADD(2030031, 0, 0)
DBPATCH_ADD(2030032, 0, 1)
DBPATCH_ADD(2030033, 0, 1)
DBPATCH_ADD(2030034, 0, 1)
DBPATCH_ADD(2030035, 0, 1)
DBPATCH_ADD(2030036, 0, 1)
DBPATCH_ADD(2030037, 0, 1)
DBPATCH_ADD(2030038, 0, 1)
DBPATCH_ADD(2030039, 0, 1)
DBPATCH_ADD(2030040, 0, 1)
DBPATCH_ADD(2030041, 0, 1)
DBPATCH_ADD(2030042, 0, 1)
DBPATCH_ADD(2030043, 0, 1)
DBPATCH_ADD(2030044, 0, 1)
DBPATCH_ADD(2030045, 0, 1)
DBPATCH_ADD(2030046, 0, 1)
DBPATCH_ADD(2030047, 0, 1)
DBPATCH_ADD(2030048, 0, 1)
DBPATCH_ADD(2030049, 0, 1)
DBPATCH_ADD(2030050, 0, 1)
DBPATCH_ADD(2030051, 0, 1)
DBPATCH_ADD(2030052, 0, 1)
DBPATCH_ADD(2030053, 0, 1)
DBPATCH_ADD(2030054, 0, 1)
DBPATCH_ADD(2030055, 0, 1)
DBPATCH_ADD(2030056, 0, 1)
DBPATCH_ADD(2030057, 0, 1)
DBPATCH_ADD(2030058, 0, 1)
DBPATCH_ADD(2030059, 0, 1)
DBPATCH_ADD(2030060, 0, 1)
DBPATCH_ADD(2030061, 0, 1)
DBPATCH_ADD(2030062, 0, 1)
DBPATCH_ADD(2030063, 0, 1)
DBPATCH_ADD(2030064, 0, 1)
DBPATCH_ADD(2030065, 0, 1)
DBPATCH_ADD(2030066, 0, 1)
DBPATCH_ADD(2030067, 0, 1)
DBPATCH_ADD(2030068, 0, 1)
DBPATCH_ADD(2030069, 0, 1)
DBPATCH_ADD(2030070, 0, 1)
DBPATCH_ADD(2030071, 0, 1)
DBPATCH_ADD(2030072, 0, 1)
DBPATCH_ADD(2030073, 0, 1)
DBPATCH_ADD(2030074, 0, 1)
DBPATCH_ADD(2030075, 0, 1)
DBPATCH_ADD(2030076, 0, 1)
DBPATCH_ADD(2030077, 0, 1)
DBPATCH_ADD(2030078, 0, 1)
DBPATCH_ADD(2030079, 0, 1)
DBPATCH_ADD(2030080, 0, 1)
DBPATCH_ADD(2030081, 0, 1)
DBPATCH_ADD(2030083, 0, 1)
DBPATCH_ADD(2030084, 0, 1)
DBPATCH_ADD(2030085, 0, 1)
DBPATCH_ADD(2030086, 0, 1)
DBPATCH_ADD(2030087, 0, 1)
DBPATCH_ADD(2030088, 0, 1)
DBPATCH_ADD(2030089, 0, 1)
DBPATCH_ADD(2030090, 0, 1)
DBPATCH_ADD(2030091, 0, 1)
DBPATCH_ADD(2030092, 0, 1)
DBPATCH_ADD(2030093, 0, 1)

DBPATCH_END()
