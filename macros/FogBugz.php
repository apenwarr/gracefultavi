<?php

class FogTable
{
    var $a; // the array we're using
    
    function FogTable(&$new_a)
    {
	$this->a = $new_a;
    }
    
    // return an anonymous function that retrieves a particular field
    // from the given object.  used by first().
    function fieldfunc($field)
    {
	return create_function('&$x', "return \$x->$field;");
    }
    
    // return the keys of the array, in order
    function keys()
    {
	return array_keys($a);
    }
    
    // return the first item where the given field equals the given value.
    function first($field, $value)
    {
	$func = FogTable::fieldfunc($field);
	foreach ($this->a as $v)
	    if ($func($v) == $value)
	      return $v;
	return $notdef;
    }
    
    // return the first item where the given field starts with the given value.
    function first_prefix($field, $prefix)
    {
	$func = FogTable::fieldfunc($field);
	foreach ($this->a as $v)
	    if (substr($func($v), 0, strlen($prefix)) == $prefix)
	      return $v;
	return $notdef;
    }

    // return the first item where the given field matches the given regex.
    function first_match($field, $regex)
    {
	$func = FogTable::fieldfunc($field);
	foreach ($this->a as $v)
	    if (preg_match($regex, $func($v)))
	        return $v;
	return $notdef;
    }
}


class Person
{
    var $ix;
    var $fullname;
    var $email;
    var $username;
    var $deleted;
    
    function Person($a, $b, $c, $d)
    {
	$this->ix = $a;
	$this->fullname = $b;
	$this->email = $c;
	$this->username = preg_replace("/@.*/", "", $c);
	$this->deleted = $d;
    }
}


class PersonTable extends FogTable
{
    function PersonTable()
    {
	$p = array();
	$res = bug_query("select ixPerson, sFullName, sEmail, fDeleted " .
			 "  from Person");
	while ($r = mysql_fetch_row($res))
	  $p[$r[0]] = new Person($r[0], $r[1], $r[2], $r[3]);
	$this->FogTable($p);
    }
}


class Project
{
    var $ix;
    var $name;
    var $owner;
    var $deleted;
    
    function Project($a, $b, $c, $d)
    {
	$this->ix = $a;
	$this->name = $b;
	$this->owner = $c;
	$this->deleted = $d;
    }
}


class ProjectTable extends FogTable
{
    function ProjectTable($persons)
    {
	$p = array();
	$res = bug_query
	  ("select ixProject, sProject, ixPersonOwner, fDeleted " .
	   "  from Project");
	while ($r = mysql_fetch_row($res))
	  $p[$r[0]] = new Project($r[0], $r[1], $persons->a[$r[2]], $r[3]);
	$this->FogTable($p);
    }
}


class FixFor
{
    var $ix;
    var $name;
    var $date;
    var $deleted;
    var $project;
    
    function FixFor($a, $b, $c, $d, $e)
    {
	$this->ix = $a;
	$this->name = $b;
	$this->date = $c ? $c : "2099-09-09"; // no due date? in the future...
	$this->deleted = $d;
	$this->project = $e;
    }
    
    // returns true if this is due before the fixfor $f.
    function due_before($f)
    {
	return strcmp($this->date, $f->date) <= 0;
    }
}


// php can't seem to handle it if this is a member function...
function fixfor_compare($a, $b)
{
//    $adate = $a->date ? $a->date : "2099-09-09";
//    $bdate = $b->date ? $b->date : "2099-09-09";
    
    if ($a->date > $b->date)
      return 1;
    else if ($a->date < $b->date)
      return -1;
    else
      return strcmp($a->name, $b->name);
}


class FixForTable extends FogTable
{
    function FixForTable($projects)
    {
	$p = array();
	$res = bug_query
	  ("select ixFixFor, sFixFor, dt, bDeleted, ixProject " .
	   "  from FixFor");
	while ($r = mysql_fetch_row($res))
	  $p[$r[0]] = new FixFor($r[0], $r[1], $r[2], $r[3],
				 $projects->a[$r[4]]);
	uasort($p, "fixfor_compare");
	$this->FogTable($p);
    }
    
