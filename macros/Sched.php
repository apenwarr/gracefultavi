<?php
global $sch_user;
global $sch_start;
global $sch_load;
global $sch_bugs;
global $sch_curday;
global $sch_elapsed_curday;
global $sch_got_bug;
global $sch_did_all_done;
global $sch_elapsed_subtract;
global $sch_unknown_fixfor;
global $bug_h;
global $SchedServer;
global $SchedUser;
global $SchedPass;
global $SchedName;


function bug_init()
{
    global $bug_h;
    global $SchedServer;
    global $SchedUser;
    global $SchedPass;
    global $SchedName;

    if (!$bug_h)
    {
        $bug_h = mysql_connect($SchedServer, $SchedUser, $SchedPass);
        mysql_select_db($SchedName, $bug_h);
    }
}
                    

function bug_person($user)
{
    global $bug_h;
    bug_init();

    $query = "select ixPerson from Person where sEmail like '$user@%'";
    $result = mysql_query($query, $bug_h);
    $row = mysql_fetch_row($result);
    if ($row)
        return $row[0];
    else
        return -1;
}


function bug_duedate($fixfor)
{
    global $bug_h;
    bug_init();

    $query = "select dt from FixFor where sFixFor='$fixfor'";
    $result = mysql_query($query, $bug_h);
    $row = mysql_fetch_row($result);
    if ($row)
        return $row[0];
    else
        return '';
}


// figure out what we need to append to the "where" clause of an sql query
// in order to find all bugs due on or before the given fixfor and/or end
// date.
function bug_fixfor_query($fixfor, $enddate)
{
    $ffquery = "";
    $endquery = "";
    
    if ($fixfor and $enddate)
        $ffquery = "  and (f.sFixFor = '$fixfor' or " .
                   "(f.dt is not null and f.dt <= '$enddate')) ";
    else if ($fixfor)
        $ffquery = "  and f.sFixFor = '$fixfor' ";

    // any bug opened before the end date counts toward that deadline, even
    // if the deadline has *now* passed and the bug isn't done.  Any bug
    // opened *after* the end date is obviously not intended for that
    // milestone.
    if ($enddate)
        $endquery = "  and b.dtOpened < '$enddate' ";

    return $ffquery . $endquery;
}


// return a list of all ACTIVE bugs due by the given fixfor/enddate.
//
// Returns an array:
//       bugid -> (title,OrigEst,CurrEst,Elapsed,FixForDate)
function bug_unfinished_list($user, $fixfor, $enddate)
{
    global $bug_h;
    bug_init();

    $personid = bug_person($user);
    $ffquery = bug_fixfor_query($fixfor, $enddate);
    
    $query = "select ixStatus,b.ixBug,sTitle, " .
             "       hrsOrigEst,hrsCurrEst,hrsElapsed, " .
             "  ifnull(f.dt, '2099/9/9') as sortdate " .
             "from Bug as b, FixFor as f " .
             "where b.ixFixFor=f.ixFixFor " .
             "  and b.ixPersonAssignedTo=$personid " .
             $ffquery .
             "  order by sortdate, sFixFor, ixPriority, ixBug ";
    //print "(($query))<p>";
    $result = mysql_query($query, $bug_h);
    if (!$result)
        print mysql_error($bug_h);

    $a = array();
    while ($row = mysql_fetch_row($result))
    {
	$status = array_shift($row);
	if ($status != 1)
	{
	    $row[1] = "VERIFY: $row[1]";
	    $row[2] = $row[3] = 0.1;
	    $row[4] = 0.09;
	}
        array_push($a, $row);
    }
    
    return $a;
}


