<?php

namespace WeDevs\DokanPro\Modules\Printful\Integrations;

use WeDevs\DokanPro\Dependencies\Printful\Exceptions\PrintfulApiException;
use WeDevs\DokanPro\Dependencies\Printful\Exceptions\PrintfulException;
use WeDevs\DokanPro\Dependencies\Printful\PrintfulApiClient;
use WeDevs\DokanPro\Dependencies\Printful\PrintfulProducts;


/**
 * Class ProductsClient.
 *
 * @phpcs:disable WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase,WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase,WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
 *
 * @since 3.13.0
 */
class ProductsClient extends PrintfulProducts {

    protected $printfulClient;

    /**
     * ProductsClient constructor.
     *
     * @since 3.13.0
     *
     * @param PrintfulApiClient $printfulClient
     */
    public function __construct( PrintfulApiClient $printfulClient ) {
        $this->printfulClient = $printfulClient;
        parent::__construct( $printfulClient );
    }

    /**
     * Preforms GET SyncProduct request.
     *
     * @since 3.13.0
     *
     * @param int $id Printful Product ID
     *
     * @return SyncProductRequestResponse
     * @throws PrintfulApiException|PrintfulException
     */
    public function getProduct( $id ): SyncProductRequestResponse {
        $response = $this->printfulClient->get( PrintfulProducts::ENDPOINT_PRODUCTS . '/' . $id );

        return SyncProductRequestResponse::fromArray( $response );
    }
}
