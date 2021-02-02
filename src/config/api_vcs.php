<?php

return [

	'enabled' => env('VCS_ENABLED', false),

	//Latest version of the API
	'api_version' => env('VCS_API_VERSION', 1),

	//Default fallback version of the API
	'api_default_version' => env('VCS_API_DEFAULT_VERSION', 1),

	//Allow access to the API even if device is not submitted
	'allow_deviceless_access' => env('VCS_ALLOW_DEVICELESS_ACCESS', false),

	//ENV override will take priority on what is on env other than config.
	//This way you don't need to setup the => env('{key}', '{value}'), you can just specify the
	//expected value and if need be - override with .env by using the correct scheme
	'env_override' => true,

	'devices' => [
		//List of devices that can access our api.

		'ios' => 'iOS',
		'android' => 'Android',
		'web' => 'Web API'
	],

	'minimum_version' => [
		//Without the minimum version required, a any api call will be denied

		'ios' => 		env('VCS_IOS_MINIMUM_VERSION', '0.0.0'),
		'android' => 	env('VCS_ANDROID_MINIMUM_VERSION', '0.0.0'),
		'web' => 		env('VCS_WEB_MINIMUM_VERSION', '0.0.0')
	],

	'features' => [
		//You can set a list of features here and limit to specific versions of specific devices
		//You can even deny the device all together for that feature if not included on the list

		// Here's the semver control characters
		// 		0.1.0	- Minimum version required
		//		!0.5.0	- Exact version
		//		!0.5.*	- Exact minor
		//		<1.0.0  - Less than version
		//		any		- Any version is allowed

		//This can be combined with | operator ie: 0.4.0|!0.1.* (minimum 0.4.0 but also allow access to 0.1.*

		/*
			'profile' => [
				'ios' =>		env('VCS_IOS_PROFILE_VERSION', '0.4.0|!0.3.4'),
				'android' =>	env('VCS_ANDROID_PROFILE_VERSION', 'any'),
				'web' =>		env('VCS_WEB_PROFILE_VERSION', '1.1.0|!0.5.*|!0.6.*|<2.0.0')
			]
		*/
	],

	//Lock specific versions (exact version) from a device
	'locked_versions' => [

		//You can also block the main api
		//'global' => env('VCS_GLOBAL_BLOCK_VERSION', '2'),
		//'ios' => env('VCS_IOS_BLOCK_VERSION', '1.2.0, 1.2.1'),
		//'android' => '' //this would still be overridden by VCS_ANDROID_BLOCK_VERSION if env_override is enabled

	]
];