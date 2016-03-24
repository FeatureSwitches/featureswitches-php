# Featureswitches

A PHP client library for interacting with [FeatureSwitches.com](https://featureswitches.com).  This library is under active development and is likely to change frequently.  Bug reports and pull requests are welcome.

## Installation

Install with [Composer](https://getcomposer.org/)

```bash
php composer.phar require featureswitches/featureswitches-php
```

## Usage

```php
require 'vendor/autoload.php';

// Create a new FSClient with your customer and environment API key's
$featureswitches = new FeatureSwitches\FSClient('customer_api_key', 'environment_api_key', array('options'));

// Ensure that the API credentials are valid
$result = $featureswitches->authenticate();  # result will be true/false to indicate success

// Sync feature state
$featureswitches->sync();

// Add a user
$result = $featureswitches->addUser('user_identifier', 'optional_customer_identifier', 'optional_name', 'optional_email');

// Check if a feature is enabled
$result = $featureswitches->isEnabled('feature_key', 'optional_user_identifier', default(true/false, default=false));

if ($result == true) {
    // Feature enabled, do something
} else {
    // Feature disabled, do something else
}
```

### Configuration Options
The library locally caches responses from the FeatureSwitches API to cut down on response time and repeat requests. The default cache timeout is 300 seconds (5 minutes).  You can adjust the cache timeout by providing the 'cache_timeout' config option when initializing the library.

```php
array(
    'cache_timeout' => SECONDS, // optional, defaults to 300 seconds
)
```
## Contributing

Bug reports and pull requests are welcome on GitHub at https://github.com/featureswitches/featureswitches-php.


## License

The library is available as open source under the terms of the [MIT License](http://opensource.org/licenses/MIT).

