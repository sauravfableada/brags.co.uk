import { twMerge } from 'tailwind-merge';
import { RawHTML, useState } from '@wordpress/element';
import { humanTimeDiff } from '@wordpress/date';
import { __ } from '@wordpress/i18n';
import { DokanModal, DokanLink } from '@dokan/components';

function NoteItem( { note, deleteNote } ) {
    const [ isOpenDeleteNote, setIsOpenDeleteNote ] = useState( false );

    return (
        <div key={ note.id } className="group">
            <div
                className={ twMerge(
                    'p-3 rounded-md rounded-bl-none',
                    note.customer_note ? 'bg-[#D7CAD2]' : 'bg-gray-100'
                ) }
            >
                <RawHTML>{ note.note }</RawHTML>
            </div>
            <div className="flex flex-row justify-between items-center mt-1 text-xs text-gray-500">
                <span>{ humanTimeDiff( note.date_created, new Date() ) }</span>
                &nbsp;
                <DokanLink
                    href="#"
                    onClick={ ( e ) => {
                        e.preventDefault();
                        setIsOpenDeleteNote( true );
                    } }
                >
                    { __( 'Delete note', 'dokan' ) }
                </DokanLink>
                <DokanModal
                    isOpen={ isOpenDeleteNote }
                    namespace={ `dokan-delete-subscription-note-${ note.id }` }
                    onConfirm={ () => deleteNote( note ) }
                    onClose={ () => setIsOpenDeleteNote( false ) }
                    dialogTitle={ __( 'Delete Subscription Note', 'dokan' ) }
                    cancelButtonText={ __( 'Close', 'dokan' ) }
                    confirmButtonText={ __( 'Yes, Delete', 'dokan' ) }
                    confirmationTitle={ __(
                        'Are you sure you want to proceed?',
                        'dokan'
                    ) }
                    confirmationDescription={ __(
                        'Do you want to proceed deleting this subscription note?',
                        'dokan'
                    ) }
                />
            </div>
        </div>
    );
}

export default NoteItem;
