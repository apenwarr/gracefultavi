<?php

require('template/save.php');
require('template/spamconfirm.php');
require('lib/category.php');
require('lib/diff.php');
require('parse/save.php');

// Commit an edit to the database.
function action_save()
{
    global $Admin, $archive, $categories, $comment, $document, $Diff3Cmd;
    global $EnableDiff3, $EnableSubscriptions, $EmailSuffix, $ErrorPageLocked;
    global $HTTP_POST_VARS, $MaxPostLen, $minoredit, $nextver, $NickName, $page;
    global $pagefrom, $pagestore, $REMOTE_ADDR, $Save, $SaveMacroEngine;
    global $section, $template, $text_before, $text_after, $UserName;
    global $WorkingDirectory;

    if(isset($HTTP_POST_VARS['quickadd'])) $quickadd = $HTTP_POST_VARS['quickadd'];
    if(isset($HTTP_POST_VARS['appending'])) $appending = $HTTP_POST_VARS['appending'];

    // added for "Add a Quote" feature for AnnoyingQuote page
    if(isset($HTTP_POST_VARS['quoteAuthor'])) $quoteAuthor = $HTTP_POST_VARS['quoteAuthor'];
    if(isset($HTTP_POST_VARS['appendingQuote'])) $appendingQuote = $HTTP_POST_VARS['appendingQuote'];

    if (get_magic_quotes_gpc())
    {
        if (isset($quickadd)) { $quickadd = stripslashes($quickadd); }
        if (isset($quoteAuthor)) { $quoteAuthor = stripslashes($quoteAuthor); }
    }

    // added for spam detection
    if(isset($HTTP_POST_VARS['isnotspam'])) $isnotspam = $HTTP_POST_VARS['isnotspam'];

    // spam detection
    if (!$UserName && isset($appending))
    {
        $spam_level = look_like_spam($quickadd);
        if ($spam_level > 1 || ($spam_level == 1 && !isset($isnotspam)))
        {
            template_spamconfirm(array('page'           => $page,
                                       'quickadd'       => $quickadd,
                                       'comment'        => $comment,
                                       'appendingQuote' => $appendingQuote,
                                       'quoteAuthor'    => $quoteAuthor,
                                       'spam_level'     => $spam_level));
            exit;
        }
    }

    if (empty($Save))                   // Didn't click the save button.
    {
        include('action/preview.php');
        action_preview();
        return;
    }

    $pagestore->lock();                 // Ensure atomicity.

    $pg = $pagestore->page($page);
    $pg->read();

    if (isset($appending))
    {
        $document = $pg->text;
        $nextver = $pg->version + 1;
    }
    else
    {
        if ($section)
        {
            $document = $text_before."\n\n".trim($document)."\n\n".$text_after;
        }
        $pg->template = $template;
    }

    // Edit disallowed.
    if (!$pg->mutable || (!$UserName && !isset($appending))) {
        $pagestore->unlock();
        die($ErrorPageLocked);
    }

    // gets the hostname without the nickname
    $page_hostname = array_pop(explode('@', $pg->hostname));

    if ($pg->exists()                   // Page already exists.
        && $pg->version >= $nextver     // Someone has changed it.
        && $page_hostname != gethostbyaddr($REMOTE_ADDR) // Wasn't us.
        && !$archive)                   // Not editing an archive version.
    {
        $merge_conflict = 1;

        $diff3_test = array();
        if ($EnableDiff3)
        {
            exec($Diff3Cmd . ' --help', $diff3_test);
        }
        if (count($diff3_test))
        {
            // page conflict, try to merge
            $your_file_text = $pg->text;
            $my_file_text = str_replace("\r", "", $document);
            $pg_old = $pagestore->page($page);
            $pg_old->version = $nextver-1;
            $pg_old->read();
            $old_file_text = $pg_old->text;

            global $TempDir;
            $num = posix_getpid();  // Comment if running on Windows.
            // $num = rand();       // Uncomment if running on Windows.
            $my_file = $TempDir . '/wiki_' . $num . '_my_file.txt';
            $old_file = $TempDir . '/wiki_' . $num . '_old_file.txt';
            $your_file = $TempDir . '/wiki_' . $num . '_your_file.txt';
            if (!($h_my = fopen($my_file, 'w')) ||
                !($h_old = fopen($old_file, 'w')) ||
                !($h_your = fopen($your_file, 'w')))
                { die("ErrorCreatingTemp"); }
            if (fwrite($h_my, $my_file_text) < 0 ||
                fwrite($h_old, $old_file_text) < 0 ||
                fwrite($h_your, $your_file_text) < 0)
                { die("ErrorWritingTemp"); }
            fclose($h_my);
            fclose($h_old);
            fclose($h_your);

            exec($Diff3Cmd.' -m '.$my_file.' '.$old_file.' '.$your_file, $merge);

            unlink($my_file);
            unlink($old_file);
            unlink($your_file);

            $merge = implode("\n", $merge);
            $regexp = '/[<\|>]{7} '.
                preg_quote($TempDir.'/wiki_'.$num.'_', '/').'/s';
            $merge_conflict = preg_match($regexp, $merge);
        }

        if ($merge_conflict)
        {
            $pagestore->unlock();
            include('action/conflict.php');
            action_conflict();
            return;
        }
        else
        {
            $document = $merge;
        }
    }

    // "Add a Comment" is "Add a Quote" for specific pages like AnnoyingQuote
    if (isset($quickadd) && isset($appendingQuote))
    {
        // if we're appending a quote, instead of a comment
        $quoteAuthor = trim($quoteAuthor);
        if ($quoteAuthor != '')
        {
            // Add author to quote if author provided. Add a leading dash if
            // needed. See strpos help for information about "=== false".
            $pos = strpos($quoteAuthor, '-');
            if ($pos === false || $pos > 0)
                { $quoteAuthor = "-- $quoteAuthor"; }

            $quickadd .= " $quoteAuthor";
        }
    }

    // Silently trim string to $MaxPostLen chars.
    $document = substr($document, 0, $MaxPostLen);

    if (isset($appending))
        $document = str_replace("\\\\'", '"', $document);

    $document = str_replace("\\", "\\\\", $document);
    $document = str_replace("'", "\\'", $document);
    $document = str_replace("\r", "", $document);

    $comment = str_replace("\\", "\\\\", $comment);
    $comment = str_replace("'", "\\'", $comment);

    if (isset($appending) && isset($quickadd))
    {
        // Add new lines if document is not empty.
        if($document) $document = trim($document) . "\n\n";
        $quickadd = str_replace("\\", "\\\\", $quickadd);
        $quickadd = str_replace("'", "\\'", $quickadd);
        $quickadd = str_replace("\r", "", $quickadd);

        $document .= $quickadd;
    }

    $pg->text = $document;
    $pg->hostname = gethostbyaddr($REMOTE_ADDR);
    if (!$UserName && $NickName)
    {
        $nick_sql = str_replace("\\", "\\\\", $NickName);
        $nick_sql = str_replace("'", "\\'", $nick_sql);
        $pg->hostname = $nick_sql . '@' . $pg->hostname;
    }
    $pg->username = $UserName;
    $pg->comment  = $comment;

    if ($pg->exists)
        $pg->version++;
    else
        $pg->version = 1;

    if (!$pg->write($minoredit)) {
        $pagestore->unlock();
        die("Error saving a page.");
    }

    // Parenting stuff for new pages.
    if ($pg->version == 1)
    {
        // Ensures that $pagefrom is really a backlink.
        if ($pagefrom)
        {
            $backlinks = $pagestore->getBacklinks($page);
            if (!in_array($pagefrom, $backlinks))
                $pagefrom = '';
        }

        // If no parent is specified, tries to find one.
        if (!$pagefrom) { $pagefrom = $pagestore->findFosterParent($page); }

        // The $pagefrom page becomes the new page's parent.
        if ($pagefrom)
        {
            $tempPage = $pagestore->page($pagefrom);
            if($tempPage->exists())
                $pagestore->reparent($page, $pagefrom);
        }
    }

    // Editor asked page to be added to a category or categories.
    if (!empty($categories))
        add_to_category($page, $categories);

    // Process save macros (e.g., to define interwiki entries).
    parseText($document, $SaveMacroEngine, $page);

    // Remove any parenting if the page is empty.
    if (strlen(trim($document)) == 0) {
        $pagestore->reparent_emptypage($page);
    }

    $pagestore->unlock();               // End "transaction".

    // Handles page subscriptions in background
    if ($EnableSubscriptions && isset($EmailSuffix)) {
        if ($subscribed_users = $pg->getSubscribedUsers($UserName)) {
            global $ScriptBase, $WikiName;
            foreach ($subscribed_users as $user) {
                $msg = "This is your friendly neighbourhood wiki ($WikiName) " .
                       "letting you know that the page $page has changed!\n\n";
                if ($minoredit) { $msg .= "This was a minor edit.\n\n"; }
                $msg .= "View page: $ScriptBase?$page\n\n" .
                        "History: $ScriptBase?action=history&page=$page\n\n";

                // friendly diff
                $history = $pagestore->history($page);
                if (count($history) > 1) {
                    $previous_ver = $history[1][2];
                    $latest_ver = $history[0][2];

                    $p1 = $pagestore->page($page);
                    $p1->version = $previous_ver;

                    $p2 = $pagestore->page($page);
                    $p2->version = $latest_ver;

                    $diff = diff_compute($p1->read(), $p2->read());
                    $msg .= "Friendly diff:\n\n$diff";
                }

                mail($user . $EmailSuffix, "$WikiName: $page has changed",
                     $msg, "From: $Admin");
            }
        }
    }

    // Aligns the browser with an HTML anchor, showing the last added comment (or quote)
    // See: action/save.php, template/save.php, template/view.php
    if (isset($quickadd))
    {
        // if Add a Comment or Add a Quote
        template_save(array('page' => $page, 'text' => $document, 'anchor' => 'pageContentBottom'));
    }
    else
    {
        // Standard save
        template_save(array('page' => $page, 'text' => $document));
    }
}
?>
