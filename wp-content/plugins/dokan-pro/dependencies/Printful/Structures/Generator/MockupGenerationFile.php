<?php


namespace WeDevs\DokanPro\Dependencies\Printful\Structures\Generator;

/**
 * Class used when generating mockups
 */
class MockupGenerationFile
{
    /**
     * Placement identifier ( constant value from @see \WeDevs\DokanPro\Dependencies\Printful\Structures\Placements )
     * @var string
     */
    public $placement;

    /**
     * @var string
     */
    public $imageUrl;

    /**
     * Optional positions for generation
     * @var MockupPositionItem
     */
    public $position;
}