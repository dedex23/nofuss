<?php

namespace Nf;

class Localization extends Singleton
{

	/*
		démarrage bootstrap : locale définie

	*/

	protected static $_instance;

	protected static $_currentLocale='fr_FR';

	public static function normalizeLocale($str) {
		$str=str_replace('-', '_', $str);
		$arr=explode('_', $str);
		$out=strtolower($arr[0]) . '_' . strtoupper($arr[1]);
		return $out;
	}


}