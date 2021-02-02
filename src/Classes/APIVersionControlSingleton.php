<?php

namespace Imageplus\APIVersionControl\Classes;

use Imageplus\APIVersionControl\Facades\APIVersionValidator;

class APIVersionControlSingleton
{
	protected $list_of_devices;
	protected $device;
	protected $using_version;

	/**
	 *
	 */
	public function __construct(){
		$this->list_of_devices = $this->listOfDevices();
	}

	public function onlyAPIVersion($versions, \Closure $closure){
		if (is_string($versions) || is_numeric($versions)) $versions = collect($versions);

		if (APIVersionValidator::validateVersion($this->using_version, $versions))
			$closure();
	}

	public function onlyDeviceVersion($device_type, $versions, \Closure $closure){
		if ($device_type !== $this->device->type) return;

		if (is_string($versions)) $versions = collect($versions);

		if (APIVersionValidator::validateVersion($this->device->getVersion(), $versions))
			$closure();
	}

	public function onlyFeature($feature, \Closure $closure){
		if ($this->device->hasAccessToFeature($feature))
			$closure();
	}


	/**
	 * Registers the device used in the request
	 *
	 * @param $device_type
	 * @param $device_version
	 */
	public function registerRequestDevice($device_type, $device_version){
		if (!$device_type || !$device_version){
			//We can allow public access
			if (config('api_vcs.allow_deviceless_access', false))
				return false;

			abort(400, 'Please supply your device type and your version by including it on the headers Device-Type and Device-Version');
		}

		if (!$this->list_of_devices->has($device_type))
			abort(400, 'Your device is not registered');

		$this->device = $this->list_of_devices->get($device_type);
		$this->device->setup($device_version);
	}

	/**
	 * Registers the API global version
	 *
	 * @param $version
	 */
	public function registerRequestGlobalVersion($version){
		if (!$version) $version = config('api_vcs.api_default_version', 1);
		if ($version > (int)config('api_vcs.api_version')) abort(404);

		if (APIVersionValidator::isGlobalVersionBlocked($version))
			abort(426, 'API Version is not allowed');

		$this->using_version = $version;
	}


	/**
	 * Get API global version
	 *
	 * @return integer|null
	 */
	public function getVersion(){
		return $this->using_version;
	}

	/**
	 * Returns the device in request.
	 *
	 * @return Device
	 */
	public function getDevice(){
		return $this->device;
	}


	/**
	 * Checks if registered device has global access to API, unless feature is provided
	 *
	 * @param string $feature_key
	 * @return false
	 */
	public function hasAccess($feature_key = null){
		if (!$this->device) return config('api_vcs.allow_deviceless_access', false);
		if ($feature_key){
			if (!$this->device->checkGlobalAccess()) return false;

			return $this->device->hasAccessToFeature($feature_key);
		}

		return $this->device->checkGlobalAccess();
	}

	public function getAccessMessage($feature_key = null){
		if (!$this->device)
			return 'Device-Type and Device-Version headers have to be supplied.';
		if (!$this->device->getMinimumVersionRequired())
			return 'Your device has no access to the api. Please assure the device was correctly registered on the server.';
		if (!$this->device->hasMinimumVersion())
			return "Your device version needs to be higher or equal than {$this->device->getMinimumVersionRequired()}. Please update your device.";
		if ($this->device->hasBlockedVersion())
			return "Your device version is blocked on the API. Please update your device.";
		if ($feature_key && !$this->device->hasAccessToFeature($feature_key))
			return "Your device hasn't got access to this feature.";

		return null;
	}


	/**
	 * Returns a collection of the defined devices in config
	 *
	 * @return \Illuminate\Support\Collection
	 */
	protected function listOfDevices(){
		$devices = config('api_vcs.devices');
		if (!$devices) abort(503, 'Missing the list of devices. Please make sure api_vcs config exists');

		$devices = collect($devices)->map(function ($name, $key) {
			return new Device($key, $name);
		});

		return $devices;
	}
}