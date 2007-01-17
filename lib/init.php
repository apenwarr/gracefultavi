<?php

// General initialization code.

// Flag character for parse engine.
// Note: Must ABSOLUTELY be here, it is used in config.php.
$FlgChr = chr(255);

require('lib/defaults.php');
require('config.php');
require('lib/url.php');
require('lib/messages.php');
require('lib/pagestore.php');
require('lib/rate.php');

$PgTbl = $DBTablePrefix . 'pages';
$CoTbl = $DBTablePrefix . 'content';
$IwTbl = $DBTablePrefix . 'interwiki';
$SwTbl = $DBTablePrefix . 'sisterwiki';
$LkTbl = $DBTablePrefix . 'links';
$RtTbl = $DBTablePrefix . 'rate';
$RemTbl = $DBTablePrefix . 'remote_pages';
$PaTbl = $DBTablePrefix . 'parents';
$SuTbl = $DBTablePrefix . 'subscribe';
$VeTbl = $DBTablePrefix . 'version';
// Don't forget to update pagestore->lock() when adding new tables.

// page attributes
define('MUTABLE_ATTR', 1);
define('TEMPLATE_ATTR', 2);
//define('', 4);
//define('', 8);
//define('', 16);

$pagestore = new PageStore();
$db = $pagestore->dbh;

$Entity = array();                      // Global parser entity list.

// Strip slashes from incoming variables.
if(get_magic_quotes_gpc())
{
  if(isset($categories)) $categories = stripslashes($categories);
  if(isset($comment)) $comment = stripslashes($comment);
  if(isset($document)) $document = stripslashes($document);
  if(isset($find)) $find = stripslashes($find);
  if(isset($nickname)) $nickname = stripslashes($nickname);
  if(isset($page)) $page = stripslashes($page);
  if(isset($text_after)) $text_after = stripslashes($text_after);
  if(isset($text_before)) $text_before = stripslashes($text_before);
}

// Read username from htaccess login
if(isset($_SERVER["PHP_AUTH_USER"]))
    $UserName = $_SERVER["PHP_AUTH_USER"];
else if(isset($_SERVER["REMOTE_USER"]))
    $UserName = $_SERVER["REMOTE_USER"];

// Read user preferences from cookie.
$prefstr = isset($HTTP_COOKIE_VARS[$CookieName]) ?
    $HTTP_COOKIE_VARS[$CookieName] : '';

if(!empty($prefstr))
{
  if(ereg("rows=([[:digit:]]+)", $prefstr, $result))
    { $EditRows = $result[1]; }
  if(ereg("nickname=([^&]*)", $prefstr, $result))
    {
      $NickName = rawurldecode($result[1]);
      if (posix_getpwnam($NickName) !== false) { $NickName = ''; }
    }
  if(ereg("days=([[:digit:]]+)", $prefstr, $result))
    { $DayLimit = $result[1]; }
  if(ereg("min=([[:digit:]]+)", $prefstr, $result))
    { $MinEntries = $result[1]; }
  if(ereg("hotpages=([[:digit:]]+)", $prefstr, $result))
    { $UseHotPages = $result[1]; }
  if(ereg("hist=([[:digit:]]+)", $prefstr, $result))
    { $HistMax = $result[1]; }
  if(ereg("tzoff=(-?[[:digit:]]+)", $prefstr, $result))
    { $TimeZoneOff = $result[1]; }
}

if($Charset != '')
  { header("Content-Type: text/html; charset=$Charset"); }

$ViewMacroEngine=array();

if(!$WorkingDirectory) $WorkingDirectory = ".";

if($dir=opendir("$WorkingDirectory/macros"))
{
    while($file=readdir($dir))
    {
        if ($file != ".." && $file != ".")
        {
           $pieces=explode(".", $file);
           $name=$pieces[0];
           if($pieces[count($pieces)-1]=="php")
           {
               require_once("macros/$file");
               eval("\$ViewMacroEngine['$name']=new Macro_$name;");
               if(isset($ViewMacroEngine[$name]->trigger))
               {
                  // Macro has an alternate trigger defined, use that
                  // instead of the macro name.
                  $ViewMacroEngine[$ViewMacroEngine[$name]->trigger]=$ViewMacroEngine[$pieces[0]];
                  $name=$ViewMacroEngine[$name]->trigger;
                  unset($ViewMacroEngine[$pieces[0]]);
               }
           }
        }
    }
    closedir($dir);
}

?>
