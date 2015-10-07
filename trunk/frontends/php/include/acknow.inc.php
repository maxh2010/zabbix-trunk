<?php
/*
** Zabbix
** Copyright (C) 2001-2015 Zabbix SIA
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


function get_last_event_by_triggerid($triggerId) {
	$dbEvents = DBfetch(DBselect(
		'SELECT e.*'.
		' FROM events e'.
		' WHERE e.objectid='.zbx_dbstr($triggerId).
			' AND e.source='.EVENT_SOURCE_TRIGGERS.
			' AND e.object='.EVENT_OBJECT_TRIGGER.
		' ORDER BY e.objectid DESC,e.object DESC,e.eventid DESC',
		1
	));

	return $dbEvents ? $dbEvents : false;
}

/**
 * Get acknowledgement table.
 *
 * @param array $event
 * @param array $event['acknowledges']
 * @param array $event['acknowledges']['clock']
 * @param array $event['acknowledges']['alias']
 * @param array $event['acknowledges']['message']
 *
 * @return CTableInfo
 */
function makeAckTab($event) {
	$acknowledgeTable = (new CTableInfo())
		->setHeader([_('Time'), _('User'), _('Message')]);

	if (!empty($event['acknowledges']) && is_array($event['acknowledges'])) {
		foreach ($event['acknowledges'] as $acknowledge) {
			$acknowledgeTable->addRow([
				zbx_date2str(DATE_TIME_FORMAT_SECONDS, $acknowledge['clock']),
				getUserFullname($acknowledge),
				zbx_nl2br($acknowledge['message'])
			]);
		}
	}

	return $acknowledgeTable;
}