<?php
  function fileExistsDialog(string $fileName, bool $isDirectory, callable $translate) {
	$newFileName = $fileName;
	$extension = '';
	if (!$isDirectory) {
		$nameSplit = explode('.', $newFileName);
		if (count($nameSplit) > 1) {
			$extension = array_pop($nameSplit);
		}
	}
    return [
	  'fileName' => $fileName,
	  'newFileName' => $newFileName,
	  'isDirectory' => $isDirectory,
	  'extension' => $extension,
	  'translate' => $translate
    ];

  }

  $escape = fn($text) => htmlspecialchars(
    (string) $text,
    ENT_QUOTES,
    'UTF-8'
);

$dialog = fileExistsDialog(
    'document.pdf',
    false,
    fn($key) => $key
);

extract($dialog);
?>

  <div
    id="fileConflictModal"
    class="fixed inset-0 flex items-center justify-center bg-gray-700/50 z-50"
    data-is-directory="<?= $isDirectory ? 'true' : 'false' ?>"
    data-extension="<?= $escape($extension) ?>"
>
    <!-- Fenêtre principale -->
    <div
        id="conflictMain"
        class="dark:bg-NcBlack bg-white rounded-lg shadow-lg p-6 w-96"
    >
        <h2 class="text-lg font-semibold mb-4">
            <?= $escape($translate('file.already.exist')) ?>
        </h2>

        <p>
            <?= $escape($translate('file.pt.1')) ?>
            <?= $escape($fileName) ?>
            <?= $escape($translate('file.pt.2')) ?>
        </p>

        <div class="flex justify-end mt-4 space-x-2">
            <button type="button" id="showOverwriteButton">
                <?= $escape($translate('overwrite')) ?>
            </button>

            <?php if (!$isDirectory): ?>
                <button type="button" id="showRenameButton">
                    <?= $escape($translate('rename')) ?>
                </button>
            <?php endif; ?>

            <button type="button" id="cancelConflictButton">
                <?= $escape($translate('cancel')) ?>
            </button>
        </div>
    </div>

    <!-- Fenêtre de renommage -->
	 <div id="conflictRename" hidden>
		<?php
			$initialFileName = $fileName;
			$isDirectory = $isDirectory;
			$translate = $translate;
			include __DIR__ . '/EditFileName.php';
		?>
	 </div>

    <!-- Fenêtre de confirmation d'écrasement -->
    <div
        id="conflictOverwrite"
        hidden
        class="dark:bg-NcBlack bg-white rounded-lg shadow-lg p-6 w-96"
    >
        <h2 class="text-lg font-semibold mb-4">
            <?= $escape($translate('you.are.going.to.erase.file.folder')) ?>
        </h2>

        <div class="flex items-center content-evenly">
            <input type="checkbox" id="overwriteForAll" />
            <p><?= $escape($translate('apply.to.all.*')) ?></p>
        </div>

        <p class="text-xs text-gray-400">
            <?= $escape($translate('*.text')) ?>
        </p>

        <div class="flex justify-end mt-4 space-x-2">
            <button type="button" id="confirmOverwriteButton">
                <?= $escape($translate('confirm')) ?>
            </button>

            <button type="button" id="backOverwriteButton">
                <?= $escape($translate('cancel')) ?>
            </button>
        </div>
    </div>
</div>

<script src="./js/EditFileName.js" defer></script>

<style></style>
