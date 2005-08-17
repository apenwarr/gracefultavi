/**
 * TableSort
 *
 * Author:
 *   Original version:
 *     Copyright Stuart Langridge MIT License
 *     See: www.kryogenix.org/code/browser/sorttable/
 *
 *   Modified by:
 *     Michel Emond
 *     Net Integration Technologies
 *     www.net-itech.com
 *
 * Description:
 *   Turns a simple html table into a sortable table. The columns headers become
 *   clickable and the sorting order toggles between ascending and descending.
 *   Different data types (letters, numbers, currency, and dates) are
 *   automatically supported.
 *
 *   Restrictions:
 *     - The table's HTML must be well formed.
 *     - The table must have headers so there's something to click on.
 *
 * Syntax:
 *   In order to activate TableSort on a table, add a "tablesort" attribute
 *   having the value "1".
 *
 * Examples:
 *   <table tablesort="1" border="1">
 *   [...]
 *   </table>
 */


/**
 * Queuing system for functions to run on window.onLoad event.
 */
_onLoad = new Array();
addEvent(window, "load", doOnLoad);

function doOnLoad()
{
    for (var i = 0; i < _onLoad.length; i++) {
        eval(_onLoad[i]);
    }
}


/**
 * Cross-browser event handling for IE5+, NS6 and Mozilla
 * By Scott Andrew
 */
function addEvent(elm, evType, fn, useCapture)
{
    if (elm.addEventListener) {
        elm.addEventListener(evType, fn, useCapture);
        return true;
    } else if (elm.attachEvent) {
        var r = elm.attachEvent("on" + evType, fn);
        return r;
    }
}


// initialization function called on window.onLoad event, see shared.js
_onLoad.push('tablesortInit()');

// flag so other libraries know that TableSort has been loaded up
if (document.getElementsByTagName) {
    var TABLESORT = true;
}

var SORT_COLUMN_INDEX;

function tablesortInit()
{
    if (typeof(TABLESORT) != 'boolean') { return; }

    // Find all tables with attribute tablesort and make them sortable
    var tbls = document.getElementsByTagName("table");
    for (var ti = 0; ti < tbls.length; ti++) {
        var thisTbl = tbls[ti];
        if (thisTbl.getAttribute('tablesort')) {
            thisTbl.className = 'tablesort';
            ts_makeSortable(thisTbl);
        }
    }
}

function ts_makeSortable(table)
{
    var id;
    if (table.rows && table.rows.length > 0) {
        var firstRow = table.rows[0];
    }
    if (!firstRow) { return; }
    // We have a first row: assume it's the header, and make its contents
    // clickable links.
    for (var i=0;i<firstRow.cells.length;i++) {
        var cell = firstRow.cells[i];
        cell.style.backgroundColor = '#eee';
        cell.style.color = '#666666';
        cell.style.fontWeight = 'bold';
        var txt = ts_getInnerText(cell);
        id = Math.random();
        cell.innerHTML = '<a id="'+id+'" href="#" '+
            'style="text-decoration: none; display: block;" '+
            'onclick="ts_resortTable(\''+id+'\'); return false;">'+txt+
            '<span class="sortarrow" ' +
            'style="color: black; text-decoration: none;">'+
            '&nbsp;&nbsp;&nbsp;</span></a>';
    }
}

function ts_getInnerText(el)
{
    if (typeof el == "string") { return el; }
    if (typeof el === "undefined") { return el; }
    if (el.innerText) { return el.innerText; } //Not needed but it is faster
    var str = "";

    var cs = el.childNodes;
    var l = cs.length;
    for (var i = 0; i < l; i++) {
        switch (cs[i].nodeType) {
            case 1: //ELEMENT_NODE
                str += ts_getInnerText(cs[i]);
                break;
            case 3: //TEXT_NODE
                str += cs[i].nodeValue;
                break;
        }
    }
    return str;
}

function ts_resortTable(id)
{
    var lnk = document.getElementById(id);
    var spans = lnk.getElementsByTagName('span');

    if (spans.length > 0) {
        spans[0].innerHTML = '&nbsp;<img src="images/hourglass.gif" alt="" '+
                             'title=" width="16" height="19" border="0">';
    }

    setTimeout('ts_resortTable_do(\''+id+'\')', 250);
}

