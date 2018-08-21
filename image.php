<?php
// This script takes an image and resizes it to the given dimensions, then saves
// that version on the filesystem so Apache can serve it directly in the future.

header("Cache-control: public");
header("Cache-control: max-age=3600000");
header("Expires: Thu, 01 Jan 2040 00:00:01 GMT");


// sleep(5);


// $LastModified_unix = 1294844676;
$LastModified = gmdate("D, d M Y H:i:s \G\M\T", $LastModified_unix);
$IfModifiedSince = false;
if (isset($_ENV['HTTP_IF_MODIFIED_SINCE']))
    $IfModifiedSince = strtotime(substr($_ENV['HTTP_IF_MODIFIED_SINCE'], 5));  
if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']))
    $IfModifiedSince = strtotime(substr($_SERVER['HTTP_IF_MODIFIED_SINCE'], 5));
if ($IfModifiedSince && $IfModifiedSince >= $LastModified_unix) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified');
    exit;
}
header('Last-Modified: '. $LastModified);

if (isset($_GET['amp;url'])) {
	$_GET['url'] = $_GET['amp;url'];
}

chdir(dirname(__FILE__));
$size     = $_GET['size'];
$url     = urldecode($_GET['url']);


if (strpos($url, "/images") === 0) {
	$url = "http://v-gornom.ru" . $url;
}

if (strpos($url, "//") === 0) {
	$url = "http:" . $url;
}


// Check the size is valid
if (is_numeric($size)) $size = "numeric";

switch ($size) {
    case 'image':
        $thumbWidth  = 210;
        $thumbHeight = 104;
        break;
    case 'text':
        $thumbWidth  = 55;
        $thumbHeight = 55;
        break;
    case 'photo':
        $thumbWidth  = 184;
        $thumbHeight = null;
        break;
    case 'banner':
        $thumbWidth  = 422;
        $thumbHeight = null;
        break;	
    case 'numeric':
        $thumbWidth  = $_GET['size'];
        $thumbHeight = null;
        break;	
    default:
        die('Invalid image size');
}

$size     = $_GET['size'];

$file = basename($url);
if (strpos($file, "?") > 0) {
	$file = substr($file, 0, strpos($file, "?"));
}
	
// Make sure the directory exists
if (!is_dir("imagecache/originals")) {
	mkdir("imagecache/originals");
	chmod("imagecache/originals", 0777);
}

$ext = "." . pathinfo($file, PATHINFO_EXTENSION);

$original = "imagecache/originals/" . dechex(crc32($url)) . $ext;
$target   = "imagecache/$size/" . dechex(crc32($url)) . $ext;

// Make sure the file doesn't exist already
if (!file_exists($target)) {

	if (!file_exists($original)) {
		// copy($url, $original);
		require 'guzzle/vendor/autoload.php';

		$client = new \GuzzleHttp\Client();
		$client->get($url, ['save_to' => $original]);
	}
	

	// Check the original file exists
	if (!is_file($original)) {
		die('File doesn\'t exist');
	}
	
	// Make sure the directory exists
	if (!is_dir("imagecache/" . $size)) {
		mkdir("imagecache/" . $size);
		if (!is_dir("imagecache/" . $size)) {
			die('Cannot create directory');
		}
		chmod("imagecache/" . $size, 0777);
	}


    // Make sure we have enough memory
    ini_set('memory_limit', 128 * 1024 * 1024);
    // Get the current size & file type
    list($width, $height, $type) = getimagesize($original);
	
	if ($width < $thumbWidth) {
		$thumbWidth = $width;
	} 
	// else {	
		
		// Load the image
		switch ($type) {
			case IMAGETYPE_GIF:
				$image = imagecreatefromgif($original);
				break;
			case IMAGETYPE_JPEG:
				$image = imagecreatefromjpeg($original);
				break;
			case IMAGETYPE_PNG:
				$image = imagecreatefrompng($original);
				break;
			default:
				die("Invalid image type (#{$type} = " . image_type_to_extension($type) . ")");
		}
		// Calculate height automatically if not given
		if ($thumbHeight === null) {
			$thumbHeight = round($height * $thumbWidth / $width);
		}
		// Ratio to resize by
		$widthProportion  = $thumbWidth / $width;
		$heightProportion = $thumbHeight / $height;
		$proportion       = max($widthProportion, $heightProportion);
		// Area of original image that will be used
		$origWidth        = floor($thumbWidth / $proportion);
		$origHeight       = floor($thumbHeight / $proportion);
		// Co-ordinates of original image to use
		$x1               = floor($width - $origWidth) / 2;
		$y1               = floor($height - $origHeight) / 2;
		// Resize the image
		$thumbImage       = imagecreatetruecolor($thumbWidth, $thumbHeight);
		imagecopyresampled($thumbImage, $image, 0, 0, $x1, $y1, $thumbWidth, $thumbHeight, $origWidth, $origHeight);
		// Save the new image
		switch ($type) {
			case IMAGETYPE_GIF:
				imagegif($thumbImage, $target);
				break;
			case IMAGETYPE_JPEG:
				imagejpeg($thumbImage, $target, 60);
				break;
			case IMAGETYPE_PNG:
				imagepng($thumbImage, $target);
				break;
			default:
				throw new LogicException;
		}
		// Make sure it's writable
		chmod($target, 0666);
		// Close the files
		imagedestroy($image);
		imagedestroy($thumbImage);
	// }
}
$data = getimagesize($target);
if (!$data) {
    die("Cannot get mime type");
} else {
    header('Content-Type: ' . $data['mime']);
}
// Send the file to the browser
readfile($target);