// return a list of all RESOLVED bugs that were resolved by this user before
// the given fixfor *and* (if given) the given enddate.
//
// The list is in order of least-to-most-recently-resolved.
//
// Returns an array:
//       bugid -> (title,OrigEst,CurrEst,Elapsed,FixForDate)
function bug_finished_list($user, $fixfor, $startdate, $enddate)
{
    global $bug_h;
    bug_init();
    
    // We want to get all bugs *resolved by* the user after the given start
    // date.  The bugs are probably no longer assigned to that user.
    $personid = bug_person($user);

    $ffquery = bug_fixfor_query($fixfor, $enddate);

    $query = "select distinct " .
             "b.ixBug,sTitle,hrsOrigEst,hrsCurrEst,hrsElapsed, " .
             "  e.ixPerson, e.ixBugEvent, " .
             "  ifnull(f.dt, '2099/9/9') as sortdate " .
             "from Bug as b, BugEvent as e, FixFor as f " .
             "where e.ixBug=b.ixBug " .
             "  and f.ixFixFor=b.ixFixFor " .
             "  and e.dt >= '$startdate' " .
             $ffquery .
             "  and b.ixStatus > 1 " .
             "  and e.sVerb like 'RESOLVED%' " .
             "  and e.sVerb != 'Resolved (Again)' " .
             "  order by ixBugEvent desc ";
    // print "(($query))<p>";
    $result = mysql_query($query, $bug_h);
    if (!$result)
        print mysql_error($bug_h);
    
    $owned = array();
    $done = array();
    $a = array();
    while ($row = mysql_fetch_row($result))
    {
        $bug = array_shift($row);
	$byperson = $row[4];
	if ($byperson != $personid)
	{
	    $done[$bug] = 1;
	    continue;
	}
	
	if ($owned[$bug])
	{
	    // I own this bug already
	    continue;
	}
	
	if ($done[$bug])
	{
	    // someone resolved this bug *after* I did - don't lose it
	    $row[0] = "STOLEN: $row[0]";
	    $row[2] = $row[3] = 0.1;
	}

        // the bug is done, so don't give it any more time remaining
        if (!$row[2])
            $row[2] = 0.001;  // nonzero so we know the 'remaining' is accurate
        $row[3] = $row[2]; // elapsed = estimate; bug is done!

	if (!$owned[$bug])
	{
	    array_unshift($row, $bug);
	    array_push($a, $row);
	    $owned[$bug] = 1;
	}
    }

    return array_reverse($a);
}


function bug_get($bugid)
{
    global $bug_h;

    bug_init();
    $result = mysql_query("select sTitle,hrsOrigEst,hrsCurrEst,hrsElapsed, " .
                          "    sStatus,sFixFor " .
                          "from Bug as b, Status as s, FixFor as f " .
                          "where s.ixStatus=b.ixStatus " .
                          "  and f.ixFixFor=b.ixFixFor " .
                          "  and ixBug=" . ($bugid+0),
                          $bug_h);
    $row = mysql_fetch_row($result);

    if (!$row)
        return array($bugid, 0, 0, 0);
    else
        return $row;
}


function bug_link($bugid)
{
    return "<a href='http://nits/FogBUGZ3/?$bugid'>$bugid</a>";
}


function bug_title($bugid)
{
    $bug = bug_get($bugid);
    return $bug[0];
}


function bug_milestone_realname($name)
{
    global $bug_h;
    bug_init();

    $result = mysql_query("select sFixFor from FixFor " .
                          "where sFixFor like '$name%' " .
                          "limit 1");
    $row = mysql_fetch_row($result);
    if (!$row)
        return $name;
    else
        return $row[0];
}


function bug_set_release($fixfor, $dt)
{
    global $bug_h;
    bug_init();

    $query = "delete from schedulator.Milestone " .
             "  where sMilestone='$fixfor' and nSub=0";
    $result = mysql_query($query, $bug_h);

    $query = "insert into schedulator.Milestone " .
             "  (sMilestone, nSub, dtDue) " .
             "  values ('$fixfor', 0, '$dt')";
    $result = mysql_query($query, $bug_h);
}


function bug_set_milestones($fixfor, $dates)
{
    global $bug_h;
    bug_init();

    $query = "delete from schedulator.Milestone " .
             "  where sMilestone='$fixfor' and nSub>0";
    $result = mysql_query($query, $bug_h);
    if (!$result)
        print mysql_error($bug_h);

    $n = 1;
    foreach ($dates as $d)
    {
        $query = "insert into schedulator.Milestone " .
                 "  (sMilestone, nSub, dtDue) " .
                 "  values ('$fixfor', $n, '$d')";
        $result = mysql_query($query, $bug_h);
        if (!$result)
            print mysql_error($bug_h);
        $n++;
    }
}


