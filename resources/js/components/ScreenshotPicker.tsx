// Screenshot picker s region-first workflow + html2canvas-pro.
// Pipeline:
//   1) Overlay s crosshair na ŽIVÉ stránce (žádný capture na startu)
//   2) User drag rectangle → mouseUp (>= 10x10 px)
//   3) Vyloučit overlay přes html2canvas-pro `ignoreElements` callback →
//      capture jen vybrané oblasti pod overlayem → File PNG → onCapture
//   4) html2canvas-pro je drop-in fork s podporou OkLCH/LCH/Lab/color-mix
//      (DaisyUI v5 + BrandingProvider injektuje --color-primary jako oklch())
//
// Pozn.: visibility:hidden NEstačí — html2canvas DOM element pořád zachytí.
// `ignoreElements` je jediný spolehlivý způsob, jak ze snímku vyříznout overlay
// včetně dim pozadí, hint textu, selection rectangle a loading spinneru.

import { useCallback, useEffect, useRef, useState } from 'react';

import { Z_LAYERS } from '../lib/z-layers';

interface ScreenshotPickerProps {
    onCapture: (file: File) => void;
    onCancel: () => void;
    /** Maximální velikost výsledného PNG v bytech (default neomezeno). */
    maxSize?: number;
}

interface SelectionRect {
    startX: number;
    startY: number;
    endX: number;
    endY: number;
}

/** Minimální velikost výběru v CSS px — pod touto hodnotou bereme jako klik. */
const MIN_SELECTION_PX = 10;

/**
 * Normalizuje souřadnice výběru — uživatel může táhnout libovolným směrem.
 * Vrací { x, y, width, height } v CSS pixelech.
 */
function normalizeSelection(rect: SelectionRect): { x: number; y: number; width: number; height: number } {
    const x = Math.min(rect.startX, rect.endX);
    const y = Math.min(rect.startY, rect.endY);
    const width = Math.abs(rect.endX - rect.startX);
    const height = Math.abs(rect.endY - rect.startY);
    return { x, y, width, height };
}

