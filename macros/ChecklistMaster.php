<?php

function checklist_init() {
    global $chklst_h;
    global $ChecklistServer;
    global $ChecklistUser;
    global $ChecklistPass;
    global $ChecklistName;

    if (!$chklst_h)
    {
        $chklst_h = mysql_connect($ChecklistServer, $ChecklistUser,
                                  $ChecklistPass);
        mysql_select_db($ChecklistName, $chklst_h);
    }
}

function chklst_query($query)
{
    global $chklst_h;

    $result = mysql_query($query, $chklst_h);
    if (!$result)
        print(mysql_error($chklst_h));

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

function chklst_insert_query($query)
{
    global $chklst_h;

    $result = mysql_query($query, $chklst_h);
    if (!$result)
        print(mysql_error($chklst_h));

    return mysql_insert_id($chklst_h);
}

function quote($text)
{
    if (!get_magic_quotes_gpc()) {
        return addslashes($text);
    } else {
        return $text;
    }
}

// emulates behavior of magic quotes for imported data
function magic_quotes($text)
{
    if (get_magic_quotes_gpc()) {
        return addslashes($text);
    } else {
        return $text;
    }
}



class Macro_ChecklistMaster
{
    var $page;
    var $chklst_h;
    var $outdata = "";
    var $outdata_push = array();
    var $db;
    var $done = false;

    var $users_list;
    var $category_list;
    var $url_params;

    var $parsing_rules = array('parse_elem_flag',
                               'parse_freelink',
                               'parse_interwiki',
                               'parse_wikiname',
                               'parse_elements');


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

    function style($text)
    {
        $this->out('<style type="text/css">'."\n" .
                   "$text\n" .
                   "</style>\n");
    }


    function form($action = '', $use_post = 1)
    {
        $method = $use_post ? 'POST' : 'GET';
        $action = $action ? 'action="'.$action.'"' : '';
        $this->out('<form enctype="multipart/form-data" '.
                   $action.' method="'.$method.'" name="checklistform">');
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

    function table($tabclass)
    {
        $this->out('<table class="'.$tabclass.'">');
    }

    function table_end()
    {
        $this->out('</table>');
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


    function redirect($cmd)
    {
        $id = $_REQUEST['id'] ? '&id=' . $_REQUEST['id'] : '';
        header('Location: ' . $_SERVER["PHP_SELF"] .
               '?page=' . $_REQUEST['page'] .
               '&' . $cmd . '=1' . $id);
        exit;
    }


    function mystyle()
    {
        $this->style("
.table td { }
.table th {
    background: lightgray
}
td.done {
    color: gray;
    font-style: italic;
    text-decoration: line-through;
}
.smallwidget {
    font-size: xx-small;
}
.tooltip {
    font-size: 11px;
    background-color: #ffc;
    border: 1px solid #000;
    padding: 2px;
    -moz-border-radius: 3px;
}
td.calendar_header {
    padding: 3px;
    clear: both;
    background-color: lightgray;
    border-bottom: 1px solid #000;
    white-space: nowrap;
    text-align: right;
}
.calendar_item {
    color: #000;
    font-size: 12px;
    font-family: Geneva,Arial,Helvetica,sans-serif;
    padding: 1px;
}
.calendar_item0 {
    font-size: 12px;
    font-family: Geneva,Arial,Helvetica,sans-serif;
    padding: 1px;
}
.calendar_item1 {
    font-size: 12px;
    font-family: Geneva,Arial,Helvetica,sans-serif;
    padding: 1px;
}");
    }


    function checklist_js()
    {
        static $checklist_js_done;

        if (isset($checklist_js_done)) return;
        $checklist_js_done = 1;

        $js = <<<CHECKLIST_JAVASCRIPT

        <script language="javascript">
        <!--

        function setRowStyle(rowid, checked)
        {
            for (var i = 0; i < 4; i++)
            {
                var cell = document.getElementById('col'+i+'row'+rowid);

                if (checked)
                {
                    cell.style.color = 'gray';
                    cell.style.fontStyle = 'italic';
                    cell.style.textDecoration = 'line-through';
                }
                else
                {
                    cell.style.color = 'black';
                    cell.style.fontStyle = 'normal';
                    cell.style.textDecoration = 'none';
                }
            }
        }

        function editNote(noteid)
        {
            var viewtd = document.getElementById('notesview'+noteid);

            if (viewtd.style.display != 'none')
            {
                var form = document.forms.checklistform;
                form['notes'+noteid].value = form['prevnotes'+noteid].value;
                form['noteshidden'+noteid].checked =
                    form['prevnoteshidden'+noteid].value == 1 ? true : false;

                viewtd.style.display = 'none';

                var edittd = document.getElementById('notesedit'+noteid);
                edittd.style.display = 'block';
            }
        }

        function editNoteCancel(noteid)
        {
            var viewtd = document.getElementById('notesview'+noteid);
            viewtd.style.display = 'block';

            var edittd = document.getElementById('notesedit'+noteid);
            edittd.style.display = 'none';

            var form = document.forms.checklistform;
            form['notes'+noteid].value = form['prevnotes'+noteid].value;
            form['noteshidden'+noteid].checked =
                form['prevnoteshidden'+noteid].value == 1 ? true : false;
        }

        // unused, does not work properly
        function showHideCompletedRows()
        {
            var form = document.forms.checklistform;
            var rowids = form.checklistrowids.value.split(',');

            var dowhat = -1;
            var row;

            for (var i = 0; i < rowids.length; i++)
            {
                if (form['status'+rowids[i]].checked)
                {
                    row = document.getElementById('row'+rowids[i]);
                    if (dowhat == -1)
                        dowhat = (row.style.display == 'none') ? 1 : 0;
                    row.style.display = (dowhat == 0) ? 'none' : 'block';
                }
            }
        }

        //-->
        </script>

CHECKLIST_JAVASCRIPT;

        $this->out($js);
    }


    function tooltip_js()
    {
        static $tooltip_js_done;

        if (isset($tooltip_js_done)) return;
        $tooltip_js_done = 1;

        $js = <<<TOOLTIP_JAVASCRIPT

        <script language="javascript">
        <!--

        function wrap(text)
        {
            var width = 75;

            var words = text.split(' ');
            var word = '';
            var output = '';
            var line = '';
            var temp = '';
            for (var i = 0; i < words.length; i++)
            {
                word = words[i];
                if (word.length > width)
                {
                    if (line != '') line += ' ';
                    output += line+word.substring(0, width-line.length)+'<br>';
                    line = word.substr(width-line.length, word.length);
                }
                else
                {
                    temp = line + ' ' + word;
                    if (temp.length > width)
                    {
                        output += line + '<br>';
                        line = word;
                    }
                    else
                        line = temp;
                }
            }

            output += line;

            return output;
        }

        function open_window(id)
        {
            var width = 600;
            var height = 600;
            var param = "toolbar=no,location=no,status=no,scrollbars=yes," +
                "resizable=yes,width=" + width + ",height=" + height +
                ",left=0,top=0";
            var win = window.open('', 'notespopup', param);
            win.focus();

            var doc = win.document;

            if (id == null) { id = ''; }

            doc.open();
            doc.write('<p><b>Category:</b> ');
            doc.write(document.forms.checklistform['category'+id].value);
            doc.write('<p><b>Description:</b> ');
            doc.write(document.forms.checklistform['description'+id].value);
            doc.write('<form>');
            doc.write('<p><b>Notes:</b><br>');
            doc.write('<textarea name="notes" cols="60" rows="20" ' +
                'wrap="virtual">');
            doc.write(document.forms.checklistform['notes'+id].value);
            doc.write('</textarea>');
            doc.write('<br>');
            doc.write('<input type="button" value="Save" ' +
                'onClick="opener.document.forms.' +
                'checklistform[\'notes'+id+'\'].value=this.form.notes.value;' +
                'opener.document.getElementById(\'notesspan'+id+'\').style.' +
                'color=(this.form.notes.value==\'\')?\'gray\':\'\';' +
                'window.close();">&nbsp;');
            doc.write('<input type="button" value="Cancel" ' +
                'onClick="window.close();">');
            doc.write('</form>');
            doc.close();
        }

        /**
        * Tooltip Javascript
        *
        * Provides the javascript to display tooltips.
        */

        var isIE = document.all ? true : false;
        var activeTimeout;

        if (!isIE) {
            document.captureEvents(Event.MOUSEMOVE);
            document.onmousemove = mousePos;
            var netX, netY;
        }

        function posX()
        {
            tempX = document.body.scrollLeft + event.clientX;
            if (tempX < 0) {
                tempX = 0;
            }
            return tempX;
        }

        function posY()
        {
            tempY = document.body.scrollTop + event.clientY;
            if (tempY < 0) {
                tempY = 0;
            }
            return tempY;
        }

        function mousePos(e)
        {
            netX = e.pageX;
            netY = e.pageY;
        }

        function tooltipShow(pX, pY, src)
        {
            if (pX < 1) {
                pX = 1;
            }
            if (pY < 1) {
                pY = 1;
            }
            if (isIE) {
                document.all.tooltip.style.visibility = 'visible';
                document.all.tooltip.innerHTML = src;
                document.all.tooltip.style.left = pX + 'px';
                document.all.tooltip.style.top = pY + 'px';
            } else {
                document.getElementById('tooltip').style.visibility = 'visible';
                document.getElementById('tooltip').style.left = pX + 'px';
                document.getElementById('tooltip').style.top = pY + 'px';
                document.getElementById('tooltip').innerHTML = src;
            }
        }

        function tooltipClose()
        {
            if (isIE) {
                document.all.tooltip.innerHTML = '';
                document.all.tooltip.style.visibility = 'hidden';
            } else {
                document.getElementById('tooltip').style.visibility = 'hidden';
                document.getElementById('tooltip').innerHTML = '';
            }
            clearTimeout(activeTimeout);
        }

        function tooltipLink(tooltext)
        {
            text = '<div class="tooltip">' + tooltext + '</div>';
            if (isIE) {
                xpos = posX();
                ypos = posY();
            } else {
                xpos = netX;
                ypos = netY;
            }
            activeTimeout = setTimeout('tooltipShow(xpos - 110, ypos + 15, ' +
                'text);', 300);
        }

        document.write('<div id="tooltip" style="position: absolute; ' +
            'visibility: hidden;"></div>');

        //-->
        </script>

TOOLTIP_JAVASCRIPT;

        $this->out($js);
    }


    function calendar_js()
    {
        static $calendar_js_done;

        if (isset($calendar_js_done)) return;
        $calendar_js_done = 1;

        $js = <<<CALENDAR_JAVASCRIPT

        <script language="javascript">
        <!--

        var currentDate, currentYear, curImgId;

        function openCalendar(imgId, target)
        {
            dateparts = target.value.split('-');

            if (dateparts[0] && !isNaN(dateparts[0])
                && dateparts[1] && !isNaN(dateparts[1])
                && dateparts[2] && !isNaN(dateparts[2]))
                var d = new Date(dateparts[0], dateparts[1]-1, dateparts[2]);
            else
                var d = new Date();

            var e = d;

            openGoto(d.getTime(), imgId, target);
        }

        function openGoto(timestamp, imgId, target)
        {
            var row, cell, img, link, days;

            var d = new Date(timestamp);
            currentDate = d;
            var month = d.getMonth();
            var year = d.getYear();
            if (year < 1900) {
                year += 1900;
            }
            currentYear = year;
            var firstOfMonth = new Date(year, month, 1);
            var diff = firstOfMonth.getDay() - 1;
            if (diff == -1) {
                diff = 6;
            }
            switch (month) {
            case 3:
            case 5:
            case 8:
            case 10:
                days = 30;
                break;

            case 1:
                if (year % 4 == 0 && (year % 100 != 0 || year % 400 == 0)) {
                    days = 29;
                } else {
                    days = 28;
                }
                break;

            default:
                days = 31;
                break;
            }

            var wdays = ['Mo','Tu','We','Th','Fr','Sa','Su'];

            var months = ['January','February','March','April','May','June',
                'July','August','September','October','November','December'];

            var layer = document.getElementById('goto');
            if (layer.firstChild) {
                layer.removeChild(layer.firstChild);
            }

            var table = document.createElement('TABLE');
            var tbody = document.createElement('TBODY');
            table.appendChild(tbody);
            table.className = 'calendar_item';
            table.cellSpacing = 0;
            table.cellPadding = 2;
            table.border = 0;

            // Title bar.
            row = document.createElement('TR');

            cell = document.createElement('TD');
            cell.colSpan = 5;
            cell.align = 'left';
            cell.className = 'calendar_header';
            link = document.createElement('A');
            link.href = '#';
            link.onclick = function() {
                var today = new Date();
                var year = today.getYear();
                if (year < 1900) { year += 1900; }
                var month = today.getMonth() + 1;
                var day = today.getDate();

                target.value = year + '-' +
                    ((month<10)?'0':'') + month + '-' +
                    ((day<10)?'0':'') + day;

                var layer = document.getElementById('goto');
                layer.style.visibility = 'hidden';
                if (layer.firstChild) {
                    layer.removeChild(layer.firstChild);
                }

                return false;
            }
            img = document.createElement('IMG')
            img.src = 'images/ChecklistMaster/today.png';
            img.border = 0;
            link.appendChild(img);

            algn = document.createElement('DIV');
            algn.align = 'left';
            algn.appendChild(link);

            cell.appendChild(algn);
            row.appendChild(cell);


            cell = document.createElement('TD');
            cell.colSpan = 2;
            cell.align = 'right';
            cell.className = 'calendar_header';
            link = document.createElement('A');
            link.href = '#';
            link.onclick = function() {
                var layer = document.getElementById('goto');
                layer.style.visibility = 'hidden';
                if (layer.firstChild) {
                    layer.removeChild(layer.firstChild);
                }
                return false;
            }
            img = document.createElement('IMG')
            img.src = 'images/ChecklistMaster/close.png';
            img.border = 0;
            link.appendChild(img);
            cell.appendChild(link);
            row.appendChild(cell);


            tbody.appendChild(row);

            // Year.
            row = document.createElement('TR');
            cell = document.createElement('TD');
            cell.align = 'left';
            link = document.createElement('A');
            link.href = '#';
            link.onclick = function() {
                newDate = new Date(currentYear - 1, currentDate.getMonth(),
                    currentDate.getDate());
                openGoto(newDate.getTime(), imgId, target);
                return false;
            }
            cell.appendChild(link);
            img = document.createElement('IMG')
            img.src = 'images/ChecklistMaster/left.png';
            img.align = 'middle';
            img.border = 0;
            link.appendChild(img);
            row.appendChild(cell);

            cell = document.createElement('TD');
            cell.colSpan = 5;
            cell.align = 'center';
            var y = document.createTextNode(year);
            cntr = document.createElement('CENTER');
            cntr.appendChild(y);
            cell.appendChild(cntr);
            row.appendChild(cell);

            cell = document.createElement('TD');
            cell.align = 'right';
            link = document.createElement('A');
            link.href = '#';
            link.onclick = function() {
                newDate = new Date(currentYear + 1, currentDate.getMonth(),
                    currentDate.getDate());
                openGoto(newDate.getTime(), imgId, target);
                return false;
            }
            cell.appendChild(link);
            img = document.createElement('IMG')
            img.src = 'images/ChecklistMaster/right.png';
            img.align = 'middle';
            img.border = 0;
            link.appendChild(img);
            row.appendChild(cell);
            tbody.appendChild(row);

            // Month name.
            row = document.createElement('TR');
            cell = document.createElement('TD');
            cell.align = 'left';
            link = document.createElement('A');
            link.href = '#';
            link.onclick = function() {
                newDate = new Date(currentYear, currentDate.getMonth() - 1,
                    currentDate.getDate());
                openGoto(newDate.getTime(), imgId, target);
                return false;
            }
            cell.appendChild(link);
            img = document.createElement('IMG')
            img.src = 'images/ChecklistMaster/left.png';
            img.align = 'middle';
            img.border = 0;
            link.appendChild(img);
            row.appendChild(cell);

            cell = document.createElement('TD');
            cell.colSpan = 5;
            cell.align = 'center';
            var m = document.createTextNode(months[month]);
            cntr = document.createElement('CENTER');
            cntr.appendChild(m);
            cell.appendChild(cntr);
            row.appendChild(cell);

            cell = document.createElement('TD');
            cell.align = 'right';
            link = document.createElement('A');
            link.href = '#';
            link.onclick = function() {
                newDate = new Date(currentYear, currentDate.getMonth() + 1,
                    currentDate.getDate());
                openGoto(newDate.getTime(), imgId, target);
                return false;
            }
            cell.appendChild(link);
            img = document.createElement('IMG')
            img.src = 'images/ChecklistMaster/right.png';
            img.align = 'middle';
            img.border = 0;
            link.appendChild(img);
            row.appendChild(cell);
            tbody.appendChild(row);

            // Weekdays.
            row = document.createElement('TR');
            for (var i = 0; i < 7; i++) {
                cell = document.createElement('TD');
                weekday = document.createTextNode(wdays[i]);
                cell.appendChild(weekday);
                row.appendChild(cell);
            }
            tbody.appendChild(row);

            // Rows.
            var week, italic;
            var count = 1;
            var today = new Date();
            var thisYear = today.getYear();
            if (thisYear < 1900) {
                thisYear += 1900;
            }

            var odd = true;
            for (var i = 1; i <= days; i++) {
                if (count == 1) {
                    row = document.createElement('TR');
                    row.align = 'right';
                    if (odd) {
                        row.className = 'calendar_item0';
                    } else {
                        row.className = 'calendar_item1';
                    }
                    odd = !odd;
                }
                if (i == 1) {
                    for (var j = 0; j < diff; j++) {
                        cell = document.createElement('TD');
                        row.appendChild(cell);
                        count++;
                    }
                }
                cell = document.createElement('TD');
                if (thisYear == year &&
                    today.getMonth() == month &&
                    today.getDate() == i) {
                    cell.style.border = '1px solid red';
                }

                link = document.createElement('A');
                algn = document.createElement('DIV');
                algn.align = 'right';
                algn.appendChild(link);
                cell.appendChild(algn);
                link.href = i;
                link.onclick = function() {
                    var day = this.href;
                    while (day.indexOf('/') != -1) {
                        day = day.substring(day.indexOf('/') + 1);
                    }

                    target.value = year + '-' +
                        ((month<9)?'0':'') + (month + 1) + '-' +
                        ((day<10)?'0':'') + day;

                    var layer = document.getElementById('goto');
                    layer.style.visibility = 'hidden';
                    if (layer.firstChild) {
                        layer.removeChild(layer.firstChild);
                    }

                    return false;
                }

                day = document.createTextNode(i);
                link.appendChild(day);

                row.appendChild(cell);
                if (count == 7) {
                    tbody.appendChild(row);
                    count = 0;
                }
                count++;
            }
            if (count > 1) {
                for (i = count; i <= 7; i++) {
                    cell = document.createElement('TD');
                    row.appendChild(cell);
                }
                tbody.appendChild(row);
            }

            if (curImgId != imgId) {
                // We're showing this popup for the first time, so try to
                // position it next to the image anchor.
                var el = document.getElementById(imgId);
                var p = getAbsolutePosition(el);

                // special adjustment for the wiki macro
                if (target.name.substr(0,12) == 'lastconfdate') { p.x -= 100; }

                layer.style.left = p.x + 'px';
                layer.style.top = p.y + 'px';
            }

            curImgId = imgId;
            layer.appendChild(table);

            layer.style.display = 'block';
            layer.style.visibility = 'visible';
        }

        function getAbsolutePosition(el)
        {
            var r = {x: el.offsetLeft, y: el.offsetTop};
            if (el.offsetParent) {
                var tmp = getAbsolutePosition(el.offsetParent);
                r.x += tmp.x;
                r.y += tmp.y;
            }
            return r;
        }

        //-->
        </script>


CALENDAR_JAVASCRIPT;

        $this->out($js);
    }


    function list_roles()
    {
        return chklst_query("select id, name from chklst_role " .
                            "order by lower(name)");
    }


    function list_categories($id)
    {
        if (isset($this->category_list))
            return $this->category_list;

        $categories = chklst_query("select distinct category, category foo " .
                                   "from chklst_checklist_details " .
                                   "where checklist = $id " .
                                   "order by lower(category)");
        $categories[-1] = '';

        $this->category_list = $categories;

        return $categories;
    }



    // *************************** users ***************************

    function list_users()
    {
        if (isset($this->users_list))
            return $this->users_list;

        $users = chklst_query("select id, name " .
                              "from chklst_owner " .
                              "order by name");

        $this->users_list = $users;

        return $users;
    }

    function mark_used_users(&$users)
    {
        $result = chklst_query("select distinct owner, owner foo " .
                               "from chklst_checklist_details");

        foreach ($users as $id => $name)
            if (isset($result[$id]))
                $users[$id] .= '*';
    }

    function user_add($name)
    {
        $this->list_users();

        if (in_array($name, $this->users_list)) return;

        $name = quote(trim($name));
        if (!$name) return;

        chklst_insert_query("insert into chklst_owner (name) " .
                            "values ('$name')");
    }

    function user_rename($id, $name)
    {
        $name = quote(trim($name));
        if (!$name) return;

        chklst_query("update chklst_owner set " .
                     "name = '$name' " .
                     "where id = $id");
    }

    function user_delete($id)
    {
        $result = chklst_query("select count(*) count " .
                               "from chklst_checklist_details " .
                               "where owner = $id");

        if ($result['count'] == 0)
            chklst_query("delete from chklst_owner " .
                         "where id = $id");
    }

    function do_edit_user()
    {
        switch ($_REQUEST['useraction'])
        {
            case 'add':
                $this->user_add($_REQUEST['username']);
                break;

            case 'rename':
                $this->user_rename($_REQUEST['userid'], $_REQUEST['username']);
                break;

            case 'delete':
                $this->user_delete($_REQUEST['userid']);
                break;
        }

        $this->redirect('template_list');
    }



    // *************************** template ***************************

    function list_templates()
    {
        return chklst_query("select id, name " .
                            "from chklst_template " .
                            "order by lower(name)");
    }

    function template_exists($id)
    {
        if (!$id) return false;

        $result = chklst_query("select id, name " .
                               "from chklst_template " .
                               "where id = $id");

        return isset($result[$id]);
    }

    function get_template_name($id)
    {
        if (!$id) return '';

        $result = chklst_query("select id, name " .
                               "from chklst_template " .
                               "where id = $id");

        return $result[$id];
    }

    function get_template_details($id = null)
    {
        if (!$id) return array();

        return chklst_query("select id, category, description, " .
                            "owner " .
                            "from chklst_template_details  " .
                            "where template = $id " .
                            "order by torder");
    }

    function create_template($name)
    {
        $name = quote(trim($name));
        if (!$name)
            $name = "Unnamed " . date('Y-M-d H:i:s');

        $id = chklst_insert_query("insert into chklst_template (name) " .
                                  "values ('$name')");

        return $id;
    }

    function save_template($id, $name)
    {
        $name = quote(trim($name));
        if (!$name)
            $name = "Unnamed " . date('Y-M-d H:i:s');

        chklst_query("update chklst_template set " .
                     "name = '$name' " .
                     "where id = $id");
    }

    function delete_template($id)
    {
        chklst_query("delete from chklst_template_details " .
                     "where template = $id");
        chklst_query("delete from chklst_template " .
                     "where id = $id");
    }

    function save_template_row($id, $template, $category,
        $description, $owner, $before_id = -1)
    {
        if (!$template) return;

        $category = quote(trim($category));
        $description = quote(trim($description));

        if ($id)
        {
            $id = chklst_query("update chklst_template_details set " .
                               "category = '$category', " .
                               "description = '$description', " .
                               "owner = $owner " .
                               "where id = $id " .
                               "and template = $template");
        }
        else
        {
            if ($before_id == -1)
            {
                $order = chklst_query("select ifnull(max(torder)+1, 0) torder ".
                                      "from chklst_template_details " .
                                      "where template = $template");
                $order = $order['torder'];
            }
            else
            {
                $order = chklst_query("select torder ".
                                      "from chklst_template_details " .
                                      "where id = $before_id");
                $order = $order['torder'];

                chklst_query("update chklst_template_details " .
                             "set torder=torder+1 " .
                             "where torder >= $order " .
                             "and template = $template");
            }

            $id = chklst_insert_query("insert into chklst_template_details (" .
                                      "template, category, description, " .
                                      "owner, torder) " .
                                      "values ($template, '$category', " .
                                      "'$description', $owner, $order)");

            // renumber order
            /*
            $row_ids = chklst_query("select id, id id2 " .
                                    "from chklst_template_details " .
                                    "where template = $template " .
                                    "order by torder");
            $i = 0;
            foreach ($row_ids as $row_id)
            {
                chklst_query("update chklst_template_details " .
                             "set torder = $i " .
                             "where id=$row_id " .
                             "and template= $template");
                $i++;
            }
            */
        }
    }

    function delete_template_row($id)
    {
        chklst_query("delete from chklst_template_details " .
                     "where id = $id");
    }

    function do_template_list()
    {
        $templates = $this->list_templates();

        $this->out('<p><h2>Templates</h2>');

        foreach ($templates as $id => $name)
        {
            $this->out('<p>');
            $this->form_button('template_edit', 'Edit', $id);
            $this->form_button('template_delete', 'Delete', $id, true);
            $this->out($name);
        }
        if (!$templates) {
            $this->out('<i>No templates</i>');
        }

        $this->out('<p>');
        $this->form_button('template_edit', 'New', 0);


        $users = $this->list_users();
        $this->mark_used_users($users);

        $this->out('<p>');
        $this->out('<b>Edit owners:</b> ');
        $this->form_select("userid", '', '', $users);
        $this->form_input('username', '', 20);
        $this->form_radio('useraction', 'add', 'Add', true);
        $this->form_radio('useraction', 'rename', 'Rename', false);
        $this->form_radio('useraction', 'delete', 'Delete', false);
        $this->form_button('usergo', 'Go');
        $this->out('<br><small>(Owners marked with a * are currently used ' .
                   'and cannot be deleted.)</small>');
    }

    function print_edit_template_row($row, $roles, $last_row)
    {
        $this->out('<tr><td>');
        if ($row['id'])
            $this->form_checkbox('delete'.$row['id'], $name, false);
        $this->out('</td><td>');
        if ($row['id'])
            $this->form_radio('addposid', $row['id'], '', $last_row);
        else
            $this->form_select('addpos', 'append', 'Append',
                array('before' => 'Add Before'));
        $this->out('</td><td>');
        $this->form_input('category'.$row['id'], $row['category'], 35);
        $this->out('</td><td>');
        $this->form_input('description'.$row['id'], $row['description'], 60);
        $this->out('</td><td>');
        $this->form_select('owner'.$row['id'], $row['owner'], '', $roles);
        $this->out('</td></tr>');
    }

    function do_template_edit()
    {
        $this->mystyle();

        $id = $_REQUEST['id'];

        if ($id && !$this->template_exists($id))
            $this->redirect('template_list');

        $template_details = $this->get_template_details($id);
        $name = $this->get_template_name($id);

        $roles = $this->list_roles();
        $roles[-1] = '';

        if ($id)
            $this->out('<p><h2>Edit Template</h2>');
        else
            $this->out('<p><h2>New Template</h2>');

        $this->form_hidden('id', $id);

        $this->out('<p>');
        $this->form_button('template_apply', 'Apply');
        $this->form_button('template_save', 'Save & Close');
        $this->form_button('template_list', 'Cancel');

        $this->out('<p><b>Name:</b> ');
        $this->form_input('name', $name, 40);

        $this->out('<p>');
        $this->table('table');
        $this->out('<tr>
            <th>Delete</th>
            <th></th>
            <th>Category</th>
            <th>Description</th>
            <th>Owner</th>
            </tr>');

        $i = 1;
        $last_owner = '';
        foreach ($template_details as $id => $details)
        {
            $this->print_edit_template_row($details, $roles,
                $i == count($template_details));
            $i++;
            $last_owner = $details['owner'];
        }
        $this->form_hidden('templaterowids',
            implode(',', array_keys($template_details)));
        $this->print_edit_template_row(array('owner' => $last_owner), $roles,
                                       false);
        $this->table_end();

        $this->out('<p><b>Import:</b> ');
        $this->form_file('import', 20);
        $this->form_button('template_export', 'Export');

        $this->out('<p>');
        $this->form_button('template_apply', 'Apply');
        $this->form_button('template_save', 'Save & Close');
        $this->form_button('template_cancel', 'Cancel');
    }

    function do_template_save()
    {
        if (!$_REQUEST['id'])
            $_REQUEST['id'] = $this->create_template($_REQUEST['name']);

        $this->save_template($_REQUEST['id'], $_REQUEST['name']);

        if ($_REQUEST['description'])
        {
            if (!$_REQUEST['addposid'] || $_REQUEST['addpos'] == 'append')
                $before_id = -1;
            else
                $before_id = $_REQUEST['addposid'];

            $this->save_template_row(null, $_REQUEST['id'],
                $_REQUEST['category'], $_REQUEST['description'],
                $_REQUEST['owner'], $before_id);
        }

        $template_row_ids = explode(',', $_REQUEST['templaterowids']);
        foreach ($template_row_ids as $row_id)
        {
            if ($row_id == '') continue;

            if ($_REQUEST["delete$row_id"])
            {
                $this->delete_template_row($row_id);
            }
            else
            {
                $this->save_template_row($row_id, $_REQUEST['id'],
                    $_REQUEST["category$row_id"],
                    $_REQUEST["description$row_id"],
                    $_REQUEST["owner$row_id"]);
            }
        }

        if (isset($_FILES['import']['tmp_name']))
        {
            $roles = $this->list_roles();

            $roles_ex = array();
            foreach ($roles as $role_id => $role)
            {
                $roles_ex[] = array('id' => $role_id, 'name' => $role);
                $words = split(' ', $role);
                if (count($words) > 1)
                {
                    $new_role = '';
                    foreach ($words as $word)
                        $new_role .= substr($word, 0, 1);
                    $roles_ex[] = array('id' => $role_id, 'name' => $new_role);
                }
            }

            $content = file_get_contents($_FILES['import']['tmp_name']);
            $content = explode("\n", $content);

            $category = '';
            foreach ($content as $line)
            {
                if (preg_match('/^\s+/', $line))
                {
                    $description = trim($line);
                    $owner = 'null';

                    foreach ($roles_ex as $role_ex)
                    {
                        $role = preg_quote($role_ex['name']);
                        if (preg_match("/^$role\s+(.*)$/i", $description,
                                       $matches))
                        {
                            $owner = $role_ex['id'];
                            $description = $matches[1];
                            break;
                        }
                    }

                    $description = magic_quotes(trim($description));

                    $this->save_template_row(null, $_REQUEST['id'],
                        $category, $description, $owner, -1);
                }
                else
                    $category = magic_quotes(trim($line));
            }
        }
    }

    function do_template_delete()
    {
        if ($_REQUEST['id'])
        {
            $this->delete_template($_REQUEST['id']);
        }

        $this->redirect('template_list');
    }

    function do_template_export()
    {
        $name = $this->get_template_name($_REQUEST['id']);
        $template_details = $this->get_template_details($_REQUEST['id']);
        $roles = $this->list_roles();

        $output = '';
        $category = '';
        foreach ($template_details as $row)
        {
            if ($row['category'] != $category)
            {
                $category = $row['category'];
                $output .= $row['category'] . "\n";
            }

            if ($row['owner']) $output .= "\t" . $roles[$row['owner']];
            $output .= "\t" . $row['description'] . "\n";
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: filename="' . $name . '"');

        print $output;
        exit;
    }



    // *************************** checklist ***************************

    function checklist_exists($id)
    {
        if (!$id) return false;

        $result = chklst_query("select id, name " .
                               "from chklst_checklist " .
                               "where id = $id");

        return isset($result[$id]);
    }

    function get_checklist_id($name)
    {
        $name = quote(trim($name));

        $result = chklst_query("select id " .
                               "from chklst_checklist " .
                               "where name = '$name'");

        return isset($result['id']) ? $result['id'] : false;
    }

    function get_checklist_details($id, $sort = '')
    {
        switch ($sort)
        {
            case 'owner':
                $tmp_col = '';
                $sort_col = "lower(ifnull(o.name, 'zzzzzzzzzzzzzzz')), ";
                $join = 'left join chklst_owner o on d.owner = o.id ';
                break;

            case 'duedate':
                $tmp_col = ", ifnull(d.duedate, '9999-99-99') datesort";
                $sort_col = 'datesort, ';
                $join = '';
                break;

            case 'lastconfdate':
                $tmp_col = ", ifnull(d.lastconfdate, '9999-99-99') datesort";
                $sort_col = 'datesort, ';
                $join = '';
                break;

            default:
                $tmp_col = '';
                $sort_col = '';
                $join = '';
        }

        $result = chklst_query("select d.id, d.category, d.description, d.owner, " .
                               "d.duedate, d.lastconfdate, d.notes, d.noteshidden, " .
                               "d.status$tmp_col " .
                               "from chklst_checklist_details d " . $join .
                               "where checklist = $id " .
                               "order by ${sort_col}d.torder");

        return $result;
    }

    function create_checklist($name)
    {
        $name = quote(trim($name));
        return chklst_insert_query("insert into chklst_checklist (name) " .
                                   "values ('$name')");
    }

    function delete_checklist($id)
    {
        chklst_query("delete from chklst_checklist_details " .
                     "where checklist = $id");
        chklst_query("delete from chklst_checklist " .
                     "where id = $id");
    }

    function validate_date(&$date)
    {
        if ($date === '')
        {
            $date = 'null';
            return;
        }

        list($year, $month, $day) = explode('-', $date, 3);
        if (!is_numeric($year)) $year = date('Y');
        if (!is_numeric($month)) $month = date('m');
        if (!is_numeric($day)) $day = date('d');
        $date = "'" . date('Y-m-d', mktime(0, 0, 0, $month, $day, $year)) . "'";
    }

    function save_checklist_row($id, $checklist_id, $category, $description,
        $owner_id, $duedate = '', $lastconfdate = '', $notes = '',
        $noteshidden = 0, $before_id = -1)
    {
        $category = quote(trim($category));
        $description = quote(trim($description));
        $notes = quote(trim($notes));
        $noteshidden = $noteshidden ? 1 : 0;
        if (!$owner_id || $owner_id == -1) $owner_id = 'null';
        $this->validate_date($duedate);
        $this->validate_date($lastconfdate);

        if ($id)
        {
            $id = chklst_query("update chklst_checklist_details set " .
                               "category = '$category', " .
                               "description = '$description', " .
                               "owner = $owner_id, duedate = $duedate, " .
                               "lastconfdate = $lastconfdate, " .
                               "notes = '$notes', " .
                               "noteshidden = $noteshidden " .
                               "where id = $id " .
                               "and checklist = $checklist_id");
        }
        else
        {
            if ($before_id == -1)
            {
                $order = chklst_query("select ifnull(max(torder)+1, 0) torder ".
                                      "from chklst_checklist_details " .
                                      "where checklist = $checklist_id");
                $order = $order['torder'];
            }
            else
            {
                $order = chklst_query("select torder ".
                                      "from chklst_checklist_details " .
                                      "where id = $before_id");
                $order = $order['torder'];

                chklst_query("update chklst_checklist_details " .
                             "set torder=torder+1 " .
                             "where torder >= $order " .
                             "and checklist = $checklist_id");
            }

            return chklst_insert_query("insert into chklst_checklist_details (".
                                       "checklist, category, description, " .
                                       "owner, duedate, lastconfdate, notes, " .
                                       "noteshidden, torder) " .
                                       "values ($checklist_id, '$category', " .
                                       "'$description', $owner_id, " .
                                       "$duedate, $lastconfdate, '$notes', " .
                                       "$noteshidden, $order)");
        }
    }

    function delete_checklist_row($id)
    {
        chklst_query("delete from chklst_checklist_details " .
                     "where id = $id");
    }

    function update_checklist_status($id, $status)
    {
        $status = $status ? 1 : 0;

        chklst_query("update chklst_checklist_details " .
                     "set status = $status " .
                     "where id = $id");
    }

    function update_checklist_notes($id, $notes = '', $noteshidden = 0)
    {
        $notes = quote(trim($notes));
        $noteshidden = $noteshidden ? 1 : 0;
        chklst_query("update chklst_checklist_details " .
                     "set notes = '$notes', " .
                     "noteshidden = $noteshidden " .
                     "where id = $id");
    }

    function do_checklist_create_form($name)
    {
        $templates = $this->list_templates();
        $roles= $this->list_roles();
        $users = $this->list_users();

        $this->out('<p><h2>Create Checklist</h2>');

        $this->out("<p><b>Name:</b> $name");

        $this->out('<p><b>Template:</b> ');
        $this->form_select('template', '', '--Select Template--', $templates);

        $this->out('<p><b>Roles</b>');
        $this->out('<p>');

        $this->table('table');
        foreach ($roles as $role_id => $role)
        {
            $this->out('<tr><td>');
            $this->out($role);
            $this->out('</td><td>');
            $this->form_select("user$role_id", '', '', $users);
            $this->out('</td></tr>');
        }
        $this->table_end();

        $this->out('<p>');
        $this->form_button('checklist_create', 'Create');
    }

    function do_checklist_create($name, $template, $roles_users)
    {
        $id = $this->create_checklist($name);

        $template_details = $this->get_template_details($template);
        foreach ($template_details as $template_row)
        {
            $category = magic_quotes($template_row['category']);
            $description = magic_quotes($template_row['description']);
            $owner = $roles_users[$template_row['owner']];

            $this->save_checklist_row(null, $id, $category, $description,
                                      $owner);
        }

        $this->redirect('checklist_edit');
    }

    // when used in edit mode, $edit_link is true
    // when used in view mode, $edit_link is false
    function notes_link($id, $notes, $edit_link = true)
    {
        $tooltip = ' onmouseover="var frm=document.forms.checklistform;' .
                   'if(frm.notes'.$id.'.value!=\'\'){' .
                   'tooltipLink(wrap(frm.notes'.$id.'.value));}return true;" ' .
                   'onmouseout="var frm=document.forms.checklistform;' .
                   'if(frm.notes'.$id.'.value!=\'\'){tooltipClose();}"';
        $edit_link_js = $edit_link ? 'open_window('.$id.')' : '';
        $link = '<a href="javascript:'.$edit_link_js.';"' . $tooltip . '>';
        $spanstyle = strlen($notes) ? '' : 'style="color: gray;"';
        $label = $edit_link ? 'Notes' : 'Hidden';
        $link .= '<span id="notesspan'.$id.'"'.$spanstyle.'>'.$label.
                 '</span></a>';

        return $link;
    }

    function print_edit_checklist_row($row, $users, $last_row, $checklist_id)
    {
        $this->out('<tr>');
        if ($row['id'])
        {
            $this->out('<td>');
            $this->form_checkbox('delete'.$row['id'], $name, false);
            $this->out('</td><td>');
            $this->form_radio('addposid', $row['id'], '', $last_row);
        }
        else
        {
            $this->out('<td colspan="2">');
            $this->form_select('addpos', 'append', 'Append',
                array('before' => 'Add Before'), true);
        }
        $this->out('</td><td>');
        $this->out($this->notes_link($row['id'], $row['notes']));
        $this->form_hidden('notes'.$row['id'], $row['notes']);
        $this->form_hidden('noteshidden'.$row['id'], $row['noteshidden']);
        $this->out('</td><td>');
        $this->form_input('category'.$row['id'], $row['category'], 35, true);
        if ($row['id'] == '')
        {
            $categories = $this->list_categories($checklist_id);
            foreach ($categories as $key => $value)
                if (strlen($value) > 28)
                    $categories[$key] = substr($value, 0, 28) . '...';
            $this->form_select('categorylist', $row['category'], '',
                               $categories, true);
        }
        $this->out('</td><td>');
        $this->form_input('description'.$row['id'], $row['description'], 60,
                          true);
        $this->out('</td><td>');
        $this->form_select('owner'.$row['id'], $row['owner'], '', $users, true);
        $this->out('</td><td nowrap>');

        $this->form_input('duedate'.$row['id'], $row['duedate'], 10, true);
        $this->out('<a id="caldue'.$row['id'].'" ' .
                   'href="javascript:openCalendar(\'caldue'.$row['id'].'\', ' .
                   'document.forms.checklistform.duedate'.$row['id'].');" ' .
                   'onmouseout="window.status=\'\';" ' .
                   'onmouseover="window.status=\'Select a date\'; ' .
                   'return true;">' .
                   '<img src="images/ChecklistMaster/calendar.png" ' .
                   'alt="Select date" title="Select date" width="16" ' .
                   'height="16" border="0"></a>');

        $this->out('</td><td nowrap>');

        $this->form_input('lastconfdate'.$row['id'], $row['lastconfdate'], 10,
                          true);
        $this->out('<a id="callast'.$row['id'].'" ' .
            'href="javascript:openCalendar(\'callast'.$row['id'].'\', ' .
            'document.forms.checklistform.lastconfdate'.$row['id'].');" ' .
            'onmouseout="window.status=\'\';" ' .
            'onmouseover="window.status=\'Select a date\'; ' .
            'return true;">' .
            '<img src="images/ChecklistMaster/calendar.png" ' .
            'alt="Select date" title="Select date" width="16" ' .
            'height="16" border="0"></a>');

        $this->out('</td></tr>');
    }

    function do_checklist_edit($name)
    {
        $this->mystyle();
        $this->tooltip_js();
        $this->calendar_js();

        $id = $this->get_checklist_id($name);
        if (!$id)
        {
            $this->do_checklist_create_form($name);
            return;
        }

        $checklist_details = $this->get_checklist_details($id);

        $users = $this->list_users();
        $users[-1] = '';

        $this->out('<div id="goto" class="control" style="position:absolute;' .
                   'visibility:hidden;padding:1px"></div>');

        $this->out('<p><h2>Edit Checklist</h2>');

        $this->out('<p>');
        $this->form_button('checklist_apply', 'Apply');
        $this->form_button('checklist_save', 'Save & Close');
        $this->form_button('checklist_delete', 'Delete Checklist', $id, true);
        $this->form_button('checklist_use', 'Cancel');

        $this->out("<p><b>Name:</b> $name");

        $this->out('<p>');
        $this->table('table');
        $this->out('<tr>
            <th>Del</th>
            <th></th>
            <th>Notes</th>
            <th>Category</th>
            <th>Description</th>
            <th>Owner</th>
            <th nowrap>Due Date<br><small>(yyyy-mm-dd)</small></th>
            <th nowrap>Last Conf. Date<br><small>(yyyy-mm-dd)</small></th>
            </tr>');

        $i = 1;
        foreach ($checklist_details as $row)
        {
            $this->print_edit_checklist_row($row, $users,
                $i == count($checklist_details), $id);
            $i++;
        }
        $this->form_hidden('checklistrowids',
            implode(',', array_keys($checklist_details)));
        $this->print_edit_checklist_row(array(), $users, false, $id);
        $this->table_end();

        $this->out('<p>');
        $this->form_button('checklist_apply', 'Apply');
        $this->form_button('checklist_save', 'Save & Close');
        $this->form_button('checklist_delete', 'Delete Checklist', $id, true);
        $this->form_button('checklist_use', 'Cancel');
    }

    function do_checklist_save($name)
    {
        $id = $this->get_checklist_id($name);
        if (!$id)
        {
            $this->do_checklist_create_form($name);
            return;
        }

        if ($_REQUEST['description'])
        {
            if (!$_REQUEST['addposid'] || $_REQUEST['addpos'] == 'append')
                $before_id = -1;
            else
                $before_id = $_REQUEST['addposid'];

            if ($_REQUEST['categorylist'] != -1)
                $_REQUEST['category'] = $_REQUEST['categorylist'];

            $this->save_checklist_row(null, $id, $_REQUEST['category'],
                $_REQUEST['description'], $_REQUEST['owner'],
                $_REQUEST['duedate'], $_REQUEST['lastconfdate'],
                $_REQUEST['notes'], $_REQUEST['noteshidden'], $before_id);
        }

        $checklist_row_ids = explode(',', $_REQUEST['checklistrowids']);
        foreach ($checklist_row_ids as $row_id)
        {
            if ($row_id == '') continue;

            if ($_REQUEST["delete$row_id"])
            {
                $this->delete_checklist_row($row_id);
            }
            else
            {
                $this->save_checklist_row($row_id, $id,
                    $_REQUEST["category$row_id"],
                    $_REQUEST["description$row_id"],
                    $_REQUEST["owner$row_id"],
                    $_REQUEST["duedate$row_id"],
                    $_REQUEST["lastconfdate$row_id"],
                    $_REQUEST["notes$row_id"],
                    $_REQUEST["noteshidden$row_id"]);
            }
        }
    }

    function do_checklist_delete($name)
    {
        $id = $this->get_checklist_id($name);

        if ($id) $this->delete_checklist($id);

        $this->do_checklist_create_form($name);
    }

    function create_wiki_name($name)
    {
        global $LinkPtn;

        // if already a wiki link
        if (preg_match("/$LinkPtn/", $name)) return $name;

        // clean the name and return if only one word
        $name = preg_replace('/[^0-9a-z ]/', '', strtolower($name));
        if (strpos($name, ' ') === false) return $name;

        // glue the words into a wiki link
        return str_replace(' ', '', ucwords($name));
    }

    function params_url($name = null, $value = null)
    {
        $params = array('hidecat', 'sort', 'hidecompleted');

        $url_params = array();
        foreach ($params as $param)
            if (isset($_REQUEST[$param]))
                $url_params[$param] = $_REQUEST[$param];
        if (isset($name))
            if (isset($value))
                $url_params[$name] = $value;
            else
                unset($url_params[$name]);

        $url = '';
        foreach ($url_params as $param => $value)
            $url .= "&$param=" . rawurlencode($value);

        return $_SERVER["PHP_SELF"] . '?page=' . $_REQUEST['page'] . $url;
    }

    function show_hide_category_link($cat, $hide_cat)
    {
        $temp_hide_cat = $hide_cat;

        if (($key = array_search($cat, $hide_cat)) === false)
        {
            $image = 'expanded.png';
            $alt = 'Collapse Category';
            $temp_hide_cat[] = $cat;
        }
        else
        {
            $image = 'collapsed.png';
            $alt = 'Expand Category';
            unset($temp_hide_cat[$key]);
        }

        if (!$temp_hide_cat)
            $temp_hide_cat = null;
        else
            $temp_hide_cat = implode(',', $temp_hide_cat);

        return '<a href="'.$this->params_url('hidecat',$temp_hide_cat).'">'.
               '<img src="images/ChecklistMaster/'.$image.'" '.
               'alt="'.$alt.'" hspace="2" title="'.$alt.'" '.
               'width="9" height="9" border="0"></a>';
    }

    function do_checklist_use($name)
    {
        $this->mystyle();
        $this->tooltip_js();
        $this->checklist_js();

        $id = $this->get_checklist_id($name);
        if (!$id)
        {
            $this->do_checklist_create_form($name);
            return;
        }

        $checklist_details =
            $this->get_checklist_details($id, $_REQUEST['sort']);

        $users = $this->list_users();

        if (isset($_REQUEST['hidecat']))
            $hide_cat = explode(',', $_REQUEST['hidecat']);
        else
            $hide_cat = array();


        $this->out("<h1>$name</h1>");

        $this->out('<p>');
        $this->form_button('checklist_update', 'Submit');
        $this->form_button('checklist_edit', 'Edit Checklist');

        $this->out('<p>');
        if ($_REQUEST['hidecompleted'])
            $this->out('<a href="'.$this->params_url('hidecompleted').
                       '">Show completed tasks</a>');
        else
            $this->out('<a href="'.$this->params_url('hidecompleted', 1).
                       '">Hide completed tasks</a>');

        $sort_img = '<img src="images/ChecklistMaster/sort.gif" alt="Sort" ' .
                    'hspace="2" title="Sort" width="13" height="13" ' .
                    'border="0">';
        $sort_owner = ($_REQUEST['sort'] == 'owner') ? $sort_img : '';
        $sort_due = ($_REQUEST['sort'] == 'duedate') ? $sort_img : '';
        $sort_last = ($_REQUEST['sort'] == 'lastconfdate') ? $sort_img : '';
        $sort_cat = (!$sort_owner && !$sort_due && !$sort_last) ? $sort_img:'';

        $this->out('<p>');
        $this->table('table');
        $this->out('<tr><td nowrap><a href="'.$this->params_url('sort').'">' .
                   '<b>Category</b></a>'.$sort_cat.'</td><td></td><td>' .
                   '<b>Description</b></td><td nowrap>' .
                   '<a href="'.$this->params_url('sort','owner').'">' .
                   '<b>Owner</b>'.$sort_owner.'</a></td><td nowrap>' .
                   '<a href="'.$this->params_url('sort','duedate').'">' .
                   '<b>Due Date</b>'.$sort_due.'</a></td><td nowrap>' .
                   '<a href="'.$this->params_url('sort','lastconfdate').'">' .
                   '<b>Last Conf. Date</b>'.$sort_last.'</a></td><td>' .
                   '<b>Notes</b></td></tr>');

        $category = '';
        foreach ($checklist_details as $row)
        {
            if ($sort_cat && $row['category'] != $category)
            {
                $this->out('<tr><td colspan="7">');
                $this->out($this->show_hide_category_link($row['category'],
                           $hide_cat));
                $parsed_category = parseText($row['category'],
                    $this->parsing_rules, $this->page);
                $this->out($parsed_category);
                $this->out('</td></tr>');
                $category = $row['category'];
            }

            if (in_array($row['category'], $hide_cat))
                continue;

            if ($_REQUEST['hidecompleted'] && $row['status'] == 1)
                continue;

            $class = $row['status'] == 1 ? 'class="done"' : '';
            $this->out('<tr valign="top" id="row'.$row['id'].'"><td>');
            if (!$sort_cat)
            {
                $this->out($this->show_hide_category_link($row['category'],
                           $hide_cat));
                $this->out($row['category']);
            }
            $this->out('</td><td>');
            $this->form_hidden('prevstatus'.$row['id'], $row['status']);
            $this->form_checkbox('status'.$row['id'], '', $row['status']==1,
                'setRowStyle('.$row['id'].', this.checked);');
            $this->out('</td><td id="col0row'.$row['id'].'" '.$class.'>');
            $this->out('<label for="status'.$row['id'].'">');
            $parsed_description = parseText($row['description'],
                $this->parsing_rules, $this->page);
            $this->out($parsed_description);
            $this->out('</label></td>');
            $this->out('<td id="col1row'.$row['id'].'" '.$class.' nowrap>');
            $this->out('<a href="?'.
                $this->create_wiki_name($users[$row['owner']])
                .'">'.$users[$row['owner']].'</a>');
            $this->out('</td>');

            $tmp_date = str_replace('-', '', $row['duedate']);
            $tmp_style = ($tmp_date && $row['status'] != 1 &&
                          $tmp_date < date('Ymd')) ? 'style="color: red" ' : '';
            $this->out('<td id="col2row'.$row['id'].'" '.$class.' '.
                       $tmp_style.'nowrap>');
            $this->out($row['duedate']);
            $this->out('</td>');

            $tmp_date = str_replace('-', '', $row['lastconfdate']);
            $tmp_style = ($tmp_date && $row['status'] != 1 &&
                          $tmp_date < date('Ymd')) ? 'style="color: red" ' : '';
            $this->out('<td id="col3row'.$row['id'].'" '.$class.' '.
                       $tmp_style.'nowrap>');
            $this->out($row['lastconfdate']);
            $this->out('</td><td id="col4row'.$row['id'].'">');

            $this->out('<span id="notesview'.$row['id'].'">');
            $this->out('<a href="javascript:editNote('.$row['id'].');">'.
                       '<img src="images/ChecklistMaster/edit.gif" '.
                       'alt="Edit notes" title="Edit notes" align="left" '.
                       'width="13" height="14" border="0"></a>');
            if ($row['noteshidden']) {
                $this->out($this->notes_link($row['id'], $row['notes'], false));
            } else {
                $parsed_notes = parseText($row['notes'],
                    $this->parsing_rules, $this->page);
                $this->out($parsed_notes);
            }
            $this->out('</span>');

            $this->out('<span style="display: none;" id="notesedit'.
                       $row['id'].'">');
            $this->form_hidden('prevnotes'.$row['id'], $row['notes']);
            $this->out('<input type="button" value="Cancel" '.
                       'onClick="editNoteCancel('.$row['id'].')">');
            $this->form_hidden('prevnoteshidden'.$row['id'],
                               $row['noteshidden']);
            $this->form_checkbox('noteshidden'.$row['id'], '',
                                 $row['noteshidden']==1, '');
            $this->out('<label for="noteshidden'.$row['id'].'">Hide</label>');
            $this->out('<br><textarea name="notes'.$row['id'].'" '.
                       'cols="40" rows="10" wrap="virtual">'.
                       htmlspecialchars($row['notes']).
                       '</textarea>');
            $this->out('</span>');

            $this->out('</td></tr>');
        }
        $this->table_end();

        $this->form_hidden('checklistrowids',
            implode(',', array_keys($checklist_details)));

        $this->out('<p>');
        $this->form_button('checklist_update', 'Submit');
        $this->form_button('checklist_edit', 'Edit Checklist');
    }

    function do_checklist_row_update($name)
    {
        $id = $this->get_checklist_id($name);
        if (!$id)
        {
            $this->do_checklist_create_form($name);
            return;
        }

        $checklist_row_ids = explode(',', $_REQUEST['checklistrowids']);

        foreach ($checklist_row_ids as $row_id)
        {
            if ($row_id == '') continue;

            // status
            $status = $_REQUEST["status$row_id"];
            $prev_status = $_REQUEST["prevstatus$row_id"];
            if ((isset($status) && $prev_status == 0)
                || (!isset($status) && $prev_status == 1))
                $this->update_checklist_status($row_id, $status);

            // notes
            $notes = $_REQUEST["notes$row_id"];
            $prev_notes = $_REQUEST["prevnotes$row_id"];

            $noteshidden = isset($_REQUEST["noteshidden$row_id"]) ? 1 : 0;
            $prev_noteshidden = $_REQUEST["prevnoteshidden$row_id"];

            if ((isset($notes) && isset($prev_notes)
                 && $notes != $prev_notes)
                || (isset($prev_noteshidden)
                    && $noteshidden != $prev_noteshidden))
                $this->update_checklist_notes($row_id, $notes, $noteshidden);
        }

        $this->redirect('checklist_use');
    }


    // api function for the Toc macro
    function getCategories($name)
    {
        checklist_init();

        $id = $this->get_checklist_id($name);
        if (!$id) return array();

        $checklist_details = $this->get_checklist_details($id);

        $categories = array();
        $category = '';
        foreach ($checklist_details as $row)
        {
            if ($row['category'] != $category)
            {
                $category = $row['category'];
                $categories[] = $category;
            }
        }

        return $categories;
    }


    // main gracefultavi entry point
    function parse($args, $page)
    {
        if ($this->done) return '';

        $this->page = $page;

        global $chklst_h;
        checklist_init();
        $this->chklst_h = $chklst_h;

        $this->form($_SERVER["PHP_SELF"].'?page='.$_REQUEST['page'], 1);
        $this->form_hidden('id', '');

        if ($args)
        {
            // Checklist handling
            if ($_REQUEST['checklist_create'] && $_REQUEST['template']
                && $_REQUEST['template'] != -1)
            {
                $roles = $this->list_roles();
                $roles_users = array();
                foreach ($roles as $role_id => $role)
                    $roles_users[$role_id] = $_REQUEST["user$role_id"];
                $this->do_checklist_create($args, $_REQUEST['template'],
                                           $roles_users);
            }
            else if ($_REQUEST['checklist_edit'])
            {
                $this->do_checklist_edit($args);
            }
            else if ($_REQUEST['checklist_apply'])
            {
                $this->do_checklist_save($args);
                $this->redirect('checklist_edit');
            }
            else if ($_REQUEST['checklist_save'])
            {
                $this->do_checklist_save($args);
                $this->redirect('checklist_use');
            }
            else if ($_REQUEST['checklist_delete'])
            {
                $this->do_checklist_delete($args);
            }
            else if ($_REQUEST['checklist_update'])
            {
                $this->do_checklist_row_update($args);
            }
            else if ($this->get_checklist_id($args))
            {
                $this->do_checklist_use($args);
            }
            else
            {
                $this->do_checklist_create_form($args);
            }
        }
        else
        {
            // Template handling
            if ($_REQUEST['template_edit'])
            {
                $this->do_template_edit();
            }
            else if ($_REQUEST['template_apply'])
            {
                $this->do_template_save();
                $this->redirect('template_edit');
            }
            else if ($_REQUEST['template_save'])
            {
                $this->do_template_save();
                $this->redirect('template_list');
            }
            else if ($_REQUEST['template_delete'])
            {
                $this->do_template_delete();
            }
            else if ($_REQUEST['template_export'])
            {
                $this->do_template_export();
            }
            else if ($_REQUEST['usergo'])
            {
                $this->do_edit_user();
            }
            else
            {
                $this->do_template_list();
            }
        }

        $this->form_end();

        $this->done = true;

        return $this->outdata;
    }
}

?>
