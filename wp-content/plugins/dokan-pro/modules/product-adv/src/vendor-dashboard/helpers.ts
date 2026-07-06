import { __ } from '@wordpress/i18n';
import type { AdvLocalized } from './types';

export const advData = (): AdvLocalized =>
    ( window as any ).dokan_purchase_advertisement ?? {};

export function wpAjaxPost(
    action: string,
    data: Record< string, unknown >
): Promise< any > {
    return ( window as any ).wp.ajax.post( action, data );
}

export function extractErrorMessage( err: unknown ): string {
    const e = err as any;
    return (
        e?.message ??
        e?.responseJSON?.data?.message ??
        e?.responseJSON?.message ??
        e?.responseText ??
        __( 'Something went wrong.', 'dokan' )
    );
}
