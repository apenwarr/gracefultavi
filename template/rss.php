<?php

// The RSS template is passed an associative array with the following
// elements:
//
//   itemdesc  => A string containing the item elements for the syndication.
//   page      => A string containing the wiki page name.

function template_rss($args)
{
  global $Charset, $MetaDescription, $ScriptBase, $WikiName;

  header('Content-type: text/xml');
?>
<?php print '<?xml '; ?>version="1.0" encoding="<?php print $Charset; ?>"?>

<rss version="2.0">

<!--
Add a "days=nnn" URL parameter to get nnn days of information
(the default is 2).  Use days=-1 to show entire history.
Add a "min=nnn" URL parameter to force a minimum of nnn entries
in the output (the default is 10).
-->

<channel>

<title><?php print htmlspecialchars($WikiName); ?> - <?php print htmlspecialchars($args['page']); ?></title>
<link><?php print htmlspecialchars($ScriptBase); ?></link>
<description><?php print htmlspecialchars($MetaDescription); ?></description>

<?php print $args['itemdesc']; ?>
</channel>

</rss>
<?php
}
?>
