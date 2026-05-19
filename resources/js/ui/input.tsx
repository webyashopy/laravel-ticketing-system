import { forwardRef, useId, type InputHTMLAttributes, type ReactNode } from 'react';

import { cn } from '../lib/cn';

/**
 * DaisyUI v5 input primitiv balíčku.
 *
 * Vlastní kopie — NEimportuje z host `@/components/ui/*`. Používá výhradně
 * DaisyUI sémantické třídy (`input`, `input-error`, `text-error`, …),
 * takže barvy čte runtime z CSS proměnných host aplikace (BrandingProvider).
 */
export interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
    label?: string;
    description?: string;
    error?: string;
    withAsterisk?: boolean;
    leftSection?: ReactNode;
    rightSection?: ReactNode;
}

const Input = forwardRef<HTMLInputElement, InputProps>(
    (
        {
            className,
            label,
            description,
            error,
            withAsterisk,
            leftSection,
            rightSection,
            id,
            ...props
        },
        ref,
    ) => {
        // Použij `id` z propů, jinak vygeneruj přes useId() pro svázání labelu.
        const generatedId = useId();
        const inputId = id ?? generatedId;

        return (
            <div className="flex w-full flex-col gap-1">
                {label && (
                    <label className="text-sm font-medium" htmlFor={inputId}>
                        {label}
                        {withAsterisk && <span className="ml-1 text-error">*</span>}
                    </label>
                )}
                {description && (
                    <p className="text-xs text-base-content/70">{description}</p>
                )}
                <div className="relative">
                    {leftSection && (
                        <div className="pointer-events-none absolute inset-y-0 left-0 z-10 flex items-center pl-3">
                            {leftSection}
                        </div>
                    )}
                    <input
                        ref={ref}
                        id={inputId}
                        className={cn(
                            'input w-full',
                            error && 'input-error',
                            leftSection && 'pl-10',
                            rightSection && 'pr-10',
                            className,
                        )}
                        {...props}
                    />
                    {rightSection && (
                        <div className="absolute inset-y-0 right-0 flex items-center pr-3">
                            {rightSection}
                        </div>
                    )}
                </div>
                {error && <p className="text-xs text-error">{error}</p>}
            </div>
        );
    },
);

Input.displayName = 'Input';

// TextInput jako alias pro Input (kompatibilita s původním API).
const TextInput = Input;

export { Input, TextInput };
