<?php

function checklist_init() {
    global $chklst_h;
    global $ChecklistServer;
    global $ChecklistUser;
    global $ChecklistPass;
    global $ChecklistName;

    if (!$chklst_h)
    {
        $chklst_h = mysql_connect($ChecklistServer, $ChecklistUser, $ChecklistPass);
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
    var $page;
    var $chklst_h;
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
        $this->out('<input type="radio" name="'.$name.'" '.
                   'value="'.htmlspecialchars($value).'" '.$ch.'>'.
                   htmlspecialchars($content).'</input>');
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
tr.done {
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

        function setRowStyle(row, checked)
        {
            if (checked)
            {
                row.style.color = 'gray';
                row.style.fontStyle = 'italic';
                row.style.textDecoration = 'line-through';
            }
            else
            {
                row.style.color = 'black';
                row.style.fontStyle = 'normal';
                row.style.textDecoration = 'none';
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
                var d = new Date(dateparts[0], dateparts[1]-1, dateparts[2])
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
                var month = d.getMonth() + 1;
                var day = d.getDate();

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

        if ($id)
            $this->out('<p><h2>Edit Template</h2>');
        else
            $this->out('<p><h2>New Template</h2>');

        $this->form_hidden('id', $id);

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
        foreach ($template_details as $id => $details)
        {
            $this->print_edit_template_row($details, $roles,
                $i == count($template_details));
            $i++;
        }
        $this->form_hidden('templaterowids',
            implode(',', array_keys($template_details)));
        $this->print_edit_template_row(array(), $roles, false);
        $this->table_end();

        $this->out('<p><b>Import:</b> ');
        $this->form_file('import', 20);
        $this->form_button('template_export', 'Export');

        $this->out('<p>');
        $this->form_button('template_save', 'Submit');

        $this->out('<p>');
        $this->form_button('template_list', 'Return');
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

            $content = file_get_contents($_FILES['import']['tmp_name']);
            $content = explode("\n", $content);

            $category = '';
            foreach ($content as $line)
            {
                if (preg_match('/^\s+/', $line))
                {
                    $description = trim($line);
                    $owner = 'null';

                    foreach ($roles as $role_id => $role)
                    {
                        $role = preg_quote($role);
                        if (preg_match("/^$role\s+(.*)$/i", $description,
                                       $matches))
                        {
                            $owner = $role_id;
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

        $this->redirect('template_edit');
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

        $output = '';
        $category = '';
        foreach ($template_details as $row)
        {
            if ($row['category'] != $category)
            {
                $category = $row['category'];
                $output .= $row['category'] . "\n";
            }
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

    function get_checklist_details($id)
    {
        return chklst_query("select id, category, description, owner, " .
                            "duedate, lastconfdate, notes, status " .
                            "from chklst_checklist_details " .
                            "where checklist = $id " .
                            "order by torder");
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
        $before_id = -1)
    {
        $category = quote(trim($category));
        $description = quote(trim($description));
        $notes = quote(trim($notes));
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
                               "notes = '$notes' " .
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
                                       "torder) " .
                                       "values ($checklist_id, '$category', " .
                                       "'$description', $owner_id, " .
                                       "$duedate, $lastconfdate, '$notes', " .
                                       "$order)");
        }
    }

    function delete_checklist_row($id)
    {
        chklst_query("delete from chklst_checklist_details " .
                     "where id = $id");
    }

    function list_users()
    {
        bug_init();
        $fb_users = sql_simple("select ixPerson, sEmail " .
                               "from Person " .
                               "where fDeleted=0 " .
                               "and sEmail is not null " .
                               "order by sEmail");

        $users = array();
        foreach ($fb_users as $fb_user_id => $fb_email)
        {
            $uname = preg_replace("/@.*/", "", $fb_email);
            if ($uname != 'qa' && $uname != 'dcoombs-fogbugz')
                $users[$fb_user_id] = $uname;
        }

        return $users;
    }

    function update_checklist_status($id, $status)
    {
        $status = $status ? 1 : 0;

        chklst_query("update chklst_checklist_details " .
                     "set status = $status " .
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

    function notes_link($id, $notes)
    {
        $tooltip = ' onmouseover="var frm=document.forms.checklistform;' .
                   'if(frm.notes'.$id.'.value!=\'\'){' .
                   'tooltipLink(wrap(frm.notes'.$id.'.value));}return true;" ' .
                   'onmouseout="var frm=document.forms.checklistform;' .
                   'if(frm.notes'.$id.'.value!=\'\'){tooltipClose();}"';
        $link = '<a href="javascript:open_window('.$id.');"' . $tooltip . '>';
        $spanstyle = strlen($notes) ? '' : 'style="color: gray;"';
        $link .= '<span id="notesspan'.$id.'"'.$spanstyle.'>Notes</span></a>';

        return $link;
    }

    function print_edit_checklist_row($row, $users, $last_row)
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
        $this->out('</td><td>');
        $this->form_input('category'.$row['id'], $row['category'], 35, true);
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
                $i == count($checklist_details));
            $i++;
        }
        $this->form_hidden('checklistrowids',
            implode(',', array_keys($checklist_details)));
        $this->print_edit_checklist_row(array(), $users, false);
        $this->table_end();

        $this->out('<p>');
        $this->form_button('checklist_save', 'Submit');

        $this->out('<p>');
        $this->form_button('checklist_delete', 'Delete Checklist', $id, true);

        $this->out('<p>');
        $this->form_button('checklist_use', 'Return');
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

            $this->save_checklist_row(null, $id, $_REQUEST['category'],
                $_REQUEST['description'], $_REQUEST['owner'],
                $_REQUEST['duedate'], $_REQUEST['lastconfdate'],
                $_REQUEST['notes'], $before_id);
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
                    $_REQUEST["notes$row_id"]);
            }
        }

        $this->redirect('checklist_edit');
    }

    function do_checklist_delete($name)
    {
        $id = $this->get_checklist_id($name);

        if ($id) $this->delete_checklist($id);

        $this->do_checklist_create_form($name);
    }

    function parse_wiki_links($text)
    {
        global $LinkPtn;
        $ptn = "/(^|[^A-Za-z])(!?$LinkPtn)((\#[-A-Za-z0-9]+)?)(\"\")?/";

        return preg_replace($ptn, "\\1<a href=\"?\\2\">\\2</a>", $text, -1);
    }

    function do_checklist_use($name)
    {
        $this->mystyle();
        $this->checklist_js();

        $id = $this->get_checklist_id($name);
        if (!$id)
        {
            $this->do_checklist_create_form($name);
            return;
        }

        $checklist_details = $this->get_checklist_details($id);

        $users = $this->list_users();


        $this->out("<h1>$name</h1>");

        $this->out('<p>');
        $this->form_button('checklist_edit', 'Edit Checklist');

        $this->out('<p>');
        $this->table('table');
        $this->out('<tr>
            <td><b>Category</b></td>
            <td></td>
            <td><b>Description</b></td>
            <td><b>Owner</b></td>
            <td nowrap><b>Due Date</b></td>
            <td nowrap><b>Last Conf. Date</b></td>
            <td><b>Notes</b></td>
            </tr>');

        $category = '';
        foreach ($checklist_details as $row)
        {
            if ($row['category'] != $category)
            {
                $this->out('<tr><td colspan="3">');
                $this->out($this->parse_wiki_links($row['category']));
                $this->out('</td><td></td><td></td><td></td><td>');
                $this->out('</td></tr>');
                $category = $row['category'];
            }

            $class = $row['status'] == 1 ? 'class="done"' : '';
            $this->out('<tr id="row'.$row['id'].'" '.$class.
                       ' valign="top"><td>');
            $this->out('</td><td>');
            $this->form_hidden('prevstatus'.$row['id'], $row['status']);
            $this->form_checkbox('status'.$row['id'], '', $row['status']==1,
                'setRowStyle(document.getElementById(\'row'.$row['id'].'\'), ' .
                'this.checked);');
            $this->out('</td><td>');
            $this->out('<label for="status'.$row['id'].'">');
            $this->out($this->parse_wiki_links($row['description']));
            $this->out('</label>');
            $this->out('</td><td nowrap>');
            $this->out('<a href="?' . $users[$row['owner']] . '">' .
                 $users[$row['owner']] . '</a>');
            $this->out('</td>');
            $duedate = str_replace('-', '', $row['duedate']);
            if ($duedate && $row['status'] != 1 && $duedate < date('Ymd'))
                $this->out('<td style="color: red" nowrap>');
            else
                $this->out('<td nowrap>');
            $this->out($row['duedate']);
            $this->out('</td><td nowrap>');
            $this->out($row['lastconfdate']);
            $this->out('</td><td>');
            $this->out($row['notes']);
            $this->out('</td></tr>');
        }
        $this->table_end();

        $this->form_hidden('checklistrowids',
            implode(',', array_keys($checklist_details)));

        $this->out('<p>');
        $this->form_button('checklist_update', 'Submit');
    }

    function do_checklist_status_update($name)
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

            $status = $_REQUEST["status$row_id"];
            $prev_status = $_REQUEST["prevstatus$row_id"];

            if ((isset($status) && $prev_status == 0)
                || (!isset($status) && $prev_status == 1))
                $this->update_checklist_status($row_id, $status);
        }

        $this->redirect('checklist_use');
    }



    // main gracefultavi entry point
    function parse($args, $page)
    {
        if ($this->done) return '';

        $this->page = $page;

        global $chklst_h;
        checklist_init();
        $this->chklst_h = $chklst_h;

        $this->form($_SERVER["PHP_SELF"].'?page='.$_REQUEST['page'],1);
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
                $this->do_checklist_edit($args);
            else if ($_REQUEST['checklist_save'])
                $this->do_checklist_save($args);
            else if ($_REQUEST['checklist_delete'])
                $this->do_checklist_delete($args);
            else if ($_REQUEST['checklist_update'])
                $this->do_checklist_status_update($args);
            else if ($this->get_checklist_id($args))
                $this->do_checklist_use($args);
            else
                $this->do_checklist_create_form($args);
        }
        else
        {
            // Template handling
            if ($_REQUEST['template_edit'])
                $this->do_template_edit();
            else if ($_REQUEST['template_save'])
                $this->do_template_save();
            else if ($_REQUEST['template_delete'])
                $this->do_template_delete();
            else if ($_REQUEST['template_export'])
                $this->do_template_export();
            else
                $this->do_template_list();
        }

        $this->form_end();

        $this->done = true;

        return $this->outdata;
    }
}

?>
