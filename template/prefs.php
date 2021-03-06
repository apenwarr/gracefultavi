<?php

require('parse/html.php');
require_once('template/common.php');

function template_prefs()
{
    global $DayLimit, $EditRows, $EmailSuffix, $EnableSubscriptions, $HistMax;
    global $HTTP_REFERER, $invalid_nick, $MinEntries, $NickName, $prefs_from;
    global $PrefsScript, $SubscriptionsScript, $TimeZoneOff, $UseHotPages;
    global $UserName;

    template_common_prologue(array(
        'norobots' => 1,
        'title'    => 'UserOptions',
        'heading'  => 'UserOptions',
        'headlink' => '',
        'headsufx' => '',
        'toolbar'  => 0,

        'button_selected'  => '',
        'button_view'      => 0,
        #'timestamp'       => $args['timestamp']  no diff
        #'editver'         => $args['editver']  no edit
        'button_backlinks' => 0
    ));

    $referrer = ($prefs_from ? $prefs_from : $HTTP_REFERER);

?>

<div class="content">
<form action="<?php print $PrefsScript; ?>" method="post">
<div class="form">
  <input type="hidden" name="referrer" value="<?=htmlspecialchars($referrer)?>">

<?php if ($UserName) : ?>
  <strong>You are currently logged in as
  <?php print $UserName; ?></strong><br><br>
  Your login name will appear on the RecentChanges page to the right of
  pages you edit.
<?php else : ?>
  <strong>You are not currently logged in.</strong><br><br>
  If you wish, you may provide a nickname to use on this wiki, it will be used
  when adding comments and will appear on the RecentChanges page.<br><br>
  <?php if ($invalid_nick) : ?>
    <span style="color:red;"><b>WARNING:</b></span>
    Invalid nickname: "<b><?=htmlspecialchars($invalid_nick)?></b>". The
    nickname you provided can not be used since it is already a valid username.
    Please choose another one.<br><br>
  <?php endif; ?>
  Nickname:
  <input type="text" name="nickname" value="<?=htmlspecialchars($NickName)?>">
<?php endif; ?>
  <br><br>
  <hr>

<?php if ($EnableSubscriptions && isset($EmailSuffix) && $UserName != '') : ?>
  <strong>Subscriptions</strong><br /><br />
  <input type="button" value="Manage subscriptions"
    onClick="location='<?php print $SubscriptionsScript; ?>'"><br /><br />
  <hr />
<?php endif; ?>

  <strong>Edit box</strong><br /><br />
  Rows: <input type="text" name="rows" value="<?php print $EditRows; ?>" /><br /><br />
  <hr />

  <strong>History lists</strong><br /><br />
  Enter here the maximum number of entries to display in a document's history
  list.<br /><br />
  <input type="text" name="hist" value="<?php print $HistMax; ?>" /><br /><br />
  <hr />

  <strong>RecentChanges</strong><br /><br />
  Choose your current time here, so the server may figure out what time zone
  you are in.<br /><br />
  <select name="tzoff">
<?php
  for($i = -23.5 * 60; $i <= 23.5 * 60; $i += 30)
  {
?>
<option value="<?php print $i; ?>"<?php if($i == $TimeZoneOff) { print ' selected="selected"'; } ?>><?php
    print date('Y-m-d H:i', time() + $i * 60);
?></option>
<?php
  }
?>
  </select><br /><br />
  Enter here the number of days of edits to display on RecentChanges or any
  other subscription list.  Set this to zero if you wish to see all pages in
  RecentChanges, regardless of how recently they were edited.<br /><br />
  <input type="text" name="days" value="<?php print $DayLimit; ?>" /><br /><br />
  <em>But</em> display at least this many entries in RecentChanges and other
  subscription lists:<br /><br />
  <input type="text" name="min" value="<?php print $MinEntries; ?>" /><br /><br />

  <input type="checkbox" name="hotpages"<?php
    if($UseHotPages) { print ' checked="checked"'; } ?> />
  Use the Hot Pages flag to indicate pages modified at least 5 times during the
  last week. This ignores minor edits and multiple subsequent updates by the same
  user. Note that this might slow down access to the RecentChanges page.<br /><br />

  <hr /><br />
  <input type="submit" name="Save" value="Save" />
</div>
</form>
</div>
<?php
    template_common_epilogue(array(
        'nosearch' => 1,

        'headlink'         => '',
        'button_selected'  => '',
        'button_view'      => 0,
        #'timestamp'       => $args['timestamp']  no diff
        #'editver'         => $args['editver']  no edit
        'button_backlinks' => 0
    ));
}
?>
