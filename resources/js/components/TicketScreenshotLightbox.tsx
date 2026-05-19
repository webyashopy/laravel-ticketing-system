// Modal s plnou velikostí přílohy.
// fallback pro ne-image typy: PDF v iframe, ostatní download button.
// Komponenta se nepřejmenovává (BC s importy).

import { Download, X } from 'lucide-react';

import type { TicketAttachment } from '../types';

import { getAttachmentIcon, isImageMime, isPdfMime } from './_helpers/attachmentIcon';

interface TicketScreenshotLightboxProps {
    attachment: TicketAttachment | null;
    onClose: () => void;
}

// Naformátuje velikost souboru pro UI (KB / MB)
function formatSize(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / 1024 / 1024).toFixed(2)} MB`;
}

export function TicketScreenshotLightbox({ attachment, onClose }: TicketScreenshotLightboxProps) {
    if (!attachment) return null;

    const Icon = getAttachmentIcon(attachment.mime_type);

    return (
        <div className="modal modal-open" onClick={onClose}>
            <div
                className="modal-box max-w-5xl w-full p-2 bg-base-100"
                onClick={(e) => e.stopPropagation()}
            >
                <div className="flex justify-between items-center mb-2 px-2">
                    <h3 className="font-medium truncate">{attachment.filename}</h3>
                    <button
                        type="button"
                        className="btn btn-ghost btn-sm btn-circle"
                        onClick={onClose}
                        aria-label="Zavřít"
                    >
                        <X size={18} />
                    </button>
                </div>

                {isImageMime(attachment.mime_type) ? (
                    <img
                        src={attachment.signed_url}
                        alt={attachment.filename}
                        className="w-full h-auto max-h-[80vh] object-contain rounded"
                    />
                ) : isPdfMime(attachment.mime_type) ? (
                    <iframe
                        src={attachment.signed_url}
                        className="w-full h-[80vh] rounded"
                        title={attachment.filename}
                    />
                ) : (
                    // Ostatní typy — ikona + filename + download button
                    <div className="flex flex-col items-center justify-center gap-3 py-12">
                        <Icon size={64} className="text-base-content/60" />
                        <div className="font-medium text-center break-all px-4">
                            {attachment.filename}
                        </div>
                        <div className="text-sm text-base-content/60">
                            {formatSize(attachment.size_bytes)}
                        </div>
                        <a
                            href={attachment.signed_url}
                            download
                            className="btn btn-primary mt-4 gap-2"
                        >
                            <Download size={16} />
                            Stáhnout
                        </a>
                    </div>
                )}
            </div>
        </div>
    );
}

export default TicketScreenshotLightbox;
