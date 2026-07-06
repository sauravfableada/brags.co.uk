import { useRef } from '@wordpress/element';
import * as LucideIcons from 'lucide-react';
import { __ } from '@wordpress/i18n';
import { DokanButton } from '@dokan/components';

interface UploadImageProps {
    src?: string;
    showButton?: boolean;
    buttonLabel?: string;
    croppingWidth?: number;
    croppingHeight?: number;
    onUploadedImage?: ( image: { src: string; id: number } ) => void;
    children?: React.ReactNode;
    className?: string;
}

interface CroppedImage {
    url: string;
    id: number;
}

interface FileAttachment {
    url?: string;
    id?: number;
}

function UploadImage( {
    src = '',
    showButton = false,
    buttonLabel = __( 'Upload', 'dokan' ),
    croppingWidth,
    croppingHeight,
    onUploadedImage,
    children,
    className = '',
}: UploadImageProps ) {
    const fileFrameRef = useRef< any >( null );

    const getDefaultImageSrc = () => {
        return (
            ( window as any ).dokan?.urls?.assetsUrl +
                '/images/store-pic.png' || ''
        );
    };

    const onSelectImage = ( selectedImage: CroppedImage | FileAttachment ) => {
        const newImage = {
            src: selectedImage.url || '',
            id: selectedImage.id || 0,
        };

        if ( onUploadedImage ) {
            onUploadedImage( newImage );
        }
    };

    const calculateImageSelectOptions = function (
        attachment: any,
        controller: any
    ) {
        const dokan = ( window as any ).dokan || {};
        const xInit = croppingWidth
            ? parseInt( String( croppingWidth ), 10 )
            : parseInt( dokan.store_banner_dimension?.width || '500', 10 );
        const yInit = croppingHeight
            ? parseInt( String( croppingHeight ), 10 )
            : parseInt( dokan.store_banner_dimension?.height || '300', 10 );
        const flexWidth = !! parseInt(
            dokan.store_banner_dimension?.[ 'flex-width' ] || '0',
            10
        );
        const flexHeight = !! parseInt(
            dokan.store_banner_dimension?.[ 'flex-height' ] || '0',
            10
        );

        const realWidth = attachment.get( 'width' );
        const realHeight = attachment.get( 'height' );

        const control = controller.get( 'control' );

        controller.set(
            'canSkipCrop',
            ! control.mustBeCropped(
                flexWidth,
                flexHeight,
                xInit,
                yInit,
                realWidth,
                realHeight
            )
        );

        const imgSelectOptions = {
            handles: true,
            keys: true,
            instance: true,
            persistent: true,
            imageWidth: realWidth,
            imageHeight: realHeight,
            x1: 0,
            y1: 0,
            x2: xInit,
            y2: yInit,
            aspectRatio: xInit + ':' + yInit,
            maxHeight: yInit,
            maxWidth: xInit,
        };

        return imgSelectOptions;
    };

    const openMediaManager = (
        callback: ( image: CroppedImage | FileAttachment ) => void
    ) => {
        const wp = ( window as any ).wp;
        const dokan = ( window as any ).dokan || {};

        if ( ! wp || ! wp.media ) {
            // WordPress media library is not available
            return;
        }

        if ( fileFrameRef.current ) {
            fileFrameRef.current.open();
            return;
        }

        const fileStatesOptions = {
            library: wp.media.query(),
            multiple: false,
            title: 'Select & Crop Image',
            priority: 20,
            filterable: 'uploaded',
            autoSelect: true,
            suggestedWidth: 500,
            suggestedHeight: 300,
        };

        const cropControl: any = {
            id: 'control-id',
            params: {
                width: croppingWidth
                    ? parseInt( String( croppingWidth ), 10 )
                    : parseInt(
                          dokan.store_banner_dimension?.width || '500',
                          10
                      ),
                height: croppingHeight
                    ? parseInt( String( croppingHeight ), 10 )
                    : parseInt(
                          dokan.store_banner_dimension?.height || '300',
                          10
                      ),
                flex_width: !! parseInt(
                    dokan.store_banner_dimension?.[ 'flex-width' ] || '0',
                    10
                ),
                flex_height: !! parseInt(
                    dokan.store_banner_dimension?.[ 'flex-height' ] || '0',
                    10
                ),
            },
        };

        cropControl.mustBeCropped = function (
            flexW: boolean,
            flexH: boolean,
            dstW: number,
            dstH: number,
            imgW: number,
            imgH: number
        ) {
            // If the width and height are both flexible
            // then the user does not need to crop the image.
            if ( true === flexW && true === flexH ) {
                return false;
            }

            // If the width is flexible and the cropped image height matches the current image height,
            // then the user does not need to crop the image.
            if ( true === flexW && dstH === imgH ) {
                return false;
            }

            // If the height is flexible and the cropped image width matches the current image width,
            // then the user does not need to crop the image.
            if ( true === flexH && dstW === imgW ) {
                return false;
            }

            // If the cropped image width matches the current image width,
            // and the cropped image height matches the current image height
            // then the user does not need to crop the image.
            if ( dstW === imgW && dstH === imgH ) {
                return false;
            }

            // If the destination width is equal to or greater than the cropped image width
            // then the user does not need to crop the image...
            if ( imgW <= dstW ) {
                return false;
            }

            return true;
        };

        const fileStates = [
            new wp.media.controller.Library( fileStatesOptions ),
            new wp.media.controller.CustomizeImageCropper( {
                imgSelectOptions: calculateImageSelectOptions,
                control: cropControl,
            } ),
        ];

        const mediaOptions = {
            title: 'Select Image',
            button: {
                text: 'Select Image',
                close: false,
            },
            multiple: false,
            states: fileStates,
        };

        fileFrameRef.current = wp.media( mediaOptions );

        fileFrameRef.current.on( 'select', () => {
            fileFrameRef.current.setState( 'cropper' );
        } );

        fileFrameRef.current.on( 'cropped', ( croppedImage: CroppedImage ) => {
            callback( croppedImage );
            fileFrameRef.current = null;
        } );

        fileFrameRef.current.on( 'skippedcrop', () => {
            const selection = fileFrameRef.current.state().get( 'selection' );

            const files = selection.map( ( attachment: any ) => {
                return attachment.toJSON();
            } );

            const file = files.pop();

            callback( file );

            fileFrameRef.current = null;
        } );

        fileFrameRef.current.on( 'close', () => {
            fileFrameRef.current = null;
        } );

        fileFrameRef.current.on( 'ready', () => {
            fileFrameRef.current.uploader.options.uploader.params = {
                type: 'dokan-vendor-option-media',
            };
        } );

        fileFrameRef.current.open();
    };

    const uploadImage = () => {
        openMediaManager( onSelectImage );
    };

    const displaySrc = src || getDefaultImageSrc();

    return (
        <>
            { children ? (
                <div
                    onClick={ uploadImage }
                    onKeyDown={ ( e ) => {
                        if ( e.key === 'Enter' || e.key === ' ' ) {
                            e.preventDefault();
                            uploadImage();
                        }
                    } }
                    role="button"
                    tabIndex={ 0 }
                    className={ className + ' cursor-pointer' }
                >
                    { children }
                </div>
            ) : (
                <div
                    className={ `flex items-center gap-[22.46px] ${ className } cursor-pointer` }
                >
                    <div
                        className="w-[92.5px] h-[92.5px] border border-[#E9E9E9] rounded-full flex items-center justify-center"
                        onClick={ uploadImage }
                        onKeyDown={ ( e ) => {
                            if ( e.key === 'Enter' || e.key === ' ' ) {
                                e.preventDefault();
                                uploadImage();
                            }
                        } }
                        role="button"
                        tabIndex={ 0 }
                    >
                        { displaySrc ? (
                            <img
                                src={ displaySrc }
                                alt="Upload"
                                className="w-[43px] h-auto object-contain"
                            />
                        ) : (
                            <LucideIcons.Image
                                size={ 43 }
                                className="text-gray-400"
                            />
                        ) }
                    </div>
                    { showButton && (
                        <DokanButton
                            variant="secondary"
                            onClick={ () => {
                                uploadImage();
                            } }
                            className="shadow-none !border !border-[#E9E9E9] !ring-[#E9E9E9] h-[28px] w-[98px] !p-0"
                        >
                            <LucideIcons.Upload
                                size={ 16 }
                                className="!text-[#828282]"
                                color="#828282"
                            />
                            <span className="!text-[#25252D] font-[500] text-[12px] leading-[16px]">
                                { buttonLabel }
                            </span>
                        </DokanButton>
                    ) }
                </div>
            ) }
        </>
    );
}

