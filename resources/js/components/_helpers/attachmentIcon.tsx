// Mapování MIME typů přílohy ticketu na lucide ikonu.
// Sdílené pro TicketDetail (thumbnail grid) a TicketScreenshotLightbox (fallback view).

import {
    File,
    FileArchive,
    FileSpreadsheet,
    FileText,
    Image as ImageIcon,
    type LucideIcon,
} from 'lucide-react';

// Vrátí lucide ikonu odpovídající MIME typu přílohy
export function getAttachmentIcon(mime: string): LucideIcon {
    // Obrázky
    if (mime.startsWith('image/')) {
        return ImageIcon;
    }
    // PDF
    if (mime === 'application/pdf') {
        return FileText;
    }
    // ZIP archivy (oba běžné MIME)
    if (mime === 'application/zip' || mime === 'application/x-zip-compressed') {
        return FileArchive;
    }
    // Plain-text a CSV
    if (mime === 'text/plain' || mime === 'text/csv' || mime === 'application/csv') {
        return FileText;
    }
    // Excel (XLSX)
    if (mime === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
        return FileSpreadsheet;
    }
    // Word (DOCX)
    if (mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
        return FileText;
    }
    // Neznámý typ — generická ikona souboru
    return File;
}

// Pravda pro libovolný image/* MIME
export function isImageMime(mime: string): boolean {
    return mime.startsWith('image/');
}

// Pravda pouze pro PDF (iframe preview v lightboxu)
export function isPdfMime(mime: string): boolean {
    return mime === 'application/pdf';
}
