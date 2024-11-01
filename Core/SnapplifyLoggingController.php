<?php
declare(strict_types=1);
/**
 * @author Snapplify
 * @package SnapplifyEcommerce\Core
 * @version 1.0.0
 */
defined('ABSPATH') || exit;

class SnapplifyLoggingController
{
    /**
     * @var \WC_Logger_Interface
     * @todo Typed property not allowed in PHP 7.2, type must be cast when upgrading code base to higher PHP version.
     */
    private $logger;

    /**
     * @var array
     * @todo Typed property not allowed in PHP 7.2, type must be cast when upgrading code base to higher PHP version.
     */
    private $logContextSources = [
        "auth" => "Snapplify eCommerce Auth",
        "product-api" => "Snapplify eCommerce Product API",
        "product-scheduled-task" => "Snapplify eCommerce Product Scheduled Task",
        "order-fulfilment" => "Snapplify eCommerce Order Fulfilment",
        "voucher-fulfilment" => "Snapplify eCommerce Voucher Fulfilment",
    ];

    /**
     * @return \WC_Logger_Interface
     */
    private function getLogger(): WC_Logger_Interface
    {
        if (true === empty($this->logger)) {
            $this->logger = wc_get_logger();
        }
        return $this->logger;
    }

    /**
     * @return array|string[]
     */
    private function getLogContextSources(): array
    {
        return $this->logContextSources;
    }

    /**
     * @return bool
     */
    private function isDebugLoggingEnabled(): bool
    {
        $isDebugLoggingEnabled = false;
        if ('yes' === get_option('wcsnapplify_debug_logging')) {
            $isDebugLoggingEnabled = true;
        }
        return $isDebugLoggingEnabled;
    }

    /**
     * @param string $contextSourceIdentifier
     * @return string
     * @throws \Exception
     */
    private function getContextSourceByIdentifier(string $contextSourceIdentifier): string
    {
        $logContextSources = $this->getLogContextSources();
        if (false === array_key_exists($contextSourceIdentifier, $logContextSources)) {
            throw new \Exception(sprintf('%s is not a valid context source identifier.', $contextSourceIdentifier));
        }
        return $logContextSources[$contextSourceIdentifier];
    }

    /**
     * @param string $contextSourceIdentifier
     * @return array
     * @throws \Exception
     */
    private function getContextByIdentifier(string $contextSourceIdentifier): array
    {
        return  ['source' => $this->getContextSourceByIdentifier($contextSourceIdentifier)];
    }

    /**
     * @param string $logMessage
     * @param string $contextSourceIdentifier
     * @return void
     * @throws \Exception
     */
    public function addSystemLog(string $logMessage, string $contextSourceIdentifier): void
    {
        $logger = $this->getLogger();
        $logger->info($logMessage, $this->getContextByIdentifier($contextSourceIdentifier));
    }

    /**
     * @param string $logMessage
     * @param string $contextSourceIdentifier
     * @return void
     * @throws \Exception
     */
    public function addDebugLog(string $logMessage, string $contextSourceIdentifier): void
    {
        if (true === $this->isDebugLoggingEnabled()) {
            $logger = $this->getLogger();
            $logger->debug($logMessage, $this->getContextByIdentifier($contextSourceIdentifier));
        }
    }

    /**
     * @param string $logMessage
     * @param string $contextSourceIdentifier
     * @return void
     * @throws \Exception
     */
    public function addErrorLog(string $logMessage, string $contextSourceIdentifier): void
    {
        $logger = $this->getLogger();
        $logger->error($logMessage, $this->getContextByIdentifier($contextSourceIdentifier));
    }
}
