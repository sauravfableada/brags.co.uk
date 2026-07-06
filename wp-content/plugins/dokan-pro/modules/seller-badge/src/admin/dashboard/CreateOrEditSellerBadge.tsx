import { __ } from '@wordpress/i18n';
import { useEffect, useState, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { DokanButton, DokanLink } from '@dokan/components';
import { ChevronLeft } from 'lucide-react';
import { Card, SimpleInput, useToast, DokanToaster } from '@getdokan/dokan-ui';
import UploadImage from './UploadImage';
import LogicalComponent from './LogicalComponent';
import TrendingProduct from './TrendingProduct';
import VerifiedSeller from './VerifiedSeller';
import NoLevels from './NoLevels';
import DummyLevels from './DummyLevels';
import BadgeEventInput from './BadgeEventInput';
import { BadgeEvent, BadgeLevel, SellerBadge } from './types';

export default function CreateOrEditSellerBadge( { params, navigate } ) {
    const { id } = params;
    const [ isLoading, setIsLoading ] = useState( false );
    const [ isSaving, setIsSaving ] = useState( false );
    const [ availableEvents, setAvailableEvents ] = useState< BadgeEvent[] >(
        []
    );
    const [ badge, setBadge ] = useState< SellerBadge >( {
        id: 0,
        badge_name: '',
        badge_logo: '',
        badge_logo_raw: '',
        event_type: '',
        event: {
            id: '',
            title: '',
            description: '',
            badge_logo: '',
            badge_logo_raw: '',
            condition_text: {
                type: '',
                prefix: '',
                suffix: '',
            },
            hover_text: '',
            group: {
                key: '',
                title: '',
                type: '',
            },
            has_multiple_levels: false,
            badge_logo: '',
            badge_logo_raw: '',
            input_group_icon: '',
            type: '',
            created: false,
        },
        badge_type: 'published',
        levels: [],
    } );
    const toast = useToast();

    const pageTitle = id
        ? __( 'Editing Badge', 'dokan' )
        : __( 'Creating New badge', 'dokan' );
    const saveButtonText = id
        ? __( 'Update Badge', 'dokan' )
        : __( 'Create Badge', 'dokan' );

    useEffect( () => {
        // Fetch available badge events
        setIsLoading( true );
        apiFetch< any >( { path: '/dokan/v1/seller-badge/events' } )
            .then( ( data ) => {
                setAvailableEvents( data || [] );
            } )
            .catch( () => {
                setAvailableEvents( [] );
            } )
            .finally( () => {
                setIsLoading( false );
            } );
    }, [] );

    useEffect( () => {
        if ( id ) {
            setIsLoading( true );
            apiFetch< SellerBadge >( {
                path: `/dokan/v1/seller-badge/${ id }`,
            } )
                .then( ( data ) => {
                    const normalized = {
                        ...data,
                        levels: ( data.levels || [] ).map( ( lvl ) => ( {
                            ...lvl,
                            level_data: String(
                                ( lvl as any ).level_data ?? ''
                            ),
                        } ) ),
                    } as SellerBadge;
                    setBadge( normalized );
                } )
                .catch( ( e ) => {
                    toast( {
                        title:
                            e?.message ||
                            __( 'Failed to load badge.', 'dokan' ),
                        type: 'error',
                    } );
                } )
                .finally( () => {
                    setIsLoading( false );
                } );
        }
    }, [ id ] );

    const handleEventChange = ( eventId: string ) => {
        const selectedEvent = availableEvents.find( ( e ) => e.id === eventId );
        if ( selectedEvent ) {
            setBadge( {
                ...badge,
                event_type: eventId,
                event: selectedEvent,
                badge_name: badge.badge_name || selectedEvent.title,
                badge_logo: selectedEvent.badge_logo,
                badge_logo_raw: selectedEvent.badge_logo_raw,
                default_logo: selectedEvent.badge_logo_raw,
                formatted_default_logo: selectedEvent.badge_logo,
                levels: [],
            } );
        }
    };

    const handleSave = () => {
        if ( ! badge.badge_name ) {
            toast( {
                title: __( 'Please enter a valid badge name.', 'dokan' ),
                type: 'error',
            } );
            return;
        }
        if ( ! badge.event_type ) {
            toast( {
                title: __( 'Please select a valid badge event.', 'dokan' ),
                type: 'error',
            } );
            return;
        }

        setIsSaving( true );
        const method = id ? 'PUT' : 'POST';
        const path = id
            ? `/dokan/v1/seller-badge/${ id }`
            : '/dokan/v1/seller-badge';

        apiFetch( {
            path,
            method,
            data: badge,
        } )
            .then( () => {
                toast( {
                    title: id
                        ? __( 'Badge updated successfully', 'dokan' )
                        : __( 'Badge added successfully', 'dokan' ),
                    type: 'success',
                } );

                navigate( '/dokan-seller-badge' );
            } )
            .catch( ( e ) => {
                toast( {
                    title: e?.message || __( 'Failed to save badge.', 'dokan' ),
                    type: 'error',
                } );
            } )
            .finally( () => {
                setIsSaving( false );
            } );
    };

    const handleCancel = () => {
        navigate( '/dokan-seller-badge' );
    };

    const handleRestoreDefault = () => {
        setBadge( {
            ...badge,
            badge_logo: badge?.formatted_default_logo ?? '',
            badge_logo_raw: badge?.default_logo ?? '',
        } );
    };

    const handleLevelsChange = useCallback( ( levels: BadgeLevel[] ) => {
        const normalized = ( levels || [] ).map( ( lvl ) => ( {
            ...lvl,
            level_data: String( ( lvl as any ).level_data ?? '' ),
        } ) );

        setBadge( ( prev ) => ( {
            ...prev,
            levels: normalized,
        } ) );
    }, [] );

    // Component mapping similar to Vue's responsibleComponents
    const responsibleComponents: Record<
        string,
        React.ComponentType< any >
    > = {
        product_published: LogicalComponent,
        sale_amount: LogicalComponent,
        customer_review: LogicalComponent,
        number_of_items_sold: LogicalComponent,
        number_of_orders: LogicalComponent,
        store_support_count: LogicalComponent,
        trending_product: TrendingProduct,
        verified_seller: VerifiedSeller,
    };

    const getCurrentComponent = () => {
        if ( ! badge.event_type ) {
            return DummyLevels;
        }
        return responsibleComponents[ badge.event_type ] || NoLevels;
    };

    const CurrentLevelComponent = getCurrentComponent();

    const getEvent = ( eventId: string ) => {
        return availableEvents.find( ( e ) => e.id === eventId );
    };

    if ( isLoading ) {
        return (
            <div className="flex items-center justify-center h-64">
                <div className="text-gray-500">
                    { __( 'Loading…', 'dokan' ) }
                </div>
            </div>
        );
    }

    return (
        <div className="seller-badge-edit-wrapper">
            { /* Header */ }
            <div className="flex items-end justify-between mb-6">
                <div>
                    <div className="flex flex-row justify-start">
                        <DokanLink
                            as="button"
                            type="button"
                            className="flex flex-row w-auto items-center gap-1 !text-[#828282] font-[400] text-[14px] hover:!underline"
                            onClick={ () => {
                                navigate( '/dokan-seller-badge' );
                            } }
                        >
                            <ChevronLeft size="15" />
                            <span>{ __( 'Seller Badge List', 'dokan' ) }</span>
                        </DokanLink>
                    </div>

                    <div className="mt-[24px]">
                        <h1 className="text-[#25252D] font-[700] text-[24px]">
                            { pageTitle }
                        </h1>
                    </div>
                </div>

                <div className="flex items-center gap-3">
                    <DokanButton
                        variant="tertiary"
                        onClick={ handleCancel }
                        disabled={ isSaving }
                        className="shadow-none !text-[#25252D]"
                    >
                        { __( 'Cancel', 'dokan' ) }
                    </DokanButton>
                    <DokanButton
                        className="disabled:!bg-[#A5A5AA] disabled:!text-white disabled:!border-none"
                        variant="primary"
                        onClick={ handleSave }
                        disabled={
                            isSaving || ! badge.badge_name || ! badge.event_type
                        }
                        loading={ isSaving }
                        label={
                            <div className="flex items-center gap-2">
                                { saveButtonText }
                            </div>
                        }
                    />
                </div>
            </div>

            { /* Main Content */ }
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-[20px]">
                { /* Left Column */ }
                <div className="lg:col-span-2 space-y-[20px]">
                    { /* Badge Event & Name Section */ }
                    <Card className="bg-white rounded-lg p-6">
                        <div className="space-y-4">
                            { /* Badge Event */ }
                            <div>
                                <div>
                                    <BadgeEventInput
                                        events={ availableEvents }
                                        value={
                                            badge?.event?.id
                                                ? getEvent( badge.event.id )
                                                : ''
                                        }
                                        onChange={ ( ev ) =>
                                            handleEventChange( ev.id )
                                        }
                                    />
                                </div>
                            </div>

                            { /* Badge Name */ }
                            <div>
                                <label
                                    htmlFor="badge_name"
                                    className="cursor-pointer text-sm font-medium leading-[21px] text-gray-900 mb-2 inline-block"
                                >
                                    { __( 'Badge Name', 'dokan' ) }
                                </label>
                                <SimpleInput
                                    value={ badge.badge_name }
                                    onChange={ ( e ) =>
                                        setBadge( {
                                            ...badge,
                                            badge_name: e.target.value,
                                        } )
                                    }
                                    input={ {
                                        placeholder: __(
                                            'e.g. Best Seller',
                                            'dokan'
                                        ),
                                        type: 'text',
                                        disabled: ! badge.event_type,
                                        name: 'badge_name',
                                    } }
                                    // label={__('Badge Name', 'dokan')}
                                    className="w-full"
                                />
                            </div>
                        </div>
                    </Card>

                    { /* Condition & Level Section */ }
                    <Card className="bg-white rounded-lg">
                        <div className="p-6">
                            <h3 className="text-[14px] font-[500] text-[#25252D] mb-[4px]">
                                { __( 'Badge Image', 'dokan' ) }
                            </h3>
                            { badge.event_type && (
                                <p className="text-[#828282] font-[400] test-[12px] mb-[13px]">
                                    { __(
                                        '60X65 pixel, JPG, PNG or SVG.',
                                        'dokan'
                                    ) }
                                </p>
                            ) }

                            { badge.event_type ? (
                                <div className="space-y-[20px]">
                                    <UploadImage
                                        showButton={ true }
                                        src={ badge.badge_logo }
                                        buttonLabel={ __( 'Change', 'dokan' ) }
                                        croppingWidth={ 60 }
                                        croppingHeight={ 65 }
                                        className="flex items-center gap-4"
                                        onUploadedImage={ ( image ) => {
                                            setBadge( {
                                                ...badge,
                                                badge_logo: image.src,
                                                badge_logo_raw: String(
                                                    image.id
                                                ),
                                            } );
                                        } }
                                    />
                                    <p className="text-sm text-gray-600">
                                        { __(
                                            'Click above to edit or update badge image or',
                                            'dokan'
                                        ) }{ ' ' }
                                        <button
                                            onClick={ handleRestoreDefault }
                                            className="dokan-link"
                                        >
                                            { __( 'restore default', 'dokan' ) }
                                        </button>
                                    </p>
                                </div>
                            ) : (
                                <div>
                                    <p className="text-[12px] text-[#828282] font-[400] mb-[13px]">
                                        { __(
                                            'It will be appeared here after selecting badge event.',
                                            'dokan'
                                        ) }
                                    </p>

                                    <UploadImage.Placeholder />
                                </div>
                            ) }
                        </div>

                        <div className="w-full h-[1px] bg-[#E9E9E9]"></div>

                        <div className="p-6">
                            <h3 className="text-[14px] font-[500] text-[#25252D] mb-[10px]">
                                { __( 'Condition & Level', 'dokan' ) }
                            </h3>
                            <CurrentLevelComponent
                                event={ badge.event }
                                levels={ badge.levels || [] }
                                onLevelsChange={ handleLevelsChange }
                            />
                        </div>
                    </Card>
                </div>

                { /* Right Column - Status */ }
                <div className="lg:col-span-1">
                    <Card className="bg-white rounded-lg p-6 sticky top-6">
                        <h3 className="text-base font-semibold text-gray-900 mb-4">
                            { __( 'Status', 'dokan' ) }
                        </h3>
                        <div className="flex flex-col gap-[20px]">
                            { /* Publish Option */ }
                            <label className="flex items-start gap-3 cursor-pointer group">
                                <input
                                    type="radio"
                                    name="status"
                                    value="published"
                                    checked={
                                        badge.badge_status === 'published'
                                    }
                                    onChange={ ( e ) =>
                                        setBadge( {
                                            ...badge,
                                            badge_status: e.target.value as
                                                | 'published'
                                                | 'draft',
                                        } )
                                    }
                                    className="mt-0.5 w-4 h-4 !text-dokan-primary checked:!border checked:!border-dokan-btn-border checked:bg-[white] checked:before:!bg-dokan-btn hover:!bg-white focus:!bg-white"
                                />
                                <div className="flex-1 group">
                                    <div className="font-[600] text-[#575757] text-[14px] group-hover:!text-dokan-primary">
                                        { __( 'Publish', 'dokan' ) }
                                    </div>
                                    <div className="text-[#828282] font-[400] text-[12px] mt-[6px]">
                                        { __(
                                            'It will publish immediately.',
                                            'dokan'
                                        ) }
                                    </div>
                                </div>
                            </label>

                            { /* Draft Option */ }
                            <label className="flex items-start gap-3 cursor-pointer group">
                                <input
                                    type="radio"
                                    name="status"
                                    value="draft"
                                    checked={ badge.badge_status === 'draft' }
                                    onChange={ ( e ) =>
                                        setBadge( {
                                            ...badge,
                                            badge_status: e.target.value as
                                                | 'published'
                                                | 'draft',
                                        } )
                                    }
                                    className="mt-0.5 w-4 h-4 !text-dokan-primary checked:!border checked:!border-dokan-btn-border checked:bg-[white] checked:before:!bg-dokan-btn hover:!bg-white focus:!bg-white"
                                />
                                <div className="flex-1">
                                    <div className="font-[600] text-[#575757] text-[14px] group-hover:!text-dokan-primary">
                                        { __( 'Draft', 'dokan' ) }
                                    </div>
                                    <div className="text-[#828282] font-[400] text-[12px] mt-[6px]">
                                        { __(
                                            'It will be saved as draft',
                                            'dokan'
                                        ) }
                                    </div>
                                </div>
                            </label>
                        </div>
                    </Card>
                </div>
            </div>

            <DokanToaster />
        </div>
    );
}
