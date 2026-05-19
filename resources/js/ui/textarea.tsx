import { forwardRef, useId, type TextareaHTMLAttributes } from 'react';

import { cn } from '../lib/cn';

/**
 * DaisyUI v5 textarea primitiv balíčku.
 *
 * Vlastní kopie — NEimportuje z host `@/components/ui/*`. 100% DaisyUI
 * sémantické třídy.
 */
export interface TextareaProps
    extends TextareaHTMLAttributes<HTMLTextAreaElement> {
    label?: string;
    description?: string;
    error?: string;
    withAsterisk?: boolean;
    minRows?: number;
    autosize?: boolean;
}

const Textarea = forwardRef<HTMLTextAreaElement, TextareaProps>(
    (
        {
            className,
            label,
            description,
            error,
            withAsterisk,
            minRows = 3,
            autosize,
            id,
            ...props
        },
        ref,
    ) => {
        // Použij `id` z propů, jinak vygeneruj přes useId() pro svázání labelu.
        const generatedId = useId();
        const textareaId = id ?? generatedId;

        return (
            <div className="flex w-full flex-col gap-1">
                {label && (
                    <label className="text-sm font-medium" htmlFor={textareaId}>
                        {label}
                        {withAsterisk && <span className="ml-1 text-error">*</span>}
                    </label>
                )}
                {description && (
                    <p className="text-xs text-base-content/70">{description}</p>
                )}
                <textarea
                    ref={ref}
                    id={textareaId}
                    rows={minRows}
                    className={cn(
                        'textarea w-full',
                        error && 'textarea-error',
                        autosize && 'resize-none',
                        className,
                    )}
                    {...props}
                />
                {error && <p className="text-xs text-error">{error}</p>}
            </div>
        );
    },
);

Textarea.displayName = 'Textarea';

export { Textarea };
