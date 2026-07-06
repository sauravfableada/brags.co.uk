<?php

namespace WeDevs\DokanPro\Modules\Printful\Auth;

use WeDevs\DokanPro\Dependencies\League\OAuth2\Client\Provider\GenericProvider;
use WeDevs\DokanPro\Dependencies\League\OAuth2\Client\Provider\ResourceOwnerInterface;
use WeDevs\DokanPro\Dependencies\League\OAuth2\Client\Token\AccessToken;

/**
 * Printdul Auth Provider.
 *
 * @since 3.13.0
 */
class PrintfulAuthProvider extends GenericProvider {

    /**
     * Constructor.
     *
     * @param array $options Options.
     * @param array $collaborators Collaborators.
     */
    public function __construct( array $options = [], array $collaborators = [] ) {
        if ( empty( $collaborators['grantFactory'] ) ) {
            $collaborators['grantFactory'] = new PrintfulGrantFactory();
        }
        parent::__construct( $options, $collaborators );
    }

    /**
     * Prepare access token response.
     *
     * @since 3.13.0
     *
     * @param array $result Token Response result.
     *
     * @return array
     */
    protected function prepareAccessTokenResponse( array $result ): array {
        $result = parent::prepareAccessTokenResponse( $result );

        if ( isset( $result['expires_at'] ) ) {
            $result['expires'] = absint( $result['expires_at'] );
        }

        if ( empty( $result['resource_owner_id'] ) ) {
            $result['resource_owner_id'] = $this->getResourceOwner( new AccessToken( $result ) )->getId();
        }

        return $result;
    }

    /**
     * Generates a resource owner object from a successful resource owner
     * details request.
     *
     * @since 3.13.0
     *
     * @param  array $response Resource owner response.
     * @param  AccessToken $token Access Token.
     *
     * @return ResourceOwnerInterface
     */
    protected function createResourceOwner( array $response, AccessToken $token ): ResourceOwnerInterface {
        if ( isset( $response['result'][0] ) ) {
            $response = $response['result'][0];
        }

        return parent::createResourceOwner( $response, $token );
    }
}
