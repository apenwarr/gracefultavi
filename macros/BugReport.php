<?php

function db_init() {
    global $db_h;
    global $SchedServer, $SchedUser, $SchedPass, $SchedName;

    if (!$db_h)
    {
        $db_h = mysql_connect($SchedServer, $SchedUser, $SchedPass);
        mysql_select_db($SchedName, $db_h);
    }
}

function db_query($query)
{
    global $db_h;

    $result = mysql_query($query, $db_h);
    if (!$result)
        print(mysql_error($db_h));

    if ($result === true)
        return $result;

    $ret = array();

    while ($row = mysql_fetch_assoc($result))
    {
        $keys = array_keys($row);
        $key0 = array_shift($keys);

        if (count($row) == 1)
            $ret[$key0] = $row[$key0];
        else if (count($row) == 2)
            $ret[$row[$key0]] = $row[array_shift($keys)];
        else
            $ret[$row[$key0]] = $row;
    }

    return $ret;
}


class Macro_BugReport
{
    var $page;
    var $db_h;
    var $outdata = "";
    var $outdata_push = array();
    var $db;
    var $done = false;


    function out()
    {
        $args = func_get_args();
        $data = join("", $args);
        $this->outdata .= $data . "\n";
        return $data;
    }

    function outln()
    {
        $args = func_get_args();
        $data = join("", $args);
        return $this->out(htmlentities($data) . "<br>");
    }


    function form($action = '', $use_post = 1)
    {
        $method = $use_post ? 'POST' : 'GET';
        $action = $action ? 'action="'.$action.'"' : '';
        $this->out('<form '.$action.' method="'.$method.'" '.
                   'name="bugreportform">');
        $this->form_hidden("page", $this->page);
    }

    function form_end()
    {
        $this->out('</form>');
    }

    function form_hidden($name, $value)
    {
        $this->out('<input type="hidden" name="'.$name.'" value="'.
                   htmlspecialchars($value).'" />');
    }

    function form_input($name, $value, $width = 10, $small = false)
    {
        $class = $small ? 'class="smallwidget"' : '';
        $this->out('<span '.$class.'><input '.$class.' type="text" '.
                   'name="'.$name.'" value="'.htmlspecialchars($value).'" '.
                   'size="'.$width.'" /></span>');
    }

    function form_file($name, $width = 10)
    {
        $this->out('<input type="file" name="'.$name.'" size="'.$width.'" />');
    }

    function form_button($name, $title, $id = null, $confirm = false)
    {
        $onClick = '';
        if (strlen($id))
        {
            $onClick = 'onClick="this.form.id.value=\''.$id.'\';';
            if ($confirm)
                $onClick .= "return confirm('Confirm $title');";
            $onClick .= "\" ";
        }
        $this->out('<input type="submit" name="'.$name.'" value="'.$title.'" '.
                   $onClick.' />');
    }

    function form_checkbox($name, $content, $checked, $onclick = '')
    {
        $ch = $checked ? "checked" : "";
        $onclick = $onclick ? 'onClick="'.$onclick.'"' : '';
        $this->out('<input id="'.$name.'" type="checkbox" name="'.$name.'" '.
                   'value="1" '.$ch.' '.$onclick.'>'.
                   htmlspecialchars($content).'</input>');
    }

    function form_radio($name, $value, $content, $checked)
    {
        $ch = $checked ? "checked" : "";

        if (strlen($content))
        {
            $id = 'id="'.$name.$value.'"';
            $label = '<label for="'.$name.$value.'">'.
                     htmlspecialchars($content).'</label>';
        }
        else
        {
            $id = '';
            $label = '';
        }

        $this->out('<input '.$id.' type="radio" name="'.$name.'" '.
                   'value="'.htmlspecialchars($value).'" '.$ch.'>'.
                   $label);
    }

    function form_select($name, $selectedid, $selected, $items, $small = false)
    {
        if ($selectedid == "")
            $selectedid = -1;
        $did_sel = 0;
        $class = $small ? 'class="smallwidget"' : '';
        $this->out('<select '.$class.' name="'.$name.'">');
        foreach ($items as $id => $value)
        {
            if ($id == $selectedid)
            {
                $sel = 'selected="selected"';
                $did_sel = 1;
            }
            else
                $sel = "";
            $this->out('<option value="'.$id.'" '.$sel.'>'.
                       htmlspecialchars($value).'</option>');
        }
        if (!$did_sel)
            $this->out('<option value="'.$selectedid.'" selected="selected">'.
                       $selected.'</option>');
        $this->out('</select>');
    }

    function table()
    {
        $this->out('<table>');
    }

    function table_end()
    {
        $this->out('</table>');
    }


    function redirect($cmd)
    {
        $id = $_REQUEST['id'] ? '&id=' . $_REQUEST['id'] : '';
        header('Location: ' . $_SERVER["PHP_SELF"] .
               '?page=' . rawurlencode($_REQUEST['page']) .
               '&' . $cmd . '=1' . $id);
        exit;
    }


    // *************************** users ***************************

