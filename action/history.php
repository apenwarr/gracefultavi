<?php

require_once('parse/main.php');
require_once('parse/macros.php');
require_once('parse/html.php');
require_once('lib/diff.php');
require('template/history.php');
require('lib/headers.php');

function do_diff($body1, $body2)
{
    global $diff_mode, $DiffModeCookieName, $EnableWordDiff, $HTTP_COOKIE_VARS;

    if ($EnableWordDiff)
    {
        if (!isset($diff_mode))
            if (isset($HTTP_COOKIE_VARS[$DiffModeCookieName]))
                $diff_mode = $HTTP_COOKIE_VARS[$DiffModeCookieName];
            else
                $diff_mode = 0;
        if (!isset($HTTP_COOKIE_VARS[$DiffModeCookieName]) ||
            $diff_mode != $HTTP_COOKIE_VARS[$DiffModeCookieName])
            setcookie($DiffModeCookieName, $diff_mode, time() + 157680000, "/",
                      false);
    }
    else
        $diff_mode = 0;

    // diff mode: 0 = regular diff, 1 = word diff
    if ($diff_mode == 1)
    {
        $diff = wdiff_compute($body1, $body2);
        $diff = wdiff_parse($diff);
    }
    else
    {
        $diff = diff_compute($body1, $body2);
        $diff = diff_parse($diff);
    }

    return $diff;
}

// Display the known history of a page's edits.
function action_history()
{
    global $diff_mode, $full, $HistMax, $page, $pagestore, $UserName, $ver1;
    global $ver2;

    $history = $pagestore->history($page);

    gen_headers($history[0][0]);

    $versions = diff_get_history_versions($history, $ver1, $ver2);
    $latest_ver = $versions['latest_ver'];
    $previous_ver = $versions['previous_ver'];

    $text = '';

    for($i = 0; $i < count($history); $i++)
    {
        if($i < $HistMax || $full)
            $text = $text . html_history_entry($page, $history[$i][2],
                                               $history[$i][0], $history[$i][1],
                                               $history[$i][3],
                                               $previous_ver == $history[$i][2],
                                               $latest_ver == $history[$i][2],
                                               $history[$i][4]);
    }

    if($i >= $HistMax && !$full)
        $text = $text . html_fullhistory($page, count($history));

    $p1 = $pagestore->page($page);
    $p1->version = $previous_ver;
    $p2 = $pagestore->page($page);
    $p2->version = $latest_ver;

    $diff = do_diff($p1->read(), $p2->read());

    template_history(array(
        'page'      => $page,
        'history'   => $text,
        'diff'      => $diff,
        'editver'   => ($UserName && $p2->mutable) ? 0 : -1,
        'timestamp' => $p2->time,
        'edituser'  => $p2->username,
        'diff_mode' => $diff_mode
    ));
}
?>