function bug_get_milestones($fixfor)
{
    global $bug_h;
    bug_init();

    $query = "select distinct dtDue from schedulator.Milestone " .
             "  where sMilestone='$fixfor' and nSub>0 order by dtDue";
    $result = mysql_query($query, $bug_h);
    if (!$result)
        print mysql_error($bug_h);

    $a = array();
    while ($row = mysql_fetch_row($result))
        array_push($a, $row[0]);
    return $a;
}


function bug_add_task($user, $fixfor, $t)
{
    global $bug_h;
    bug_init();

    $task = mysql_escape_string($t[0]);
    $subtask = mysql_escape_string($t[1]);
    $remain = $t[3]-$t[4];
    $query = "insert into schedulator.Task " .
             "  (sPerson, sFixFor, " .
             "      sTask, sSubTask, " .
             "      hrsOrigEst, hrsCurrEst, hrsElapsed, hrsRemain, dtDue, fDone) " .
             "  values ('$user', '$fixfor', \"$task\", \"$subtask\", " .
             "            $t[2], $t[3], $t[4], $remain, '$t[5]', $t[6])";
    
    $result = mysql_query($query, $bug_h);
    if (!$result)
        print 'x-' . mysql_error($bug_h);
}


function bug_add_tasks($user, $fixfor, $tasks)
{
    foreach ($tasks as $t)
        bug_add_task($user, $fixfor, $t);
}


function bug_add_volunteer_tasks()
{
    global $bug_h;
    bug_init();

    $query = "delete from schedulator.Task where sPerson like '-%-'";
    $result = mysql_query($query, $bug_h);
    if (!$result)
        print mysql_error($bug_h);

    $query = "select sFullName, sFixFor, ixBug, sTitle " .
             "  from Bug as b, Person as p, FixFor as f, Status as s " .
             "  where p.ixPerson=b.ixPersonAssignedTo " .
             "    and f.ixFixFor=b.ixFixFor " .
             "    and s.ixStatus=b.ixStatus " .
             "    and s.sStatus='ACTIVE' " .
             "    and sFullName like '-%-' ";
    $result = mysql_query($query, $bug_h);
    if (!$result)
        print mysql_error($bug_h);

    while ($row = mysql_fetch_row($result))
    {
        $person = "-???-";
        $fixfor = $row[1];
        $bugid = $row[2];
        $title = $row[3];
        $info = array($bugid, $title, 1000, 1000, 0, '2099/9/9', 0);
        bug_add_task($person, $fixfor, $info);
    }
}


function bug_start_user($user)
{
    global $bug_h;
    bug_init();

    $query = "update schedulator.Task set fValid=0 where sPerson='$user'";
    $result = mysql_query($query, $bug_h);
}


function bug_finish_user($user)
{
    global $bug_h;
    bug_init();

    $query = "delete from schedulator.Task " .
             "where (fValid=0 and sPerson='$user') or sPerson=''";
    $result = mysql_query($query, $bug_h);
}


function sch_today()
{
    // we mostly use GMT for our work, but if they want today, they want
    // the local "today"
    $dat = strftime("%Y/%m/%d", time());
    $today = sch_parse_day($dat);
    //print("(today=$dat:$today/" . sch_format_day($today) . ")");
    return $today;
}


function sch_parse_day($day)
{
    if ($day == '')
        return 0;
    else if (preg_match(",(....)[/-](..)[/-](..),", $day, $a))
    {
        $year = $a[1];
        $month = $a[2];
        $day = $a[3];

        $stamp = mktime(0,0,12, $month, $day, $year);
        $spl = localtime($stamp);
        //print("(day-$year/$month/$day:$stamp:$spl[6])");
        if ($spl[6] == 0)  // sunday
            $stamp += 24*60*60;
        else if ($spl[6] == 6) // saturday
            $stamp += 2*24*60*60;
        else if ($spl[6] == 5) // friday
            $stamp += 3*24*60*60;

        $stamp -= 4*24*60*60; // the epoch was a Thursday.  Skip to Monday.
        $days = floor($stamp/24/60/60);
        $weeks = floor($days/7);
        $days -= $weeks*7;
        //printf("(days/weeks:$days/$weeks)");
        return $weeks*4 + $days;
    }
    else
    {
        print("(INVALID DATE:'$day')");
        return 0;
    }
}


