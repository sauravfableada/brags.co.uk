<?php

use WeDevs\DokanPro\Modules\ShipStation\SafeDomDocument;

/**
 * Drop in replacement for DOMDocument that is secure against XML eXternal Entity (XXE) Injection.
 * 
 * @deprecated 5.0.0 Use \weDevs\DokanPro\Modules\ShipStation\SafeDomDocument instead.
 */

class Dokan_Safe_DOMDocument extends SafeDomDocument {} 