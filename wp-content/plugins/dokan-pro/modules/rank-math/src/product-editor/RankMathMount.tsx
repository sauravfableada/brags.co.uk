import { useRef } from '@wordpress/element';
import {
    useRankMathRepoint,
    useMetaboxAdoption,
    useLiveAnalysis,
    useRankMathSave,
} from './hooks';

type EditProps = {
    data: Record< string, any >;
    field: Record< string, any >;
    onChange: ( next: Record< string, any > ) => void;
};

// Renders Rank Math's SEO metabox; the hooks re-point and remount it per product so the SPA needs no reload.
const RankMathMount = ( { data }: EditProps ) => {
    const productId = Number( data?.id ) || 0;
    const slotRef = useRef< HTMLDivElement | null >( null );

    useRankMathRepoint( productId );
    useMetaboxAdoption( productId, slotRef );
    useLiveAnalysis( productId, data );
    useRankMathSave( productId );

    // Hidden bridges Rank Math's jQuery analyser reads — same role as the legacy template's inputs.
    return (
        <div className="dokan-rank-math-mount">
            <input
                type="hidden"
                id="post_name"
                value={ data?.slug ?? '' }
                readOnly
            />
            <input
                type="hidden"
                id="title"
                value={ data?.name ?? '' }
                readOnly
            />
            <input
                type="hidden"
                id="post_content"
                value={ data?.description ?? '' }
                readOnly
            />
            <input
                type="hidden"
                id="post_excerpt"
                value={ data?.short_description ?? '' }
                readOnly
            />
            <div ref={ slotRef } />
        </div>
    );
};

export default RankMathMount;
