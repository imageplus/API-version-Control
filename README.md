# Imageplus API Version Source Control
Simple to use version control system that allows resources to be adapted, deprecated or injected depending on the API request.
This provides a fully configurable ACL (Access control list) for your API to aid both app developers and public access
dependent on the device and features allowed / capable.

It's therefore a good tool keep your API life cycle up-to-date without sacrificing compatibility between devices. 
It's also a good way to force applications on mobile devices to update, as it can reject any request 
to a specific version, or a minimum version supplied.

This tool does not provide any kind of security in that regard, meaning the device making the request has to
provided the information itself. Don't use this as a security system but rather a compatibility / deprecation system.

## Getting Started
To install the package run
```
composer require imageplus/apivcs
```

You should also publish the config to allow you to further customize your settings.

```
php artisan vendor:publish  --tag=imageplus-api-vcs-config
```

Turn on the feature by adding `VCS_ENABLED=true` to your env.

### Request Params
```
headers: [
    ...
    Device-Type: ios,
    Device-Version: 1.32.1-rc_final
]
```

## Setup Global API Version

This package can be configured to use global API versions (as in `/api/v1/...`, `/api/v6/...`). To increase the
version just update your env to include the following key|value, where value is the latest version of your API eg: 3
```
VCS_API_VERSION=3
```
If you wish to remove a previous version, see blocking versions below.

Global API versions can only be an integer and to used them the request must include `/api/v{version_number}/{uri}`.
If version number is not specified on the url it will default to env `VCS_API_DEFAULT_VERSION`.

Note: You don't need to specify your api routes to accept versioning.

__Warning__: do not redirect within the API if you are using global versions, otherwise your request will lose the API version submitted.

## Setup devices and versions

On the published config file you will be able to configure which devices you plan to version control.
```php
'devices' => [
    'android' => 'Android',
    'ps5' => 'Playstation 5'
]
```
Once the device is added you will need to supply the minimum version for said device on the config or using the env override (see ENV override below).

```php
'minimum_version' => [
    'android' => '2.1.0',
    'ps5' => '1.0.0'
],
```

By default, a device type and device version has to be supplied to access the API. This can be changed using `VCS_ALLOW_DEVICELESS_ACCESS` env flag.

### Features
If you have a specific feature you want to limit to a number of devices or versions, you can configure your features using the following:
```php
'my_feature_key' => [
    'android' => '2.7.0',
    'ps5' => '1.0.0|!1.3.*'
],
'no_android_access' => [
    'ps5' => '1.0.0'
]
```
By using the version helper you can tag specific versions or minimum versions of specific devices. If a 
particular devices isn't set on the feature it won't have access to it.

## Usage

#### Facade

`APIVersionValidator`

This is a version comparator mainly used as a helper. It allows you to compare between given versions and can act as a ACL checker.

`APIVersionControl`

This facade will help you create conditions based on the device registered on request. As an example let's imagine
you want to provide your resource as both json or XML dependent on the device. You can use something like the follow:
```php
if (APIVersionControl::hasAccess('XML'))
    return Response::makeXML($resource);

return Response::makeJSON($resource);
```

Or add additional properties to the resource if a specific device
```php
APIVersionControl::onlyDeviceVersion('ios', '!1.2.*', function () use ($resource) {
    $resource->valid_version = true;
});
```

#### Middleware
There's three route middlewares to help you further customise your ACL. On your `api.php` file you can
utilize these middlewares to block specific routes.

__FeatureVersionMiddleware__: Only allow devices with access to the specified feature
```php
//Only devices that support notifications
Route::group([
    'name' => 'Notifications routes',
    'prefix' => 'api/notifications',
    'as' => 'notifications.',
    
    'middleware' => 'feature:notifications'
], function (){

    //All notification routes
    
});
```

__APIVersionMiddleware__: Uses the version helper to block or only allow access to a specific API version
```php
//This route only works on API version 1
Route::get('/api/user', [
    'as' => 'user.index',
    'uses' => 'UsersController@index'
])->middleware('api_version:=1');

//This route works on any API version above or equal 2
Route::get('/api/profile', [
    'as' => 'profile',
    'uses' => 'ProfileController@index'
])->middleware('api_version:2');
```

__DeviceVersionMiddleware__: Uses the version helper to block or only allow access to specific devices and versions
```php
//This route only works on ios
Route::get('/api/blob', [
    'as' => 'blob',
    'uses' => 'BlobController@index'
])->middleware('device_version:ios,2.8.0');

//This route works on any version for playstation but only after 2.9.0 for iOS
Route::get('/api/blobs', [
    'as' => 'blobs',
    'uses' => 'BlobController@all'
])->middleware([
    'device_version:ps5,any',
    'device_version:ios,2.9.0'
]);
```


## Block specific versions

You can block specific versions of devices or global API versions as well using the config `locked_versions`

```php
'locked_versions' => [
    'global' => env('VCS_GLOBAL_BLOCK_VERSION', '2'),
    'ios' => env('VCS_IOS_BLOCK_VERSION', '2.1.2, 2.1.6'),
]
```
These are seperated by `,` and you need to specify the exact version as shown above.

## ENV Override
While `env_override` is true in your config file, most of the configuration can be overriten on
your `env` file. Below is the correct syntax for the supported overrides:
```
VCS_{device_type}_MINIMUM_VERSION //Sets the minimum version for {device_type}

VCS_{device_type}_LOCKED_VERSION //Defines (and replaces) the locked versions for {device_type} Use GLOBAL for global api version
```

## Command helper

You can use the provide command to simulate a specific device access.
```
php artisan api:has-access --device=ios --device_ver=1.0.0-pre-alpha2

php artisan api:has-access --device=ps5 --device_ver=1.0.1-rcA --feature=profile

php artisan api:has-access --device=ios --device_ver=1.42.12 --api_ver=4
```