// @ts-ignore
// eslint-disable-next-line
import { RichText } from '@dokan/components';

type RichTextEditorProps = {
    value: string;
    onChange: ( value: string ) => void;
};

const RichTextEditor = ( { value, onChange }: RichTextEditorProps ) => {
    return (
        <RichText
            className="min-h-64"
            input={ {
                id: 'rich-text-editor',
                placeholder: 'Type something...',
            } }
            value={ value }
            onChange={ onChange }
        />
    );
};

export default RichTextEditor;
