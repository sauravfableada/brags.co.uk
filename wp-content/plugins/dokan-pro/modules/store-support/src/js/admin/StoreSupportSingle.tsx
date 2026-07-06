import { useEffect, useState, RawHTML } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { __ } from '@wordpress/i18n';
import StoreSupportSingleSkeleton from './StoreSupportSingleSkeleton';
import { twMerge } from 'tailwind-merge';
import { useToast, DokanToaster, ToggleSwitch } from '@getdokan/dokan-ui';
import {
    Calendar,
    HashIcon,
    MessageSquare,
    ShoppingBag,
    Store,
    User,
} from 'lucide-react';
import { DokanModal, NotFound } from '@dokan/components';

type Topic = {
    ID: number;
    post_author: string;
    post_date: string;
    post_date_gmt: string;
    post_content: string;
    post_title: string;
    post_excerpt: string;
    post_status: 'open' | 'closed' | string;
    comment_status: string;
    ping_status: string;
    post_password: string;
    post_name: string;
    to_ping: string;
    pinged: string;
    post_modified: string;
    post_modified_gmt: string;
    post_content_filtered: string;
    post_parent: number;
    guid: string;
    menu_order: number;
    post_type: string;
    post_mime_type: string;
    comment_count: string;
    filter: string;
    avatar_url: string;
    post_author_name: string;
    post_date_formated: string;
    order_id: string;
};

type Comment = {
    comment_ID: string;
    comment_post_ID: string;
    comment_author: string;
    comment_author_email: string;
    comment_author_url: string;
    comment_author_IP: string;
    comment_date: string;
    comment_date_gmt: string;
    comment_content: string;
    comment_karma: string;
    comment_approved: string;
    comment_agent: string;
    comment_type: string;
    comment_parent: string;
    user_id: string;
    avatar_url: string;
    comment_user_type: {
        type: string;
        text: string;
    };
    comment_date_formated: string;
};

type StoreInfo = {
    store_id?: string;
    store_name: string;
    store_image_url?: string;
    store_url: string;
    social?: {
        fb?: string;
        twitter?: string;
        pinterest?: string;
        linkedin?: string;
        youtube?: string;
        instagram?: string;
        flickr?: string;
        threads?: string;
    };
    payment?: {
        paypal?: {
            email?: string;
        };
        bank?: {
            ac_name?: string;
            ac_number?: string;
            bank_name?: string;
            bank_addr?: string;
            routing_number?: string;
            iban?: string;
            swift?: string;
            ac_type?: string;
            declaration?: string;
        };
        skrill?: {
            email?: string;
        };
        dokan_custom?: {
            value?: string;
        };
        dokan_paypal_marketplace?: {
            email?: string;
        };
    };
    phone?: string;
    show_email?: string;
    address?: {
        street_1?: string;
        street_2?: string;
        city?: string;
        zip?: string;
        country?: string;
        state?: string;
    };
    location?: string;
    banner?: number;
    icon?: number;
    gravatar?: number;
    enable_tnc?: string;
    store_tnc?: string;
    show_min_order_discount?: string;
    store_seo?: any[];
    dokan_store_time_enabled?: string;
    dokan_store_open_notice?: string;
    dokan_store_close_notice?: string;
    find_address?: string;
    dokan_store_time?: {
        [ key: string ]: {
            status?: string;
            opening_time?: any[];
            closing_time?: any[];
        };
    };
    order_min_max?: {
        enable_vendor_min_max_quantity?: string;
        min_quantity_to_order?: string;
        max_quantity_to_order?: string;
        vendor_min_max_products?: any[];
        vendor_min_max_product_cat?: string;
        enable_vendor_min_max_amount?: string;
        min_amount_to_order?: string;
        max_amount_to_order?: string;
    };
    vendor_biography?: string;
    show_support_btn_product?: string;
    support_btn_name?: string;
    show_support_btn?: string;
    setting_go_vacation?: string;
    settings_closing_style?: string;
    setting_vacation_message?: string;
    seller_vacation_schedules?: any[];
    vendor_store_location_pickup?: {
        multiple_store_location?: string;
        default_location_name?: string;
    };
    profile_completion?: {
        closed_by_user?: boolean;
        gravatar?: number;
        phone?: number;
        banner?: number;
        store_name?: number;
        address?: number;
        location?: number;
        paypal?: number;
        next_todo?: string;
        progress?: number;
        progress_vals?: {
            banner_val?: number;
            profile_picture_val?: number;
            store_name_val?: number;
            address_val?: number;
            phone_val?: number;
            map_val?: number;
            payment_method_val?: number;
            social_val?: {
                [ key: string ]: number;
            };
        };
    };
    setting_minimum_order_amount?: string;
    setting_order_percentage?: string;
    sale_only_here?: boolean;
    categories?: Array< {
        term_id?: number;
        name?: string;
        slug?: string;
        term_group?: number;
        term_taxonomy_id?: number;
        taxonomy?: string;
        description?: string;
        parent?: number;
        count?: number;
        filter?: string;
    } >;
    company_name?: string;
    vat_number?: string;
    company_id_number?: string;
    bank_name?: string;
    bank_iban?: string;
    company_verification_files?: string[];
    dokan_verification?: any[];
    current_subscription?: {
        name?: string;
        label?: string;
    };
    assigned_subscription?: string;
    assigned_subscription_info?: {
        subscription_id?: string;
        has_subscription?: boolean;
        expiry_date?: string;
        published_products?: string;
        remaining_products?: number;
        recurring?: boolean;
        start_date?: string;
    };
    store_locations?: Array< {
        location_name?: string;
        street_1?: string;
        street_2?: string;
        city?: string;
        zip?: string;
        state?: string;
        country?: string;
    } >;
    enable_manual_order?: boolean;
    catalog_mode?: {
        hide_add_to_cart_button?: string;
        hide_product_price?: string;
        request_a_quote_enabled?: string;
    };
};

