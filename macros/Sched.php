<?php
global $sch_user;
global $sch_start;
global $sch_load;
// FIXME: sch_curday, sch_elapsed_curday shouldn't be global
// The day we are currently processing, in days since the epoch
global $sch_curday;
// The date up to which we think the user has done work (in days since the
// epoch).
global $sch_elapsed_curday;
// Array of bugs already processed from FogBugz (to avoid duplicates)
global $sch_got_bug;
// Array of bugs the user has explicitly scheduled
global $sch_manual_bugs;
// True if we've already processed all finished bugs from FogBugz
global $sch_did_all_done;
// Array of bugs with unknown fixfor targets (so they can be added to the
// database when a [[Sched MILESTONE/FIXFOR/RELEASE]] is encountered)
global $sch_unknown_fixfor;
// Bug database handle
global $bug_h;
global $SchedServer;
global $SchedUser;
global $SchedPass;
global $SchedName;

// Contains lists of complete and incomplete bugs, one per Fixfor.
// $sch_bug_lists[fixfor-name] is an array with two elements, "incomplete" and
// "complete".  Each of these is itself an array of bugs.
global $sch_bug_lists;
// Working lists of complete and incomplete bugs (we don't know the Fixfor yet)
global $sch_cur_incomplete_bugs;
global $sch_cur_complete_bugs;


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
	    $row[2] = $row[3] = 0.01;
	    $row[4] = 0.009;
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
    
    // print "results: " . count($a) . "<p>\n";

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


// Put all bugs assigned to "-Needs Volunteer-", "-Tech Support-", etc into
// the Schedulator database.
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


