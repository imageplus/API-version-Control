<?php


namespace Imageplus\APIVersionControl\Facades;


use Illuminate\Support\Facades\Facade;

/**
 * Class Sns
 * @package Imageplus\Sns\Facades
 * @author Harry Hindson
 */
class APIVersionValidator extends Facade
{
	public static function getFacadeAccessor()
	{
		//version control system as a singleton instance
		return 'api_vcs_validator';
	}
}
