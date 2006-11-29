<?php

// The RSS template is passed an associative array with the following
// elements:
//
//   itemseq   => A string containing the rdf:li elements for the syndication.
//   itemdesc  => A string containing the item elements for the syndication.

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

<title><?php print htmlspecialchars($WikiName); ?></title>
<link><?php print htmlspecialchars($ScriptBase); ?></link>
<description><?php print htmlspecialchars($MetaDescription); ?></description>
<style type="text/css">
td.diff-added { background-color: #ccffcc; }
td.diff-removed { background-color: #ffaaaa; }
</style>

<?php print $args['itemdesc']; ?>
</channel>

</rss>
<?php
}
?>
