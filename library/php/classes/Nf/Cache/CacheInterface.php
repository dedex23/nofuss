<?php

namespace Nf\Cache;

interface CacheInterface
{

	public function load($keyName, $keyValues);

	public function save($keyName, $keyValues, $data, $lifetime=Cache::DEFAULT_LIFETIME);

	public function delete($keyName, $keyValues);

}