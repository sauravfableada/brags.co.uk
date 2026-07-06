import { SimpleInput, TextArea } from '@getdokan/dokan-ui';
import { __ } from '@wordpress/i18n';

interface ItemEditProps {
    item: {
        id?: number;
        title?: string;
        content?: string;
        rating?: number;
    };
    onChange: ( e: any ) => void;
}

const ItemEdit = ( { item, onChange }: ItemEditProps ) => {
    return (
        <div>
            { /* Header with title and rating */ }
            <div className="mb-4">
                <div className="text-xl font-bold text-gray-900 mb-2">
                    { __( 'Edit Review', 'dokan' ) }
                </div>
            </div>

            { /* Edit form content */ }
            <div className="space-y-4">
                <div>
                    <div className="block text-sm font-medium text-gray-700 mb-2">
                        { __( 'Rating', 'dokan' ) }
                    </div>
                    <div className="grid grid-cols-5 items-center gap-4">
                        { [ 1, 2, 3, 4, 5 ].map( ( star ) => (
                            <button
                                key={ star }
                                type="button"
                                className={ `flex flex-col h-24 items-center justify-center rounded-lg border-2 transition-all duration-200 ${
                                    star <= ( item.rating || 0 )
                                        ? 'border-purple-500 bg-purple-50'
                                        : 'border-gray-300 bg-white hover:border-purple-300'
                                }` }
                                onClick={ () =>
                                    onChange( {
                                        target: { name: 'rating', value: star },
                                    } )
                                }
                            >
                                <div
                                    className={ `text-2xl mb-1 ${
                                        star <= ( item.rating || 0 )
                                            ? 'text-yellow-400'
                                            : 'text-gray-400'
                                    }` }
                                >
                                    { star <= ( item.rating || 0 ) ? '★' : '☆' }
                                </div>
                                <div
                                    className={ `text-lg font-semibold ${
                                        star <= ( item.rating || 0 )
                                            ? 'text-purple-600'
                                            : 'text-gray-600'
                                    }` }
                                >
                                    { star }
                                </div>
                            </button>
                        ) ) }
                    </div>
                </div>

                <div>
                    <div className="block text-sm font-medium text-gray-700 mb-2">
                        { __( 'Title', 'dokan' ) }
                    </div>
                    <SimpleInput
                        value={ item.title || '' }
                        onChange={ onChange }
                        input={ {
                            name: 'title',
                            placeholder: __( 'Review title', 'dokan' ),
                        } }
                    />
                </div>

                <div>
                    <div className="block text-sm font-medium text-gray-700 mb-2">
                        { __( 'Content', 'dokan' ) }
                    </div>
                    <TextArea
                        value={ item.content || '' }
                        onChange={ onChange }
                        input={ {
                            placeholder: __( 'Review content', 'dokan' ),
                            id: 'review-content',
                            name: 'content',
                            rows: 6,
                        } }
                    />
                </div>
            </div>
        </div>
    );
};

export default ItemEdit;
