<?php

function get_captcha_md5()
{
    global $CaptchaObfuscate;

    $n = 5;

    $code = '';
    for ($i = 0; $i < $n; $i++) {
        $code .= chr(rand(ord('a'), ord('z')));
    }

    $code = str_rot13($code);
    $md5 = strtolower(md5(crypt($code)));

    for ($i = 0; $i < $n; $i++) {
        $l = substr($code, $i, 1);
        $p = $CaptchaObfuscate[$i];
        $md5 = substr_replace($md5, $l, $p, 1);
    }

    return $md5;
}

function decode_captcha_md5($md5)
{
    global $CaptchaObfuscate;

    $code = '';
    for ($i = 0; $i < 5; $i++) {
        $code .= substr($md5, $CaptchaObfuscate[$i], 1);
    }
    $code = str_rot13($code);

    return $code;
}

function imagerotatex($im, $r, $c)
{
    $r = deg2rad($r);

    $sx = imagesx($im);
    $sy = imagesy($im);

    $im2 = imagecreate($sx, $sy);
    imagepalettecopy($im2, $im);

    $dx = floor($sx / 2);
    $dy = floor($sy / 2);

    for ($x = 0; $x < $sx; $x++) {
        for ($y = 0; $y < $sy; $y++) {
            $xx = $x - $dx;
            $yy = $y - $dy;

            $x1 = round($xx * cos($r) + $yy * sin($r)) + $dx;
            $y1 = round(-$xx * sin($r) + $yy * cos($r)) + $dy;

            $p = imagecolorat($im, $x1, $y1);
            if ($p !== $c) { $p = 0; }
            imagesetpixel($im2, $x, $y, $p);
        }
    }

    return $im2;
}

function imagesmoothedges($im, $fg, $bg)
{
    $rgbf = imagecolorsforindex($im, $fg);
    $rgbb = imagecolorsforindex($im, $bg);
    $hcr = round(($rgbf['red'] + $rgbb['red']) / 2);
    $hcg = round(($rgbf['green'] + $rgbb['green']) / 2);
    $hcb = round(($rgbf['blue'] + $rgbb['blue']) / 2);
    $hc = imagecolorallocate($im, $hcr, $hcg, $hcb);

    $sx = imagesx($im);
    $sy = imagesy($im);

    for ($x = 1; $x < $sx-1; $x++) {
        for ($y = 1; $y < $sy-1; $y++) {
            $p = imagecolorat($im, $x, $y);
            if ($p !== $bg) { continue; }

            $dx = array(-1, 1, 0, 0);
            $dy = array(0, 0, -1, 1);
            $c = 0;
            for ($i = 0; $i < 4; $i++) {
                if (imagecolorat($im, $x+$dx[$i], $y+$dy[$i]) == $fg) {
                    $c++;
                    if ($c > 1) {
                        imagesetpixel($im, $x, $y, $hc);
                        break;
                    }
                }
            }
        }
    }
}

function get_imgltr($letter)
{
    $im = imagecreate(15, 20);

    $greypale = imagecolorallocate($im, 192, 192, 192);
    $grey = imagecolorallocate($im, 127, 127, 127);

    imagechar($im, 5, 3, 1, $letter, $grey);

    $im2 = imagecreate(30, 40);
    imagecopyresized($im2, $im, 0, 0, 0, 0, 30, 40, 15, 20);

    $angles = array(-25, -15, 15, 25);
    $im3 = imagerotatex($im2, $angles[array_rand($angles, 1)], $grey);

    return $im3;
}

function output_captcha_img($md5)
{
    $code = decode_captcha_md5($md5);
    $n = strlen($code);

    $im = imagecreate($n*30, 40);
    $sx = imagesx($im);
    $sy = imagesy($im);

    $greypale = imagecolorallocate($im, 192, 192, 192);
    $grey = imagecolorallocate($im, 127, 127, 127);

    for ($i = 0; $i < $n; $i++) {
        $imltr = get_imgltr(substr($code, $i, 1));
        imagecopy($im, $imltr, $i*30, 0, 0, 0, 30, 40);
    }

    imagesmoothedges($im, $grey, $greypale);

    $black = imagecolorallocate($im, 0, 0, 0);
    $white = imagecolorallocate($im, 255, 255, 255);

    $random_x = array();
    while (count($random_x) < 5) {
        $x = rand(5, $sx-6);
        if (!in_array($x, $random_x)) {
            $random_x[] = $x;
        }
    }

    $random_y = array();
    while (count($random_y) < 5) {
        $y = rand(5, $sy-6);
        if (!in_array($y, $random_y)) {
            $random_y[] = $y;
        }
    }

    for ($n = 0; $n < 5; $n++) {
        $x = $random_x[$n];
        imageline($im, $x, 0, $x, $sy-1, $white);
        $y = $random_y[$n];
        imageline($im, 0, $y, $sx-1, $y, $white);
    }

    imagerectangle($im, 0, 0, $sx-1, $sy-1, $black);

    header('Content-type: image/png');
    imagepng($im);
    exit;
}

function print_captcha_box()
{
    $captcha_md5 = get_captcha_md5();
    print 'Validation code: '."\n";
    print '<input type="hidden" name="captcha" value="'.$captcha_md5.'">'."\n";
    print '<input type="text" name="validationcode" size="25" maxlength="50" '.
          'value="" autocomplete="off"><br>'."\n";
    print 'Enter the validation code from the picture below.<br>'."\n";
    print '<img src="'.captchaURL($captcha_md5).'" alt="" border="0">'.
          '<br><br>'."\n";
}

?>