function sch_format_day($day)
{
    if (!$day)
        return '';

    // convert working days to "real" days
    $weeks = floor($day/4);
    $days = $day - $weeks*4;
    $frac = $days - floor($days);
    //print "(weeks/days/frac:$weeks/$days/$frac)";
    $stamp = ($weeks*7 + floor($days)) * 24*60*60;
    $stamp += 4*24*60*60; // the epoch was a Thursday
    $stamp += 12*60*60;   // php timezone handling is insane

    $ret = strftime("%Y/%m/%d", $stamp);// . sprintf("+%.1f", $frac);
    //print "(ret:$ret)";
    return $ret;
}


function sch_add_hours($day, $hours)
{
    global $sch_load;

    //if ($hours < 0)
    //  return $day;  // never subtract hours from the date!

    return $day + ($hours * $sch_load) / 8.0;
}


// returns a time in hours
function sch_parse_period($str)
{
    //return "($str)";
    if (preg_match('/([0-9.]+) *(h|hr|hrs|hour|hours)$/', $str, $out))
        return $out[1]+0.0;
    else if (preg_match('/([0-9.]+) *(d|day|days)$/', $str, $out))
        return $out[1] * 8.0;
    else if (preg_match('/([0-9.]+) *(min|minutes)$/', $str, $out))
        return $out[1] / 60.0;
    else
        return $str + 0.0;
}


function _sch_period($hours)
{
    //return $hours;
    if (!$hours)
        return "";
    else if ($hours < 9)
        return sprintf("%dh", $hours);
    else if ($hours < 10*8.0)
        return sprintf("%.1fd", $hours/8);
    else
        return sprintf("%dd", $hours/8);
}


function sch_period($hours)
{
    if ($hours < -1)
        return "<font color=red>-" . _sch_period(-$hours) . "</font>";
    else
        return _sch_period($hours);
}

function sch_fullline($text)
{
    return "<tr><td colspan=7>$text</td></tr>";
}


function sch_warning($text)
{
    return sch_fullline("<font color=red>&nbsp;&nbsp;"
                        . "** WARNING: $text **"
                        . "</font>");
}


function sch_genline($feat, $task, $orig, $curr, $elapsed, $left, $due)
{
    $ret = "<tr>";

    $junk1 = $junk2 = '';
    if ($left == "done")
    {
        $junk1 = "<strike><font color=gray><i>";
        $junk2 = "</i></font></strike>";
    }

    if ($task)
        $ret .= "<td>$junk1$feat$junk2</td><td>$junk1$task$junk2</td>";
    else
        $ret .= "<td colspan=2>$junk1$feat$junk2</td>";
    return $ret . "<td>$junk1" .
           join("$junk2</td><td>$junk1",
                array($orig, $curr, $elapsed, $left, $due)) .
           "$junk2</td></tr>";
}


