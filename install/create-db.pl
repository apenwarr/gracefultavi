#!/usr/bin/perl

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
                     . "bodylength SMALLINT UNSIGNED NOT NULL DEFAULT '0', "
                     . "attributes TINYINT UNSIGNED NOT NULL DEFAULT 1, "
                     . "createtime TIMESTAMP(14) NOT NULL, "
                     . "updatetime TIMESTAMP(14) NOT NULL, "
                     . "body TEXT NOT NULL, "
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
                     . "restricted tinyint(1) NOT NULL default '0', "
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

$qid = $dbh->prepare("CREATE TABLE " . $prefix . "poll ( "
                     . "id int(10) unsigned NOT NULL auto_increment, "
                     . "title varchar(200) NOT NULL default '', "
                     . "author varchar(80) NOT NULL default '', "
                     . "choice varchar(200) NOT NULL default '', "
                     . "primary key (id) )");
$qid->execute or die "Error creating table\n";

$qid = $dbh->prepare("CREATE TABLE " . $prefix . "version ( "
                     . "version TINYINT(3) UNSIGNED DEFAULT 0 )");
$qid->execute or die "Error creating table\n";

@otb = (
    "INSERT INTO `" . $prefix . "pages` VALUES (1,'" . $wikiname . "','" . $wikiname . "',1,1,'" . $wikiname . "',271,1,NULL,NULL,'');",
    "INSERT INTO `" . $prefix . "pages` VALUES (2,'GoodWikiKarma','GoodWikiKarma',1,1,'KTWKKRM',1043,1,NULL,NULL,'');",
    "INSERT INTO `" . $prefix . "pages` VALUES (3,'HelpPage','HelpPage',1,1,'HLPJ',1029,1,NULL,NULL,'');",
    "INSERT INTO `" . $prefix . "pages` VALUES (4,'HierarchalStructure','HierarchalStructure',1,1,'HRRXLSTRKTR',373,1,NULL,NULL,'');",
    "INSERT INTO `" . $prefix . "pages` VALUES (5,'HowDoIEdit','HowDoIEdit',1,1,'HTTT',1867,1,NULL,NULL,'');",
    "INSERT INTO `" . $prefix . "pages` VALUES (6,'HowDoINavigate','HowDoINavigate',1,1,'HTNFKT',350,1,NULL,NULL,'');",
    "INSERT INTO `" . $prefix . "pages` VALUES (7,'JumpSearch','JumpSearch',1,1,'JMPSRX',1168,1,NULL,NULL,'');",
    "INSERT INTO `" . $prefix . "pages` VALUES (8,'RecentChanges','RecentChanges',1,1,'RSNTXNJS',8,0,NULL,NULL,'');",
    "INSERT INTO `" . $prefix . "pages` VALUES (9,'RemoteWikiLinks','RemoteWikiLinks',1,1,'RMTWKLNKS',1270,1,NULL,NULL,'');",
    "INSERT INTO `" . $prefix . "pages` VALUES (10,'SandBox','SandBox',1,1,'SNTBKS',29,1,NULL,NULL,'');",
    "INSERT INTO `" . $prefix . "pages` VALUES (11,'TextFormattingRules','TextFormattingRules',1,1,'TKSTFRMTNKRLS',15860,1,NULL,NULL,'');",
    "INSERT INTO `" . $prefix . "pages` VALUES (12,'WantedPages','WantedPages',1,1,'WNTTPJS',73,0,NULL,NULL,'');",
    "INSERT INTO `" . $prefix . "pages` VALUES (13,'WikiName','WikiName',1,1,'WKNM',185,1,NULL,NULL,'');",
    "INSERT INTO `" . $prefix . "pages` VALUES (14,'WikiWikiWeb','WikiWikiWeb',1,1,'WKWKWB',86,1,NULL,NULL,'');",
    "INSERT INTO `" . $prefix . "pages` VALUES (15,'OpenNit','OpenNit',1,1,'OPNT',41,1,NULL,NULL,'');",
    "INSERT INTO `" . $prefix . "pages` VALUES (16,'RemoteWikiURL','RemoteWikiURL',1,1,'RMTWKRL',26,1,NULL,NULL,'');",
    "INSERT INTO `" . $prefix . "pages` VALUES (17,'CsvTable','CsvTable',1,1,'KSFTBL',13,1,NULL,NULL,'');",
    "INSERT INTO `" . $prefix . "pages` VALUES (18,'GracefulTavi','GracefulTavi',1,1,'KRSFLTF',98,1,NULL,NULL,'');",

    "INSERT INTO `" . $prefix . "content` VALUES (1,1,NULL,NULL,0,'','','','Welcome to [[GetTopLevel]].\\n\\n***Add content here***\\n\\n<hr>\\nAre you new to Wiki? See HelpPage and <a href=\"?action=prefs\">UserOptions</a> to get started.\\n\\nNavigation Hint: use the \"Jump to:\" (JumpSearch) entry box at the top of the page. It\\'s more powerful than you think.\\n');",
    "INSERT INTO `" . $prefix . "content` VALUES (2,1,NULL,NULL,0,'','','','So, you know how to use the Wiki.  Here\\'s how to use it well.\\n\\n- **DON\\'T** delete stuff from documentation pages.  It\\'s always good to have a record of what was discussed, even if it\\'s obsolete.  \\n\\n - There are a million ways of thinking of this, but the above statement is not totally true.  See WikiWikiWeb:GoodWikiCitizen and especially WikiWikiWeb:ThreadMess for some more discussion.  If there\\'s a discussion of whether to do something and how, you don\\'t have to retain the entire discussion: you can *summarize* it instead, which will make it more useful to people in the long run.\\n\\n- **DO** clean pages up.  If page X gets cluttered (and especially if it\\'s hard to tell what\\'s current and what\\'s old) move obsolete items to ObsoleteX and provide a link.\\n\\n - ObsoleteX is not always necessary either.  If the stuff really is obsolete, deleting it is pretty much fine.  (But again, in a discussion, be careful to retain the train of thought that made the \"wrong\" viewpoint obsolete.  Otherwise the argument will just happen again later.)\\n');",
    "INSERT INTO `" . $prefix . "content` VALUES (3,1,NULL,NULL,0,'','','','What is this?\\n\\n  It\\'s called a wiki web; it\\'s a kind of collaborative website.\\n\\nThe idea is that anyone who can read pages can also write them, and nobody really \"owns\" a page.  If you notice a spelling mistake, just fix it; if someone forgets to mention something, add it in.  If a page gets too long and complicated, split it into a few smaller pages.\\n\\nYou can change a page by clicking the \"Edit\" button, or add a comment to the bottom using the \"Add a Comment\" button.\\n\\nEach page has a WikiName - a name which is usually one or more capitalized words run together.   When you write a page, [WikiName]s are automatically converted to hyperlinks, so you don\\'t need to know HTML syntax.  If a WikiName doesn\\'t yet have a page to go with it, it gets a blue \\'?\\' that you can click to create the page.\\n\\n===Recommended Readings===\\n\\n- HowDoIEdit\\n- TextFormattingRules - the essential on how to write a wiki page\\n- HowDoINavigate\\n- GoodWikiKarma\\n\\nSee also:\\n\\n- GracefulTavi (this Wiki software)\\n- WikiWikiWeb (the original wiki)\\n');",
    "INSERT INTO `" . $prefix . "content` VALUES (4,1,NULL,NULL,0,'','','','The wiki keeps track of parent-child relationships between pages. Context and overview links are displayed at the top of each page.\\n\\nWhen you create a new page, it\\'s parent will be the page on which you clicked the ? link. You can change the parent, or add more, by clicking on Backlinks.\\n\\nTo view the entire hierarchy, see the <a href=\"?action=content\">Wiki Content</a>.\\n');",
    "INSERT INTO `" . $prefix . "content` VALUES (5,1,NULL,NULL,0,'','','','== How do I edit? ==\\n\\nClick \"Edit\" in the toolbar, at the top and bottom of any page (SandBox is a good place to practice). Edit the text and click the \"Save\" button. Text will be formatted according to the TextFormattingRules.\\n\\n== How do I link and create new pages? ==\\n\\nTo create a link to another wiki page, just write a WikiName. (If you don\\'t use a WikiName, you should enclose the name in square brackets).\\n\\nIf the page does not yet exist, a ? will be displayed - click it to create the new page. This is how new wiki pages are added.\\n\\nTo link to a page on another site, just type the url. Or, you can use HTML. Also, RemoteWikiLinks are a convenient notation for linking to pages on another site.\\n\\n== What if I make a mistake? ==\\n\\nIf some good text gets lost by mistake, you might find it by backing up in your browser - copy and paste it back into the page. Otherwise, the site admin may be able to help undo the changes.\\n\\n== Words to use carefully ==\\n\\nWords like \"current\", \"currently\", \"at the moment\", and \"right now\" should be used carefully. While a topic is still hot, the meaning of those words might be clear and obvious, but as time goes by (sometimes years), it may become difficult to discern what moment in the past \"currently\" actually refers to. You may add a timestamp to avoid this problem: *\"This is currently (as of July 12, 2006) true.\"*\\n\\n== How do I delete a page? ==\\n\\nSave the page with an empty content. The history is preserved, but the wiki act as if the page doesn\\'t exist.\\n\\n== How do I rename a page? ==\\n\\nCreate a page with the new name and copy/paste the content from the old page to the new one.\\n\\nIf the old page is not linked from other pages anymore (see the Backlinks), delete the old page (see how to delete a page above).\\n\\nIf the old page is still being linked from other pages, put a redirection (see TextFormattingRules).\\n');",
    "INSERT INTO `" . $prefix . "content` VALUES (6,1,NULL,NULL,0,'','','','How do I navigate?\\n\\nClick the icon at the top left to return to the [[GetTopLevel]].\\n\\nClick the Backlinks button to list all other pages which link to this one.\\n\\nUse the JumpSearch field which appears on every page for fast navigation and searching.\\n\\nRecentChanges lists all pages by modification date.\\n\\nSee also HierarchalStructure and WantedPages.\\n');",
    "INSERT INTO `" . $prefix . "content` VALUES (7,1,NULL,NULL,0,'','','','To jump directly to a page, enter the first few letters of its name in the \"Jump to / Search\" field at the top-left of the screen and press enter.\\n\\nIf more than one page matches, the alphabetically first one is chosen.  Capital letters are ignored in the search string.\\n\\nIf no matches are found, *or* if the search expression begins with \\'!\\' *or* contains 2 words or more, a full-text search of all the pages is done instead.\\n\\nAbout the full-text search:\\n\\n:The search is case-insensitive, and will try to match each words in the search expression with the title and body of all the pages. The resutls are sorted based on the number of matches, giving more weight to matches in the title.\\n\\n:If the search expression is found as is in the body, the page will show up first in the results.\\n\\n:To force the presence or absence of specific words, prefix the word with respectively the \"+\" or \"-\" sign.\\n\\n:To search for an expression containing a space, enclose it with double quotes. The \"+\" and \"-\" signs also apply and must be placed before the opening double quote.\\n\\nTo list all pages alphabetically, leave the expression blank.\\n\\nSee also: RecentChanges, HowDoINavigate\\n');",
    "INSERT INTO `" . $prefix . "content` VALUES (8,1,NULL,NULL,0,'','','','[[! *]]\\n');",
    "INSERT INTO `" . $prefix . "content` VALUES (9,1,NULL,NULL,0,'','','','A notation for linking to pages on other web sites.\\n\\n\\n=== Simple Notation ===\\n\\nSyntax: \\'&lt;local wiki page name&gt;:&lt;parameter&gt;\\'\\n\\nExample:\\n\\n- OpenNit:FrontPage\\n\\n- GoogleDefine:internet\\n\\nThe local wiki page has to describe the remote server. At minimum it should contain the RemoteWikiURL keyword followed by the URL on the same line. For example:\\n\\n- On the OpenNit page:\\n\\n  RemoteWikiURL: http://open.nit.ca/wiki/?\\n\\n- On the GoogleDefine page:\\n\\n  RemoteWikiURL: http://www.google.com/search?q=define%3A\\n\\nRemoteWikiLinks are then transformed into a link to the URL with the parameter appended to its end.\\n\\n\\n=== Extended Notation ===\\n\\nURLs with multiple parameters can also be defined.\\n\\nSyntax: \\'```&lt;local wiki page name&gt;:&lt;parameter1&gt;[:&lt;parameter2&gt;[:&lt;parameter3&gt;...]]```\\'\\n\\nInstead of being appended to the end of the URL, parameters will replace variables in the URL. For example:\\n\\n- On the GoogleGroups page:\\n\\n  RemoteWikiURL: http://groups.google.com/groups?group=\$1&hl=\$2\\n\\nRemoteWikiLinks for this URL would look like this:\\n\\n- GoogleGroups:alt:fr\\n\\n- GoogleGroups:misc:en\\n\\nand links for these RemoteWikiLinks would respectively be:\\n\\n- http://groups.google.com/groups?group=alt&hl=fr\\n\\n- http://groups.google.com/groups?group=misc&hl=en\\n');",
    "INSERT INTO `" . $prefix . "content` VALUES (10,1,NULL,NULL,0,'','','','This is a sandbox, have fun!\\n');",
    "INSERT INTO `" . $prefix . "content` VALUES (11,1,NULL,NULL,0,'','','','Your text is formatted as a web page according to some simple markup rules. These are intended to be convenient and unobtrusive.\\n\\n[[Toc 3]]\\n\\n\\n== Basic markup ==\\n\\n- Separate paragraphs with a blank line\\n\\n- For *emphasis (aka italic)*, use ```*``` ... ```*```\\n\\n- For \\'\\'\\'bold\\'\\'\\', use ```\\'\\'\\'``` ... ```\\'\\'\\'```\\n\\n- For **highlight**, use ```**``` ... ```**```\\n\\n- For _underline_, use ```_``` ... ```_```\\n\\n- For html headers, use =\\n\\n - ```=``` ... ```=``` for &lt;H1&gt; ... &lt;/H1&gt;\\n - ```==``` ... ```==``` for &lt;H2&gt; ... &lt;/H2&gt;\\n - ```===``` ... ```===``` for &lt;H3&gt; ... &lt;/H3&gt;\\n - ```====``` ... ```====``` for &lt;H4&gt; ... &lt;/H4&gt;\\n - ```=====``` ... ```=====``` for &lt;H5&gt; ... &lt;/H5&gt;\\n - ```======``` ... ```======``` for &lt;H6&gt; ... &lt;/H6&gt;\\n\\n- For underlined html headers, prepend the line with _\\n\\n - ```_=``` ... ```=``` for underlined &lt;H1&gt;\\n - ```_==``` ... ```==``` for underlined &lt;H2&gt;\\n - etc.\\n\\n- For numbered html headers, prepend the line with \@\\n\\n - ```\@=``` ... ```=``` for numbered &lt;H1&gt;\\n - ```\@==``` ... ```==``` for numbered &lt;H2&gt;\\n - etc.\\n\\n- To prevent the default carriage return after html headers, append the header markup with ```\\\\```.\\n\\n - ```=``` ... ```=\\\\``` ...\\n - ```==``` ... ```==\\\\``` ...\\n - etc.\\n\\n- For horizontal line &lt;HR&gt;, use ```----``` on a single line\\n\\n- For monospaced text, i.e the html tag CODE, enclose the text with single quotes,\\n  for example: ```\\'```&lt;dtml-var foo>```\\'``` becomes \\'&lt;dtml-var foo>\\'\\n\\n- For teletype text, i.e the html tag TT, enclose the text with double curly braces,\\n  for example: ```{{```&lt;dtml-var foo>```}}``` becomes {{&lt;dtml-var foo>}}\\n\\n- [WikiName]s and words enclosed in square brackets are converted to hyperlinks (prepend the link or the line with ! to prevent this). Strings enclosed in square brackets that wouldn\\'t be a valid wiki page name won\\'t become a link and don\\'t need to be prepend with a !. To append an anchor to a wiki link, simply follow the WikiName by a # and the anchor name (within the square brackets when they are present).\\n\\n - \\'!HelpPage\\' becomes HelpPage, it is a wiki link\\n - \\'![foo]\\' becomes [foo], it is a valid wiki name\\n - \\'!!HelpPage\\' becomes !HelpPage, prefixing with ! prevents the conversion into a link\\n - \\'!![foo]\\' becomes ![foo], same as previous line\\n - \\'[random text]\\' becomes [random text], it is not a valid wiki name and is not converted into a link\\n - \\'![random text]\\' becomes ![random text], prefixing with ! is unnecessary\\n - \\'```TextFormattingRules#toc1```\\' becomes TextFormattingRules#toc1\\n\\n- In order to change the caption of a WikiName link, enclose the WikiName in square brackets, followed by a pipe \"|\", and the new caption. For example: ```[HelpPage|link to the Help Page]``` becomes [HelpPage|link to the Help Page]. To use an anchor with this notation, follow the new caption by a # and the anchor name. For example: ```[TextFormattingRules|Basic Markup#toc1]``` becomes [TextFormattingRules|Basic Markup#toc1].\\n\\n- Bare urls are converted to hyperlinks; there is no need for the &lt;A&gt; tag. If the url is to an image, it is converted into a &lt;IMG&gt; tag instead.\\n\\n- Bare urls followed by some text enclosed in square brackets are converted to hyperlinks having the text as the caption. For example: ```[http://google.com/ Link to Google]``` becomes [http://google.com/ Link to Google].\\n\\n- Emails like this: \\'```mailto:email\@domain.org```\\' are converted into a link like\\n  this: mailto:email\@domain.org.\\n\\n- HTML & DTML tags may be used\\n\\n- To disable the parsing on a single line, use `````````\\n  ... `````````.\\n\\n- To disable the parsing on multiple lines, use &lt;PRE&gt; ... &lt;/PRE&gt;\\n\\n- In order to define a table, separate each cell with a double pipe \"||\", each row being on a single line. There must be no spaces before the first pipes of a row.\\n\\n <pre>\\n|| cell 1 || cell 2 || cell 3 ||\\n|| cell 4 || cell 5 || cell 6 ||\\n|| cell 7 || cell 8 || cell 9 ||</pre>\\n\\n will produce this table:<br><br>\\n|| cell 1 || cell 2 || cell 3 ||\\n|| cell 4 || cell 5 || cell 6 ||\\n|| cell 7 || cell 8 || cell 9 ||\\n\\n- In order to enable a \"Download as CSV\" link for a table, prepend the _first_ row with a ```\\'*\\'```:\\n\\n <pre>\\n*|| A1 || B1 || C1 || D1 || E1 || F1 || G1 || H1 || I1 || J1 || K1 || L1 || M1 || N1 || O1 || P1 ||\\n || A2 || B2 || C2 || D2 || E2 || F2 || G2 || H2 || I2 || J2 || K2 || L2 || M2 || N2 || O2 || P2 ||\\n || A3 || B3 || C3 || D3 || E3 || F3 || G3 || H3 || I3 || J3 || K3 || L3 || M3 || N3 || O3 || P3 || </pre>\\n\\n will produce this table:<br><br>\\n*|| A1 || B1 || C1 || D1 || E1 || F1 || G1 || H1 || I1 || J1 || K1 || L1 || M1 || N1 || O1 || P1 ||\\n|| A2 || B2 || C2 || D2 || E2 || F2 || G2 || H2 || I2 || J2 || K2 || L2 || M2 || N2 || O2 || P2 ||\\n|| A3 || B3 || C3 || D3 || E3 || F3 || G3 || H3 || I3 || J3 || K3 || L3 || M3 || N3 || O3 || P3 ||\\n\\n- In order to convert a CSV file to a wiki formatted table, use the CsvTable macro.\\n\\n\\n== Special Markup ==\\n\\n=== Redirection ===\\n\\n:<b>Syntax: \\'```#redirect page_name```\\'</b>\\n\\n:Redirects to the specified wiki page name. Its behavior is highly inspired from [http://en.wikipedia.org/wiki/Wikipedia:How_to_use_redirect_pages Wikipedia].\\n\\n: *General tips:*\\n\\n - A page content is not restricted to the redirection command; it may contain other information, like why it has been created or redirected.\\n\\n - Use preview to test the redirection; the page name will turn into a link if it\\'s valid and if it exists.\\n\\n - After saving, you won\\'t be redirected to the specified page.\\n\\n - When a redirection occurs, a line below the page title will inform you: \"Redirected from previous_page\", previous_page being a link to the page without redirection so you can edit, diff, reparent, etc.\\n\\n - When viewing an archive version of a page, redirection is also disabled.\\n\\n\\n== Indentations ==\\n\\nThe word \"indentation\" is used to describe nested paragraphs, with or without bullets or numbers. Those refers to UL, OL, and DL html tags.\\n\\n- For bulleted lists, use - or *\\n\\n- For numbered lists using numbers, use # or a digit followed by a dot (![0-9].)\\n\\n- For numbered lists using letters, use a lowercase letter from \"a\" to \"z\" followed by a dot (![a-z].)\\n\\n- For numbered lists using roman numbers, use the letter \"i\" (in lowercase) two times followed by a dot (ii.)\\n\\n- For simple indentation without bullets or numbers, use :\\n\\n- For email-style highlighting with a nice blue line on the left, use &gt;\\n\\n\\nThe following rules apply to each of the previous indentation modes.\\n\\n- Each line starting with an indent char defines a new list item.\\n\\n- The number of spaces before the indent char defines the indentation level.\\n\\n- Consecutive lines after a list item will remain in the same list item as long as there are no blank line in between.\\n\\n- To start a new paragraph within the same item, insert a blank line and prepend the new paragraph with as many spaces as the indentation level.\\n\\n- Lines ending with a backslash will treat the next line as being in the same item, no matter what. If the next line begins with an indent char, that char will be seen as normal text instead of an indent char.\\n\\n- To stop the indentation list, insert a blank line.\\n\\n- To stop a numbered lists and start a new one right afterwards, use something like \\'&lt;!-- end the list --&gt;\\' in between, the numbering will restart to 1.\\n\\n\\nIndentation example: (see the source of this page to see how this was made)\\n\\n# Item 1\\n # Item 1.1\\n # Item 1.2\\n# Item 2\\n # Item 2.1\\n # Item 2.2\\n  # Item 2.2.1\\n# Item 3\\n\\na. Using letters 1\\n ii. Using roman numbers 1.1\\n ii. Using roman numbers 1.2\\nb. Using letters 2\\n ii. Using roman numbers 2.1\\n ii. Using roman numbers 2.2\\nc. Using letters 3\\n\\n- This is\\na text\\non multiple lines\\nwithin the same item\\n\\n- This is the first paragraph\\nof this item\\n\\n This is the second paragraph\\nof this item\\n\\n- This is another simple item\\n\\n :This line is indented below the previous item with a colon\\n\\n- This item steals next lines\\\\\\n\\\\\\nwith the backslash char at\\\\\\nthe\\\\\\n\\\\\\n\\\\\\nend of the\\\\\\nline\\n\\n> This is an email-style\\n> highlighting\\n>\\n>> with a nice blue line\\n>\\n> on the left\\n\\n\\n== Macros ==\\n\\nMacros are an easy way to add features to GracefulTavi. As long as they follow\\ncertain rules, it\\'s only a matter of dropping a php file in a directory and the\\nmacro is up and running. The syntax for calling a macro is:\\n```[[macro_name arguments]]```.\\n\\nHere are the most useful macros we\\'re using on this wiki.\\n\\n=== Attach ===\\n\\n:<b>Syntax: \\'```[[Attach image|inline|csv|file filename]]```\\'</b>\\n\\n This macro allows you to attach a file to the given wiki page.  Filenames are globally shared across all pages, so use a creative name that won\\'t conflict.  (The reason for this is that you might want to link to the same file from more than one page.)  Attachments can be deleted (or undeleted) or locked to prevent tampering.  After you have locked an attachment, you can\\'t change it anymore (unless the admin unlocks it); you\\'ll have to pick a new filename if you want to replace it.\\n\\n The Attach macro allows several different view modes:\\n  - \\'image\\': display the attachment inline as an image.\\n  - \\'inline\\': display the attachment inline as preformatted text.\\n  - \\'csv\\': display the attachment inline as a table, with columns separated by commas.\\n  - \\'file\\': do not attempt to display the attachment inline at all; just display a hyperlink to the file.  (If you leave out the type, this is the default.)\\n\\n=== Box ===\\n\\n:<b>Syntax: \\'```[[Box start|end]]```\\'</b>\\n\\n This Macro surrounds a chunk of text with a thin black line, i.e. puts it into a box. Every \"\\'Box start\\'\" must have a corresponding \"\\'Box end\\'\". It is possible to nest multiple Boxes inside of each other.\\n\\n:<b>Example:</b>\\n\\n The following commands:\\n\\n :\\'```[[Box start]]```\\'\\n\\n :Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.\\n\\n :\\'```[[Box end]]```\\'\\n\\n will produce this result:\\n[[Box start]]\\nLorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.\\n[[Box end]]\\n\\n=== Dance ===\\n\\n:<b>Authors:</b> OpenNit:senns, OpenNit:kjrose\\n\\n:<b>Syntax: \\'```[[Dance TypeOfDance]]```\\'</b>\\n\\n :Where !TypeOfDance = {\"\", kirby, kirbyjive, kirbyboogie, russian}\\n\\n This Macro puts little ASCII dancing people on your page wherever you declare it.\\n\\n:<b>Example:</b>\\n\\n :<pre>[[Dance kirbyjive]]</pre>\\n\\n  [[Dance kirbyjive]]\\n\\n=== HTML anchor ===\\n\\n:<b>Syntax: \\'```[[Anchor anchor_name]]```\\'</b>\\n\\n Insert an HTML anchor.\\n\\n:<b>Example:</b> \\'```[[Anchor foo]]```\\' will insert this: \\'&lt;a name=\"foo\">&lt;/a>\\'\\n\\n=== Memo ===\\n\\n:<b>Syntax:</b>\\n\\n:\\'```[[Memo START label]]```\\'\\n:\\'```[[Memo COLOR color]]```\\'\\n:\\'```[[Memo content]]```\\'\\n:\\'```[[Memo END]]```\\'\\n\\n Puts a memo on the page, displaying only the *label*. When passing the mouse\\n cursor over the *label*, the *content* will display in the shape of a sticky\\n note. The COLOR command is optional, the value of *color* can be any standard\\n html color value (#C0FFC0, blue, pink, etc.), it is #FFC (pale yellow) by\\n default.\\n\\n:\\'```[[Memo DISABLE]]```\\'\\n\\n The DISABLE command prevents any memo occuring after the command from appearing.\\n\\n:<b>Example:</b>\\n\\n The following commands:\\n\\n :\\'```[[Memo START This a sticky note!]]```\\'\\n :\\'```[[Memo COLOR #ccf]]```\\'\\n :\\'```[[Memo Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum.]]```\\'\\n :\\'```[[Memo]]```\\'\\n :\\'```[[Memo At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. At vero eos et accusam et justo duo dolores et ea rebum.]]```\\'\\n :\\'```[[Memo END]]```\\'\\n\\n will produce this memo: [[Memo START This a sticky note!]]\\n[[Memo COLOR #ccf]]\\n[[Memo Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum.]]\\n[[Memo]]\\n[[Memo At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. At vero eos et accusam et justo duo dolores et ea rebum.]]\\n[[Memo END]]\\n\\n=== Revision History ===\\n\\n:<table><tr valign=\"top\">\\n<td style=\"background:#ffffff;\"><b>Syntax:</b></td>\\n<td style=\"background:#ffffff;\"><b>\\n\\'```[[RevisionHistory &lt;revision number&gt; &lt;override description&gt;]]```\\' <br>\\n\\'```[[RevisionHistory[ ALL]]]```\\'\\n</b></td>\\n</tr></table>\\n\\n Displays a *revision history* table based on the wiki database content. The table shows columns for the date, version, description, and author of each modification. By default, it skips minor edits, edits with empty comments, or when the comment is \"Comment\". To show all edits, use the \"ALL\" parameter. The first edit is always displayed.\\n\\n In case some revisions would be missing, or have an unsatisfying description, the *override* syntax may be used to add these revisions to the table. There can be as many override lines as required, but they must all come before the last call to !RevisionHistory with the optional ALL parameter.\\n\\n:<b>Example:</b>\\n\\n The following lines:\\n\\n :```== Revision History ==```<br>\\n  ```[[RevisionHistory 2 The text formatting rules!]]```<br>\\n  ```[[RevisionHistory]]```\\n\\n produces this result on this page:\\n\\n :<h2>Revision History</h2>\\n  [[RevisionHistory 2 The text formatting rules!]]\\n  [[RevisionHistory]]\\n\\n=== !ShowHide ===\\n\\n:<b>Syntax: \\'```[[ShowHide start|end [hide]]]```\\'</b>\\n\\n Provides a way to hide and show chunks of a page. Every \"\\'!ShowHide start\\'\" must have a corresponding \"\\'!ShowHide end\\'\". It is possible to nest multiple !ShowHide sections inside of each other.\\n\\n:<b>Example:</b>\\n\\n The following commands:\\n\\n :\\'```[[ShowHide start]]```\\'\\n\\n :Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.\\n\\n :\\'```[[ShowHide start hide]]```\\'\\n :At vero eos et accusam et justo duo dolores et ea rebum.\\n :\\'```[[ShowHide end]]```\\'\\n\\n :Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.\\n\\n :\\'```[[ShowHide end]]```\\'\\n\\n will produce this result:\\n\\n[[ShowHide start]]\\nLorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.\\n\\n[[ShowHide start hide]]\\nAt vero eos et accusam et justo duo dolores et ea rebum.\\n[[ShowHide end]]\\n\\nStet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.\\n[[ShowHide end]]\\n\\n=== Table of content ===\\n\\n:<b>Syntax: \\'```[[Toc maxlevel]]```\\'</b>\\n\\n Display a table of content based on the headers in the page. The optional\\n \\'maxlevel\\' param is the maximum header level to display in the table of\\n content. See the table of content on this page for a good example.\\n\\n=== Transclusion ===\\n\\n:<b>Syntax: \\'```[[Transclude wiki_page_name]]```\\'</b>\\n\\n Include the entire content of a wiki page within the page.\\n\\n:<b>Example:</b> \\'```[[Transclude WikiPage]]```\\' will include the content of WikiPage.\\n\\n=== Wiki Poll ===\\n\\n:<b>Syntax: \\'```[[WikiPoll [poll_type] poll_title (choice_1[|choice_2[|choice_3...]])]]```\\'</b>\\n\\n Display a poll voting form or the poll results in the page. The \\'poll_type\\'\\n parameter is either \\'SINGLE\\' (radio) or \\'MULTIPLE\\' (checkbox). It defaults to\\n \\'SINGLE\\' if missing.\\n\\n:<b>Examples:</b>\\n\\n \\'```[[WikiPoll What is your favourite animal? (cat|dog|turtle)]]```\\'\\n\\n \\'```[[WikiPoll MULTIPLE What are your favourite colours? (red|green|blue|pink)]]```\\'\\n');",
    "INSERT INTO `" . $prefix . "content` VALUES (12,1,NULL,NULL,0,'','','','If you are bored, the following pages need information:\\n\\n[[WantedPages]]\\n');",
    "INSERT INTO `" . $prefix . "content` VALUES (13,1,NULL,NULL,0,'','','','Wiki page names are two or more CapitalizedWords joined together without spaces. \\nThey are easy to read and write while still being distinctive enough to be recognized by the computer.\\n');",
    "INSERT INTO `" . $prefix . "content` VALUES (14,1,NULL,NULL,0,'','','','The original wiki web, and home to a thriving community.\\n\\nRemoteWikiURL: http://www.c2.com/cgi/wiki?\\n');",
    "INSERT INTO `" . $prefix . "content` VALUES (15,1,NULL,NULL,0,'','','','RemoteWikiURL: http://open.nit.ca/wiki/?\\n');",
    "INSERT INTO `" . $prefix . "content` VALUES (16,1,NULL,NULL,0,'','','','#redirect RemoteWikiLinks\\n');",
    "INSERT INTO `" . $prefix . "content` VALUES (17,1,NULL,NULL,0,'','','','[[CsvTable]]\\n');",
    "INSERT INTO `" . $prefix . "content` VALUES (18,1,NULL,NULL,0,'','','','GracefulTavi is the wiki software running _this_ wiki.\\n\\nSee the home page on OpenNit:GracefulTavi\\n');",

    "INSERT INTO `" . $prefix . "interwiki` VALUES ('OpenNit','OpenNit','http://open.nit.ca/wiki/?');",
    "INSERT INTO `" . $prefix . "interwiki` VALUES ('WikiWikiWeb','WikiWikiWeb','http://www.c2.com/cgi/wiki?');",

    "INSERT INTO `" . $prefix . "links` VALUES ('CsvTable','CsvTable',1);",
    "INSERT INTO `" . $prefix . "links` VALUES ('GoodWikiKarma','GoodWikiCitizen',2);",
    "INSERT INTO `" . $prefix . "links` VALUES ('GoodWikiKarma','ObsoleteX',4);",
    "INSERT INTO `" . $prefix . "links` VALUES ('GoodWikiKarma','ThreadMess',2);",
    "INSERT INTO `" . $prefix . "links` VALUES ('GoodWikiKarma','WikiWikiWeb',2);",
    "INSERT INTO `" . $prefix . "links` VALUES ('GracefulTavi','GracefulTavi',1);",
    "INSERT INTO `" . $prefix . "links` VALUES ('HelpPage','GoodWikiKarma',1);",
    "INSERT INTO `" . $prefix . "links` VALUES ('HelpPage','GracefulTavi',2);",
    "INSERT INTO `" . $prefix . "links` VALUES ('HelpPage','HowDoIEdit',1);",
    "INSERT INTO `" . $prefix . "links` VALUES ('HelpPage','HowDoINavigate',1);",
    "INSERT INTO `" . $prefix . "links` VALUES ('HelpPage','TextFormattingRules',1);",
    "INSERT INTO `" . $prefix . "links` VALUES ('HelpPage','WikiName',3);",
    "INSERT INTO `" . $prefix . "links` VALUES ('HelpPage','WikiWikiWeb',1);",
    "INSERT INTO `" . $prefix . "links` VALUES ('HowDoIEdit','RemoteWikiLinks',1);",
    "INSERT INTO `" . $prefix . "links` VALUES ('HowDoIEdit','SandBox',1);",
    "INSERT INTO `" . $prefix . "links` VALUES ('HowDoIEdit','TextFormattingRules',2);",
    "INSERT INTO `" . $prefix . "links` VALUES ('HowDoIEdit','WikiName',2);",
    "INSERT INTO `" . $prefix . "links` VALUES ('HowDoINavigate','GetTopLevel',2);",
    "INSERT INTO `" . $prefix . "links` VALUES ('HowDoINavigate','HierarchalStructure',1);",
    "INSERT INTO `" . $prefix . "links` VALUES ('HowDoINavigate','JumpSearch',1);",
    "INSERT INTO `" . $prefix . "links` VALUES ('HowDoINavigate','RecentChanges',1);",
    "INSERT INTO `" . $prefix . "links` VALUES ('HowDoINavigate','WantedPages',1);",
    "INSERT INTO `" . $prefix . "links` VALUES ('JumpSearch','HowDoINavigate',1);",
    "INSERT INTO `" . $prefix . "links` VALUES ('JumpSearch','RecentChanges',1);",
    "INSERT INTO `" . $prefix . "links` VALUES ('RecentChanges','RecentChanges',1);",
    "INSERT INTO `" . $prefix . "links` VALUES ('RemoteWikiLinks','GoogleDefine',4);",
    "INSERT INTO `" . $prefix . "links` VALUES ('RemoteWikiLinks','GoogleGroups',6);",
    "INSERT INTO `" . $prefix . "links` VALUES ('RemoteWikiLinks','OpenNit',1);",
    "INSERT INTO `" . $prefix . "links` VALUES ('RemoteWikiLinks','RemoteWikiLinks',3);",
    "INSERT INTO `" . $prefix . "links` VALUES ('RemoteWikiLinks','RemoteWikiURL',4);",
    "INSERT INTO `" . $prefix . "links` VALUES ('" . $wikiname . "','HelpPage',1);",
    "INSERT INTO `" . $prefix . "links` VALUES ('" . $wikiname . "','JumpSearch',1);",
    "INSERT INTO `" . $prefix . "links` VALUES ('TextFormattingRules','CsvTable',1);",
    "INSERT INTO `" . $prefix . "links` VALUES ('TextFormattingRules','foo',2);",
    "INSERT INTO `" . $prefix . "links` VALUES ('TextFormattingRules','GracefulTavi',1);",
    "INSERT INTO `" . $prefix . "links` VALUES ('TextFormattingRules','HelpPage',2);",
    "INSERT INTO `" . $prefix . "links` VALUES ('TextFormattingRules','Memo',2);",
    "INSERT INTO `" . $prefix . "links` VALUES ('TextFormattingRules','RevisionHistory',4);",
    "INSERT INTO `" . $prefix . "links` VALUES ('TextFormattingRules','ShowHide',8);",
    "INSERT INTO `" . $prefix . "links` VALUES ('TextFormattingRules','TextFormattingRules',2);",
    "INSERT INTO `" . $prefix . "links` VALUES ('TextFormattingRules','WikiName',4);",
    "INSERT INTO `" . $prefix . "links` VALUES ('TextFormattingRules','WikiPage',2);",
    "INSERT INTO `" . $prefix . "links` VALUES ('WantedPages','WantedPages',1);",
    "INSERT INTO `" . $prefix . "links` VALUES ('WikiName','CapitalizedWords',2);",

    "INSERT INTO `" . $prefix . "parents` VALUES ('CsvTable','TextFormattingRules');",
    "INSERT INTO `" . $prefix . "parents` VALUES ('GoodWikiKarma','HelpPage');",
    "INSERT INTO `" . $prefix . "parents` VALUES ('GracefulTavi','HelpPage');",
    "INSERT INTO `" . $prefix . "parents` VALUES ('HelpPage','" . $wikiname . "');",
    "INSERT INTO `" . $prefix . "parents` VALUES ('HierarchalStructure','HowDoINavigate');",
    "INSERT INTO `" . $prefix . "parents` VALUES ('HowDoIEdit','HelpPage');",
    "INSERT INTO `" . $prefix . "parents` VALUES ('HowDoINavigate','HelpPage');",
    "INSERT INTO `" . $prefix . "parents` VALUES ('JumpSearch','HowDoINavigate');",
    "INSERT INTO `" . $prefix . "parents` VALUES ('OpenNit','RemoteWikiLinks');",
    "INSERT INTO `" . $prefix . "parents` VALUES ('RecentChanges','HowDoINavigate');",
    "INSERT INTO `" . $prefix . "parents` VALUES ('RemoteWikiLinks','HowDoIEdit');",
    "INSERT INTO `" . $prefix . "parents` VALUES ('RemoteWikiURL','RemoteWikiLinks');",
    "INSERT INTO `" . $prefix . "parents` VALUES ('SandBox','HowDoIEdit');",
    "INSERT INTO `" . $prefix . "parents` VALUES ('TextFormattingRules','HowDoIEdit');",
    "INSERT INTO `" . $prefix . "parents` VALUES ('WantedPages','HowDoINavigate');",
    "INSERT INTO `" . $prefix . "parents` VALUES ('WikiName','HelpPage');",
    "INSERT INTO `" . $prefix . "parents` VALUES ('WikiWikiWeb','HelpPage');",

    "INSERT INTO `" . $prefix . "version` VALUES (4);"
);

while($temp = pop @otb) {
  $qid = $dbh->prepare($temp);
  $qid->execute or die "Error creating initial tables\n";
}

for ($i=1; $i<=18; $i++) {
  $qry = "SELECT body FROM `" . $prefix . "content` WHERE page=" . $i;
  $qid = $dbh->prepare($qry);
  $qid->execute or die "Error creating initial tables\n";
  @row = $qid->fetchrow();

  $qry = "UPDATE `". $prefix . "pages` SET body=? WHERE id=?";
  $qid = $dbh->prepare($qry);
  $qid->execute($row[0], $i) or die "Error creating initial tables\n";
}

print "Your tables were created.  Next you should run configure.pl\n";
print "to configure your preferences.\n";
