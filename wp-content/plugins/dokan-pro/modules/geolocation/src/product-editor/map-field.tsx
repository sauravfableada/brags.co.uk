import domReady from '@wordpress/dom-ready';
import { addFilter } from '@wordpress/hooks';
import { useEffect, useRef, useCallback, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
// @ts-ignore
import { CustomField } from '@dokan/product-editor';
import { SimpleCheckbox, SimpleInput } from '@getdokan/dokan-ui';
// @ts-ignore
import { DokanButton, DokanAlert } from '@dokan/components';
import { LocateFixed } from 'lucide-react';

declare const google: any;
declare const mapboxgl: any;
declare const MapboxGeocoder: any;

interface MapConfig {
    map_source: 'google_maps' | 'mapbox';
    api_key?: string;
    access_token?: string;
    default_location: {
        latitude: number;
        longitude: number;
        address?: string;
    };
    map_zoom: number;
}

interface GeoValue {
    latitude: string;
    longitude: string;
    address: string;
    use_store_settings: boolean;
    store_has_settings: boolean;
    store_settings_url: string;
}

/**
 * Google Maps implementation for the product editor map field.
 */
const GoogleMapField = ( {
    config,
    value,
    onLocationChange,
}: {
    config: MapConfig;
    value: GeoValue;
    onLocationChange: ( lat: string, lng: string, address: string ) => void;
} ) => {
    const mapRef = useRef< HTMLDivElement | null >( null );
    const mapInstanceRef = useRef< any >( null );
    const markerRef = useRef< any >( null );
    const geocoderRef = useRef< any >( null );
    const inputRef = useRef< HTMLInputElement | null >( null );

    const lat =
        parseFloat( value.latitude ) || config.default_location.latitude;
    const lng =
        parseFloat( value.longitude ) || config.default_location.longitude;

    useEffect( () => {
        if ( ! mapRef.current || typeof google === 'undefined' ) {
            return;
        }

        const curpoint = new google.maps.LatLng( lat, lng );

        const map = new google.maps.Map( mapRef.current, {
            center: curpoint,
            zoom: config.map_zoom || 13,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
        } );

        const marker = new google.maps.Marker( {
            position: curpoint,
            map,
            draggable: true,
        } );

        const geocoder = new google.maps.Geocoder();

        mapInstanceRef.current = map;
        markerRef.current = marker;
        geocoderRef.current = geocoder;

        // Place autocomplete on the address input.
        if ( inputRef.current ) {
            const autocomplete = new google.maps.places.Autocomplete(
                inputRef.current
            );
            autocomplete.addListener( 'place_changed', () => {
                const place = autocomplete.getPlace();
                if ( place?.geometry?.location ) {
                    const newLat = place.geometry.location.lat();
                    const newLng = place.geometry.location.lng();
                    updateMap( newLat, newLng, place.formatted_address );
                }
            } );
        }

        // Click on map.
        map.addListener( 'click', ( e: any ) => {
            updateMap( e.latLng.lat(), e.latLng.lng() );
        } );

        // Drag marker.
        marker.addListener( 'dragend', ( e: any ) => {
            updateMap( e.latLng.lat(), e.latLng.lng() );
        } );
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [] );

    const updateMap = useCallback(
        ( newLat: number, newLng: number, formattedAddress?: string ) => {
            const curpoint = new google.maps.LatLng( newLat, newLng );

            if ( mapInstanceRef.current ) {
                mapInstanceRef.current.setCenter( curpoint );
            }
            if ( markerRef.current ) {
                markerRef.current.setPosition( curpoint );
            }

            if ( formattedAddress ) {
                if ( inputRef.current ) {
                    inputRef.current.value = formattedAddress;
                }
                onLocationChange(
                    String( newLat ),
                    String( newLng ),
                    formattedAddress
                );
            } else {
                // Reverse geocode to get address.
                geocoderRef.current?.geocode(
                    { location: { lat: newLat, lng: newLng } },
                    ( results: any[], status: string ) => {
                        const addr =
                            status === 'OK' && results[ 0 ]
                                ? results[ 0 ].formatted_address
                                : '';
                        if ( inputRef.current ) {
                            inputRef.current.value = addr;
                        }
                        onLocationChange(
                            String( newLat ),
                            String( newLng ),
                            addr
                        );
                    }
                );
            }
        },
        [ onLocationChange ]
    );

    return (
        <div className="flex flex-col gap-2.5">
            <div className="flex gap-2 items-center">
                <div
                    ref={ ( el ) => {
                        if ( el && ! inputRef.current ) {
                            inputRef.current = el.querySelector( 'input' );
                        }
                    } }
                    className="flex-1"
                >
                    <SimpleInput
                        input={ {
                            placeholder: __(
                                'Search for a location…',
                                'dokan'
                            ),
                            defaultValue: value.address || '',
                        } }
                    />
                </div>
                <DokanButton
                    variant="secondary"
                    className="flex items-center justify-center w-[38px] h-[38px] p-0 shrink-0"
                    title={ __( 'Use my location', 'dokan' ) }
                    onClick={ () => {
                        if ( navigator.geolocation ) {
                            navigator.geolocation.getCurrentPosition(
                                ( pos ) => {
                                    updateMap(
                                        pos.coords.latitude,
                                        pos.coords.longitude
                                    );
                                }
                            );
                        }
                    } }
                >
                    <LocateFixed size={ 18 } />
                </DokanButton>
            </div>
            <div
                ref={ mapRef }
                className="w-full rounded-md border border-gray-200 overflow-hidden bg-gray-100"
                style={ { height: '350px' } }
            />
        </div>
    );
};

/**
 * Mapbox implementation for the product editor map field.
 */
const MapboxField = ( {
    config,
    value,
    onLocationChange,
}: {
    config: MapConfig;
    value: GeoValue;
    onLocationChange: ( lat: string, lng: string, address: string ) => void;
} ) => {
    const mapRef = useRef< HTMLDivElement | null >( null );
    const mapInstanceRef = useRef< any >( null );
    const markerRef = useRef< any >( null );
    const geocoderRef = useRef< any >( null );
    const geocoderContainerRef = useRef< HTMLDivElement | null >( null );

    const lat =
        parseFloat( value.latitude ) || config.default_location.latitude;
    const lng =
        parseFloat( value.longitude ) || config.default_location.longitude;

    const updateMapboxLocation = useCallback(
        ( newLat: number, newLng: number ) => {
            if ( markerRef.current ) {
                markerRef.current.setLngLat( [ newLng, newLat ] );
            }
            if ( mapInstanceRef.current ) {
                mapInstanceRef.current.setCenter( [ newLng, newLat ] );
            }

            const accessToken = mapboxgl?.accessToken || config.access_token || '';
            if ( ! accessToken ) {
                onLocationChange( String( newLat ), String( newLng ), '' );
                return;
            }

            const url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${ newLng }%2C${ newLat }.json?access_token=${ accessToken }&autocomplete=true`;
            fetch( url )
                .then( ( r ) => r.json() )
                .then( ( response ) => {
                    const addr = response.features?.[ 0 ]?.place_name || '';
                    if ( geocoderRef.current && addr ) {
                        // Prevent MapboxGeocoder from flying to the place again
                        geocoderRef.current.setInput( addr );
                    }
                    onLocationChange( String( newLat ), String( newLng ), addr );
                } )
                .catch( () => {
                    onLocationChange( String( newLat ), String( newLng ), '' );
                } );
        },
        [ config.access_token, onLocationChange ]
    );

    useEffect( () => {
        if ( ! mapRef.current || typeof mapboxgl === 'undefined' ) {
            return;
        }

        mapboxgl.accessToken = config.access_token || '';

        const map = new mapboxgl.Map( {
            container: mapRef.current,
            style: 'mapbox://styles/mapbox/streets-v10',
            center: [ lng, lat ],
            zoom: config.map_zoom || 12,
        } );

        map.addControl( new mapboxgl.NavigationControl() );

        const marker = new mapboxgl.Marker( { draggable: true } )
            .setLngLat( [ lng, lat ] )
            .addTo( map );

        mapInstanceRef.current = map;
        markerRef.current = marker;

        // Geocoder control.
        if ( typeof MapboxGeocoder !== 'undefined' ) {
            const geocoder = new MapboxGeocoder( {
                accessToken: mapboxgl.accessToken,
                mapboxgl,
                zoom: map.getZoom(),
                placeholder: __( 'Search for a location…', 'dokan' ),
                marker: false,
                reverseGeocode: true,
            } );

            if ( geocoderContainerRef.current ) {
                geocoderContainerRef.current.appendChild(
                    geocoder.onAdd( map )
                );
            } else {
                map.addControl( geocoder, 'top-left' );
            }

            if ( value.address ) {
                geocoder.setInput( value.address );
            }

            geocoder.on( 'result', ( result: any ) => {
                const lngLat = result.result.center;
                const address = result.result.place_name;

                marker.setLngLat( lngLat );
                map.setCenter( lngLat );

                onLocationChange(
                    String( lngLat[ 1 ] ),
                    String( lngLat[ 0 ] ),
                    address
                );
            } );

            geocoderRef.current = geocoder;
        }

        // Marker drag.
        marker.on( 'dragend', () => {
            const lngLat = marker.getLngLat().wrap();
            updateMapboxLocation( lngLat.lat, lngLat.lng );
        } );

        return () => {
            map.remove();
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [] );

    return (
        <div className="dokan-geo-mapbox-container flex flex-col gap-2.5">
            <div className="flex gap-2 items-center">
                <div
                    ref={ geocoderContainerRef }
                    className="flex-1 [&_input]:border [&_input]:rounded [&_input]:border-(--color-border) [&_input]:bg-white [&_input]:w-full [&_input]:shadow-none [&_input]:pl-7.5 [&>div]:max-w-full [&>div]:w-full [&>div]:shadow-none"
                />
                <DokanButton
                    variant="secondary"
                    className="flex items-center justify-center w-[38px] h-[38px] p-0 shrink-0"
                    title={ __( 'Use my location', 'dokan' ) }
                    onClick={ () => {
                        if ( navigator.geolocation ) {
                            navigator.geolocation.getCurrentPosition(
                                ( pos ) => {
                                    updateMapboxLocation(
                                        pos.coords.latitude,
                                        pos.coords.longitude
                                    );
                                }
                            );
                        }
                    } }
                >
                    <LocateFixed size={ 18 } />
                </DokanButton>
            </div>
            <div
                ref={ mapRef }
                className="w-full rounded-md border border-gray-200 overflow-hidden"
                style={ { height: '350px' } }
            />
        </div>
    );
};

/**
 * Main map field edit component for the product editor.
 * Renders Google Maps or Mapbox based on the admin settings.
 */
const MapFieldEdit = ( { data, field, onChange }: any ) => {
    const mapValue: GeoValue = data[ field.id ] || {};
    const config: MapConfig = field.options || field.elements?.[ 0 ] || {};
    const [ useStoreSettings, setUseStoreSettings ] = useState(
        mapValue.use_store_settings ?? true
    );

    const handleLocationChange = useCallback(
        ( lat: string, lng: string, address: string ) => {
            onChange( {
                [ field.id ]: {
                    ...mapValue,
                    latitude: lat,
                    longitude: lng,
                    address,
                    use_store_settings: false,
                },
            } );
        },
        [ field.id, mapValue, onChange ]
    );

    const handleToggleStoreSettings = useCallback(
        ( checked: boolean ) => {
            setUseStoreSettings( checked );
            onChange( {
                [ field.id ]: {
                    ...mapValue,
                    use_store_settings: checked,
                },
            } );
        },
        [ field.id, mapValue, onChange ]
    );

    return (
        <CustomField field={ field }>
            <div className="flex flex-col gap-3">
                { /* Toggle: use store settings */ }
                <SimpleCheckbox
                    checked={ useStoreSettings }
                    onChange={ ( e ) =>
                        handleToggleStoreSettings( e.target.checked )
                    }
                    label={ __( 'Same as store', 'dokan' ) }
                    input={ {
                        id: 'dokan-geo-use-store-settings',
                    } }
                />

                { useStoreSettings && ! mapValue.store_has_settings && (
                    <DokanAlert
                        variant="info"
                        label={ __(
                            "Your store doesn't have geolocation settings.",
                            'dokan'
                        ) }
                    >
                        { mapValue.store_settings_url ? (
                            <span className="block mt-1">
                                <a
                                    href={ mapValue.store_settings_url }
                                    className="font-semibold underline"
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    { __(
                                        'Set it in your store settings',
                                        'dokan'
                                    ) }
                                </a>
                            </span>
                        ) : null }
                    </DokanAlert>
                ) }

                { /* Map (only shown when not using store settings) */ }
                { ! useStoreSettings && (
                    <>
                        { config.map_source === 'mapbox' ? (
                            <MapboxField
                                config={ config }
                                value={ mapValue }
                                onLocationChange={ handleLocationChange }
                            />
                        ) : (
                            <GoogleMapField
                                config={ config }
                                value={ mapValue }
                                onLocationChange={ handleLocationChange }
                            />
                        ) }
                    </>
                ) }
            </div>
        </CustomField>
    );
};

domReady( () => {
    addFilter(
        'dokan_product_editor_ui_variant',
        'dokan_product_editor_ui_variant/map-field-filter',
        ( variants: any ) => {
            variants[ 'location_map' ] = () => ( {
                Edit: MapFieldEdit,
                type: 'text',
            } );
            return variants;
        }
    );
} );
