<?php

namespace Nf;

abstract class Image
{
	// intégrer ce code : https://gist.github.com/1364489

	public static function generateThumbnail($imagePath, $thumbnailPath, $thumbnailWidth=100, $thumbnailHeight=100, $cut=false) {

		// load the original image
		$image = new \Imagick($imagePath);

		// get the original dimensions
		$width = $image->getImageWidth();
		$height = $image->getImageHeight();

		// the image will not be cut and the final dimensions will be within the requested dimensions
		if(!$cut) {
			// width & height : maximums and aspect ratio is maintained
			if($thumbnailHeight==0) {
				$r=$width/$height;
				$thumbnailHeight=ceil($thumbnailWidth/$r);
				// create thumbnail
				$image->thumbnailImage($thumbnailWidth, $thumbnailHeight);
			}
			elseif($thumbnailWidth==0) {
				$r=$width/$height;
				$thumbnailWidth=ceil($thumbnailHeight*$r);
				// create thumbnail
				$image->thumbnailImage($thumbnailWidth, $thumbnailHeight);
			}
			else {
				// determine which dimension to fit to
				$fitWidth = ($thumbnailWidth / $width) < ($thumbnailHeight / $height);
				// create thumbnail
				$image->thumbnailImage(
				  $fitWidth ? $thumbnailWidth : 0,
				  $fitWidth ? 0 : $thumbnailHeight
				);
			}
		}
		else {

			if($thumbnailWidth==0 || $thumbnailHeight==0) {
				throw new Exception ('Cannot generate thumbnail in "cut" mode when a dimension equals zero. Specify the dimensions.');
			}

			// scale along the smallest side
			$r=$width/$height;
			if($r<1) {
				$newWidth=$thumbnailWidth;
				$newHeight=ceil($thumbnailWidth/$r);
			}
			else {
				$newWidth=ceil($thumbnailHeight*$r);
				$newHeight=$thumbnailHeight;
			}

			$image->thumbnailImage($newWidth, $newHeight);
			$width=$newWidth;
			$height=$newHeight;

			$workingImage=$image->getImage();
			$workingImage->contrastImage(50);
			//$workingImage->setImageBias(25000);
			$kernel = array( 0,-1,0,
			                 -1,4,-1,
			                 0,-1,0);

			$workingImage->convolveImage($kernel);
			//$workingImage->edgeImage(1);


			//header('Content-type: image/jpg');
//			echo $workingImage;
//			die();



			$x=0;
			$y=0;
			$sliceLength=16;

			while($width-$x>$thumbnailWidth) {

				$sliceWidth=min($sliceLength, $width - $x - $thumbnailWidth);
				$imageCopy1=$workingImage->getImage();
				$imageCopy2=$workingImage->getImage();
				$imageCopy1->cropImage($sliceWidth, $height, $x, 0);
				$imageCopy2->cropImage($sliceWidth, $height, $width - $sliceWidth, 0);

				if(self::entropy($imageCopy1) < self::entropy($imageCopy2)) {
					$x+=$sliceWidth;
				}
				else {
					$width-=$sliceWidth;
				}
			}

			while($height-$y>$thumbnailHeight) {

				$sliceHeight=min($sliceLength, $height - $y - $thumbnailHeight);
				$imageCopy1=$workingImage->getImage();
				$imageCopy2=$workingImage->getImage();
				$imageCopy1->cropImage($width, $sliceHeight, 0, $y);
				$imageCopy2->cropImage($width, $sliceHeight, 0, $height - $sliceHeight);

				if(self::entropy($imageCopy1) < self::entropy($imageCopy2)) {
					$y+=$sliceHeight;
				}
				else {
					$height-=$sliceHeight;
				}
			}

			// @image.crop(x, y, crop_width, crop_height)
			$image->cropImage($thumbnailWidth, $thumbnailHeight, $x, $y);
		}

		$image->writeImage($thumbnailPath);
		$image->clear();
		$image->destroy();

		return $thumbnailPath;
	}


	private static function entropy($image) {
		$image->setImageType(\Imagick::IMGTYPE_GRAYSCALE);
		$pixels=$image->getImageHistogram();
		$hist=array();
		foreach($pixels as $p){
			$color = $p->getColor();
			$theColor=$color['r'];
			if(!isset($hist[$theColor])) {
				$hist[$theColor]=0;
			}
			$hist[$theColor]+=$p->getColorCount();
		}
		// calculates the entropy from the histogram
		// cf http://www.mathworks.com/help/toolbox/images/ref/entropy.html
		$entropy=0;
		foreach($hist as $c=>$v) {
			$entropy-=$v*log($v, 2);
		}
		return $entropy;
	}

	// inspired from http://codebrawl.com/contests/content-aware-image-cropping-with-chunkypng
	public static function contentAwareCrop($sourceFile, $destFile, $width=100, $height=100) {
		// 1) $im->scaleImage(2000, 1500, true); // => 1600x1200
		// 2) bestfit
		//Imagick::resizeImage ( int $columns , int $rows , int $filter , float $blur [, bool $bestfit = false ] )
	}

	public static function identifyImage($sourceFile) {
		return \Imagick::identifyImage($sourceFile);
	}


}