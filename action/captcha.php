<?php

require('lib/captcha.php');

// This function emits a captcha image.
function action_captcha()
{
    global $md5;

    output_captcha_img($md5);
}
