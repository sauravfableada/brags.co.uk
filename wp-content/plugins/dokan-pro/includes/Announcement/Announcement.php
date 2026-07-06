<?php

namespace WeDevs\DokanPro\Announcement;

use WeDevs\Dokan\Cache;
use WeDevs\Dokan\Traits\ChainableContainer;
use WeDevs\DokanPro\Announcement\Admin\Announcements;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 *  Dokan Announcement class for Admin
 *
 *  Announcement for seller
 *
 * @since  2.1
 *
 * @author weDevs <info@wedevs.com>
 *
 * @property Manager           $manager
 * @property BackgroundProcess $processor
 */
class Announcement {

    use ChainableContainer;

    /**
     *  Automatically load all actions
     */
    public function __construct() {
        $this->set_controllers();
    }

    public function set_controllers() {
        $this->container['post_type'] = new PostType();
        $this->container['template']  = new Frontend\Template();
        $this->container['manager']   = new Manager();
        $this->container['mails']     = new Mails();
        $this->container['processor'] = new BackgroundProcess();
        $this->container['announcements'] = new Announcements();
    }

    /**
     * Delete individual seller announcement cache.
     *
     * @since 3.4.2
     *
     * @param array|int $seller_ids
     * @param int       $post_id
     *
     * @return void
     */
    public function delete_announcement_cache( $seller_ids, $post_id = null ) {
        if ( is_array( $seller_ids ) ) {
            foreach ( $seller_ids as $seller_id ) {
                Cache::invalidate_group( "seller_announcement_{$seller_id}" );
            }
        } elseif ( is_numeric( $seller_ids ) ) {
            Cache::invalidate_group( "seller_announcement_{$seller_ids}" );
        } elseif ( is_numeric( $post_id ) ) {
            $seller_ids = dokan_pro()->announcement->manager->get_assigned_seller_from_db( $post_id );
            foreach ( $seller_ids as $seller_id ) {
                Cache::invalidate_group( "seller_announcement_{$seller_id['user_id']}" );
            }
        }

        // remove the main cache group
        Cache::invalidate_group( 'announcements' );
    }
}
