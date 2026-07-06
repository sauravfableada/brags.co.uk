import { useLayoutEffect } from '@wordpress/element';
import { w, pollUntil } from './utils';

type SlotRef = { current: HTMLDivElement | null };

// Adopt the footer metabox into our slot; useLayoutEffect parks it back before React drops the slot (else blank on revisit).
export const useMetaboxAdoption = ( productId: number, slotRef: SlotRef ) => {
    useLayoutEffect( () => {
        const slot = slotRef.current;
        if ( ! productId || ! slot ) {
            return;
        }
        let home: HTMLElement | null = null;

        const adopt = ( { wrap, dc }: any ) => {
            if ( ! home ) {
                home = wrap.parentElement;
            }
            if ( wrap.parentElement !== slot ) {
                slot.appendChild( wrap );
            }
            const $ = w.jQuery;
            if ( dc && $ ) {
                dc.elemContent = $( '#post_content' );
                dc.elemDescription = $( '#post_excerpt' );
                dc.elemSlug = $( '#post_name' );
                dc.elemTitle = $( '#title' );
            }
        };

        const cancelPoll = pollUntil( () => {
            const wrap = document.getElementById( 'rank-math-metabox-wrapper' );
            const dc = w.rankMathEditor?.assessor?.dataCollector;
            return wrap && dc ? { wrap, dc } : null;
        }, adopt );

        return () => {
            cancelPoll();
            const wrap = document.getElementById( 'rank-math-metabox-wrapper' );
            if ( wrap && home && wrap.parentElement !== home ) {
                home.appendChild( wrap );
            }
        };
    }, [ productId, slotRef ] );
};
