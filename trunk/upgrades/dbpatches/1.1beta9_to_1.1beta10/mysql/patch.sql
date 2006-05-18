alter table config add work_period varchar(100) DEFAULT '1-5,00:00-24:00' NOT NULL;
alter table graphs add show_work_period int(1) DEFAULT '1' NOT NULL;
alter table graphs add show_triggers int(1) DEFAULT '1' NOT NULL;

alter table profiles change     value value     varchar(255)	DEFAULT '' NOT NULL;
alter table profiles add        valuetype	int(4)		DEFAULT 0 NOT NULL;

--
-- Table structure for table 'applications'
--

CREATE TABLE applications (
        applicationid           int(4)          NOT NULL auto_increment,
        hostid                  int(4)          DEFAULT '0' NOT NULL,
        name                    varchar(255)    DEFAULT '' NOT NULL,
        templateid              int(4)          DEFAULT '0' NOT NULL,
        PRIMARY KEY     (applicationid),
        KEY             hostid (hostid),
        KEY             templateid (templateid),
        UNIQUE          appname (hostid,name)
) type=InnoDB;

--
-- Table structure for table 'items_applications'
--

CREATE TABLE items_applications (
	applicationid		int(4)		DEFAULT '0' NOT NULL,
	itemid			int(4)		DEFAULT '0' NOT NULL,
	PRIMARY KEY (applicationid,itemid)
) type=InnoDB;


alter table audit rename auditlog;

alter table auditlog add resourcetype          int(4)          DEFAULT '0' NOT NULL;
update auditlog set resourcetype=resource;
alter table auditlog drop resource;

alter table screens add hsize	int(4)	DEFAULT '1' NOT NULL;
alter table screens add vsize	int(4)	DEFAULT '1' NOT NULL;
update screens set hsize=cols;
update screens set vsize=rows;
alter table screens drop cols;
alter table screens drop rows;

alter table screens_items add resourcetype    int(4)          DEFAULT '0' NOT NULL;
update screens_items set resourcetype=resource;
alter table screens_items drop resource;

alter table functions change function function varchar(12) DEFAULT '' NOT NULL;

CREATE TABLE help_items (
        itemtype                int(4)          DEFAULT '0' NOT NULL,
        key_                    varchar(64)     DEFAULT '' NOT NULL,
        description             varchar(255)    DEFAULT '' NOT NULL,
        PRIMARY KEY (itemtype, key_)
) type=InnoDB;
