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
	$this->date = $c;
	$this->deleted = $d;
	$this->project = $e;
    }
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
	$this->FogTable($p);
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
    
    function Bug($a, $b, $c, $d, $e, $f, $g, $h, $i, $j, $k, $l, $m, $n)
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
    }
    
    function isopen()
        { return $this->open == 1; }
    
    function isresolved()
        { return $this->status != 1; }
    
    function isactive()
        { return $this->isopen() && !$this->isresolved(); }
}


class XTask
{
    var $ix;
    var $task;
    var $name;
    var $fixfor;
    var $assignto;
    
    function XTask($a, $b, $c, $d, $e)
    {
	$this->ix = $a;
	$this->task = $b;
	$this->name = $c;
	$this->fixfor = $d;
	$this->assignto = $e;
    }
}


class XTaskTable extends FogTable
{
    function XTaskTable($where, $persons, $fixfors)
    {
	$p = array();
	$res = bug_query
	  ("select ixXTask, sTask, sSubTask, ixFixFor, ixPersonAssignedTo " .
	   "  from schedulator.XTask $where");
	while ($r = mysql_fetch_row($res))
	  $p[$r[0]] = new XTask($r[0], $r[1], $r[2], $fixfors->a[$r[3]],
				$persons->a[$r[4]]);
	$this->FogTable($p);
    }
}


class BugTable extends FogTable
{
    function BugTable($where, $persons, $projects, $fixfors)
    {
	$p = array();
	$res = bug_query
	  ("select ixBug, fOpen, dtOpened, sTitle, ixProject, ixArea, " .
	   "    ixPersonOpenedBy, ixPersonAssignedTo, ixStatus, ixPriority, " .
	   "    ixFixFor, hrsOrigEst, hrsCurrEst, hrsElapsed " .
	   "  from Bug " .
	   "  $where ");
	while ($r = mysql_fetch_row($res))
	  $p[$r[0]] = new Bug($r[0], $r[1], $r[2], $r[3],
			      $projects->a[$r[4]],
			      $r[5], $persons->a[$r[6]],
			      $persons->a[$r[7]], $r[8], $r[9],
			      $fixfors->a[$r[10]], $r[11], $r[12], $r[13]);
	$this->FogTable($p);
    }
}


class Estimate
{
    var $ix;
    var $assignto;
    var $isbug;
    var $task;
    var $origest;
    var $currest;
    var $elapsed;
    var $id;
    
    function Estimate($aa, $a, $b, $c, $d, $e, $f)
    {
	$this->ix = $aa;
	$this->assignto = $a;
	$this->isbug = $b;
	$this->task = $c;
	$this->origest = $d;
	$this->currest = $e;
	$this->elapsed = $f;
	if (!$this->isbug)
	  $this->id = "TM#" . $this->task->ix;
	else
	  $this->id = $this->task->ix;
    }
    
    function isestimated()
    {
	return $this->currest != '' && $this->elapsed != '';
    }
    
    function remain()
    {
	return $this->currest - $this->elapsed;
    }
}


class EstimateTable extends FogTable
{
    function EstimateTable($where, $persons, $bugs, $xtasks)
    {
	$p = array();
	$res = bug_query
	  ("select ixEstimate, ixPerson, fIsBug, ixTask, " .
	   "    hrsOrigEst, hrsCurrEst, hrsElapsed " .
	   "  from schedulator.Estimate " .
	   "  $where ");
	while ($r = mysql_fetch_row($res))
	  $p[] = new Estimate($r[0], $persons->a[$r[1]], $r[2],
			      $r[2] ? $bugs->a[$r[3]] : $xtasks->a[$r[3]],
			      $r[4], $r[5], $r[6]);
	$this->FogTable($p);
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
    var $person;
    var $project;
    var $fixfor;
    var $bug;
    var $xtask;
    var $estimate;
    
    function FogTables($userix)
    {
	$this->person = new PersonTable();
	$this->project = new ProjectTable($this->person);
	$this->fixfor = new FixForTable($this->project);
	
	$p = $this->person->a[$userix];
	$pix = $p->ix;
	$whichbugs = sql_simple("select ixTask from schedulator.Estimate " .
				"    where ixPerson=$pix and fIsBug=1");
	$whichtasks = sql_simple("select ixTask from schedulator.Estimate " .
				 "    where ixPerson=$pix and fIsBug=0");
	
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
	$resolved_tasks = array_keys($resolved_tasks);
	
	$whichbugs += $resolved_tasks;
	
	$bugwhere = " where ixBug in (" . join(",", $whichbugs) . ")";
	$taskwhere = " where ixXTask in (" . join(",", $whichtasks) . ")";
	
	$this->bug = new BugTable($bugwhere,
				  $this->person,
				  $this->project, $this->fixfor);
	$this->xtask = new XTaskTable($taskwhere,
				      $this->person, $this->fixfor);
	$this->estimate = new EstimateTable("where ixPerson=$pix",
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
