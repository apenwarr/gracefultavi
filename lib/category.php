<?php

require('parse/main.php');

// Add a page to a list of categories.
function add_to_category($page, $catlist)
{
    global $Entity, $FlgChr, $NickName, $pagestore, $REMOTE_ADDR, $UserName;

    // Parse the category list for category names.
    $parsed = parseText($catlist, array('parse_wikiname', 'parse_freelink'), '');
    $pagenames = array();
    preg_replace('/' . $FlgChr . '(\\d+)' . $FlgChr . '/e',
                 '$pagenames[]=$Entity[\\1][1]', $parsed);

    if (validate_page($page) == 2)
        { $page = '((' . $page . '))'; }

    // Add it to each category.
    foreach ($pagenames as $category)
    {
        $pg = $pagestore->page($category);

        $pg->read();
        if ($pg->exists)
        {
            if (preg_match ('/\\[\\[!.*\\]\\]/', $pg->text))
            {
                if (!preg_match ("/\\[\\[!.*$page.*\\]\\]/", $pg->text))
                {
                    $pg->text = preg_replace('/(\\[\\[!.*)\\]\\]/',
                                             "\\1 $page]]", $pg->text);
                }
                else
                    { continue; }
            }
            else
                { $pg->text = $pg->text . "\n[[! $page]]\n"; }

            $pg->text = str_replace("\\", "\\\\", $pg->text);
            $pg->text = str_replace("'", "\\'", $pg->text);

            $pg->version++;
            $pg->hostname = gethostbyaddr($REMOTE_ADDR);
            if (!$UserName && $NickName)
            {
                $nick_sql = str_replace("\\", "\\\\", $NickName);
                $nick_sql = str_replace("'", "\\'", $nick_sql);
                $pg->hostname = $nick_sql . '@' . $pg->hostname;
            }
            $pg->username = $UserName;

            $pg->write();
        }
    }
}
?>
