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

function bug_list($user, $fixfor, $startdate)
{
    global $bug_h;
    bug_init();
    
    $ffquery = '';
    $userquery = '';

    if ($user)
    {
	$personid = bug_person($user);
	$userquery = "  and (b.ixPersonAssignedTo=$personid or (e.ixPerson=$personid and e.sVerb like 'RESOLVED%')) ";
    }

    if ($fixfor)
      $ffquery = "  and f.sFixFor = '$fixfor' ";
    
    $query = "select distinct " . 
      "b.ixBug,sStatus,sTitle,hrsOrigEst,hrsCurrEst,hrsElapsed " .
      "from Bug as b, BugEvent as e, FixFor as f, Status as s " .
      "where e.ixBug=b.ixBug " .
      "  and f.ixFixFor=b.ixFixFor " .
      "  and s.ixStatus=b.ixStatus " .
      "  and e.dt >= '$startdate' " .
      $userquery .
      $ffquery .
      "  order by sFixFor, ixPriority, ixBug ";
    $result = mysql_query($query, $bug_h);
    if (!$result)
      print mysql_error($bug_h);
    #print "(($query))";
    
    $a = array();
    while ($row = mysql_fetch_row($result))
    {
	$bug = array_shift($row);
	$status = array_shift($row);
	if ($status != "ACTIVE")
	  $row[3] = $row[2]; # elapsed = estimate; bug is done!
	$a[$bug] = $row;
    }
    
    return $a;
}

function bug_get($bugid)
{
    global $bug_h;
    
    bug_init();
    $result = mysql_query("select sTitle,hrsOrigEst,hrsCurrEst,hrsElapsed " .
			  "from Bug " .
			  "where ixBug=" . ($bugid+0),
			  $bug_h);
    $row = mysql_fetch_row($result);
    
    if (!$row)
      return array($bugid, 0, 0, 0);
    else
    {
	$row[0] = "<a href='http://nits/FogBUGZ3/?$bugid'>(BuG:$bugid)</a> " .
	           $row[0];
	return $row;
    }
}

function bug_title($bugid)
{
    #$bug = bug_get($bugid);
    #return $bug[0];
    return "<a href='http://nits/FogBUGZ3/?$bugid'>$bugid</a>";
}


function sch_parse_day($day)
{
    if (preg_match(",(....)/(..)/(..),", $day, $a))
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
    if ($hours < 0)
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
    if ($task)
      $ret .= "<td>$feat</td><td>$task</td>";
    else
      $ret .= "<td colspan=2>$feat</td>";
    return $ret . "<td>" .
	     join("</td><td>", array($orig, $curr, $elapsed, $left, $due)) .
      "</td></tr>";
}

function sch_line($feat, $task, $_orig, $_curr, $_elapsed)
{
    global $sch_curday, $sch_bugs, $sch_got_bug;
    
    if (preg_match('/^[0-9]+$/', $feat))
    {
	$sch_got_bug[$feat] = 1;
	$feat = bug_title($feat);
    }
    
    $orig = sch_parse_period($_orig);
    $curr = sch_parse_period($_curr);
    $elapsed = sch_parse_period($_elapsed);
    
    $remain = $curr - $elapsed;
    if (!$remain && $curr)
      $remain = 'done';
    else if (!$remain)
      $remain = '';
    else
      $remain = sch_period($remain);
    
    $sch_curday = sch_add_hours($sch_curday, $curr);
    #$due = sprintf("%.1f", $sch_curday);
    $due = sch_format_day($sch_curday);
    
    return sch_genline($feat, $task,
		       sch_period($orig), sch_period($curr),
		       sch_period($elapsed), $remain,
		       $due);
}

function sch_extrabugs($user, $fixfor)
{
    global $sch_got_bug, $sch_start;
    
    $start = sch_format_day($sch_start);
    
    $a = bug_list($user, $fixfor, $start);
    foreach ($a as $bugid => $bug)
    {
	if (!$sch_got_bug[$bugid])
	    $ret .= sch_line($bugid, $bug[0], $bug[1], $bug[2], $bug[3]);
    }
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
	$ret .= sch_line("START", "", '', '', '');
    }
    else if ($words[0] == "LOADFACTOR")
    {
	$loadtmp = $words[1] + 0.0;
	if ($loadtmp < 1.0)
	  $ret .= "(INVALID LOAD FACTOR:'$words[1]')";
	else
	  $sch_load = $loadtmp;
	
	#$ret .= sch_line("LOADFACTOR", "= $sch_load", '', '', '');
	$ret .= sch_fullline("(Load factor = $sch_load)");
    }
    else if ($words[0] == "FIXFOR")
    {
	$ret .= sch_extrabugs($sch_user, $words[1]);
    }
    else if ($words[0] == "MILESTONE" || $words[0] == "RELEASE")
    {
	$msname = $words[1];
	$msdue = $words[2];
	
	$ret .= sch_extrabugs($sch_user, $msname);
	
	$newday = sch_parse_day($msdue);
	$slip = ($newday-$sch_curday)*8 / $sch_load;
	$ret .= sch_line("SLIPPAGE", "", $slip, $slip, 0);
	if (round($newday) != round($sch_curday))
	  $ret .= "(EEK! MILESTONE ROUNDING ERROR! $newday!=$sch_curday)";
	#$ret .= sch_line($sch_curday, sch_format_day($sch_curday), '', '', '');
	$ret .= sch_line("<b>$words[0]: $msname</b><br>&nbsp;", '', 0,0,0);
    }
    else if ($words[0] == "END")
    {
	$ret .= sch_extrabugs($sch_user, '');
	
	$ret .= sch_line("END", "", '', '', '');
	$ret .= "</table>";
    }
    else
    {
	$bug = $words[0];
	$task = $words[1];
	$est = $words[2];
	$elapsed = $words[3];
	  
	$ret .= sch_line($bug, $task, $est, $est, $elapsed);
    }

    return $ret;
}

?>