function sch_line($feat, $task, $orig, $curr, $elapsed, $remain, $done, $allow_red)
{
    global $sch_curday, $sch_elapsed_curday, $sch_elapsed_subtract;
    global $sch_bugs;

    if (preg_match('/^[0-9]+$/', $feat))
        $xfeat = bug_link($feat);
    else
        $xfeat = $feat;

    if ($done)
        $sremain = 'done';
    else if (!$remain)
        $sremain = '';
    else
        $sremain = sch_period($remain);

    $today = sch_today();
    $was_over_elapsed = ($sch_elapsed_curday - 4 > $today);

    $sch_curday = sch_add_hours($sch_curday, $curr);
    $sch_elapsed_curday = sch_add_hours($sch_elapsed_curday, $elapsed);

    $sub = $sch_elapsed_subtract[$feat];
    if ($sub)
    {
        $sch_curday = sch_add_hours($sch_curday, -$sub);
        $sch_elapsed_curday = sch_add_hours($sch_elapsed_curday, -$sub);
        $sch_elapsed_subtract[$feat] = 0; // only compensate once!
    }

    $due = sch_format_day($sch_curday);

    if ($allow_red && (!$curr || $remain) && !$done
        && floor($sch_curday) < floor($today))
        $due = "<font color=red>$due</font>";

    $ret .= sch_genline($xfeat, $task,
            sch_period($orig), sch_period($curr),
            sch_period($elapsed), $sremain,
            $due);
    //$ret .= sch_fullline("gork: $was_over_elapsed $sch_elapsed_curday $sch_curday $today");
    if (!$was_over_elapsed && $sch_elapsed_curday - 4 > $today)
        $ret .= sch_warning("The START time, plus the time so far in your " .
                            "ELAPSED column, puts you into the future!  " .
                            "Either your START date is wrong, or some of " .
                            "your elapsed hours are wrong, or you're working " .
                            "too hard.");
    return $ret;
}


function sch_bug($feat, $task, $_orig, $_curr, $_elapsed, $done)
{
    global $sch_user, $sch_curday, $sch_bugs, $sch_need_extraline;
    global $sch_got_bug, $sch_unknown_fixfor;
    
    if (!$done)
        $done = 0;
    else if ($done == -1)
    {
	$notdone = 1;
	$done = 0;
    }

    if ($sch_need_extraline)
    {
        $ret .= sch_fullline('&nbsp;');
        $sch_need_extraline = 0;
    }

    $fixfor = '';
    if (preg_match('/^[0-9]+$/', $feat))
    {
        $bug = bug_get($feat);
	// $bug = (title origest currest elapsed status fixfor)
	
        if (!$task)     $task = $bug[0];
        if (!$_orig)    $_orig = $bug[1];
        if (!$_curr)    $_curr = $bug[2];
        if (!$_elapsed) $_elapsed = $bug[3];
        if (!$done && !$notdone && $bug[4] != 'ACTIVE') $done = 1;
	if ((!$done && $_curr != $_elapsed)
	    || ($bug[4] != 'ACTIVE'))
	{
	    // already listed as not done, *or* really done in fogbugz:
	    // never need to auto-import this bug again.
	    $sch_got_bug[$feat] = 1;
	}
        $fixfor = $bug[5];
    }

    $orig = sch_parse_period($_orig);
    $curr = sch_parse_period($_curr);
    $elapsed = sch_parse_period($_elapsed);

    $remain = $curr - $elapsed;

    if (!$remain && $curr)
        $done = 1;

    $ret .= sch_line($feat, $task, $orig, $curr, $elapsed, $remain, $done,
                     true);
    if ($done && $curr != $elapsed)
        $ret .= sch_warning("This bug is done, but elapsed time is different " .
                            "from the current estimate.  You know " .
                            "how long it really took, so make your estimate " .
                            "accurate.");
    else if ($curr < $elapsed)
        $ret .= sch_warning("This bug's current estimate is less than the " .
                            "elapsed time so far.  Update your estimate!");
    $buga = array($feat, $task, $orig, $curr, $elapsed,
                  sch_format_day($sch_curday), $done);
    if ($fixfor)
        bug_add_task($sch_user, $fixfor, $buga);
    else
        array_push($sch_unknown_fixfor, $buga);
    return $ret;
}


