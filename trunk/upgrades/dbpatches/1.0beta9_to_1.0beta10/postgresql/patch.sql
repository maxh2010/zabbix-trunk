alter table items add  units           varchar(10)     DEFAULT '' NOT NULL;
alter table items add  multiplier      int4            DEFAULT '' NOT NULL;
alter table sysmaps_links add triggerid int4;

update items set units='bps' where key_ like "netload%";
update items set units='bytes' where key_ like "memory[%]";
update items set units='bytes' where key_ like "disk%[%]";
update items set units='bytes' where key_ like "swap[%]";
update items set units=' ' where key_ like "inode%[%]";

update items set multiplier=1 where key_ like "disk%[%]";


CREATE TABLE stats (
  itemid                int4            DEFAULT '0' NOT NULL,
  year                  int4            DEFAULT '0' NOT NULL,
  month                 int4            DEFAULT '0' NOT NULL,
  day                   int4            DEFAULT '0' NOT NULL,
  hour                  int4            DEFAULT '0' NOT NULL,
  value_max		float8		DEFAULT '0.0000' NOT NULL,
  value_min		float8		DEFAULT '0.0000' NOT NULL,
  value_avg		float8		DEFAULT '0.0000' NOT NULL,
  PRIMARY KEY (itemid,year,month,day,hour)
);

--
-- Table structure for table 'screens_graphs'
--

CREATE TABLE screens_graphs (
  screengraphid		serial,
  screenid		int4		DEFAULT '0' NOT NULL,
  itemid		int4		DEFAULT '0' NOT NULL,
  width			int4		DEFAULT '320' NOT NULL,
  height		int4		DEFAULT '200' NOT NULL,
  x			int4		DEFAULT '0' NOT NULL,
  y			int4		DEFAULT '0' NOT NULL,
  PRIMARY KEY  (screengraphid)
);

