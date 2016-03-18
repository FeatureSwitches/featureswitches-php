<?php
namespace FeatureSwitches;

use phpFastCache\CacheManager;
use GuzzleHttp\Client;
use \GuzzleHttp\Exception\ConnectException;
use \GuzzleHttp\Exception\ClientException;
use \GuzzleHttp\Exception\ServerException;

/**
 * API client for FeatureSwitches.com
 */
class FSClient {
    const VERSION = '0.8.0';

    protected $_customerKey;
    protected $_environmentKey;
    protected $_auth;
    protected $_api;
    protected $_cacheTimeout;
    protected $_cache;
    protected $_httpCLient;

    public function __construct($customerKey, $environmentKey, $options = array()) {
        $this->_customerKey = $customerKey;
        $this->_environmentKey = $environmentKey;

        $this->_auth = "{$customerKey}:{$environmentKey}";

        $this->_api = 'https://api.featureswitches.com/v1/';
        if (isset($options['api'])) {
            $this->_api = $options['api'];
        } 

        $this->_cacheTimeout = 300;
        if (isset($options['cache_timeout'])) {
            $this->_cacheTimeout = $options['cache_timeout'];
        }

        CacheManager::setup(array(
            "path" => sys_get_temp_dir(),
        ));

        CacheManager::CachingMethod("phpfastcache");

        $this->_cache = CacheManager::Files();

        $this->_httpClient = new Client(array(
            'base_uri' => $this->_api,
        ));
    }

    public function authenticate() {
        $endpoint = 'authenticate';
        $result = $this->_apiRequest($endpoint);

        return $result['success'];
    }

    public function isEnabled($featureKey, $userIdentifier = '', $default=false) {
        $feature = $this->_cache->get($featureKey);

        if (is_null($feature)) {
            $feature = $this->_getFeature($featureKey);

            if (is_null($feature)) {
                return $default;
            }
        }

        return $this->_enabledForUser($feature, $userIdentifier);
    }

    public function addUser($userIdentifier, $customerIdentifier = '', $name = '', $email = '') {
        $endpoint = 'user/add';
        $params = array(
            'user_identifier' => $userIdentifier,
            'customer_identifier' => $customerIdentifier,
            'name' => $name,
            'email' => $email
        );

        $result = $this->_apiRequest($endpoint, $params, 'POST');

        if ($result['success']) {
            return true;
        }

        return false;
    }

    protected function _getFeature($featureKey) {
        $endpoint = 'feature';
        $params = array('feature_key' => $featureKey);

        $result = $this->_apiRequest($endpoint, $params);

        if ($result['success'] == true) {
            $feature = $result['data']['feature'];
            $this->_cache->set($featureKey, $feature, $this->_cacheTimeout);

            return $feature;
        } else {
            return null;
        }
    }

    protected function _enabledForUser($feature, $userIdentifier) {
        if ($feature['enabled'] && isset($userIdentifier)) {
            if (count($feature['include_users']) > 0) {
                if (in_array($userIdentifier, $feature['include_users'])) {
                    return true;
                } else {
                    return false;
                }
            } else if (count($feature['exclude_users']) > 0) {
                if (in_array($userIdentifier, $feature['exclude_users'])) {
                    return false;
                } else { 
                    return true;
                }
            }
        } else if (!isset($userIdentifier) && (count($feature['include_users']) > 0 || count($feature['exclude_users']) > 0)) {
            return false;
        }

        return $feature['enabled'];
    }

    protected function _apiRequest($endpoint, $params = array(), $method = 'GET') {
        $result = array (
            'success' => false,
            'message' => '',
            'statusCode' => -1,
            'data' => null
        );

        if ($method == 'GET') {
            $paramType = 'query';
        } else {
            $paramType = 'form_params';
        }

        try {
            $response = $this->_httpClient->request(
                $method, 
                $endpoint, 
                array(
                    $paramType => $params,
                    'headers' => array(
                        'Authorization' => $this->_auth,
                        'User-Agent' => 'FeatureSwitches-PHP/' . $this->VERSION
                    )
                )
            );

            try {
                $data = json_decode($response->getBody(), true);
                if ($response->getStatusCode() == 200) {
                    $result['success'] = true;
                    $result['statusCode'] = 200;
                    $result['data'] = $data;
                } else {
                    $result['statusCode'] = $response->getStatusCode();
                    $result['message'] = $data['message'];
                }
            } catch (Exception $e) { 
                $result['message'] = 'Error communicating with FeatureSwitches';
            }
        } catch (ConnectionException $e) {
            $result['message'] = 'Error communicating with FeatureSwitches';
        } catch (ServerException $e) {
            $result['message'] = 'Error communicating with FeatureSwitches';
        } catch (ClientException $e) {
            $result['statusCode'] = $e->getResponse()->getStatusCode();
            $result['message'] = $e->getResponse()->getBody();
        }

        return $result;
    }
}