    // return the last fixfor with a due date <= the given date.
    function last_before($date)
    {
	foreach ($this->a as $f)
	{
	    if (!$date || strcmp($f->date, $date) <= 0)
	      $match = $f;
	    else
	      return $match; // passed the last one
	}
	
	return $match;
    }
}


class Bug
{
    var $ix;
    var $open;
    var $opendate;
    var $name;
    var $project;
    var $area;
    var $openby;
    var $assignto;
    var $status;
    var $priority;
    var $fixfor;
    var $origest;
    var $currest;
    var $elapsed;
    var $resolved_byme;
    var $my_user;
    
    function Bug($a, $b, $c, $d, $e, $f, $g, $h,
		 $i, $j, $k, $l, $m, $n, $o, $p, $q)
    {
	$this->ix = $a;
	$this->open = $b;
	$this->opendate = $c;
	$this->name = $d;
	$this->project = $e;
	$this->area = $f;
	$this->openby = $g;
	$this->assignto = $h;
	$this->status = $i;
	$this->priority = $j;
	$this->fixfor = $k;
	$this->origest = $l;
	$this->currest = $m;
	$this->elapsed = $n;
	$this->resolvedate = $o;
	$this->resolved_byme = $p;
	$this->my_user = $q;
	
	if ($this->isresolved())
	  $this->elapsed = $this->currest;
    }
    
    function isopen()
        { return $this->open == 1; }
    
    function isresolved()
        { return $this->status != 1; }
    
    function isactive()
        { return $this->isopen() && !$this->isresolved(); }
    
    function isdone()
        { return $this->isresolved() 
	         && $this->my_user->ix != $this->assignto->ix; }
    
    function get_priority()
        { return $this->priority; }
    
    function hyperlink()
    {
	$title = htmlentities($this->name, ENT_QUOTES);
	return "<a href=\"http://nits/fogbugz3/?$this->ix\" " .
	  "title=\"Bug #$this->ix: $title\">$this->ix</a>";
    }
    
    function finish()
    {
	// FIXME: for safety, I don't want to modify the fogbugz database
	// from inside the ever-changing unstable schedulator code.  So you
	// can't *actually* ask this to finish a fogbugz case.  However,
	// this function is here so that you can go estimate->task->finish()
	// and not get an error regardless of whether the task is a Bug or
	// and XTask.
    }
    
    function assign($personix)
    {
	// see finish()
    }
}


function bug_compare($a, $b)
{
    $fixcmp = fixfor_compare($a->fixfor, $b->fixfor);
    $ares = ($a->resolved_byme && $a->resolvedate)
	      ? $a->resolvedate : "2099-09-09";
    $bres = ($b->resolved_byme && $b->resolvedate)
	      ? $b->resolvedate : "2099-09-09";
    
    if ($ares != $bres)
      return strcmp($ares, $bres);
    else if ($fixcmp)
      return $fixcmp;
    else if ($a->get_priority() != $b->get_priority())
      return $a->get_priority() - $b->get_priority();
    else
      return $a->ix - $b->ix;
}


class BugTable extends FogTable
{
    function BugTable($where, $my_userix, $resolved_byme,
		      $persons, $projects, $fixfors)
    {
	$p = array();
	$res = bug_query
	  ("select ixBug, fOpen, dtOpened, sTitle, ixProject, ixArea, " .
	   "    ixPersonOpenedBy, ixPersonAssignedTo, ixStatus, ixPriority, " .
	   "    ixFixFor, hrsOrigEst, hrsCurrEst, hrsElapsed, dtResolved " .
	   "  from Bug " .
	   "  $where ");
	while ($r = mysql_fetch_row($res))
	  $p[$r[0]] = new Bug($r[0], $r[1], $r[2], $r[3],
			      $projects->a[$r[4]],
			      $r[5], $persons->a[$r[6]],
			      $persons->a[$r[7]], $r[8], $r[9],
			      $fixfors->a[$r[10]], $r[11], $r[12], $r[13],
			      $r[14], $resolved_byme[$r[0]] != '' ? 1 : 0,
			      $persons->a[$my_userix]);
	uasort($p, "bug_compare");
	$this->FogTable($p);
    }
}


