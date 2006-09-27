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

function get_imgltr($letter)
{
    $im = imagecreate(15, 20);

    #$white = imagecolorallocate($im, 255, 255, 255);
    #$grey = imagecolorallocate($im, 90, 90, 90);
    $greypale = imagecolorallocate($im, 192, 192, 192);
    $grey = imagecolorallocate($im, 127, 127, 127);

    imagechar($im, 5, 0, 0, $letter, $grey);

    return $im;
}

function output_captcha_img($md5)
{
    $code = decode_captcha_md5($md5);
    $n = strlen($code);

    $im = imagecreate($n*15+5, 20);
    $sx = imagesx($im);
    $sy = imagesy($im);

    #$white = imagecolorallocate($im, 255, 255, 255);
    #$grey = imagecolorallocate($im, 90, 90, 90);
    $greypale = imagecolorallocate($im, 192, 192, 192);

    for ($i = 0; $i < $n; $i++) {
        $imltr = get_imgltr(substr($code, $i, 1));
        imagecopy($im, $imltr, $i*15+5, 0, 0, 0, 15, 20);
    }

    $im2 = imagecreate($sx*2, $sy*2);
    $sx2 = imagesx($im2);
    $sy2 = imagesy($im2);

    imagecopyresized($im2, $im, 0, 0, 0, 0, $sx2, $sy2, $sx, $sy);

    $black = imagecolorallocate($im2, 0, 0, 0);
    $white = imagecolorallocate($im2, 255, 255, 255);

    #for ($i = 3; $i < 256; $i++) {
    #    imagecolorallocate($im2, rand(80, 255), rand(80, 255), rand(80, 255));
    #}
    #for ($x = 0; $x < $sx2; $x++) {
    #    for ($y = 0; $y < $sy2; $y++) {
    #        if (imagecolorat($im2, $x, $y) == 0) {
    #            imagesetpixel($im2, $x, $y, rand(0, 255));
    #        }
    #    }
    #}

    $random_x = array();
    while (count($random_x) < 5) {
        $x = rand(15, $sx2-10);
        if (!in_array($x, $random_x)) {
            $random_x[] = $x;
        }
    }

    $random_y = array();
    while (count($random_y) < 5) {
        $y = rand(5, $sy2-10);
        if (!in_array($y, $random_y)) {
            $random_y[] = $y;
        }
    }

    for ($n = 0; $n < 5; $n++) {
        $x = $random_x[$n];
        imageline($im2, $x, 0, $x, $sy2-1, $white);
        $y = $random_y[$n];
        imageline($im2, 0, $y, $sx2-1, $y, $white);
    }

    imagerectangle($im2, 0, 0, $sx2-1, $sy2-1, $black);

    header('Content-type: image/png');
    imagepng($im2);
    exit;
}

function print_captcha_box()
{
    $captcha_md5 = get_captcha_md5();
    print 'Validation code: '."\n";
    print '<input type="hidden" name="captcha" value="'.$captcha_md5.'">'."\n";
    print '<input type="text" name="validationcode" size="25" maxlength="50" '.
          'value="" autocomplete="off"><br>'."\n";
    print 'Enter the validation code from the picture below:<br>'."\n";
    print '<img src="'.captchaURL($captcha_md5).'" alt="" border="0">'.
          '<br><br>'."\n";
}

?>
