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
  $HTTP_REFERER = $HTTP_SERVER_VARS['HTTP_REFERER'];
  $QUERY_STRING = $HTTP_SERVER_VARS['QUERY_STRING'];
  $REMOTE_ADDR  = $HTTP_SERVER_VARS['REMOTE_ADDR'];

  $action       = $HTTP_GET_VARS['action'];
  $page         = $HTTP_GET_VARS['page'];
  $ver1         = $HTTP_GET_VARS['ver1'];
  $ver2         = $HTTP_GET_VARS['ver2'];
  $find         = $HTTP_GET_VARS['find'];
  $version      = $HTTP_GET_VARS['version'];
  $full         = $HTTP_GET_VARS['full'];
  $branch_search= $HTTP_GET_VARS['branch_search'];

  $Preview      = $HTTP_POST_VARS['Preview'];
  $Save         = $HTTP_POST_VARS['Save'];
  $archive      = $HTTP_POST_VARS['archive'];
  $auth         = $HTTP_POST_VARS['auth'];
  $categories   = $HTTP_POST_VARS['categories'];
  $cols         = $HTTP_POST_VARS['cols'];
  $comment      = $HTTP_POST_VARS['comment'];
  $days         = $HTTP_POST_VARS['days'];
  $discard      = $HTTP_POST_VARS['discard'];
  $document     = $HTTP_POST_VARS['document'];
  $hist         = $HTTP_POST_VARS['hist'];
  $min          = $HTTP_POST_VARS['min'];
  $nextver      = $HTTP_POST_VARS['nextver'];
  $rows         = $HTTP_POST_VARS['rows'];
  $tzoff        = $HTTP_POST_VARS['tzoff'];
  $user         = $HTTP_POST_VARS['user'];
  $minoredit    = $HTTP_POST_VARS['minoredit'];
  $pagefrom     = $HTTP_POST_VARS['pagefrom'];
}
require('lib/init.php');
require('parse/transforms.php');

// To add an action=x behavior, add an entry to this array.  First column
//   is the file to load, second is the function to call, and third is how
//   to treat it for rate-checking purposes ('view', 'edit', or 'search').
$ActionList = array(
                'view' => array('action/view.php', 'action_view', 'view'),
                'edit' => array('action/edit.php', 'action_edit', 'view'),
                'save' => array('action/save.php', 'action_save', 'edit'),
                'diff' => array('action/diff.php', 'action_diff', 'search'),
                'find' => array('action/find.php', 'action_find', 'search'),
                'history' => array('action/history.php', 'action_history', 'search'),
                'prefs'   => array('action/prefs.php', 'action_prefs', 'view'),
                'macro'   => array('action/macro.php', 'action_macro', 'search'),
                'rss'     => array('action/rss.php', 'action_rss', 'view'),
                'style'   => array('action/style.php', 'action_style', ''),
                'backlinks' => array('action/backlinks.php', 'action_backlinks', 'view'),
                'reparent'  => array('action/reparent.php', 'action_reparent', 'edit'),
                'content' => array('action/content.php', 'action_content', 'edit'),
                'watch'   => array('action/watch.php', 'action_watch', 'edit'),
                'lock'    => array('action/lock.php', 'action_lock', '')
              );

// Default action and page names.
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


/*
if ($UserName == 'mich' && $page == 'mich')
{
    $pages = $pagestore->getAllPageNames();

    foreach ($pages as $page)
    {
        $backlinks = $pagestore->getBacklinks($page);
        $parents = $pagestore->getParents($page);

        if ($diff = array_diff($parents, $backlinks))
        {
           print html_ref($page, $page) . ": ";
           foreach ($diff as $i)
               print "$i ";
           print "<br>";
        }
    }
}
*/
?>
