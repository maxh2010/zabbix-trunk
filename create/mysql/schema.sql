#
# Table structure for table 'config'
#

CREATE TABLE config (
  smtp_server varchar(255) DEFAULT '' NOT NULL,
  smtp_helo varchar(255) DEFAULT '' NOT NULL,
  smtp_email varchar(255) DEFAULT '' NOT NULL
);

insert into config (smtp_server,smtp_helo,smtp_email) values ("localhost","localhost","zabbix@localhost");

#
# Table structure for table 'alerts'
#

CREATE TABLE alerts (
  alertid int(4) NOT NULL auto_increment,
  clock int(4) DEFAULT '0' NOT NULL,
  type varchar(10) DEFAULT '' NOT NULL,
  sendto varchar(100) DEFAULT '' NOT NULL,
  subject varchar(255) DEFAULT '' NOT NULL,
  message varchar(255) DEFAULT '' NOT NULL,
  PRIMARY KEY (alertid),
  KEY clock (clock)
);

#
# Table structure for table 'actions'
#

CREATE TABLE actions (
  actionid int(4) NOT NULL auto_increment,
  triggerid int(4) DEFAULT '0' NOT NULL,
  userid int(4) DEFAULT '0' NOT NULL,
  good int(4) DEFAULT '0' NOT NULL,
  delay int(4) DEFAULT '0' NOT NULL,
  subject varchar(255) DEFAULT '' NOT NULL,
  message varchar(255) DEFAULT '' NOT NULL,
  nextcheck int(4) DEFAULT '0' NOT NULL,
  PRIMARY KEY (actionid)
);

#
# Table structure for table 'alarms'
#

CREATE TABLE alarms (
  alarmid int(4) NOT NULL auto_increment,
  triggerid int(4) DEFAULT '0' NOT NULL,
  clock int(4) DEFAULT '0' NOT NULL,
  istrue int(4) DEFAULT '0' NOT NULL,
  PRIMARY KEY (alarmid)
);

#
# Table structure for table 'functions'
#

CREATE TABLE functions (
  functionid int(4) NOT NULL auto_increment,
  itemid int(4) DEFAULT '0' NOT NULL,
  triggerid int(4) DEFAULT '0' NOT NULL,
  lastvalue double(16,4) DEFAULT '0.0000' NOT NULL,
  function varchar(10) DEFAULT '' NOT NULL,
  parameter int(4) DEFAULT '0' NOT NULL,
  PRIMARY KEY (functionid),
  KEY itemid (itemid),
  KEY triggerid (itemid),
  KEY itemidfunctionparameter (itemid,function,parameter)
);

#
# Table structure for table 'history'
#

CREATE TABLE history (
  itemid int(4) DEFAULT '0' NOT NULL,
  clock int(4) DEFAULT '0' NOT NULL,
  value double(16,4) DEFAULT '0.0000' NOT NULL,
  PRIMARY KEY (itemid,clock)
);

#
# Table structure for table 'hosts'
#

CREATE TABLE hosts (
  hostid int(4) NOT NULL auto_increment,
  platformid int(4) NOT NULL,
  host varchar(64) DEFAULT '' NOT NULL,
  port int(4) DEFAULT '0' NOT NULL,
  status int(4) DEFAULT '0' NOT NULL,
  PRIMARY KEY (hostid),
  KEY (platformid),
  KEY (status)
);

#
# Table structure for table 'platforms'
#

CREATE TABLE platforms (
  platformid int(4) NOT NULL,
  platform varchar(32) DEFAULT '' NOT NULL,
  PRIMARY KEY (platformid)
);

insert into platforms (platformid,platform)	values (1,"Linux (Intel) v2.2");
insert into platforms (platformid,platform)	values (2,"HP-UX 10.xx/11.xx");
insert into platforms (platformid,platform)	values (3,"AIX 4.xx");
insert into platforms (platformid,platform)	values (4,"MS Windows 98");
insert into platforms (platformid,platform)	values (5,"MS Windows 2000");

#
# Table structure for table 'items_template'
#

CREATE TABLE items_template (
  itemtemplateid int(4) NOT NULL,
  platformid int(4) NOT NULL,
  description varchar(255) DEFAULT '' NOT NULL,
  key_ varchar(64) DEFAULT '' NOT NULL,
  delay int(4) DEFAULT '0' NOT NULL,
  PRIMARY KEY (itemtemplateid),
  UNIQUE (platformid, key_),
  KEY (platformid)
);