export function ScreenshotPicker({ onCapture, onCancel, maxSize }: ScreenshotPickerProps) {
    // Ref na drag overlay container — předáme ho do html2canvas-pro ignoreElements,
    // aby se overlay (dim pozadí, hint text, selection rect, Zrušit btn) nezachytil
    const containerRef = useRef<HTMLDivElement | null>(null);
    // Ref na loading overlay — také je potřeba ignorovat, protože v okamžiku
    // capture už je v DOM (setIsCapturing(true) je synchronní před await)
    const loadingRef = useRef<HTMLDivElement | null>(null);

    const [selection, setSelection] = useState<SelectionRect | null>(null);
    const [isDragging, setIsDragging] = useState(false);
    const [isCapturing, setIsCapturing] = useState(false);
    const [captureError, setCaptureError] = useState<string | null>(null);

    // ESC → cancel (zachováno z původu)
    useEffect(() => {
        const handleKeyDown = (e: KeyboardEvent) => {
            if (e.key === 'Escape') {
                onCancel();
            }
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [onCancel]);

    // Mouse handlery pro drag selection
    const handleMouseDown = useCallback((e: React.MouseEvent<HTMLDivElement>) => {
        if (isCapturing || captureError) {
            return;
        }
        // Ignorovat pravé tlačítko
        if (e.button !== 0) {
            return;
        }
        const rect = containerRef.current?.getBoundingClientRect();
        if (!rect) {
            return;
        }
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        setSelection({ startX: x, startY: y, endX: x, endY: y });
        setIsDragging(true);
    }, [isCapturing, captureError]);

    const handleMouseMove = useCallback((e: React.MouseEvent<HTMLDivElement>) => {
        if (!isDragging || !selection) {
            return;
        }
        const rect = containerRef.current?.getBoundingClientRect();
        if (!rect) {
            return;
        }
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        setSelection({ ...selection, endX: x, endY: y });
    }, [isDragging, selection]);

    const handleMouseUp = useCallback(async () => {
        if (!isDragging || !selection) {
            return;
        }
        setIsDragging(false);

        const sel = normalizeSelection(selection);

        // Pod minimální velikost — bereme jako klik, ne jako commit
        if (sel.width < MIN_SELECTION_PX || sel.height < MIN_SELECTION_PX) {
            setSelection(null);
            return;
        }

        // 1) Loading state — overlay zůstává VIDITELNÝ, html2canvas ho vyloučí přes ignoreElements
        setIsCapturing(true);

        try {
            // 2) Dynamický import html2canvas-pro (drop-in fork s podporou OkLCH)
            const { default: html2canvas } = await import('html2canvas-pro');
            const dpr = window.devicePixelRatio || 1;

            // Ignorovat náš overlay (dim, hint, selection rect) i loading spinner —
            // bez toho by se zachytily do výsledného snímku jako tmavá vrstva
            const overlayEl = containerRef.current;
            const loadingEl = loadingRef.current;
            const ignoreElements = (el: Element): boolean => {
                if (overlayEl && (el === overlayEl || overlayEl.contains(el))) {
                    return true;
                }
                if (loadingEl && (el === loadingEl || loadingEl.contains(el))) {
                    return true;
                }
                return false;
            };

            const canvas = await html2canvas(document.body, {
                x: window.scrollX + sel.x,
                y: window.scrollY + sel.y,
                width: sel.width,
                height: sel.height,
                scale: dpr,
                useCORS: true,
                logging: false,
                ignoreElements,
            });

            // 4) toBlob → File PNG s timestamp filename (Windows-safe — bez `:` `.`)
            canvas.toBlob((blob) => {
                if (!blob) {
                    setCaptureError('Nepodařilo se vytvořit obrázek');
                    return;
                }
                if (maxSize && blob.size > maxSize) {
                    const mb = Math.round(maxSize / 1024 / 1024);
                    setCaptureError(`Screenshot je příliš velký (max ${mb} MB).`);
                    return;
                }
                const ts = new Date().toISOString().replace(/[:.]/g, '-');
                const filename = `screenshot-${ts}.png`;
                onCapture(new File([blob], filename, { type: 'image/png' }));
            }, 'image/png');
        } catch (error) {
            const msg = error instanceof Error ? error.message : 'Neznámá chyba';
            setCaptureError(`Pořízení screenshotu selhalo: ${msg}`);
        }
    }, [isDragging, selection, onCapture, maxSize]);

    // Error state — pokud capture selhal, zobrazit alert
    if (captureError) {
        return (
            <div
                className="fixed inset-0 flex items-center justify-center bg-base-content/60"
                style={{ zIndex: Z_LAYERS.screenshotTop }}
            >
                <div role="alert" className="alert alert-error max-w-md">
                    <div className="flex flex-col gap-3">
                        <span>{captureError}</span>
                        <button type="button" className="btn btn-sm" onClick={onCancel}>
                            Zavřít
                        </button>
                    </div>
                </div>
            </div>
        );
    }

    // Vykreslení selection rectangle (jen pokud user drží myš)
    const selRect = selection ? normalizeSelection(selection) : null;

    return (
        <>
            {/* Overlay — vždy renderován, html2canvas ho vyřízne přes ignoreElements */}
            <div
                ref={containerRef}
                className="fixed inset-0 cursor-crosshair select-none overflow-hidden bg-base-content/40"
                style={{ zIndex: Z_LAYERS.screenshotOverlay }}
                onMouseDown={handleMouseDown}
                onMouseMove={handleMouseMove}
                onMouseUp={handleMouseUp}
                onMouseLeave={handleMouseUp}
            >
                {/* Selection rectangle (jen když user drží myš) */}
                {isDragging && selRect && selRect.width > 0 && selRect.height > 0 && (
                    <div
                        className="pointer-events-none absolute border-2 border-dashed border-primary bg-primary/10"
                        style={{
                            left: `${selRect.x}px`,
                            top: `${selRect.y}px`,
                            width: `${selRect.width}px`,
                            height: `${selRect.height}px`,
                        }}
                    />
                )}

                {/* Hint text uprostřed nahoře */}
                <div className="pointer-events-none absolute left-1/2 top-4 -translate-x-1/2 rounded-full bg-base-content/60 px-3 py-1.5 text-sm text-base-100">
                    Vyberte oblast pro screenshot
                </div>

                {/* Cancel tlačítko top-right */}
                <button
                    type="button"
                    className="btn btn-sm btn-ghost absolute right-4 top-4 text-base-100 hover:bg-base-content/20"
                    onClick={(e) => {
                        e.stopPropagation();
                        onCancel();
                    }}
                    onMouseDown={(e) => e.stopPropagation()}
                >
                    Zrušit
                </button>
            </div>

            {/* Loading spinner během capturing — nad overlayem, ignoreElements ho vyloučí z capture */}
            {isCapturing && (
                <div
                    ref={loadingRef}
                    className="fixed inset-0 flex items-center justify-center bg-base-content/60"
                    style={{ zIndex: Z_LAYERS.screenshotTop }}
                >
                    <span className="loading loading-spinner loading-lg text-primary" />
                </div>
            )}
        </>
    );
}

export default ScreenshotPicker;
