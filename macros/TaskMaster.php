<?php

function bug_ixperson($person)
{
    global $bug_h;
    $query = "select ixPerson from Person where sEmail like '$person%' limit 1";
    $result = mysql_query($query, $bug_h);
    if (!$result)
        print 'x-' . mysql_error($bug_h);
    $row = mysql_fetch_row($result);
    return $row[0] + 0;
}


function bug_ixfixfor($fixfor)
{
    global $bug_h;
    $query = "select ixFixFor from FixFor where sFixFor='$fixfor' limit 1";
    $result = mysql_query($query, $bug_h);
    if (!$result)
        print 'x-' . mysql_error($bug_h);
    $row = mysql_fetch_row($result);
    return $row[0] + 0;
}


class Macro_TaskMaster
{
    var $page;
    var $bug_h;
    var $outdata = "";
    var $outdata_push = array();
    
    function push()
    {
	array_push($this->outdata_push, $this->outdata);
	$this->outdata = "";
    }
    
    function pop()
    {
	$data = $this->outdata;
	$this->outdata = array_pop($this->outdata_push);
	return $data;
    }
    
    function out()
    {
	$args = func_get_args();
	$data = join("", $args);
	$this->outdata .= $data;
	return $data;
    }
    
    function style($text)
    {
	$this->out("<style type='text/css'>\n" .
		   "$text\n" .
		   "</style>\n");
    }
    
    function col($isheader, $text)
    {
	if ($isheader)
	  $this->out("<th>$text</th>");
	else
	  $this->out("<td>$text</td>");
    }
    
    # usage: row($nheaders, col, col, col, ...)
    function row($nheaders)
    {
	$n = 0;
	$args = func_get_args();
	array_shift($args);
	
        $this->out("<tr>");
	foreach ($args as $col)
	{
	    if (is_array($col))
	    {
		foreach ($col as $c)
		{
		    $this->col($n < $nheaders, $c);
		    $n++;
		}
	    }
	    else
	    {
		$this->col($n < $nheaders, $col);
		$n++;
	    }
	}
	$this->out("</tr>\n");
    }
    
    function form_hidden($name, $value)
    {
	$this->out("<input type='hidden' name='$name' value='$value' />");
    }
    
    function form_input($name, $value)
    {
	$this->out("<input type='input' name='$name' value='$value' />");
    }
    
    function form_button($name, $title)
    {
	$this->out("<input type='submit' name='$name' value='$title' />");
    }
    
    function form_checkbox($name, $checked)
    {
	$ch = $checked ? "checked" : "";
	$this->out("<input type='checkbox' name='$name' $ch />");
    }
    
    function form_select($name, $selectedid, $selected, $items)
    {
	if ($selectedid == "")
	  $selectedid = -1;
	$did_sel = 0;
	$this->out("<select name='$name'>");
	foreach ($items as $id => $value)
	{
	    if ($id == $selectedid)
	    {
		$sel = "selected='selected'";
		$did_sel = 1;
	    }
	    else
	        $sel = "";
	    $this->out("<option value='$id' $sel>$value</option>");
	}
	if (!$did_sel)
	  $this->out("<option value='$selectedid' selected='selected'>"
		     . "$selected</option>");
	$this->out("</select>");
    }
    
    function table($tabclass)
    {
        $this->out("<table class='$tabclass'>");
    }
    
    function table_end()
    {
	$this->out("</table>");
    }
    
    function form($use_post = 1)
    {
	$method = $use_post ? "POST" : "GET";
	$this->out("<form method='$method'>");
	$this->form_hidden("page", $this->page);
    }
    
    function form_end()
    {
	$this->out("</form>");
    }
    
