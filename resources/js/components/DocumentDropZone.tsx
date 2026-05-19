// zkopírováno z host `@/components/documents/DocumentDropZone`.
//
// Modul Tickets používá DropZone v `staged` režimu (upload až při submitu
// formuláře v TicketCreateModal). Host appka má vlastní obecnou DropZone pro
// dokumenty; balíček si nese izolovanou kopii bez závislosti na host `@/*`.
// Jediná změna oproti originálu: import `api` z balíčkového klienta
// (`../lib/api` místo `@/lib/api`).

import { useState, useRef, useCallback } from 'react';
import { Upload, File, X, Check } from 'lucide-react';
import { toast } from 'sonner';

import { api } from '../lib/api';

export interface DocumentDropZoneProps {
    /**
     * Plný API endpoint pro upload (např. /api/cases/{uuid}/documents).
     * V `mode='staged'` je nepovinný — upload provádí rodičovský formulář.
     */
    endpoint?: string;
    /**
     * Režim uploadu.
     * - `immediate` (default) — soubory se uploadují okamžitě po dropu na `endpoint`.
     * - `staged` — žádný upload, jen se drží v lokálním stavu a předávají rodiči
     *   přes `onFilesChange`. Použití pro modaly, kde se submituje jako celý form.
     */
    mode?: 'immediate' | 'staged';
    /** Callback v `staged` režimu — vrací aktuální seznam staged File objektů. */
    onFilesChange?: (files: File[]) => void;
    /** Callback po úspěšném uploadu (přijde po každém souboru) — jen `immediate` mode */
    onUploaded?: () => void;
    /**
     * Callback po úspěšném uploadu, který dostane parsovanou
     * JSON odpověď z `endpoint`. Volaný kromě `onUploaded` — pokud chceš
     * jen prostou notifikaci, použij `onUploaded`. Pokud potřebuješ z odpovědi
     * vyčíst např. `document_uuid` pro polling, použij `onUploadSuccess`.
     */
    onUploadSuccess?: (response: unknown, file: File) => void;
    /** Povolené MIME typy - default: PDF, JPG, PNG, GIF, WEBP, DOC, DOCX, XLS, XLSX, TXT, RTF */
    allowedMimes?: string[];
    /** Maximální velikost souboru v bytech - default 20 MB */
    maxSize?: number;
    /** Maximální počet souborů — pokud je nastaven, další se odmítnou s toastem. */
    maxFiles?: number;
    /** Zobrazit checkbox "Spustit OCR" - default true */
    showOcrCheckbox?: boolean;
    /** Zobrazit pole pro Název + Popis - default true */
    showMetadataInputs?: boolean;
    /** Doplňková CSS třída na vnější element */
    className?: string;
    /**
     * Custom popisek povolených typů pro footer pod
     * dropzonou (např. „PDF, JPG, PNG"). Pokud není zadáno, použije se
     * generický fallback „PDF, obrázky, Word, Excel, TXT".
     * Doporučeno: vždy předat, pokud `allowedMimes` zužuješ pro daný kontext —
     * jinak text v UI lže.
     */
    acceptedTypesLabel?: string;
    /**
     * Počáteční staged soubory — použito např. pro screenshot
     * předvyplnění z TicketCreateModal. Aplikuje se jen v `staged` režimu
     * a jen při mountu (parent musí změnit `key` pro remount, pokud chce
     * resetovat).
     */
    initialFiles?: File[];
}

interface UploadingFile {
    file: File;
    progress: number;
    status: 'pending' | 'uploading' | 'success' | 'error';
    error?: string;
    // Per-file metadata — každý soubor má vlastní title/description
    title: string;
    description: string;
}

// Výchozí povolené MIME typy (PDF, obrázky, Office, TXT, RTF)
const DEFAULT_ALLOWED_MIMES = [
    'application/pdf',
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'text/plain',
    'application/rtf',
];

const DEFAULT_MAX_SIZE = 20 * 1024 * 1024; // 20 MB

