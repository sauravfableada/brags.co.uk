<?php


namespace WeDevs\DokanPro\Dependencies\Printful\Structures\Generator\Templates;


/**
 * @see \WeDevs\DokanPro\Dependencies\Printful\Structures\Placements
 */
class PlacementConflictItem
{
    /**
     * Placement id
     * @var string
     * @see \WeDevs\DokanPro\Dependencies\Printful\Structures\Placements::$types
     */
    public $placement;

    /**
     * List of placement ids that are in conflict (cannot be used at the same time)
     * @var string[]
     */
    public $conflictingPlacements;
}