import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useRef, useState } from '@wordpress/element';
import {
    DokanStripeExpressBlockData,
    DokanStripeExpressPRData,
} from '../types';

// Inline Tax Input Form component (no modal, no imperative API)
const TaxInputForm = ( {
    setTaxId = ( id ) => id,
    settings,
}: {
    setTaxId: ( id: string ) => void;
    settings: DokanStripeExpressPRData | DokanStripeExpressBlockData;
} ) => {
    const [ value, setValue ] = useState( '' );
    const [ saved, setSaved ] = useState( false );
    const inputRef = useRef< HTMLInputElement >( null );

    useEffect( () => {
        // focus the input on mount for quick entry
        inputRef.current?.focus();
    }, [] );

    const dispatchChange = ( taxID: string ) => {
        try {
            window.dispatchEvent(
                new CustomEvent( 'dokan:stripe:tax-id:change', {
                    detail: { taxID },
                } )
            );
        } catch ( _e ) {
            // noop
        }
    };

    const onSave = () => {
        const taxID = value.trim();
        dispatchChange( taxID );
        setTaxId( taxID );
        setSaved( true );
        // also mirror into a hidden input so non-React code can read it
        const hiddenId = 'dokan-stripe-tax-id-hidden';
        let hidden = document.getElementById(
            hiddenId
        ) as HTMLInputElement | null;
        if ( ! hidden ) {
            hidden = document.createElement( 'input' );
            hidden.type = 'hidden';
            hidden.id = hiddenId;
            hidden.name = 'dokan_stripe_tax_id';
            document.body.appendChild( hidden );
        }
        hidden.value = taxID;
        // We do not submit here; Express flow controls submission.
        setTimeout( () => setSaved( false ), 2000 );
    };

    if ( ! settings.euCompliance.needTaxId ) {
        return;
    }

    return (
        <div
            className="dokan-stripe-express-payment-method-root dokan-stripe-tax-inline-form"
            style={ { marginBottom: '1rem', display: 'flex', gap: '0.5rem' } }
        >
            <div
                style={ {
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '0.5rem',
                    flex: 1,
                    minWidth: 0,
                } }
            >
                <label
                    htmlFor="dokan-tax-id"
                    className="block text-sm font-medium"
                >
                    { settings.euCompliance.taxIDFieldTitle }
                </label>
                <input
                    id="dokan-tax-id"
                    ref={ inputRef }
                    type="text"
                    className="w-full dokan-form-control"
                    placeholder={ sprintf(
                        // translators: %s: Tax ID field title
                        __( 'Enter your %s', 'dokan' ),
                        settings.euCompliance.taxIDFieldTitle
                    ) }
                    value={ value }
                    onChange={ ( e: any ) => setValue( e.target.value ) }
                    onKeyDown={ ( e: any ) => {
                        if ( e.key === 'Enter' ) {
                            e.preventDefault();
                            onSave();
                        }
                    } }
                />
            </div>
            <div
                style={ {
                    display: 'flex',
                    gap: '0.5rem',
                    flex: 0,
                    width: 'fit-content',
                    alignItems: 'end',
                } }
            >
                <button
                    type="button"
                    className="button alt"
                    style={ {
                        height: 'fit-content',
                    } }
                    onClick={ onSave }
                >
                    { saved ? __( 'Saved', 'dokan' ) : __( 'Save', 'dokan' ) }
                </button>
            </div>
        </div>
    );
};

export default TaxInputForm;
