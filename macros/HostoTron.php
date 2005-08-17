<?php

class Macro_HostoTron
{
    var $pagestore;
    var $describe = array();
    var $reserve = array();

    function parse_day($date)
    {
        if ($date == '')
            return 0;
        else if (preg_match(",(....)[/-](..)[/-](..),", $date, $a))
        {
            $year = $a[1];
            $month = $a[2];
            $day = $a[3];

            return mktime(12,0,0, $month, $day, $year);
        }
        else
        {
            print("(INVALID DATE:'$day')");
            return 555;
        }
    }

    function parse($args, $page)
    {
        if (!preg_match_all('/"[^"]*"|[^ \t]+/', $args, $words))
            return "regmatch failed!\n";
        $words = $words[0]; // don't know why I have to do this, exactly...
        foreach ($words as $key => $value)
        {
            if (preg_match('/^"(.*)"$/', $value, $result))
                $words[$key] = $result[1];
        }

        if ($words[0] == "DESCRIBE")
        {
            $hw = $words[1];
            $descr = $words[2];
            $rsv = $words[3];
            $this->describe[$hw] = $descr;
            if ($rsv != "")
                $this->reserve[$hw] = $rsv;
        }
        else if ($words[0] == "RESERVE")
        {
            $hw = $words[1];
            $user = $words[2];
            $until = $words[3];
            if ($user!="" && $until!="" && $this->reserve[$hw]=="")
            {
                $until = $this->parse_day($until);

                if ($until+24*60*60 > time())
                {
                    $dt = strftime("%Y/%m/%d", $until);
                    $this->reserve[$hw] = "$user until $dt";
                }
                else
                {
                    $this->reserve[$hw] = "EXPIRED";
                    return "Warning: expired reservation "
                        . "for $user on $hw.<br>\n";
                }
            }
        }
        else if ($words[0] == "RUN")
        {
            $hide_old_hosts = isset($_GET['hostotron_hide_old']);

            $ret = "";
            $total = 0;
            $url = "http://$words[1]";
            $f = fopen($url, "r");
            if (!$f)
                return "Can't get URL: ($url)\n";

            $ret .= "<tr><th>"
                    . join("</th><th>",
                           array("Ether", "IP", "Name", "Descr",
                                 "Ver", "Status", "Last Check", "Reserve"))
                    . "</th></tr>";
            while ($f && ($line = fgets($f)))
            {
                $row = "";
                $a = split("\\|", $line);

                $now = time();

                if ($this->describe[$a[0]] != "")
                    $a[3] = $this->describe[$a[0]];

                $a[6] = round(($now - $a[6])/60);
                if ($hide_old_hosts && $a[6] > 60)
                {
                    continue;
                }
                $a[6] .= " min";

                if ($this->reserve[$a[0]]
                    || ($a[5] == "OK" && substr($a[3],0,6)=="Shared"))
                {
                    $a[8] = $this->reserve[$a[0]];
                    if ($a[8] == "")
                        $a[8] = "<b>**AVAILABLE**</b>";
                }
                else
                    $a[8] = "-";

                $total++;

                foreach ($a as $key => $val)
                {
                    $col = "";
                    if ($key == 1)
                        $col .= "<a href='http://$val:8042/'>$val</a>";
                    else
                        $col = $val;
                    $row .= "<td><font size=-2>$col</font></td>";
                }
                $row = "<tr>$row</tr>\n";
                $ret .= $row;
            }
            $ret = "<table tablesort='1' width=90%>$ret</table>";

            $link = '<p><a href="?page='.rawurlencode($page).
                    ($hide_old_hosts ? '' : '&hostotron_hide_old=1').'">'.
                    ($hide_old_hosts ? 'Show' : 'Hide').' old hosts</a></p>';
            $ret = $link . $ret;

            $ret .= "<p>$total total hosts indexed.</p>";
            if ($f) fclose($f);
            return $ret;
        }
        else
        {
            return "HostoTron: unknown mode '$words[0]'";
        }

        return "";
    }
}

return 1;
?>