UploadImage.Placeholder = () => {
    return (
        <div className="flex items-center gap-[22.46px]">
            <div className="w-[92.5px] h-[92.5px] border border-[#E9E9E9] rounded-full flex items-center justify-center">
                <div>
                    <svg
                        width="43"
                        height="47"
                        viewBox="0 0 43 47"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            d="M38.8627 0H4.12737C1.84541 0 0 1.91962 0 4.31914V30.933C0 32.4353 0.734195 33.8333 1.94462 34.6261L19.1982 45.9248C20.5277 46.8011 22.2144 46.8011 23.5538 45.9352L41.0355 34.6261C42.2559 33.8333 43 32.4353 43 30.9225V4.31914C43 1.91962 41.1546 0 38.8627 0Z"
                            fill="#BCBCBC"
                        />
                        <circle
                            cx="21.8583"
                            cy="20.4208"
                            r="11.1083"
                            fill="#9C9C9C"
                        />
                    </svg>
                </div>
            </div>
            <DokanButton
                variant="secondary"
                disabled
                className="shadow-none !bg-[#F1F1F4] !border-none !outline-0 !ring-0 !text-[#A5A5AA]"
                size="sm"
                label={ __( 'Upload', 'dokan' ) }
            />
        </div>
    );
};

export default UploadImage;