/**
 * Obecná komponenta pro upload dokumentů s drag & drop podporou.
 * Funguje proti libovolnému endpointu, který přijímá multipart/form-data
 * s polem `file` (a volitelně `title`, `description`, `run_ocr`).
 *
 * UX rozhodnutí:
 * - Single mode (1 soubor) → shared inputy nahoře, auto-upload při dropu
 * - Multi mode (2+ souborů) → shared inputy se SKRYJÍ, každý soubor má
 *   vlastní per-file form (Název + Popis), upload se spouští kliknutím
 *   na tlačítko „Nahrát"
 */
export function DocumentDropZone({
    endpoint,
    mode = 'immediate',
    onFilesChange,
    onUploaded,
    onUploadSuccess,
    allowedMimes = DEFAULT_ALLOWED_MIMES,
    maxSize = DEFAULT_MAX_SIZE,
    maxFiles,
    showOcrCheckbox = true,
    showMetadataInputs = true,
    className,
    initialFiles,
    acceptedTypesLabel,
}: DocumentDropZoneProps) {
    // Staged režim drží jen plain File objekty (žádný upload progress)
    // initialFiles aplikujeme jen při mountu.
    const [stagedFiles, setStagedFiles] = useState<File[]>(() => initialFiles ?? []);
    const [isDragging, setIsDragging] = useState(false);
    const [uploadingFiles, setUploadingFiles] = useState<UploadingFile[]>([]);
    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');
    const [runOcr, setRunOcr] = useState(true);
    const fileInputRef = useRef<HTMLInputElement>(null);

    // Ref pro aktuální stav souborů — uploadFile() ji čte při samotném POSTu,
    // aby zachytila nejnovější per-file metadata (i když user změní inputy
    // PO startu uploadu, závisle na časování batch operací).
    const uploadingFilesRef = useRef<UploadingFile[]>([]);
    uploadingFilesRef.current = uploadingFiles;

    // Velikost limitu v MB pro hlášky
    const maxSizeMb = Math.round(maxSize / (1024 * 1024));

    // Multi mode = 2+ souborů, nebo přesně 1 který ještě není uploadnutý ze stavu single
    // (jednoduchá pravda: 2+ souborů → multi)
    const isMultiMode = uploadingFiles.length >= 2;

    // Shared inputy nahoře zobraz jen v single mode (0 nebo 1 soubor)
    const showSharedInputs = showMetadataInputs && !isMultiMode;

    // Validace souboru (typ a velikost)
    const validateFile = (file: File): string | null => {
        if (!allowedMimes.includes(file.type)) {
            return 'Nepovolený typ souboru';
        }
        if (file.size > maxSize) {
            return `Soubor je příliš velký (max ${maxSizeMb} MB)`;
        }
        return null;
    };

    // Vlastní upload jednoho souboru.
    // Čte aktuální stav z `uploadingFilesRef`, protože per-file inputy
    // se mohou měnit i během uploadu (multi mode batch).
    const uploadFile = async (index: number) => {
        const item = uploadingFilesRef.current[index];
        if (!item) return;

        const formData = new FormData();
        formData.append('file', item.file);
        if (showOcrCheckbox) {
            formData.append('run_ocr', runOcr ? '1' : '0');
        }
        if (showMetadataInputs) {
            if (item.title.trim()) {
                formData.append('title', item.title.trim());
            }
            if (item.description.trim()) {
                formData.append('description', item.description.trim());
            }
        }

        try {
            setUploadingFiles((prev) =>
                prev.map((f, i) =>
                    i === index ? { ...f, status: 'uploading' as const, progress: 10 } : f,
                ),
            );

            // 4: zachytit parsovanou JSON response pro onUploadSuccess
            const response = await api.upload<unknown>(endpoint!, formData);

            setUploadingFiles((prev) =>
                prev.map((f, i) =>
                    i === index ? { ...f, status: 'success' as const, progress: 100 } : f,
                ),
            );

            toast.success(`Dokument "${item.file.name}" byl nahrán`);
            onUploaded?.();
            onUploadSuccess?.(response, item.file);

            // Vyčistit po úspěchu (s krátkou pauzou pro UX)
            setTimeout(() => {
                setUploadingFiles((prev) => prev.filter((_, i) => i !== index));
            }, 2000);
        } catch (error: unknown) {
            const message = error instanceof Error ? error.message : 'Neznámá chyba';
            setUploadingFiles((prev) =>
                prev.map((f, i) =>
                    i === index
                        ? { ...f, status: 'error' as const, error: message }
                        : f,
                ),
            );
            toast.error(`Upload "${item.file.name}" selhal: ${message}`);
        }
    };

    // Aktualizace per-file metadat (title nebo description)
    const updateFileMeta = (
        index: number,
        field: 'title' | 'description',
        value: string,
    ) => {
        setUploadingFiles((prev) =>
            prev.map((f, i) => (i === index ? { ...f, [field]: value } : f)),
        );
    };

    // Spuštění uploadu všech `pending` souborů (multi mode trigger)
    const handleUploadAll = () => {
        const pending = uploadingFilesRef.current
            .map((item, idx) => ({ item, idx }))
            .filter(({ item }) => item.status === 'pending');

        pending.forEach(({ idx }) => {
            uploadFile(idx);
        });
    };

    // Zpracování seznamu souborů (z drop nebo input)
    const handleFiles = useCallback(
        (files: FileList | File[]) => {
            const fileArray = Array.from(files);
            const validFiles: File[] = [];

            // Validace všech souborů nejdřív (žádný setState mezi tím)
            fileArray.forEach((file) => {
                const error = validateFile(file);
                if (error) {
                    toast.error(`${file.name}: ${error}`);
                    return;
                }
                validFiles.push(file);
            });

            if (validFiles.length === 0) {
                return;
            }

            // Staged režim — žádný upload, jen drží soubory v lokálním stavu
            if (mode === 'staged') {
                setStagedFiles((prev) => {
                    const merged = [...prev, ...validFiles];
                    // Aplikace maxFiles limitu (pokud je nastaven)
                    const limited = typeof maxFiles === 'number' ? merged.slice(0, maxFiles) : merged;
                    if (typeof maxFiles === 'number' && merged.length > maxFiles) {
                        toast.error(`Maximální počet souborů: ${maxFiles}`);
                    }
                    onFilesChange?.(limited);
                    return limited;
                });
                return;
            }

            // Spočítáme finální stav PO přidání pro rozhodnutí auto-upload vs. multi
            setUploadingFiles((prev) => {
                const startCount = prev.length;
                const totalAfter = startCount + validFiles.length;

                // Single mode = po přidání bude přesně 1 soubor a žádný předtím nebyl
                const isSingleAfterAdd = totalAfter === 1;

                const newItems: UploadingFile[] = validFiles.map((file, i) => ({
                    file,
                    progress: 0,
                    status: 'pending',
                    // Single mode: přebrat shared title/description z formuláře nahoře
                    // Multi mode: každý soubor začne s prázdným per-file metadatem
                    title: isSingleAfterAdd && i === 0 ? title : '',
                    description: isSingleAfterAdd && i === 0 ? description : '',
                }));

                const merged = [...prev, ...newItems];

                // Single mode → auto-upload (zachované původní chování)
                // Multi mode → ručně přes tlačítko „Nahrát"
                if (isSingleAfterAdd) {
                    setTimeout(() => uploadFile(startCount), 100);
                }

                return merged;
            });

            // POZN.: NESmažeme shared title/description (oprava bugu) —
            // user je teprve uvidí, dokud upload nedoběhne.
        },
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [endpoint, mode, maxFiles, onFilesChange, onUploadSuccess, runOcr, title, description, showOcrCheckbox, showMetadataInputs],
    );

    // Drag & Drop handlery
    const handleDragOver = (e: React.DragEvent) => {
        e.preventDefault();
        setIsDragging(true);
    };

    const handleDragLeave = (e: React.DragEvent) => {
        e.preventDefault();
        setIsDragging(false);
    };

    const handleDrop = (e: React.DragEvent) => {
        e.preventDefault();
        setIsDragging(false);
        if (e.dataTransfer.files.length > 0) {
            handleFiles(e.dataTransfer.files);
        }
    };

    // Klik na dropzone otevře nativní file dialog
    const handleClick = () => {
        fileInputRef.current?.click();
    };

    // Změna inputu (file dialog)
    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files && e.target.files.length > 0) {
            handleFiles(e.target.files);
            e.target.value = ''; // Reset inputu pro možnost znovu vybrat stejný soubor
        }
    };

    // Odstranění chybového záznamu z fronty
    const handleRemoveFile = (index: number) => {
        setUploadingFiles((prev) => prev.filter((_, i) => i !== index));
    };

    // Odstranění staged souboru
    const handleRemoveStagedFile = (index: number) => {
        setStagedFiles((prev) => {
            const next = prev.filter((_, i) => i !== index);
            onFilesChange?.(next);
            return next;
        });
    };

    // Sestavení accept atributu pro file dialog (ze seznamu MIME typů)
    const acceptAttr = allowedMimes.join(',');

    // Pro multi mode: existuje aspoň jeden pending soubor → ukázat tlačítko „Nahrát"
    const hasPending = uploadingFiles.some((f) => f.status === 'pending');

    return (
        <div className={className}>
            {/* Volitelné metadata — v multi mode se skryjí, každý soubor má vlastní */}
            {showSharedInputs && (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div className="flex flex-col gap-1">
                        <label className="label py-1 block">
                            <span className="font-medium text-xs">Název dokumentu (volitelné)</span>
                        </label>
                        <input
                            type="text"
                            className="input input-bordered input-sm w-full"
                            placeholder="Automaticky z názvu souboru"
                            value={title}
                            onChange={(e) => setTitle(e.target.value)}
                        />
                    </div>
                    <div className="flex flex-col gap-1">
                        <label className="label py-1 block">
                            <span className="font-medium text-xs">Popis (volitelné)</span>
                        </label>
                        <input
                            type="text"
                            className="input input-bordered input-sm w-full"
                            placeholder="Krátký popis dokumentu"
                            value={description}
                            onChange={(e) => setDescription(e.target.value)}
                        />
                    </div>
                </div>
            )}

            {/* OCR checkbox */}
            {showOcrCheckbox && (
                <div className="flex flex-col gap-1 mb-4">
                    <label className="label cursor-pointer justify-start gap-2">
                        <input
                            type="checkbox"
                            className="checkbox checkbox-sm checkbox-primary"
                            checked={runOcr}
                            onChange={(e) => setRunOcr(e.target.checked)}
                        />
                        <span className="text-sm font-medium">Spustit OCR rozpoznání textu</span>
                    </label>
                </div>
            )}

            {/* Drop zone */}
            <div
                className={`border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition-colors ${
                    isDragging
                        ? 'border-primary bg-primary/10'
                        : 'border-base-300 hover:border-primary/50 hover:bg-base-200'
                }`}
                onDragOver={handleDragOver}
                onDragLeave={handleDragLeave}
                onDrop={handleDrop}
                onClick={handleClick}
            >
                <input
                    ref={fileInputRef}
                    type="file"
                    className="hidden"
                    multiple
                    accept={acceptAttr}
                    onChange={handleInputChange}
                />

                <Upload
                    size={48}
                    className={`mx-auto mb-3 ${isDragging ? 'text-primary' : 'text-base-content/30'}`}
                />

                <p className="text-base-content/70">
                    Přetáhněte soubory sem nebo <span className="text-primary">klikněte pro výběr</span>
                </p>
                <p className="text-xs text-base-content/50 mt-2">
                    {acceptedTypesLabel ?? 'PDF, obrázky, Word, Excel, TXT'} (max {maxSizeMb} MB)
                </p>
            </div>

            {/* Multi mode: tlačítko „Nahrát" pro spuštění bulk uploadu */}
            {isMultiMode && hasPending && (
                <div className="mt-3 flex items-center justify-between">
                    <p className="text-xs text-base-content/60">
                        Vyplňte volitelné metadata u souborů a klikněte na Nahrát.
                    </p>
                    <button
                        type="button"
                        className="btn btn-primary btn-sm"
                        onClick={handleUploadAll}
                    >
                        Nahrát {uploadingFiles.filter((f) => f.status === 'pending').length} souborů
                    </button>
                </div>
            )}

            {/* Staged režim — seznam vybraných souborů */}
            {mode === 'staged' && stagedFiles.length > 0 && (
                <ul className="mt-4 space-y-2">
                    {stagedFiles.map((file, index) => (
                        <li
                            key={`${file.name}-${index}`}
                            className="flex items-center gap-3 p-3 rounded-lg bg-base-200"
                        >
                            <File size={20} className="text-base-content/50 shrink-0" />
                            <div className="flex-1 min-w-0">
                                <div className="text-sm font-medium truncate">{file.name}</div>
                                <div className="text-xs text-base-content/50">
                                    {(file.size / 1024).toFixed(1)} KB
                                </div>
                            </div>
                            <button
                                type="button"
                                className="btn btn-ghost btn-xs btn-circle"
                                onClick={() => handleRemoveStagedFile(index)}
                                title="Odebrat" aria-label="Odebrat"
                            >
                                <X size={14} />
                            </button>
                        </li>
                    ))}
                </ul>
            )}

            {/* Seznam nahrávaných souborů */}
            {uploadingFiles.length > 0 && (
                <ul className="mt-4 space-y-2">
                    {uploadingFiles.map((item, index) => (
                        <li
                            key={index}
                            className={`flex flex-col gap-2 p-3 rounded-lg ${
                                item.status === 'error'
                                    ? 'bg-error/10'
                                    : item.status === 'success'
                                      ? 'bg-success/10'
                                      : 'bg-base-200'
                            }`}
                        >
                            <div className="flex items-center gap-3">
                                <File size={20} className="text-base-content/50 shrink-0" />
                                <div className="flex-1 min-w-0">
                                    <div className="text-sm font-medium truncate">
                                        {item.file.name}
                                    </div>
                                    <div className="text-xs text-base-content/50">
                                        {(item.file.size / 1024).toFixed(1)} KB
                                        {item.error && (
                                            <span className="text-error ml-2">{item.error}</span>
                                        )}
                                    </div>
                                    {item.status === 'uploading' && (
                                        <progress
                                            className="progress progress-primary w-full h-1 mt-1"
                                            value={item.progress}
                                            max="100"
                                        />
                                    )}
                                </div>
                                {item.status === 'uploading' && (
                                    <span className="loading loading-spinner loading-sm text-primary" />
                                )}
                                {item.status === 'success' && (
                                    <Check size={20} className="text-success" />
                                )}
                                {(item.status === 'error' || item.status === 'pending') && (
                                    <button
                                        type="button"
                                        className="btn btn-ghost btn-xs btn-circle"
                                        onClick={() => handleRemoveFile(index)}
                                        title="Odebrat" aria-label="Odebrat"
                                    >
                                        <X size={14} />
                                    </button>
                                )}
                            </div>

                            {/* Per-file metadata form — jen v multi mode a jen pro pending/uploading soubory */}
                            {isMultiMode && showMetadataInputs && (
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 pl-8">
                                    <input
                                        type="text"
                                        className="input input-bordered input-xs"
                                        placeholder="Název (volitelné)"
                                        value={item.title}
                                        onChange={(e) =>
                                            updateFileMeta(index, 'title', e.target.value)
                                        }
                                        disabled={
                                            item.status === 'uploading' ||
                                            item.status === 'success'
                                        }
                                    />
                                    <input
                                        type="text"
                                        className="input input-bordered input-xs"
                                        placeholder="Popis (volitelné)"
                                        value={item.description}
                                        onChange={(e) =>
                                            updateFileMeta(index, 'description', e.target.value)
                                        }
                                        disabled={
                                            item.status === 'uploading' ||
                                            item.status === 'success'
                                        }
                                    />
                                </div>
                            )}
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

export default DocumentDropZone;
