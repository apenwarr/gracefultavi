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
	return array_keys($this->a);
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
    var $bounce_date;
    var $deleted;
    var $project;
    var $release_date;
    
    function FixFor($a, $b, $c, $d, $e, $f)
    {
	$this->ix = $a;
	$this->name = $b;
	$this->bounce_date = $c ? $c : "2099-09-09"; // no bounce date? in the future...
	$this->deleted = $d;
	$this->project = $e;
	$this->release_date = $f ? $f : "2099-09-09"; // no release date? in the future...;
    }
    
    // returns true if this is due before the fixfor $f.
    function due_before($f)
    {
	return strcmp($this->release_date, $f->release_date) <= 0;
    }
}


// To sort releases by next bounce date rather than by the release date,
// change this function to reference the bounce_date.
// PHP can't seem to handle it if this is a member function...
function fixfor_compare($a, $b)
{
    if ($a->release_date > $b->release_date)
        return 1;
    else if ($a->release_date < $b->release_date)
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
	    ("SELECT ff.ixFixFor, ff.sFixFor, " . 
             "    IFNULL(m.dtDue, ff.dt) bouncedate, " .
	     "    ff.bDeleted, ff.ixProject, ff.dt releasedate " .
	     "FROM FixFor ff " .
	     "    LEFT OUTER JOIN schedulator.Milestone m " .
	     "    ON ff.sFixFor=m.sMilestone AND m.nSub>0 " .
	     "    AND m.dtDue>=now() " .
	     "ORDER BY ff.ixFixFor, bouncedate");
	$old_ixFixFor = -1;
	while ($r = mysql_fetch_row($res))
	{
	    // Only take the first bounce date for each fixfor.
	    if ($old_ixFixFor != $r[0])
	    {
		$p[$r[0]] = new FixFor($r[0], $r[1], $r[2], $r[3],
				       $r[4] >= 0 ? $projects->a[$r[4]] : '',
				       $r[5]);
		$old_ixFixFor = $r[0];
	    }
	}
	uasort($p, "fixfor_compare");
	$this->FogTable($p);
    }
    
    // return the last fixfor with a due date <= the given date.
    function last_before($date)
    {
	foreach ($this->a as $f)
	{
	    if (!$date || strcmp($f->release_date, $date) <= 0)
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
    var $category;
    var $manual_ix;
    
    function Bug($a, $b, $c, $d, $e, $f, $g, $h,
		 $i, $j, $k, $l, $m, $n, $o, $p, $q, $r,
		 $_manual_ix=0)
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
	$this->category = $r;
	$this->manual_ix = $_manual_ix;
	
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
	  "title=\"Bug #$this->ix: " . htmlentities($title) 
	    . "\">$this->ix</a>";
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
    $ares = (isset($a->resolved_byme) && $a->resolved_byme && $a->resolvedate)
	      ? $a->resolvedate : "2099-09-09";
    $bres = (isset($b->resolved_byme) && $b->resolved_byme && $b->resolvedate)
	      ? $b->resolvedate : "2099-09-09";
    $aix =  $a->manual_ix == 0 ? $a->ix : $a->manual_ix;
    $bix =  $b->manual_ix == 0 ? $b->ix : $b->manual_ix;

    if ($fixcmp)
        return $fixcmp;
    else if ($ares != $bres)
        return strcmp($ares, $bres);
    else if ($a->get_priority() != $b->get_priority())
        return $a->get_priority() - $b->get_priority();
    else
        return $aix - $bix;
}


class BugTable extends FogTable
{
    var $resolved_tasks;
    var $persons;
    var $projects;
    var $fixfors;
    var $my_userix;

    // resolved_tasks is an array of:
    //      ixBug => (ixBug, ixPersonWhoResolved, dtResolved)
    function BugTable($where, $_my_userix, $_resolved_tasks,
		      $_persons, $_projects, $_fixfors)
    {
        $this->resolved_tasks = $_resolved_tasks;
        $this->persons = $_persons;
        $this->projects = $_projects;
        $this->fixfors = $_fixfors;
        $this->my_userix = $_my_userix;
	$p = array();
	$res = bug_query($this->get_query($where));
	while ($r = mysql_fetch_row($res))
	{
            //print "Creating bug ".$r[0]."<br>\n";
	    $p[$r[0]] = $this->create_bug_from_db_row($r);
            //print_r($p[$r[0]]);
	}
	uasort($p, "bug_compare");
	$this->FogTable($p);
    }

    // Returns the query needed to get all required FogBugz data for creating
    // Bug objects.
    function get_query($where)
    {
        return "select ixBug, fOpen, dtOpened, sTitle, ixProject, ixArea, " .
        "    ixPersonOpenedBy, ixPersonAssignedTo, ixStatus, ixPriority, " .
        "    ixFixFor, hrsOrigEst, hrsCurrEst, hrsElapsed, ixCategory " .
        "  from Bug " .
        "  $where ";
    }

    function create_bug($bugid)
    {
        $r = bug_onerow($this->get_query(" where ixBug = $bugid"));
        if (!$r)
        {
            // FIXME: Freak out, or make something up
            print "Error: Could not find bug $bugid in the database.<br>\n";
        }
	return $this->create_bug_from_db_row($r);
    }

    // Takes an array that is the contents of a single row of a get_query()
    // query, and returns a new Bug object.
    function create_bug_from_db_row($r)
    {
        $resolved_byme = $this->resolved_tasks[$r[0]][1];
        $resolved_date = $this->resolved_tasks[$r[0]][2];

        return new Bug($r[0], $r[1], $r[2], $r[3],
		       $this->projects->a[$r[4]],
		       $r[5], $this->persons->a[$r[6]],
		       $this->persons->a[$r[7]], $r[8], $r[9],
		       $this->fixfors->a[$r[10]], $r[11], $r[12], $r[13],
		       $resolved_date, $resolved_byme != '' ? 1 : 0,
		       $this->persons->a[$this->my_userix],
		       $r[14]);
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
    var $manual_ix;
    
    function XTask($a, $b, $c, $d, $e, $f, 
		   $_manual_ix=0)
    {
	$this->ix = $a;
	$this->task = $b;
	$this->name = $c;
	$this->fixfor = $d;
	$this->assignto = $e;
	$this->my_user = $f;
	$this->manual_ix = $_manual_ix;
    }
    
    function isdone()
        { return $this->my_user != $this->assignto; }
    
    function get_priority()
        { return 7; } // currently XTask is always low-priority
    
    function hyperlink()
    { 
	if ($this->manual_ix > 0)
	    return $this->task;
	else 
	    return "TM#".$this->ix; 
    }
    
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
    var $max_ix;

    function XTaskTable($where, $my_userix, $persons, $fixfors)
    {
        $this->max_ix = 0;
	$p = array();
	$res = bug_query
	  ("select ixXTask, sTask, sSubTask, ixFixFor, ixPersonAssignedTo " .
	   "  from schedulator.XTask $where");
	while ($r = mysql_fetch_row($res))
        {
            $p[$r[0]] = &new XTask($r[0], $r[1], $r[2], $fixfors->a[$r[3]],
                                $persons->a[$r[4]], $persons->a[$my_userix]);
            if ($r[0] > $this->max_ix)
                $this->max_ix = $r[0];
        }
	uasort($p, "bug_compare");
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
    var $resolvedate;
    var $my_user;
    var $loadfactor;
    var $id;
    
    function Estimate($fake, $be_done, $aa, $a, $b, $c, $d, $e, $f, $g, $h, 
        $loadfactor = -1)
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
        $this->loadfactor = $loadfactor;
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
	if ($this->origest !== '')
            return $this->origest;
	else if ($this->isbug && $this->task->isresolved() && !$this->task->resolved_byme)
            return 0.01; // FIXME: for broken old-style schedulator weirdness
	else if ($this->isbug)
            return $this->task->origest;
	else
            return 0;
    }
    
    function est_curr()
    {
	if ($this->currest !== '')
            return $this->currest;
	else if ($this->isbug && $this->task->isresolved() && !$this->task->resolved_byme)
            return 0.01; // FIXME: for broken old-style schedulator weirdness
	else if ($this->isbug)
            return $this->task->currest;
	else
            return 0;
    }
    
    function est_elapsed()
    {
	if ($this->elapsed !== '')
            return $this->elapsed;
	else if ($this->isbug && $this->task->isresolved() && !$this->task->resolved_byme)
            return 0.009; // FIXME: for broken old-style schedulator weirdness
	else if ($this->isbug)
            return $this->task->elapsed;
	else
            return 0;
    }
    
    function est_remain()
    {
	if ($this->isestimated())
            return $this->est_curr() - $this->est_elapsed();
	else
            return 0;
    }

    function isestimated()
    {
	return $this->est_curr() !== '' && $this->est_elapsed() !== '';
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
	if (!$this->isbug)
	{
	    if($this->task->manual_ix > 0)
		return $this->task->name;
	    else
		return $this->task->task.": ".$this->task->name;
	}
	else if ($this->isbug && $this->task->isresolved() 
		 && !$this->isdone())
            return "VERIFY: " . $this->task->name;
	else
            return $this->task->name;
    }
}


// compare two Estimate objects for sorting purposes.
function estimate_compare($a, $b)
{
    if ($a->isdone() != $b->isdone())
        return $b->isdone() - $a->isdone(); // done before not done
    else if ($a->isdone() && $a->get_resolvedate() != $b->get_resolvedate())
        return strcmp($a->get_resolvedate(), $b->get_resolvedate());
    else
        return bug_compare($a->task, $b->task);
}


// compare two Estimate objects for sorting purposes.  This one only sorts
// by FixFor and ix, so two estimates should *always* stay in order even if
// you finish/reopen them.
function estimate_compare_fixfor_only($a, $b)
{
    $ffcmp = fixfor_compare($a->task->fixfor, $b->task->fixfor);
    if ($ffcmp)
        return $ffcmp;
    else if ($a->task->ix != $b->task->ix)
        return $a->task->ix - $b->task->ix;
    else
        return $a->ix - $b->ix;
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
	    $bugix = $r[3];
	    $bug = $isbug ? $bugs->a[$bugix] : $xtasks->a[$bugix];
	    if (!$bug)
	    {
		// this situation is actually normal: the above query doesn't
		// know the fixfor of any of the estimates it reads, so it
		// should just skip any estimate that has an unknown bug;
		// that bug probably wasn't loaded because it doesn't match
		// the requested fixfor filter anyway, so skipping the
		// estimate is the right thing to do.
		//
		// print "(WARNING: bug '$isbug'-'$bugix' not found!) ";
		continue;
	    }
	    $done = $r[5] == $r[6] && $bug->assignto->ix != $my_userix;
	    $e = &new Estimate(0, // not fake
			      $done,
			      $r[0], $persons->a[$r[1]], $isbug,
			      $bug,
			      $r[4], $r[5], $r[6], $r[7],
			      $me);
	    $p[$r[0]] = $e;
	    if ($e->isbug)
		$did_bug[$bug->ix] = 1;
	    else
		$did_xtask[$bug->ix] = 1;
	}
	
	foreach ($bugs->a as $bug)
	{
	    if (!$did_bug[$bug->ix])
	    {
		$p[] = &new Estimate(1, // fake (not in database)
				     $bug->isdone(),
				     "", $me, 1,
				     $bug, "", "", "", $bug->resolvedate,
				     $me);
	    }
	}
	
	foreach ($xtasks->a as $task)
	{
	    if (!$did_xtask[$task->ix])
	    {
		$p[] = &new Estimate(1, // fake (not in database)
				     $task->isdone(),
				     "", $me, 0, 
				     $task, "", "", "", "",
				     $me);
	    }
	}
	$this->FogTable($p);
	$this->do_sort();
    }
    
    function do_sort()
    {
	uasort($this->a, "estimate_compare");
    }

    function remove_estimate($bugid)
    {
	foreach ($this->a as $i=>$e)
	    if ($this->a[$i]->task->ix == $bugid)
		unset($this->a[$i]);
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
            $ret[$row[0]] = $row[0];
	else if (count($row) == 2)
            $ret[$row[0]] = $row[1];
	else
            $ret[$row[0]] = $row;
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
    
    function FogTables($userix, $fixforix, $start_date='1970-01-01')
    {
	$this->person = new PersonTable();
	$this->project = new ProjectTable($this->person);
	$this->fixfor = new FixForTable($this->project);
	
	// clean up the fixforix
	if (!$fixforix || $fixforix==1)
	    $fixforix = 0;
	$f = $this->fixfor->a[$fixforix];
	if (!$f)
	    $f = $this->fixfor->first("name", $fixforix);
	if (!$f)
	    $f = $this->fixfor->first_prefix("name", $fixforix);
	if ($f)
	    $fixforix = $f->ix;
	if (!$fixforix || $fixforix==1)
	    $fixforix = 0;
	$and_fixfor = $fixforix>0 ? "and ixFixFor=$fixforix" : "";

	// clean up the userix
	if (!$userix || $userix==1)
	    $userix = 0;
	$p = $this->person->a[$userix];
	if (!$p)
	    $p = $this->person->first("username", $userix);
	if (!$p)
	    $p = $this->person->first("fullname", $userix);
	if ($p)
            $userix = $p->ix;
	if (!$userix || $userix==1)
            $userix = 0;
	$this->my_userix = $userix;
	if ($userix > 0 || $fixforix <= 0) // don't ever get *all* bugs...
	{
	    $and_user = "and ixPerson=$userix";
	    $and_useras = "and ixPersonAssignedTo=$userix";
	    $and_userrb = "and ixPersonResolvedBy=$userix";
	}
	else
	    $and_user = $and_useras = $and_userrb = "";
	$and_dtresolved = "and (dtResolved>'$start_date' or ISNULL(dtResolved))"; 
 
	$whichbugs = sql_simple("select ixTask from schedulator.Estimate " .
				"    where 1 $and_user " .
				"      $and_dtresolved " .
				"      and fIsBug=1");
	$whichbugs += sql_simple("select ixBug from Bug " .
				 "    where 1 $and_useras " .
				 "     $and_fixfor");
	
	// this mess is referred to by the mysql people as the 'max-concat'
	// trick.  It returns the list of bug numbers that are currently
	// resolved *and* the most recent person to resolve them was the
	// specified user.
	$q = 
	  ("select e.ixBug, " .
	   "   substring(max(concat(lpad(ixBugEvent,12,' '),ixPerson)),13) " .
	   "       as ixPersonResolvedBy, " .
	   "   substring(max(concat(lpad(ixBugEvent,12,' '),dt)),13) " .
	   "       as dtResolved " .
	   "  from BugEvent e, Bug b " .
	   "  where e.ixBug=b.ixBug " .
	   "    and b.ixStatus>1 and sVerb like 'RESOLVED%' " .
	   "    and sVerb != 'Resolved (Again)' " .
	   "    $and_fixfor " .
	   "  group by e.ixBug " .
	   "  having 1 $and_userrb" .
	   "    $and_dtresolved ");
	$resolved_tasks = sql_simple($q);
	
	$whichbugs += array_keys($resolved_tasks);
	
	$whichtasks = sql_simple("select ixTask from schedulator.Estimate " .
				 "    where 1 $and_user " .
				 "      $and_dtresolved " .
				 "      and fIsBug=0");
	$whichtasks += sql_simple("select ixXTask from schedulator.XTask " .
				  "    where 1 $and_useras " .
				  "      $and_fixfor");
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
	$this->estimate = new EstimateTable("where 1 $and_user $and_dtresolved",
					    $userix,
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
