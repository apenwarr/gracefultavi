<?php
// $Id: main.php,v 1.2 2003/04/01 01:11:33 mich Exp $

// If register_globals is off, we need to harvest the script parameters
// at this point.

/*
if($_SERVER["PATH_INFO"])
{
  header('Location: ' . $_SERVER["SCRIPT_NAME"] . '?' . substr($_SERVER["PATH_INFO"], 1));
  exit;
}
*/

if(!ini_get('register_globals'))
{
  if(isset($HTTP_SERVER_VARS['HTTP_REFERER'])) $HTTP_REFERER = $HTTP_SERVER_VARS['HTTP_REFERER'];
  if(isset($HTTP_SERVER_VARS['QUERY_STRING'])) $QUERY_STRING = $HTTP_SERVER_VARS['QUERY_STRING'];
  if(isset($HTTP_SERVER_VARS['REMOTE_ADDR'])) $REMOTE_ADDR  = $HTTP_SERVER_VARS['REMOTE_ADDR'];

  if(isset($HTTP_GET_VARS['action'])) $action = $HTTP_GET_VARS['action'];
  if(isset($HTTP_GET_VARS['page'])) $page = $HTTP_GET_VARS['page'];
  if(isset($HTTP_GET_VARS['ver1'])) $ver1 = $HTTP_GET_VARS['ver1'];
  if(isset($HTTP_GET_VARS['ver2'])) $ver2 = $HTTP_GET_VARS['ver2'];
  if(isset($HTTP_GET_VARS['find'])) $find = $HTTP_GET_VARS['find'];
  if(isset($HTTP_GET_VARS['version'])) $version = $HTTP_GET_VARS['version'];
  if(isset($HTTP_GET_VARS['full'])) $full = $HTTP_GET_VARS['full'];
  if(isset($HTTP_GET_VARS['branch_search'])) $branch_search = $HTTP_GET_VARS['branch_search'];
  if(isset($HTTP_GET_VARS['redirect_from'])) $redirect_from = $HTTP_GET_VARS['redirect_from'];
  if(isset($HTTP_GET_VARS['no_redirect'])) $no_redirect = $HTTP_GET_VARS['no_redirect'];
  if(isset($HTTP_GET_VARS['diff_mode'])) $diff_mode = $HTTP_GET_VARS['diff_mode'];

  if(isset($HTTP_POST_VARS['Preview'])) $Preview = $HTTP_POST_VARS['Preview'];
  if(isset($HTTP_POST_VARS['Save'])) $Save = $HTTP_POST_VARS['Save'];
  if(isset($HTTP_POST_VARS['archive'])) $archive = $HTTP_POST_VARS['archive'];
  if(isset($HTTP_POST_VARS['auth'])) $auth = $HTTP_POST_VARS['auth'];
  if(isset($HTTP_POST_VARS['categories'])) $categories = $HTTP_POST_VARS['categories'];
  if(isset($HTTP_POST_VARS['cols'])) $cols = $HTTP_POST_VARS['cols'];
  if(isset($HTTP_POST_VARS['comment'])) $comment = $HTTP_POST_VARS['comment'];
  if(isset($HTTP_POST_VARS['days'])) $days = $HTTP_POST_VARS['days'];
  if(isset($HTTP_POST_VARS['discard'])) $discard = $HTTP_POST_VARS['discard'];
  if(isset($HTTP_POST_VARS['document'])) $document = $HTTP_POST_VARS['document'];
  if(isset($HTTP_POST_VARS['hist'])) $hist = $HTTP_POST_VARS['hist'];
  if(isset($HTTP_POST_VARS['hotpages'])) $hotpages = $HTTP_POST_VARS['hotpages'];
  if(isset($HTTP_POST_VARS['min'])) $min = $HTTP_POST_VARS['min'];
  if(isset($HTTP_POST_VARS['nextver'])) $nextver = $HTTP_POST_VARS['nextver'];
  if(isset($HTTP_POST_VARS['referrer'])) $referrer = $HTTP_POST_VARS['referrer'];
  if(isset($HTTP_POST_VARS['rows'])) $rows = $HTTP_POST_VARS['rows'];
  if(isset($HTTP_POST_VARS['tzoff'])) $tzoff = $HTTP_POST_VARS['tzoff'];
  if(isset($HTTP_POST_VARS['user'])) $user = $HTTP_POST_VARS['user'];
  if(isset($HTTP_POST_VARS['minoredit'])) $minoredit = $HTTP_POST_VARS['minoredit'];
  if(isset($HTTP_POST_VARS['pagefrom'])) $pagefrom = $HTTP_POST_VARS['pagefrom'];
  if(isset($HTTP_POST_VARS['subscribed_pages'])) $subscribed_pages = $HTTP_POST_VARS['subscribed_pages'];
}
require('lib/init.php');
require('parse/transforms.php');

// To add an action=x behavior, add an entry to this array.  First column
//   is the file to load, second is the function to call, and third is how
//   to treat it for rate-checking purposes ('view', 'edit', or 'search').
$ActionList = array(
                'view'    => array('action/view.php', 'action_view', 'view'),
                'edit'    => array('action/edit.php', 'action_edit', 'view'),
                'save'    => array('action/save.php', 'action_save', 'edit'),
                'diff'    => array('action/diff.php', 'action_diff', 'search'),
                'find'    => array('action/find.php', 'action_find', 'search'),
                'history' => array('action/history.php', 'action_history', 'search'),
                'prefs'   => array('action/prefs.php', 'action_prefs', 'view'),
                'macro'   => array('action/macro.php', 'action_macro', 'search'),
                'rss'     => array('action/rss.php', 'action_rss', 'view'),
                'style'   => array('action/style.php', 'action_style', ''),
                'backlinks' => array('action/backlinks.php', 'action_backlinks', 'view'),
                'children' => array('action/children.php', 'action_children', 'view'),
                'reparent' => array('action/reparent.php', 'action_reparent', 'edit'),
                'content' => array('action/content.php', 'action_content', 'edit'),
                'subscribe' => array('action/subscribe.php', 'action_subscribe', 'edit'),
                'subscriptions' => array('action/subscriptions.php', 'action_subscriptions', 'view'),
                'lock'    => array('action/lock.php', 'action_lock', ''),
                'js'      => array('action/js.php', 'action_js', '')
              );

// Default action and page names.
if(isset($q))
  { $action = 'find'; $find = $q; }
if(empty($page) && empty($action))
  { $page = $QUERY_STRING; }
if(empty($action))
  { $action = 'view'; }
if(empty($page))
  { $page = $HomePage; }

// Confirm we have a valid page name.
if(!validate_page($page))
  { die($ErrorInvalidPage); }

// Don't let people do too many things too quickly.
if($ActionList[$action][2] != '')
  { rateCheck($pagestore->dbh, $ActionList[$action][2]); }

// Dispatch the appropriate action.
if(!empty($ActionList[$action]))
{
  include($ActionList[$action][0]);
  $ActionList[$action][1]();
}

// Expire old versions, etc.
$pagestore->maintain();

?>
