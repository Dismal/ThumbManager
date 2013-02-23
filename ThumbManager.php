<?php

/**
 * ThumbManager
 * This class creates thumbnails optimal way.
 * The image is resized without distortion and then crop the extra space.
 *
 * @author Dmitry Kuznetsov (https://github.com/Dismal/ThumbManager)
 * GD library is required
 *
 * Usage:
 *
 * $tm = new ThumbManager();
 * $tm->thumb_width = 100;
 * $tm->thumb_height = 100;
 * $tm->watermark = './images/watermark.png';
 * $result = $tm->create_thumb('./images/image.jpg', './images/thumb_image.jpg');
 * if (!$result) print_r($tm->get_errors());
 *
 * @property integer $thumb_width Width of the thumbnail.
 * @property integer $thumb_height Height of the thumbnail.
 * @property string $wm_uri Path to the watermark.
 *
*/

class ThumbManager {

	public $thumb_width = 0;
	public $thumb_height = 0;
	public $wm_uri = false;
	
	protected $_src_uri;
	protected $_drc_uri;
    protected $_src_format;
    protected $_wm_format;
    protected $_errors = array();

	/*
	 * Creating the thumbnail
	 * @param string $src_uri Path to the source image. For example: "./images/source.jpg"
	 * @param string $drc_uri Path to the thumbnail. For example: "./images/thumb.jpg"
	 * @return boolean
	 */
	function create_thumb ($src_uri, $drc_uri) {

		$this->_src_uri = $src_uri;
		$this->_drc_uri = $drc_uri;

        if (!$this->checkRights()) return false;

        $srcfunc = "imagecreatefrom".$this->_src_format;
		$im=$srcfunc($src_uri) ;
		
		$im_width = imagesx($im) ;
		$im_height = imagesy($im) ;
		
		//если высота не указана
		if ($this->thumb_height == 0) {
			$im1_height = round($im_height*$this->thumb_width / $im_width) ;
			$im1_width = $this->thumb_width ;
			$im1 = imagecreatetruecolor($im1_width, $im1_height) ;
			$this->thumb_height = $im1_height ;
		}
		
		//если ширина не указана
		elseif ($this->thumb_width == 0) {
			$im1_width = round($im_width*$this->thumb_height / $im_height) ;
			$im1_height = $this->thumb_height ;
			$im1 = imagecreatetruecolor($im1_width, $im1_height) ;
			$this->thumb_width = $im1_width ;
		}
		
		//Если миниатюра более квадратная, то вписываем изображение в нее	
		elseif (
			(($im_width/$im_height) + (1/($im_width/$im_height)))
			> 
			(($this->thumb_width/$this->thumb_height) + (1/($this->thumb_width/$this->thumb_height)))
			) {
				//Если ширина больше высоты, то уменьшаем по высоте и пропорционально ширину
				if ($im_width >= $im_height) {
					 $im1_width = round($im_width*$this->thumb_height / $im_height) ;
					 $im1_height = $this->thumb_height ;
					 $im1 = imagecreatetruecolor($im1_width, $im1_height) ;
				}
				
				//Если высота больше ширины, то уменьшаем по ширине и пропорционально высоту
				else {
					 $im1_height = round($im_height*$this->thumb_width / $im_width) ;
					 $im1_width = $this->thumb_width ;
					 $im1 = imagecreatetruecolor($im1_width, $im1_height) ;
				}
		
		} 
		
		// Если изображение более квадратное, чем миниатюра, то наоборот вписываем миниатюру в изображение
		else {
	
				//Если ширина миниатюры больше высоты, то уменьшаем по ширине и пропорционально высоту
				if ($this->thumb_width >= $this->thumb_height) {
					 $im1_height = round($im_height*$this->thumb_width / $im_width) ;
					 $im1_width = $this->thumb_width ;
					 $im1 = imagecreatetruecolor($im1_width, $im1_height) ;
				}
				
				//Если высота больше ширины, то уменьшаем по высоте и пропорционально ширину
				else {
					$im1_width = round($im_width*$this->thumb_height / $im_height) ;
					$im1_height = $this->thumb_height ;
					$im1 = imagecreatetruecolor($im1_width, $im1_height) ;
				}
			
		}
		
		imagecopyresampled($im1,$im,0,0,0,0,$im1_width,$im1_height,$im_width,$im_height) ;
		
		//обрезаем все лишнее	
		$thumb = imagecreatetruecolor($this->thumb_width, $this->thumb_height) ;
		$center_x = floor(($im1_width - $this->thumb_width)/2) ;
		$center_y = floor(($im1_height - $this->thumb_height)/2) ;
		imagecopy ($thumb, $im1, 0, 0, $center_x, $center_y, $this->thumb_width, $this->thumb_height) ;
		
		if ($this->wm_uri !== FALSE) {
			$thumb = $this->create_watermark($thumb) ;
		}

		imagejpeg($thumb, $this->_drc_uri) ;
		return true ;
	}

    /*
	 * Get all errors after executing create_thumb() function
	 * @return array
	 */
    public function getErrors() {
        return $this->_errors;
    }

    protected function checkRights() {

        // check the source file
        if (!file_exists($this->_src_uri))
            $this->_errors[] = 'Source file not found';
        elseif (!$size = getimagesize($this->_src_uri))
            $this->_errors[] = 'Source file is not an image';
        else {
            $this->_src_format = strtolower(substr($size['mime'], strpos($size['mime'], '/')+1));
            $icfunc = "imagecreatefrom" . $this->_src_format;
            if (!function_exists($icfunc))
                $this->_errors[] = "Function $icfunc not found for source file";
        }

        // check the destination folder
        if(!is_writable(dirname($this->_drc_uri))) $this->_errors[] = 'Destination folder is not writable or not found';

        // check the watermark file
        if ($this->wm_uri) {
            if (!file_exists($this->wm_uri))
                $this->_errors[] = 'Watermark not found';
            elseif (!$size = getimagesize($this->wm_uri))
                $this->_errors[] = 'Watermark is not an image';
            else {
                $this->_wm_format = strtolower(substr($size['mime'], strpos($size['mime'], '/')+1));
                $icfunc = "imagecreatefrom" . $this->_wm_format;
                if (!function_exists($icfunc))
                    $this->_errors[] = "Function $icfunc not found for watermark file";
            }
        }

        if (!empty($this->_errors))
            return false;
        return true;
    }
	
	// Overlay watermark
	protected function create_watermark($tmb) {
        $wmfunc = "imagecreatefrom".$this->_wm_format;
        $wm=$wmfunc($this->wm_uri) ;
		$wm_pos_x = imagesx($tmb) - imagesx($wm);
		$wm_pos_y = imagesy($tmb) - imagesy($wm);		
		imagecopy ($tmb, $wm, $wm_pos_x, $wm_pos_y, 0, 0, imagesx($wm), imagesy($wm)) ;		
		return $tmb ;
	}
}