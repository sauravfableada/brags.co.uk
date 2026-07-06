import { useEffect } from '@wordpress/element';
// @ts-ignore — @wordpress/api-fetch ships with WordPress.
import apiFetch from '@wordpress/api-fetch';
import { w, pollUntil, getRepointDispatch, isRankMathLoaded } from './utils';

// Re-point Rank Math at this product: re-seed the store and remount the section, no reload.
export const useRankMathRepoint = ( productId: number ) => {
    useEffect( () => {
        if ( ! productId ) {
            return;
        }
        let cancelled = false;

        // Remember this as the current edit-post so a full reload re-localizes it.
        apiFetch( {
            path: `/dokan/v2/rank-math/${ productId }/store-current-editable-post`,
            method: 'POST',
        } ).catch( () => {} );

        const repoint = ( dispatch: any ) => {
            // Skeleton only when re-syncing an already-loaded section; toggling mid-boot throws.
            const wasLoaded = isRankMathLoaded();
            if ( wasLoaded ) {
                dispatch.toggleLoaded?.( false );
            }
            apiFetch( {
                path: `/dokan/v2/rank-math/${ productId }/editor-data`,
            } )
                .then( ( payload: any ) => {
                    if ( cancelled ) {
                        return;
                    }
                    if ( payload ) {
                        // Keep site-level keys; override only this product's values.
                        const merged = { ...w.rankMath, ...payload };
                        w.rankMath = merged;
                        dispatch.resetStore( merged );
                        dispatch.updatePostID( productId ); // post-id change remounts the section
                    }
                } )
                .catch( () => {} )
                .finally( () => {
                    // Hide the skeleton only on the live, non-cancelled repoint.
                    if ( ! cancelled && wasLoaded ) {
                        dispatch.toggleLoaded?.( true );
                    }
                } );
        };

        const cancelPoll = pollUntil( getRepointDispatch, repoint );
        return () => {
            cancelled = true;
            cancelPoll();
        };
    }, [ productId ] );
};
