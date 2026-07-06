import { Card, DokanToaster, useToast } from '@getdokan/dokan-ui';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { RawHTML, useCallback, useEffect, useState } from '@wordpress/element';
import { Answer, Question } from '../types';
// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { DokanButton, DokanModal, NotFound } from '@dokan/components';
import RichTextEditor from './RichTextEditor';
import { useSelect } from '@wordpress/data';
import QuestionViewSkeleton from './QuestionViewSkeleton';
import { redirectToEditProduct } from '../utils';

type QuestionViewProps = {
    params?: Record< string, string >;
    navigate?: ( path: string ) => void;
};

const QuestionView = ( { params, navigate }: QuestionViewProps ) => {
    const currentUser = useSelect( ( select ) => {
        // @ts-ignore
        return select( 'dokan/core' ).getCurrentUser();
    }, [] );
    const toast = useToast();
    const { questionId } = params;
    const [ question, setQuestion ] = useState< Question >( null );
    const [ isLoading, setIsLoading ] = useState< boolean >( true );
    const [ answer, setAnswer ] = useState< string >( '' );
    const [ isSaving, setIsSaving ] = useState< boolean >( false );
    const [ isConfirm, setIsConfirm ] = useState( false );
    const [ isConfirmAnswer, setIsConfirmAnswer ] = useState( false );
    const [ isEditMode, setIsEditMode ] = useState( false );

    const updateHandler = async () => {
        try {
            await apiFetch( {
                path: `/dokan/v1/product-answers/${ question.answer.id }`,
                method: 'PUT',
                data: {
                    answer,
                    user_id: currentUser.id,
                },
            } );
        } catch ( error ) {
            toast( {
                type: 'error',
                title: __( 'Failed to update answer', 'dokan' ),
            } );
        } finally {
            setIsSaving( false );
            setQuestion( ( prevState ) => ( {
                ...prevState,
                answer: { ...prevState.answer, answer },
            } ) );
            setAnswer( '' );
            toast( {
                type: 'success',
                title: __( 'Answer updated successfully', 'dokan' ),
            } );
            setIsEditMode( false ); // Exit edit mode after saving
        }
    };

    const submitAnswer = async () => {
        // Handle answer submission logic here
        if ( ! answer.trim() ) {
            return;
        }
        setIsSaving( true );
        if ( question.answer?.answer ) {
            await updateHandler();
            return;
        }
        try {
            question.answer = await apiFetch< Answer >( {
                path: '/dokan/v1/product-answers',
                method: 'POST',
                data: {
                    answer,
                    question_id: questionId,
                    user_id: currentUser.id,
                },
            } );
            toast( {
                type: 'success',
                title: __( 'Answer submitted successfully', 'dokan' ),
            } );
        } catch ( error ) {
            toast( {
                type: 'error',
                title: __( 'Failed to saved answer', 'dokan' ),
            } );
        } finally {
            setIsSaving( false );
            setAnswer( '' ); // Clear the answer input after saving
            setIsEditMode( false ); // Exit edit mode after saving
        }
    };

    const deleteHandler = async () => {
        try {
            await apiFetch( {
                path: `/dokan/v1/product-questions/${ questionId }`,
                method: 'DELETE',
            } );
            toast( {
                type: 'success',
                title: __( 'Question deleted successfully', 'dokan' ),
            } );
            navigate( '/product-questions-answers' );
        } catch ( error ) {
            // Handle error if needed
            toast( {
                type: 'error',
                title: __( 'Failed to delete question', 'dokan' ),
            } );
        }
    };

    const deleteAnswerHandler = async () => {
        try {
            await apiFetch( {
                path: `/dokan/v1/product-answers/${ question.answer.id }`,
                method: 'DELETE',
            } );
            toast( {
                type: 'success',
                title: __( 'Answer deleted successfully', 'dokan' ),
            } );
            question.answer = null;
        } catch ( error ) {
            // Handle error if needed
            toast( {
                type: 'error',
                title: __( 'Failed to delete answer', 'dokan' ),
            } );
        }
    };

    const fetchQuestionDetails = useCallback( async () => {
        setIsLoading( true );
        try {
            const response = await apiFetch< Question >( {
                path: `/dokan/v1/product-questions/${ questionId }`,
            } );
            setQuestion( response );
        } catch ( error ) {
            throw error;
        } finally {
            setIsLoading( false );
        }
    }, [ questionId ] );

    useEffect( () => {
        // eslint-disable-next-line
        fetchQuestionDetails().catch( console.error );
    }, [ questionId, fetchQuestionDetails ] );

    if ( isLoading ) {
        return <QuestionViewSkeleton />;
    }

    const NavigateToList = () => (
        <DokanButton
            variant="primary"
            onClick={ () => navigate( '/product-questions-answers' ) }
        >
            { __( 'Back to List', 'dokan' ) }
        </DokanButton>
    );

    if ( ! question ) {
        return <NotFound navigateButton={ <NavigateToList /> } />;
    }

    return (
        <>
            <div className="grid grid-cols-3 gap-4">
                <Card className="col-span-2">
                    <Card.Header>
                        <div className="font-bold">
                            { __( 'Question Details', 'dokan' ) }
                        </div>
                    </Card.Header>
                    <Card.Body>
                        <div className="divide-y divide-gray-200">
                            <div className="pb-2 flex gap-4">
                                <strong className="w-32">
                                    { __( 'Product:', 'dokan' ) }
                                </strong>
                                <div className="flex gap-2">
                                    <img
                                        src={ question.product.image }
                                        alt={ question.product.title }
                                        className="w-14 h-14 rounded object-cover flex-shrink-0"
                                    />
                                    <a
                                        href={ redirectToEditProduct(
                                            String( question?.product.id )
                                        ) }
                                    >
                                        { question?.product.title }
                                    </a>
                                </div>
                            </div>
                            <div className="py-2 flex gap-4">
                                <strong className="w-32">
                                    { __( 'Questioner:', 'dokan' ) }
                                </strong>
                                <span>{ question?.user_display_name }</span>
                            </div>
                            <div className="py-2 flex gap-4 min-w-0">
                                <strong className="w-32 shrink-0">
                                    { __( 'Question:', 'dokan' ) }
                                </strong>
                                <span className="flex-1 min-w-0 break-words">
                                    { question?.question }
                                </span>
                            </div>
                        </div>
                    </Card.Body>
                </Card>
                <Card className="col-span-1">
                    <Card.Header>
                        <div className="font-bold">
                            { __( 'Status', 'dokan' ) }
                        </div>
                    </Card.Header>
                    <Card.Body>
                        <div className="space-y-2">
                            <div>
                                <strong>{ __( 'Created:', 'dokan' ) }</strong>{ ' ' }
                                { question?.human_readable_created_at }
                            </div>
                            <div>
                                <strong>
                                    { __( 'Last Updated:', 'dokan' ) }
                                </strong>{ ' ' }
                                { question?.human_readable_updated_at }
                            </div>
                        </div>
                        <DokanButton
                            className="mt-4"
                            variant="secondary"
                            onClick={ () => setIsConfirm( true ) }
                            label={ __( 'Delete Question', 'dokan' ) }
                        />
                    </Card.Body>
                </Card>
            </div>
            <Card className="mt-4">
                <Card.Header>
                    <div className="font-bold">{ __( 'Answer', 'dokan' ) }</div>
                </Card.Header>
                <Card.Body className="flex flex-col gap-4">
                    { isEditMode || ! question.answer?.answer ? (
                        <>
                            <RichTextEditor
                                value={ answer }
                                onChange={ setAnswer }
                            />
                            <div className="flex justify-end">
                                <DokanButton
                                    variant="primary"
                                    label={ __( 'Save', 'dokan' ) }
                                    loading={ isSaving }
                                    disabled={ isSaving || ! answer.trim() }
                                    onClick={ submitAnswer }
                                />
                            </div>
                        </>
                    ) : (
                        <div>
                            <RawHTML>{ question.answer?.answer }</RawHTML>
                            <div className="text-sm text-gray-500 mt-2 flex gap-1">
                                <span>{ __( 'Answered by:', 'dokan' ) } </span>
                                <span className="font-bold">
                                    { question.answer?.user_display_name }
                                </span>
                                <span>{ __( 'on', 'dokan' ) } </span>
                                <span className="font-bold">
                                    { question.answer?.created_at }
                                </span>
                            </div>
                            <div className="flex gap-2 mt-4">
                                <DokanButton
                                    variant="secondary"
                                    label={ __( 'Edit', 'dokan' ) }
                                    onClick={ () => {
                                        setAnswer( question.answer?.answer );
                                        setIsEditMode( true );
                                    } }
                                />
                                <DokanButton
                                    variant="danger"
                                    label={ __( 'Delete', 'dokan' ) }
                                    onClick={ () => setIsConfirmAnswer( true ) }
                                />
                            </div>
                        </div>
                    ) }
                </Card.Body>
            </Card>
            <DokanModal
                isOpen={ isConfirm }
                namespace="question-delete"
                onClose={ () => setIsConfirm( false ) }
                dialogTitle={ __( 'Delete Question', 'dokan' ) }
                onConfirm={ deleteHandler }
                confirmationTitle={ __(
                    'Are you sure you want to delete this question?',
                    'dokan'
                ) }
                confirmationDescription={ __(
                    'This question will be permanently deleted.',
                    'dokan'
                ) }
            />
            <DokanModal
                isOpen={ isConfirmAnswer }
                namespace="question-delete"
                onClose={ () => setIsConfirmAnswer( false ) }
                dialogTitle={ __( 'Delete Answer', 'dokan' ) }
                onConfirm={ deleteAnswerHandler }
                confirmationTitle={ __(
                    'Are you sure you want to delete this answer?',
                    'dokan'
                ) }
                confirmationDescription={ __(
                    'This question will be permanently deleted.',
                    'dokan'
                ) }
            />
            <DokanToaster />
        </>
    );
};

export default QuestionView;
