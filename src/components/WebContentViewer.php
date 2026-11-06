<?php
$mode = $mode ?? 'zip';
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<div class="flex flex-col h-full w-full border" data-web-content-viewer
     data-zip-url="<?= $escape($zipUrl) ?>"
     data-mode="<?= $escape($mode) ?>">
    <nav class="flex flex-row mt-1 items-start container max-h-8" aria-label="Dossiers">
        <div data-breadcrumbs class="flex items-center gap-2"></div>
    </nav>

    <div class="flex h-12 items-center border-b border-gray-300">
        <div class="flex items-center cursor-pointer pl-4">
            <input type="checkbox" data-check-all aria-label="Tout sélectionner">
        </div>
        <div class="ml-2 w-5/6 px-4 py-2 text-gray-500 font-semibold border-r border-gray-300">
            <?= $escape($translate('name')) ?>
        </div>
        <div class="w-1/6 px-4 py-2 text-gray-500 font-semibold">
            <?= $escape($translate('size')) ?>
        </div>
    </div>

    <div data-file-list class="overflow-y-auto h-full" hidden></div>
    <div data-loading class="flex h-full items-center justify-center" role="status">
        <span class="animate-spin text-NcBlue text-2xl" aria-label="Chargement">⟳</span>
    </div>
    <div data-empty class="flex h-full items-center justify-center" hidden>
        <span class="text-gray-500"><?= $escape($translate('no.content')) ?></span>
    </div>
    <div data-error class="text-red-500 p-4" role="alert" hidden></div>
</div>
