// <html><body><a name=DumpArea>(DumpArea)</a><script>


/// return the DOM object with the given html name/id in the current document
function getDomObj(id)
{
    var x = document.getElementsByName(id)[0];
    var msg;
    if (!x) {
        msg = "Couldn't find object '"+id+"' in document "+document+"!";
        alert("ERROR: " + msg);
        throw msg;
    }
    return x;
}


/// return the document object held inside the given iframe.  This is done
/// differently in several different browsers, so it's a bit funny.
function frameDoc(f)
{
    var doc = f.contentDocument;
    if (!doc && f.contentWindow) { doc = f.contentWindow.document; }
    if (!doc) { doc = f.document; }
    return doc;
}


/// given a <td> object of a table, figure out the index number of that column
/// in the table.
function colNumOf(colobj)
{
    var row = colobj.parentNode;
    for (var key = 0; key < row.cells.length; key++) {
    	if (row.cells[key] == colobj) {
    	    return key;
        }
    }
    return null;
}


/// strip HTML tags from the given string
function stripTags(str)
{
    return str.replace(/<[^>]*>/g, "");
}


/// remove leading/trailing whitespace from the given string
function trim_string(s)
{
    s.replace(/^\s+/, "");
    s.replace(/\s+$/, "");
    return s;
}


/// convert a string to a number type - but only if the string is *exactly*
/// a number, with no extra weird characters (except whitespace)
function numerize(s)
{
    if (s.match(/^[-+0-9.e\s]*$/)) {
	    return parseFloat(s);
    } else {
	    return s;
    }
}


/// given an object somewhere in a <table> tag, find the parent <table> tag.
function tableOf(obj)
{
    while (obj.parentNode && obj.parentNode != obj && !obj.tBodies) {
	    obj = obj.parentNode;
    }
    if (obj.tBodies) {
	    return obj;
    }
    return null;
}


/// add an option with the given string to a <select> object
function addOpt(sels, string)
{
    var opt = new Option(string, string);
    sels.add(opt, null);
}


/// in the given table, give the first row css style class1, the second row
/// class2, and then alternate back and forth for all future rows.
function alternateRows(_table, class1, class2)
{
    var table = tableOf(_table);
    var body = table.tBodies[0];
    for (var i = 0; i < body.rows.length; i+=2)
    {
        body.rows[i].className = class1;
        if (body.rows[i+1]) {
            body.rows[i+1].className = class2;
        }
    }

    table.autoAlternateRows =
        function () { alternateRows(table, class1, class2); };
}


/// make *all* tables in the current document have rows that use
/// alternateRows(class1,class2).
function allAlternateRows(class1, class2)
{
    var alltags = document.getElementsByTagName('table');
    for (var i=0; i < alltags.length; i++) {
	    alternateRows(alltags[i], class1, class2);
    }
}


/// return a copy of the given list, skipping the first 'skip' elements.
/// You could do this with l.splice, but the 'arguments' array is special
/// and doesn't support that operation.  Sigh.
function copyList(l, skip)
{
    var ret = [];
    for (var i = skip; i < l.length; i++) {
	    ret.push(l[i]);
    }
    return ret;
}


/// given a function pointer, return a string that you can use in
/// setTimeout() to call it.  Normally setTimeout() is supposed to be allowed
/// to take function pointers anyway, but that doesn't work in konqueror 3.1.x
/// (at least).  See after(), which uses this.
var stringifiedFuncs = [];
function stringifyFunc(func)
{
    var args = copyList(arguments, 1);
    var idx = stringifiedFuncs.length;
    stringifiedFuncs[idx] = function() {
	    func.apply(this, args);
        stringifiedFuncs[idx] = undefined;
    };
    return "stringifiedFuncs[" + idx + "]();";
}


/// Run func() after msec milliseconds.  Like setTimeout, but takes a function
/// pointer followed by arguments to that function, rather than a string.
/// We wouldn't have to use stringifyFunc(), except that konqueror doesn't
/// support setTimeout(func, msec), only setTimeout(string, msec).
function after(msec, func)
{
    var s = stringifyFunc(func, copyList(arguments, 2));
    setTimeout(s, msec);
}


// return a dump string for all the keys in a given object
function _dumpKeys(obj, maxdepth)
{
    var str = "Object '" + obj + "'";
    var key, list, name;
    if (maxdepth > 0) {
        list = [];
        for (key in obj) {
            list.push(key);
        }

        for (key in list.sort()) {
            str += ", ";

            name = list[key];
            if (obj.getAttribute) {
                if (obj.getAttribute(name) !== null) {
                    str += name + "=" + obj.getAttribute(name);
                } else {
                    str += name;
                }
            } else {
                str += name;
            }
        }

        list = [];
        if (obj && obj.childNodes) {
            for (key in obj.childNodes) {
                list.push(obj.childNodes[key]);
            }
        }

        str += "<ul>";
        for (key in list.sort()) {
            str += "<li>" + _dumpKeys(list[key], maxdepth-1);
        }
        str += "</ul>";
    } else {
	    str += " (STOPPED)\n";
    }

    return str;
}


function dumpAdd(str)
{
    getDomObj('DumpArea').innerHTML+="<br>" + str;
}


/// dump all keys in the given object into an html section named "DumpArea"
/// in the current document.
function dumpKeys(obj)
{
    dumpAdd(_dumpKeys(obj, 5));
}


//dumpKeys(document);


// </script>This is a javascript file.</body></html>