function sch_extrabugs($user, $fixfor, $enddate, $only_done)
{
    global $sch_got_bug, $sch_start, $sch_did_all_done, $sch_elapsed_subtract;

    // $ret .= sch_warning("Extrabugs for $fixfor ($enddate) ($only_done)");
    
    $start = sch_format_day($sch_start);
    $today = sch_today();
    $fixfor_in_past = ($enddate && sch_parse_day($enddate) < $today);

    $bugs1 = array();
    $bugs2 = array();

    $ua = bug_unfinished_list($user, $fixfor, $enddate);
    if (count($ua))  // there are unfinished bugs for this release!
    {
        if ($sch_did_all_done)
            $a = array();
        else
            $a = bug_finished_list($user, "", $start, ""); // *all* finished bugs

        $do_all_done = true;
    }
    else
    {
        $a = bug_finished_list($user, $fixfor, $start, $enddate); // only some
        $do_all_done = false;
    }

    // handle done bugs
    foreach ($a as $idx => $bug)
    {
	$bugid = array_shift($bug);
        $zeroest = (abs($bug[2]) <= 0.01 && abs($bug[2]) >= 0.0001);
        $done = ($bug[2] && $bug[3] == $bug[2]);
        if (!$done)
            $ret .= sch_warning("Weird1: I don't know if bug #$bugid is done!");

        if ($sch_got_bug[$bugid])
            continue;

        if ($zeroest)
        {
            // the bug is done, but with a zero estimate; probably a
            // duplicate, wontfix, or something.  Skip it.
            $sch_got_bug[$bugid] = 1;
            continue;
        }

        $ret .= sch_bug($bugid, $bug[0], $bug[1], $bug[2], $bug[3], 0);
	$sch_got_bug[$bugid] = 1;  # definitely done now
    }

    if ($do_all_done && !$fixfor_in_past && !$sch_did_all_done)
    {
        $elapsed = 0;
        $sch_elapsed_subtract = array();

        $a = bug_unfinished_list($user, '', '');
        foreach ($a as $idx => $bug)
        {
	    $bugid = array_shift($bug);
            $elapsed += $bug[3];
            $sch_elapsed_subtract[$bugid] = $bug[3];
        }

        if ($elapsed > 0.1)
            $ret .= sch_bug("MAGIC",
                            "Time elapsed on unfinished bugs listed below",
                            $elapsed, $elapsed, $elapsed, true);

        $sch_did_all_done = true;
    }

    // handle unfinished bugs
    if (!$only_done)
    {
        foreach ($ua as $idx => $bug)
        {
	    $bugid = array_shift($bug);
            $zeroest = (abs($bug[2]) <= 0.01 && abs($bug[2]) >= 0.0001);
            $done = ($bug[2] && $bug[3] == $bug[2]);

            if ($sch_got_bug[$bugid])
                continue;

            if ($done)  // not actually done - adjust estimate!
            {
                // $ret .= sch_warning("Weird2: I don't know if bug #$bugid is done!");
                $bug[2] += 0.0001;
            }

            if (0 && $zeroest)
            {
                // the bug is done, but with a zero estimate; probably a
                // duplicate, wontfix, or something.  Skip it.
                $sch_got_bug[$bugid] = 1;
                continue;
            }

            if ($fixfor_in_past)
            {
                // if this release is in the past, but it's not fixed yet,
                // the bug must not *actually* be for this milestone.  Skip
                // it now, and add it into the next release or at the end of
                // the schedule.
                continue;
            }

            $ret .= sch_bug($bugid, $bug[0], $bug[1], $bug[2], $bug[3], -1);
	    $sch_got_bug[$bugid] = 1;
        }
    }

    return $ret;
}


function sch_all_done($user)
{
    global $sch_did_all_done, $sch_elapsed_subtract;

    if (!$sch_did_all_done)
        $ret .= sch_extrabugs($user, '', '', true);

    return $ret;
}


function sch_milestone($descr, $name, $due)
{
    global $sch_user, $sch_load, $sch_curday, $sch_need_extraline;

    // if no due date was given, assume it's the current date for purposes
    // of collecting extra bugs.
    if (!$due)
        $tmpdue = sch_format_day($sch_curday);
    else
        $tmpdue = $due;

    // fill in all bugs up to this milestone
    $ret .= sch_extrabugs($sch_user, $name, $tmpdue, false);

    // if no due date was given, make it the day after the last bug finished.
    if (!$due)
        $due = sch_format_day($sch_curday+1);

    $today = sch_today();
    $old_curday = $sch_curday;

    $newday = sch_parse_day($due);
    $xdue = sch_format_day($newday);
    $slip = ($newday-$sch_curday)*8 / $sch_load;
    //$ret .= sch_line("SLIPPAGE (to $xdue)", "", 0,$slip,0,$slip, 0, true);
    $done = $newday < $today;
    $ret .= sch_genline("<b>$descr: $name ($xdue)</b>", '',
                        '', sch_period($slip), '',
                        $done ? "done" : sch_period($slip),
                        '');
    $sch_need_extraline = 1;

    $sch_curday = $old_curday; // slippage doesn't actually take time... right?

    return $ret;
}