// Returns an array of all FixFors the given user has active bugs for, or has
// ever fixed a bug for, ordered by the due date of the fixfors.
function bug_get_fixfors($user)
{
    global $bug_h;
    bug_init();

    // We want to get all bugs *resolved by* the user after the given start
    // date.  The bugs are probably no longer assigned to that user.
    $personid = bug_person($user);

    $query = "select distinct sFixFor, " .
             "  ifnull(f.dt, '2099/9/9') as sortdate " .
             "  from Bug as b, BugEvent as e, FixFor as f, Person as p " .
             "  where p.ixPerson = $personid " . 
             "    and p.ixPerson = e.ixPerson " .
             "    and e.ixBug = b.ixBug " .
             "    and b.ixFixFor = f.ixFixFor " .
             "  order by sortdate";
    //print "(($query))<p>";
    $result = mysql_query($query, $bug_h);
    if (!$result)
        print mysql_error($bug_h);
    
    $a = array();
    
    while ($row = mysql_fetch_row($result))
    {
        //print "((Adding fixfor $row[0] to array))";
        array_push($a, $row[0]);
    }

    return $a;
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


// Given a day in "yyyy/mm/dd" or "yyyy-mm-dd" format, return the next
// Schedulator-day in days since the epoch.
// FIXME: Actually returns working-days since the epoch, approximately equal
// to days_since_epoch * 4/7. 
function sch_parse_day($day)
{
    if ($day == '')
        return 0;
    else if (preg_match(",(....)[/-](..)[/-](..),", $day, $a))
    {
        $year = $a[1];
        $month = $a[2];
        $day = $a[3];

        // FIXME: Insane math ahoy
        $stamp = mktime(12,0,0, $month, $day, $year);
        $spl = localtime($stamp);
        //print("(day-$year/$month/$day:$stamp:$spl[6])<br>\n");
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


// Given a value in days since the epoch, return a formatted string in the
// form "yyyy/mm/dd", suitable for display.
// FIXME: This actually takes a value in "working days since the epoch".
// Must fix this.
function sch_format_day($day)
{
    if (!$day)
        return '';

    //print "sch_format_day: day='$day'; ";
    // convert working days to "real" days
    // FIXME: Insane math ahoy.  Should at least divide by days_worked_per_week
    $weeks = floor($day/4);
    $days = $day - $weeks*4;
    $frac = $days - floor($days);
    //print "(weeks/days/frac:$weeks/$days/$frac)";
    $stamp = ($weeks*7 + floor($days)) * 24*60*60;
    $stamp += 4*24*60*60; // the epoch was a Thursday
    $stamp += 12*60*60;   // php timezone handling is insane

    $ret = strftime("%Y/%m/%d", $stamp);// . sprintf("+%.1f", $frac);
    //print "(ret:$ret)<br>\n";
    return $ret;
}


function sch_add_hours($day, $hours)
{
    global $sch_load;

    //if ($hours < 0)
    //  return $day;  // never subtract hours from the date!

    return $day + ($hours * $sch_load) / 8.0;
}


// Given a time in the format "4h", "3 days", "30 min" (etc), returns the time
// in hours.
function sch_parse_period($str)
{
    //return "($str)";
    if (preg_match('/([0-9]+) *(d|day|days) *([0-9]+) *(h|hr|hrs|hour|hours)$/', $str, $out))
        return $out[1] * 8.0 + $out[3];
    elseif (preg_match('/([0-9.]+) *(h|hr|hrs|hour|hours)$/', $str, $out))
        return $out[1]+0.0;
    else if (preg_match('/([0-9.]+) *(d|day|days)$/', $str, $out))
        return $out[1] * 8.0;
    else if (preg_match('/([0-9.]+) *(min|minutes)$/', $str, $out))
        return $out[1] / 60.0;
    else
        return $str + 0.0;
}


// FIXME: This gets returned in a non-standard format (4.4d instead of 4d3h)
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


// Return the given time in hours formatted into a suitable denomination
// (hours, days), suitable for inclusion directly into HTML.
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


function sch_line($feat, $task, $orig, $curr, $elapsed, $remain, $done, 
                $allow_red)
{
    global $sch_curday, $sch_elapsed_curday;

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
    // FIXME: Insane math?
    $was_over_elapsed = ($sch_elapsed_curday - 4 > $today);

    // All unfinished bugs have had their elapsed time already accounted for
    if ($done)
    {
        $sch_curday = sch_add_hours($sch_curday, $curr);
        $sch_elapsed_curday = sch_add_hours($sch_elapsed_curday, $elapsed);
    }
    else
    {
        $sch_curday = sch_add_hours($sch_curday, $curr - $elapsed);
        //$ret .= sch_fullline("Ignoring $elapsed elapsed hours for bug $feat");
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
    global $sch_user, $sch_curday, $sch_need_extraline;
    global $sch_got_bug, $sch_unknown_fixfor;
    global $sch_load;

    if ($feat == "LOADFACTOR")
    {
        if ($task != $sch_load)
            $ret .= sch_fullline("(Load factor = $task)");
        $sch_load = $task;
        return $ret;
    }

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


// Get a list of all FogBugz assigned to the user, and insert them into the
// buglists for the correct milestones
function sch_add_all_fogbugz($user)
{
    global $sch_bug_lists;
    global $sch_cur_complete_bugs; 
    global $sch_cur_incomplete_bugs;

    $fixfors = bug_get_fixfors($user);

    foreach ($fixfors as $fixfor)
    {
        sch_extrabugs($user, $fixfor, '', false);
    }
    
    return $ret;
}


// FIXME: This function can be cleaned up an awful lot
// Handle all finished and optionally all unfinished bugs for the given
// FixFor.  If no FixFor given, do all bugs.
function sch_extrabugs($user, $fixfor, $enddate, $only_done)
{
    global $sch_got_bug, $sch_start, $sch_did_all_done;
    global $sch_bug_lists;
    global $sch_manual_bugs;

    $ret .= sch_warning("Extrabugs for $fixfor ($enddate) ($only_done)");
    
    $start = sch_format_day($sch_start);
    $today = sch_today();
    $fixfor_in_past = ($enddate && sch_parse_day($enddate) < $today);

    $bugs1 = array();
    $bugs2 = array();

    $a = bug_finished_list($user, $fixfor, $start, $enddate); 

    // handle done bugs
    foreach ($a as $idx => $bug)
    {
	$bugid = array_shift($bug);
        $zeroest = (abs($bug[2]) <= 0.01 && abs($bug[2]) >= 0.0001);
        $done = ($bug[2] && $bug[3] == $bug[2]);
        if (!$done)
            $ret .= sch_warning("Weird1: I don't know if bug #$bugid is done!");

        if ($sch_got_bug[$bugid] || $sch_manual_bugs[$bugid])
            continue;

        if ($zeroest)
        {
            // the bug is done, but with a zero estimate; probably a
            // duplicate, wontfix, or something.  Skip it.
            $sch_got_bug[$bugid] = 1;
            continue;
        }

        $bugarr = array($bugid, $bug[0], $bug[1], $bug[2], $bug[3]);
        $sch_bug_lists[$fixfor]["complete"][] = $bugarr;

	$sch_got_bug[$bugid] = 1;  # definitely done now
    }

    // handle unfinished bugs
    if (!$only_done)
    {
        $ua = bug_unfinished_list($user, $fixfor, $enddate);
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

            if ($sch_manual_bugs[$bugid])
            {
                // The bug was already manually listed, so don't mess with it.
                continue;
            }

            $bugarr = array($bugid, $bug[0], $bug[1], $bug[2], $bug[3]);
            $sch_bug_lists[$fixfor]["incomplete"][] = $bugarr;

	    $sch_got_bug[$bugid] = 1;
        }
    }
    
    // print "done: " . count($sch_bug_lists[$fixfor]["complete"]) . "<br>\n";

    return $ret;
}

// Output all unprocessed completed bugs from both FogBugz and the bug lists
function sch_all_done($user)
{
    global $sch_did_all_done;
    global $sch_bug_lists;
    global $sch_load;

    $oldload = $sch_load;

    if (!$sch_did_all_done)
    {
        // Can't just use foreach ($sch_bug_lists as $milestone => $type)
        // because the foreach would clobber the internal array pointers
        $milestones = array_keys($sch_bug_lists);
        foreach ($milestones as $milestone)
        {
            $type = $sch_bug_lists[$milestone];
            // print("Adding " . count($type["complete"]) . " done bugs for $milestone<br>\n");
            if (is_array($type["complete"]) && count($type["complete"]) > 0)
            {
                foreach ($type["complete"] as $bug)
                {
                    //print("Adding done bug $bug[0]<br>\n");
                    $ret .= sch_bug($bug[0], $bug[1], $bug[2], $bug[3], $bug[4], 1);
                }
            }

            //unset($sch_bug_lists[$milestone]["complete"]);
        }

        // Display the MAGIC line after all done bugs, but it already has all
        // relevant loadfactors accounted for.
        $extra = sch_elapsed_time_unfinished($user);
        // Fudge to get it to display if 0
        if ($extra == 0)
            $extra = 0.001;
        $extra = $extra / $oldload;
        $ret .= sch_line("MAGIC", "Time elapsed on unfinished bugs " .
                        "listed below", 
                        $extra, $extra, $extra, 0, true, false);

        $sch_did_all_done = true;
    }

    // Running through all these bugs can mess with our load factor, make sure
    // to reset it
    if ($sch_load != $oldload)
        $ret .= sch_bug("LOADFACTOR", $oldload, "", "", "", 0);

    return $ret;
}


// FIXME: This function doesn't make all that much sense any more.  I think it
// now just outputs the MILESTONE line and the blank line.  Fair enough, but
// could be simpler.
function sch_milestone($descr, $name, $due)
{
    global $sch_user, $sch_load, $sch_curday, $sch_need_extraline;

    // if no due date was given, make it the day after the last bug finished.
    if (!$due)
        $due = sch_format_day($sch_curday+1);

    $today = sch_today();
    $old_curday = $sch_curday;

    $newday = sch_parse_day($due);
    $xdue = sch_format_day($newday);
    // FIXME: Insane math
    $slip = ($newday-$sch_curday)*8 / $sch_load;
    //$ret .= sch_line("SLIPPAGE (to $xdue)", "", 0,$slip,0,$slip, 0, true);
    $done = $newday < $today;
    // FIXME: If current day is past the milestone's release date, don't show
    // the "current" column in red.  See sch_period().
    $ret .= sch_genline("<b>$descr: $name ($xdue)</b>", '',
                        '', sch_period($slip), '',
                        $done ? "done" : sch_period($slip),
                        '');
    $sch_need_extraline = 1;

    $sch_curday = $old_curday; // slippage doesn't actually take time... right?

    return $ret;
}

// Count up the elapsed time in hours on all unfinished bugs, taking
// loadfactors into account
function sch_elapsed_time_unfinished($user)
{
    global $sch_bug_lists, $sch_cur_incomplete_bugs;

    $elapsed = 0;
    $local_loadfactor = 1;

    $milestones = array_keys($sch_bug_lists);
    foreach ($milestones as $milestone)
    {
        $inc = $sch_bug_lists[$milestone]["incomplete"];
        if (is_array($inc))
            foreach($inc as $bug)
            {
                if ($bug[0] == "LOADFACTOR")
                    $local_loadfactor = $bug[1];
                else if ($bug[4] > 0)
                    $elapsed += sch_parse_period($bug[4]) * $local_loadfactor;
            }
    }

    return $elapsed;
}


// Returns true if the given milestone has at least one incomplete bug (that
// isn't a LoadFactor)
function sch_has_inc_bug($milestone)
{
    global $sch_bug_lists;
    if (is_array($sch_bug_lists[$milestone]["incomplete"]))
    {
        $bugids = array_keys($sch_bug_lists[$milestone]["incomplete"]);
        foreach ($bugids as $bugid)
        {
            $bug = $sch_bug_lists[$milestone]["incomplete"][$bugid];
            if ($bug[0] != "LOADFACTOR")
                return 1;
        }
    }
    return 0;
}


// Output the HTML for all the bugs in the given milestone.  $buglists is an
// array containing two subarrays, $buglists["incomplete"] and
// $buglists["complete"]
function sch_output_milestone($user, $milestone, $msname, $msdue)
{
    global $sch_did_all_done, $sch_need_extraline;
    global $sch_bug_lists;
    global $sch_curday;

    $buglists = $sch_bug_lists[$milestone];

    $num_incomplete = count($buglists["incomplete"]);
    // $ret .= sch_fullline("Processing $num_incomplete incomplete bugs for milestone $milestone");
    
    $extra_due = bug_get_milestones($msname);
    $num_extra = count($extra_due);
    $next_fixfor = 0;

    // If we have an incomplete bug, make sure to list all completed bugs
    if (!$sch_did_all_done && sch_has_inc_bug($milestone))
    {
	// $ret .= sch_fullline("Found incomplete bug, adding all completed ".
	//                     "bugs first");
        $ret .= sch_all_done($user);
    }

    // FIXME: This loop has become big, and identical to the one below.
    // $ret .= sch_fullline("List of ".count($buglists["complete"])." completed bugs for $milestone<br>\n");
    if (!$sch_did_all_done && is_array($buglists["complete"]))
    {
        $fixfor_day = sch_parse_day($extra_due[$next_fixfor]);
        foreach($buglists["complete"] as $bug)
        {
            $bug_line = sch_bug($bug[0], $bug[1], $bug[2], $bug[3], $bug[4], 0);
            while ($sch_curday > $fixfor_day && $next_fixfor < $num_extra)
            {
                $ret .= sch_milestone("ZeroBugBounce", $msname, $extra_due[$next_fixfor]);
                $next_fixfor++;
                if ($next_fixfor < $num_extra)
                    $fixfor_day = sch_parse_day($extra_due[$next_fixfor]);
            }
            if ($sch_need_extraline)
            {
                $ret .= sch_fullline('&nbsp;');
                $sch_need_extraline = 0;
            }
            $ret .= $bug_line;
        }
    }

    // $ret .= sch_fullline("List of $num_incomplete incomplete bugs for $milestone<br>\n");
    if (is_array($buglists["incomplete"]))
    {
        $fixfor_day = sch_parse_day($extra_due[$next_fixfor]);
        foreach($buglists["incomplete"] as $bug)
        {
            $bug_line = sch_bug($bug[0], $bug[1], $bug[2], $bug[3], $bug[4], -1);
            while ($sch_curday > $fixfor_day && $next_fixfor < $num_extra)
            {
                $ret .= sch_milestone("ZeroBugBounce", $msname, $extra_due[$next_fixfor]);
                $next_fixfor++;
                $fixfor_day = sch_parse_day($extra_due[$next_fixfor]);
            }
            if ($sch_need_extraline)
            {
                $ret .= sch_fullline('&nbsp;');
                $sch_need_extraline = 0;
            }
            $ret .= $bug_line;
        }
    }

    // Clear out bugs we've already listed, so we can find all unlisted
    // bugs later.
    $sch_bug_lists[$milestone]["complete"] = array();
    $sch_bug_lists[$milestone]["incomplete"] = array();

    while ($next_fixfor < $num_extra)
    {
        $ret .= sch_milestone("ZeroBugBounce", $msname, $extra_due[$next_fixfor]);
        $next_fixfor++;
    }

    if ($msdue)
        $ret .= sch_milestone("RELEASE", $msname, $msdue);

    return $ret;
}


// Output an HTML schedule based on the current list of bugs
function sch_create($user)
{
    global $sch_start;
    global $sch_bug_lists;
    global $sch_cur_complete_bugs; 
    global $sch_cur_incomplete_bugs;
    global $sch_load;
    
    $ret .= "<table border=0 width='95%'>\n";
    $ret .= "<tr><th>" .
    join("</th><th>", array("Task", "Subtask", "Orig", "Curr",
         "Done", "Left", "Due")) .
         "</th></tr>\n";
    $ret .= sch_line("START", "", 0,0,0,0, 0, false);
    if ($sch_start > sch_today())
        $ret .= sch_warning("START date is in the future!");

    sch_add_all_fogbugz($user);

    // Shove the remaining current list of incomplete bugs onto the pile,
    // even though we don't know its target name
    $cur_list_name = "-Undecided-";
    sch_merge_cur_bugs($cur_list_name);

    // make sure to print initial load factor
    $tmpload = $sch_load;
    $sch_load = 1;
    $ret .= sch_bug("LOADFACTOR", $tmpload, "", "", "", 0);

    $fixfors = array();

    $milestones = array_keys($sch_bug_lists);
    foreach ($milestones as $milestone)
    {
        $msnames[$milestone] = bug_milestone_realname($milestone);
        $msdue = bug_duedate($msnames[$milestone]);
        // FIXME: It's a bit gross hard-coding this date, but PHP doesn't have
        // a "uasort()" to do user-defined sorting based on array values so
        // otherwise milestones with no release date (-Undecided- and
        // -Wishlist- in particular) will sort at the start, which is exactly
        // wrong.
        if ($msdue == "")
            $msdue = "2099-09-09 00:00:00";
        $fixfors[$milestone] = $msdue;
    }

    // List milestones in order of release date
    asort($fixfors);

    foreach ($fixfors as $milestone => $msdue)
    {
        $ret .= sch_output_milestone($user, $milestone, $msnames[$milestone], 
                                    $msdue);
    }

    $ret .= sch_line("END", "", 0,0,0,0, 0, true);
    $ret .= "</table>";

    return $ret;
}


function sch_merge_cur_bugs($fixfor)
{
    global $sch_cur_incomplete_bugs;
    global $sch_cur_complete_bugs;
    
    sch_list_merge($fixfor, "complete", $sch_cur_complete_bugs);
    sch_list_merge($fixfor, "incomplete", $sch_cur_incomplete_bugs);
    $sch_cur_incomplete_bugs = array();
    $sch_cur_complete_bugs = array();
}


function sch_list_merge($fixfor, $sect, $arr)
{
    global $sch_bug_lists;
    
    if (!is_array($sch_bug_lists[$fixfor])) {
	$sch_bug_lists[$fixfor][$sect] = $arr;
    }
    else
      $sch_bug_lists[$fixfor][$sect] =
        array_merge($sch_bug_lists[$fixfor][$sect], $arr);
}


function sch_switch_loadfactor($load)
{
    global $sch_cur_incomplete_bugs;
    global $sch_cur_complete_bugs;
    global $sch_load;
    
    $loadbug = array("LOADFACTOR", $load, "", "", "");
    
    // Mark where the load factor changed for both the complete and
    // incomplete bug lists, so we can display something sane.
    array_push($sch_cur_incomplete_bugs, $loadbug);
    array_push($sch_cur_complete_bugs, $loadbug);
    
    $sch_load = $load;
    //$ret .= "Setting loadfactor to $sch_load<br>\n";
}


function sch_next_milestone($fixfor)
{
    global $bug_h;
    bug_init();
    $ret = "";
    
    $query = "select dtDue " . 
             "  from schedulator.Milestone " .
             "  where dtDue > now() and sMilestone='$fixfor' " .
             "  order by dtDue limit 1 ";
    $result = mysql_query($query, $bug_h);
    $row = mysql_fetch_row($result);
    if (count($row) >= 1)
      return $row[0];
    else
      return "";
}


function sch_month_out($str, $count)
{
    $months = array("Jan", "Feb", "Mar", "Apr", "May", "Jun",
		    "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");
    $simple = $months[substr($str, 5, 2) - 1];
    return "<th class='year' colspan=$count>$simple</th>";
}


function sch_summary($fixfor)
{
    global $bug_h;
    bug_init();
    $ret = "";
    
    $query = "select dtDue, sPerson, sTask " . 
             "  from schedulator.Task " .
             "  where fValid=1 and sFixFor='$fixfor' and fDone = 0 " .
             "  order by dtDue ";
    $result = mysql_query($query, $bug_h);

    $dates = array();
    $bugs = array();
    
    while ($row = mysql_fetch_row($result))
    {
	$due = $row[0];
	$person = $row[1];
	$task = $row[2];
	
	# $nicedue = ereg_replace("-", " ", $due);
	$nicedue = ereg_replace("(....)-(..)-(..)", "\\3", $due);
	$dates[$due] = $nicedue;
	$last_date = $due;
	
	$bugs[$person][$due][] = $task;
    }
    
    $nextbounce = sch_next_milestone($fixfor);
    if ($nextbounce)
    {
	$next_str = " (Scheduled for $nextbounce)";
	$daystobounce = (strtotime($nextbounce) - time()) / 24 / 60 / 60;
    }
    else
    {
	$next_str = " (No bounces scheduled)";
	$daystobounce = 365;
    }
    
    $ret .= "<h1>Schedulator Summary for '$fixfor'</h1>\n";
    $ret .= "<p>Predicted Bounce: $last_date$next_str</p>\n";
    
    $ret .= "<style type='text/css'>\n" .
      " table.schedsum th " .
             "{ font-weight:normal; margin:0pt; text-align:left }\n" .
      " table.schedsum th.year,th.day,th.person " .
             "{ background: lightgray }\n" .
      " table.schedsum th.day,td.bugs " .
             "{ font-size: 8pt; }\n" .
      " table.schedsum td.superlate " .
             "{ background: #ee9999 }\n" .
      " table.schedsum td.superlate a:link,td.superlate a:visited " .
             "{ color: yellow; background: #aa0000 }\n" .
      " table.schedsum td.late " .
             "{ background: #f0f0c0 }\n" .
      " table.schedsum td.late a:link,td.late a:visited " .
             "{ color: red; background: yellow }\n" .
      "</style>";
    
    $date_list = array_keys($dates);
    sort($date_list);
    
    $person_list = array_keys($bugs);
    sort($person_list);
    $rowcount = 2 + count($person_list);
    
    $ret .= "<table class='schedsum'>\n";
    
    $ret .= "<tr class='schedsum'><th></th>";
    $lastyear = 0;
    $yearcount = 0;
    foreach ($date_list as $due)
    {
	$year = ereg_replace("(....)-(..)-(..)", "\\1", $due);
	if (!$lastyear) $lastyear = $year;
	if ($year != $lastyear)
	{
	    $ret .= "<th class='year' colspan=$yearcount>$lastyear</th>";
	    $lastyear = $year;
	    $yearcount = 0;
	}
	$yearcount++;
    }
    $ret .= "<th class='year' colspan=$yearcount>$lastyear</th>";
    $ret .= "</tr>\n";
    
    $ret .= "<tr class='schedsum'><th></th>";
    $lastmonth = 0;
    $monthcount = 0;
    foreach ($date_list as $due)
    {
	$month = ereg_replace("(....)-(..)-(..)", "\\1 \\2", $due);
	if (!$lastmonth) $lastmonth = $month;
	if ($month != $lastmonth)
	{
	    $ret .= sch_month_out($lastmonth, $monthcount);
	    $lastmonth = $month;
	    $monthcount = 0;
	}
	$monthcount++;
    }
    $ret .= sch_month_out($lastmonth, $monthcount);
    $ret .= "</tr>\n";
    
    $ret .= "<tr><th></th><th class='day'>" 
      . join("</th><th class='day'>", array_values($dates)) . "</th>\n";
    $ret .= "</tr>";
    
    foreach ($person_list as $person)
    {
	$schedname = strtoupper(substr($person, 0, 1)) 
	  . substr($person, 1) . "Schedule";
	
	$ret .= "<tr><th class='person'>" .
	  "<a href='index.php?$schedname' title=\"$person's schedulator\">" .
	  "$person</a></th>";
	foreach ($date_list as $due)
	{
	    $n = 0;
	    $v = "";
	    if (is_array($bugs[$person][$due]))
	    {
		foreach ($bugs[$person][$due] as $bug)
		{
		    $n++;
		    if (($bug + 0) . "" == $bug)
		      $v .= "<a href='http://nits/fogbugz3?$bug' " . 
		            "title='FogBugz bug #$bug'>$n</a> ";
		    else
		      $v .= "$n";
		}
	    }

	    $colclass = "";
	    $daysleft = (strtotime($due) - time()) / 24 / 60 / 60;
	    if ($daysleft < -7)
	      $colclass = "superlate";
	    else if ($daysleft < -2)
	      $colclass = "late";
	    else if ($daystobounce - $daysleft < 0)
	      $colclass = "superlate";
	    else if ($daystobounce - $daysleft < ($daystobounce / 2.5))
	      $colclass = "late";
	    
	    $ret .= "<td class='bugs $colclass'>$v</td>";
	}
	$ret .= "</tr>\n";
    }
    
    $ret .= "</table>\n";
    
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
        global $sch_bug_lists;
        global $sch_cur_complete_bugs;
        global $sch_cur_incomplete_bugs;
        global $sch_got_bug, $sch_manual_bugs;

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
            // Initialize bug lists
            $sch_bug_lists = array();
            $sch_cur_incomplete_bugs = array();
            $sch_cur_complete_bugs = array();
            $sch_unknown_fixfor = array();

            $sch_user = $words[1];
            $sch_start = $sch_curday = $sch_elapsed_curday
                = sch_parse_day($words[2]);
            $sch_unknown_fixfor = array();
            $sch_load = 0.1;
	    sch_switch_loadfactor(1.0);
	    
            bug_start_user($sch_user);
        }
        else if ($words[0] == "LOADFACTOR")
        {
            $loadtmp = $words[1] + 0.0;
            if ($loadtmp < 0.1)
                $ret .= "(INVALID LOAD FACTOR:'$words[1]')";
	    
	    sch_switch_loadfactor($loadtmp);
        }
        else if ($words[0] == "FIXFOR")
        {
            // We now know where the current lists of bugs go
	    sch_merge_cur_bugs($words[1]);

            // Carry over the current LoadFactor
            // FIXME: Make this a function or something
            $loadbug = array("LOADFACTOR", $sch_load, "", "", "");
            array_push($sch_cur_incomplete_bugs, $loadbug);
            array_push($sch_cur_complete_bugs, $loadbug);
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

            // We now know where the current lists of bugs go
	    sch_merge_cur_bugs($msname);
            
            // Carry over the current LoadFactor
            // FIXME: Make this a function or something
            $loadbug = array("LOADFACTOR", $sch_load, "", "", "");
            array_push($sch_cur_incomplete_bugs, $loadbug);
            array_push($sch_cur_complete_bugs, $loadbug);

            bug_set_release($msname, $msdue);
            bug_add_tasks($sch_user, $msname, $sch_unknown_fixfor);
            $sch_unknown_fixfor = array();
        }
        else if ($words[0] == "END")
        {
            bug_add_tasks($sch_user, 'UNKNOWN', $sch_unknown_fixfor);
            bug_finish_user($sch_user);
            bug_add_volunteer_tasks();

            $ret .= sch_create($sch_user);
        }
	else if ($words[0] == "SUMMARY")
	{
	    $fixfor = $words[1];
	    $ret .= sch_summary($fixfor);
	}
        else
        {
            $bugid = $words[0];
            $task = $words[1];
            $est = $words[2];
            $elapsed = $words[3];

            $force_done = ($est && $est==$elapsed);

            $fixfor = '';
            $orig = $est;
            $done = $force_done;
            if (preg_match('/^[0-9]+$/', $bugid))
            {
                // title, origest, currest, elapsed, status, fixfor
                $bugdata = bug_get($bugid);
                if (!$task)    $task = $bugdata[0];
                // Override orig estimate with one from FogBugz
                $orig = $bugdata[1];
                if (!$est)    $est = $bugdata[2];
                if (!$elapsed) $elapsed = $bugdata[3];
                if (!$done && $bugdata[4] != 'ACTIVE') $done = 1;
                if ((!$done && $curr != $elapsed) || ($bugdata[4] != 'ACTIVE'))
                {
                    // already listed as not done, *or* really done in fogbugz:
                    // never need to auto-import this bug again.
                    $sch_got_bug[$feat] = 1;
                }
                $fixfor = $bugdata[5];
                $sch_manual_bugs[$bugid] = 1;
            }

            if (!$elapsed) $elapsed = 0;

            // Make an array entry to put into bug lists
            $bug = array($bugid, $task, $orig, $est, $elapsed);

            if (!$force_done && !$done)
            {
                array_push($sch_cur_incomplete_bugs, $bug);
                // $ret .= "Adding bug ($bug[0], $bug[1], $bug[2], $bug[3], $bug[4], $bug[5]) to incomplete buglist<br>\n";
            }
            else
            {
                array_push($sch_cur_complete_bugs, $bug);
                // $ret .= "Adding bug ($bug[0], $bug[1], $bug[2], $bug[3], $bug[4], $bug[5]) to completed buglist<br>\n";
            }
        }

        return $ret;
    }
}

return 1;
?>
