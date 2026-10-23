<?php
  function fileNameEditor(string $initialFileName, bool $isDirectory, callable $translate) {    
    $newFileName = $initialFileName;
    $extension = '';
    $nbParts = 1;
    if (!$isDirectory) {
      $nameSplit = explode('.', $newFileName);
      $nbParts = count($nameSplit);
      if (count($nameSplit) > 1) {
        $extension = array_pop($nameSplit);
      }
    }return [
        'initialFileName' => $initialFileName,
        'newFileName' => $newFileName,
        'isDirectory' => $isDirectory,
        'extension' => $extension,
        'nbParts' => $nbParts,
        'translate' => $translate
    ];
  }

  $editor = fileNameEditor(
    $initialFileName,
    $isDirectory,
    $translate
);

  extract($editor);
?>
  <div
    class="fixed inset-0 flex items-center justify-center bg-gray-700 bg-opacity-50 z-50"
    id = "fileNameModal"
  >
    <div class="dark:bg-NcBlack bg-white rounded-lg shadow-lg p-6 w-96">
      <h2 class="text-lg font-semibold mb-4"><?= htmlspecialchars($translate('modify.file.name'), ENT_QUOTES, 'UTF-8') ?></h2>
      <input
          type="text"
          id="newFileName"
          value="<?= htmlspecialchars($newFileName, ENT_QUOTES, 'UTF-8') ?>"
          data-is-directory="<?= $isDirectory ? 'true' : 'false' ?>"
          data-extension="<?= htmlspecialchars($extension, ENT_QUOTES, 'UTF-8') ?>"
          data-nb-parts="<?= $nbParts ?>"
          placeholder="Entrez le nom du fichier"
          class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      <div class="flex justify-end mt-4 space-x-2">
        <button
          id="saveButton"
          class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition"
        >
          <?= htmlspecialchars($translate('rename'), ENT_QUOTES, 'UTF-8') ?>
        </button>
        <button
          id = "cancelButton"
          class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition"
        >
          <?= htmlspecialchars($translate('cancel'), ENT_QUOTES, 'UTF-8') ?>
        </button>
      </div>
    </div>
  </div>

<style scoped></style>