class Macro_Sched
{
    var $pagestore;

    function parse($args, $page)
    {
        global $sch_start, $sch_curday, $sch_elapsed_curday;
        global $sch_user, $sch_load;
        global $sch_unknown_fixfor;

        $ret = "";

        if (!preg_match_all('/"[^"]*"|[^ \t]+/', $args, $words))
            return "regmatch failed!\n";
        $words = $words[0]; // don't know why I have to do this, exactly...

        foreach ($words as $key => $value)
        {
            if (preg_match('/^"(.*)"$/', $value, $result))
                $words[$key] = $result[1];
        }

        if (0)
        {
            $ret .= "Hello: ";
            foreach ($words as $word)
                $ret .= "($word) ";
            $ret .= "<br>\n";
        }

        if ($words[0] == "START")
        {
            $ret .= "<table border=0 width='95%'>\n";
            $ret .= "<tr><th>" .
            join("</th><th>", array("Task", "Subtask", "Orig", "Curr",
                 "Done", "Left", "Due")) .
                 "</th></tr>\n";
            $sch_user = $words[1];
            $sch_start = $sch_curday = $sch_elapsed_curday
                = sch_parse_day($words[2]);
            $sch_unknown_fixfor = array();
            $sch_load = 1.0;
            $ret .= sch_line("START", "", 0,0,0,0, 0, false);
            if ($sch_start > sch_today())
                $ret .= sch_warning("START date is in the future!");
            bug_start_user($sch_user);
        }
        else if ($words[0] == "LOADFACTOR")
        {
            $loadtmp = $words[1] + 0.0;
            if ($loadtmp < 0.1)
                $ret .= "(INVALID LOAD FACTOR:'$words[1]')";
            else
                $sch_load = $loadtmp;

            $ret .= sch_fullline("(Load factor = $sch_load)");
        }
        else if ($words[0] == "FIXFOR")
        {
            $ret .= sch_extrabugs($sch_user, $words[1], '', false);
        }
        else if ($words[0] == "SETBOUNCE")
        {
            $cmd = array_shift($words);
            $msname = bug_milestone_realname(array_shift($words));
            bug_set_milestones($msname, $words);
        }
        else if ($words[0] == "MILESTONE" || $words[0] == "RELEASE"
                 || $words[0] == "BOUNCE")
        {
            $msname = bug_milestone_realname($words[1]);
            $msdue = $words[2];

            $extra_due = bug_get_milestones($msname);
            foreach ($extra_due as $xdue)
                $ret .= sch_milestone("ZeroBugBounce", $msname, $xdue);

            if (!$msdue)
                $msdue = bug_duedate($msname);
            $ret .= sch_milestone($words[0], $msname, $msdue);
            bug_set_release($msname, $msdue);
            bug_add_tasks($sch_user, $msname, $sch_unknown_fixfor);
            $sch_unknown_fixfor = array();
        }
        else if ($words[0] == "END")
        {
            $ret .= sch_extrabugs($sch_user, '', '', false);
            bug_add_tasks($sch_user, 'UNKNOWN', $sch_unknown_fixfor);
            bug_finish_user($sch_user);
            bug_add_volunteer_tasks();
            $ret .= sch_line("END", "", 0,0,0,0, 0, true);
            $ret .= "</table>";
        }
        else
        {
            $bug = $words[0];
            $task = $words[1];
            $est = $words[2];
            $elapsed = $words[3];

            $force_done = ($est && $est==$elapsed);

            $bugdata = bug_get($bug);
            if (!$force_done && (!$bugdata[4] || $bugdata[4] == 'ACTIVE'))
                $ret .= sch_all_done($sch_user);

            $ret .= sch_bug($bug, $task, $est, $est, $elapsed, 0);
        }

        return $ret;
    }
}

return 1;
?>
