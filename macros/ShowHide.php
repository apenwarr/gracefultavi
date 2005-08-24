<?php

class Macro_ShowHide
{
    function parse($args, $page)
    {
        $args = strtolower($args);

        $args = explode(' ', $args);
        if (!isset($args[0])) { return ''; }

        if (!isset($args[1]) || strtolower($args[1]) != 'hide')
        {
            $div_display = '';
            $img = 'down';
        }
        else
        {
            $div_display = ' style="display:none;"';
            $img = 'right';
        }

        switch ($args[0]) {
            case 'start':
                $id = rand();
                $onClick1 = 'if((div=document.getElementById(\'showhidediv'.$id.
                            '\'))){div.style.display=(div.style.display=='.
                            '\'none\'?\'\':\'none\');}';
                $onClick2 = 'if((img=document.getElementById(\'showhideimg'.$id.
                            '\'))){img.src=\'images/arrow_\'+'.
                            '(div.style.display==\'none\'?\'right\':\'down\')+'.
                            '\'.png\';}';
                $anchor = '<a href="#" onClick="'.$onClick1.$onClick2.
                          'return false;">';
                $alt = 'Show/Hide a section of the page';
                $img = '<img id="showhideimg'.$id.'" src="images/arrow_'.$img.
                       '.png" align="left" alt="'.$alt.'" title="'.$alt.'" '.
                       'width="11" height="11" border="0"></a>';
                return $anchor.$img.
                       '<div id="showhidediv'.$id.'"'.$div_display.'>';
                break;

            case 'end':
                return '</div>';
                break;
        }
    }
}

return 1;

?>
