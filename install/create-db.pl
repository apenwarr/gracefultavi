#!/usr/bin/perl

# $Id: create-db.pl,v 1.3 2002/01/04 15:54:42 smoonen Exp $

if(!(-t))
  { die "You must execute this script from the command line.\n"; }

# This script is used to create the initial database for WikkiTikkiTavi
# to use for page storage.

if($#ARGV < 2)
{
  print "Usage: \n";
  print "  perl ./create-db.pl dbname dbuser dbpassword [table_prefix [dbserver]]\n";
  print "\n";
  print "Examples:\n\n";
  print "  perl ./create-db.pl wiki joe passwd\n";
  print "  perl ./create-db.pl project sally pass wiki_ database.example.com\n";
  print "  perl ./create-db.pl common jim key \"\" database.example.com\n";
  exit;
}

$database = $ARGV[0];                   # Database name.
$user     = $ARGV[1];                   # Database user name.
$pass     = $ARGV[2];                   # Database password.
if($#ARGV > 2)                          # Table prefix.
  { $prefix = $ARGV[3]; }
else
  { $prefix = ""; }
if($#ARGV > 3)                          # Database host.
  { $dbhost = $ARGV[4]; }
else
  { $dbhost = ""; }

use DBI;

$dbh = DBI->connect("DBI:mysql:$database:$dbhost", $user, $pass)
       or die "Connecting: $DBI::errstr\n";

print "Creating database...\n";

$qid = $dbh->prepare("CREATE TABLE " . $prefix . "links ( "
                     . "page varchar(80) binary DEFAULT '' NOT NULL, "
                     . "link varchar(80) binary DEFAULT '' NOT NULL, "
                     . "count int(10) unsigned DEFAULT '0' NOT NULL, "
                     . "PRIMARY KEY (page, link) )");
$qid->execute or die "Error creating table\n";

$qid = $dbh->prepare("CREATE TABLE " . $prefix . "pages ( "
                     . "title varchar(80) binary DEFAULT '' NOT NULL, "
                     . "version int(10) unsigned DEFAULT '1' NOT NULL, "
                     . "time timestamp(14), "
                     . "supercede timestamp(14), "
                     . "mutable set('off', 'on') DEFAULT 'on' NOT NULL, "
                     . "username varchar(80), "
                     . "author varchar(80) DEFAULT '' NOT NULL, "
                     . "comment varchar(80) DEFAULT '' NOT NULL, "
                     . "body text, "
                     . "minoredit tinyint DEFAULT 0 NOT NULL, "
                     . "PRIMARY KEY (title, version) )");
$qid->execute or die "Error creating table\n";

$qid = $dbh->prepare("CREATE TABLE " . $prefix . "rate ( "
                     . "ip char(20) DEFAULT '' NOT NULL, "
                     . "time timestamp(14), "
                     . "viewLimit smallint(5) unsigned, "
                     . "searchLimit smallint(5) unsigned, "
                     . "editLimit smallint(5) unsigned, "
                     . "PRIMARY KEY (ip) )");
$qid->execute or die "Error creating table\n";

$qid = $dbh->prepare("CREATE TABLE " . $prefix . "interwiki ( "
                     . "prefix varchar(80) binary DEFAULT '' NOT NULL, "
                     . "where_defined varchar(80) binary DEFAULT '' NOT NULL, "
                     . "url varchar(255) DEFAULT '' NOT NULL, "
                     . "PRIMARY KEY (prefix ) )");
$qid->execute or die "Error creating table\n";

$qid = $dbh->prepare("CREATE TABLE " . $prefix . "sisterwiki ( "
                     . "prefix varchar(80) binary DEFAULT '' NOT NULL, "
                     . "where_defined varchar(80) binary DEFAULT '' NOT NULL, "
                     . "url varchar(255) DEFAULT '' NOT NULL, "
                     . "PRIMARY KEY (prefix ) )");
$qid->execute or die "Error creating table\n";

$qid = $dbh->prepare("CREATE TABLE " . $prefix . "remote_pages ( "
                     . "page varchar(80) binary DEFAULT '' NOT NULL, "
                     . "site varchar(80) DEFAULT '' NOT NULL, "
                     . "PRIMARY KEY (page, site) )");
$qid->execute or die "Error creating table\n";

$qid = $dbh->prepare("CREATE TABLE " . $prefix . "parents ( "
                     . "page varchar(80) binary not null, "
                     . "parent varchar(80) binary not null, "
                     . "primary key (page, parent) )");
$qid->execute or die "Error creating table\n";

$qid = $dbh->prepare("CREATE TABLE " . $prefix . "metaphone ( "
                     . "page varchar(80) binary not null, "
                     . "metaphone varchar(80) binary default '' not null, "
                     . "primary key (page) )");
$qid->execute or die "Error creating table\n";

$qid = $dbh->prepare("CREATE TABLE " . $prefix . "pageswatch ( "
                     . "page varchar(80) binary not null, "
                     . "username varchar(80) not null, "
                     . "time timestamp(14), "
                     . "primary key (page, username) )");
$qid->execute or die "Error creating table\n";

print "Your tables were created.  Next you should run configure.pl\n";
print "to configure your preferences.\n";
