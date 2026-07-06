import { ApiResponse, FetchError } from '../Types';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { useEffect, useState } from '@wordpress/element';

export interface Link {
    href: string;
}

export interface Links {
    self: Link[];
    collection: Link[];
    up: Link[];
}

export interface SubscriptionNote {
    id: number;
    author: string;
    date_created: string; // ISO 8601 date string
    date_created_gmt: string; // ISO 8601 date string in GMT
    note: string;
    customer_note: boolean;
    _links: Links;
}

export type SubscriptionNotes = SubscriptionNote[];

export const fetchNotes = async (
    subscriptionId: string | number
): Promise< ApiResponse > => {
    const response: Record< any, any > = await apiFetch( {
        method: 'GET',
        path: addQueryArgs(
            `/dokan/v1/product-subscriptions/${ subscriptionId }/notes`,
            {}
        ),
        parse: true,
    } );

    const body = response;
    return { body };
};
export const deleteNotes = async (
    orderId: string | number,
    note: SubscriptionNote
): Promise< ApiResponse > => {
    const response: Record< any, any > = await apiFetch( {
        method: 'DELETE',
        path: addQueryArgs(
            `/dokan/v1/product-subscriptions/${ orderId }/notes/${ note.id }`,
            {
                force: true,
            }
        ),
        parse: false,
    } );

    const body: SubscriptionNotes = await response.json();
    return { body };
};
export const createNote = async (
    orderId: string | number,
    payload: Record< any, any >
): Promise< ApiResponse > => {
    const response: Record< any, any > = await apiFetch( {
        method: 'POST',
        path: addQueryArgs(
            `/dokan/v1/product-subscriptions/${ orderId }/notes`,
            {
                ...payload,
            }
        ),
        parse: false,
    } );

    const body: SubscriptionNotes = await response.json();
    return { body };
};

export const useNotes = ( subscriptionId: string | number ) => {
    const [ data, setData ] = useState< SubscriptionNotes >( [] ); // Replace 'any' with your data type
    const [ isLoading, setIsLoading ] = useState< boolean >( false );
    const [ error, setError ] = useState< FetchError | null >( null );

    const loadData = async () => {
        setIsLoading( true );
        setError( null );

        try {
            const response = await fetchNotes( subscriptionId );

            setData( response.body );
        } catch ( err ) {
            setError( {
                message:
                    err instanceof Error
                        ? err.message
                        : 'An error occurred while fetching data',
            } );

            // @ts-ignore
            console.error( 'Error fetching data:', err );
        } finally {
            setIsLoading( false );
        }
    };

    const deleteSubscriptionNotes = ( note: SubscriptionNote ) => {
        return deleteNotes( subscriptionId, note );
    };

    const createSubscriptionNote = ( payload: Record< any, any > ) => {
        return createNote( subscriptionId, payload );
    };

    useEffect( () => {
        loadData();
    }, [ subscriptionId ] );

    const refresh = () => {
        loadData();
    };

    return {
        data,
        setData,
        isLoading,
        error,
        refresh,
        deleteSubscriptionNotes,
        createSubscriptionNote,
    };
};
