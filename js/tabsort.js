// <html><body><a name=DumpArea>(DumpArea)</a>
// <script type="text/javascript" language="javascript" src="script.js"></script>
// <script>

function tabSortComp(a, b)
{
    if (a[1] < b[1]) return -1;
    else if (a[1] > b[1]) return 1;
    else return 0;
}

function tabAddRow(tabsection, celldata)
{
    var body = tabsection;
    var rows = body.rows;
    
    var newrow = body.insertRow(-1);
	
    // IE5.5 (at least) can't handle setting innerHTML on rows.  Sigh.
    // Set cells one by one instead.
    for (var col = 0; col < celldata.length; col++)
    {
	var cell = newrow.insertCell(-1);
	if (celldata[col].innerHTML != undefined)
	    cell.innerHTML = celldata[col].innerHTML;
	else
	    cell.innerHTML = celldata[col];
    }
}

function tabSort(colobj)
{
    var table = tableOf(colobj);
    var body = table.tBodies[0];
    var rows = body.rows;
    var col = colNumOf(colobj);
    var list = [];
    var sortfunc;
    
    if (table.tabSortMode == col)
    {
	// sort in reverse order
	// ...but when equal, preserve the *old* order
	sortfunc = function (a,b) { return tabSortComp(b,a) || a[0]-b[0]; }
        table.tabSortMode = null;     // next time, forward order again
    }
    else
    {
	// sort in forward order
	// ...but when equal, preserve the *old* order (forwards!)
	sortfunc = function (a,b) { return tabSortComp(a,b) || a[0]-b[0]; };
	table.tabSortMode = col;
    }
    
    for (var rownum=0; rownum < rows.length; rownum++)
    {
	var row = rows[rownum];
	list.push([rownum, 
		   numerize(trim_string(stripTags(row.cells[col].innerHTML))),
		   row]);
    }
    
    var origlen = rows.length;
    
    for (var key in list.sort(sortfunc))
    {
	var srcrow = list[key][2];
	tabAddRow(body, srcrow.cells);
/*		  
	var newrow = body.insertRow(-1);
	
	// IE5.5 can't handle setting innerHTML on rows.  Sigh.
	// Set cells one by one instead.
	for (var col = 0; col < srcrow.cells.length; col++)
	{
	    var cell = newrow.insertCell(-1);
	    cell.innerHTML = srcrow.cells[col].innerHTML;
	}*/
    }
    
    for (var row = 0; row < rows.length; row++)
	body.deleteRow(0);
    
    if (table.autoAlternateRows)
	table.autoAlternateRows();
}


// this is a separate function so that "cell" is a new name each time it's
// called. That means each generated function remembers a different "cell",
// rather than all pointing at the same one!
function _makeSort(cell)
{
    return function () { tabSort(cell) }
}


/// make the given table sort its rows when you click on column headings.
function makeTabSort(tab)
{
    if (tab.tHead && tab.tHead.rows)
    {
	var row = tab.tHead.rows[0];
	if (row)
	{
	    for (var col=0; col < row.cells.length; col++)
	    {
		var cell = row.cells[col];
		cell.onclick = cell.ondoubleclick = _makeSort(cell);
	    }
	}
    }
}


/// make *all* tables in the current document use makeTabSort().
function allTabSort()
{
    // apply an onclick tag to each column of the first row in <thead>.
    var alltags = document.getElementsByTagName('table');
    for (var tabi=0; tabi < alltags.length; tabi++)
	makeTabSort(alltags[tabi]);
}


// dumpKeys(document);


// </script>
// This is a javascript file, but if you run it as html, it tests itself.
/*

<table>
 <thead><tr><th onclick="tabSort(this);">NAME</th>
            <th onclick="tabSort(this);">AGE</th></tr></thead>
 <tbody>
  <tr><td>Avery</td><td>26</td></tr>
  <tr><td>Jana 3</td><td><b>23</b></td></tr>
  <tr><td>Jana</td><td><b>23</b></td></tr>
  <tr><td>Jana 2</td><td><b>23</b></td></tr>
  <tr><td>Weaver</td><td>7</td></tr>
  <tr><td>NITI</td><td>4</td></tr>
  <tr><td>Linux 1</td><td>12.110</td></tr>
  <tr><td>Linux 2</td><td>12.11</td></tr>
  <tr><td>Linux 3</td><td>012.05</td></tr>
  <tr><td>Linux 4</td><td>12.1</td></tr>
 </tbody>
</table>

 */
// </body></html>
