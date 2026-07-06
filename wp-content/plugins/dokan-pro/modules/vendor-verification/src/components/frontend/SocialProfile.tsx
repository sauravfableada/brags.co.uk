import { Card } from '@getdokan/dokan-ui';
import { __ } from '@wordpress/i18n';
import { SocialProvider } from '../../frontend/types';
// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { DokanAlert, DokanButton } from '@dokan/components';

type SocialProfileProps = {
    providers: SocialProvider[];
};
const SocialProfile = ( { providers }: SocialProfileProps ) => {
    const connectedProviders = providers.filter(
        ( provider ) => provider.is_connected
    );
    const disconnectedProviders = providers.filter(
        ( provider ) => ! provider.is_connected
    );
    return (
        <Card>
            <Card.Header>
                <h4 className="text-base font-medium p-0 m-0">
                    { __( 'Social Profiles', 'dokan' ) }
                </h4>
            </Card.Header>
            <Card.Body>
                { providers.length === 0 && (
                    <div className="[&_p]:m-0">
                        <DokanAlert variant="info">
                            { __(
                                'No Social App is configured by website Admin.',
                                'dokan'
                            ) }
                        </DokanAlert>
                    </div>
                ) }
                <div className="flex flex-wrap gap-4">
                    { connectedProviders.map( ( provider, index ) => (
                        <Card key={ index }>
                            <Card.Body>
                                <h4 className="text-2xl underline font-bold p-0 mb-4">
                                    { provider.title }
                                </h4>
                                <div className="grid grid-cols-3 gap-4">
                                    <div className="h-32 w-32 rounded-lg border border-solid border-gray-200">
                                        <img
                                            src={ provider.photo_url }
                                            alt={ provider.title }
                                            className="object-cover"
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <a
                                            href={ provider.profile_url }
                                            target="_blank"
                                            rel="noreferrer"
                                        >
                                            { provider.display_name }
                                        </a>
                                        <div>{ provider.email }</div>
                                    </div>
                                    <DokanButton
                                        className="h-max"
                                        variant="secondary"
                                        onClick={ () =>
                                            ( window.location.href =
                                                provider.disconnect_url )
                                        }
                                    >
                                        { __( 'Disconnect', 'dokan' ) }{ ' ' }
                                        { provider.title }
                                    </DokanButton>
                                </div>
                            </Card.Body>
                        </Card>
                    ) ) }
                    { disconnectedProviders.map( ( provider, index ) => (
                        <DokanButton
                            key={ index }
                            variant="secondary"
                            onClick={ () =>
                                ( window.location.href = provider.connect_url )
                            }
                        >
                            { __( 'Connect', 'dokan' ) } { provider.title }
                        </DokanButton>
                    ) ) }
                </div>
            </Card.Body>
        </Card>
    );
};

export default SocialProfile;
