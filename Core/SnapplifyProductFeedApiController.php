<?php
declare(strict_types=1);
/**
 * @author Snapplify
 * @package SnapplifyEcommerce\Core
 * @version 1.0.0
 */
defined('ABSPATH') || exit;

use JsonSchema\Validator;

class SnapplifyProductFeedApiController
{

    /**
     * @var string
     */
    private const API_ENDPOINT = 'wc/snapplify/v1';

    /**
     * @var string
     */
    private const API_ENDPOINT_PRODUCT_FEED = 'push';

    /**
     * @var int
     * @todo Typed property not allowed in PHP 7.2, type must be cast when upgrading code base to higher PHP version.
     */
    private $apiResponseCode = 200;
    /**
     * @var string
     * @todo Typed property not allowed in PHP 7.2, type must be cast when upgrading code base to higher PHP version.
     */
    private $apiResponseMessage = '';
    /**
     * @var string
     * @todo Typed property not allowed in PHP 7.2, type must be cast when upgrading code base to higher PHP version.
     */
    private $apiResponseStatus = 'success';

    /**
     * @return void
     */
    public function initialize(): void
    {
        add_action('rest_api_init', [&$this, 'registerProductFeedApiRestRoute'], 10);
    }

    public function getApiProductFeedEndpoint(): string
    {
        return self::API_ENDPOINT . '/' . self::API_ENDPOINT_PRODUCT_FEED;
    }

    /**
     * @return int
     */
    private function getApiResponseCode(): int
    {
        return $this->apiResponseCode;
    }

    /**
     * @param int $apiResponseCode
     * @return void
     * @noinspection PhpSameParameterValueInspection
     */
    private function setApiResponseCode(int $apiResponseCode): void
    {
        $this->apiResponseCode = $apiResponseCode;
    }

    /**
     * @return string
     */
    private function getApiResponseMessage(): string
    {
        return $this->apiResponseMessage;
    }

    /**
     * @param string $apiResponseMessage
     * @return void
     */
    private function setApiResponseMessage(string $apiResponseMessage): void
    {
        $this->apiResponseMessage = $apiResponseMessage;
    }

    /**
     * @return string
     */
    private function getApiResponseStatus(): string
    {
        return $this->apiResponseStatus;
    }

    /**
     * @param string $apiResponseStatus
     * @return void
     * @noinspection PhpSameParameterValueInspection
     */
    private function setApiResponseStatus(string $apiResponseStatus): void
    {
        $this->apiResponseStatus = $apiResponseStatus;
    }

    /**
     * @param string $data
     * @return array
     */
    private function parseFeedData(string $data): array
    {
        $data = json_decode($data);
        $description = (string)$data->description;
        if (3000 < strlen($description)) {
            $description = substr($description, 0, 3000);
        }
        $data->description = $description;
        return [json_encode($data)];
    }

    /**
     * @return void
     */
    private function updateLastProcessedDateTime(): void
    {
        if (200 === $this->getApiResponseCode()) {
            $dateTime = current_datetime();
            $dateTime = $dateTime->format('Y-m-d H:i:s.u');
            update_option('wcsnapplify_last_incoming_push_time', $dateTime);
        }
    }

    /**
     * @param object $json
     * @return \JsonSchema\Validator
     */
    private function initializeJsonValidator(object $json): Validator
    {
        global $snapplifyEcommerce;
        $schemaFilename = realpath($snapplifyEcommerce->getPluginPath() . 'product-schema.json');
        $validator = new Validator();
        $validator->validate($json, (object)['$ref' => 'file://' . $schemaFilename]);
        return $validator;
    }

    /**
     * @param $request
     * @return bool
     * @throws \Exception
     */
    private function isValidApiRequest($request): bool
    {
        global $snapplifyEcommerce;
        $loggingController = $snapplifyEcommerce->getLoggingController();
        try {
            $unparsedRequest = $request->get_body();
            if (false === is_array(json_decode($unparsedRequest, true))) {
                throw new \Exception(sprintf('Product request is not valid JSON. Request Payload: %s', $unparsedRequest));
            } else {
                $parsedRequest = json_decode($unparsedRequest);
                $validator = $this->initializeJsonValidator($parsedRequest);
                $isValidatorValid = $validator->isValid();
                $isValid = true;
                if (false === $isValidatorValid) {
                    $errorMessage = '';
                    foreach ($validator->getErrors() as $validationError) {
                        $errorMessage .= sprintf('[%s] %s,' . PHP_EOL, $validationError['property'], $validationError['message']);
                    }
                    $loggingController->addErrorLog($errorMessage, 'product-api');
                    if ('yes' === get_option('wcsnapplify_strict_validation')) {
                        $isValid = false;
                        $this->setApiResponseCode(400);
                        $this->setApiResponseMessage($errorMessage);
                        $this->setApiResponseStatus('failed');
                    }
                }
            }
        } catch (\Throwable $error) {
            $isValid = false;
            $errorMessage = $error->getMessage();
            $loggingController->addErrorLog($errorMessage, 'product-api');
            $this->setApiResponseCode(400);
            $this->setApiResponseMessage($errorMessage);
            $this->setApiResponseStatus('failed');
        }
        return $isValid;
    }

    /**
     * @param $request
     * @return array
     * @throws \Exception
     */
    public function createProductFeedScheduledTask($request): array
    {
        if (true === $this->isValidApiRequest($request)) {
            global $snapplifyEcommerce;
            $loggingController = $snapplifyEcommerce->getLoggingController();
            $data = $this->parseFeedData($request->get_body());
            try {
                $snapplifyEcommerce->geProductFeedScheduledTaskController()->addScheduledTask('wc_snap_process_product_exec', $data);
            } catch (\Throwable $error) {
                $loggingController->addErrorLog(sprintf('Error consuming and queueing product API request: Snap Id: %s, Snap Identifier: %s, Message: %s', $data['id'], $data['identifier'], $error->getMessage()), 'product-api');
                $loggingController->addDebugLog(sprintf('Product payload: %s', json_encode($data)), 'product-api');
                $this->setApiResponseCode(400);
                $this->setApiResponseMessage('Failed to queue product request.');
                $this->setApiResponseStatus('failed');
            }
        }
        $this->updateLastProcessedDateTime();
        return [
            'code' => $this->getApiResponseCode(),
            'message' => $this->getApiResponseMessage(),
            'status' => $this->getApiResponseStatus(),
        ];
    }

    /**
     * @param $request
     * @return bool
     * @throws \Exception
     */
    public function isValidateBearerTokenPermission($request): bool
    {
        $isValidateAuth = false;
        global $snapplifyEcommerce;
        $loggingController = $snapplifyEcommerce->getLoggingController();
        $authRequest = $request->get_header('authorization');
        $authChallenge = 'Bearer ' . get_option('wcsnapplify_token');
        $authMessage = 'request has wrong token. Not authorised.';
        if ($authRequest === $authChallenge) {
            $isValidateAuth = true;
            $authMessage = 'request is authorised.';
        }
        $loggingController->addSystemLog(sprintf('Attempted Push: %s', $authMessage), 'auth');
        return $isValidateAuth;
    }

    /**
     * @return void
     */
    public function registerProductFeedApiRestRoute(): void
    {
        register_rest_route(
            self::API_ENDPOINT,
            self::API_ENDPOINT_PRODUCT_FEED,
            [
                'methods' => 'POST',
                'callback' => [&$this, 'createProductFeedScheduledTask'],
                'permission_callback' => [&$this, 'isValidateBearerTokenPermission'],
            ]
        );
    }
}
