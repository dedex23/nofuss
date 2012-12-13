<?php

namespace Nf;

use \IntlDateFormatter, \NumberFormatter;

class Localization extends Singleton
{

	protected static $_instance;

	protected $_currentLocale='fr_FR';

	const NONE=IntlDateFormatter::NONE;
	const SHORT=IntlDateFormatter::SHORT;
	const MEDIUM=IntlDateFormatter::MEDIUM;
	const LONG=IntlDateFormatter::LONG;
	const FULL=IntlDateFormatter::FULL;

	public static function normalizeLocale($str) {
		$str=str_replace('-', '_', $str);
		$arr=explode('_', $str);
		$out=strtolower($arr[0]) . '_' . strtoupper($arr[1]);
		return $out;
	}

	public static function setLocale($locale) {
		$instance=self::$_instance;
		$instance->_currentLocale=$locale;
	}

	public static function getLocale() {
		$instance=self::$_instance;
		return $instance->_currentLocale;
	}

	public static function formatDate($timestamp, $formatDate=self::SHORT, $formatTime=self::SHORT) {
		$instance=self::$_instance;
		$fmt=new IntlDateFormatter($instance->_currentLocale, $formatDate, $formatTime);
		return $fmt->format($timestamp);
	}

	public static function formatCurrency($amount, $currency) {
		$instance=self::$_instance;
		$fmt = new NumberFormatter($instance->_currentLocale(), NumberFormatter::CURRENCY);
		return $fmt->formatCurrency($amount, $currency);
	}

	public static function formatNumber($value) {
		$instance=self::$_instance;
		$fmt = new NumberFormatter($instance->_currentLocale(), NumberFormatter::DECIMAL);
		return $fmt->format($value);
	}

	public static function dateToTimestamp($date, $formatDate=self::SHORT, $formatTime=self::SHORT) {
		if(self::isTimestamp($date)) {
			return $date;
		}
		else {
			$instance=self::$_instance;
			$fmt=new IntlDateFormatter($instance->_currentLocale, $formatDate, $formatTime);
			$timestamp=$fmt->parse($date);
			if($timestamp) {
				return $timestamp;
			}
			else {
				throw new \Exception('input date is in another format and is not recognized:' . $date);
			}
		}

	}

	public static function isTimestamp($timestamp) {
		return ((string) (int) $timestamp === $timestamp)
		    && ($timestamp <= PHP_INT_MAX)
		    && ($timestamp >= ~PHP_INT_MAX);
	}

}