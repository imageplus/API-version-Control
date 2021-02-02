<?php


namespace Imageplus\APIVersionControl\Classes;


class APIVersionControlValidator
{
	protected $env_override;

	public function __construct()
	{
		$this->env_override = config('api_vcs.env_override', true);
	}

	/**
	 * Checks whether the main version is blocked
	 *
	 * @param $version
	 * @return bool
	 */
	public function isGlobalVersionBlocked($version){
		return array_search($version, $this->getBlockedVersions('global')) !== false;
	}

	/**
	 * Checks if device has version blocked
	 *
	 * @param $device
	 * @param $version
	 * @return bool
	 */
	public function isDeviceVersionBlocked($device, $version = null){
		$device_object = $this->getDeviceKeyAndVersion($device, $version);

		return array_search($device_object['version'], $this->getBlockedVersions($device_object['key'])) !== false;
	}

	/**
	 * Gets minimum version required for device
	 *
	 * @param $device
	 * @return string|null
	 */
	public function minimumVersionRequired($device){
		$key = $this->getDeviceKeyAndVersion($device, null)['key'];

		$config = config("api_vcs.minimum_version.{$key}", null);

		if (!$this->env_override) return $config;

		$key = strtoupper($key);

		return env("VCS_{$key}_MINIMUM_VERSION", $config);
	}

	/**
	 * @param $version
	 * @param $list_of_versions
	 * @return bool|int
	 */
	public function validateVersion($version, $list_of_versions){
		$allow = false;

		foreach ($list_of_versions as $compare_version){
			if ($compare_version === 'any'){
				$allow = true;
			}
			else if (substr($compare_version, 0, 1) === '='){
				if ($this->compareExact($version, substr($compare_version, 1)))
					return true;
			}
			else if (substr($compare_version, 0, 1) === '!'){
				if ($this->compareExact($version, substr($compare_version, 1)))
					return false;
			}
			else if (substr($compare_version, 0, 1) === '<'){
				$allow = $this->compareLess($version, substr($compare_version, 1));
			}
			else {
				$allow = $this->compareMinimum($version, $compare_version);
			}
		}

		return $allow;
	}


	//Private methods

	protected function compareExact($version, $against){
		if (strrpos($against, '*')){
			$against = substr($against, 0, strrpos($against, '*')) . '0';
			$version = substr($version, 0, strripos($version, '.') + 1) . '0';
			return version_compare($version, $against, '=');
		}

		return version_compare($version, $against, '=');
	}

	protected function compareLess($version, $against){
		return version_compare($version, $against, '<');
	}

	protected function compareMinimum($version, $against){
		return version_compare($version, $against, '>=');
	}

	/**
	 * Compiles a simple object with the device key and device version
	 *
	 * @param $device
	 * @param $version
	 * @return array
	 */
	protected function getDeviceKeyAndVersion($device, $version){
		$key = null;

		if ($device instanceof Device) {
			$key = $device->type;
			if (!$version)
				$version = $device->getVersion();
		}
		else {
			$key = $device;
		}

		return [
			'key' => strtolower($key),
			'version' => $version
		];
	}

	/**
	 * Get's blocked versions for key
	 *
	 * @param $key
	 * @return array
	 */
	protected function getBlockedVersions($key){
		$key = strtolower($key);

		$config = array_map('trim',
			explode(',', config("api_vcs.locked_versions.{$key}", ''))
		);

		if (!$this->env_override) return $config;

		$key = strtoupper($key);

		if (env("VCS_{$key}_LOCKED_VERSION"))
			$config = array_map('trim', explode(',', env("VCS_{$key}_LOCKED_VERSION")));

		return $config;
	}
}
