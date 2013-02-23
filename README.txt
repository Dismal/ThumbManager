ThumbManager
This class creates thumbnails optimal way.
The image is resized without distortion and then crop the extra space.

GD library is required

Usage:

require_once("./ThumbManager.php");
$tm = new ThumbManager();
$tm->thumb_width = 100;
$tm->thumb_height = 100;
$tm->watermark = './images/watermark.png';
$result = $tm->create_thumb('./images/image.jpg', './images/thumb_image.jpg');
if (!$result) print_r($tm->get_errors());

@property integer $thumb_width Width of the thumbnail.
@property integer $thumb_height Height of the thumbnail.
@property string $wm_uri Path to the watermark.