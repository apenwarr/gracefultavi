<?php

/**
 * Displays a revision history table based on the wiki database content. The
 * table shows columns for the date, version, description, and author of each
 * modification. By default, it skips minor edits, edits with empty comments, or
 * when the comment is "Comment". To show all edits, use the "ALL" parameter.
 * The first edit is always displayed.
 *
 * Basic Syntax:
 * [[RevisionHistory [ALL]]]
 *
 * In case some revisions would be missing, or have an unsatisfying description,
 * the override syntax may be used to add these revisions to the table. There
 * can be as many override lines as required, but they must all come before the
 * last call to RevisionHistory with the optional ALL parameter.
 *
 * Override Syntax:
 * [[RevisionHistory <revision number> <override description>]]
*/

class Macro_RevisionHistory
{
	function parse($args, $page)
	{
        global $CoTbl, $DiffScript, $HistMax, $pagestore, $PgTbl;
        static $overrides = array();

        require_once('lib/diff.php');

        if (preg_match_all('/\s*(\d+)\s(.*)/', $args, $words)) {
            $overrides[$words[1][0]] = $words[2][0];
            return;
        }

        $dbName = str_replace('\\', '\\\\', $page);
        $dbName = str_replace('\'', '\\\'', $dbName);

        $filter = (trim(strtolower($args)) != 'all');

        $qry = "
            SELECT version, time, minoredit, username, comment, lastversion
            FROM $PgTbl
            INNER JOIN $CoTbl ON page = id
            WHERE title = '$dbName'
            ORDER BY version";

        $rs = $pagestore->dbh->query($qry);

        $history = array();
        while ($row = $pagestore->dbh->result($rs)) {
            $history[$row[0]] = $row;
            if (isset($overrides[$row[0]])) {
                $history[$row[0]][4] = $overrides[$row[0]];
                $history[$row[0]][2] = 2; // sets as an override
            }
        }

        $minVer = min(array_keys($history));
        $maxVer = max(array_keys($history));

        $return = "<table>\n";
        $return .= "<tr><td><b>Date</b></td><td><b>Version</b></td>".
                   "<td><b>Description</b></td><td><b>Author</b></td></tr>\n";
        foreach ($history as $row) {
            if (($row[0] > 1) && $filter && $this->filterRow($row)) {
                continue;
            }

            $date = substr($row[1], 0, 4) . '-'. substr($row[1], 4, 2) . '-'.
                    substr($row[1], 6, 2);

            if ($row[0] > $minVer) {
                $ver1 = $row[0]-1;
                $ver2 = $row[0];

                if ($filter && ($row[0] > 1)) {
                    // finds additional contiguous edits of the same author
                    // that are filtered out but within 24 hours later
                    $username = $history[$row[0]][3];
                    $time = timestampToSeconds($history[$row[0]][1]);

                    while ($ver2 < $maxVer) {
                        $nextTime = timestampToSeconds($history[$ver2+1][1]);
                        if (($history[$ver2+1][3] == $username) &&
                            $this->filterRow($history[$ver2+1]) &&
                            (($nextTime - $time) <= 86400)) {
                            $ver2++;
                        } else {
                            break;
                        }
                    }
                }

                $versionUrl = $DiffScript.'&amp;page='.$page.'&amp;'.
                              'ver1='.$ver1.'&amp;ver2='.$ver2;
                if (($row[5] - $ver1 + 1) > $HistMax) {
                    $versionUrl .= '&amp;full=1';
                }
            } else {
                $versionUrl = viewUrl($page, $row[0]);
            }
            $version = '<div align="right"><a href="'.$versionUrl.'">' .
                       $row[0] . '</a>&nbsp;</div>';

            $comment = $row[4];
            if ($row[2] == 1) {
                $comment = '<small><i>(minor edit)</i></small> ' . $comment;
            }

            $author = $row[3] ? html_ref($row[3],$row[3]) : '';

            $return .= '<tr>';
            $return .= '<td>' . $date . '</td>';
            $return .= '<td>' . $version . '</td>';
            $return .= '<td>' . $comment . '</td>';
            $return .= '<td>' . $author . '</td>';
            $return .= "</tr>\n";
        }
        $return .= '</table>';

        return $return;
	}

    function filterRow($row) {
        if ($row[2] == 2) { return false; } // override, never filtered

        return                              // filter row when...
            ($row[3] == '') ||              // no username
            (trim($row[4]) == 'Comment') || // meaningless comment
            (trim($row[4]) == '') ||        // empty comment
            ($row[2] == 1);                 // minoredit
    }
}

return 1;

?>
