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
require('parse/schedulator.php');

$PgTbl = $DBTablePrefix . 'pages';
$IwTbl = $DBTablePrefix . 'interwiki';
$SwTbl = $DBTablePrefix . 'sisterwiki';
$LkTbl = $DBTablePrefix . 'links';
$RtTbl = $DBTablePrefix . 'rate';
$RemTbl = $DBTablePrefix . 'remote_pages';
$PaTbl = $DBTablePrefix . 'parents';
$MpTbl = $DBTablePrefix . 'metaphone';
$PwTbl = $DBTablePrefix . 'pageswatch';


$pagestore = new PageStore();
$db = $pagestore->dbh;

$Entity = array();                      // Global parser entity list.

// Strip slashes from incoming variables.

if(get_magic_quotes_gpc())
{
  $document = stripslashes($document);
  $categories = stripslashes($categories);
  $comment = stripslashes($comment);
  $page = stripslashes($page);
}

// Read username from htaccess login
$UserName = $_SERVER["PHP_AUTH_USER"];
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

$prefstr = $HTTP_COOKIE_VARS[$CookieName];
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
  if(ereg("hist=([[:digit:]]+)", $prefstr, $result))
    { $HistMax = $result[1]; }
  if(ereg("tzoff=([[:digit:]]+)", $prefstr, $result))
    { $TimeZoneOff = $result[1]; }
}

if($Charset != '')
  { header("Content-Type: text/html; charset=$Charset"); }
?>
