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
    global $document, $redirect_from;

    $pg = $pagestore->page($page);

    if (file_exists("modules/" . $page . ".php"))
    {
        require_once("modules/" . $page . ".php");

        if (function_exists($page . "_content"))
        {
            eval("\$pg->text=" . $page . "_content();");
        }
        $pg->mutable = 0;
    }
    else
    {
        if($version != '')
            $pg->version = $version;
        $pg->read();
    }

    $document = $pg->text;

    gen_headers($pg->time);

    template_view(array('page'      => $page,
                        'html'      => parseText($pg->text, $ParseEngine, $page),
                        'editable'  => $UserName && $pg->mutable,
                        'timestamp' => $pg->time,
                        'archive'   => $version != '',
                        'version'   => $pg->version,
                        'edituser'  => $pg->username,
                        'redirect_from'  => $redirect_from));
}
?>
