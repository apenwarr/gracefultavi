<?php
// $Id: save.php,v 1.7 2002/01/07 16:28:32 smoonen Exp $

require('template/save.php');
require('lib/category.php');
require('parse/save.php');

// Commit an edit to the database.
function action_save()
{
    global $pagestore, $comment, $categories, $archive, $quickadd, $appending;
    global $Save, $page, $document, $nextver, $REMOTE_ADDR, $UserName;
    global $MaxPostLen, $UserName, $SaveMacroEngine, $ErrorPageLocked;
    global $minoredit, $pagefrom;

    // added for "Add a Quote" feature for AnnoyingQuote page
    global $appendingQuote, $quoteAuthor;

    if (empty($Save))                   // Didn't click the save button.
    {
        include('action/preview.php');
        action_preview();
        return;
    }

    $pagestore->lock();                 // Ensure atomicity.

    $pg = $pagestore->page($page);
    $pg->read();

    if ($appending)
    {
        $document = $pg->text;
        $nextver = $pg->version + 1;
    }

    if (!$pg->mutable)                  // Edit disallowed.
        die($ErrorPageLocked);

    if ($pg->exists()                   // Page already exists.
        && $pg->version >= $nextver     // Someone has changed it.
        && $pg->hostname != gethostbyaddr($REMOTE_ADDR) // Wasn't us.
        && !$archive)                   // Not editing an archive version.
    {
        $pagestore->unlock();
        include('action/conflict.php');
        action_conflict();
        return;
    }

    // "Add a Comment" is "Add a Quote" for specific pages like AnnoyingQuote
    if ($quickadd && $appendingQuote)
    {
        // if we're appending a quote, instead of a comment
        $quoteAuthor = trim($quoteAuthor);
        if ($quoteAuthor)
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

    if ($appending)
        $document = str_replace("\\\\'", '"', $document);

    $document = str_replace("\\", "\\\\", $document);
    $document = str_replace("'", "\\'", $document);
    $document = str_replace("\r", "", $document);

    $comment = str_replace("\\", "\\\\", $comment);
    $comment = str_replace("'", "\\'", $comment);

    if ($appending && $quickadd)
    {
        // Add new lines if document is not empty.
        if($document) $document = trim($document) . "\n\n";
        $quickadd = str_replace("\r", "", $quickadd);

        $document .= $quickadd;
    }

    $pg->text = $document;
    $pg->hostname = gethostbyaddr($REMOTE_ADDR);
    $pg->username = $UserName;
    $pg->comment  = $comment;

    if ($pg->exists)
        $pg->version++;
    else
        $pg->version = 1;

    $pg->write($minoredit);

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

    // Aligns the browser with an HTML anchor, showing the last added comment (or quote)
    // See: action/save.php, template/save.php, template/view.php
    if ($quickadd)
    {
        // if Add a Comment or Add a Quote
        template_save(array('page' => $page, 'text' => $document, 'anchor' => 'pageContentBottom'));
    }
    else
    {
        // Standard save
        template_save(array('page' => $page, 'text' => $document));
    }

    // Process save macros (e.g., to define interwiki entries).
    parseText($document, $SaveMacroEngine, $page);

    $pagestore->unlock();               // End "transaction".
}
?>
