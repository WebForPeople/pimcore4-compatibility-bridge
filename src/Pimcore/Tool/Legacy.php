<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Tool;

use Pimcore\Config;
use Pimcore\Tool;

class Legacy {

    /**
     * @var null
     */
    protected static $isFrontend = null;


    /**
     * @param string $type
     * @param array $options
     * @return \Zend_Http_Client
     * @throws \Exception
     * @throws \Zend_Http_Client_Exception
     */
    public static function getHttpClient($type = "Zend_Http_Client", $options = [])
    {
        $config = Config::getSystemConfig();
        $clientConfig = $config->httpclient->toArray();
        $clientConfig["adapter"] =  (isset($clientConfig["adapter"]) && !empty($clientConfig["adapter"])) ? $clientConfig["adapter"] : "Zend_Http_Client_Adapter_Socket";
        $clientConfig["maxredirects"] =  isset($options["maxredirects"]) ? $options["maxredirects"] : 2;
        $clientConfig["timeout"] =  isset($options["timeout"]) ? $options["timeout"] : 3600;
        $type = empty($type) ? "Zend_Http_Client" : $type;

        $type = "\\" . ltrim($type, "\\");

        if (Tool::classExists($type)) {
            $client = new $type(null, $clientConfig);

            // workaround/for ZF (Proxy-authorization isn't added by ZF)
            if ($clientConfig['proxy_user']) {
                $client->setHeaders('Proxy-authorization', \Zend_Http_Client::encodeAuthHeader(
                    $clientConfig['proxy_user'], $clientConfig['proxy_pass'], \Zend_Http_Client::AUTH_BASIC
                ));
            }
        } else {
            throw new \Exception("Pimcore_Tool::getHttpClient: Unable to create an instance of $type");
        }

        return $client;
    }

    /**
     * @static
     * @param \Zend_Controller_Request_Abstract $request
     * @return bool
     */
    public static function useFrontendOutputFilters(\Zend_Controller_Request_Abstract $request)
    {

        // check for module
        if (!self::isFrontend()) {
            return false;
        }

        if (self::isFrontendRequestByAdmin()) {
            return false;
        }

        // check for manually disabled ?pimcore_outputfilters_disabled=true
        if ($request->getParam("pimcore_outputfilters_disabled") && PIMCORE_DEBUG) {
            return false;
        }


        return true;
    }
    /**
     * eg. editmode, preview, version preview, always when it is a "frontend-request", but called out of the admin
     */
    public static function isFrontendRequestByAdmin()
    {
        if (array_key_exists("pimcore_editmode", $_REQUEST)
            || array_key_exists("pimcore_preview", $_REQUEST)
            || array_key_exists("pimcore_admin", $_REQUEST)
            || array_key_exists("pimcore_object_preview", $_REQUEST)
            || array_key_exists("pimcore_version", $_REQUEST)
            || (isset($_SERVER["REQUEST_URI"]) && preg_match("@^/pimcore_document_tag_renderlet@", $_SERVER["REQUEST_URI"]))) {
            return true;
        }

        return false;
    }

    /**
     * @static
     * @return bool
     */
    public static function isFrontend()
    {
        if (self::$isFrontend !== null) {
            return self::$isFrontend;
        }

        $isFrontend = true;

        if ($isFrontend && php_sapi_name() == "cli") {
            $isFrontend = false;
        }

        if ($isFrontend && \Pimcore::inAdmin()) {
            $isFrontend = false;
        }

        if ($isFrontend && isset($_SERVER["REQUEST_URI"])) {
            $excludePatterns = [
                "/^\/admin.*/",
                "/^\/install.*/",
                "/^\/plugin.*/",
                "/^\/webservice.*/"
            ];

            foreach ($excludePatterns as $pattern) {
                if (preg_match($pattern, $_SERVER["REQUEST_URI"])) {
                    $isFrontend = false;
                    break;
                }
            }
        }

        self::$isFrontend = $isFrontend;

        return $isFrontend;
    }

    /**
     * @static
     * @param \Zend_Controller_Response_Abstract $response
     * @return bool
     */
    public static function isHtmlResponse(\Zend_Controller_Response_Abstract $response)
    {
        // check if response is html
        $headers = $response->getHeaders();
        foreach ($headers as $header) {
            if ($header["name"] == "Content-Type") {
                if (strpos($header["value"], "html") === false) {
                    return false;
                }
            }
        }

        return true;
    }
}
