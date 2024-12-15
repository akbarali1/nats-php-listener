<?php

return [
	'-1000' => [
		"message"     => "Unknown exception",
		"description" => "An unidentified exception occurred.",
	],
	'-3000' => [
		"message"     => "NATS service not found",
		"description" => "The requested NATS service could not be located.",
	],
	'-3001' => [
		"message"     => "NATS route not found",
		"description" => "The specified route does not exist in NATS routes.",
	],
	'-3002' => [
		"message"     => "Invalid parameters",
		"description" => "The provided parameters are invalid or incomplete for the NATS request.",
	],
	'-3003' => [
		"message"     => "Reflector error",
		"description" => "An error occurred while reflecting the NATS service or method.",
	],
	'-3004' => [
		"message"     => "Action data error",
		"description" => "The provided action data is invalid or failed validation.",
	],
	'-3005' => [
		"message"     => "Unknown NATS error",
		"description" => "An unidentified error occurred during the NATS operation.",
	],
	'-3006' => [
		"message"     => "NATS service method not found",
		"description" => "The specified method does not exist in the NATS service.",
	],
	'-3007' => [
		"message"     => "NATS request method not found",
		"description" => "The requested NATS method could not be found.",
	],
	'-3008' => [
		"message"     => "Service interface not implemented",
		"description" => "The NATS service does not implement the required interface.",
	],
	'-3009' => [
		"message"     => "Unauthenticated request",
		"description" => "The NATS request requires authentication, but none was provided.",
	],
	'-3010' => [
		"message"     => "Route file not found",
		"description" => "The NATS route configuration file is missing.",
	],
	'-3011' => [
		"message"     => "Response error",
		"description" => "An error occurred while processing the NATS response.",
	],
	'-3012' => [
		"message"     => "Config name not set",
		"description" => "The NATS configuration name has not been specified.",
	],
	'-3013' => [
		"message"     => "Function not supported",
		"description" => "The requested NATS function is not supported.",
	],
	'-3014' => [
		"message"     => "No routes available",
		"description" => "No routes are defined or available for NATS.",
	],
];