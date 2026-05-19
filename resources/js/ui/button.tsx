import { forwardRef, type ButtonHTMLAttributes, type ReactNode } from 'react';

import { cn } from '../lib/cn';

/**
 * DaisyUI v5 button primitiv balíčku.
 *
 * Vlastní kopie — NEimportuje z host `@/components/ui/*` a záměrně
 * NEpoužívá `class-variance-authority` (žádná extra závislost). Varianty
 * řeší prosté lookup mapy. 100% DaisyUI sémantické třídy.
 */
export type ButtonVariant =
    | 'default'
    | 'secondary'
    | 'destructive'
    | 'outline'
    | 'ghost'
    | 'link'
    | 'subtle';

export type ButtonSize = 'default' | 'sm' | 'xs' | 'lg' | 'icon';

const VARIANT_CLASSES: Record<ButtonVariant, string> = {
    default: 'btn-primary',
    secondary: 'btn-secondary',
    destructive: 'btn-error',
    outline: 'btn-outline',
    ghost: 'btn-ghost',
    link: 'btn-link',
    subtle: 'btn-ghost bg-base-200 hover:bg-base-300',
};

const SIZE_CLASSES: Record<ButtonSize, string> = {
    default: 'btn-md',
    sm: 'btn-sm',
    xs: 'btn-xs',
    lg: 'btn-lg',
    icon: 'btn-square btn-sm',
};

export interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: ButtonVariant;
    size?: ButtonSize;
    fullWidth?: boolean;
    rounded?: boolean;
    loading?: boolean;
    leftSection?: ReactNode;
    rightSection?: ReactNode;
}

const Button = forwardRef<HTMLButtonElement, ButtonProps>(
    (
        {
            className,
            variant = 'default',
            size = 'default',
            fullWidth = false,
            rounded = false,
            loading = false,
            leftSection,
            rightSection,
            children,
            disabled,
            ...props
        },
        ref,
    ) => {
        return (
            <button
                ref={ref}
                disabled={disabled || loading}
                className={cn(
                    'btn inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium transition-colors focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50',
                    VARIANT_CLASSES[variant],
                    SIZE_CLASSES[size],
                    fullWidth && 'w-full',
                    rounded && 'rounded-full',
                    className,
                )}
                {...props}
            >
                {loading && (
                    <span className="loading loading-spinner loading-sm" />
                )}
                {!loading && leftSection}
                {children}
                {rightSection}
            </button>
        );
    },
);

Button.displayName = 'Button';

export { Button };
