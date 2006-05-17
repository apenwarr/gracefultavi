<?php
// Users may redefine these functions if they wish to change the
// URL scheme, e.g., to enable links like:
//
//     http://somewiki.org/PageName
//
// The new versions of the relevant functions should be defined in
// config.php.  Those functions that are redefined will not be
// redefined here.

if(!isset($ViewBase))
  { $ViewBase    = $ScriptName . '?page='; }
if(!isset($EditBase))
  { $EditBase    = $ScriptName . '?action=edit&amp;page='; }
if(!isset($HistoryBase))
  { $HistoryBase = $ScriptName . '?action=history&amp;page='; }
if(!isset($FindScript))
  { $FindScript  = $ScriptName . '?action=find'; }
if(!isset($FindBase))
  { $FindBase    = $FindScript . '&amp;find=!'; }
if(!isset($SaveBase))
  { $SaveBase    = $ScriptName . '?action=save&amp;page='; }
if(!isset($DiffScript))
  { $DiffScript  = $ScriptName . '?action=history'; } // '?action=diff'
if(!isset($PrefsScript))
  { $PrefsScript = $ScriptName . '?action=prefs'; }
if(!isset($StyleScript))
  { $StyleScript = $ScriptName . '?action=style'; }
if(!isset($BacklinksBase))
  { $BacklinksBase = $ScriptName . '?action=backlinks&amp;page='; }
if(!isset($ReparentBase))
  { $ReparentBase = $ScriptName . '?action=reparent&amp;page='; }
if(!isset($ContentBase))
  { $ContentBase = $ScriptName . '?action=content&amp;page='; }
if(!isset($PageSubscribeBase))
  { $PageSubscribeBase = $ScriptName . '?action=subscribe&amp;page='; }
if(!isset($SubscriptionsScript))
  { $SubscriptionsScript = $ScriptName . '?action=subscriptions'; }
if(!isset($ChildrenBase))
  { $ChildrenBase = $ScriptName . '?action=children&amp;page='; }
if(!isset($TableSortScript))
  { $TableSortScript = $ScriptName . '?action=js&file=tablesort'; }
if(!isset($CommonScript))
  { $CommonScript = $ScriptName . '?action=js&file=common'; }
if(!isset($RevertScript))
  { $RevertScript = $ScriptName . '?action=revert&page='; }

if(!function_exists('viewURL'))
{
function viewURL($page, $version = '', $full = '')
{
  global $ViewBase;

  return $ViewBase . urlencode($page) .
         ($version == '' ? '' : "&amp;version=$version") .
         ($full == '' ? '' : '&amp;full=1');
}
}

if(!function_exists('viewFullURL'))
{
function viewFullURL($page, $version = '')
{
  global $ScriptBase;

  return $ScriptBase . '?page=' . urlencode($page) .
         ($version == '' ? '' : "&amp;version=$version");
}
}

if(!function_exists('editURL'))
{
function editURL($page, $version = '', $pagefrom = '')
{
  global $EditBase;

  return $EditBase . urlencode($page) .
         ($version == '' ? '' : "&amp;version=$version") .
         ($pagefrom == '' ? '' : "&amp;pagefrom=$pagefrom");
}
}

if(!function_exists('editSectionURL'))
{
function editSectionURL($page, $section)
{
  global $EditBase;

  return $EditBase . urlencode($page) . "&amp;section=$section";
}
}

if(!function_exists('historyURL'))
{
function historyURL($page, $full = '')
{
  global $HistoryBase;

  return $HistoryBase . urlencode($page) .
         ($full == '' ? '' : '&amp;full=1');
}
}

if(!function_exists('findURL'))
{
function findURL($page)
{
  global $FindBase;

  return $FindBase . urlencode($page);
}
}

if(!function_exists('saveURL'))
{
function saveURL($page)
{
  global $SaveBase;

  return $SaveBase . urlencode($page);
}
}

if(!function_exists('backlinksURL'))
{
function backlinksURL($page)
{
  global $BacklinksBase;

  return $BacklinksBase . urlencode($page);
}
}

if(!function_exists('reparentURL'))
{
function reparentURL($page)
{
  global $ReparentBase;

  return $ReparentBase . urlencode($page);
}
}

if(!function_exists('contentURL'))
{
function contentURL($page)
{
  global $ContentBase;

  return $ContentBase . urlencode($page);
}
}

if(!function_exists('pageSubscribeURL'))
{
function pageSubscribeURL($page)
{
  global $PageSubscribeBase;

  return $PageSubscribeBase . urlencode($page);
}
}

if(!function_exists('childrenURL'))
{
function childrenURL($page)
{
  global $ChildrenBase;

  return $ChildrenBase . urlencode($page);
}
}

if(!function_exists('revertURL'))
{
function revertURL($page)
{
  global $RevertScript;

  return $RevertScript . urlencode($page);
}
}

if(!function_exists('tablecsvURL'))
{
function tablecsvURL($page, $tablenum)
{
  global $ScriptName;

  return $ScriptName . '?action=tablecsv&page='.urlencode($page).
         '&tablenum='.$tablenum;
}
}
?>