class XTask
{
    var $ix;
    var $task;
    var $name;
    var $fixfor;
    var $assignto;
    var $my_user;
    
    function XTask($a, $b, $c, $d, $e, $f)
    {
	$this->ix = $a;
	$this->task = $b;
	$this->name = $c;
	$this->fixfor = $d;
	$this->assignto = $e;
	$this->my_user = $f;
    }
    
    function isdone()
        { return $this->my_user != $this->assignto; }
    
    function get_priority()
        { return 7; } // currently XTask is always low-priority
    
    function hyperlink()
        { return "TM#$this->ix"; }
    
    function assign($personix)
    {
	bug_query
	  ("update schedulator.XTask " .
	   "  set ixPersonAssignedTo=$personix " .
	   "  where ixXTask=$this->ix");
    }
    
    function finish()
    {
	$this->assign(1);
    }
}


class XTaskTable extends FogTable
{
    function XTaskTable($where, $my_userix, $persons, $fixfors)
    {
	$p = array();
	$res = bug_query
	  ("select ixXTask, sTask, sSubTask, ixFixFor, ixPersonAssignedTo " .
	   "  from schedulator.XTask $where");
	while ($r = mysql_fetch_row($res))
	  $p[$r[0]] = new XTask($r[0], $r[1], $r[2], $fixfors->a[$r[3]],
				$persons->a[$r[4]], $persons->a[$my_userix]);
	$this->FogTable($p);
    }
}


class Estimate
{
    var $fake;
    var $be_done;
    var $ix;
    var $assignto;
    var $isbug;
    var $task;
    var $origest;
    var $currest;
    var $elapsed;
    var $id;
    var $resolvedate;
    var $my_user;
    
    function Estimate($fake, $be_done, $aa, $a, $b, $c, $d, $e, $f, $g, $h)
    {
	$this->fake = $fake;
	$this->be_done = $be_done;
	$this->ix = $aa;
	$this->assignto = $a;
	$this->isbug = $b;
	$this->task = $c;
	$this->origest = $d;
	$this->currest = $e;
	$this->elapsed = $f;
	$this->resolvedate = $g;
	$this->my_user = $h;
	if (!$this->isbug)
	  $this->id = "TM#" . $this->task->ix;
	else
	  $this->id = $this->task->ix;
    }
    
    function get_resolvedate()
    {
	return $this->resolvedate ? $this->resolvedate
	  : ($this->isbug ? $this->task->resolvedate : '');
    }
    
    function est_orig()
    {
	if ($this->origest != '')
	  return $this->origest;
	else if ($this->isbug && $this->task->isresolved() && !$this->task->resolved_byme)
	  return 0.01; // FIXME: for broken old-style schedulator weirdness
	else if ($this->isbug)
	  return $this->task->origest;
	else
	  return '';
    }
    
    function est_curr()
    {
	if ($this->currest != '')
	  return $this->currest;
	else if ($this->isbug && $this->task->isresolved() && !$this->task->resolved_byme)
	  return 0.01; // FIXME: for broken old-style schedulator weirdness
	else if ($this->isbug)
	  return $this->task->currest;
	else
	  return '';
    }
    
    function est_elapsed()
    {
	if ($this->elapsed != '')
	  return $this->elapsed;
	else if ($this->isbug && $this->task->isresolved() && !$this->task->resolved_byme)
	  return 0.009; // FIXME: for broken old-style schedulator weirdness
	else if ($this->isbug)
	  return $this->task->elapsed;
	else
	  return '';
    }
    
