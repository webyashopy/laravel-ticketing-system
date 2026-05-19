import { forwardRef, useId, type InputHTMLAttributes } from 'react';

import { cn } from '../lib/cn';

/**
 * DaisyUI v5 checkbox primitiv balíčku.
 *
 * Vlastní kopie — NEimportuje z host `@/components/ui/*`. 100% DaisyUI
 * sémantické třídy. Label je svázaný s inputem přes `htmlFor`.
 */
export interface CheckboxProps
    extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> {
    label?: string;
    description?: string;
    error?: string;
    indeterminate?: boolean;
}

const Checkbox = forwardRef<HTMLInputElement, CheckboxProps>(
    (
        { className, label, description, error, indeterminate, id, ...props },
        ref,
    ) => {
        // Použij `id` z propů, jinak vygeneruj přes useId() pro svázání labelu.
        const generatedId = useId();
        const inputId = id ?? generatedId;

        return (
            <div className="flex flex-col gap-1">
                {/*
                  DaisyUI v5: label svázaný s inputem přes htmlFor; checkbox
                  je inline vedle textu (klik na text přepne stav).
                */}
                <label
                    className="flex cursor-pointer items-start gap-3"
                    htmlFor={inputId}
                >
                    <input
                        ref={(el) => {
                            if (typeof ref === 'function') ref(el);
                            else if (ref) ref.current = el;
                            // `indeterminate` nejde nastavit přes atribut → ref.
                            if (el) el.indeterminate = indeterminate ?? false;
                        }}
                        id={inputId}
                        type="checkbox"
                        className={cn(
                            'checkbox',
                            error && 'checkbox-error',
                            className,
                        )}
                        {...props}
                    />
                    {label && (
                        <span className="text-sm font-medium">
                            {label}
                            {description && (
                                <span className="block font-normal text-base-content/70">
                                    {description}
                                </span>
                            )}
                        </span>
                    )}
                </label>
                {error && <p className="text-xs text-error">{error}</p>}
            </div>
        );
    },
);

Checkbox.displayName = 'Checkbox';

export { Checkbox };