insert into items_template (itemtemplateid,platformid,description,key_,delay)
	values (1,1,"Free memory","freemem", 30);
insert into items_template (itemtemplateid,platformid,description,key_,delay)
	values (2,1,"Free disk space on /","root_free", 30);
insert into items_template (itemtemplateid,platformid,description,key_,delay)
	values (3,1,"Free disk space on /tmp","tmp_free", 30);
insert into items_template (itemtemplateid,platformid,description,key_,delay)
	values (4,1,"Free disk space on /usr","usr_free", 30);
insert into items_template (itemtemplateid,platformid,description,key_,delay)
	values (5,1,"Free number of inodes on /","root_inode", 30);
insert into items_template (itemtemplateid,platformid,description,key_,delay)
	values (6,1,"Free number of inodes on /opt","opt_inode", 30);
insert into items_template (itemtemplateid,platformid,description,key_,delay)
	values (7,1,"Free number of inodes on /tmp","tmp_inode", 30);
insert into items_template (itemtemplateid,platformid,description,key_,delay)
	values (8,1,"Free number of inodes on /usr","usr_inode", 30);
insert into items_template (itemtemplateid,platformid,description,key_,delay)
	values (9,1,"Number of processes","proccount", 30);
insert into items_template (itemtemplateid,platformid,description,key_,delay)
	values (10,1,"Processor load","procload", 10);
insert into items_template (itemtemplateid,platformid,description,key_,delay)
	values (11,1,"Processor load5","procload5", 30);
insert into items_template (itemtemplateid,platformid,description,key_,delay)
	values (12,1,"Processor load15","procload15", 60);
insert into items_template (itemtemplateid,platformid,description,key_,delay)
	values (13,1,"Number of running processes","procrunning", 30);
insert into items_template (itemtemplateid,platformid,description,key_,delay)
	values (14,1,"Free swap space","swapfree", 30);
insert into items_template (itemtemplateid,platformid,description,key_,delay)
	values (16,1,"Size of /var/log/syslog","syslog_size", 30);
insert into items_template (itemtemplateid,platformid,description,key_,delay)
	values (17,1,"Number of users connected","users", 30);
insert into items_template (itemtemplateid,platformid,description,key_,delay)
	values (18,1,"Number of established TCP connections","tcp_count", 30);
insert into items_template (itemtemplateid,platformid,description,key_,delay)
	values (19,1,"Md5sum of /etc/inetd.conf","md5sum_inetd", 600);
insert into items_template (itemtemplateid,platformid,description,key_,delay)
	values (20,1,"Md5sum of /vmlinuz","md5sum_kernel", 600);
insert into items_template (itemtemplateid,platformid,description,key_,delay)
	values (21,1,"Md5sum of /etc/passwd","md5sum_passwd", 600);
insert into items_template (itemtemplateid,platformid,description,key_,delay)
	values (22,1,"Ping of server","ping", 30);
insert into items_template (itemtemplateid,platformid,description,key_,delay)
	values (23,1,"Free disk space on /home","home_free", 30);
insert into items_template (itemtemplateid,platformid,description,key_,delay)
	values (24,1,"Free number of inodes on /home","home_inode", 30);

#
# Table structure for table 'triggers_template'
#

CREATE TABLE triggers_template (
  triggertemplateid int(4) NOT NULL,
  itemtemplateid int(4) NOT NULL,
  description varchar(255) DEFAULT '' NOT NULL,
  expression varchar(255) DEFAULT '' NOT NULL,
  PRIMARY KEY (triggertemplateid),
  KEY (itemtemplateid)
);

insert into triggers_template (triggertemplateid,itemtemplateid,description,expression)
	values (1,1,"Lack of free memory","{:.last(0)}<1000000");
insert into triggers_template (triggertemplateid,itemtemplateid,description,expression)
	values (2,2,"Low free disk space on /","{:.last(0)}<1000000000");
insert into triggers_template (triggertemplateid,itemtemplateid,description,expression)
	values (3,3,"Low free disk space on /tmp","{:.last(0)}<1000000000");
insert into triggers_template (triggertemplateid,itemtemplateid,description,expression)
	values (4,4,"Low free disk space on /usr","{:.last(0)}<1000000000");
insert into triggers_template (triggertemplateid,itemtemplateid,description,expression)
	values (5,5,"Low number of free inodes on /","{:.last(0)}<1000000000");
insert into triggers_template (triggertemplateid,itemtemplateid,description,expression)
	values (6,6,"Low number of free inodes on /opt","{:.last(0)}<1000000000");
