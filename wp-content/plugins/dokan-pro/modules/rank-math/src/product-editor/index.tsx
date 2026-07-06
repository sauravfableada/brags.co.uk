import domReady from '@wordpress/dom-ready';
import { addFilter } from '@wordpress/hooks';
import { createElement, useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import RankMathMount from './RankMathMount';
import RankMathSkeleton from './RankMathSkeleton';
import './index.scss';

// Reads a hidden bridge input that RankMathMount renders for the current product.
const inputValue = ( id: string ) =>
    ( document.getElementById( id ) as HTMLInputElement | null )?.value ?? '';

// Wraps Rank Math's metabox App so React remounts it per product: it's keyed by
// post id (its widgets mount once and never re-read the store), a skeleton shows
// while data syncs, and the freshly mounted App re-syncs title/slug + analyses.
const withRemount = ( App: any ) =>
    function DokanRankMathSection() {
        const { postId, loaded } = useSelect( ( select: any ) => {
            const store = select( 'rank-math' );
            return {
                postId: store?.getPostID?.() ?? 0,
                loaded: store?.isLoaded?.() ?? false,
            };
        }, [] );

        // Runs on the live App: feed title/slug to the collector (SERP + permalink) and analyse.
        useEffect( () => {
            if ( ! loaded ) {
                return;
            }
            const rm = ( window as any ).rankMathEditor;
            const dc = rm?.assessor?.dataCollector;
            dc?.handleTitleChange?.( inputValue( 'title' ) );
            dc?.handleSlugChange?.( inputValue( 'post_name' ) );
            rm?.refresh?.( 'init' );
        }, [ postId, loaded ] );

        return loaded
            ? createElement( App, { key: postId } )
            : createElement( RankMathSkeleton );
    };

// Priority 99 runs after Rank Math's own filter, so we wrap the real App (not the `{}` default).
addFilter( 'rank_math_app', 'dokan-pro/rank-math/remount', withRemount, 99 );

// Registers the `rank_math_seo` variant so the Product Form Manager renders our mount component.
domReady( () => {
    addFilter(
        'dokan_product_editor_ui_variant',
        'dokan-pro/rank-math/variant',
        ( variants: Record< string, any > ) => {
            variants.rank_math_seo = () => ( {
                Edit: RankMathMount,
                type: 'text',
            } );
            return variants;
        }
    );
} );
