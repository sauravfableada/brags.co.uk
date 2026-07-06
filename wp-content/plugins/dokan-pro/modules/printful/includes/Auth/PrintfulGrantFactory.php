<?php

namespace WeDevs\DokanPro\Modules\Printful\Auth;

use WeDevs\DokanPro\Dependencies\League\OAuth2\Client\Grant\GrantFactory;

class PrintfulGrantFactory extends GrantFactory {

    /**
     * Registers a default grant singleton by name.
     *
     * @param  string $name
     * @return self
     */
    protected function registerDefaultGrant($name)
    {
        // PascalCase the grant. E.g: 'authorization_code' becomes 'AuthorizationCode'
        $class = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $name)));
        $class = 'WeDevs\\DokanPro\\Dependencies\\League\\OAuth2\\Client\\Grant\\' . $class;

        $this->checkGrant($class);

        return $this->setGrant($name, new $class);
    }
}