    function est_remain()
    {
	if ($this->isestimated())
	  return $this->est_curr() - $this->est_elapsed();
	else
	  return '';
    }

    function isestimated()
    {
	return $this->est_curr() != '' && $this->est_elapsed() != '';
    }
    
    function isdone()
    {
	return $this->be_done;
    }
    
    function update($currest, $elapsed)
    {
	// make sure they're valid numbers
	if ($currest == 0)
	  $currest = 0;
	if ($elapsed == 0)
	  $elapsed = 0;
	
	$userix = $this->assignto->ix;
	$taskix = $this->task->ix;
	
	$u = $this->assignto;
	bug_query
	  ("insert ignore into schedulator.Estimate " .
	   "   (ixPerson, fIsBug, ixTask) " .
	   "   values ($userix, $this->isbug, $taskix)");
	
	if (!$this->origest)
	  $this->origest = $currest;
	$this->currest = $currest;
	$this->elapsed = $elapsed;
	$this->be_done = ($this->currest == $this->elapsed);
	if ($this->be_done && $this->task->assignto->ix == $this->my_user->ix)
	  $this->task->finish();
	else
	  $this->task->assign($this->my_user->ix);
	
	$resolv = $this->est_remain() 
	  ? "dtResolved=null, " : "dtResolved=now(), ";
	bug_query
	  ("update schedulator.Estimate " .
	   "  set $resolv " .
	   "      hrsOrigEst=$this->origest, hrsCurrEst=$this->currest, " .
	   "      hrsElapsed=$this->elapsed " .
	   "  where fIsBug=$this->isbug and ixTask=$taskix and " .
	   "        ixPerson=$userix ");
    }
    
    function nice_title()
    {
	if ($this->isbug && $this->task->isresolved() && !$this->task->resolved_byme)
	  return "VERIFY: " . $this->task->name;
	else
	  return $this->task->name;
    }
}


function estimate_compare($a, $b)
{
    if ($a->isdone() != $b->isdone())
      return $b->isdone() - $a->isdone(); // done before not done
    else if ($a->isdone() && $a->get_resolvedate() != $b->get_resolvedate())
      return strcmp($a->get_resolvedate(), $b->get_resolvedate());
    else
      return bug_compare($a->task, $b->task);
}


class EstimateTable extends FogTable
{
    function EstimateTable($where, $my_userix, $persons, $bugs, $xtasks)
    {
	$p = array();
	$did_bug = array();
	$did_xtask = array();
	$me = $persons->a[$my_userix];
	
	$res = bug_query
	  ("select ixEstimate, ixPerson, fIsBug, ixTask, " .
	   "    hrsOrigEst, hrsCurrEst, hrsElapsed, dtResolved " .
	   "  from schedulator.Estimate " .
	   "  $where ");
	while ($r = mysql_fetch_row($res))
	{
	    $isbug = $r[2];
	    $bug = $isbug ? $bugs->a[$r[3]] : $xtasks->a[$r[3]];
	    $done = $r[5] == $r[6] && $bug->assignto->ix != $my_userix;
	    $e = &new Estimate(0, // not fake
			      $done,
			      $r[0], $persons->a[$r[1]], $r[2],
			      $bug,
			      $r[4], $r[5], $r[6], $r[7],
			      $me);
	    $p[$r[0]] = $e;
	    if (!$e->isdone() || ($e->isdone() && $bug->isdone()))
	    {
		if ($e->isbug)
		  $did_bug[$bug->ix] = 1;
		else
		  $did_xtask[$bug->ix] = 1;
	    }
	}
	
	foreach ($bugs->a as $bug)
	{
	    if (!$did_bug[$bug->ix])
	      $p[] = &new Estimate(1, // fake (not in database)
				  $bug->isdone(),
				  "", $me, 1,
				  $bug, "", "", "", $bug->resolvedate,
				  $me);
	}
	
	foreach ($xtasks->a as $task)
	{
	    if (!$did_xtask[$task->ix])
	      $p[] = &new Estimate(1, // fake (not in database)
				  $task->isdone(),
				  "", $me, 0, 
				  $task, "", "", "", "",
				  $me);
	}
	
	$this->FogTable($p);
	$this->do_sort();
    }
    
