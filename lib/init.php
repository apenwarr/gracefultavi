<?php
// $Id: init.php,v 1.8 2002/01/03 21:46:23 smoonen Exp $

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
require('lib/lastedit_check.php');

$PgTbl = $DBTablePrefix . 'pages';
$IwTbl = $DBTablePrefix . 'interwiki';
$SwTbl = $DBTablePrefix . 'sisterwiki';
$LkTbl = $DBTablePrefix . 'links';
$RtTbl = $DBTablePrefix . 'rate';
$RemTbl = $DBTablePrefix . 'remote_pages';
$PaTbl = $DBTablePrefix . 'parents';
$MpTbl = $DBTablePrefix . 'metaphone';
$PwTbl = $DBTablePrefix . 'pageswatch';
$LeTbl = $DBTablePrefix . 'lastedit';
// Don't forget to update pagestore->lock() when adding new tables.

$pagestore = new PageStore();
$db = $pagestore->dbh;

$Entity = array();                      // Global parser entity list.

// Strip slashes from incoming variables.

if(get_magic_quotes_gpc())
{
  if(isset($document)) $document = stripslashes($document);
  if(isset($categories)) $categories = stripslashes($categories);
  if(isset($comment)) $comment = stripslashes($comment);
  if(isset($page)) $page = stripslashes($page);
}

// Read username from htaccess login
if(isset($_SERVER["PHP_AUTH_USER"]))
    $UserName = $_SERVER["PHP_AUTH_USER"];
else if(isset($_SERVER["REMOTE_USER"]))
    $UserName = $_SERVER["REMOTE_USER"];

/*
$userinfo = posix_getpwnam($UserName);

//$gecos = $userinfo['gecos'];
//$UserName = str_replace(" ", "", $gecos);
//list($UserName) = split(',', $UserName); // to eliminate any trailing commas
$UserName = $userinfo['name'];
$UserName = str_replace(" ", "", $UserName);
*/

// Read user preferences from cookie.

//$UserInfo = posix_getpwnam($PHP_AUTH_USER);
//    { $UserName = urldecode($result[1]); }

if(isset($HTTP_COOKIE_VARS[$CookieName])) $prefstr = $HTTP_COOKIE_VARS[$CookieName];
//$prefstr='';

if(!empty($prefstr))
{
  if(ereg("rows=([[:digit:]]+)", $prefstr, $result))
    { $EditRows = $result[1]; }
  if(ereg("cols=([[:digit:]]+)", $prefstr, $result))
    { $EditCols = $result[1]; }
/*  if(ereg("user=([^&]*)", $prefstr, $result))
    { $UserName = $_SERVER["PHP_AUTH_USER"];
      $userinfo = posix_getpwnam($UserName);
      $gecos = $userinfo['gecos'];
      $UserName = str_replace(" ", "", $gecos);}*/
  if(ereg("days=([[:digit:]]+)", $prefstr, $result))
    { $DayLimit = $result[1]; }
  if(ereg("auth=([[:digit:]]+)", $prefstr, $result))
    { $AuthorDiff = $result[1]; }
  if(ereg("min=([[:digit:]]+)", $prefstr, $result))
    { $MinEntries = $result[1]; }
  if(ereg("hotpages=([[:digit:]]+)", $prefstr, $result))
    { $UseHotPages = $result[1]; }
  if(ereg("hist=([[:digit:]]+)", $prefstr, $result))
    { $HistMax = $result[1]; }
  if(ereg("tzoff=([[:digit:]]+)", $prefstr, $result))
    { $TimeZoneOff = $result[1]; }
}

if($Charset != '')
  { header("Content-Type: text/html; charset=$Charset"); }

$ViewMacroEngine=array();
//$SaveMacroEngine=array();

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
               //if(method_exists($ViewMacroEngine[$name], "save"))
               //{
               //   array_push($SaveMacroEngine, $name);
               //}
           }
        }
    }
    closedir($dir);
}

// Check if the lastedit table has been created and is up to date.
lastedit_check();

?>
