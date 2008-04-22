<?php

function static_cache_headers()
{
    $dtformat = "D, d M Y H:i:s";
    header("Last-modified: " . gmdate($dtformat, time()));
    header("Expires: " . gmdate($dtformat, time()+3600*24));
    header("Cache-control: public, max-age=" . 3600*24);
}

// Generate headers saying not to assume this page won't change.
//
// The page's last-edited time stamp is currently not used. This caused problems
// with certain browser settings; especially with pages containing category
// lists, like RecentChanges. Although the page itself didn't change, the
// category list did.

function gen_headers($timestamp)
{
  /*
  $time = strtotime($timestamp);
  $mod = gmdate("D, d M Y H:i:s", $time);
  */

  $now = gmdate("D, d M Y H:i:s");

  header("Expires: $now GMT");
  header("Last-Modified: $now GMT");
  /*
  header("Cache-Control: no-cache, must-revalidate");
  header("Pragma: no-cache");
  */
}
?>