function ts_resortTable_do(id)
{
    var lnk = document.getElementById(id);

    // get the span
    var span, i, ci;
    for (ci = 0; ci < lnk.childNodes.length; ci++) {
        if (lnk.childNodes[ci].tagName &&
            lnk.childNodes[ci].tagName.toLowerCase() == 'span') {
            span = lnk.childNodes[ci];
        }
    }
    var td = lnk.parentNode;
    var column = td.cellIndex;
    var table = getParent(td,'table');

    // Work out a type for the column
    if (table.rows.length <= 1) { return; }
    var itm = ts_getInnerText(table.rows[1].cells[column]);
    var sortfn = ts_sort_caseinsensitive;
    if (itm.match(/^\d\d[\/-]\d\d[\/-]\d\d\d\d$/)) { sortfn = ts_sort_date; }
    if (itm.match(/^\d\d[\/-]\d\d[\/-]\d\d$/)) { sortfn = ts_sort_date; }
    //if (itm.match(/^[£$]/)) { sortfn = ts_sort_currency; }
    //if (itm.match(/^-|[\d\.]+( KB)?$/)) { sortfn = ts_sort_numeric; }
    if (itm.match(/^-?[\d]+ min$/)) { sortfn = ts_sort_numeric; }
    if (itm.match(/^[\d]+\.[\d]+\.[\d]+\.[\d]+$/)) { sortfn = ts_sort_ipaddress; }
    SORT_COLUMN_INDEX = column;
    var firstRow = new Array();
    var newRows = new Array();
    for (i = 0; i < table.rows[0].length; i++) {
        firstRow[i] = table.rows[0][i];
    }
    for (var j = 1; j < table.rows.length; j++) {
        newRows[j - 1] = table.rows[j];
    }

    newRows.sort(sortfn);

    var ARROW;
    if (span.getAttribute("sortdir") == 'down') {
        ARROW = '&nbsp;&nbsp;&uarr;';
        newRows.reverse();
        span.setAttribute('sortdir','up');
    } else {
        ARROW = '&nbsp;&nbsp;&darr;';
        span.setAttribute('sortdir','down');
    }

    // We appendChild rows that already exist to the tbody, so it moves them
    // rather than creating new ones don't do sortbottom rows
    for (i = 0; i < newRows.length; i++) {
        if (!newRows[i].className || (newRows[i].className &&
            (newRows[i].className.indexOf('sortbottom') == -1))) {
            table.tBodies[0].appendChild(newRows[i]);
        }
    }
    // do sortbottom rows only
    for (i = 0; i < newRows.length; i++) {
        if (newRows[i].className &&
            (newRows[i].className.indexOf('sortbottom') != -1)) {
            table.tBodies[0].appendChild(newRows[i]);
        }
    }

    // Delete any other arrows there may be showing
    var tbl;
    if ((tbl = getParent(lnk,"table"))) {
        var allspans = tbl.getElementsByTagName("span");
        for (ci = 0; ci < allspans.length; ci++) {
            // IE is silly, it gets unstable unless innerHTML is changed only
            // when really necessary
            if (allspans[ci].className == 'sortarrow' &&
                allspans[ci].innerHTML != '&nbsp;&nbsp;&nbsp;') {
                allspans[ci].innerHTML = '&nbsp;&nbsp;&nbsp;';
            }
        }
    }

    span.innerHTML = ARROW;
}

function getParent(el, pTagName)
{
    if (el === null) {
        return null;
    } else if (el.nodeType == 1 &&
               el.tagName.toLowerCase() == pTagName.toLowerCase()) {
        // Gecko bug, supposed to be uppercase
        return el;
    } else {
        return getParent(el.parentNode, pTagName);
    }
}

function ts_sort_date(a, b)
{
    // y2k notes: two digit years less than 50 are treated as 20XX, greater
    // than 50 are treated as 19XX
    var aa = ts_getInnerText(a.cells[SORT_COLUMN_INDEX]);
    var bb = ts_getInnerText(b.cells[SORT_COLUMN_INDEX]);
    var dt1, dt2, yr;
    if (aa.length == 10) {
        dt1 = aa.substr(6,4)+aa.substr(3,2)+aa.substr(0,2);
    } else {
        yr = aa.substr(6,2);
        if (parseInt(yr) < 50) { yr = '20'+yr; } else { yr = '19'+yr; }
        dt1 = yr+aa.substr(3,2)+aa.substr(0,2);
    }
    if (bb.length == 10) {
        dt2 = bb.substr(6,4)+bb.substr(3,2)+bb.substr(0,2);
    } else {
        yr = bb.substr(6,2);
        if (parseInt(yr) < 50) { yr = '20'+yr; } else { yr = '19'+yr; }
        dt2 = yr+bb.substr(3,2)+bb.substr(0,2);
    }
    if (dt1 == dt2) { return 0; }
    if (dt1 < dt2) { return -1; }
    return 1;
}

function ts_sort_currency(a, b)
{
    var aa = ts_getInnerText(a.cells[SORT_COLUMN_INDEX]).replace(/[^0-9.]/g,'');
    var bb = ts_getInnerText(b.cells[SORT_COLUMN_INDEX]).replace(/[^0-9.]/g,'');
    return parseFloat(aa) - parseFloat(bb);
}

function ts_sort_numeric(a, b)
{
    var aa = parseFloat(ts_getInnerText(a.cells[SORT_COLUMN_INDEX]));
    if (isNaN(aa)) { aa = 0; }
    var bb = parseFloat(ts_getInnerText(b.cells[SORT_COLUMN_INDEX]));
    if (isNaN(bb)) { bb = 0; }
    return aa - bb;
}

function ts_sort_caseinsensitive(a, b)
{
    var aa = ts_getInnerText(a.cells[SORT_COLUMN_INDEX]).toLowerCase();
    var bb = ts_getInnerText(b.cells[SORT_COLUMN_INDEX]).toLowerCase();
    if (aa == bb) { return 0; }
    if (aa < bb) { return -1; }
    return 1;
}

function ts_sort_default(a, b)
{
    var aa = ts_getInnerText(a.cells[SORT_COLUMN_INDEX]);
    var bb = ts_getInnerText(b.cells[SORT_COLUMN_INDEX]);
    if (aa == bb) { return 0; }
    if (aa < bb) { return -1; }
    return 1;
}

function getPseudoIp(ip)
{
    ip = ip.replace(/\b(\d)\b/g, '0$1');
    ip = ip.replace(/\b(\d\d)\b/g, '0$1');
    ip = ip.replace(/[.]/g, '');
    return ip;
}

function ts_sort_ipaddress(a, b)
{
    var aa = getPseudoIp(ts_getInnerText(a.cells[SORT_COLUMN_INDEX]));
    var bb = getPseudoIp(ts_getInnerText(b.cells[SORT_COLUMN_INDEX]));
    return parseFloat(aa) - parseFloat(bb);
}