insert into triggers_template (triggertemplateid,itemtemplateid,description,expression)
	values (7,7,"Low number of free inodes on /tmp","{:.last(0)}<1000000000");
insert into triggers_template (triggertemplateid,itemtemplateid,description,expression)
	values (8,8,"Low number of free inodes on /usr","{:.last(0)}<1000000000");
insert into triggers_template (triggertemplateid,itemtemplateid,description,expression)
	values (9,9,"Too many processes running","{:.last(0)}>500");
insert into triggers_template (triggertemplateid,itemtemplateid,description,expression)
	values (10,10,"Processor load is too high","{:.last(0)}>5");
insert into triggers_template (triggertemplateid,itemtemplateid,description,expression)
	values (13,13,"Too many processes running","{:.last(0)}>10");
insert into triggers_template (triggertemplateid,itemtemplateid,description,expression)
	values (14,14,"Lack of free swap space","{:.last(0)}<100000000");
insert into triggers_template (triggertemplateid,itemtemplateid,description,expression)
	values (17,17,"Too may users connected","{:.last(0)}>50");
insert into triggers_template (triggertemplateid,itemtemplateid,description,expression)
	values (18,18,"Too may established TCP connections","{:.last(0)}>500");
insert into triggers_template (triggertemplateid,itemtemplateid,description,expression)
	values (19,19,"/etc/inetd.conf has been changed","{:.diff(0)}>0");
insert into triggers_template (triggertemplateid,itemtemplateid,description,expression)
	values (20,20,"/vmlinuz has been changed","{:.diff(0)}>0");
insert into triggers_template (triggertemplateid,itemtemplateid,description,expression)
	values (21,21,"/passwd has been changed","{:.diff(0)}>0");
insert into triggers_template (triggertemplateid,itemtemplateid,description,expression)
	values (22,22,"No ping from server","{:.nodata(60)}>0");
insert into triggers_template (triggertemplateid,itemtemplateid,description,expression)
	values (23,23,"Low free disk space on /home","{:.last(0)}<1000000000");
insert into triggers_template (triggertemplateid,itemtemplateid,description,expression)
	values (24,24,"Low number of free inodes on /home","{:.last(0)}<1000000000");

#
# Table structure for table 'items'
#

CREATE TABLE items (
  itemid int(4) NOT NULL auto_increment,
  hostid int(4) NOT NULL,
  description varchar(255) DEFAULT '' NOT NULL,
  key_ varchar(64) DEFAULT '' NOT NULL,
  delay int(4) DEFAULT '0' NOT NULL,
  history int(4) DEFAULT '0' NOT NULL,
  lastdelete int(4) DEFAULT '0' NOT NULL,
  nextcheck int(4) DEFAULT '0' NOT NULL,
  lastvalue double(16,4) DEFAULT NULL,
  lastclock int(4) DEFAULT NULL,
  prevvalue double(16,4) DEFAULT NULL,
  status int(4) DEFAULT '0' NOT NULL,
  PRIMARY KEY (itemid),
  UNIQUE shortname (hostid,key_),
  KEY (hostid)
);

#
# Table structure for table 'media'
#

CREATE TABLE media (
  mediaid int(4) NOT NULL auto_increment,
  userid int(4) DEFAULT '0' NOT NULL,
  type varchar(10) DEFAULT '' NOT NULL,
  sendto varchar(100) DEFAULT '' NOT NULL,
  active int(4) DEFAULT '0' NOT NULL,
  PRIMARY KEY (mediaid)
);

#
# Table structure for table 'triggers'
#

CREATE TABLE triggers (
  triggerid int(4) NOT NULL auto_increment,
  expression varchar(255) DEFAULT '' NOT NULL,
  description varchar(255) DEFAULT '' NOT NULL,
  istrue int(4) DEFAULT '0' NOT NULL,
  lastcheck int(4) DEFAULT '0' NOT NULL,
  priority int(2) DEFAULT '0' NOT NULL,
  lastchange int(4) DEFAULT '0' NOT NULL,
  comments blob,
  PRIMARY KEY (triggerid)
);

#
# Table structure for table 'users'
#

CREATE TABLE users (
  userid int(4) NOT NULL auto_increment,
  alias varchar(100) DEFAULT '' NOT NULL,
  name varchar(100) DEFAULT '' NOT NULL,
  surname varchar(100) DEFAULT '' NOT NULL,
  PRIMARY KEY (userid),
  UNIQUE (alias)
);

