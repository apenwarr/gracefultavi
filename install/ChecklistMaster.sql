DROP TABLE IF EXISTS chklst_checklist;
CREATE TABLE chklst_checklist (
  id    int(10) unsigned NOT NULL auto_increment,
  name  varchar(100) binary NOT NULL default '',
  PRIMARY KEY  (id),
  UNIQUE name_unique (name)
);

DROP TABLE IF EXISTS chklst_checklist_details;
CREATE TABLE chklst_checklist_details (
  id            int(10) unsigned NOT NULL auto_increment,
  checklist     int(10) unsigned default NULL,
  category      varchar(200) binary NOT NULL default '',
  description   varchar(200) binary NOT NULL default '',
  owner         int(10) unsigned default NULL,
  duedate       date default NULL,
  lastconfdate  date default NULL,
  status        tinyint(1) NOT NULL default 0,
  torder        int(10) unsigned NOT NULL default 0,
  noteshidden   tinyint(1) NOT NULL default 0,
  notes         blob,
  PRIMARY KEY  (id)
);


DROP TABLE IF EXISTS chklst_template;
CREATE TABLE chklst_template (
  id    int(10) unsigned NOT NULL auto_increment,
  name  varchar(100) binary NOT NULL default '',
  PRIMARY KEY  (id)
);

DROP TABLE IF EXISTS chklst_template_details;
CREATE TABLE chklst_template_details (
  id            int(10) unsigned NOT NULL auto_increment,
  template      int(10) unsigned default NULL,
  category      varchar(200) binary NOT NULL default '',
  description   varchar(200) binary NOT NULL default '',
  owner         int(10) unsigned default NULL,
  torder        int(10) unsigned NOT NULL default 0,
  PRIMARY KEY  (id)
);


DROP TABLE IF EXISTS chklst_role;
CREATE TABLE chklst_role (
  id    int(10) default NULL,
  name  varchar(100) default NULL
);

INSERT INTO chklst_role VALUES (1,'CTO');
INSERT INTO chklst_role VALUES (2,'Release Pusher');
INSERT INTO chklst_role VALUES (3,'Feature Pusher');
