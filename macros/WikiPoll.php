<?php

/**
 * Wiki Poll
 *
 * Authors: Konstantin Ryabitsev
 *          Michel Emond
 *
 * Provides the feature of having polls in the wiki. Multiple polls on a single
 * wiki page are supported. Unlogged visitors are given the results but can't
 * vote.
 *
 * Syntax:
 *
 *   [[WikiPoll [poll_type] poll_title (choice_1[|choice_2[|choice_3...]])]]
 *
 *   Where poll_type is either SINGLE (radio) or MULTIPLE (checkbox); defaults
 *   to SINGLE if missing.
 *
 * Examples:
 *
 *   [[WikiPoll What is your favourite animal? (cat|dog|turtle)]]
 *
 *   [[WikiPoll MULTIPLE What are your favourite colors? (red|green|blue|pink)]]
 *
 */

/*

Table initialization:

CREATE TABLE <DATABASE_TABLE_PREFIX>poll (
  id      int(10) unsigned NOT NULL auto_increment,
  title   varchar(200) NOT NULL default '',
  author  varchar(80) NOT NULL default '',
  choice  varchar(200) NOT NULL default '',
  PRIMARY KEY  (id)
);

*/


define('POLL_SINGLE', 0);
define('POLL_MULTIPLE', 1);


class Macro_WikiPoll
{
    var $pagestore;
    var $barwidth = 200;
    var $barheight = 20;

    function Macro_WikiPoll()
    {
        global $DBTablePrefix;
        $this->tblname = $DBTablePrefix . 'poll';
    }

    function parse($args, $page)
    {
        global $HTTP_COOKIE_VARS, $pagestore, $UserName;

        $poll_user = addslashes($UserName);

        // is a ballot being cast?
        if ($_POST['poll_vote']) {

            if ($poll_user && $_POST['poll_choice']) {
                $vote_title = $this->quote($_POST['poll_title']);

                // check if they have already voted.
                $q1 = $pagestore->dbh->query("SELECT id " .
                                             "FROM ".$this->tblname." " .
                                             "WHERE title='$vote_title' " .
                                             "AND author='$poll_user'");
                $results = $pagestore->dbh->result($q1);

                if (!$results) {
                    foreach ($_POST['poll_choice'] as $vote_choice) {
                        $vote_choice = $this->quote($vote_choice);
                        $query = "INSERT INTO ".$this->tblname." " .
                                 "(title, author, choice) " .
                                 "VALUES ('$vote_title', '$poll_user', " .
                                 "'$vote_choice')";
                        $pagestore->dbh->query($query);
                    }
                }
            }

            header('Location: ' . viewURL($page));
            exit;

        } else if ($_GET['vote_cancel']) {

            if ($poll_user && $_GET['poll_title']) {
                $vote_title = $this->quote($_GET['poll_title']);

                $query = "DELETE FROM ".$this->tblname." " .
                         "WHERE title='$vote_title' " .
                         "AND author='$poll_user'";
                $pagestore->dbh->query($query);
            }

            header('Location: ' . viewURL($page));
            exit;

        } else if (isset($_GET['poll_show_results'])) {

            $poll_show_results = $_GET['poll_show_results'] ? 1 : 0;
            setcookie('pollshowresults', $poll_show_results, time() + 157680000,
                      "/", false);

            header('Location: ' . viewURL($page));
            exit;

        }

        // poll type
        preg_match('/^((single|multiple) )?(.*)/i', $args, $matches);
        $poll_type = (strtolower($matches[2]) == 'multiple')
                     ? POLL_MULTIPLE : POLL_SINGLE;
        $args = $matches[3];

        // poll title and members
        $ob = strpos($args, '(');
        if ($ob === FALSE) {
            $poll_title = $args;
            $poll_members = array();
        } else {
            $poll_title = trim(substr($args, 0, $ob));
            $poll_members = substr($args, $ob + 1);
            $poll_members = rtrim($poll_members, ')');
            $poll_members = explode('|', $poll_members);
            for ($i = 0; $i < count($poll_members); $i++) {
                $poll_members[$i] = trim($poll_members[$i]);
            }
        }

        if ($poll_user) {
            // check if they have already voted.
            $query_title = addslashes($poll_title);
            $q1 = $pagestore->dbh->query("SELECT id " .
                                         "FROM ".$this->tblname." " .
                                         "WHERE title='$query_title' " .
                                         "AND author='$poll_user'");
            $results = $pagestore->dbh->result($q1);
        } else {
            // show results if not logged in
            $results = true;
        }

        if ($results) {
            $show_results = 1;
            $show_vote_widgets = 0;
        } else {
            $show_results = (isset($HTTP_COOKIE_VARS['pollshowresults'])
                             && $HTTP_COOKIE_VARS['pollshowresults']) ? 1 : 0;
            $show_vote_widgets = 1;
        }

        return $this->draw_poll($poll_user, $poll_type, $poll_title,
            $poll_members, $show_results, $show_vote_widgets);
    }