type TopicResponse = {
    topic: Topic;
    comments: Comment[];
    store_info: StoreInfo;
    site_image_url: string;
    site_title: string;
    unread_topics_count: number;
    dokan_admin_email_notification_global: boolean;
    dokan_admin_email_notification: string;
};

const StoreSupportSingle = ( {
    params,
    navigate,
}: {
    navigate?: ( url: string ) => any;
    params?: {
        ticketId: string;
        vendorId: string;
    };
} ) => {
    const [ ticket, setTicket ] = useState< TopicResponse | null >( null );
    const [ ticketId, setTicketId ] = useState( 0 );
    const [ vendorId, setVendorId ] = useState( 0 );
    const [ isLoading, setIsLoading ] = useState( true );
    const [ isNotFound, setIsNotFound ] = useState( false );
    const [ newComment, setNewComment ] = useState( '' );
    const [ isSubmitting, setIsSubmitting ] = useState( false );
    const [ isStatusDropdownOpen, setIsStatusDropdownOpen ] = useState( false );
    const [ notifyCustomer, setNotifyCustomer ] = useState( false );
    const [ notifyButtonDisabled, setNotifyButtonDisabled ] = useState( false );
    const [ replyAs, setReplyAs ] = useState< 'admin' | 'vendor' >( 'admin' );
    const [ isDropdownOpen, setIsDropdownOpen ] = useState( false );
    const [ isDeleteModalOpen, setIsDeleteModalOpen ] = useState( false );
    const [ commentToDelete, setCommentToDelete ] = useState< string | null >(
        null
    );

    const toast = useToast();

    // @ts-ignore
    const orderUrl = dokanAdminDashboardSettings[ 'admin-store-support' ]
        .order_url as string;

    const fetchTicket = async () => {
        setIsLoading( true );
        try {
            const data = await apiFetch< TopicResponse >( {
                path: addQueryArgs(
                    '/dokan/v1/admin/support-ticket/' + ticketId,
                    { vendor_id: vendorId }
                ),
            } );

            if ( ! data.topic?.ID ) {
                throw new Error( __( 'Ticket not found', 'dokan' ) );
            }

            setTicket( data );
            setNotifyCustomer( data.dokan_admin_email_notification === 'on' );
        } catch ( err ) {
            setIsNotFound( true );
        } finally {
            setIsLoading( false );
        }
    };

    const handleCommentSubmit = async () => {
        if ( ! newComment.trim() ) {
            return;
        }

        setIsSubmitting( true );
        try {
            await apiFetch( {
                path: `/dokan/v1/admin/support-ticket/${ ticketId }`,
                method: 'POST',
                data: {
                    replay: newComment,
                    vendor_id: vendorId,
                    selected_user: replyAs,
                },
            } );
            toast( {
                title: __( 'Reply posted successfully', 'dokan' ),
                subtitle: __(
                    'Your comment has been posted successfully.',
                    'dokan'
                ),
                type: 'success',
                duration: 2000,
            } );
            setNewComment( '' );
            fetchTicket(); // Refetch ticket to show the new comment
        } catch ( error ) {
            toast( {
                title: __( 'Error posting reply.', 'dokan' ),
                subtitle: __(
                    'There was an error posting your reply. Please try again.',
                    'dokan'
                ),
                type: 'error',
                duration: 5000,
            } );
        } finally {
            setIsSubmitting( false );
        }
    };

    const handleStatusChange = async ( newStatus: string ) => {
        setIsStatusDropdownOpen( false );
        try {
            await apiFetch( {
                path: `/dokan/v1/admin/support-ticket/${ ticketId }/status`,
                method: 'POST',
                data: {
                    status: newStatus,
                },
            } );
            toast( {
                title: __( 'Status changed successfully', 'dokan' ),
                subtitle: __(
                    'The ticket status has been changed successfully.',
                    'dokan'
                ),
                type: 'success',
            } );
            fetchTicket(); // Refetch to update status
        } catch ( error ) {
            toast( {
                title: __( 'Error changing status.', 'dokan' ),
                subtitle: __(
                    'There was an error changing the ticket status. Please try again.',
                    'dokan'
                ),
                type: 'error',
            } );
        }
    };

    useEffect( () => {
        setTicketId( Number( params.ticketId ) );
        setVendorId( Number( params.vendorId ) );
    }, [ params ] );

    useEffect( () => {
        if ( ! ticketId || ! vendorId ) {
            return;
        }
        fetchTicket();
    }, [ ticketId, vendorId ] );

    if ( isLoading ) {
        return <StoreSupportSingleSkeleton />;
    }

    if ( isNotFound || ! ticket ) {
        return <NotFound className="min-h-[calc(100vh-130px)] rounded-md" />;
    }

    const allMessages = [
        {
            id: ticket.topic.ID,
            avatar_url: ticket.topic.avatar_url,
            author: ticket.topic.post_author_name,
            role: __( 'Customer', 'dokan' ),
            date: ticket.topic.post_date_formated,
            content: ticket.topic.post_content,
            isAdmin: false,
        },
        ...ticket.comments.map( ( comment ) => {
            if ( 'customer' === comment.comment_user_type.type ) {
                return {
                    id: comment.comment_ID,
                    avatar_url: comment.avatar_url,
                    author: comment.comment_author,
                    role: __( 'Customer', 'dokan' ),
                    date: comment.comment_date_formated,
                    content: comment.comment_content,
                    isAdmin: false,
                };
            }
            if ( 'admin' === comment.comment_user_type.type ) {
                return {
                    id: comment.comment_ID,
                    avatar_url: ticket.site_image_url || comment.avatar_url,
                    author: ticket.site_title,
                    role: __( 'Admin', 'dokan' ),
                    date: comment.comment_date_formated,
                    content: comment.comment_content,
                    isAdmin: true,
                };
            }

            return {
                id: comment.comment_ID,
                avatar_url:
                    ticket.store_info?.store_image_url || comment.avatar_url,
                author: ticket.store_info?.store_name || comment.comment_author,
                role: __( 'Vendor', 'dokan' ),
                date: comment.comment_date_formated,
                content: comment.comment_content,
                isAdmin: false,
            };
        } ),
    ];

    const conversationCount = allMessages.length;

    const handleEmailNotificationToggle = async () => {
        setNotifyButtonDisabled( ( prev ) => true );
        setNotifyCustomer( ( nc ) => ! nc );
        try {
            await apiFetch( {
                path: `/dokan/v1/admin/support-ticket/${ ticketId }/email-notification`,
                method: 'POST',
                data: {
                    notification: ! notifyCustomer,
                },
            } );
            toast( {
                title: __( 'Email notification updated', 'dokan' ),
                subtitle: __(
                    'Your email notification preference has been updated.',
                    'dokan'
                ),
                type: 'success',
            } );
            // fetchTicket();
        } catch ( error ) {
            setNotifyCustomer( ( nc ) => ! nc );
            toast( {
                title: __( 'Error updating email notification.', 'dokan' ),
                subtitle: __(
                    'There was an error updating your email notification preference. Please try again.',
                    'dokan'
                ),
                type: 'error',
            } );
        }
        setNotifyButtonDisabled( ( prev ) => false );
    };

    const handleDeleteClick = ( commentId: string ) => {
        setCommentToDelete( commentId );
        setIsDeleteModalOpen( true );
    };

    const handleDeleteCancel = () => {
        setIsDeleteModalOpen( false );
        setCommentToDelete( null );
    };

    const handleDeleteConfirm = async () => {
        if ( ! commentToDelete ) {
            return;
        }

        try {
            await apiFetch( {
                path: `/dokan/v1/admin/support-ticket/${ commentToDelete }/comment`,
                method: 'DELETE',
            } );
            toast( {
                title: __( 'Comment deleted successfully', 'dokan' ),
                subtitle: __(
                    'The comment has been deleted successfully.',
                    'dokan'
                ),
                type: 'success',
                duration: 2000,
            } );
            setIsDeleteModalOpen( false );
            setCommentToDelete( null );
            fetchTicket(); // Refetch ticket to update the list
        } catch ( error ) {
            toast( {
                title: __( 'Error deleting comment.', 'dokan' ),
                subtitle: __(
                    'There was an error deleting the comment. Please try again.',
                    'dokan'
                ),
                type: 'error',
                duration: 2000,
            } );
            console.error( 'Failed to delete comment:', error );
        }
    };

    return (
        <div className="">
            { /* Back Button */ }
            <button
                onClick={ () => navigate?.( '/admin-store-support' ) }
                className="text-gray-600 hover:text-gray-800 mb-6 flex items-center text-sm"
            >
                <svg
                    className="w-4 h-4 mr-1"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={ 2 }
                        d="M15 19l-7-7 7-7"
                    />
                </svg>
                { __( 'Ticket list', 'dokan' ) }
            </button>

            { /* Ticket Title */ }
            <h3 className="text-2xl font-bold text-gray-900 mb-5">
                { ticket.topic.post_title }
            </h3>

            <div className="flex gap-5">
                { /* Main Content */ }
                <div className="flex-1 w-3/4 flex flex-col gap-5">
                    { /* Conversation Thread */ }
                    <div className="divide-y bg-white divide-[#E9E9E9] border border-[#E9E9E9] rounded-md shadow-[0px_1px_3px_0px_#0000001A]">
                        { allMessages.map( ( message ) => (
                            <div
                                key={ message.id }
                                className="flex items-start p-4"
                            >
                                { /* Avatar */ }
                                <img
                                    src={ message.avatar_url }
                                    alt={ message.author }
                                    className="w-10 h-10 rounded-full mr-4"
                                />

                                <div className="flex-1 flex flex-col gap-3">
                                    { /* Header */ }
                                    <div className="flex justify-between items-start align-middle">
                                        <div>
                                            <div className="font-semibold text-gray-900">
                                                { message.author }
                                            </div>
                                            <div className="text-xs text-gray-500">
                                                { message.role }
                                            </div>
                                        </div>
                                        <div className="text-xs text-gray-500 self-center">
                                            { message.date }
                                        </div>
                                    </div>

                                    { /* Content */ }
                                    <RawHTML className="text-gray-700 text-sm leading-relaxed whitespace-pre-line">
                                        { message.content }
                                    </RawHTML>

                                    { message.isAdmin && (
                                        <div>
                                            <button
                                                className="text-xs text-red-500 hover:underline"
                                                onClick={ () =>
                                                    handleDeleteClick(
                                                        String( message.id )
                                                    )
                                                }
                                            >
                                                { __( 'Delete', 'dokan' ) }
                                            </button>
                                        </div>
                                    ) }
                                </div>
                            </div>
                        ) ) }
                    </div>

                    { /* Reply Box */ }
                    { ticket.topic.post_status === 'open' && (
                        <div className="">
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                { __( 'Reply box:', 'dokan' ) }
                            </label>
                            <div className="bg-white rounded-md shadow-sm">
                                <textarea
                                    value={ newComment }
                                    onChange={ ( e ) =>
                                        setNewComment( e.target.value )
                                    }
                                    placeholder={ __(
                                        'Write something',
                                        'dokan'
                                    ) }
                                    className="w-full p-4 border-0 rounded-t-md focus:ring-0 focus:outline-none resize-none"
                                    rows={ 6 }
                                    disabled={ isSubmitting }
                                />
                                <div className="bg-white p-4 rounded-b-md flex justify-end border-t border-[#E9E9E9]">
                                    <div className="relative inline-flex rounded-md">
                                        <div className="relative inline-flex rounded-md">
                                            <button
                                                onClick={ handleCommentSubmit }
                                                disabled={
                                                    isSubmitting ||
                                                    ! newComment.trim()
                                                }
                                                className="relative inline-flex items-center rounded-l-md bg-[#7047EB] px-6 py-2 font-medium text-white hover:bg-[#5B32DA] disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                { isSubmitting
                                                    ? __( 'Sending…', 'dokan' )
                                                    : replyAs === 'admin'
                                                    ? __(
                                                          'Send as Admin',
                                                          'dokan'
                                                      )
                                                    : __(
                                                          'Send as Vendor',
                                                          'dokan'
                                                      ) }
                                            </button>
                                            <div className="relative">
                                                <button
                                                    onClick={ () =>
                                                        setIsDropdownOpen(
                                                            ( isDop ) => ! isDop
                                                        )
                                                    }
                                                    className="relative -ml-px inline-flex border-l border-[#ac98ea] items-center rounded-r-md bg-[#7047EB] px-2 py-3.5 text-white font-medium hover:bg-[#5B32DA] disabled:opacity-50 disabled:cursor-not-allowed"
                                                    disabled={
                                                        isSubmitting ||
                                                        ! newComment.trim()
                                                    }
                                                >
                                                    <svg
                                                        className="w-4 h-4"
                                                        fill="currentColor"
                                                        viewBox="0 0 20 20"
                                                    >
                                                        <path
                                                            fillRule="evenodd"
                                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                            clipRule="evenodd"
                                                        />
                                                    </svg>
                                                </button>
                                                { isDropdownOpen && (
                                                    <div className="absolute right-0 mt-2 w-40 bg-white rounded-md shadow-lg z-10 border border-gray-200">
                                                        <button
                                                            onClick={ () => {
                                                                setReplyAs(
                                                                    'admin'
                                                                );
                                                                setIsDropdownOpen(
                                                                    false
                                                                );
                                                            } }
                                                            className="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                        >
                                                            { __(
                                                                'Send as Admin',
                                                                'dokan'
                                                            ) }
                                                        </button>
                                                        <button
                                                            onClick={ () => {
                                                                setReplyAs(
                                                                    'vendor'
                                                                );
                                                                setIsDropdownOpen(
                                                                    false
                                                                );
                                                            } }
                                                            className="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                        >
                                                            { __(
                                                                'Send as Vendor',
                                                                'dokan'
                                                            ) }
                                                        </button>
                                                    </div>
                                                ) }
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ) }
                </div>

                { /* Sidebar */ }
                <div className="w-1/4 ">
                    <div className="bg-white p-6 rounded-md shadow-sm">
                        { /* Header with Status Dropdown */ }
                        <div className="flex justify-between items-center mb-6">
                            <h2 className="text-md font-bold text-gray-900">
                                { __( 'Ticket Summary', 'dokan' ) }
                            </h2>
                            <div className="relative">
                                <button
                                    onClick={ () =>
                                        setIsStatusDropdownOpen(
                                            ! isStatusDropdownOpen
                                        )
                                    }
                                    className={ twMerge(
                                        'px-4 py-2 rounded-md text-white text-sm font-medium flex gap-1.5 divide-x divide-[#E9E9E9] items-center',
                                        ticket.topic.post_status === 'open'
                                            ? 'bg-[#E64B5F] hover:bg-[#CD414C]'
                                            : 'bg-[#0FAD83] hover:bg-[#008864]'
                                    ) }
                                >
                                    { ticket.topic.post_status === 'open'
                                        ? __( 'Open', 'dokan' )
                                        : __( 'Closed', 'dokan' ) }
                                    <svg
                                        className="w-4 h-4 ml-1"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fillRule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clipRule="evenodd"
                                        />
                                    </svg>
                                </button>

                                { isStatusDropdownOpen && (
                                    <div className="absolute right-0 mt-2 w-32 bg-white rounded-md shadow-lg z-10 border border-gray-200">
                                        { ticket.topic.post_status ===
                                        'closed' ? (
                                            <button
                                                onClick={ () =>
                                                    handleStatusChange( 'open' )
                                                }
                                                className="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                            >
                                                { __( 'Open Ticket', 'dokan' ) }
                                            </button>
                                        ) : (
                                            <button
                                                onClick={ () =>
                                                    handleStatusChange(
                                                        'closed'
                                                    )
                                                }
                                                className="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                            >
                                                { __(
                                                    'Close Ticket',
                                                    'dokan'
                                                ) }
                                            </button>
                                        ) }
                                    </div>
                                ) }
                            </div>
                        </div>

                        { /* Ticket Details */ }
                        <ul className="flex flex-col gap-4 text-sm mb-6">
                            <li className="flex gap-1.5 items-center text-gray-600">
                                <HashIcon
                                    size={ 20 }
                                    className="text-gray-400"
                                />
                                <span className="">
                                    { __( 'Ticket ID:', 'dokan' ) }
                                </span>
                                <span className="font-medium text-gray-900">
                                    #{ ticket.topic.ID }
                                </span>
                            </li>
                            <li className="flex gap-1.5 items-center text-gray-600">
                                <ShoppingBag
                                    size={ 20 }
                                    className="text-gray-400"
                                />
                                <span className="">
                                    { __( 'Order ID:', 'dokan' ) }
                                </span>
                                <a
                                    href={ orderUrl + ticket.topic.order_id }
                                    className="font-medium text-[#7047EB]"
                                >
                                    #{ ticket.topic.order_id }
                                </a>
                            </li>
                            <li className="flex gap-1.5 items-start text-gray-600">
                                <Store size={ 20 } className="text-gray-400" />
                                <span className="">
                                    { __( 'Vendor:', 'dokan' ) }
                                </span>
                                <div className="flex gap-1.5 w-full flex-col">
                                    <a
                                        href={ ticket.store_info.store_url }
                                        className="font-medium text-[#7047EB] flex"
                                    >
                                        { ticket.store_info?.store_name ||
                                            'N/A' }
                                    </a>
                                    <span className="break-all text-gray-500 flex">
                                        { (
                                            ticket.store_info?.categories || []
                                        )
                                            .map( ( cat ) => cat?.name )
                                            .join( ', ' ) }
                                    </span>
                                </div>
                            </li>
                            <li className="flex gap-1.5 items-center text-gray-600">
                                <User size={ 20 } className="text-gray-400" />
                                <span className="">
                                    { __( 'Customer:', 'dokan' ) }
                                </span>
                                <span className="font-medium text-gray-900">
                                    { ticket.topic.post_author_name }
                                </span>
                            </li>
                            <li className="flex gap-1.5 items-center text-gray-600">
                                <MessageSquare
                                    size={ 20 }
                                    className="text-gray-400"
                                />
                                <span className="">
                                    { __( 'Conversation:', 'dokan' ) }
                                </span>
                                <span className="font-medium text-gray-900">
                                    { conversationCount }
                                </span>
                            </li>
                            <li className="flex gap-1.5 items-center text-gray-600">
                                <Calendar
                                    size={ 20 }
                                    className="text-gray-400"
                                />
                                <span className="">
                                    { __( 'Created At:', 'dokan' ) }
                                </span>
                                <span className="font-medium text-gray-900 text-xs">
                                    { ticket.topic.post_date_formated }
                                </span>
                            </li>
                        </ul>

                        { /* Divider */ }
                        <div className="border-t border-gray-200 my-6 -mx-6"></div>

                        { /* Email Notification Toggle */ }
                        <div className="flex gap-4 items-center">
                            <ToggleSwitch
                                checked={ notifyCustomer }
                                disabled={ notifyButtonDisabled }
                                color="primary"
                                onChange={ () => {
                                    if ( notifyButtonDisabled ) {
                                        return;
                                    }
                                    return handleEmailNotificationToggle();
                                } }
                            />
                            <div>
                                <div className="font-semibold text-gray-900 mb-1">
                                    { __( 'Email Notification', 'dokan' ) }
                                </div>
                                <div className="text-xs text-gray-500">
                                    { __(
                                        'Notify customer about this ticket.',
                                        'dokan'
                                    ) }
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            { /* Delete Confirmation Modal */ }
            <DokanModal
                namespace="dokan-admin-support-ticket-delete-modal"
                dialogTitle={ __( 'Delete Reply', 'dokan' ) }
                isOpen={ isDeleteModalOpen }
                onClose={ handleDeleteCancel }
                onConfirm={ handleDeleteConfirm }
                confirmButtonVariant="danger"
            />

            <DokanToaster />
        </div>
    );
};

export default StoreSupportSingle;
