<?php defined('SYSPATH') or die('No direct access allowed.');

return array
(
	'mysqli-example' => array(
		'type'       => 'mysqli',
		'connection' => array(
			/**
			 * The following options are available for PDO:
			 *
			 * string   dsn         Data Source Name
			 * string   username    database username
			 * string   password    database password
			 * boolean  persistent  use persistent connections?
			 */
			'host'       => 'localhost',
			'username'   => 'user',
			'password'   => 'password',
			'database'   => 'kohana',
			'persistent' => FALSE,
			'port'       => NULL,
			'socket'     => NULL,
			'params'     => NULL,
		),
		'table_prefix' => '',
		'charset'      => 'utf8',
		'caching'      => FALSE,
		'profiling'    => TRUE,
	),
);