<?php

return [
	'configuration'     => [
		'host'         => 'localhost',
		'jwt'          => null,
		'lang'         => 'php',
		'pass'         => null,
		'pedantic'     => false,
		'port'         => 4222,
		'reconnect'    => true,
		'timeout'      => 1,
		'token'        => null,
		'user'         => null,
		'nkey'         => null,
		'verbose'      => false,
		'version'      => 'dev',
		'pingInterval' => 2,
		'inboxPrefix'  => '_INBOX',
		'tlsKeyFile'   => null,
		'tlsCertFile'  => null,
		'tlsCaFile'    => null,
	],
	'connection'        => [
		'delay' => 1,
		'name'  => 'default',
	],
	'available_locales' => ['uz', 'ru',],
];
