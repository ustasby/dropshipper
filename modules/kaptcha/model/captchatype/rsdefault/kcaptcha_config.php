<?php

# KCAPTCHA configuration file

$alphabet = "0123456789abcdefghijklmnopqrstuvwxyz"; # do not change without changing font files!

# symbols used to draw CAPTCHA
//$allowed_symbols = "0123456789"; #digits
$allowed_symbols = "0123456789"; #alphabet without similar symbols (o=0, 1=l, i=j, t=f)

$no_fonts = true;
# folder with fonts
$fontsdir = 'fonts';	

# CAPTCHA string length
//$length = mt_rand(5,6); # random 5 or 6
$length = 6;

# CAPTCHA image size (you do not need to change it, whis parameters is optimal)
$width = 100;
$height = 42;

# symbol's vertical fluctuation amplitude divided by 2
$fluctuation_amplitude = 4;

$no_fluctuation = false;
$no_wave = false;

# increase safety by prevention of spaces between symbols
$no_spaces = false;

# show credits
$show_credits = false; # set to false to remove credits line. Credits adds 12 pixels to image height
$credits = ''; # if empty, HTTP_HOST will be shown

# CAPTCHA image colors (RGB, 0-255)
/*
$red = mt_rand(0,255);
$green = mt_rand(0,255);
$blue = mt_rand(0,255);
$background_color = array($red, $green, $blue);
$foreground_color = array(255-$red, 255-$green, 255-$blue);
*/
$foreground_color = array(0,0,80);
$line_color = array(200,200,255);
$background_color = array(255,255,255);

# JPEG quality of CAPTCHA image (bigger is better quality, but larger file size)
$jpeg_quality = 50;
