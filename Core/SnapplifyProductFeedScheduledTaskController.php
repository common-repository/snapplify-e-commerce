<?php
declare(strict_types=1);
/**
 * @author Snapplify
 * @package SnapplifyEcommerce\Core
 * @version 1.0.0
 */
defined('ABSPATH') || exit;

use League\Pipeline\Pipeline;
use Snapplify\PipelineStages\CreateProduct;

class SnapplifyProductFeedScheduledTaskController
{

    /**
     * @return void
     */
    public function initialize(): void
    {
        add_action('wc_snap_process_product_exec', [&$this, 'processProductFeedScheduledTask'], 10);
    }

    /**
     * @param string $data
     * @return array
     */
    private function parseFeedData(string $data): array
    {
        if (null === json_decode($data)) {
            $data = base64_decode($data);
        }
        $data = json_decode($data, true);
        if (false === is_array($data)) {
            $data = [];
        }
        return $data;
    }

    /**
     * @return void
     */
    private function updateLastProcessedDateTime(): void
    {
        $dateTime = current_datetime();
        $dateTime = $dateTime->format('Y-m-d H:i:s.u');
        update_option('wcsnapplify_last_processed_time', $dateTime);
    }

    /**
     * @param array $data
     * @return string
     */
    private function getProductIdFromData(array $data): string
    {
        $identifier = '';
        $datum = $this->parseFeedData($data[0]);
        if (true === array_key_exists('id', $datum)) {
            $identifier = (string)$datum['id'];
        }
        return $identifier;
    }

    /**
     * @param array $data
     * @return int
     */
    private function getScheduledTaskPriorityFromData(array $data): int
    {
        $priority = 10;
        $datum = $this->parseFeedData($data[0]);
        if (
            (true === array_key_exists('availability', $datum))
            && ('UNAVAILABLE' === $datum['availability'])
        ) {
            $priority = 1;
        }
        return $priority;
    }

    /**
     * @param string $hook
     * @param array $data
     * @return void
     * @todo Implement arguments data to include ID, version items in array, and the a data item containing the actual JSON.
     */
    public function addScheduledTask(string $hook, array $data): void
    {
        as_enqueue_async_action($hook, $data, 'Snapplify ECommerce', false, $this->getScheduledTaskPriorityFromData($data));
    }

    /**
     * @param string $data
     * @return void
     * @throws \Exception
     */
    public function processProductFeedScheduledTask(string $data): void
    {
        $request = $this->parseFeedData($data);
        global $snapplifyEcommerce;
        $loggingController = $snapplifyEcommerce->getLoggingController();
        wp_defer_term_counting(true);
        wp_defer_comment_counting(true);
        try {
            $pipeline = (new Pipeline())
                ->pipe(new CreateProduct());

            $json = $pipeline->process($request);
        } catch (\Throwable $th) {
            $loggingController->addErrorLog($th->getMessage(), 'product-scheduled-task');
            wp_die(esc_html($th->getMessage()));
        }
        $this->updateLastProcessedDateTime();
        wp_defer_term_counting(false);
        wp_defer_comment_counting(false);
    }
}
