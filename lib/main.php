<?php

// Database version used by this software version.
// remember to update install/create-db.pl when changing this value
define('DB_VERSION', 4);

// GracefulTavi Version
define('GRACEFULTAVI_VERSION', '1.09');

// If register_globals is off, we need to harvest the script parameters
// at this point.
if (!ini_get('register_globals'))
{
    if (isset($_SERVER['HTTP_REFERER'])) $HTTP_REFERER = $_SERVER['HTTP_REFERER'];
    if (isset($_SERVER['QUERY_STRING'])) $QUERY_STRING = $_SERVER['QUERY_STRING'];
    if (isset($_SERVER['REMOTE_ADDR'])) $REMOTE_ADDR  = $_SERVER['REMOTE_ADDR'];

    if (isset($_GET['action'])) $action = $_GET['action'];
    if (isset($_GET['args'])) $args = $_GET['args'];
    if (isset($_GET['branch_search'])) $branch_search = $_GET['branch_search'];
    if (isset($_GET['csstype'])) $csstype = $_GET['csstype'];
    if (isset($_GET['diff_mode'])) $diff_mode = $_GET['diff_mode'];
    if (isset($_GET['find'])) $find = $_GET['find'];
    if (isset($_GET['full'])) $full = $_GET['full'];
    if (isset($_GET['invalid_nick'])) $invalid_nick = $_GET['invalid_nick'];
    if (isset($_GET['macro'])) $macro = $_GET['macro'];
    if (isset($_GET['md5'])) $md5 = $_GET['md5'];
    if (isset($_GET['no_redirect'])) $no_redirect = $_GET['no_redirect'];
    if (isset($_GET['page'])) $page = $_GET['page'];
    if (isset($_GET['pagefrom'])) $pagefrom = $_GET['pagefrom'];
    if (isset($_GET['prefs_from'])) $prefs_from = $_GET['prefs_from'];
    if (isset($_GET['q'])) $q = $_GET['q'];
    if (isset($_GET['redirect_from'])) $redirect_from = $_GET['redirect_from'];
    if (isset($_GET['section'])) $section = $_GET['section'];
    if (isset($_GET['tablenum'])) $tablenum = $_GET['tablenum'];
    if (isset($_GET['use_template'])) $use_template = $_GET['use_template'];
    if (isset($_GET['ver1'])) $ver1 = $_GET['ver1'];
    if (isset($_GET['ver2'])) $ver2 = $_GET['ver2'];
    if (isset($_GET['version'])) $version = $_GET['version'];
    if (isset($_GET['view_source'])) $view_source = $_GET['view_source'];

    if (isset($_POST['archive'])) $archive = $_POST['archive'];
    if (isset($_POST['captcha'])) $captcha = $_POST['captcha'];
    if (isset($_POST['categories'])) $categories = $_POST['categories'];
    if (isset($_POST['comment'])) $comment = $_POST['comment'];
    if (isset($_POST['days'])) $days = $_POST['days'];
    if (isset($_POST['diff_mode'])) $diff_mode = $_POST['diff_mode'];
    if (isset($_POST['discard'])) $discard = $_POST['discard'];
    if (isset($_POST['document'])) $document = $_POST['document'];
    if (isset($_POST['hist'])) $hist = $_POST['hist'];
    if (isset($_POST['hotpages'])) $hotpages = $_POST['hotpages'];
    if (isset($_POST['min'])) $min = $_POST['min'];
    if (isset($_POST['minoredit'])) $minoredit = $_POST['minoredit'];
    if (isset($_POST['nextver'])) $nextver = $_POST['nextver'];
    if (isset($_POST['nickname'])) $nickname = $_POST['nickname'];
    if (isset($_POST['pagefrom'])) $pagefrom = $_POST['pagefrom'];
    if (isset($_POST['Preview'])) $Preview = $_POST['Preview'];
    if (isset($_POST['referrer'])) $referrer = $_POST['referrer'];
    if (isset($_POST['rows'])) $rows = $_POST['rows'];
    if (isset($_POST['Save'])) $Save = $_POST['Save'];
    if (isset($_POST['section'])) $section = $_POST['section'];
    if (isset($_POST['subscribed_pages'])) $subscribed_pages = $_POST['subscribed_pages'];
    if (isset($_POST['template'])) $template = $_POST['template'];
    if (isset($_POST['text_after'])) $text_after = $_POST['text_after'];
    if (isset($_POST['text_before'])) $text_before = $_POST['text_before'];
    if (isset($_POST['tzoff'])) $tzoff = $_POST['tzoff'];
    if (isset($_POST['validationcode'])) $validationcode = $_POST['validationcode'];
}
require('lib/init.php');
require('parse/transforms.php');

// Make sure the database version is up to date
require('lib/dbupgrade.php');
check_db_version(DB_VERSION);

// To add an action=x behavior, add an entry to this array. First column is the
// file to load, second is the function to call, and third is how to treat it
// for rate-checking purposes ('view', 'edit', or 'search').
$ActionList = array(
    'view'          => array('action/view.php', 'action_view', 'view'),
    'edit'          => array('action/edit.php', 'action_edit', 'view'),
    'save'          => array('action/save.php', 'action_save', 'edit'),
    'diff'          => array('action/diff.php', 'action_diff', 'search'),
    'find'          => array('action/find.php', 'action_find', 'search'),
    'history'       => array('action/history.php', 'action_history', 'search'),
    'revert'        => array('action/revert.php', 'action_revert', 'edit'),
    'prefs'         => array('action/prefs.php', 'action_prefs', 'view'),
    'macro'         => array('action/macro.php', 'action_macro', 'search'),
    'rss'           => array('action/rss.php', 'action_rss', 'view'),
    'style'         => array('action/style.php', 'action_style', ''),
    'backlinks'     => array('action/backlinks.php', 'action_backlinks', 'view'),
    'children'      => array('action/children.php', 'action_children', 'view'),
    'reparent'      => array('action/reparent.php', 'action_reparent', 'edit'),
    'content'       => array('action/content.php', 'action_content', 'edit'),
    'subscribe'     => array('action/subscribe.php', 'action_subscribe', 'edit'),
    'subscriptions' => array('action/subscriptions.php', 'action_subscriptions', 'view'),
    'imgbar'        => array('action/imgbar.php', 'action_imgbar', ''),
    'lock'          => array('action/lock.php', 'action_lock', ''),
    'js'            => array('action/js.php', 'action_js', ''),
    'tablecsv'      => array('action/tablecsv.php', 'action_tablecsv', 'view'),
    'captcha'       => array('action/captcha.php', 'action_captcha', 'view')
);

// Default action and page names.
if (isset($q)) {
    $action = 'find';
    $find = $q;
}
if (($action == 'find') && ($find == '%s')) {
    // small hack for browser bookmarklets
    $action = 'view';
    $page = 'RecentChanges';
}
if (empty($page) && empty($action)) {
    $page = $QUERY_STRING;
}
if (empty($action)) {
    $action = 'view';
}
if (empty($page)) {
    $page = $HomePage;
}

// Confirm we have a valid page name.
if (!validate_page($page)) {
    die($ErrorInvalidPage);
}

// Don't let people do too many things too quickly.
if ($ActionList[$action][2] != '') {
    rateCheck($pagestore->dbh, $ActionList[$action][2]);
}

// Dispatch the appropriate action.
if (!empty($ActionList[$action])) {
    include($ActionList[$action][0]);
    $ActionList[$action][1]();
}

// Expire old versions, etc.
$pagestore->maintain();
?>
