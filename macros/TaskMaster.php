<?php

$olderr = error_reporting(E_ALL);

class Macro_TaskMaster
{
    var $page;
    var $bug_h;
    var $outdata = "";
    var $outdata_push = array();
    var $db;
    
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
    
    function col($isheader, $text, $class = "")
    {
	$class = $class ? "class='$class'" : "";
	if ($isheader)
	  $this->out("<th $class>$text</th>");
	else
	  $this->out("<td $class>$text</td>");
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
    
    function form_input($name, $value, $width = 10)
    {
	$this->out("<input type='input' name='$name' " .
		   " value='$value' size='$width' />");
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
	$this->out("<form method='$method' name='taskform'>");
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
	# save settings in cookies for convenience
	$this->savecookie("filter-user");
	$this->savecookie("filter-fixfor");
	$this->savecookie("filter-text");
	
	$this->out("Filter: ");
	# $this->form_checkbox("check", 1);
	# $this->form_checkbox("check", 0);
	$this->form_input("filter-text", $_REQUEST["filter-text"]);
	$this->form_select("filter-user", $_REQUEST["filter-user"], "??",
			   $this->list_users("--Any User--"));
	$this->form_select("filter-fixfor", $_REQUEST["filter-fixfor"], "??",
			   $this->list_fixfors("--Any FixFor--"));
	$this->form_button("Filter", "Filter");
	$this->out("<hr>");
    }
    
    
    function query($q)
    {
	$result = mysql_query($q, $this->bug_h);
	if (!$result)
	  print 'x-' . mysql_error($this->bug_h);
	return $result;
    }
    
    
    function savecookie($name)
    {
	if ($_GET[$name])
	{
	    setcookie($name, $_GET[$name]);
	    $_REQUEST[$name] = $_GET[$name];
	}
	else if ($_POST[$name])
	{
	    setcookie($name, $_POST[$name]);
	    $_REQUEST[$name] = $_POST[$name];
	}
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
	# print "do retarget ($fixforto)!";
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
    
    function mystyle()
    {
	$this->style(".table td {  }\n" .
		     ".table th { background: lightgray }\n" .
		     "tr.done { color: gray; font-style: italic; " .
		     "     text-decoration: line-through; }\n" .
		     ".fooref:hover { background: yellow }\n");
    }
    
    function do_assign_form($person)
    {
	$this->mystyle();
	
	# filter bar
	$this->do_filterbar();
	
	# command bar
	$this->form_button("select-all", "Select All");
	$this->form_button("unselect-all", "Unselect All");
	
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
	$this->row(6, "(*)", "XTask", "Task", "Subtask",
		   "FixFor", "Assigned To");
	
	while ($row = mysql_fetch_row($result))
	{
	    $done = $row[5];
	    $doneclass = $done ? "done" : "notdone";
	    $this->out("<tr class='$doneclass fooref' " .
	       "onClick='" .
	       "x = document.forms[\"taskform\"][\"task-select-$row[0]\"]; " .
	       "x.waschecked = x.checked = !x.waschecked'>");
	    
	    $checked = $_REQUEST["select-all"] || $_REQUEST[$row[0]];
	    if ($_REQUEST["unselect-all"])
	      $checked = 0;
	    $this->push();
	    $this->form_checkbox("task-select-$row[0]", $checked);
	    $this->col(1, $this->pop());
	    
	    $this->col(1, $row[0]);
	    $this->col(0, $row[1]);
	    $this->col(0, $row[2], "fooref");
	    $this->col(0, $row[3]);
	    $this->col(0, $row[4]);
	    
	    $this->out("</tr>");
	}
	$this->table_end();
	
	$assignto = $_REQUEST["assignto"];
	$this->form_select("assignto", $user ? $user : $assignto, "??", 
			   $this->list_users("--Do Nothing--"));
	$this->form_button("cmd", "Assign");
	$this->out(" ");
	$fixforto = $_REQUEST["fixforto"];
	$this->form_select("fixforto", $fixfor ? $fixfor : $fixforto, "??", 
			   $this->list_fixfors("--Do Nothing--"));
	$this->form_button("cmd", "Retarget");
    }
    
    function do_create_form()
    {
	$this->savecookie("filter-user");
	$this->savecookie("filter-fixfor");
	
	$this->out("Assign to: ");
	$this->form_select("filter-user", $_REQUEST["filter-user"], "??",
			   $this->list_users("--Choose User--"));
	$this->out("&nbsp;&nbsp;Fix for: ");
	$this->form_select("filter-fixfor", $_REQUEST["filter-fixfor"], "??",
			   $this->list_fixfors("--Choose FixFor--"));
	
	$this->table("table");
	$this->row(2, "Task", "Subtask");
	for ($i = 0; $i < 10; $i++)
	{
	    $this->out("<tr>");
	    $this->push();
	    $this->form_input("create-$i-task", "", 20);
	    $this->col(0, $this->pop());
	    $this->push();
	    $this->form_input("create-$i-subtask", "", 40);
	    $this->col(0, $this->pop());
	    $this->out("</tr>");
	}
	$this->table_end();
	$this->form_button("cmd", "Create");
    }
    
    function do_create()
    {
	$fixfor = $_REQUEST["filter-fixfor"];
	$user = $_REQUEST["filter-user"];
	
	$this->out("<b>");
	
	if ($fixfor <= 0 || $user <= 0)
	{
	    $this->out("Can't create: You must specify the user " .
		       "and fixfor!<br>");
	}
	else
	{
	    for ($i = 0; $i < 10; $i++)
	    {
		$task = $_REQUEST["create-$i-task"];
		$subtask = $_REQUEST["create-$i-subtask"];
		if ($task != "") {
		    $this->out("Creating task: '$task' '$subtask'<br>");
		    $this->query
		     ("insert into schedulator.XTask " .
		      "  (sTask,sSubTask,ixFixFor,ixPersonAssignedTo,fDone) " .
		      "  values (\"$task\", \"$subtask\", $fixfor, $user, 0) "
		      );
		}
	    }
	}
	$this->out("</b><p><hr>");
	$this->do_create_form();
    }
    
    
    function estimcol($isbug, $taskid, $coltype, $value, $done)
    {
	if ($value == 0)
	  $value = "";
	if ($done)
	    $this->col(0, $value);
	else
	{
	    $this->push();
	    if ($coltype == "currest")
	      $this->form_hidden("hasest-$isbug-$taskid", 1);
	    $this->form_input("$coltype-$isbug-$taskid", $value, 5);
	    $this->col(0, $this->pop());
	}
    }
    
    
    function estimate_rows($prefix, $isbug, $res)
    {
	while ($row = mysql_fetch_row($res))
	{
	    $taskid  = array_shift($row);
	    $task    = array_shift($row);
	    $subtask = array_shift($row);
	    $fixfor  = array_shift($row);
	    $origest = array_shift($row);
	    $currest = array_shift($row);
	    $elapsed = array_shift($row);
	    $done    = array_shift($row);
	    if ($currest!='' && $currest == $elapsed)
	      $done = 1;
	    $doneclass = $done ? "done" : "notdone";
	    $this->out("<tr class='$doneclass'>");
	    $this->col(1, "$prefix#$isbug-$taskid");
	    $this->col(0, $task);
	    $this->col(0, $subtask);
	    $this->col(0, $fixfor);
	    $this->estimcol($isbug, $taskid, "origest", $origest, 1);
	    $this->estimcol($isbug, $taskid, "currest", $currest, $done);
	    $this->estimcol($isbug, $taskid, "elapsed", $elapsed, $done);
	    $this->estimcol($isbug, $taskid, "remain", $currest-$elapsed, 1);
	    $this->push();
	    if (!$done)
	      $this->form_button("done-$isbug-$taskid", "Done");
	    else
	      $this->form_button("reopen-$isbug-$taskid", "Reopen");
	    $this->col(0, $this->pop());
	    $this->out("</tr>");
	}
    }
    
    
    function do_estimate_form()
    {
	$user = $_REQUEST["filter-user"];
	
	$this->mystyle();
	$this->do_filterbar();
	
	if ($user > 0)
	{
	    $this->out("<b>");
	    $res = $this->query
	      ("select fIsBug,ixTask from schedulator.Estimate " .
	       "   where ixPerson=$user");
	    while ($row = mysql_fetch_row($res))
	    {
		if ($_REQUEST["hasest-$row[0]-$row[1]"]!='')
		{
		    $cur = $_REQUEST["currest-$row[0]-$row[1]"] + 0;
		    if ($cur == 0)
		      $cur = 0;
		    $elapsed = $_REQUEST["elapsed-$row[0]-$row[1]"] + 0;
		    if ($elapsed == 0)
		      $elapsed = 0;
		    $this->out("Estimating task $row[0]-$row[1].<br>");
		    $this->query
		      ("update schedulator.Estimate " .
		       "  set hrsCurrEst=$cur, hrsElapsed=$elapsed " .
		       "  where fIsBug=$row[0] and ixTask=$row[1] and " .
		       "        ixPerson=$user ");
		}
		
		if ($_REQUEST["done-$row[0]-$row[1]"])
		{
		    $this->out("Closing task $row[0]-$row[1].<br>");
		    $this->query
		      ("update schedulator.Estimate " .
		       "  set hrsCurrEst=if(hrsCurrEst is not null," .
		       "                    hrsCurrEst,0), " .
		       "      hrsElapsed=hrsCurrEst " .
		       "  where fIsBug=$row[0] and ixTask=$row[1] and " .
		       "        ixPerson=$user ");
		}
		else if ($_REQUEST["reopen-$row[0]-$row[1]"])
		{
		    $this->out("Reopening task $row[0]-$row[1].<br>");
		    $this->query
		      ("update schedulator.Estimate " .
		       "  set hrsCurrEst=if(hrsCurrEst is not null," .
		       "                    hrsCurrEst,0), " .
		       "      hrsElapsed=hrsCurrEst-0.1 " .
		       "  where fIsBug=$row[0] and ixTask=$row[1] and " .
		       "        ixPerson=$user ");
		}
	    }
	    $this->out("</b>");
	    
	    $this->table("table");
	    $this->row(8, "Source", "Task", "Subtask", "FixFor",
		       "OrigEst", "CurrEst", "Elapsed", "Remain");
	    
	    $this->query
	      ("insert into schedulator.Estimate (ixPerson, fIsBug, ixTask) " .
	       "  select $user,0,ixXTask from schedulator.XTask " .
	       "    where ixPersonAssignedTo=$user");
	    
	    $this->query
	      ("insert into schedulator.Estimate (ixPerson, fIsBug, ixTask) " .
	       "  select $user,1,ixBug from Bug " .
	       "    where ixPersonAssignedTo=$user");
	    
	    $res = $this->query
	      ("select ixTask, " .
	       "       sTask, sSubTask, sFixFor, " .
	       "       hrsOrigEst, hrsCurrEst, hrsElapsed, " .
	       "       if(x.ixPersonAssignedTo=$user,0,1) as fMeDone " .
	       "  from schedulator.XTask x, schedulator.Estimate e, " .
	       "       FixFor f " .
	       "  where fIsBug=0 and e.ixTask=x.ixXTask " .
	       "    and f.ixFixFor=x.ixFixFor " .
	       "    and e.ixPerson=$user ");
	    $this->estimate_rows("TM", 0, $res);
	    
	    $res = $this->query
	      ("select ixTask, " .
	       "   ixBug, sTitle, sFixFor, " .
	       "   if(e.hrsOrigEst is not null,e.hrsOrigEst,b.hrsOrigEst), " .
	       "   if(e.hrsCurrEst is not null,e.hrsCurrEst,b.hrsCurrEst), " .
	       "   if(e.hrsElapsed is not null,e.hrsElapsed,b.hrsElapsed), " .
	       "       if(b.ixPersonAssignedTo=$user,0,1) as fMeDone " .
	       "  from Bug b, schedulator.Estimate e, FixFor f " .
	       "  where fIsBug=1 and e.ixTask=b.ixBug " .
	       "    and f.ixFixFor=b.ixFixFor " .
	       "    and e.ixPerson=$user ");
	    $this->estimate_rows("Bug", 1, $res);
	    
	    $this->table_end();
	    $this->form_button("Save", "Save");
	}
    }
    
    // main gracefultavi entry point
    function parse($args, $page)
    {
	$this->page = $page;
	
	global $bug_h;
	bug_init();
	$this->bug_h = $bug_h;
	
	$this->db = new FogTables($_REQUEST["filter-user"]);

        if (!preg_match_all('/"[^"]*"|[^ \t]+/', $args, $words))
            return "regmatch failed!\n";
        $words = $words[0]; // don't know why I have to do this, exactly...

        foreach ($words as $key => $value)
        {
            if (preg_match('/^"(.*)"$/', $value, $result))
                $words[$key] = $result[1];
        }
	
	$this->outdata = "";

	$this->out("TaskMaster $words[0]");
	$this->form(1);
        if ($words[0] == "ESTIMATE")
	{
	    $this->do_estimate_form();
	}
	else if ($words[0] == "CREATE")
	{
	    if ($_REQUEST["cmd"] == "Create")
	      $this->do_create();
	    else
	      $this->do_create_form();
	    
	    $ave = $this->db->person->first_prefix("username", "apenwar");
	    $this->out("person: $ave->fullname; $ave->email; $ave->username<br>");
	    
	    $foo = $this->db->project->first("name", "1-Weaver");
	    $em = $foo->owner->email;
	    $this->out("project: $foo->ix; $foo->name; $em<br>");
	    
	    $foo = $this->db->fixfor->first("name", "Wv 4.0");
	    $projname = $foo->project->name;
	    $this->out("fixfor: $foo->ix; $foo->name; $projname<br>");
	    
	    $foo = $this->db->bug->first_match("name", "/crash/i");
	    $this->out("bug: $foo->name;<br>");
	    
	    $foo = $this->db->estimate->a[5];
	    $pname = $foo->assignto->fullname;
	    $this->out("estimate: $pname;<br>");
	    
	    $this->table("table");
	    $this->row(5, "XTask", "Task", "SubTask", "FixFor", "Assigned To");
	    foreach ($this->db->xtask->a as $t)
	    {
		$this->row(1, $t->ix, $t->task, $t->name,
			   $t->fixfor->name, $t->assignto->email);
	    }
	    $this->table_end();

	    $this->table("table");
	    $this->row(4, "Bug", "Title", "FixFor", "Assigned To");
	    foreach ($this->db->bug->a as $t)
	    {
		$this->row(1,
		   "<a href='http://nits/fogbugz3?$t->ix'>$t->ix</a>",
		   $t->name, $t->fixfor->name, $t->assignto->email);
	    }
	    $this->table_end();

	    $this->table("table");
	    $this->row(8, "ix", "Person", "bug?", "task",
		       "origest", "currest", "elapsed", "remain");
	    foreach ($this->db->estimate->a as $e)
	    {
		$this->row(2, $e->id,
			   $e->assignto->email, $e->isbug, $e->task->name,
			   $e->origest, $e->currest, $e->elapsed,
			   $e->remain());
	    }
	    $this->table_end();
	}
	else if ($words[0] == "ASSIGN")
	{
	    if ($_REQUEST["cmd"] == "Assign")
	      $this->do_assign();
	    if ($_REQUEST["cmd"] == "Retarget")
	      $this->do_retarget();
	    $this->do_assign_form($_REQUEST["new-user"]);
	}
	$this->form_end();
	
	return $this->outdata;
    }
}

error_reporting($olderr);

return 1;
?>
