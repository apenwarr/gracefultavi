<?php

class Macro_Memo
{
    var $lines = array();
    var $label = '';
    var $color = '#ffc';
    var $start = false;
    var $disable = false;


    function tooltip_style()
    {
        static $tooltip_style_done;

        if (isset($tooltip_style_done)) return;
        $tooltip_style_done = 1;

        return '<style type="text/css">'."\n".
               '.tooltip {'."\n".
               'font-size: 9pt;'."\n".
               'border: 1px solid #000;'."\n".
               'padding: 2px;'."\n".
               '-moz-border-radius: 3px;'."\n".
               '}'."\n".
               '</style>'."\n";
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

        function tooltipShow(color, pX, pY, src)
        {
            if (pX < 1) {
                pX = 1;
            }
            if (pY < 1) {
                pY = 1;
            }
            if (isIE) {
                document.all.tooltip.style.backgroundColor = color;
                document.all.tooltip.style.visibility = 'visible';
                document.all.tooltip.innerHTML = src;
                document.all.tooltip.style.left = pX + 'px';
                document.all.tooltip.style.top = pY + 'px';
            } else {
                document.getElementById('tooltip').style.visibility = 'visible';
                document.getElementById('tooltip').style.backgroundColor = color;
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

        function tooltipLink(toolcolor, tooltext)
        {
            text = '<div class="tooltip">' + tooltext + '</div>';
            color = toolcolor;
            if (isIE) {
                xpos = posX();
                ypos = posY();
            } else {
                xpos = netX;
                ypos = netY;
            }
            activeTimeout = setTimeout('tooltipShow(color, xpos - 110, ' +
                'ypos + 15, text);', 300);
        }

        document.write('<div id="tooltip" style="position: absolute; ' +
            'visibility: hidden;"></div>');

        //-->
        </script>

TOOLTIP_JAVASCRIPT;

        return $js;
    }


    // main gracefultavi entry point
    function parse($args, $page)
    {
        if ($this->disable) return;

        if (strtoupper(trim($args)) == 'DISABLE') {

            $this->disable = true;
            return;

        }


        if (strtoupper(substr($args, 0, 5)) == 'START') {

            $this->start = true;
            $this->lines = array();
            $this->label = trim(substr($args, 5));
            return;

        }

        if (!$this->start) return;


        if (strtoupper(substr($args, 0, 5)) == 'COLOR') {

            $this->color = preg_replace('/[^a-zA-Z0-9#]/', '', substr($args, 5));

        } else if (strtoupper(trim($args)) == 'END') {

            // wrap and merge the lines
            $content = '';
            foreach ($this->lines as $line) {
                $content .= wordwrap($line, 75, '<br>', true) . '<br>';
            }

            // ensures a javascript safe string
            $content = str_replace('\\', '\\\\', $content);
            $content = str_replace('\'', '\\\'', $content);

            // assemble output
            $tooltip_init = $this->tooltip_js() . $this->tooltip_style();
            $tooltip = 'onmouseover="tooltipLink(\''.$this->color.'\', ' .
                       '\''.$content.'\');return true;" ' .
                       'onmouseout="tooltipClose();"';
            $output = $tooltip_init.'<span style="font-weight:normal; '.
                      'font-size:9pt; border:1px solid #000; '.
                      'padding: 2px; -moz-border-radius: 3px; '.
                      'background-color: '.$this->color.';" '.$tooltip.'>'.
                      $this->label.'</span>';

            // reset class for next PostIt
            $this->lines = array();
            $this->color = '#ffc';
            $this->start = false;

            return $output;

        } else {

            $this->lines[] = trim(strip_tags($args));

        }
    }
}

?>
