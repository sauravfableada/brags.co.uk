import { twMerge } from 'tailwind-merge';

import { useEffect, useState } from '@wordpress/element';

import { StatusFilterSkeleton } from './StatusFilterSkeleton';
import { useStatusFilters } from '../../hooks/useStatusFilters';
import { StatusNavigationProps } from '../../../types/store-support';

export default function StatusFilter( {
    statusParam,
    loadFilters = false,
    onChange,
    onLoadComplete,
}: StatusNavigationProps ) {
    // Add a separate loading state for this component
    const [ isStatusFilterLoading, setIsStatusFilterLoading ] =
        useState< boolean >( false );
    const { statusCounts, isLoading, fetchStatusCounts } = useStatusFilters();

    useEffect( () => {
        const doFetch = async () => {
            if ( loadFilters ) {
                setIsStatusFilterLoading( true );
                await fetchStatusCounts();
                setIsStatusFilterLoading( false );
                onLoadComplete();
            }
        };

        void doFetch();
    }, [ fetchStatusCounts, loadFilters, onLoadComplete ] );

    // Show skeleton during both internal loading states
    if ( isLoading || isStatusFilterLoading || ! statusCounts ) {
        return <StatusFilterSkeleton />;
    }

    return (
        <nav className="flex items-center" role="navigation">
            { statusCounts.map( ( filter, index ) => (
                <div
                    key={ `${ filter.key }-${ index }` }
                    className="flex items-center"
                >
                    <span
                        onClick={ () => onChange( filter.key ) }
                        className={ twMerge(
                            'text-dokan-link text-xs transition-all cursor-pointer',
                            statusParam === filter.key
                                ? 'font-bold'
                                : 'font-normal'
                        ) }
                        aria-current={
                            statusParam === filter.key ? 'page' : undefined
                        }
                        role="button"
                        tabIndex={ 0 }
                        onKeyDown={ ( e ) => {
                            if ( e.key === 'Enter' || e.key === ' ' ) {
                                onChange( filter.key );
                            }
                        } }
                    >
                        { filter.label }{ ' ' }
                        { filter.count > 0 ? `(${ filter.count })` : '' }
                    </span>

                    { index < statusCounts.length - 1 && (
                        <div className="border-r h-3 mx-1" aria-hidden="true" />
                    ) }
                </div>
            ) ) }
        </nav>
    );
}
