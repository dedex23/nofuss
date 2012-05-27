<?php

namespace Nf;

abstract class Image
{
	// intégrer ce code : https://gist.github.com/1364489

	public static function generateThumbnail($imagePath, $thumbnailPath, $thumbnailWidth=100, $thumbnailHeight=100) {

		// path to the sRGB ICC profile
		// $srgbPath = realpath(__DIR__ . '/Image/sRGB_v4_ICC_preference.icc');

		// load the original image
		$image = new \Imagick($imagePath);

		// get the original dimensions
		$width = $image->getImageWidth();
		$height = $image->getImageHeight();

		// set colour profile
		// this step is necessary even though the profiles are stripped out in the next step to reduce file size
		// $srgb = file_get_contents($srgbPath);
		// $image->profileImage('icc', $srgb);

		// strip colour profiles
		// $image->stripImage();

		// set colorspace
		// $image->setImageColorspace(\Imagick::COLORSPACE_SRGB);

		// width & height : maximums and aspect ratio is maintained
		if($thumbnailHeight==0) {
			$r=$width/$height;
			$thumbnailHeight=ceil($thumbnailWidth/$r);
			// create thumbnail
			$image->thumbnailImage($thumbnailWidth, $thumbnailHeight);
		}
		elseif($thumbnailWidth==0) {
			$r=$width/$height;
			$thumbnailWidth=ceil($thumbnailHeight/$r);
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

		// save thumbnail and free up memory
		$image->writeImage($thumbnailPath);
		$image->clear();
		$image->destroy();

		return $thumbnailPath;
	}



	/*
ALGO :
def image_entropy(img):
    """calculate the entropy of an image"""
    hist = img.histogram()
    hist_size = sum(hist)
    hist = [float(h) / hist_size for h in hist]

    return -sum([p * math.log(p, 2) for p in hist if p != 0])

def square_image(img):
    """if the image is taller than it is wide, square it off. determine
    which pieces to cut off based on the entropy pieces."""
    x,y = img.size
    while y > x:
        #slice 10px at a time until square
        slice_height = min(y - x, 10)

        bottom = img.crop((0, y - slice_height, x, y))
        top = img.crop((0, 0, x, slice_height))

        #remove the slice with the least entropy
        if image_entropy(bottom) < image_entropy(top):
            img = img.crop((0, 0, x, y - slice_height))
        else:
            img = img.crop((0, slice_height, x, y))

        x,y = img.size

    return img

CODE EN RUBY

class Cake
  attr_accessor :debug

  def initialize(file)
    @image = ChunkyPNG::Image.from_file(file)
  end

  def crop_and_scale(new_width = 100, new_height = 100)
    width, height = @image.width, @image.height

    if width > height
      width = height
    else
      height = width
    end

    result = crop(width, height)
    result.resample_bilinear!(new_width, new_height) unless debug
    result
  end

  def crop(crop_width = 100, crop_height = 100)
    x, y, width, height = 0, 0, @image.width, @image.height
    slice_length = 16

    while (width - x) > crop_width
      slice_width = [width - x - crop_width, slice_length].min

      left = @image.crop(x, 0, slice_width, @image.height)
      right = @image.crop(width - slice_width, 0, slice_width, @image.height)

      if entropy(left) < entropy(right)
        x += slice_width
      else
        width -= slice_width
      end
    end

    while (height - y) > crop_height
      slice_height = [height - y - crop_height, slice_length].min

      top = @image.crop(0, y, @image.width, slice_height)
      bottom = @image.crop(0, height - slice_height, @image.width, slice_height)

      if entropy(top) < entropy(bottom)
        y += slice_height
      else
        height -= slice_height
      end
    end

    if debug
      return @image.rect(x, y, x + crop_width, y + crop_height, ChunkyPNG::Color::WHITE)
    end

    @image.crop(x, y, crop_width, crop_height)
  end

  private

  def histogram(image)
    hist = Hash.new(0)

    image.height.times do |y|
      image.width.times do |x|
        hist[image[x,y]] += 1
      end
    end

    hist
  end

  # http://www.mathworks.com/help/toolbox/images/ref/entropy.html
  def entropy(image)
    hist = histogram(image.grayscale)
    area = image.area.to_f

    -hist.values.reduce(0.0) do |e, freq|
      p = freq / area
      e + p * Math.log2(p)
    end
  end
end

options = { :width => 100, :height => 100 }
	   */

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