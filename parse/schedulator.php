<?php

global $sch_user;
global $sch_start;
global $sch_load;
global $sch_bugs;
global $sch_curday;
global $sch_got_bug;
global $bug_h;

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

function bug_list($user, $fixfor, $startdate, $enddate)
{
    global $bug_h;
    bug_init();
    
    $ffquery = '';
    $userquery = '';
    $endquery = '';

    if ($fixfor and $enddate)
      $ffquery = "  and (f.sFixFor = '$fixfor' or " .
                   "(f.dt is not null and f.dt <= '$enddate')) ";
    else if ($fixfor)
      $ffquery = "  and f.sFixFor = '$fixfor' ";
    
    # any bug opened before the end date counts toward that deadline, even
    # if the deadline has passed and the bug isn't done.
    if ($enddate)
      $endquery = "  and b.dtOpened < '$enddate' ";
    
    # We want to get all bugs resolved by the user after the given start
    # date, *and* all bugs currently assigned to the user.
    if ($user)
    {
	$personid = bug_person($user);
	$userquery = "  and (b.ixPersonAssignedTo=$personid " .
	  " or (e.ixPerson=$personid and e.sVerb like 'RESOLVED%' " .
	  "     and e.dt >= '$startdate')) ";
    }

    $query = "select distinct " . 
      "b.ixBug,sStatus,sTitle,hrsOrigEst,hrsCurrEst,hrsElapsed " .
      "from Bug as b, BugEvent as e, FixFor as f, Status as s " .
      "where e.ixBug=b.ixBug " .
      "  and f.ixFixFor=b.ixFixFor " .
      "  and s.ixStatus=b.ixStatus " .
      $endquery .
      $userquery .
      $ffquery .
      "  order by f.dt, sFixFor, ixPriority, ixBug ";
    #print "(($query))<p>";
    $result = mysql_query($query, $bug_h);
    if (!$result)
      print mysql_error($bug_h);
    
    $a = array();
    while ($row = mysql_fetch_row($result))
    {
	$bug = array_shift($row);
	$status = array_shift($row);
	if ($status != "ACTIVE")
	{
	    # the bug is done, so don't give it any more time remaining
	    if (!$row[2])
	      $row[2] = 0.01;  # nonzero so we know the 'remaining' is accurate
	    $row[3] = $row[2]; # elapsed = estimate; bug is done!
	}
	$a[$bug] = $row;
    }
    
    return $a;
}

