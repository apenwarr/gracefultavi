<?php
function action_imgbar(){
    $w = $_GET{'width'};
    $h = $_GET{'height'};
    $p = $_GET{'per'};
    $barwidth=intval($p*$w/100);
    $im = imagecreate($w, $h);
    $red = imagecolorallocate($im, 255, 0, 0);
    $yellow = imagecolorallocate($im, 255, 255, 50);
    $green = imagecolorallocate($im, 0, 255, 0);
    $gray = imagecolorallocate($im, 220, 220, 220);
    $black = imagecolorallocate($im, 0, 0, 0);
    imagefilledrectangle($im, 0, 0, $w, $h, $gray);
    if ($p != 0){
        if ($p < 30)
            $color = $red;
        elseif ($p < 60)
            $color = $yellow;
        else
            $color = $green;
        imagefilledrectangle($im, 0, 0, $barwidth, $h, $color);
    }
    imagestring($im, 1, intval($w/2-10), intval($h/2-4), "$p%", $black);

    Header("Content-Type: image/png");
    imagepng($im);
}
?>
