<?php
global $sch_user;
global $sch_start;
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
                    

function bug_get($bugid)
{
    global $sch_db;

    $b = $sch_db->bug->first("ix", $bugid);
    if ($b)
        return $b;
    else
        return $sch_db->bug->create_bug($bugid);
}


// This function could maybe be inlined
function bug_milestone_realname($name)
{
    global $sch_db;

    $f = $sch_db->fixfor->first_prefix("name", $name);
    return $f->name;
}


function bug_set_release($fixfor, $dt)
{
    global $bug_h;
    bug_init();
    $ff = addslashes($fixfor);
    $query = "delete from schedulator.Milestone " .
             "  where sMilestone='$ff' and nSub=0";
    $result = mysql_query($query, $bug_h);

    $query = "insert into schedulator.Milestone " .
             "  (sMilestone, nSub, dtDue) " .
             "  values ('$ff', 0, '$dt')";
    $result = mysql_query($query, $bug_h);
}


function bug_set_milestones($fixfor, $dates)
{
    $ff = addslashes($fixfor);
    $query = "delete from schedulator.Milestone " .
             "  where sMilestone='$ff' and nSub>0";
    $result = bug_query($query);

    $n = 1;
    foreach ($dates as $d)
    {
        $query = "insert into schedulator.Milestone " .
                 "  (sMilestone, nSub, dtDue) " .
                 "  values ('$ff', $n, '$d')";
        $result = bug_query($query);
        $n++;
    }
}


function bug_get_milestones($fixfor)
{
    $ff = addslashes($fixfor);
    $query = "select distinct dtDue " .
	"from schedulator.Milestone " .
	"where sMilestone='$ff' " .
	"    and nSub>0 " .
	"    and dtDue>=now() " .
	"order by dtDue";
    $result = bug_query($query);

    $a = array();
    while ($row = mysql_fetch_row($result))
      $a[] = $row[0];
    return $a;
}

// Format of $t:
//   task, subtask, hrsOrigEst, hrsCurrEst, hrsElapsed, dtDue, 
//   fDone, fResolved, ixPriority
function bug_add_task($user, $fixfor, $t)
{
    global $bug_h;
    bug_init();
    $ff = addslashes($fixfor);
    $u = addslashes($user);
    $task = mysql_escape_string($t[0]);
    $subtask = mysql_escape_string($t[1]);
    $remain = $t[3]-$t[4];
    // Boolean false converts to the empty string.  Stupid PHP.
    $done = (int)$t[6];
    $query = "insert into schedulator.Task " .
             "  (sPerson, sFixFor, " .
             "      sTask, sSubTask, " .
             "      hrsOrigEst, hrsCurrEst, hrsElapsed, hrsRemain, " .
             "      dtDue, fDone, fResolved, ixPriority) " .
             "  values ('$u', '$ff', \"$task\", \"$subtask\", " .
             "            $t[2], $t[3], $t[4], $remain, " . 
             "            '$t[5]', $done, '$t[7]', '$t[8]')";

    $result = mysql_query($query, $bug_h);
    if (!$result)
        print 'x-' . mysql_error($bug_h) . '<br>';
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
    $query = "delete from schedulator.Task where sPerson like '-%-'";
    $result = bug_query($query, $bug_h);

    $query = "select sFullName, sFixFor, ixBug, sTitle, " .
             "    b.ixStatus, ixPriority " .
             "  from Bug as b, Person as p, FixFor as f, Status as s " .
             "  where p.ixPerson=b.ixPersonAssignedTo " .
             "    and f.ixFixFor=b.ixFixFor " .
             "    and s.ixStatus=b.ixStatus " .
             "    and s.sStatus='ACTIVE' " .
             "    and sFullName like '-%-' ";
    $result = bug_query($query);

    while ($row = mysql_fetch_row($result))
    {
        $person = "-???-";
        $fixfor = $row[1];
        $bugid = $row[2];
        $title = $row[3];
	$resolved = ($row[4] != 1);
	$priority = $row[5];
        $info = array($bugid, $title, 1000, 1000, 0, '2099/9/9', 0,
		      $resolved, $priority);
        bug_add_task($person, $fixfor, $info);
    }
}


