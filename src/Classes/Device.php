<?php


namespace Imageplus\APIVersionControl\Classes;


use Imageplus\APIVersionControl\Facades\APIVersionValidator;

class Device
{
	public $type;
	public $name;

	protected $version;
	protected $features;

	protected $minimum_version_required;

	public function __construct($type, $name)
	{
		$this->type = $type;
		$this->name = $name;
	}

	/**
	 * Sets the device

	 * @param $version
	 */
	public function setup($version){
		//Filter version to x.x.x
		$version_matches = [];
		preg_match('/^([0-9]+)\.([0-9]+)\.([0-9]+)?/', $version, $version_matches);

		if (!count($version_matches)) {
			if (is_nan((int)$version))
				abort(400, 'Invalid version format');
			
			$bits = str_split((string)$version);
			if (count($bits) > 3)
				$this->version = "{$bits[0]}.{$bits[1]}{$bits[2]}.{$bits[3]}";
			else
				$this->version = "{$bits[0]}.{$bits[1]}.{$bits[2]}";
		}
		else {
			$this->version = $version_matches[0];
		}

		$this->features = $this->compileFeatures();
	}

	/**
	 * Gets device version
	 *
	 * @return mixed
	 */
	public function getVersion(){
		return $this->version;
	}

	/**
	 * Returns if device has global access to API
	 *
	 * @return bool
	 */
	public function checkGlobalAccess(){
		if (!$this->hasMinimumVersion()) return false;
		if ($this->hasBlockedVersion()) return false;
		return true;
	}

	public function hasAccessToFeature($key){
		$feature = $this->features->where('key', $key)->first();
		if (!$feature) return null; //This still fails the access, but lets the dev know it was because device isn't setup

		$allowed_versions = $feature->allowed_versions;
		return APIVersionValidator::validateVersion($this->version, $allowed_versions);
	}

	/**
	 * Returns if device has minimum version to access API
	 *
	 * @return bool
	 */
	public function hasMinimumVersion(){
		if (!$this->getMinimumVersionRequired()) return false;
		if (!version_compare($this->version, $this->getMinimumVersionRequired(), '>=')) return false;
		
		return true;
	}

	/**
	 * Returns if device is using a blocked version
	 *
	 * @return mixed
	 */
	public function hasBlockedVersion(){
		return APIVersionValidator::isDeviceVersionBlocked($this);
	}

	/**
	 * Returns the minimum version for device
	 *
	 * @return string|null
	 */
	public function getMinimumVersionRequired(){
		if (!isset($this->minimum_version_required))
			$this->minimum_version_required = APIVersionValidator::minimumVersionRequired($this);

		return $this->minimum_version_required;
	}

	/**
	 * Compiles a list of features for that device type
	 *
	 * @return \Illuminate\Support\Collection
	 */
	protected function compileFeatures(){
		$list_of_features = config('api_vcs.features', []);
		$allowed_features = collect();

		foreach ($list_of_features as $key => $feature){
			if (isset($feature[$this->type]))
				$allowed_features->push((object)[
					'key' => $key,
					'allowed_versions' => collect(explode('|', $feature[$this->type]))
				]);
		}

		return $allowed_features;
	}
}