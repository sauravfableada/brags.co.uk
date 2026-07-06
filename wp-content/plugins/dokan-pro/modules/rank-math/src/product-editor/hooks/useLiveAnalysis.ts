import { useEffect } from '@wordpress/element';
import { w, isRankMathLoaded } from './utils';

// Sync live edits into the analyser so the score updates as the vendor types; skip mid-remount (the remount re-syncs).
export const useLiveAnalysis = (
    productId: number,
    data: Record< string, any >
) => {
    useEffect( () => {
        if ( ! productId ) {
            return;
        }
        const rm = w.rankMathEditor;
        const dc = rm?.assessor?.dataCollector;
        if ( ! dc || ! isRankMathLoaded() ) {
            return;
        }
        // Set values directly — React updates the value attribute without firing an `input` event.
        dc.handleTitleChange?.( data?.name ?? '' );
        dc.handleSlugChange?.( data?.slug ?? '' );
        if ( typeof dc.getExcerpt === 'function' ) {
            dc._data.excerpt = dc.getExcerpt();
        }
        if ( typeof dc.getContent === 'function' ) {
            dc._data.content = dc.getContent();
        }
        rm?.refresh?.( 'content' );
    }, [
        productId,
        data?.name,
        data?.slug,
        data?.short_description,
        data?.description,
    ] );
};
