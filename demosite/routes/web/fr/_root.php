<?php

$_routes[]=array(
				'test_(.*)',
				array('home', 'index', 'test'),
				array('id')
				);

$_routes[]=array(
				'list',
				array('home', 'index', 'list')
				);