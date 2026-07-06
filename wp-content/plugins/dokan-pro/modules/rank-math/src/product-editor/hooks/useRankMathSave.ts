import { useEffect } from '@wordpress/element';
import { addAction, removeAction } from '@wordpress/hooks';
import { w } from './utils';

// After-save action exposed by Lite's `useProductEditor.submitHandler`.
const AFTER_SAVE_ACTION = 'dokan_product_editor_after_save';
const SAVE_NAMESPACE = 'dokan-pro/rank-math/save';

// Persist Rank Math's meta/schemas/redirection once Lite has saved the product.
export const useRankMathSave = ( productId: number ) => {
    useEffect( () => {
        if ( ! productId ) {
            return;
        }
        const onSaved = ( savedId: number ) => {
            if ( savedId !== productId ) {
                return;
            }
            const assessor = w.rankMathEditor?.assessor;
            if ( ! assessor ) {
                return;
            }
            const meta = assessor.saveMeta?.() ?? Promise.resolve();
            const schemas = assessor.saveSchemas?.( meta ) ?? meta;
            assessor.saveRedirection?.( schemas );
        };
        addAction( AFTER_SAVE_ACTION, SAVE_NAMESPACE, onSaved );
        return () => removeAction( AFTER_SAVE_ACTION, SAVE_NAMESPACE );
    }, [ productId ] );
};
