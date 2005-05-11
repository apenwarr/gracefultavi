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
    
    function outln()
    {
	$args = func_get_args();
	$data = join("", $args);
	return $this->out(htmlentities($data) . "<br>");
    }
    
    function style($text)
    {
	$this->out("<style type='text/css'>\n" .
		   "$text\n" .
		   "</style>\n");
    }
    
    # extra_opt lets you put extra attributes into the <tr>/<th> tag, such as
    # the deprecated "nowrap" attribute which keeps the "Reopen" checkbox from
    # making the page look totally disgusting.
    function col($isheader, $text, $class = "", $extra_opt = "")
    {
	$class = $class ? "class='$class'" : "";
	if ($isheader)
	  $this->out("<th $class $extra_opt>$text</th>");
	else
	  $this->out("<td $class $extra_opt>$text</td>");
    }
    
    // usage: row($nheaders, col, col, col, ...)
    function row($nheaders)
    {
	$args = func_get_args();
	array_shift($args);
	return $this->_row($nheaders, "", $args);
    }
    
    function row_class($nheaders, $class)
    {
	$args = func_get_args();
	array_shift($args);
	array_shift($args);
	return $this->_row($nheaders, $class, $args);
    }
    
    function _row($nheaders, $class, $args)
    {
	$n = 0;
	if ($class != '')
	  $class = "class='$class'";
	
        $this->out("<tr $class>");
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
	$this->out("<input type='hidden' name='$name' value='$value' />\n");
    }
    
    function form_input($name, $value, $width = 10)
    {
	$this->out("<input type='input' name='$name' " .
		   " value='$value' size='$width' />\n");
    }
    
    function form_button($name, $title)
    {
	$this->out("<input type='submit' name='$name' value='$title' />\n");
    }
    
    function form_checkbox($name, $content, $checked)
    {
	$ch = $checked ? "checked" : "";
	$this->out("<input type='checkbox' name='$name' $ch>$content</input>\n");
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
	$this->out("</select>\n");
    }
    
    function table($tabclass)
    {
        $this->out("<table class='$tabclass'>");
    }
    
    function table_end()
    {
	$this->out("</table>\n");
    }
    
    function form($use_post = 1, $name = "taskform")
    {
	$method = $use_post ? "POST" : "GET";
	$this->out("<form method='$method' name='$name'>");
	$this->form_hidden("page", $this->page);
    }
    
    function form_end()
    {
	$this->out("</form>\n");
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
	$users = sql_simple("select ixPerson, sFullName from Person " .
			    "where fDeleted=0 and sEmail is not null " .
			    "order by sFullName");
	return $this->wild_merge($users, $wild);
    }
    
    function list_fixfors($wild)
    {
	$fixfors = sql_simple("select ixFixFor, sFixFor from FixFor " .
			      "where bDeleted=0 " .
			      "order by sFixFor");
	return $this->wild_merge($fixfors, $wild);
    }
    
    function add_filter_user_list()
    {
	$this->form_select("filter-user", $_REQUEST["filter-user"], "??",
			   $this->list_users("--Any User--"));
    }

    function add_filter_fixfor_list()
    {
	$this->form_select("filter-fixfor", $_REQUEST["filter-fixfor"], "??",
			   $this->list_fixfors("--Any FixFor--"));
    }

    function do_filterbar()
    {
	# save settings in cookies for convenience
	$this->savecookie("filter-user");
	$this->savecookie("filter-fixfor");
	$this->savecookie("filter-text");

        $this->form(0, "filterform");
	
        $this->out("Filter: ");
        $this->form_input("filter-text", $_REQUEST["filter-text"]);

        $this->add_filter_user_list();
        $this->add_filter_fixfor_list();

        $this->form_button("Filter", "Filter");

        $this->form_end();

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
	if (array_key_exists($name, $_GET))
	{
	    setcookie($name, $_GET[$name]);
	    $_REQUEST[$name] = $_GET[$name];
	}
	else if (array_key_exists($name, $_POST))
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
	
	$this->form(1);

	# command bar
	$this->form_button("select-all", "Select All");
	$this->form_button("unselect-all", "Unselect All");
	
	# content...
	$user = $_REQUEST["filter-user"];
	$fixfor = $_REQUEST["filter-fixfor"];
	$text = $_REQUEST["filter-text"];
	
	$this->table("table");
	$this->row(6, "(*)", "XTask", "Task", "Subtask",
		   "FixFor", "Assigned To");
	
	foreach ($this->db->xtask->a as $t)
	{
	    if ($fixfor>=0 && $t->fixfor->ix != $fixfor)
	        continue;
	    if ($user>=0 && $t->assignto->ix != $user)
	        continue;
	    if ($text && !strstr(strtolower($t->name), strtolower($text)))
	        continue;
	    
	    $tag = "task-select-" . $t->ix;
	    $done = $t->isdone();
	    $doneclass = $done ? "done" : "notdone";
	    $this->out("<tr class='$doneclass fooref' " .
	       "onClick='" .
	       "x = document.forms[\"taskform\"][\"$tag\"]; " .
	       "x.waschecked = x.checked = !x.waschecked'>");
	    
	    $checked = $_REQUEST["select-all"] || $_REQUEST[$tag];
	    if ($_REQUEST["unselect-all"])
	        $checked = 0;
	    $this->push();
	    $this->form_checkbox($tag, "", $checked);
	    $this->col(1, $this->pop());
	    
	    $this->col(1, $t->ix);
	    $this->col(0, htmlentities($t->task));
	    $this->col(0, htmlentities($t->name), "fooref");
	    $this->col(0, $t->fixfor->name);
	    $this->col(0, $t->assignto->fullname);
	    
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

	$this->form_end();
    }
    
    function do_create_form()
    {
	$this->mystyle();
	
	$this->out("Assign to: ");
	
	$this->form(1);

        $this->add_filter_user_list();
        $this->add_filter_fixfor_list();

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
	$this->form_button("cmdCreate", "Create");
	$this->out("<hr>");
	$this->out("For build:");
	$bounce = $this->db->fixfor->a[$_REQUEST["filter-fixfor"]];
	if (!$bounce)
	    $bounce = "??";
	else
	    $bounce = $bounce->name . "#??";
	$this->form_hidden("oldtestplan-bounce", $bounce);
	$this->form_input("testplan-bounce", $bounce, 20);
	$this->form_button("cmdTestPlans", "Create TestPlans");

	$this->form_end();
    }
    
    function add_task($task, $subtask, $fixforix, $userix)
    {
	$task = mysql_escape_string($task);
	$subtask = mysql_escape_string($subtask);
	$fixforix += 0;
	$userix += 0;
	$this->query
	  ("insert ignore into schedulator.XTask " .
	   "  (sTask,sSubTask,ixFixFor,ixPersonAssignedTo) " .
	   "  values (\"$task\", \"$subtask\", '$fixforix', '$userix') "
	   );
    }
    
    function do_create()
    {
	$fixfor = $_REQUEST["filter-fixfor"];
	$user = $_REQUEST["filter-user"];
	
	$this->out("<b>");
	
	if ($fixfor <= 0 || $user <= 0)
	{
	    $this->outln("Can't create: You must specify the user " .
			 "and fixfor!");
	}
	else
	{
	    $task = "";
	    for ($i = 0; $i < 10; $i++)
	    {
		$_task = $_REQUEST["create-$i-task"];
		$task = $_task ? $_task : $task;
		$subtask = $_REQUEST["create-$i-subtask"];
		if ($task != "" && $subtask != "") {
		    $this->outln("Creating task: '$task' '$subtask'");
		    $this->add_task($task, $subtask, $fixfor, $user);
		}
	    }
	    
	    if ($_REQUEST["cmdTestPlans"])
	    {
		$rel = $_REQUEST["testplan-bounce"];
		$this->outln("Creating test plans in release '$rel'.");
		
		if ($rel == $_REQUEST["oldtestplan-bounce"])
		{
		    $this->outln("You forgot to fill in the bounce number!  " .
			       "Aborted.");
		}
		else
		{
		    global $pagestore;
		    $pg = $pagestore->page("TestingWeaver");
		    $pg->read();
		    
		    preg_match_all("/(Testing[-a-zA-Z0-9_.]+)/", $pg->text,
				   $matches);
		    $create = array();
		    $skip = array();
		    
		    foreach ($matches[0] as $match)
		    {
			if ($match == "TestingProcedureTemplate"
			    || $match == "TestingFeature"
			    || preg_match("/^TestingWeaver[0-9]/", $match))
			  $skip[$match] = $match;
			else
			  $create[$match] = $match;
		    }
		    
		    sort($skip);
		    sort($create);
		    
		    if (count($skip) > 0)
		    {
			$this->out("WARNING: Not making tasks " .
				   "for these pages: <ul>");
			foreach ($skip as $p)
			    $this->out("$p ");
			$this->out("</ul>");
		    }
		    
		    $this->out("Making tasks for these pages:<ul>");
		    foreach ($create as $p)
		    {
			$this->out("$p ");
			$this->add_task("QA $rel",
					"$p: Update test plan",
					$fixfor, $user);
			$this->add_task("QA $rel",
					"$p: Run through test plan",
					$fixfor, $user);
		    }
		    $this->out("</ul>");
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
	    $this->form_hidden("old$coltype-$isbug-$taskid", $value);
	    $this->form_input("$coltype-$isbug-$taskid", $value, 5);
	    $this->col(0, $this->pop());
	}
    }
    
    
    function estimate_rows($fixfor)
    {
	$myarray = $this->db->estimate->a;
	uasort($myarray, estimate_compare_fixfor_only);

	foreach (array_keys($myarray) as $ekey)
	{
	    $e = $this->db->estimate->a[$ekey];
	    if ($fixfor>0 && $e->task->fixfor->ix != $fixfor)
	        continue;
	    
	    $done = $e->isdone();
	    $isbug = $e->isbug;
	    $taskid = $e->task->ix;
	    
	    if (!$e->isdone() && $e->assignto->ix != $e->task->assignto->ix)
	        $extra_assign = " <i>(now reassigned to " .
	            $e->task->assignto->fullname . ")</i>";
	    else
	        $extra_assign = "";

	    $doneclass = $done ? "done" : "notdone";
	    $this->out("<tr class='$doneclass'>");
	    $this->col(1, $e->task->hyperlink());
	    $this->col(0, htmlentities($e->nice_title()) . $extra_assign);
	    $this->col(0, $e->task->fixfor->name);
	    $this->estimcol($isbug, $taskid, "origest", $e->est_orig(), 1);
	    $this->estimcol($isbug, $taskid, "currest", $e->est_curr(), $done);
	    $this->estimcol($isbug, $taskid, "elapsed", $e->est_elapsed(), $done);
	    $this->estimcol($isbug, $taskid, "remain", $e->est_remain(), 1);
	    $this->push();
	    if (!$done)
	        $this->form_checkbox("done-$isbug-$taskid", "Done", 0);
	    else
	        $this->form_checkbox("reopen-$isbug-$taskid", "Reopen", 0);
	    $this->col(0, $this->pop(), "", "nowrap");
	    $this->out("</tr>");
	}
    }
    
    
    function do_estimate_form()
    {
	$user = $_REQUEST["filter-user"];
	
	$this->mystyle();
	$this->do_filterbar();
	
	$this->form(1);
	if ($user > 0)
	{
	    $this->out("<b>");
	    
	    // this changes the contents of the array, we we need a special
	    // construct here rather than the usual foreach().  foreach()
	    // makes a *copy* of the array, so changes aren't permanent!
	    foreach (array_keys($this->db->estimate->a) as $ekey)
	    {
		$e = &$this->db->estimate->a[$ekey];
		
		$tag = $e->isbug . "-" . $e->task->ix;
		if ($_REQUEST["oldcurrest-$tag"] != $_REQUEST["currest-$tag"]
		 || $_REQUEST["oldelapsed-$tag"] != $_REQUEST["elapsed-$tag"])
		{
		    $this->outln("Estimating task $tag.");
		    $e->update($_REQUEST["currest-$tag"] + 0,
			       $_REQUEST["elapsed-$tag"] + 0);
		    
		    $cur = $this->db->estimate->a[$ekey]->currest;
		    print "(update:$ekey:$cur)";
		}
		
		if ($_REQUEST["done-$tag"])
		{
		    $this->outln("Closing task $tag.");
		    $e->update($e->est_curr(), $e->est_curr());
		}
		else if ($_REQUEST["reopen-$tag"])
		{
		    $this->outln("Reopening task $tag.");
		    $e->update($e->est_curr(), $e->est_curr()-0.1);
		}
	    }
	    $this->out("</b>");
	    
	    $this->db->estimate->do_sort();
	    
	    $this->form_button("Save", "Save");
	    $this->table("table");
	    $this->row(7, "TaskID", "Task: Subtask", "FixFor",
		       "OrigEst", "CurrEst", "Elapsed", "Remain");
	    $this->estimate_rows($_REQUEST["filter-fixfor"]);
	    $this->table_end();
	    $this->form_button("Save", "Save");
	}
	$this->form_end();
    }
    
    // main gracefultavi entry point
    function parse($args, $page)
    {
	$this->page = $page;
	
	global $bug_h;
	bug_init();
	$this->bug_h = $bug_h;
	
	$this->savecookie("filter-user");
	$this->savecookie("filter-fixfor");
	$userix = $_REQUEST["filter-user"];
	$fixforix = $_REQUEST["filter-fixfor"];
	
	$this->db = new FogTables($userix, $fixforix);

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
        if ($words[0] == "ESTIMATE")
	{
	    $this->do_estimate_form();
	}
	else if ($words[0] == "CREATE")
	{
	    if ($_REQUEST["cmdCreate"] || $_REQUEST["cmdTestPlans"])
	      $this->do_create();
	    else
	      $this->do_create_form();
	}
	else if ($words[0] == "ASSIGN")
	{
	    if ($_REQUEST["cmd"] == "Assign")
	    {
		$this->do_assign();
		unset($this->db);
		$this->db = new FogTables($userix, $fixforix);
	    }
	    if ($_REQUEST["cmd"] == "Retarget")
	    {
		$this->do_retarget();
		unset($this->db);
		$this->db = new FogTables($userix, $fixforix);
	    }
	    
	    $this->do_assign_form($_REQUEST["new-user"]);
	}
	
	return $this->outdata;
    }
}

error_reporting($olderr);

return 1;
?>
