<?php

class Macro_Attach
{
    var $pagestore;

    function parse($args, $page)
    {
        global $pagestore, $ParseEngine, $ParseObject, $WorkingDirectory;
        global $UserName;

        if (!preg_match_all('/"[^"]*"|[^ \t]+/', $args, $words))
            return "regmatch failed!\n";
        $words = $words[0]; // don't know why I have to do this, exactly...

        if ($words[1])
        {
            $type = $words[0];
            $filename = $words[1];
        }
        else
        {
            $type = "file";
            $filename = $words[0];
        }

        $filename = ereg_replace("[^-_.a-zA-Z0-9]", "-", $filename);
        $filename = ereg_replace("^\\.", "x.", $filename);
        $cleanname = "f-" . preg_replace("/[^-\w]/", "x", $filename);
        $fullname = "attachments/$filename";
        $delname = "attachments/.deleted/$filename";
        $lockname = $fullname . ".locked";

        // $out .= "(Attach '$type' '$fullname')<br>";
        // $out .= var_dump($_FILES);

        $out = '<span style="background:#eeeeff;">';

        if ($_FILES["$cleanname"] && !file_exists($fullname))
        {
            $tmpname = $_FILES["$cleanname"]["tmp_name"];
            move_uploaded_file($tmpname, $fullname);
            chmod($fullname, 0644);
        }

        if (file_exists($fullname))
        {
            if (!file_exists($lockname))
            {
                if ($_POST["action-$cleanname"])
                {
                    if ($_POST['Delete'])
                    {
                        @unlink($delname);
                        rename($fullname, $delname);
                    }
                    elseif ($_POST['Lock'])
                    {
                        $f = fopen($lockname, "w+");
                        fclose($f);
                    }
                }
            }
        }
        else if (file_exists($delname))
        {
            if ($_POST["action-$cleanname"])
            {
                if ($_POST['Undelete'])
                {
                    rename($delname, $fullname);
                }
            }
        }

        if (file_exists($fullname))
        {
            $out .= "<b>Attach:</b> <a href=\"$fullname\">$filename</a> ";

            if ($UserName && !file_exists($fullname . ".locked"))
            {
                $out .= "<form method=POST style=\"display:inline;\">"
                     . "<input type='hidden' name='action-$cleanname' value=1 />"
                     . "<input type='submit' name='Delete' value='Delete' />"
                     . "<input type='submit' name='Lock' value='Lock' />"
                     . "</form>";
            }

            if ($type == "inline")
            {
                $f = fopen("$fullname", "r");
                $s = fread($f, 102400);
                $out .= "<p><pre>".htmlspecialchars($s)."</pre>";
                fclose($f);
            }
            else if ($type == "csv")
            {
                $f = fopen("$fullname", "r");
                $out .= "<p><table>";
                while (!feof($f))
                {
                    $line = fgets($f, 1024);
                    $cols = preg_split("/,/", $line);
                    $out .= "<tr>";
                    foreach ($cols as $col)
                        $out .= "<td>$col</td>";
                    $out .= "</tr>";
                }
                $out .= "</table>";
                fclose($f);
            }
            else if ($type == "img" || $type == "image")
            {
                $out .= "<p>";

                // html bits
                $widthHeight = '';
                $linkOpen = '';
                $linkClose = '';

                $browserWindowWidth =
                    max(0, $_COOKIE["browserWindowWidth"]-100);

                if (function_exists('getimagesize') && $browserWindowWidth) {
                    $dimensions = getimagesize($fullname);
                    $width = $dimensions[0];
                    $height = $dimensions[1];

                    if ($width > $browserWindowWidth) {
                        $height = floor($browserWindowWidth*$height/$width);
                        $width = $browserWindowWidth;

                        $out .= "The image has been reduced for display, ".
                                "click to enlarge.<br>";
                        $widthHeight = "width=\"$width\" height=\"$height\"";
                        $linkOpen = '<a href="'.$fullname.'">';
                        $linkClose = '</a>';
                    }
                }

                $out .= $linkOpen;
                $out .= "<img src='$fullname' alt='$filename' $widthHeight>";
                $out .= $linkClose;
            }
        }
        else
        {
            $out .= "<b>Attach:</b> $filename ";

            if ($UserName)
            {
                $out .= "<form method=POST enctype='multipart/form-data' "
                     . "style=\"display:inline;\">"
                     . "<input type='file' name='$cleanname' />"
                     . "<input type='submit' value='Submit' />"
                     . "</form>";

                if (file_exists($delname))
                {
                    $out .= "<form method=POST style=\"display:inline;\">"
                         . "<input type='hidden' name='action-$cleanname' value=1 />"
                         . "<input type='submit' name='Undelete' value='Undelete' />"
                         . "</form>";
                }
            }
            else
            {
                $out .= " The file has not been uploaded yet.";
            }
        }

        $out .= '</span>';

        return $out;
    }
}

return 1;

?>
