<?php

class Macro_Attach
{
    var $pagestore;

    function parse($args, $page)
    {
        global $pagestore, $ParseEngine, $ParseObject, $WorkingDirectory;

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
        $cleanname = "f-" . ereg_replace("[^a-zA-Z]", "x", $filename);
        $fullname = "attachments/$filename";
        $delname = "attachments/.deleted/$filename";
        $lockname = $fullname . ".locked";

        // $out .= "(Attach '$type' '$fullname')<br>";
        // $out .= var_dump($_FILES);

        $out .= "<table><tr valign=top>";

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
             $out .= "<td><b>Attachment:</b> <a href=\"$fullname\">$filename</a>";

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
                 $out .= "<br><img src='$fullname' alt='$filename'>";
             }

             $out .= "</td>";

             if (!file_exists($fullname . ".locked"))
             {
                 $out .= "<td><form method=POST>"
                   . "<input type='hidden' name='action-$cleanname' value=1 />"
                   . "<input type='submit' name='Delete' value='Delete' />"
                   . "<input type='submit' name='Lock' value='Lock' />"
                   . "</form></td>";
             }
         }
         else
         {
             $out .= "<td><b>Attachment:</b> $filename";
             $out .= "<form method=POST enctype='multipart/form-data'>"
               . "<input type='file' name='$cleanname' />"
               . "<input type='submit' value='Submit' />"
               . "</form></td>";

             if (file_exists($delname))
             {
                 $out .= "<td><form method=POST>"
                   . "<input type='hidden' name='action-$cleanname' value=1 />"
                   . "<input type='submit' name='Undelete' value='Undelete' />"
                   . "</form></td>";
             }
         }

         $out .= "</tr></table><p>";

         return $out;
    }
}

return 1;

?>