function bug_get($bugid)
{
    global $bug_h;
    
    bug_init();
    $result = mysql_query("select sTitle,hrsOrigEst,hrsCurrEst,hrsElapsed, " .
			  "    sStatus " .
			  "from Bug as b, Status as s " .
			  "where s.ixStatus=b.ixStatus " .
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


function sch_parse_day($day)
{
    if ($day == '')
        return 0;
    else if (preg_match(",(....)[/-](..)[/-](..),", $day, $a))
	return mktime(0,0,0, $a[2], $a[3], $a[1])/24/60/60;
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
    return strftime("%Y/%m/%d", $day*24*60*60);
}

function sch_add_hours($day, $hours)
{
    global $sch_load;
    
    if ($hours < 0)
      return $day;  # never subtract hours from the date!
    
    # FIXME: deal with "working days" vs. weekends
    return $day + ($hours * $sch_load) / 8.0;
}

# returns a time in hours
function sch_parse_period($str)
{
    #return "($str)";
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
    #return $hours;
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

function sch_genline($feat, $task, $orig, $curr, $elapsed, $left, $due)
{
    $ret = "<tr>";
    
    $junk1 = $junk2 = '';
    if ($left == "done")
    {
	$junk1 = "<strike><font color=grey><i>";
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

function sch_line($feat, $task, $_orig, $_curr, $_elapsed, $done)
{
    global $sch_curday, $sch_bugs, $sch_got_bug;
    
    if (preg_match('/^[0-9]+$/', $feat))
    {
	$sch_got_bug[$feat] = 1;
	$bug = bug_get($feat);
	if (!$task)     $task = $bug[0];
	if (!$_orig)    $_orig = $bug[1];
	if (!$_curr)    $_curr = $bug[2];
	if (!$_elapsed) $_elapsed = $bug[3];
	if (!$done && $bug[4] != 'ACTIVE') $done = 1;
	$feat = bug_link($feat);
    }
    
    $orig = sch_parse_period($_orig);
    $curr = sch_parse_period($_curr);
    $elapsed = sch_parse_period($_elapsed);
    
    $remain = $curr - $elapsed;
    
    if (!$remain && $curr)
      $done = 1;

    if ($done)
      $sremain = 'done';
    else if (!$remain)
      $sremain = '';
    else
      $sremain = sch_period($remain);
    
    $sch_curday = sch_add_hours($sch_curday, $curr);
    $due = sch_format_day($sch_curday);
    if ((!$curr || $remain) && floor($sch_curday) < floor(time()/24/60/60))
      $due = "<font color=red>$due</font>";
    
    return sch_genline($feat, $task,
		       sch_period($orig), sch_period($curr),
		       sch_period($elapsed), $sremain,
		       $due);
}

function sch_extrabugs($user, $fixfor, $enddate)
{
    global $sch_got_bug, $sch_start;
    
    $start = sch_format_day($sch_start);
    
    $a = bug_list($user, $fixfor, $start, $enddate);
    foreach ($a as $bugid => $bug)
    {
	if (!$sch_got_bug[$bugid])
	    $ret .= sch_line($bugid, $bug[0], $bug[1], $bug[2], $bug[3], 0);
    }
    return $ret;
}

function sch_milestone($descr, $name, $due)
{
    global $sch_user, $sch_load, $sch_curday;
    
    if (!$due)
      $due = bug_duedate($name);
    if (!$due)
      $tmpdue = sch_format_day($sch_curday);
    else 
      $tmpdue = $due;
	
    $ret .= sch_extrabugs($sch_user, $name, $tmpdue);
    
    if (!$due)
      $due = sch_format_day($sch_curday+1);
    
    $old_curday = $sch_curday;
    
    $newday = sch_parse_day($due);
    $xdue = sch_format_day($newday);
    $slip = ($newday-$sch_curday)*8 / $sch_load;
    $ret .= sch_line("SLIPPAGE (to $xdue)", "", $slip, $slip, 0, 0);
    $ret .= sch_line("<b>$descr: $name</b><br>&nbsp;", '', 0,0,0, 0);
    
    $sch_curday = $old_curday; # slippage doesn't actually take time... right?
    
    return $ret;
}

function view_macro_schedulator($text)
{
    global $sch_start, $sch_curday, $sch_user, $sch_load, $sch_got_bug;
    
    $ret = "";
    
    if (!preg_match_all('/"[^"]*"|[^ 	]+/', $text, $words))
      return "regmatch failed!\n";
    $words = $words[0]; # don't know why I have to do this, exactly...
    
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
	$sch_start = $sch_curday = sch_parse_day($words[2]);
	$sch_load = 1.0;
	$ret .= sch_line("START", "", '', '', '', 0);
    }
    else if ($words[0] == "LOADFACTOR")
    {
	$loadtmp = $words[1] + 0.0;
	if ($loadtmp < 1.0)
	  $ret .= "(INVALID LOAD FACTOR:'$words[1]')";
	else
	  $sch_load = $loadtmp;
	
	$ret .= sch_fullline("(Load factor = $sch_load)");
    }
    else if ($words[0] == "FIXFOR")
    {
	$ret .= sch_extrabugs($sch_user, $words[1], '');
    }
    else if ($words[0] == "SETBOUNCE")
    {
	$cmd = array_shift($words);
	$msname = array_shift($words);
	foreach ($words as $msdue)
	    $ret .= sch_milestone("BOUNCE", $msname, $msdue);
    }
    else if ($words[0] == "MILESTONE" || $words[0] == "RELEASE"
	     || $words[0] == "BOUNCE")
    {
	$msname = $words[1];
	$msdue = $words[2];
	$ret .= sch_milestone($words[0], $msname, $msdue);
    }
    else if ($words[0] == "END")
    {
	$ret .= sch_extrabugs($sch_user, '', '');
	
	$ret .= sch_line("END", "", '', '', '', 0);
	$ret .= "</table>";
    }
    else
    {
	$bug = $words[0];
	$task = $words[1];
	$est = $words[2];
	$elapsed = $words[3];
	  
	$ret .= sch_line($bug, $task, $est, $est, $elapsed, 0);
    }

    return $ret;
}

?>
