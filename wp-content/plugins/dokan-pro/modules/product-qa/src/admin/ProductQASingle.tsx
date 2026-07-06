import domReady from '@wordpress/dom-ready';
import React, { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { DokanButton, DokanModal, RichText } from '@dokan/components';
import { DokanToaster, useToast, Card } from '@getdokan/dokan-ui';
import { ChevronLeft, Pen, Trash, Trash2, Eye, EyeOff, Calendar, Box } from 'lucide-react';
import { RawHTML } from '@wordpress/element';

interface Vendor {
    id: number;
    name: string;
    avatar: string;
}

interface Product {
    id: number;
    title: string;
    image: string;
}

interface Answer {
    id: number;
    answer: string;
    user_display_name?: string;
    human_readable_updated_at?: string;
}

interface Question {
    id: number;
    question: string;
    status: string;
    read: boolean;
    human_readable_created_at: string;
    human_readable_updated_at: string;
    created_at: string;
    updated_at: string;
    user_display_name: string;
    product: Product;
    vendor: Vendor;
    answer: Answer;
}
// Reusable dialog content component
const DialogContent = ({ icon: Icon, title, iconBgColor = "bg-[#FDECEE]", iconColor = "text-[#E25A5A]" }) => (
    <div className="flex items-start gap-4">
        {/* Icon Area */}
        <div className={`w-14 h-14 rounded-full ${iconBgColor} flex items-center justify-center`}>
            <Icon className={`w-7 h-7 ${iconColor}`} strokeWidth={2} />
        </div>
        {/* Title Text */}
        <h2 className="text-[18px] leading-6 font-semibold text-[#1A1A1A] pt-4 pr-8">
            {title}
        </h2>
    </div>
);
const ProductQASingle = ({ 
    navigate, 
    ...props
}: { 
    navigate?: (path: string) => void;
}) => {
    const toast = useToast();
    const id = props.params?.id || 0;
    
    const [loading, setLoading] = useState<boolean>(false);
    const [questionEditMode, setQuestionEditMode] = useState<boolean>(false);
    const [answerEditMode, setAnswerEditMode] = useState<boolean>(false);
    const [isQuestionUpdating, setIsQuestionUpdating] = useState<boolean>(false);
    const [isQuestionVisibilityUpdating, setIsQuestionVisibilityUpdating] = useState<boolean>(false);
    const [isQuestionDeleting, setIsQuestionDeleting] = useState<boolean>(false);
    const [isAnswerUpdating, setIsAnswerUpdating] = useState<boolean>(false);
    const [isAnswerDeleting, setIsAnswerDeleting] = useState<boolean>(false);
    const [showDeleteQuestionModal, setShowDeleteQuestionModal] = useState<boolean>(false);
    const [showDeleteAnswerModal, setShowDeleteAnswerModal] = useState<boolean>(false);
    const [showVisibilityModal, setShowVisibilityModal] = useState<boolean>(false);
    const [targetVisibility, setTargetVisibility] = useState<string>('');
    const [questionHovered, setQuestionHovered] = useState<boolean>(false);
    const [answerHovered, setAnswerHovered] = useState<boolean>(false);

    const [question, setQuestion] = useState<Question>({
        id: 0,
        question: '',
        status: '',
        read: false,
        human_readable_created_at: '',
        human_readable_updated_at: '',
        created_at: '',
        updated_at: '',
        user_display_name: '',
        product: { id: 0, title: '', image: '' },
        vendor: { id: 0, name: '', avatar: '' },
        answer: { id: 0, answer: '' },
    });

    const [editedQuestion, setEditedQuestion] = useState<string>('');
    const [editedAnswer, setEditedAnswer] = useState<string>('');

    const fetchQuestion = async () => {
        setLoading(true);
        try {
            const response: Question = await apiFetch({
                path: `/dokan/v1/product-questions/${id}`,
            });

            setQuestion(response);
            setEditedQuestion(response.question);
            setEditedAnswer(response.answer?.answer || '');

            // If unanswered, automatically go to answer edit mode
            if (!response.answer?.id) {
                setAnswerEditMode(true);
            }

            // Mark as read if unread
            if (!response.read) {
                await apiFetch({
                    path: `/dokan/v1/product-questions/${id}`,
                    method: 'PUT',
                    data: { read: true },
                });
            }
        } catch (error: any) {
            toast({
                type: 'error',
                title: error?.message || __('Failed to load question', 'dokan'),
            });
        } finally {
            setLoading(false);
        }
    };

    const saveQuestion = async () => {
        setIsQuestionUpdating(true);
        try {
            const response: Question = await apiFetch({
                path: `/dokan/v1/product-questions/${id}`,
                method: 'POST',
                data: { question: editedQuestion },
            });

            setQuestion(response);
            setEditedQuestion(response.question);
            setQuestionEditMode(false);
            toast({
                type: 'success',
                title: __('Question updated successfully', 'dokan'),
            });
        } catch (error: any) {
            toast({
                type: 'error',
                title: error?.message || __('Failed to update question', 'dokan'),
            });
        } finally {
            setIsQuestionUpdating(false);
        }
    };

    const changeVisibilityStatus = async () => {
        setIsQuestionVisibilityUpdating(true);
        try {
            const response: Question = await apiFetch({
                path: `/dokan/v1/product-questions/${id}`,
                method: 'PUT',
                data: { status: targetVisibility },
            });

            setQuestion(response);
            toast({
                type: 'success',
                title: __('Visibility status changed successfully', 'dokan'),
            });
        } catch (error: any) {
            toast({
                type: 'error',
                title: error?.message || __('Failed to change visibility', 'dokan'),
            });
        } finally {
            setIsQuestionVisibilityUpdating(false);
            setShowVisibilityModal(false);
        }
    };

    const deleteQuestion = async () => {
        setIsQuestionDeleting(true);
        try {
            await apiFetch({
                path: `/dokan/v1/product-questions/${id}`,
                method: 'DELETE',
            });

            toast({
                type: 'success',
                title: __('Question deleted successfully', 'dokan'),
            });
            navigate('/product-qa');
        } catch (error: any) {
            toast({
                type: 'error',
                title: error?.message || __('Failed to delete question', 'dokan'),
            });
        } finally {
            setIsQuestionDeleting(false);
            setShowDeleteQuestionModal(false);
        }
    };

    const saveAnswer = async () => {
        setIsAnswerUpdating(true);
        try {
            let response: Answer;
            
            if (question.answer.id) {
                // Update existing answer
                response = await apiFetch({
                    path: `/dokan/v1/product-answers/${question.answer.id}`,
                    method: 'PUT',
                    data: { answer: editedAnswer },
                });
            } else {
                // Create new answer
                response = await apiFetch({
                    path: '/dokan/v1/product-answers',
                    method: 'POST',
                    data: { 
                        answer: editedAnswer,
                        question_id: question.id 
                    },
                });
            }

            setQuestion({ ...question, answer: response });
            setEditedAnswer(response.answer);
            setAnswerEditMode(false);
            toast({
                type: 'success',
                title: question.answer.id 
                    ? __('Answer updated successfully', 'dokan')
                    : __('Answer created successfully', 'dokan'),
            });
            await fetchQuestion();
        } catch (error: any) {
            toast({
                type: 'error',
                title: error?.message || __('Failed to save answer', 'dokan'),
            });
        } finally {
            setIsAnswerUpdating(false);
        }
    };

    const deleteAnswer = async () => {
        setIsAnswerDeleting(true);
        try {
            await apiFetch({
                path: `/dokan/v1/product-answers/${question.answer.id}`,
                method: 'DELETE',
            });

            toast({
                type: 'success',
                title: __('Answer deleted successfully', 'dokan'),
            });
            await fetchQuestion();
        } catch (error: any) {
            toast({
                type: 'error',
                title: error?.message || __('Failed to delete answer', 'dokan'),
            });
        } finally {
            setIsAnswerDeleting(false);
            setShowDeleteAnswerModal(false);
        }
    };

    useEffect(() => {
        if (id) {
            fetchQuestion();
        }
    }, [id]);

    const vendorUrl = (vendorId: number) => {
        return `/wp-admin/admin.php?page=dokan-dashboard#/vendors/${vendorId}`;
    };

    const productUrl = (productId: number) => {
        return `/wp-admin/post.php?post=${productId}&action=edit`;
    };

    return (
        <div className="dokan-product-qa-single">
            {/* Back Button */}
            <button
                onClick={() => navigate('/product-qa')}
                className="flex items-center gap-1 text-gray-600 hover:text-gray-900 mb-4 text-sm"
            >
                <ChevronLeft size={16} />
                {__('Product Q&A', 'dokan')}
            </button>

            {/* Header */}
            <h1 className="text-2xl font-bold mb-6">
                {__('Product Question & Answer Details', 'dokan')}
            </h1>

            {/* Main Layout */}
            <div className="flex flex-col-reverse md:flex-row md:justify-between gap-4 rounded-md">
                {/* Left Side - Question and Answer */}
                <div className="md:w-3/4">
                    {/* Product Section */}
                    <Card className="bg-white shadow-sm">
                        <div className="p-4 border-b">
                            <h3 className="text-[12px] font-semibold text-[#828282] uppercase mb-3">
                                {__('PRODUCT', 'dokan')}
                            </h3>
                            <div className="flex items-center gap-3">
                                <img
                                    src={question.product.image}
                                    alt={question.product.title}
                                    className="w-11 h-11 rounded object-cover"
                                />
                                <div>
                                    <a
                                        href={productUrl(question.product.id)}
                                        className="text-sm font-medium text-[#25252D] hover:text-[#7047EB] block"
                                    >
                                        <span className="text-[#25252D] hover:text-[#7047EB] mb-2 text-[14px]">
                                            {question.product.title}
                                        </span>
                                    </a>
                                    <p className="text-[12px] text-[#828282] mr-[10px]">
                                        {__('VENDOR:', 'dokan')}{' '}
                                        <a
                                            href={vendorUrl(question.vendor.id)}
                                            className="text-gray-700 hover:text-indigo-600"
                                        >
                                            <span className="text-[12px] text-[#25252D] hover:text-[#7047EB]">
                                                {question.vendor.name}
                                            </span>
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        { /* Question Content */ }
                        <div className="relative group">
                            <div className="p-4 flex justify-between items-start">
                                <h3 className="text-base font-bold">
                                    {__('Question', 'dokan')}
                                </h3>

                                {!questionEditMode && (
                                    <button
                                        onClick={() => setQuestionEditMode(true)}
                                        className="
                                            hidden
                                            group-hover:flex
                                            items-center
                                            gap-1
                                            text-sm
                                            text-gray-600
                                            hover:text-gray-900
                                        "
                                    >
                                        <Pen size={14} />
                                        {__('Edit', 'dokan')}
                                    </button>
                                )}
                            </div>
                            <div className="pl-4 pb-4 border-b">
                                {questionEditMode ? (
                                    <div>
                                        <textarea
                                            value={editedQuestion}
                                            onChange={(e) => setEditedQuestion(e.target.value)}
                                            rows={4}
                                            className="block w-full mb-4 rounded-md border border-gray-300 p-2 text-sm focus:ring-2 focus:ring-indigo-600 focus:border-transparent"
                                        />
                                        <div className="flex gap-2">
                                            <DokanButton
                                                variant="primary"
                                                onClick={saveQuestion}
                                                disabled={isQuestionUpdating}
                                                loading={isQuestionUpdating}
                                                label={__('Save', 'dokan')}
                                            />
                                            <DokanButton
                                                variant="secondary"
                                                onClick={() => {
                                                    setQuestionEditMode(false);
                                                    setEditedQuestion(question.question);
                                                }}
                                                label={__('Cancel', 'dokan')}
                                            />
                                        </div>
                                    </div>
                                ) : (
                                    <div>
                                        <p className="prose max-w-none mb-3 text-[#25252D]">
                                            {question.question}
                                        </p>
                                        <p className="text-xs text-[#828282]">
                                            {__('Customer:', 'dokan')} {question.user_display_name}
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>
                        {/* Answer Section */}
                        <div className="relative group">
                            <div className="p-4 flex justify-between items-start">
                                <h3 className="text-base font-bold">
                                    {__('Answer', 'dokan')}
                                </h3>

                                {!answerEditMode && question.answer.id > 0 && (
                                    <div
                                        className="
                                            hidden
                                            group-hover:flex
                                            items-center
                                            gap-3
                                        "
                                    >
                                        <button
                                            onClick={() => setAnswerEditMode(true)}
                                            className="flex items-center gap-1 text-sm text-gray-600 hover:text-gray-900"
                                        >
                                            <Pen size={14} />
                                            {__('Edit', 'dokan')}
                                        </button>
                                        <button
                                            onClick={() => setShowDeleteAnswerModal(true)}
                                            className="flex items-center gap-1 text-sm text-gray-600 hover:text-gray-900"
                                        >
                                            <Trash2 size={14} />
                                            {__('Delete', 'dokan')}
                                        </button>
                                    </div>
                                )}
                            </div>

                            <div className="pl-4 pb-4">
                                {answerEditMode || question.answer.id === 0 ? (
                                    <div>
                                        <RichText
                                            value={editedAnswer}
                                            onChange={setEditedAnswer}
                                            placeholder={__('Enter your answer…', 'dokan')}
                                            readOnly={isAnswerUpdating}
                                        />
                                        <div className="flex gap-2 mt-4">
                                            <DokanButton
                                                variant="primary"
                                                onClick={saveAnswer}
                                                disabled={isAnswerUpdating || !editedAnswer.trim()}
                                                loading={isAnswerUpdating}
                                                label={__('Save', 'dokan')}
                                            />
                                            {question.answer.id > 0 && (
                                                <DokanButton
                                                    variant="secondary"
                                                    onClick={() => {
                                                        setAnswerEditMode(false);
                                                        setEditedAnswer(question.answer.answer);
                                                    }}
                                                    label={__('Cancel', 'dokan')}
                                                />
                                            )}
                                        </div>
                                    </div>
                                ) : (
                                    <div>
                                        <div className="prose max-w-none mb-3 text-[#25252D]">
                                            <RawHTML>{question.answer.answer}</RawHTML>
                                        </div>
                                        <p className="text-xs text-[#828282]">
                                            {__('by', 'dokan')}{' '}
                                            {question.answer.user_display_name}{' '}
                                            {__(' | ', 'dokan')}{' '}
                                            {question.answer.human_readable_updated_at}
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </Card>
                </div>

                {/* Right Side - Status & Actions */}
                <div className="md:w-1/4">
                    <Card className="bg-white rounded-md shadow-sm">
                        {/* Status Badge */}
                        <div className="border-b p-4">
                            <div className="flex items-center justify-between mb-7">
                                <h3 className="text-sm font-semibold">
                                    {__('Status', 'dokan')}
                                </h3>
                                <span
                                    className={`inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ${
                                        question.status === 'visible'
                                            ? 'bg-[#D4FBEF] text-[#00563F] ring-1 ring-inset ring-green-600/20'
                                            : 'bg-[#F0F0F0] text-[#575757] ring-1 ring-inset ring-gray-600/20'
                                    }`}
                                >
                                    {question.status === 'visible'
                                        ? __('Visible', 'dokan')
                                        : __('Hidden', 'dokan')}
                                </span>
                            </div>
                            <div className="flex items-start gap-2 mb-4">
                                <Calendar size={15} className="text-gray-500 mt-0.5" />
                                <div className="flex flex-row gap-1 text-[#828282]">
                                    <span className="text-xs">
                                        {__('Created:', 'dokan')}
                                    </span>
                                    <span className="text-xs">
                                        {question.human_readable_created_at}
                                    </span>
                                </div>
                            </div>
                            <div className="flex items-start gap-2">
                                <Calendar size={15} className="text-gray-500 mt-0.5" />
                                <div className="flex flex-row gap-1 text-[#828282] mb-4">
                                    <span className="text-xs">
                                        {__('Last Updated:', 'dokan')}
                                    </span>
                                    <span className="text-xs">
                                        {question.human_readable_updated_at}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Actions */}
                        <div className="p-4">
                            <h3 className="text-sm font-semibold mb-4">
                                {__('Actions', 'dokan')}
                            </h3>
                            <div className="space-y-2">
                                <button
                                    onClick={() => {
                                        setTargetVisibility(
                                            question.status === 'visible' ? 'hidden' : 'visible'
                                        );
                                        setShowVisibilityModal(true);
                                    }}
                                    disabled={isQuestionVisibilityUpdating}
                                    className="w-full flex items-center gap-2 text-sm text-gray-700 hover:text-gray-900 mb-5"
                                >
                                    {question.status === 'visible' ? (
                                        <EyeOff size={16} />
                                    ) : (
                                        <Eye size={16} />
                                    )}
                                    {question.status === 'visible'
                                        ? __('Hide from product page', 'dokan')
                                        : __('Show in product page', 'dokan')}
                                </button>

                                <button
                                    onClick={() => setShowDeleteQuestionModal(true)}
                                    disabled={isQuestionDeleting}
                                    className="w-full flex items-center gap-2 text-sm text-red-600 hover:text-red-700"
                                >
                                    <Trash size={16} />
                                    {__('Delete Entire Q&A', 'dokan')}
                                </button>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>

            {/* Modals */}
            {showDeleteQuestionModal && (
                <DokanModal
                    isOpen={showDeleteQuestionModal}
                    namespace="delete-question-modal"
                    onClose={() => setShowDeleteQuestionModal(false)}
                    onConfirm={deleteQuestion}
                    dialogTitle={__('Delete Question', 'dokan')}
                    confirmButtonText={__('Yes, Delete', 'dokan')}
                    dialogHeader={false}
                    dialogContent={
                        <DialogContent 
                            icon={Trash}
                            title={__('Are you sure you want to delete the Question?', 'dokan')}
                        />
                    }
                    confirmButtonVariant="danger"
                />
            )}

            {showDeleteAnswerModal && (
                <DokanModal
                    isOpen={showDeleteAnswerModal}
                    namespace="delete-answer-modal"
                    onClose={() => setShowDeleteAnswerModal(false)}
                    onConfirm={deleteAnswer}
                    confirmButtonText={__('Yes, Delete', 'dokan')}
                    dialogHeader={false}
                    dialogContent={
                        <DialogContent 
                            icon={Trash}
                            title={__('Are you sure you want to delete the Answer?', 'dokan')}
                        />
                    }
                    confirmButtonVariant="danger"
                />
            )}

            {showVisibilityModal && (
                <DokanModal
                    isOpen={showVisibilityModal}
                    namespace="visibility-modal"
                    onClose={() => setShowVisibilityModal(false)}
                    onConfirm={changeVisibilityStatus}
                    confirmButtonText={__('Proceed', 'dokan')}
                    dialogHeader={false}
                    dialogContent={
                        <DialogContent 
                            icon={EyeOff}
                            title={__('Are you sure you want to change the Question visibility?', 'dokan')}
                        />
                    }
                    confirmButtonVariant="primary"
                />
            )}

            <DokanToaster />
        </div>
    );
};

export default ProductQASingle;
