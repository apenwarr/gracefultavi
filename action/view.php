<?php
// $Id: view.php,v 1.7 2002/01/07 16:28:32 smoonen Exp $

require('parse/main.php');
require('parse/macros.php');
require('parse/html.php');
require('template/view.php'); // require(TemplateDir . '/view.php');
require('lib/headers.php');

// Parse and display a page.
function action_view()
{
    global $page, $pagestore, $ParseEngine, $version, $UserName;

    $pg = $pagestore->page($page);
    if($version != '')
        $pg->version = $version;
    $pg->read();

    // Saves user's access to the page if it's marked to be watched for that
    // user.
    if($pg->isWatched($UserName))
        $pg->updateAccessTime($UserName);

    gen_headers($pg->time);

    template_view(array('page'      => $page,
                        'html'      => parseText($pg->text, $ParseEngine, $page),
                        'editable'  => $pg->mutable,
                        'timestamp' => $pg->time,
                        'archive'   => $version != '',
                        'version'   => $pg->version,
                        'edituser'  => $pg->username));
}
?>