    function list_users()
    {
        $users = db_query("select ixPerson, sFullName " .
                          "from Person " .
                          "where fDeleted = 0 " .
                          "order by sFullName");

        foreach ($users as $key => $value)
        {
            $users[$key] = utf8_decode($value);
        }

        return $users;
    }

    function list_users_emails()
    {
        $users = db_query("select ixPerson, sEmail " .
                          "from Person " .
                          "where fDeleted = 0");
        return $users;
    }


    // *************************** Query Form ***************************

    function do_query_form()
    {
        global $UserName;

        $users = $this->list_users();

        // locate the current user
        $emails = $this->list_users_emails();
        $selected_user = '';
        foreach ($emails as $id => $user)
        {
            preg_match('/(.*)@/', $user, $matches);
            if ($matches[1] && $matches[1] == $UserName)
            {
                $selected_user = $id;
            }
        }

        // date from
        $d = getdate();
        $fix = $d['wday']-1;
        if ($fix == -1)  $fix = 6;
        $fix += 7;
        $datefrom = mktime(0, 0, 0, $d['mon'], $d['mday']-$fix, $d['year']);
        $datefrom = date('Y-m-d', $datefrom);

        // date to
        $dateto = date('Y-m-d');


        $this->out('<p><h2>Bug Report Query</h2>');

        $this->out('<p>');
        $this->table();

        $this->out('<tr><td><b>User:</b></td><td>');
        $this->form_select('br_user', $selected_user, '', $users);
        $this->out('</td></tr>');

        $this->out('<tr><td><b>From:</b></td><td>');
        $this->form_input('br_datefrom', $datefrom, 12);
        $this->out('<small>(yyyy-mm-dd)</small>');
        $this->out('</td></tr>');

        $this->out('<tr><td><b>To:</b></td><td>');
        $this->form_input('br_dateto', $dateto, 12);
        $this->out('<small>(yyyy-mm-dd)</small>');
        $this->out('</td></tr>');

        $this->table_end();

        $this->out('<p>');
        $this->form_button('submit', 'Submit');
    }


    // *************************** Bug Report ***************************

    function list_bugs($user, $datefrom, $dateto)
    {
        return db_query("select ixBugEvent, Project.sProject, Area.sArea, " .
                        "Bug.ixBug, Bug.sTitle " .
                        "from Bug, BugEvent, Project, Area " .
                        "where Bug.ixBug = BugEvent.ixBug " .
                        "and Bug.ixProject = Project.ixProject ".
                        "and Bug.ixArea = Area.ixArea " .
                        "and BugEvent.ixPerson = $user " .
                        "and BugEvent.dt > '$datefrom' " .
                        "and BugEvent.dt < '$dateto' " .
                        "order by sProject, sArea, ixBug");
    }


    function do_bug_report()
    {
        $d = explode('-', $_REQUEST['br_datefrom']);
        array_push($d, 0);array_push($d, 0);array_push($d, 0);
        $datefrom = mktime(0, 0, 0, $d[1], $d[2], $d[0]);
        $datefrom = date('Y-m-d', $datefrom);

        $d = explode('-', $_REQUEST['br_dateto']);
        array_push($d, 0);array_push($d, 0);array_push($d, 0);
        $dateto = mktime(0, 0, 0, $d[1], $d[2]+1, $d[0]);
        $dateto = date('Y-m-d', $dateto);

        $bugs = $this->list_bugs($_REQUEST['br_user'], $datefrom, $dateto);

        $this->out('<p><h2>Bug Report</h2>');

        $users = $this->list_users();
        $this->out('<p><b>User:</b> ' . $users[$_REQUEST['br_user']]);
        $this->out('<br><b>From:</b> ' . $datefrom);
        $this->out('<br><b>To:</b> ' . $dateto);

        $this->out('<p>');
        $this->table();

        $this->out('<tr><td><b>Project</b></td><td><b>Area</b></td><td><b>ID</b></td><td><b>Title</b></td></tr>');

        $prev_bugid = '';
        foreach ($bugs as $bug)
        {
            if ($bug['ixBug'] == $prev_bugid) continue;

            $this->out('<tr>');
            $this->out('<td>'.$bug['sProject'].'</td>');
            $this->out('<td>'.$bug['sArea'].'</td>');
            $this->out('<td><a href="http://fogbugz/?'.$bug['ixBug'].'">'.
                       $bug['ixBug'].'</a></td>');
            $this->out('<td>'.$bug['sTitle'].'</td>');
            $this->out('</tr>');

            $prev_bugid = $bug['ixBug'];
        }
        if (!$bugs) {
            $this->out('<i>No bugs</i>');
        }

        $this->table_end();
    }


    // main gracefultavi entry point
    function parse($args, $page)
    {
        if ($this->done) return '';

        $this->page = $page;

        global $db_h;
        db_init();
        $this->db_h = $db_h;

        $this->form($_SERVER["PHP_SELF"].'?page='.rawurlencode($_REQUEST['page']), 0);

        if ($_REQUEST['br_user'] && $_REQUEST['br_datefrom'] && $_REQUEST['br_dateto'])
        {
            $this->do_bug_report();
        }
        else
        {
            $this->do_query_form();
        }

        $this->form_end();

        $this->done = true;

        return $this->outdata;
    }
}

?>