    function quote($text)
    {
        if (!get_magic_quotes_gpc()) {
            return addslashes($text);
        } else {
            return $text;
        }
    }

    function draw_poll($user, $type, $title, $members, $show_results,
                       $show_vote_widgets)
    {
        global $page, $pagestore;

        $query_title = addslashes($title);

        // get the count for each choice, the number of users who answered and
        // the choices of the user
        $vote_results = array();
        foreach ($members as $member){
            $vote_results[$member] = 0;
        }
        if ($show_results || !$show_vote_widgets) {
            $query = "SELECT author, choice " .
                     "FROM ".$this->tblname." " .
                     "WHERE title='$query_title'";
            $q = $pagestore->dbh->query($query);

            $allvotes = 0;
            $allvoters = array();
            $user_choices = array();
            while ($result = $pagestore->dbh->result($q)) {
                if (in_array($result[1], $members)) {
                    if (isset($allvoters[$result[0]])) {
                        $allvoters[$result[0]]++;
                    } else {
                        $allvoters[$result[0]] = 1;
                    }

                    if (isset($vote_results[$result[1]])) {
                        $vote_results[$result[1]]++;
                    } else {
                        $vote_results[$result[1]] = 1;
                    }

                    if ($result[0] == $user) {
                        $user_choices[$result[1]] = 1;
                    }

                    $allvotes++;
                }
            }
            $allvoters = count($allvoters);
        }

        $col_span = $show_results ? 2 : 1;
        $poll = '';

        if ($show_vote_widgets) {
            $poll .= '<form method="POST" action="?page=' . $page . '">' .
                     '<input type="hidden" name="poll_title" value="' .
                     htmlspecialchars($title) . '" />';
        }

        $poll .= '<table><tr><th colspan="' . $col_span .
                 '" align="center">' . htmlspecialchars($title) . '</th></tr>';

        foreach ($vote_results as $choice => $total) {
            $poll .= '<tr><td align="left">';
            if ($show_vote_widgets) {
                $input_type = ($type == POLL_SINGLE ? 'radio' : 'checkbox');
                $poll .= '<input type="' . $input_type . '" ' .
                         'name="poll_choice[]" value="' .
                         htmlspecialchars($choice) . '" />';
            }
            if (isset($user_choices[$choice])) {
                $poll .= '<b>' . htmlspecialchars($choice) . '</b>';
            } else {
                $poll .= htmlspecialchars($choice);
            }
            $poll .= '</td>';

            if ($show_results) {
                $per = ($allvotes <= 0) ? 0 : intval($total / $allvotes * 100);
                $imgurl = '?action=imgbar&amp;width=' . $this->barwidth .
                          '&amp;height=' . $this->barheight .
                          '&amp;per=' . $per;

                $poll .= "<td><img src='$imgurl' /> ($total votes)</td>";
            }
            $poll .= '</tr>';
        }

        if ($show_vote_widgets) {
            $show_hide_results = ' <small>(<a href="?page=' . $page .
                                 '&poll_show_results=' . (1 - $show_results) .
                                 '">' . ($show_results ? 'hide' : 'show') .
                                 ' results</a>)</small>';
            if ($show_results) {
                $show_hide_results = ' <b>Total voted: ' . $allvoters . '</b>' .
                                     $show_hide_results;
            }
            $poll .= '<tr><td colspan="' . $col_span . '" align="center">' .
                     '<input type="submit" name="poll_vote" ' .
                     'value="Cast your ballot! &gt;&gt;" />' .
                     $show_hide_results . '</td></tr>';
        } else {
            $poll .= '<tr><td colspan="2"><center><b>Total voted: ' .
                     $allvoters . '</b>';
            if ($user) {
                $poll .= ' <small>(<a href="?page=' . $page . '&poll_title=' .
                         rawurlencode($title) . '&vote_cancel=1">cancel my ' .
                         'vote</a>)</small></center></td></tr>';
            }
        }

        $poll .= '</table>';

        if ($show_vote_widgets) {
            $poll .= '</form>';
        }

        return $poll;
    }
}

return 1;

?>