function bug_query($query)
{
    global $bug_h;
    bug_init();
    $result = mysql_query($query, $bug_h);
    if (!$result)
      print mysql_error($bug_h);
    return $result;
}


function bug_onerow($query)
{
    return mysql_fetch_row(bug_query($query));
}


function bug_start_user($user)
{
    // mark old schedulator tasks for this user as invalid
    $query = "update schedulator.Task set fValid=0 where sPerson='$user'";
    bug_query($query);
}


function bug_finish_user($user)
{
    $query = "delete from schedulator.Task " .
             "where (fValid=0 and sPerson='$user') or sPerson=''";
    bug_query($query);
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
        //printf("(days/weeks:$days/$weeks)<br>\n");
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
function sch_format_day($day, $seperator="/")
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

    $ret = strftime("%Y$seperator%m$seperator%d", $stamp);// . sprintf("+%.1f", $frac);
    //print "(ret:$ret)<br>\n";
    return $ret;
}


function sch_add_hours($day, $hours, $load)
{
    //if ($hours < 0)
    //  return $day;  // never subtract hours from the date!
    return $day + ($hours * $load) / 8.0;
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


function sch_genline($task, $title, $orig, $curr, $elapsed, $left, $due)
{
    $ret = "<tr>";

    $junk1 = $junk2 = '';
    if ($left == "done")
    {
        $junk1 = "<strike><font color=gray><i>";
        $junk2 = "</i></font></strike>";
    }

    if ($title)
        $ret .= "<td>$junk1$task$junk2</td><td>$junk1$title$junk2</td>";
    else
        $ret .= "<td colspan=2>$junk1$task$junk2</td>";
    return $ret . "<td>$junk1" .
	join("$junk2</td><td>$junk1",
	     array($orig, $curr, $elapsed, $left, $due)) .
	"$junk2</td></tr>";
}


function sch_line($task, $title, $orig, $curr, $elapsed, $remain, $done, 
		  $allow_red)
{
    global $sch_curday, $sch_elapsed_curday;

    //print "($task)($done)<br>\n";
    
    $ret = "";

    if ($done)
        $sremain = 'done';
    else if (!$remain)
        $sremain = '';
    else
        $sremain = sch_period($remain);

    $today = sch_today();
    // FIXME: Insane math?
    $was_over_elapsed = ($sch_elapsed_curday - 4 > $today);

    $due = sch_format_day($sch_curday);

    if ($allow_red && (!$curr || $remain) && !$done
        && floor($sch_curday) < floor($today))
        $due = "<font color=red>$due</font>";

    $ret .= sch_genline($task, $title,
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


function sch_bug($estimate)
{
    global $sch_user, $sch_curday;
    global $sch_got_bug, $sch_unknown_fixfor;
    $ret = "";
    static $old_load = -1;
    
    if ($estimate->loadfactor != $old_load)
    {
        $ret .= sch_fullline("(Load factor = ".$estimate->loadfactor.")");
        $old_load = $estimate->loadfactor;
    }

    $title = htmlentities($estimate->nice_title(), ENT_QUOTES);

    $orig = sch_parse_period($estimate->est_orig());
    $curr = sch_parse_period($estimate->est_curr());
    $elapsed = sch_parse_period($estimate->est_elapsed());

    $remain = $estimate->est_remain();

    // update the time.
    // we need to update the time BEFORE we put out the line
    // All unfinished bugs have had their elapsed time already accounted for
    if ($estimate->isdone())
    {
        $sch_curday = sch_add_hours($sch_curday, $curr, $estimate->loadfactor);
        $sch_elapsed_curday = sch_add_hours($sch_elapsed_curday, $elapsed, $estimate->loadfactor);
    }
    else
    {
        $sch_curday = sch_add_hours($sch_curday, $curr - $elapsed, $estimate->loadfactor);
        //$ret .= sch_fullline("Ignoring $elapsed elapsed hours for bug $feat");
    }

    // FIXME: Estimate::isdone() only checks the Estimate's be_done flag;
    // maybe should make it also check its task's state
    $ret .= sch_line($estimate->task->hyperlink(), $title, $orig, $curr, 
		     $elapsed, $remain, $estimate->isdone(), true);


    if ($estimate->isdone() && $curr != $elapsed)
        $ret .= sch_warning("This bug is done, but elapsed time is different " .
                            "from the current estimate.  You know " .
                            "how long it really took, so make your estimate " .
                            "accurate.");
    else if ($curr < $elapsed)
        $ret .= sch_warning("This bug's current estimate is less than the " .
                            "elapsed time so far.  Update your estimate!");

    // FIXME: Call $estimate->update or something.
    $task = $estimate->isbug ? $estimate->task->ix : $estimate->task->task;
    $resolved = $estimate->isbug && $estimate->task->isresolved();
    $buga = array($task, $title, $orig, $curr, $elapsed,
                  sch_format_day($sch_curday), $estimate->task->isdone(), 
                  $resolved, $estimate->task->get_priority());
    if (!$estimate->isdone() && 
	isset($estimate->task->fixfor) && $estimate->task->fixfor != -1)
        bug_add_task($sch_user->username, $estimate->task->fixfor->name, $buga);
    else
        $sch_unknown_fixfor[] = $buga;
    return $ret;
}

// FIXME: This function doesn't make all that much sense any more. I think it
// now just outputs the MILESTONE line and the blank line.  Fair enough, but
// could be simpler.
function sch_milestone($descr, $name, $due, $load, $newline)
{
    global $sch_curday;
    $ret = "";

    // if no due date was given, make it the day after the last bug finished.
    if (!$due)
        $due = sch_format_day($sch_curday+1);

    $today = sch_today();
    $old_curday = $sch_curday;

    $newday = sch_parse_day($due);
    $xdue = sch_format_day($newday);
    // FIXME: Insane math
    $slip = ($newday-$sch_curday)*8 / $load;
    //$ret .= sch_line("SLIPPAGE (to $xdue)", "", 0,$slip,0,$slip, 0, true);
    $done = $newday < $today;
    // FIXME: If current day is past the milestone's release date, don't show
    // the "current" column in red.  See sch_period().
    $ret .= sch_genline("<b>$descr: $name ($xdue)</b>", '',
                        '', sch_period($slip), '',
                        $done ? "done" : sch_period($slip),
                        '');
    if ($newline)
	$ret .= sch_fullline('&nbsp;');

    $sch_curday = $old_curday; // slippage doesn't actually take time... right?

    return $ret;
}

// Count up the elapsed time in hours on all unfinished bugs.
function sch_elapsed_time_unfinished($user)
{
    global $sch_db;
    $elapsed = 0;

    $estimates = $sch_db->estimate->keys();
    foreach ($estimates as $ix)
    {
        $e = $sch_db->estimate->a[$ix];
        if (!$e->isdone() && $e->est_elapsed() > 0)
            $elapsed += sch_parse_period($e->est_elapsed());
    }

    return $elapsed;
}


function sch_release_line($old_fixfor, $estimate, $newline)
{
    $a = bug_get_milestones($old_fixfor->name);
    foreach ($a as $b)
	$ret .= sch_milestone("ZeroBugBounce", $old_fixfor->name, $b, 
			      $estimate->loadfactor, false); 
    
    $ret .= sch_milestone("RELEASE", $old_fixfor->name, $old_fixfor->release_date, 
			  $estimate->loadfactor, $newline); 
    return $ret;
}


function sch_make_magic_est($magic, $load)
{
    global $sch_bug;

    $magic = sch_elapsed_time_unfinished($user);

    $est = new Estimate(/* fake - not in database */ 1,
			/*done*/true, 
			"MAGIC", 
			/*assignto*/ $sch_user->username, 
			/*isbug*/ 0,
			/*task - filled in below*/ '', 
			$magic, $magic, $magic,
			/*resolvedate*/ "1970-01-01",
			$sch_user->username, $load);
    $sch_db->xtask->max_ix++;
    $est->task = new XTask(/*ix*/ $sch_db->xtask->max_ix, 
			   "MAGIC", "Time elapsed on unfinished bugs listed below", 
			   /*fixfor - set later*/ -1,
			   /*ixPersonAssignedTo*/ $sch_user->ix,
			   /*my_user*/ $sch_user->username);
    return $est;
}


// Output an HTML schedule based on the current list of bugs
function sch_create($user)
{
    global $sch_start;
    global $sch_curday;
    global $sch_db;
    $ret = "";
    
    $ret .= "<table border=0 width='95%'>\n";
    $ret .= "<tr><th>" .
    join("</th><th>", array("Task", "Subtask", "Orig", "Curr",
         "Done", "Left", "Due")) .
         "</th></tr>\n";
    $ret .= sch_line("START", "", 0,0,0,0,0, false);
    if ($sch_start > sch_today())
        $ret .= sch_warning("START date is in the future!");

    // Shove the remaining current list of incomplete bugs onto the pile,
    // even though we don't know its target name
    sch_merge_cur_bugs("-Undecided-");

    $sch_db->estimate->do_sort();
    
    unset($old_fixfor);
    $did_magic = false;
    foreach ($sch_db->estimate->a as $e)
    {
	//print "Hello: (" . $e->task->name . ") (" . $e->task->fixfor->date . ")<br>\n";
	if (!$e->isdone() && !$did_magic)
        {
	    $ret .= sch_bug(sch_make_magic_est($magic, $e->loadfactor));
            $did_magic = true;
        }

        $f = $e->task->fixfor;
        if (!isset($old_fixfor) && !$e->isdone())
            $old_fixfor = $f;
        else if ($old_fixfor->ix != $f->ix && !$e->isdone()) 
        {
	    $ret .= sch_release_line($old_fixfor, $e, true);
	    $old_fixfor = $f;
        }

        $ret .= sch_bug($e);
    }

    // Print last release line
    $ret .= sch_release_line($old_fixfor, $e, false);

    $ret .= sch_line("END", "", 0,0,0,0,0, true);
    $ret .= "</table>";

    return $ret;
}


function sch_merge_cur_bugs($fixfor)
{
    global $sch_cur_incomplete_bugs;
    global $sch_cur_complete_bugs;
    global $sch_db;
    
    //print $fixfor . "<br>\n";
   
    $f = $sch_db->fixfor->first("name", $fixfor);
    if (!$f)
        $f = $sch_db->fixfor->first_prefix("name", $fixfor);
    foreach ($sch_cur_complete_bugs as $e)
    {
	//print "Hello: (" .$e->task->name . ")<br>\n";
        $e->task->fixfor = $f;
        $sch_db->estimate->a[] = $e;
    }
    foreach ($sch_cur_incomplete_bugs as $e)
    {
	//print "Hello: (" .$e->task->name . ")<br>\n";
        $e->task->fixfor = $f;
        $sch_db->estimate->a[] = $e;
    }

    $sch_cur_incomplete_bugs = array();
    $sch_cur_complete_bugs = array();
}

function sch_change_loadfactor($load, $date)
{
    global $sch_db;
    //print "LOADFACTOR $load <br>\n";
    foreach ($sch_db->estimate->a as $i=>$e)
    {
	if (sch_parse_day($e->get_resolvedate()) >= $date 
	    || sch_parse_day($e->get_resolvedate()) == 0)
	   $sch_db->estimate->a[$i]->loadfactor = $load;
	//print sch_parse_day($e->get_resolvedate()) . ">?$date -> " . 
	//$sch_db->estimate->a[$i]->loadfactor . "<br>\n";
    }
}

function sch_next_milestone($fixfor)
{
    $ret = "";
    $ff = addslashes($fixfor);
    $query = "select dtDue " . 
             "  from schedulator.Milestone " .
             "  where dtDue > now() and sMilestone='$ff' " .
             "  order by dtDue limit 1 ";
    $row = bug_onerow($query);
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


function sch_dateclass($due, $daystobounce)
{
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
    return $colclass;
}


function sch_summary($fixfor)
{
    global $bug_h;
    bug_init();
    $ret = "";
    $ff = addslashes($fixfor);
    $allpeople = array();
    $result = mysql_query("select distinct sPerson from schedulator.Task " .
			  "  where fValid=1 and fDone=0 " . 
			  "    and sPerson not like '-%-'" .
			  "  order by sPerson ",
			  $bug_h);
    while ($result && $row = mysql_fetch_row($result))
    {
        $person = $row[0];
        $schedname = strtoupper(substr($person, 0, 1)) 
          . substr($person, 1) . "Schedule";
        
        $allpeople[$person] = 
            "<a href='index.php?$schedname' title=\"$person's schedulator\">" .
            "$person</a>";
    }
    
    $query = "select dtDue, sPerson, sTask, sSubTask, " .
             "    fResolved, ixPriority, hrsCurrEst " . 
             "  from schedulator.Task " .
             "  where fValid=1 and sFixFor='$ff' and fDone=0 " .
             "  order by dtDue, ixPriority, sTask, sSubTask ";
    $result = mysql_query($query, $bug_h);

    $dates = array();
    $bugs = array();
    
    while ($row = mysql_fetch_row($result))
    {
	$due = $row[0];
	$person = $row[1];
	$task = $row[2];
        $subtask = $row[3];
	$resolved = $row[4];
	$priority = $row[5];
	$currest = $row[6];
	
	# $nicedue = ereg_replace("-", " ", $due);
	$nicedue = ereg_replace("(....)-(..)-(..)", "\\3", $due);
	$dates[$due] = $nicedue;
	$last_date = $due;

	$bugs[$person][$due][] = array
	  ("task" => $task, 
	   "subtask" => $subtask,
	   "resolved" => $resolved,
	   "priority" => $priority,
	   "currest" => $currest);
	unset($allpeople[$person]);
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
    
    $ret .= <<<EOF
      
<style type='text/css'>
    /* headings */
    table.schedsum th {
	font-weight:normal; margin:0pt; text-align:left;
    }
    
    /* headings except topleft corner */
    table.schedsum th.year,th.day,th.person {
	background: lightgray
    }
    
    /* typical font size */
    table.schedsum th.day,td.bugs {
	font-size: 8pt;
    }
    
    /* cell backgrounds */
    table.schedsum td.superlate { 
	background: #ee9999;
    }
    table.schedsum td.late { 
	background: #f0f0c0
    }
    
    /* normal vs. late vs. super-late bugs */
    table.schedsum a {
	text-decoration: none;
    }
    table.schedsum a.superlate:link,a.superlate:visited {
	color: yellow; background: #aa0000
    }
    table.schedsum a.late:link,a.late:visited {
	color: red; background: yellow
    }
    
    /* resolved vs. unresolved bugs */
    table.schedsum a.resolved:link,a.resolved:visited {
	font-style: italic;
    }
    table.schedsum a.unresolved:link,a.unresolved:visited {
	border-style: solid; border-width: 1px;
    }
    
    /* bug priorities */
    table.schedsum a.pri1,a.pri2 {
	font-size: 150%;
    }
    table.schedsum a.pri3 {
	font-size: 125%;
    }
    table.schedsum a.pri6,a.pri7 {
	font-size: 80%;
    }
    
    /* mouseovers on bugs */
    table.schedsum a:hover:link,a:hover:visited,span:hover {
	color: white; background: green;
    }
</style>
EOF;
    
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
	  
    $ret .= "<tr><th></th>";
    foreach ($date_list as $due)
    {
	$day = ereg_replace("(....)-(..)-(..)", "\\3", $due);
	$colclass = sch_dateclass($due, $daystobounce);
	$ret .= "<th class='day $colclass'>" .
	  $day .
	  "</th>\n";
    }
    $ret .= "</tr>\n";
    
    foreach ($person_list as $person)
    {
	$schedname = strtoupper(substr($person, 0, 1)) 
	  . substr($person, 1) . "Schedule";
	
	$ret .= "<tr><th class='person'>" .
	  "<a href='index.php?$schedname' title=\"$person's schedulator\">" .
	  "$person</a></th>";
	foreach ($date_list as $due)
	{
	    $dateclass = sch_dateclass($due, $daystobounce);
	    $n = 0;
	    $num_unresolved = 0;
	    $v = "";
	    if (is_array($bugs[$person][$due]))
	    {
		foreach ($bugs[$person][$due] as $bug)
		{
		    $n++;
                    $task = $bug["task"];
                    $subtask = $bug["subtask"];
                    $pri = $bug["priority"];
		    $priclass = "pri$pri";
		    $isbug = (($task + 0)."" == $task);
		    if ($bug["resolved"] || !$isbug)
			$bugclass = "resolved $priclass";
		    else
		    {
			$bugclass = "unresolved $priclass $dateclass";
			$num_unresolved++;
		    }
		    if ($isbug)
		      $v .= "<a class='$bugclass' " .
		            "href='http://nits/fogbugz3?$task' " . 
		            "title='Bug $task: $subtask'>$pri</a>";
		    else
		      $v .= "<span class='bugclass' " .
		            "title=\"$task: $subtask\">$pri</span>";
		    if ($bug["currest"] < 0.05)
		      $v .= "<sup>?</sup>";
		    $v .= " ";
		}
	    }

	    if ($v == "" || $num_unresolved==0)
	      $colclass = "";
	    else
	      $colclass = $dateclass;
	    
	    $ret .= "<td class='bugs $colclass'>$v</td>";
	}
	$ret .= "</tr>\n";
    }
    
    $ret .= "</table>\n";
    
    $ret .= "<p><b>Done for this release:</b> " .
      join(", ", array_values($allpeople)) .
      "</p>\n";
    
    return $ret;
}


class Macro_Sched
{
    var $pagestore;

    function parse($args, $page)
    {
        global $sch_start, $sch_curday, $sch_elapsed_curday;
        global $sch_user;
        global $sch_unknown_fixfor;
        global $sch_cur_complete_bugs;
        global $sch_cur_incomplete_bugs;
        global $sch_got_bug, $sch_manual_bugs;
	global $sch_db;

	static $current_load = 1.0;
	static $sch_manual_ix = 0;

        // Turn this on to find undefined variables, but it'll whine about the
        // spectacular $notdef variable in FogBugz.php
        //error_reporting(E_ALL);

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
            $sch_cur_incomplete_bugs = array();
            $sch_cur_complete_bugs = array();
            $sch_unknown_fixfor = array();

            $username = $words[1];
            $sch_start = $sch_curday = $sch_elapsed_curday
                = sch_parse_day($words[2]);
            $sch_unknown_fixfor = array();
	    
	    $sch_db = new FogTables($username, -1, sch_format_day($sch_start, "-"));
            $sch_user = $sch_db->person->first("username", $username);
	    
            bug_start_user($sch_user->username);
	    sch_change_loadfactor($current_load, $sch_start);
        }
        else if ($words[0] == "LOADFACTOR")
        {
            $loadtmp = $words[1] + 0.0;
            if ($loadtmp < 0.1)
            {
		$ret .= "(INVALID LOAD FACTOR:'$words[1]')";
	    }
	    else
	    {
		$current_load = $loadtmp;
		$load_date = array_key_exists(2, $words) ? sch_parse_day($words[2]) : $sch_start;
		sch_change_loadfactor($loadtmp, $load_date);
	    }
        }
        else if ($words[0] == "FIXFOR")
        {
            // We now know where the current lists of bugs go
	    sch_merge_cur_bugs($words[1]);
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
            $msdue = array_key_exists(2, $words) ? $words[2] : '';

            // We now know where the current lists of bugs go
	    sch_merge_cur_bugs($msname);
            
            bug_set_release($msname, $msdue);
            bug_add_tasks($sch_user->username, $msname, $sch_unknown_fixfor);
            $sch_unknown_fixfor = array();
        }
        else if ($words[0] == "END")
        {
            bug_add_tasks($sch_user->username, 'UNKNOWN', $sch_unknown_fixfor);
            bug_finish_user($sch_user->username);
            bug_add_volunteer_tasks();

            $ret .= sch_create($sch_user->username);
        }
	else if ($words[0] == "SUMMARY")
	{
	    $fixfor = $words[1];
	    $ret .= sch_summary($fixfor);
	}
        else
        {
            $bugid = $words[0];
            $task = array_key_exists(1, $words) ? $words[1] : '';
            $currest = array_key_exists(2, $words) ? sch_parse_period($words[2]) : '';
            $elapsed = array_key_exists(3, $words) ? sch_parse_period($words[3]) : '';

            $done = ($currest && $currest==$elapsed);

            $fixfor = '';
            $orig = $currest;
	    $sch_manual_ix++;

            $estimate = new Estimate(/* fake - not in database */ 1,
                $done, $bugid, 
                /*assignto*/ $sch_user->username, 
                /*isbug*/ 0,
                /*task - filled in below*/ '', 
                $orig, $currest, $elapsed, 
                /*resolvedate*/ "1970-01-01", // Want manual bugs to sort first
                $sch_user->username, $current_load);
	    
	    //print "($bugid)($task)<br>\n";
            if (preg_match('/^[0-9]+$/', $bugid))
            {
                // FIXME: make_bug_object
                $bug = bug_get($bugid);
		$sch_db->estimate->remove_estimate($bugid);
                
                $estimate->task = $bug;
		// Fill in manual info we have
		if ($task != "") $estimate->task->name = $task;
		if ($currest != "") $estimate->task->currest = $currest;
		if ($elapsed != "") $estimate->task->elapsed = $elapsed;
		
                $estimate->isbug = 1;
		$estimate->task->manual_ix = $sch_manual_ix;
                $estimate->be_done = $done || $bug->isresolved();
                $estimate->origest = $bug->origest;
                if (!strcmp($estimate->currest,'')) 
                    $estimate->currest = $bug->currest;
                if (!strcmp($estimate->elapsed,'')) 
                    $estimate->elapsed = $bug->elapsed;

                if ((!$done && $currest != $elapsed) || $bug->isresolved())
                {
                    // already listed as not done, *or* really done in fogbugz:
                    // never need to auto-import this bug again.
                    $sch_got_bug[$bugid] = 1;
                }
                $sch_manual_bugs[$bugid] = 1;
            }
            else
            {
                $open = ($elapsed != $currest);
                $sch_db->xtask->max_ix++;
                $estimate->task = new XTask(/*ix*/ $sch_db->xtask->max_ix, 
					    $bugid, $task,
					    /*fixfor - set later*/ -1,
					    /*ixPersonAssignedTo*/ $sch_user->ix,
					    /*my_user*/ $sch_user->username,
					    /*manual_ix*/$sch_manual_ix);
            }

            if (!$done)
                $sch_cur_incomplete_bugs[] = $estimate;
            else
                $sch_cur_complete_bugs[] = $estimate;
        }

        return $ret;
    }
}

return 1;
?>
