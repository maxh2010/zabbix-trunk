/*
** Zabbix
** Copyright (C) 2000-2011 Zabbix SIA
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

package com.zabbix.proxy;

class GeneralInformation
{
	public static final String APPLICATION_NAME = "Zabbix Java Proxy";
	public static final String REVISION_DATE = "18 May 2011";
	public static final String REVISION = "{ZABBIX_REVISION}";
	public static final String VERSION = "1.9.4";

	public static void printVersion()
	{
		System.out.printf("%s v%s (revision %s) (%s)\n", APPLICATION_NAME, VERSION, REVISION, REVISION_DATE);
	}
}
