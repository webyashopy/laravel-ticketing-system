import { forwardRef, useId, type SelectHTMLAttributes } from 'react';

import { cn } from '../lib/cn';

/**
 * DaisyUI v5 select primitiv balíčku.
 *
 * Vlastní kopie — NEimportuje z host `@/components/ui/*`. 100% DaisyUI
 * sémantické třídy.
 */
export interface SelectOption {
    value: string;
    label: string;
    disabled?: boolean;
}

export interface SelectProps
    extends Omit<SelectHTMLAttributes<HTMLSelectElement>, 'data'> {
    label?: string;
    description?: string;
    error?: string;
    withAsterisk?: boolean;
    data: (string | SelectOption)[];
    placeholder?: string;
}

const Select = forwardRef<HTMLSelectElement, SelectProps>(
    (
        {
            className,
            label,
            description,
            error,
            withAsterisk,
            data,
            placeholder,
            id,
            ...props
        },
        ref,
    ) => {
        const normalizedData: SelectOption[] = data.map((item) =>
            typeof item === 'string' ? { value: item, label: item } : item,
        );

        // Použij `id` z propů, jinak vygeneruj přes useId() pro svázání labelu.
        const generatedId = useId();
        const selectId = id ?? generatedId;

        return (
            <div className="flex w-full flex-col gap-1">
                {label && (
                    <label className="text-sm font-medium" htmlFor={selectId}>
                        {label}
                        {withAsterisk && <span className="ml-1 text-error">*</span>}
                    </label>
                )}
                {description && (
                    <p className="text-xs text-base-content/70">{description}</p>
                )}
                <select
                    ref={ref}
                    id={selectId}
                    className={cn(
                        'select w-full',
                        error && 'select-error',
                        className,
                    )}
                    {...props}
                >
                    {placeholder && (
                        <option value="" disabled>
                            {placeholder}
                        </option>
                    )}
                    {normalizedData.map((option) => (
                        <option
                            key={option.value}
                            value={option.value}
                            disabled={option.disabled}
                        >
                            {option.label}
                        </option>
                    ))}
                </select>
                {error && <p className="text-xs text-error">{error}</p>}
            </div>
        );
    },
);

Select.displayName = 'Select';

export { Select };
