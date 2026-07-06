import { __ } from '@wordpress/i18n';
import domReady from '@wordpress/dom-ready';
import ProductQAList from './components/ProductQAList';
import QuestionView from './components/QuestionView';

domReady( () => {
    // @ts-ignore
    window.wp.hooks.addFilter(
        'dokan-dashboard-routes',
        'dokan-pro-product-questions-answers',
        ( routes: any ) => {
            routes.push( {
                id: 'product-questions-answers',
                title: __( 'Product Questions & Answers', 'dokan' ),
                element: <ProductQAList />,
                path: 'product-questions-answers',
                exact: true,
                order: 10,
                parent: '',
            } );
            return routes;
        }
    );
    // @ts-ignore
    window.wp.hooks.addFilter(
        'dokan-dashboard-routes',
        'dokan-pro-product-questions-answers-view',
        ( routes: any ) => {
            routes.push( {
                id: 'product-questions-answers-view',
                title: __( 'Product Questions & Answers', 'dokan' ),
                element: <QuestionView />,
                path: 'product-questions-answers/:questionId',
                exact: true,
                order: 10,
                parent: '',
                backUrl: '/product-questions-answers',
            } );
            return routes;
        }
    );
} );