    function sql_simple($query)
    {
	$result = mysql_query($query, $this->bug_h);
	if (!$result)
	    $this->out(mysql_error($this->bug_h));
	
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
    
    function wild_merge($list, $wild)
    {
	if ($wild)
	{
	    $x = array();
	    $x["-1"] = $wild;
	    foreach ($list as $a => $b)
	      $x[$a] = $b;
	    return $x;
	}
	else
	  return $list;
    }
    
    function list_users($wild)
    {
	$users = $this->sql_simple("select ixPerson, sFullName from Person " .
				   "where fDeleted=0 and sEmail is not null " .
				   "order by sFullName");
	return $this->wild_merge($users, $wild);
    }
    
    function list_fixfors($wild)
    {
	$fixfors = $this->sql_simple("select ixFixFor, sFixFor from FixFor " .
				   "where bDeleted=0 " .
				   "order by sFixFor");
	return $this->wild_merge($fixfors, $wild);
    }
    
    function do_filterbar()
    {
	$this->out("Filter: ");
	# $this->form_checkbox("check", 1);
	# $this->form_checkbox("check", 0);
	$this->form_input("filter-text", $_REQUEST["filter-text"]);
	$this->form_select("filter-user", $_REQUEST["filter-user"], "??",
			   $this->list_users("--Any User--"));
	$this->form_select("filter-fixfor", $_REQUEST["filter-fixfor"], "??",
			   $this->list_fixfors("--Any FixFor--"));
	$this->form_button("Filter", "Filter");
    }
    
    
    function query($q)
    {
	$result = mysql_query($q, $this->bug_h);
	if (!$result)
	  print 'x-' . mysql_error($this->bug_h);
	return $result;
    }
 
    
    function do_assign()
    {
	$assignto = $_REQUEST["assignto"];
	$keys = array();
	foreach (array_keys($_REQUEST) as $key)
	{
	    $x = array();
	    preg_match("/task-select-(.*)/", $key, $x);
	    if (count($x) && $assignto > 0)
	    {
		$this->query("update schedulator.XTask " . 
			     "set ixPersonAssignedTo='$assignto' " .
			     "where ixXTask='$x[1]'");
	    }
	}
    }
    
    function do_retarget()
    {
	$fixforto = $_REQUEST["fixforto"];
	print "do retarget ($fixforto)!";
	$keys = array();
	foreach (array_keys($_REQUEST) as $key)
	{
	    $x = array();
	    preg_match("/task-select-(.*)/", $key, $x);
	    if (count($x) && $fixforto > 0)
	    {
		$this->query("update schedulator.XTask " . 
			     "set ixFixFor='$fixforto' " .
			     "where ixXTask='$x[1]'");
	    }
	}
    }
    
    function do_summary($person)
    {
	$this->style(".table td {  }\n" .
		     ".table th { background: lightgray }\n" .
		     "tr.done { color: gray; font-style: italic; text-decoration: line-through; }\n");
	
	# command bar
	$this->form_button("select-all", "Select All");
	$this->form_button("unselect-all", "Unselect All");
	$this->out(" ");
	$assignto = $_REQUEST["assignto"];
	$this->form_select("assignto", $assignto, "??", 
			   $this->list_users("--Do Nothing--"));
	$this->form_button("cmd", "Assign");
	$this->out(" ");
	$fixforto = $_REQUEST["fixforto"];
	$this->form_select("fixforto", $fixforto, "??", 
			   $this->list_fixfors("--Do Nothing--"));
	$this->form_button("cmd", "Retarget");
	
	# content...
	$user = $_REQUEST["filter-user"];
	$fixfor = $_REQUEST["filter-fixfor"];
	$text = $_REQUEST["filter-text"];
	$userquery = ($user > -1) ? "and x.ixPersonAssignedTo='$user'" : "";
	$fixforquery = ($fixfor > -1) ? "and x.ixFixFor='$fixfor'" : "";
	$textquery = ($text) ? "and sSubTask like '%$text%'" : "";
	$query = "select ixXTask, sTask, sSubTask, sFixFor, " .
	  " sFullName, fDone " .
	  " from schedulator.XTask x, FixFor f, Person p" .
	  " where x.ixFixFor=f.ixFixFor " .
	  " and p.ixPerson=x.ixPersonAssignedTo " .
	  " $userquery $fixforquery $textquery limit 500 ";
	
	$result = mysql_query($query, $this->bug_h);
	if (!$result)
	  $this->out(mysql_error($this->bug_h));
	
	$this->table("table");
	$this->row(6, "Edit?", "XTask", "Task", "Subtask",
		   "FixFor", "Assigned To");
	
	while ($row = mysql_fetch_row($result))
	{
	    $done = $row[5];
	    $doneclass = $done ? "done" : "notdone";
	    $this->out("<tr class='$doneclass'>");
	    
	    $checked = $_REQUEST["select-all"] || $_REQUEST[$row[0]];
	    if ($_REQUEST["unselect-all"])
	      $checked = 0;
	    $this->push();
	    $this->form_checkbox("task-select-$row[0]", $checked);
	    $this->col(1, $this->pop());
	    
	    $this->col(1, $row[0]);
	    $this->col(0, $row[1]);
	    $this->col(0, $row[2]);
	    $this->col(0, $row[3]);
	    $this->col(0, $row[4]);
	    
	    $this->out("</tr>");
	}
	$this->table_end();
    }
    
    // main gracefultavi entry point
    function parse($args, $page)
    {
	$this->page = $page;
	
	global $bug_h;
	bug_init();
	$this->bug_h = $bug_h;

        $this->outdata = "";
	$this->out("TaskMaster output" . $_REQUEST["foo"]);
	$this->form(1);
	$this->do_filterbar();
	$this->out("<hr>");
	if ($_REQUEST["cmd"] == "Assign")
	  $this->do_assign();
	if ($_REQUEST["cmd"] == "Retarget")
	  $this->do_retarget();
	$this->do_summary($_REQUEST["new-user"]);
	$this->form_end();
        return $this->outdata;
    }
}

return 1;
?>
