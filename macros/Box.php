<?php

class Macro_Box
{
    function parse($args, $page)
    {
        $args = strtolower($args);

        switch ($args) {
            case 'start':
                return '<div style="border: 1px solid black; padding: 10px; margin: 10px">';
                break;

            case 'end':
                return '</div>';
                break;
        }
    }
}

return 1;

?>
