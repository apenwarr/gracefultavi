<?php

require('template/prefs.php');

// View or set a user's preferences.
function action_prefs()
{
  global $CookieName, $days, $ErrorNameMatch, $hist, $hotpages, $min, $nickname;
  global $referrer, $rows, $Save, $tzoff, $user;

  if(!empty($Save))
  {
    if(!empty($user))
    {
      if(!validate_page($user))
        { die($ErrorNameMatch); }
    }

    // make sure the nickname is not a valid username
    if (posix_getpwnam($nickname) !== false) {
        $referrer = '?action=prefs&invalid_nick=' . rawurlencode($nickname) .
                    '&prefs_from=' . rawurlencode($referrer);
        $nickname = '';
    }

    ereg("([[:digit:]]*)", $rows, $result);
    if(($rows = $result[1]) <= 0)
      { $rows = 20; }
    $hotpages = (strcmp($hotpages, "") != 0) ? 1 : 0;
    $value = "rows=$rows&amp;hotpages=$hotpages";
    if(strcmp($nickname, '') != 0)
      { $value .= "&amp;nickname=" . rawurlencode(trim($nickname)); }
    if(strcmp($days, "") != 0)
      { $value = $value . "&amp;days=$days"; }
    if(strcmp($min, "") != 0)
      { $value = $value . "&amp;min=$min"; }
    if(strcmp($hist, "") != 0)
      { $value = $value . "&amp;hist=$hist"; }
    if(strcmp($tzoff, "") != 0)
      { $value = $value . "&amp;tzoff=$tzoff"; }
    setcookie($CookieName, $value, time() + 157680000, "/", "");
    header("Location: $referrer");
  }
  else
    { template_prefs(); }
}
?>
