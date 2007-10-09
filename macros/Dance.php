<?php

// The most important wiki macro ever... The dancing smiley macro!
class Macro_Dance {

// To create more dancing guys, just add them here
var $dances=array(""=>array("=D/-<", "=D|-<", "=D\\\\-<", "=D|-<"),
                  "kirby"=>array("<(' <)", "<(' ')>", "(> ')>", "<(' ')>"),
                  "kirbyjive"=>array("~('o')_", "-('o')-", "_('o')~", "-('o')-"),
                  "kirbyboogie"=>array("O('o')o", "O('o')O", "o('o')O", "o('o')o"),
                  "russian"=>array("=DX/", "=DX,", "=DX\\\\", "=DX`", "HEY!", "HEY!"),
                  );

function parse($args, $page)
{
   static $i=1;

   // Every guy has an unique identifier $i.
   $output = "
      <input type=\"hidden\" id=\"" . $args . "danceCntr" . $i . "\" value=\"1\">
      <input type=\"text\" id=\"" . $args . "dance" . $i . "\" value=\"\">
      <script>\n";

   // The updateIt function is only created once. This is the javascript function that handles
   // All of the crazy dancing people.
   if($i==1)
   {
      $output .= "function updateIt(i, type) {\n";
      $i=1;


      foreach($this->dances as $type => $value)
      {
         $output .= "if(type == '$type') {";
         $j=0;

         foreach($this->dances[$type] as $step)
         {
            $output  .= "
               if(document.getElementById(type + 'danceCntr' + i).value == " . $j . ") {
                  document.getElementById(type + 'dance' + i).value=\"".$step . "\";
               }\n";
            $j++;
         }

         $output .= "
            if(document.getElementById(type + 'danceCntr' + i).value==" . ($j-1) . ") {
               document.getElementById(type + 'danceCntr' + i).value=-1;
            }
         }\n";
      }

      $output.="
         document.getElementById(type + 'danceCntr' + i).value++;

         timerId = setTimeout('updateIt(' + i + ', \'' + type + '\')', 200);
      }\n";
   }


   // Each guy will have it's own javascript call to the updateIt so they can dance
   $output .= "updateIt($i, '$args');</script>";
   $i++;

   return $output;
}

}
?>
