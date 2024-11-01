<?php
declare(strict_types=1);
/**
 * @author Snapplify
 * @package SnapplifyEcommerce\Core
 * @version 1.0.0
 */
defined('ABSPATH') || exit;
if (true === !function_exists('get_plugin_data')) {
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

class SnapplifyEcommerce
{

    /**
     * @var SnapplifyProductFeedApiController
     * @todo Typed property not allowed in PHP 7.2, type must be cast when upgrading code base to higher PHP version.
     */
    private $productFeedApiController;

    /**
     * @var SnapplifyProductFeedScheduledTaskController
     * @todo Typed property not allowed in PHP 7.2, type must be cast when upgrading code base to higher PHP version.
     */
    private $productFeedScheduledTaskController;

    /**
     * @var SnapplifyLoggingController
     * @todo Typed property not allowed in PHP 7.2, type must be cast when upgrading code base to higher PHP version.
     */
    private $loggingController;

    /**
     * @var string
     * @todo Typed property not allowed in PHP 7.2, type must be cast when upgrading code base to higher PHP version.
     */
    private $pluginPath;

    /**
     * @var string
     * @todo Typed property not allowed in PHP 7.2, type must be cast when upgrading code base to higher PHP version.
     */
    private $pluginUrl;

    /**
     * @var string
     * @todo Typed property not allowed in PHP 7.2, type must be cast when upgrading code base to higher PHP version.
     */
    private $pluginVersion;

    /**
     * SnapplifyEcommerce constructor.
     */
    public function __construct(string $fileName)
    {
        $this->setPluginPath($fileName);
        $this->setPluginUrl($fileName);
        $this->setPluginVersion($fileName);
    }

    /**
     * @return void
     */
    public function initialize(): void
    {
        $this->geProductFeedApiController()->initialize();
        $this->geProductFeedScheduledTaskController()->initialize();
    }

    /**
     * @return string
     */
    public function getPluginPath(): string
    {
        return $this->pluginPath;
    }

    /**
     * @param string $fileName
     */
    private function setPluginPath(string $fileName): void
    {
        $this->pluginPath = plugin_dir_path($fileName);
    }

    /**
     * @return string
     */
    public function getPluginUrl(): string
    {
        return $this->pluginUrl;
    }

    /**
     * @param string $fileName
     */
    private function setPluginUrl(string $fileName): void
    {
        $this->pluginUrl = plugins_url('', $fileName);
    }

    /**
     * @return string
     */
    public function getPluginVersion(): string
    {
        return $this->pluginVersion;
    }

    /**
     * @param string $fileName
     */
    private function setPluginVersion(string $fileName): void
    {
        $version = '1.0.0';
        $wpPluginData = get_plugin_data($fileName, true, false);
        if (
            (true === is_array($wpPluginData))
            && (true === !empty($wpPluginData['Version']))
        ) {
            $version = (string)$wpPluginData['Version'];
        }
        $this->pluginVersion = $version;
    }

    /**
     * @return \SnapplifyProductFeedApiController
     */
    public function geProductFeedApiController(): SnapplifyProductFeedApiController
    {
        if (true === empty($this->productFeedApiController)) {
            require_once(__DIR__ . '/SnapplifyProductFeedApiController.php');
            $this->productFeedApiController = new SnapplifyProductFeedApiController();
        }
        return $this->productFeedApiController;
    }

    /**
     * @return \SnapplifyProductFeedScheduledTaskController
     */
    public function geProductFeedScheduledTaskController(): SnapplifyProductFeedScheduledTaskController
    {
        if (true === empty($this->productFeedScheduledTaskController)) {
            require_once(__DIR__ . '/SnapplifyProductFeedScheduledTaskController.php');
            $this->productFeedScheduledTaskController = new SnapplifyProductFeedScheduledTaskController();
        }
        return $this->productFeedScheduledTaskController;
    }

    /**
     * @return \SnapplifyLoggingController
     */
    public function getLoggingController(): SnapplifyLoggingController
    {
        if (true === empty($this->loggingController)) {
            require_once(__DIR__ . '/SnapplifyLoggingController.php');
            $this->loggingController = new SnapplifyLoggingController();
        }
        return $this->loggingController;
    }

    /**
     * @return string
     */
    public function getSiteUri(): string
    {
        return esc_url(rtrim(get_home_url(0, ''), '/'));
    }
}
