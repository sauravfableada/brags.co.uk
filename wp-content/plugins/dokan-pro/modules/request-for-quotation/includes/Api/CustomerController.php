<?php
namespace WeDevs\DokanPro\Modules\RequestForQuotation\Api;

use WC_REST_Customers_V2_Controller;

/**
 * Request A Quote Controller Class
 *
 * @since 3.6.0
 */
class CustomerController extends WC_REST_Customers_V2_Controller {

    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'dokan/v1/request-for-quote';

    /**
     * Route name
     *
     * @var string
     */
    protected $base = 'customers';
}
