<?php
/**
 * Copyleft (c) 2013 Vino Rodrigues
 * 
 * This work is Public Domain.
 *
 * **********************************************************************
 *   This code is distributed in the hope that it will be useful, but
 *   WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
 * **********************************************************************
 */

if ('cli' === PHP_SAPI) {
	// called from the command line
	if (is_array($argv) && (count($argv) == 2))
		$_REQUEST['u'] = $argv[1];
}
 
function Error() {
	header('Content-Type: image/gif');
	header('Content-Disposition: inline; filename="fakeapple.gif"');
	$blank = 'R0lGODlhAQABAPAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=='; // A transparent 1x1 GIF image
	$image = ImageCreateFromString(base64_decode($blank));
	ImageGif($image);
	ImageDestroy($image);
	die();
}

function GetPixelColor(&$img, $x, $y) {
	return ImageColorsForIndex($img, ImageColorAt($img, $x, $y));
}

function GrayscaleValue($r, $g, $b) {
	return Round(($r * 0.30) + ($g * 0.59) + ($b * 0.11));
}

function GrayscalePixel($OriginalPixel) {
	$gray = GrayscaleValue($OriginalPixel['red'],
		$OriginalPixel['green'],
		$OriginalPixel['blue']);
	return array('red'=>$gray, 'green'=>$gray, 'blue'=>$gray);
}

function ApplyMask(&$image, $mask) {
	// Create copy of mask as mask may not be the same size as image
	$mask_resized = ImageCreateTrueColor(ImageSX($image),
		ImageSY($image));
	ImageCopyResampled($mask_resized, $mask, 0, 0, 0, 0,
		ImageSX($image), ImageSY($image),
		ImageSX($mask), ImageSY($mask));

	// Create working temp
	$mask_blendtemp = ImageCreateTrueColor(ImageSX($image),
		ImageSY($image));
	$color_background = ImageColorAllocate($mask_blendtemp, 0, 0, 0);
	ImageFilledRectangle($mask_blendtemp, 0, 0,
		ImageSX($mask_blendtemp), ImageSY($mask_blendtemp),
		$color_background);

	// switch off single color alph and switch on full alpha channel
	ImageAlphaBlending($mask_blendtemp, false);
	ImageSaveAlpha($mask_blendtemp, true);

	// loop the entire image and set pixels, this will be slow for large images
	for ($x = 0; $x < ImageSX($image); $x++) {
		for ($y = 0; $y < ImageSY($image); $y++) {
			$RealPixel = GetPixelColor($image, $x, $y);
			$MaskPixel = GrayscalePixel(GetPixelColor($mask_resized, $x, $y));
			$MaskAlpha = 127 - (Floor($MaskPixel['red'] / 2) *
				(1 - ($RealPixel['alpha'] / 127)));
			$newcolor = ImageColorAllocateAlpha($mask_blendtemp,
				$RealPixel['red'], $RealPixel['green'], $RealPixel['blue'],
				$MaskAlpha);
			ImageSetPixel($mask_blendtemp, $x, $y, $newcolor);
		}
	}

	// don't need the mask copy anymore
	ImageDestroy($mask_resized);

	// switch off single color alph and switch on full alpha channel
	ImageAlphaBlending($image, false);
	ImageSaveAlpha($image, true);

	// replace the image with the blended temp
	ImageCopy($image, $mask_blendtemp, 0, 0, 0, 0,
		ImageSX($mask_blendtemp), ImageSY($mask_blendtemp));
	ImageDestroy($mask_blendtemp);
}

function CreateThumb($source, $x = 300, $y = 300)
{
	$new_x = ImageSX($source);  $new_y = ImageSY($source);
	$image = ImageCreateTrueColor($x, $y);
	ImageCopyResampled($image, $source, 0, 0, 0, 0, $x, $y, $new_x, $new_y);
	return $image;
}

function ApplyGlow(&$image)
{
	/* Aliasing trick - generate bigger image than needed, then resize */
	$x = ImageSX($image);
	$xplus = $x * 3;
	$glowmask = ImageCreateTrueColor($xplus, $xplus);
	ImageAlphaBlending($glowmask, false);
	ImageSaveAlpha($glowmask, true);
	// background
	$backcolor = ImageColorAllocateAlpha($glowmask, 255, 255, 255, 127);
	ImageFilledRectangle($glowmask, 0, 0, $xplus, $xplus, $backcolor);
	// forecolor
	$forecolor = ImageColorAllocateAlpha($glowmask, 255, 255, 255, 90);
	ImageFilledEllipse($glowmask, $xplus / 2, 0, 1.7 * $xplus, $xplus,
		$forecolor);
	ImageCopyResampled($image, $glowmask, 0, 0, 0, 0, $x, $x, $xplus, $xplus);
	ImageDestroy($glowmask);
}

function ApplyRoundedCorners(&$image, $radius = 0)
{
	if ($radius <= 0) {
		$radius = Round(ImageSX($image) / 4);
	}

	/* Generate mask at twice desired resolution and downsample afterwards for
	 * easy antialiasing mask is generated as a white double-size elipse on a
	 * triple-size black background and copy-paste-resampled onto a
	 * correct-size mask image as 4 corners due to errors when the entire mask
	 * is resampled at once (gray edges).
 	* */
	$x = ImageSX($image);  $y = ImageSY($image);
	$cornermask = ImageCreateTrueColor($x * 2 , $y * 2);
	$cornermask3 = ImageCreateTrueColor($radius * 6, $radius * 6);

	$color_transparent = ImageColorAllocate($cornermask3, 255, 255, 255);
	ImageFilledEllipse($cornermask3, $radius * 3, $radius * 3,
		$radius * 4, $radius * 4, $color_transparent);

	ImageFilledRectangle($cornermask, 0, 0, $x * 2, $y * 2, $color_transparent);

	ImageCopyResampled($cornermask, $cornermask3, 0,                  0,                 $radius,     $radius,     $radius, $radius, $radius * 2, $radius * 2);
	ImageCopyResampled($cornermask, $cornermask3, 0,                 ($y * 2) - $radius, $radius,     $radius * 3, $radius, $radius, $radius * 2, $radius * 2);
	ImageCopyResampled($cornermask, $cornermask3, ($x * 2) - $radius,($y * 2) - $radius, $radius * 3, $radius * 3, $radius, $radius, $radius * 2, $radius * 2);
	ImageCopyResampled($cornermask, $cornermask3, ($x * 2) - $radius, 0,                 $radius * 3, $radius,     $radius, $radius, $radius * 2, $radius * 2);

	ImageDestroy($cornermask3);
	ApplyMask($image, $cornermask);
	ImageDestroy($cornermask);
}

function CreateAppleIcon($source, $size = 57, $radius = 20)
{
	$image = CreateThumb($source, $size, $size);
	ApplyGlow($image);
	ApplyRoundedCorners($image, $radius);
	return $image;
}

/** Code starts here */

if (!function_exists('gd_info')) die('GD not supported on this server');

$url = (isset($_REQUEST['u']) ? $_REQUEST['u'] : false);
$size = (isset($_REQUEST['s']) ? $_REQUEST['s'] : false);

if (!$url || empty($url)) Error();
if (!$size) $size = 144;

$source = @ImageCreateFromPng($url);

if (!$source) Error();

$image = CreateAppleIcon( $source, $size, intval( (20 / 57) * $size ) );

header('Content-Type: image/png');
header('Content-Disposition: inline; filename="fakeapple.png"');
ImagePng($image);
ImageDestroy($image);
ImageDestroy($source);

/* eof */