    function do_sort()
    {
	uasort($this->a, "estimate_compare");
    }
}


function sql_simple($query)
{
    global $bug_h;
    $result = mysql_query($query, $bug_h);
    if (!$result)
      print(mysql_error($bug_h));
    
    $ret = array();
    while ($row = mysql_fetch_row($result))
    {
	if (count($row) == 1)
	  array_push($ret, $row[0]);
	else if (count($row) == 2)
	  $ret[$row[0]] = $row[1];
    }
    
    return $ret;
}
    

class FogTables
{
    var $my_userix;
    
    var $person;
    var $project;
    var $fixfor;
    var $bug;
    var $xtask;
    var $estimate;
    
    function FogTables($userix)
    {
	if (!$userix)
	  $userix = 0;
	
	$this->person = new PersonTable();
	$this->project = new ProjectTable($this->person);
	$this->fixfor = new FixForTable($this->project);
	
	$this->my_userix = $userix;
	$p = $this->person->a[$userix];
	if (!$p)
	  $p = $this->person->first("username", $userix);
	if (!$p)
	  $p = $this->person->first("fullname", $userix);
	if ($p)
	  $userix = $p->ix;
	$whichbugs = sql_simple("select ixTask from schedulator.Estimate " .
				"    where ixPerson=$userix and fIsBug=1");
	$whichbugs += sql_simple("select ixBug from Bug " .
				 "    where ixPersonAssignedTo=$userix");
	
	// this mess is referred to by the mysql people as the 'max-concat'
	// trick.  It returns the list of bug numbers that are currently
	// resolved *and* the most recent person to resolve them was the
	// specified user.
	$resolved_tasks = sql_simple
	  ("select e.ixBug, " .
	   "    substring(max(concat(lpad(ixBugEvent,12,' '),ixPerson)),13) " .
	   "       as ixPersonResolvedBy " .
	   "  from BugEvent e, Bug b " .
	   "  where e.ixBug=b.ixBug " .
	   "    and b.ixStatus>1 and sVerb like 'RESOLVED%' " .
	   "    and sVerb != 'Resolved (Again)' " .
	   "  group by e.ixBug " .
	   "  having ixPersonResolvedBy=$userix");
	
	$whichbugs += array_keys($resolved_tasks);
	
	$whichtasks = sql_simple("select ixTask from schedulator.Estimate " .
				 "    where ixPerson=$userix and fIsBug=0");
	$whichtasks += sql_simple("select ixXTask from schedulator.XTask " .
				  "    where ixPersonAssignedTo=$userix");
	
	$bugwhere = count($whichbugs) 
	  ? " where ixBug in (" . join(",", $whichbugs) . ")"
	  : " where 1=0";
	$taskwhere = count($whichtasks)
	  ? " where ixXTask in (" . join(",", $whichtasks) . ")"
	  : " where 1=0";
	
	$this->bug = new BugTable($bugwhere, $userix, $resolved_tasks,
				  $this->person,
				  $this->project, $this->fixfor);
	$this->xtask = new XTaskTable($taskwhere, $userix,
				      $this->person, $this->fixfor);
	$this->estimate = new EstimateTable("where ixPerson=$userix", $userix,
					    $this->person,
					    $this->bug, $this->xtask);
    }
}


// this is silly, but it's here so wiki won't get confused.  There is not
// actually a FogBugz macro.
class Macro_FogBugz
{
    // main gracefultavi entry point
    function parse($args, $page)
    {
    }
}


return 1;
?>
