import React, { createContext, useCallback, useContext, useEffect, useRef, useState } from 'react';
import { Link } from '@inertiajs/react';

const SubmissionFeedbackContext = createContext(null);

const VARIANTS = {
    success: {
        icon: '✓',
        iconClass: 'bg-emerald-100 text-emerald-600 ring-emerald-50',
        buttonClass: 'bg-[#0a2342] hover:bg-slate-800',
        defaultTitle: 'Submitted Successfully',
    },
    error: {
        icon: '!',
        iconClass: 'bg-red-100 text-red-600 ring-red-50',
        buttonClass: 'bg-red-600 hover:bg-red-700',
        defaultTitle: 'Submission Unsuccessful',
    },
};

function SubmissionModal({ feedback, onClose }) {
    const [visible, setVisible] = useState(false);
    const closeButtonRef = useRef(null);

    useEffect(() => {
        if (!feedback) {
            setVisible(false);
            return;
        }

        // Next frame so the enter transition actually plays
        const frame = requestAnimationFrame(() => setVisible(true));
        closeButtonRef.current?.focus();

        const handleKeyDown = (e) => {
            if (e.key === 'Escape') onClose();
        };
        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        window.addEventListener('keydown', handleKeyDown);

        return () => {
            cancelAnimationFrame(frame);
            document.body.style.overflow = previousOverflow;
            window.removeEventListener('keydown', handleKeyDown);
        };
    }, [feedback, onClose]);

    if (!feedback) return null;

    const variant = VARIANTS[feedback.type];
    const title = feedback.title || variant.defaultTitle;

    return (
        <div
            className={`fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-opacity duration-200 ${visible ? 'opacity-100' : 'opacity-0'}`}
            onClick={onClose}
        >
            <div
                role={feedback.type === 'error' ? 'alertdialog' : 'dialog'}
                aria-modal="true"
                aria-labelledby="submission-modal-title"
                aria-describedby="submission-modal-message"
                onClick={(e) => e.stopPropagation()}
                className={`w-full max-w-sm bg-white rounded-2xl shadow-2xl p-6 md:p-8 text-center transition-all duration-200 ${visible ? 'opacity-100 translate-y-0 scale-100' : 'opacity-0 translate-y-4 scale-95'}`}
            >
                <div className={`mx-auto w-16 h-16 rounded-full ring-8 flex items-center justify-center text-3xl font-black ${variant.iconClass}`}>
                    {variant.icon}
                </div>

                <h3 id="submission-modal-title" className="mt-5 text-xl font-black text-slate-900">
                    {title}
                </h3>

                <div id="submission-modal-message" className="mt-2 text-sm text-slate-600 leading-relaxed">
                    {feedback.message && <p>{feedback.message}</p>}
                    {feedback.errors?.length > 0 && (
                        <ul className="mt-3 text-left bg-red-50 border border-red-100 rounded-lg p-3 space-y-1 list-disc pl-7 text-xs font-medium text-red-700 marker:text-red-400">
                            {feedback.errors.map((error, index) => (
                                <li key={index}>{error}</li>
                            ))}
                        </ul>
                    )}
                </div>

                <div className="mt-6 flex flex-col gap-2">
                    <button
                        ref={closeButtonRef}
                        type="button"
                        onClick={onClose}
                        className={`w-full text-white py-3 rounded-lg text-sm font-bold shadow-md transition active:scale-[0.98] ${variant.buttonClass}`}
                    >
                        {feedback.type === 'success' ? 'Done' : 'Review & Try Again'}
                    </button>
                    {feedback.type === 'success' && feedback.trackable && (
                        <Link
                            href={route('resident.tracking')}
                            onClick={onClose}
                            className="w-full py-3 rounded-lg text-sm font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition active:scale-[0.98]"
                        >
                            Track My Request
                        </Link>
                    )}
                </div>
            </div>
        </div>
    );
}

export function SubmissionFeedbackProvider({ children }) {
    const [feedback, setFeedback] = useState(null);
    const close = useCallback(() => setFeedback(null), []);

    return (
        <SubmissionFeedbackContext.Provider value={setFeedback}>
            {children}
            <SubmissionModal feedback={feedback} onClose={close} />
        </SubmissionFeedbackContext.Provider>
    );
}

/**
 * Wraps Inertia visit options so the outcome of a form submission is shown in a modal.
 *
 *   post(route('resident.documents.store'), withFeedback({ trackable: true, onSuccess: () => reset() }));
 *
 * Extra options: successTitle, successMessage (fallback when the server sends no flash),
 * errorTitle, trackable (shows a "Track My Request" link on success).
 */
export function useSubmissionFeedback() {
    const setFeedback = useContext(SubmissionFeedbackContext);

    const withFeedback = useCallback((options = {}) => {
        const { successTitle, successMessage, errorTitle, trackable = false, ...visitOptions } = options;

        // Outside a provider there is nowhere to render the modal; keep default behaviour.
        if (!setFeedback) return visitOptions;

        const showError = (message, errors = []) =>
            setFeedback({ type: 'error', title: errorTitle, message, errors });

        return {
            ...visitOptions,
            onSuccess: (page) => {
                const flash = page?.props?.flash || {};

                if (flash.error) {
                    showError(flash.error);
                } else {
                    setFeedback({
                        type: 'success',
                        title: successTitle,
                        message: flash.success || successMessage || 'Your submission has been received.',
                        trackable,
                    });
                }
                visitOptions.onSuccess?.(page);
            },
            onError: (errors) => {
                showError('Please correct the following and submit again:', Object.values(errors));
                visitOptions.onError?.(errors);
            },
            onHttpException: (response) => {
                visitOptions.onHttpException?.(response);
                showError(
                    response?.status === 419
                        ? 'Your session has expired. Please refresh the page and try again.'
                        : 'Something went wrong on our end. Please try again in a moment.'
                );
                return false;
            },
            onNetworkError: (error) => {
                visitOptions.onNetworkError?.(error);
                showError('We could not reach the barangay server. Check your internet connection and try again.');
                return false;
            },
        };
    }, [setFeedback]);

    return { withFeedback };
}
