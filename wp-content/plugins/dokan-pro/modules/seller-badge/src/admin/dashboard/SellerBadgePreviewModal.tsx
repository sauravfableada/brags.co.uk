import { __, sprintf } from '@wordpress/i18n';
import { RawHTML, useEffect, useMemo, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { DokanModal } from '@dokan/components';
import { formatPrice } from '@dokan/utilities';
import { useToast } from '@getdokan/dokan-ui';

interface SellerBadgeEvent {
    id: string;
    title: string;
    description?: string;
    hover_text?: string;
    condition_text?: {
        prefix?: string;
        suffix?: string;
        type?: string; // e.g., 'price'
    };
}

interface VerificationTypeMap {
    [ key: string ]: { title?: string } | undefined;
}

interface SellerBadgeLevel {
    id: number;
    badge_id: number;
    level: number;
    level_condition: string;
    formatted_condition?: string;
    level_data?: string;
    vendor_count: number;
}

interface SellerBadgeDetails {
    id: number;
    badge_name: string;
    badge_logo: string;
    formatted_default_logo?: string;
    event_type: string;
    event?: SellerBadgeEvent;
    badge_status: 'published' | 'draft' | string;
    formatted_badge_status?: string;
    level_count: number;
    vendor_count: number;
    acquired_level_count?: number;
    levels?: SellerBadgeLevel[];
    // Map of level.id => acquisition info (available in vendor/front-end context)
    acquired?: Record< number, { formatted_created_at?: string } >;
}

interface Props {
    isOpen: boolean;
    badgeId: number | null;
    onClose: () => void;
    // Optional: when previewing for a specific vendor or in frontend context
    vendorId?: number;
    isFrontend?: boolean;
}

export default function SellerBadgePreviewModal( {
    isOpen,
    badgeId,
    onClose,
    vendorId,
    isFrontend,
}: Props ) {
    const [ loading, setLoading ] = useState( false );
    const [ error, setError ] = useState< string | null >( null );
    const [ data, setData ] = useState< SellerBadgeDetails | null >( null );
    const [ verificationTypes, setVerificationTypes ] =
        useState< VerificationTypeMap >( {} );
    const toast = useToast();

    const namespace = useMemo( () => {
        return `seller-badge-preview-${ badgeId ?? 'unknown' }`;
    }, [ badgeId ] );

    // helper: build condition text matching Vue mixin
    const getBadgeLevelConditionText = (
        event: SellerBadgeEvent | undefined,
        level: SellerBadgeLevel
    ): string => {
        if ( ! event ) {
            return '';
        }
        let levelData: any = level.level_data;
        const type = event?.condition_text?.type;
        if ( type === 'price' ) {
            // best-effort currency format; fallback to as-is
            try {
                levelData = formatPrice(
                    parseFloat( String( level.level_data || 0 ) )
                );
            } catch ( _ ) {
                levelData = level.level_data;
            }
        } else if ( event.id === 'verified_seller' ) {
            const vt = verificationTypes?.[ level.level_condition ];
            levelData = vt?.title || level.level_data;
        } else if ( event.id === 'trending_product' ) {
            levelData = sprintf(
                // translators: 1: top number, 2: condition text (e.g., day/week)
                __( 'Based on top %d (%s) best selling products.', 'dokan' ),
                Number( level.level_data || 0 ),
                level.level_condition || ''
            );
        }
        const prefix = event?.condition_text?.prefix || '';
        const suffix = event?.condition_text?.suffix || '';
        const formattedCondition = level.formatted_condition || '';
        const text = `${ prefix } ${ formattedCondition } <em>${ levelData }</em> ${ suffix }`;
        return text.trim();
    };

    useEffect( () => {
        let isMounted = true;
        if ( ! isOpen || ! badgeId ) {
            setData( null );
            setError( null );
            setLoading( false );
            return;
        }
        setLoading( true );
        setError( null );
        // Build request path with optional vendor/frontend context to receive acquisition info
        const params = new URLSearchParams();
        if ( typeof isFrontend === 'boolean' ) {
            params.set( 'is_frontend', String( isFrontend ) );
        } else {
            params.set( 'is_frontend', 'false' );
        }
        if ( vendorId ) {
            params.set( 'vendor_id', String( vendorId ) );
        }
        const path = `/dokan/v1/seller-badge/${ badgeId }?${ params.toString() }`;
        apiFetch< SellerBadgeDetails >( {
            path,
        } )
            .then( ( res: any ) => {
                if ( ! isMounted ) {
                    return;
                }
                setData( res as SellerBadgeDetails );
                try {
                    if ( ( res as any )?.event?.id === 'verified_seller' ) {
                        apiFetch< VerificationTypeMap >( {
                            path: '/dokan/v1/seller-badge/verification-types/',
                        } )
                            .then( ( vt: any ) => {
                                if ( isMounted ) {
                                    setVerificationTypes( vt || {} );
                                }
                            } )
                            .catch( () => {} );
                    }
                } catch ( _e ) {
                    toast( {
                        title: __(
                            'Failed to load verification types',
                            'dokan'
                        ),
                        type: 'error',
                    } );
                }
            } )
            .catch( () => {
                if ( ! isMounted ) {
                    return;
                }
                setError( __( 'Failed to load badge preview', 'dokan' ) );
            } )
            .finally( () => {
                if ( ! isMounted ) {
                    return;
                }
                setLoading( false );
            } );

        return () => {
            isMounted = false;
        };
    }, [ isOpen, badgeId ] );

    return (
        <DokanModal
            isOpen={ isOpen }
            namespace={ namespace }
            onClose={ onClose }
            dialogFooter={ false }
            dialogContent={
                <div className="min-w-[560px]">
                    { loading && (
                        <div className="p-6 text-sm text-gray-600">
                            { __( 'Loading…', 'dokan' ) }
                        </div>
                    ) }
                    { ! loading && error && (
                        <div className="p-6 text-sm text-red-600">
                            { error }
                        </div>
                    ) }
                    { ! loading && ! error && data && (
                        <div className="p-6">
                            <div className="flex items-center gap-3 mb-4">
                                <img
                                    src={
                                        data.formatted_default_logo ||
                                        data.badge_logo
                                    }
                                    alt={ data.badge_name }
                                    className="h-12 w-12 object-contain"
                                />
                                <div>
                                    <div className="text-lg font-semibold">
                                        { data.badge_name }
                                    </div>
                                    <div className="text-xs text-[#7A7A7A]">
                                        { __( 'No. of Vendors:', 'dokan' ) }{ ' ' }
                                        <strong>
                                            { data.vendor_count || 0 }
                                        </strong>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <div className="font-semibold mb-2">
                                    { __( 'Achieved Levels:', 'dokan' ) }
                                </div>
                                <div className="space-y-2 max-h-[500px] overflow-y-auto">
                                    { ( data.levels || [] ).map( ( lvl ) => (
                                        <div
                                            key={ lvl.id }
                                            className="border rounded p-3 flex items-start gap-3"
                                            title={ `${ __(
                                                'Level',
                                                'dokan'
                                            ) } ${ lvl.level }` }
                                        >
                                            <div className="w-[30px] h-[32px] flex items-center justify-center relative">
                                                <img
                                                    src={ data.badge_logo }
                                                    alt={ data.badge_name }
                                                />
                                                <span className="absolute flex items-center justify-center w-[15px] h-[15px] bottom-0 left-[2px] text-[0.6875rem] font-[800] text-black bg-white rounded-full shadow-[0px_3px_6px_rgba(0,_0,_0,_0.25)] translate-y-[10px] translate-x-[5px]">
                                                    { data.event_type ===
                                                    'years_active'
                                                        ? data.acquired_level_count ||
                                                          lvl.level
                                                        : lvl.level }
                                                </span>
                                            </div>
                                            <div className="flex-1">
                                                <div className="text-sm">
                                                    <RawHTML>
                                                        { getBadgeLevelConditionText(
                                                            data?.event,
                                                            lvl
                                                        ) }
                                                    </RawHTML>
                                                </div>
                                                <div className="text-xs text-[#7A7A7A] mt-1">
                                                    { __(
                                                        'No. of Vendors:',
                                                        'dokan'
                                                    ) }{ ' ' }
                                                    <strong>
                                                        { lvl.vendor_count ||
                                                            0 }
                                                    </strong>
                                                </div>
                                                { ( vendorId || isFrontend ) &&
                                                    data?.acquired?.[ lvl.id ]
                                                        ?.formatted_created_at && (
                                                        <div className="text-xs text-[#b3b3b3] mt-1 flex items-center gap-1">
                                                            { __(
                                                                'Acquisition date:',
                                                                'dokan'
                                                            ) }{ ' ' }
                                                            <strong className="text-[#78818f]">
                                                                {
                                                                    data
                                                                        ?.acquired?.[
                                                                        lvl.id
                                                                    ]
                                                                        ?.formatted_created_at
                                                                }
                                                            </strong>
                                                        </div>
                                                    ) }
                                            </div>
                                        </div>
                                    ) ) }
                                    { ( data.levels || [] ).length === 0 && (
                                        <div className="text-sm text-gray-500">
                                            { __(
                                                'No levels found for this badge.',
                                                'dokan'
                                            ) }
                                        </div>
                                    ) }
                                </div>
                            </div>
                        </div>
                    ) }
                </div>
            }
        />
    );
}
