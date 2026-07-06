import { Card } from '@getdokan/dokan-ui';
import { DownloadPermission } from '../Types';
import { useState } from '@wordpress/element';
import { twMerge } from 'tailwind-merge';
import { __, _n } from '@wordpress/i18n';
import { DateTimeHtml, DokanModal, DokanButton } from '@dokan/components';

type ProType = {
    downloadableProduct: DownloadPermission;
    revoke: ( downloadableProduct: DownloadPermission ) => void;
};

function DownloasableItem( { downloadableProduct, revoke }: ProType ) {
    const [ collapse, setCollapse ] = useState( false );
    const [ isRevoke, setIsRevoke ] = useState( false );

    const handleRevoke = () => {
        revoke( downloadableProduct );
    };

    return (
        <>
            <DokanModal
                isOpen={ isRevoke }
                namespace={ `dokan-delete-subscription-note-${ downloadableProduct?.download_id }` }
                onConfirm={ handleRevoke }
                onClose={ () => setIsRevoke( false ) }
                dialogTitle={ __( 'Revoke Access', 'dokan' ) }
                confirmationTitle={ __(
                    'Are you sure you want to proceed?',
                    'dokan'
                ) }
                confirmationDescription={ __(
                    'Do you want to proceed revoking access to this download?',
                    'dokan'
                ) }
                confirmButtonText={ __( 'Yes, Revoke', 'dokan' ) }
                cancelButtonText={ __( 'Close', 'dokan' ) }
            />
            <Card>
                <Card.Header className="p-3">
                    <Card.Title className="m-0 p-0 cursor-pointer text-sm">
                        <div className="flex justify-between flex-nowrap">
                            <div
                                className="flex items-center break-all"
                                onClick={ () => setCollapse( ! collapse ) }
                            >
                                { `#${ downloadableProduct?.product?.id ?? '' } - ${ downloadableProduct.product.name } - ${ downloadableProduct.file_data.name } - ${ downloadableProduct.file_data.file_title }` }
                            </div>
                            <div className="flex flex-row gap-2 text-sm">
                                <div
                                    className="flex items-center justify-center p-2"
                                    onClick={ () => setCollapse( ! collapse ) }
                                >
                                    <i
                                        className={ twMerge(
                                            'fas fa-sort-up',
                                            collapse ? 'rotate-0' : 'rotate-180'
                                        ) }
                                    ></i>
                                </div>
                                <div className="flex items-center justify-center">
                                    <DokanButton
                                        onClick={ () => setIsRevoke( true ) }
                                    >
                                        { __( 'Revoke', 'dokan' ) }
                                    </DokanButton>
                                </div>
                            </div>
                        </div>
                    </Card.Title>
                </Card.Header>
                { collapse && (
                    <Card.Body className="flex flex-col">
                        <div className="flex flex-row">
                            <label
                                htmlFor={ `${ downloadableProduct.download_id }-time` }
                                className="font-bold"
                            >
                                { __( 'Downloaded:', 'dokan' ) }
                            </label>
                            &nbsp;
                            <p
                                id={ `${ downloadableProduct.download_id }-time` }
                            >
                                { downloadableProduct.download_count }&nbsp;
                                { _n(
                                    'time',
                                    'times',
                                    Number( downloadableProduct.download_count )
                                ) }
                            </p>
                        </div>
                        <div className="flex flex-row">
                            <label
                                htmlFor={ `${ downloadableProduct.download_id }-remain` }
                                className="font-bold"
                            >
                                { __( 'Downloads Remaining:', 'dokan' ) }
                            </label>
                            &nbsp;
                            <p
                                id={ `${ downloadableProduct.download_id }-remain` }
                            >
                                { downloadableProduct.downloads_remaining }
                            </p>
                        </div>
                        <div className="flex flex-row">
                            <label
                                htmlFor={ `${ downloadableProduct.download_id }-expire` }
                                className="font-bold"
                            >
                                { __( 'Access Expires:', 'dokan' ) }
                            </label>
                            &nbsp;
                            <p
                                id={ `${ downloadableProduct.download_id }-expire` }
                            >
                                <DateTimeHtml.Date
                                    date={ downloadableProduct.access_expires }
                                />
                            </p>
                        </div>
                    </Card.Body>
                ) }
            </Card>
        </>
    );
}

export default DownloasableItem;
