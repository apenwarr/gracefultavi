<?php
// $Id: admin.php,v 1.1 2002/01/07 18:13:58 smoonen Exp $

require('lib/init.php');
require('parse/html.php');
require('parse/transforms.php');
require('template/admin.php');

if($AdminEnabled != 1)
  { die($ErrorAdminDisabled); }

// If register_globals is off, we need to harvest the script parameters
// at this point.

if(!ini_get('register_globals'))
{
  $REMOTE_ADDR  = $HTTP_SERVER_VARS['REMOTE_ADDR'];

  $locking      = $HTTP_GET_VARS['locking'];
  $blocking     = $HTTP_GET_VARS['blocking'];

  $Block        = $HTTP_POST_VARS['Block'];
  $Unblock      = $HTTP_POST_VARS['Unblock'];
  $Save         = $HTTP_POST_VARS['Save'];
  $address      = $HTTP_POST_VARS['address'];
  if(!isset($blocking))
    { $blocking     = $HTTP_POST_VARS['blocking']; }
  if(!isset($locking))
    { $locking      = $HTTP_POST_VARS['locking']; }
  $count        = $HTTP_POST_VARS['count'];

  if($locking && $count > 0)
  {
    for($i = 1; $i <= $count; $i++)
    {
      $var = 'name' + $i;
      $$var = $HTTP_POST_VARS[$var];
      $var = 'lock' + $i;
      $$var = $HTTP_POST_VARS[$var];
    }
  }
}

if($locking)                            // Locking/unlocking pages.
{
  if(empty($Save))                      // Not saving results; display form.
  {
    $html = html_lock_start();
    $pagelist = $pagestore->allpages();
    foreach($pagelist as $page)
      { $html = $html . html_lock_page($page[1], $page[6]); }
    template_admin(array('html' => $html . html_lock_end(count($pagelist))));
  }
  else                                  // Lock/unlock pages at admin's request.
  {
    $pagestore->lock();                 // Exclusive access to database.
    for($i = 1; $i <= $count; $i++)
    {
      $page = urldecode($HTTP_POST_VARS['name' . $i]);
      $lock = ($HTTP_POST_VARS['lock' . $i] == '1');
      $pg = $pagestore->page($page);
      $pg->read();
      $pg->version++;
      $pg->hostname = gethostbyaddr($REMOTE_ADDR);
      $pg->username = $UserName;
      $pg->comment = '';
      $pg->text = str_replace('\\', '\\\\', $pg->text);
      $pg->text = str_replace('\'', '\\\'', $pg->text);
      if($pg->exists && $pg->mutable && $lock)
      {
        $pg->mutable = 0;
        $pg->write();
      }
      else if($pg->exists && !$pg->mutable && !$lock)
      {
        $pg->mutable = 1;
        $pg->write();
      }
    }

    $pagestore->unlock();
    header('Location: ' . $AdminScript);
  }
}
else if($blocking)                      // Blocking/unblocking IP addrs.
{
  if(empty($Block) && empty($Unblock))  // Not saving results; display form.
  {
    $html = '';
    if($RatePeriod == 0)
    {
      $html = $html . html_bold_start() .
              'Rate control / IP blocking disabled' .
              html_bold_end() . html_newline();
    }

    $html = $html . html_rate_start();
    $blocklist = rateBlockList($pagestore->dbh);
    foreach($blocklist as $block)
      { $html = $html . html_rate_entry($block); }
    $html = $html . html_rate_end();

    template_admin(array('html' => $html));
  }
  else                                  // Block/unblock an address group.
  {
    if(!empty($Block))
      { rateBlockAdd($pagestore->dbh, $address); }
    else if(!empty($Unblock))
      { rateBlockRemove($pagestore->dbh, $address); }

    header('Location: ' . $AdminScript);
  }
}
else                                    // Display main menu for admin.
{
  template_admin(array('html' => html_url($AdminScript . '?locking=1',
                                          'Lock / unlock pages') .
                                 html_newline() .
                                 html_url($AdminScript . '?blocking=1',
                                          'Block / unblock hosts') .
                                 html_newline()));
}

?>
