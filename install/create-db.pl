#!/usr/bin/perl

# $Id: create-db.pl,v 1.3 2002/01/04 15:54:42 smoonen Exp $

if(!(-t))
  { die "You must execute this script from the command line.\n"; }

# This script is used to create the initial database for GracefulTavi
# to use for page storage.

if($#ARGV < 3)
{
  print "Usage: \n";
  print "  perl ./create-db.pl wikiname dbname dbuser dbpassword [table_prefix [dbserver]]\n";
  print "\n";
  print "Examples:\n\n";
  print "  perl ./create-db.pl OpenNit wiki joe passwd\n";
  print "  perl ./create-db.pl PtbcWiki project sally pass " . $prefix . " database.example.com\n";
  print "  perl ./create-db.pl MyWiki common jim key \"\" database.example.com\n";
  exit;
}

$wikiname = $ARGV[0];                   # WikiName
$database = $ARGV[1];                   # Database name.
$user     = $ARGV[2];                   # Database user name.
$pass     = $ARGV[3];                   # Database password.
if($#ARGV > 3)                          # Table prefix.
  { $prefix = $ARGV[4]; }
else
  { $prefix = ""; }
if($#ARGV > 4)                          # Database host.
  { $dbhost = $ARGV[5]; }
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
                     . "id MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT, "
                     . "title VARCHAR(80) BINARY NOT NULL DEFAULT '', "
                     . "title_notbinary VARCHAR(80) NOT NULL DEFAULT '', "
                     . "lastversion MEDIUMINT UNSIGNED NOT NULL DEFAULT '1', "
                     . "lastversion_major MEDIUMINT UNSIGNED NOT NULL DEFAULT '1', "
                     . "metaphone VARCHAR(80) BINARY NOT NULL DEFAULT '', "
                     . "bodylength SMALLINT UNSIGNED NOT NULL default '0', "
                     . "mutable SET('off', 'on') NOT NULL DEFAULT 'on', "
                     . "createtime TIMESTAMP(14) NOT NULL, "
                     . "updatetime TIMESTAMP(14) NOT NULL, "
                     . "PRIMARY KEY (id), "
                     . "UNIQUE title_u (title), "
                     . "INDEX title_idx (title), "
                     . "INDEX title_notbinary_idx (title_notbinary), "
                     . "INDEX lastversion_major_idx (lastversion_major), "
                     . "INDEX bodylength_idx (bodylength) )");
$qid->execute or die "Error creating table\n";

$qid = $dbh->prepare("CREATE TABLE " . $prefix . "content ( "
                     . "page MEDIUMINT UNSIGNED NOT NULL, "
                     . "version MEDIUMINT UNSIGNED NOT NULL DEFAULT '1', "
                     . "time TIMESTAMP(14) NOT NULL, "
                     . "supercede TIMESTAMP(14) NOT NULL, "
                     . "minoredit TINYINT NOT NULL DEFAULT 0, "
                     . "username VARCHAR(80) NOT NULL, "
                     . "author VARCHAR(80) NOT NULL DEFAULT '', "
                     . "comment VARCHAR(80) NOT NULL DEFAULT '', "
                     . "body TEXT NOT NULL, "
                     . "PRIMARY KEY (page, version) )");
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

$qid = $dbh->prepare("CREATE TABLE " . $prefix . "subscribe ( "
                     . "page varchar(80) binary not null, "
                     . "username varchar(80) not null, "
                     . "primary key (page, username) )");
$qid->execute or die "Error creating table\n";

@otb = (
    "INSERT INTO `foo_pages` VALUES (1,'" . $wikiname . "','" . $wikiname . "',1,1,'" . $wikiname . "',271,'on',NULL,NULL);",
    "INSERT INTO `foo_pages` VALUES (2,'GoodWikiKarma','GoodWikiKarma',1,1,'KTWKKRM',1043,'on',NULL,NULL);",
    "INSERT INTO `foo_pages` VALUES (3,'HelpPage','HelpPage',1,1,'HLPJ',894,'on',NULL,NULL);",
    "INSERT INTO `foo_pages` VALUES (4,'HierarchalStructure','HierarchalStructure',1,1,'HRRXLSTRKTR',346,'on',NULL,NULL);",
    "INSERT INTO `foo_pages` VALUES (5,'HowDoIEdit','HowDoIEdit',1,1,'HTTT',945,'on',NULL,NULL);",
    "INSERT INTO `foo_pages` VALUES (6,'HowDoINavigate','HowDoINavigate',1,1,'HTNFKT',353,'on',NULL,NULL);",
    "INSERT INTO `foo_pages` VALUES (7,'JumpSearch','JumpSearch',1,1,'JMPSRX',575,'on',NULL,NULL);",
    "INSERT INTO `foo_pages` VALUES (8,'RecentChanges','RecentChanges',1,1,'RSNTXNJS',8,'off',NULL,NULL);",
    "INSERT INTO `foo_pages` VALUES (9,'RemoteWikiLinks','RemoteWikiLinks',1,1,'RMTWKLNKS',252,'on',NULL,NULL);",
    "INSERT INTO `foo_pages` VALUES (10,'SandBox','SandBox',1,1,'SNTBKS',29,'on',NULL,NULL);",
    "INSERT INTO `foo_pages` VALUES (11,'StructuredTextRules','StructuredTextRules',1,1,'STRKTRTTKSTRLS',3574,'on',NULL,NULL);",
    "INSERT INTO `foo_pages` VALUES (12,'TextFormattingRules','TextFormattingRules',1,1,'TKSTFRMTNKRLS',603,'on',NULL,NULL);",
    "INSERT INTO `foo_pages` VALUES (13,'WantedPages','WantedPages',1,1,'WNTTPJS',73,'off',NULL,NULL);",
    "INSERT INTO `foo_pages` VALUES (14,'WikiName','WikiName',1,1,'WKNM',185,'on',NULL,NULL);",
    "INSERT INTO `foo_pages` VALUES (15,'WikiWikiWeb','WikiWikiWeb',1,1,'WKWKWB',86,'on',NULL,NULL);",

    "INSERT INTO `foo_content` VALUES (1,1,NULL,NULL,'','','','Welcome to [[GetTopLevel]].\\n\\n***Add content here***\\n\\n<hr>\\nAre you new to Wiki? See HelpPage and <a href=\"?action=prefs\">UserOptions</a> to get started.\\n\\nNavigation Hint: use the \"Jump to:\" (JumpSearch) entry box at the top of the page. It\\'s more powerful than you think.\\n',0);",
    "INSERT INTO `foo_content` VALUES (2,1,NULL,NULL,'','','','So, you know how to use the Wiki.  Here\\'s how to use it well.\\n\\n- **DON\\'T** delete stuff from documentation pages.  It\\'s always good to have a record of what was discussed, even if it\\'s obsolete.  \\n\\n - There are a million ways of thinking of this, but the above statement is not totally true.  See WikiWikiWeb:GoodWikiCitizen and especially WikiWikiWeb:ThreadMess for some more discussion.  If there\\'s a discussion of whether to do something and how, you don\\'t have to retain the entire discussion: you can *summarize* it instead, which will make it more useful to people in the long run.\\n\\n- **DO** clean pages up.  If page X gets cluttered (and especially if it\\'s hard to tell what\\'s current and what\\'s old) move obsolete items to ObsoleteX and provide a link.\\n\\n - ObsoleteX is not always necessary either.  If the stuff really is obsolete, deleting it is pretty much fine.  (But again, in a discussion, be careful to retain the train of thought that made the \"wrong\" viewpoint obsolete.  Otherwise the argument will just happen again later.)\\n',0);",
    "INSERT INTO `foo_content` VALUES (3,1,NULL,NULL,'','','','What is this?\\n\\n  It\\'s called a wiki web; it\\'s a kind of collaborative website.\\n\\nThe idea is that anyone who can read pages can also write them, and nobody really \"owns\" a page.  If you notice a spelling mistake, just fix it; if someone forgets to mention something, add it in.  If a page gets too long and complicated, split it into a few smaller pages.\\n\\nYou can change a page by clicking \"Edit this page\", or add a comment to the bottom using the \"Add a comment\" button.\\n\\nEach page has a WikiName - a name which is usually one or more capitalized words run together.   When you write a page, [WikiName]s are automatically converted to hyperlinks, so you don\\'t need to know HTML syntax.  If a WikiName doesn\\'t yet have a page to go with it, it gets a blue \\'?\\' that you can click to create the page.\\n\\nSee also:\\n\\n- HowDoIEdit\\n\\n- HowDoINavigate\\n\\n- GoodWikiKarma\\n\\n- WikiWikiWeb (the original wiki)\\n',0);",
    "INSERT INTO `foo_content` VALUES (4,1,NULL,NULL,'','','','If you create a \"hierarchal\" wiki web you will see that wiki keeps track of parent-child relationships between pages. Context and overview links are displayed at the top of each page.\\n\\nWhen you create a new page, it\\'s parent will be the page on which you clicked the ? link. You can change the parent, or add more, by clicking on the page title.\\n',0);",
    "INSERT INTO `foo_content` VALUES (5,1,NULL,NULL,'','','','How do I edit ?\\n\\n  Click \"Edit this page\" at the bottom of any page (SandBox is a good\\n  place to practice). Edit the text and click the \"Change..\" or \"Create..\"\\n  button. Text will be formatted according to the TextFormattingRules.\\n\\nHow do I link and create new pages ?\\n\\n  To create a link to another wiki page, just write a WikiName.\\n  (If you don\\'t use a WikiName, you should enclose the name in square brackets).\\n\\n  If the page does not yet exist, a ? will be displayed - click it to\\n  create the new page. This is how new wiki pages are added.\\n\\n  To link to a page on another site, just type the url. Or, you can\\n  use HTML. Also, RemoteWikiLinks are a convenient notation for\\n  linking to pages on another site.\\n\\nWhat if I make a mistake ?\\n\\n  If some good text gets lost by mistake, you might find it by backing\\n  up in your browser - copy and paste back it into the\\n  page. Otherwise, the site admin may be able to help undo the changes.\\n',0);",
    "INSERT INTO `foo_content` VALUES (6,1,NULL,NULL,'','','','How do I navigate ?\\n\\n  Click the icon at the top left to return to the [[GetTopLevel]].\\n\\n  Click the page title to list all other pages which link to this one (backlinks). \\n\\n  Use the JumpSearch field which appears on every page for fast navigation and\\n  searching.\\n\\n  RecentChanges lists all pages by modification date.\\n\\n  See also HierarchalStructure\\n',0);",
    "INSERT INTO `foo_content` VALUES (7,1,NULL,NULL,'','','','Using the JumpSearch feature:\\n\\nTo jump directly to a page, enter the first few letters of its name in the JumpSearch field at the top-left of the screen and press enter.\\n\\nIf more than one page matches, the alphabetically first one is chosen.  Capital letters are ignored in the search string.\\n\\nIf no matches are found, *or* if the search expression begins with \\'!\\', a full-text search of all the pages is done instead.  This is case-insensitive and spaces are preserved.\\n\\nTo list all pages alphabetically, leave the expression blank.\\n\\nSee also: RecentChanges, HowDoINavigate\\n',0);",
    "INSERT INTO `foo_content` VALUES (8,1,NULL,NULL,'','','','[[! *]]\\n',0);",
    "INSERT INTO `foo_content` VALUES (9,1,NULL,NULL,'','','','A notation for linking to pages on other wiki servers. Here\\'s an example:\\n\\nZWiki:FrontPage\\n\\nthat is, two WikiName(s) separated by **:** .\\nThe first must be a local page containing a RemoteWikiURL, the second is the name of the page on the remote site.\\n',0);",
    "INSERT INTO `foo_content` VALUES (10,1,NULL,NULL,'','','','This is a sandbox, have fun.\\n',0);",
    "INSERT INTO `foo_content` VALUES (11,1,NULL,NULL,'','','','\"Structured text is text that uses indentation and simple\\nsymbology to indicate the structure of a document.  \\n\\nA structured string consists of a sequence of paragraphs separated by\\none or more blank lines.  Each paragraph has a level which is defined\\nas the minimum indentation of the paragraph.  A paragraph is a\\nsub-paragraph of another paragraph if the other paragraph is the last\\npreceding paragraph that has a lower level.\\n\\nSpecial symbology is used to indicate special constructs:\\n\\n- A single-line paragraph whose immediately succeeding paragraphs are lower\\n  level is treated as a header.\\n\\n- A paragraph that begins with a \\'-\\', \\'*\\', or \\'o\\' is treated as an\\n  unordered list (bullet) element.\\n\\n- A paragraph that begins with a sequence of digits followed by a\\n  white-space character is treated as an ordered list element.\\n\\n- A paragraph that begins with a sequence of sequences, where each\\n  sequence is a sequence of digits or a sequence of letters followed\\n  by a period, is treated as an ordered list element.\\n\\n- A paragraph with a first line that contains some text, followed by\\n  some white-space and \\'--\\' is treated as\\n  a descriptive list element. The leading text is treated as the\\n  element title.\\n\\n- Sub-paragraphs of a paragraph that ends in the word \\'example\\' or the\\n  word \\'examples\\', or \\'::\\' is treated as example code and is output as is::\\n\\n    <table border=0>\\n      <tr>\\n        <td> Foo \\n    </table>\\n\\n- Text enclosed single quotes (with white-space to the left of the\\n  first quote and whitespace or punctuation to the right of the second quote)\\n  is treated as example code.\\n\\n  For example: \\'&lt;dtml-var foo>\\'.\\n\\n- Text surrounded by \\'*\\' characters (with white-space to the left of the\\n  first \\'*\\' and whitespace or puctuation to the right of the second \\'*\\')\\n  is *emphasized*.\\n\\n- Text surrounded by \\'**\\' characters (with white-space to the left of the\\n  first \\'**\\' and whitespace or punctuation to the right of the second \\'**\\')\\n  is made **strong**.\\n\\n- Text surrounded by \\'_\\' underscore characters (with whitespace to the left \\n  and whitespace or punctuation to the right) is made _underlined_.\\n\\n- Text encloded by double quotes followed by a colon, a URL, and concluded\\n  by punctuation plus white space, *or* just white space, is treated as a\\n  hyper link.\\n\\n  For example, \\'&quot;Zope&quot;:http://www.zope.org/\\' is interpreted as \\n  \"Zope\":http://www.zope.org/ \\n\\n  *Note: This works for relative as well as absolute URLs.*\\n\\n- Text enclosed by double quotes followed by a comma, one or more spaces,\\n  an absolute URL and concluded by punctuation plus white space, or just\\n  white space, is treated as a hyper link.\\n\\n  For example: \\'&quot;mail me&quot;, mailto:amos.com\\' is \\n  interpreted as \"mail me\", mailto:amos.com \\n\\n- Text enclosed in brackets which consists only of letters, digits,\\n  underscores and dashes is treated as hyper links within the document.\\n\\n  For example: \\'\"As demonstrated by Smith &#091;12&#093; this technique ...\"\\' \\n\\n  Is interpreted as: \"As demonstrated by Smith [12] this technique\"\\n\\n  Together with the next rule this allows easy coding of references or end notes.\\n\\n- Text enclosed in brackets which is preceded by the start of a line, two\\n  periods and a space is treated as a named link. For example:\\n\\n\\n  \\'.. &#091;12&#093; \"Effective Techniques\" Smith, Joe ...\\'\\n\\n  Is interpreted as \\n\\n.. [12] \"Effective Techniques\" Smith, Joe ...\\n\\n  *Note:  see the &lt;A NAME=\"12\"&gt; in the HTML source.*\\n\\n  Together with the previous rule this allows easy coding of references or\\n  end notes.\"\\n\\nSee also: TextFormattingRules\\n',0);",
    "INSERT INTO `foo_content` VALUES (12,1,NULL,NULL,'','','','Your text is formatted as a web page according to some simple markup rules. These are intended to be convenient and unobtrusive.\\n\\nThe StructuredTextRules are used by default. Briefly -\\n\\n- separate paragraphs with a blank line\\n\\n- a single-line paragraph followed by a more-indented paragraph makes a heading\\n\\n- for bulleted/numbered lists, use * or 0. followed by a space \\n\\n- for emphasis, use * ... * and ** ... ** \\n\\nAlso -\\n\\n- WikiName(s), bare urls and words enclosed in square brackets are \\nconverted to hyperlinks (prepend the link or the line with ! to prevent this)\\n\\n- HTML & DTML tags may be used\\n',0);",
    "INSERT INTO `foo_content` VALUES (13,1,NULL,NULL,'','','','If you are bored, the following pages need information:\\n\\n[[WantedPages]]\\n',0);",
    "INSERT INTO `foo_content` VALUES (14,1,NULL,NULL,'','','','Wiki page names are two or more CapitalizedWords joined together without spaces. \\nThey are easy to read and write while still being distinctive enough to be recognized by the computer.\\n',0);",
    "INSERT INTO `foo_content` VALUES (15,1,NULL,NULL,'','','','The original wiki web, and home to a thriving community.\\n\\nhttp://www.c2.com/cgi/wiki?\\n',0);",

    "INSERT INTO `foo_links` VALUES ('RemoteWikiLinks','RemoteWikiURL',2);",
    "INSERT INTO `foo_links` VALUES ('RemoteWikiLinks','WikiName',1);",
    "INSERT INTO `foo_links` VALUES ('RemoteWikiLinks','FrontPage',2);",
    "INSERT INTO `foo_links` VALUES ('HelpPage','WikiWikiWeb',2);",
    "INSERT INTO `foo_links` VALUES ('GoodWikiKarma','ThreadMess',2);",
    "INSERT INTO `foo_links` VALUES ('GoodWikiKarma','GoodWikiCitizen',2);",
    "INSERT INTO `foo_links` VALUES ('GoodWikiKarma','WikiWikiWeb',2);",
    "INSERT INTO `foo_links` VALUES ('GoodWikiKarma','ObsoleteX',4);",
    "INSERT INTO `foo_links` VALUES ('HelpPage','GoodWikiKarma',2);",
    "INSERT INTO `foo_links` VALUES ('WikiName','CapitalizedWords',2);",
    "INSERT INTO `foo_links` VALUES ('HelpPage','WikiName',3);",
    "INSERT INTO `foo_links` VALUES ('HowDoINavigate','HierarchalStructure',2);",
    "INSERT INTO `foo_links` VALUES ('HowDoINavigate','RecentChanges',1);",
    "INSERT INTO `foo_links` VALUES ('JumpSearch','HowDoINavigate',1);",
    "INSERT INTO `foo_links` VALUES ('JumpSearch','RecentChanges',1);",
    "INSERT INTO `foo_links` VALUES ('JumpSearch','JumpSearch',2);",
    "INSERT INTO `foo_links` VALUES ('HowDoINavigate','JumpSearch',1);",
    "INSERT INTO `foo_links` VALUES ('HelpPage','HowDoINavigate',1);",
    "INSERT INTO `foo_links` VALUES ('HowDoIEdit','RemoteWikiLinks',2);",
    "INSERT INTO `foo_links` VALUES ('HowDoIEdit','WikiName',2);",
    "INSERT INTO `foo_links` VALUES ('TextFormattingRules','WikiName',1);",
    "INSERT INTO `foo_links` VALUES ('StructuredTextRules','TextFormattingRules',1);",
    "INSERT INTO `foo_links` VALUES ('StructuredTextRules','12',4);",
    "INSERT INTO `foo_links` VALUES ('TextFormattingRules','StructuredTextRules',2);",
    "INSERT INTO `foo_links` VALUES ('HowDoIEdit','TextFormattingRules',2);",
    "INSERT INTO `foo_links` VALUES ('HowDoIEdit','SandBox',2);",
    "INSERT INTO `foo_links` VALUES ('HelpPage','HowDoIEdit',1);",
    "INSERT INTO `foo_links` VALUES ('WantedPages','WantedPages',1);",
    "INSERT INTO `foo_links` VALUES ('RecentChanges','RecentChanges',1);",
    "INSERT INTO `foo_links` VALUES ('" . $wikiname . "','JumpSearch',1);",
    "INSERT INTO `foo_links` VALUES ('" . $wikiname . "','HelpPage',1);",

    "INSERT INTO `foo_parents` VALUES ('GoodWikiKarma','HelpPage');",
    "INSERT INTO `foo_parents` VALUES ('HelpPage','" . $wikiname . "');",
    "INSERT INTO `foo_parents` VALUES ('HierarchalStructure','HowDoINavigate');",
    "INSERT INTO `foo_parents` VALUES ('HowDoIEdit','HelpPage');",
    "INSERT INTO `foo_parents` VALUES ('HowDoINavigate','HelpPage');",
    "INSERT INTO `foo_parents` VALUES ('JumpSearch','HowDoINavigate');",
    "INSERT INTO `foo_parents` VALUES ('RecentChanges','HowDoINavigate');",
    "INSERT INTO `foo_parents` VALUES ('RemoteWikiLinks','HowDoIEdit');",
    "INSERT INTO `foo_parents` VALUES ('SandBox','HowDoIEdit');",
    "INSERT INTO `foo_parents` VALUES ('StructuredTextRules','TextFormattingRules');",
    "INSERT INTO `foo_parents` VALUES ('TextFormattingRules','HowDoIEdit');",
    "INSERT INTO `foo_parents` VALUES ('WikiName','HelpPage');",
    "INSERT INTO `foo_parents` VALUES ('WikiWikiWeb','HelpPage');"
);

while($temp = pop @otb) {
  $qid = $dbh->prepare($temp);
  $qid->execute or die "Error creating initial tables\n";
}

print "Your tables were created.  Next you should run configure.pl\n";
print "to configure your preferences.\n";
