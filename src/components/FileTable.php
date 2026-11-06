<?php
// FileTable.php : the component AND its server actions, in one file.
// Use it with: include 'FileTable.php';  (and load FileTable.js in the page)

if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['csrf'] ??= bin2hex(random_bytes(16));   // protects POST requests

const FILETABLE_URL = 'FileTable.php';             // URL of this file as seen by the browser (adjust if needed)
const ROOT_PATH     = __DIR__ . '/files';          // the folder shown in the table
if (!is_dir(ROOT_PATH)) @mkdir(ROOT_PATH, 0755);

/* ===================== Helpers ===================== */

function e(?string $s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

// Replace with your real translations (same keys as the Vue app)
function translations(): array {
    return [
        'all.files' => 'Tous les fichiers', 'favorites' => 'Favoris', 'new' => 'Nouveau',
        'create.new.file' => 'Créer un nouveau dossier', 'name.of.file' => 'Nom du dossier',
        'create' => 'Créer', 'cancel' => 'Annuler', 'name' => 'Nom', 'type' => 'Type',
        'size' => 'Taille', 'options' => 'Options', 'delete' => 'Supprimer', 'edit' => 'Renommer',
        'folder' => 'Dossier', 'file' => 'Fichier',
        'cant.rename' => 'Impossible de renommer en ',
        'cant.create.folder' => 'Impossible de créer le dossier ',
        'already.exists' => ' : ce nom existe déjà.',
        'exists.title' => 'Ce nom existe déjà', 'overwrite' => 'Écraser', 'rename' => 'Renommer',
        'apply.to.all' => 'Appliquer à tous', 'error' => 'Une erreur est survenue',
    ];
}
function t(string $key): string { return translations()[$key] ?? $key; }

// "/a/../b//c" -> "/b/c": removes empty, "." and ".." parts (blocks path traversal)
function cleanDir(string $dir): string {
    $parts = array_filter(explode('/', str_replace('\\', '/', $dir)),
        fn($p) => $p !== '' && $p !== '.' && $p !== '..');
    return '/' . implode('/', $parts);
}

function absPath(string $dir): string {
    $dir = cleanDir($dir);
    return ROOT_PATH . ($dir === '/' ? '' : $dir);
}

// A file name must be a single name, never a path
function validName(string $name): bool {
    return $name !== '' && $name !== '.' && $name !== '..' && !preg_match('#[/\\\\\0]#', $name);
}

function breadcrumbParts(string $dir): array {
    return $dir === '/' ? [] : explode('/', trim($dir, '/'));
}

function formatFileSize(int $size): string {
    if ($size < 1024) return "$size B";
    if ($size < 1024 ** 2) return number_format($size / 1024, 2) . ' KB';
    if ($size < 1024 ** 3) return number_format($size / 1024 ** 2, 2) . ' MB';
    return number_format($size / 1024 ** 3, 2) . ' GB';
}

function navUrl(string $tab, string $dir): string {
    return '?' . http_build_query(['tab' => $tab, 'dir' => $dir]);
}

function itemFromPath(string $abs, string $parentDir): array {
    $isDir = is_dir($abs);
    return [
        'basename' => basename($abs),
        'path'     => ($parentDir === '/' ? '' : $parentDir) . '/' . basename($abs),
        'type'     => $isDir ? 'directory' : 'file',
        'size'     => $isDir ? 0 : (int) filesize($abs),
    ];
}

function fetchFiles(string $tab, string $dir): array {
    $items = [];
    if ($tab === 'favorites' && $dir === '/') {
        // Optional favorites.json: ["/docs", "/photo.png"]
        $favs = json_decode(@file_get_contents(__DIR__ . '/favorites.json') ?: '[]', true) ?: [];
        foreach ($favs as $fav) {
            $fav = cleanDir((string) $fav);
            if ($fav !== '/' && file_exists(absPath($fav))) {
                $parent = str_replace('\\', '/', dirname($fav));
                $items[] = itemFromPath(absPath($fav), $parent === '.' ? '/' : $parent);
            }
        }
    } else {
        $abs = absPath($dir);
        if (!is_dir($abs)) return [];
        foreach (scandir($abs) as $name) {
            if ($name === '.' || $name === '..') continue;
            $items[] = itemFromPath($abs . '/' . $name, $dir);
        }
    }
    usort($items, fn($a, $b) =>   // folders first, then by name
        [$a['type'] !== 'directory', strtolower($a['basename'])]
        <=> [$b['type'] !== 'directory', strtolower($b['basename'])]);
    return $items;
}

function deleteRecursive(string $abs): bool {
    if (is_dir($abs) && !is_link($abs)) {
        foreach (scandir($abs) as $n) {
            if ($n !== '.' && $n !== '..') deleteRecursive($abs . '/' . $n);
        }
        return rmdir($abs);
    }
    return unlink($abs);
}

/* ===================== Server actions =====================
   Only when this file is requested directly (by FileTable.js or a download link),
   not when it is included by a page. */

function fail(string $msg, int $code = 400) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['error' => $msg]);
    exit;
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {

    // GET: open / download a file
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (($_GET['action'] ?? '') !== 'download') fail('Bad request');
        $abs = absPath($_GET['path'] ?? '');
        if (!is_file($abs)) fail('Not found', 404);

        $mime = mime_content_type($abs) ?: 'application/octet-stream';
        // Only safe types open in the browser. HTML, SVG... are downloaded (avoids stored XSS).
        $inline = preg_match('#^(image/(png|jpeg|gif|webp)|application/pdf|text/plain)$#', $mime);
        header('Content-Type: ' . $mime);
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . rawurlencode(basename($abs)) . '"');
        header('Content-Length: ' . filesize($abs));
        header('X-Content-Type-Options: nosniff');
        readfile($abs);
        exit;
    }

    // POST: create / delete / rename / upload
    if (!hash_equals($_SESSION['csrf'], $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
        fail('Invalid token (file too big for post_max_size?)', 403);
    }
    header('Content-Type: application/json');

    switch ($_POST['action'] ?? '') {
        case 'create':
            $name = trim($_POST['name'] ?? '');
            if (!validName($name)) fail('Invalid name');
            $target = absPath($_POST['dir'] ?? '/') . '/' . $name;
            if (file_exists($target)) fail('exists', 409);
            if (!mkdir($target, 0755)) fail('mkdir failed', 500);
            break;

        case 'delete':
            $abs = absPath($_POST['path'] ?? '');
            if ($abs === ROOT_PATH || !file_exists($abs)) fail('Not found', 404);
            if (!deleteRecursive($abs)) fail('Delete failed', 500);
            break;

        case 'rename':
            $path = cleanDir($_POST['path'] ?? '');
            $new  = trim($_POST['newName'] ?? '');
            if ($path === '/' || !validName($new)) fail('Invalid name');
            $from = absPath($path);
            $to   = dirname($from) . '/' . $new;
            if (!file_exists($from)) fail('Not found', 404);
            if (file_exists($to)) fail('exists', 409);
            if (!rename($from, $to)) fail('Rename failed', 500);
            break;

        case 'upload':
            $dir       = cleanDir($_POST['dir'] ?? '/');
            $overwrite = ($_POST['overwrite'] ?? '0') === '1';
            $name      = trim($_POST['name'] ?? '');
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) fail('Upload failed');
            if (!validName($name)) fail('Invalid name');
            if (!is_dir(absPath($dir))) fail('Folder not found', 404);
            $target = absPath($dir) . '/' . $name;
            if (file_exists($target) && !$overwrite) fail('exists', 409);
            if (!move_uploaded_file($_FILES['file']['tmp_name'], $target)) fail('Save failed', 500);
            break;

        default:
            fail('Unknown action');
    }
    echo json_encode(['ok' => true]);
    exit;
}

/* ===================== Component (HTML) ===================== */

// State that Vue kept in data(): now it lives in the URL
$tab      = ($_GET['tab'] ?? 'default') === 'favorites' ? 'favorites' : 'default';
$dir      = cleanDir($_GET['dir'] ?? '/');
$files    = fetchFiles($tab, $dir);
$canWrite = !($tab === 'favorites' && $dir === '/');   // no upload / "New" in favorites root
$parts    = breadcrumbParts($dir);
$tabClass = fn(string $name) => $tab === $name ? ' !bg-blue-500/50' : '';
?>
<div id="file-table" class="h-full"
     data-dir="<?= e($dir) ?>"
     data-can-write="<?= $canWrite ? '1' : '0' ?>"
     data-csrf="<?= e($_SESSION['csrf']) ?>"
     data-api="<?= e(FILETABLE_URL) ?>"
     data-i18n="<?= e(json_encode(translations(), JSON_UNESCAPED_UNICODE)) ?>">

  <!-- Tabs -->
  <div class="flex flex-row gap-2 p-2">
    <a href="<?= e(navUrl('default', '/')) ?>"
       class="px-4 py-2 rounded-md bg-gray-100 hover:bg-gray-200<?= $tabClass('default') ?>"><?= e(t('all.files')) ?></a>
    <a href="<?= e(navUrl('favorites', '/')) ?>"
       class="px-4 py-2 rounded-md bg-gray-100 hover:bg-gray-200<?= $tabClass('favorites') ?>"><?= e(t('favorites')) ?></a>
  </div>

  <div class="flex flex-col h-full w-full border">

    <!-- Breadcrumb + "New" button / progress bar -->
    <div class="flex flex-row mt-1 ml-3 pr-3 items-center justify-between">
      <nav class="flex items-center gap-1 text-sm">
        <a href="<?= e(navUrl($tab, '/')) ?>" class="text-blue-600 hover:underline">Home</a>
        <?php $path = ''; foreach ($parts as $part): $path .= '/' . $part; ?>
          <span>/</span>
          <a href="<?= e(navUrl($tab, $path)) ?>" class="text-blue-600 hover:underline"><?= e($part) ?></a>
        <?php endforeach; ?>
      </nav>

      <div class="flex items-center ml-2">
        <button id="btn-new" type="button" <?= $canWrite ? '' : 'disabled' ?>
                class="flex items-center space-x-2 bg-blue-100 text-blue-600 font-medium px-4 py-2 rounded-md hover:bg-blue-200 transition disabled:opacity-50 disabled:cursor-not-allowed">
          <span>+</span><span><?= e(t('new')) ?></span>
        </button>
        <!-- Progress bar (shown during uploads) -->
        <div id="transfer" class="hidden">
          <div class="flex items-center w-48 h-full">
            <div class="relative w-full h-2 bg-gray-300 rounded-full overflow-hidden">
              <div data-progress-bar style="width: 0%" class="h-full rounded-full transition-all duration-300 bg-blue-500"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Header -->
    <div class="flex h-12 items-center border-b border-gray-300">
      <div class="w-7/12 px-4 py-2 text-gray-500 font-semibold border-r border-gray-300"><?= e(t('name')) ?></div>
      <div class="w-2/12 px-4 py-2 text-gray-500 font-semibold border-r border-gray-300"><?= e(t('type')) ?></div>
      <div class="w-2/12 px-4 py-2 text-gray-500 font-semibold"><?= e(t('size')) ?></div>
      <div class="w-1/12 px-4 py-2 text-gray-500 font-semibold"><?= e(t('options')) ?></div>
    </div>

    <!-- Rows (also the drop zone) -->
    <div id="drop-zone" class="overflow-y-auto h-full mb-14 rounded-xl border-4 border-transparent">
      <?php foreach ($files as $file):
          $isDir = $file['type'] === 'directory';
          $ext   = strtolower(pathinfo($file['basename'], PATHINFO_EXTENSION));
          $href  = $isDir
              ? navUrl($tab, $file['path'])
              : FILETABLE_URL . '?' . http_build_query(['action' => 'download', 'path' => $file['path']]);
      ?>
        <div class="flex h-16 items-center hover:bg-gray-100 rounded-lg border-b last:border-b-0 border-gray-300">

          <!-- "contents" = the link has no box, its children are the row cells -->
          <a href="<?= e($href) ?>" <?= $isDir ? '' : 'target="_blank"' ?> class="contents">
            <div class="w-7/12 flex items-center px-4 py-2 border-r border-gray-300">
              <div class="w-12 h-12 flex items-center justify-center">
                <?php if ($isDir): ?>
                  <svg fill="currentColor" viewBox="0 0 24 24" class="text-blue-600 w-10 h-10"><path d="M10,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V8C22,6.89 21.1,6 20,6H12L10,4Z"/></svg>
                <?php elseif ($ext === 'zip'): ?>
                  <svg viewBox="0 0 24 24" class="w-10 h-10"><path fill="#969696" d="M5.12,5H18.87L17.93,4H5.93L5.12,5M20.54,5.23C20.83,5.57 21,6 21,6.5V19A2,2 0 0,1 19,21H5A2,2 0 0,1 3,19V6.5C3,6 3.17,5.57 3.46,5.23L4.84,3.55C5.12,3.21 5.53,3 6,3H18C18.47,3 18.88,3.21 19.15,3.55L20.54,5.23M6,18H12V15H6V18Z"/></svg>
                <?php else: ?>
                  <svg viewBox="0 0 24 24" class="w-10 h-10"><path fill="#969696" d="M6 22c-.55 0-1.021-.196-1.412-.587A1.927 1.927 0 0 1 4 20V4c0-.55.196-1.021.588-1.413A1.926 1.926 0 0 1 6 2h8l6 6v12a1.93 1.93 0 0 1-.587 1.413A1.93 1.93 0 0 1 18 22H6Z"/></svg>
                <?php endif; ?>
              </div>
              <div class="ml-4 truncate"><?= e($file['basename']) ?></div>
            </div>
            <div class="w-2/12 px-4 py-2 border-r border-gray-300"><?= e(t($isDir ? 'folder' : 'file')) ?></div>
            <div class="w-2/12 px-4 py-2"><?= $isDir ? '-' : e(formatFileSize($file['size'])) ?></div>
          </a>

          <div class="w-1/12 px-4 py-2 flex gap-3">
            <button type="button" data-action="rename" data-path="<?= e($file['path']) ?>"
                    data-name="<?= e($file['basename']) ?>" title="<?= e(t('edit')) ?>">✏️</button>
            <button type="button" data-action="delete" data-path="<?= e($file['path']) ?>"
                    data-name="<?= e($file['basename']) ?>" title="<?= e(t('delete')) ?>">🗑️</button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Popup: create folder -->
  <dialog id="dlg-create" class="rounded-lg shadow-lg p-6 w-96 backdrop:bg-gray-700/50">
    <h2 class="text-lg font-semibold mb-4"><?= e(t('create.new.file')) ?></h2>
    <input id="create-name" type="text" placeholder="<?= e(t('name.of.file')) ?>"
           class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
    <div class="flex justify-end mt-4 gap-2">
      <button id="create-ok" type="button" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"><?= e(t('create')) ?></button>
      <button data-close type="button" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300"><?= e(t('cancel')) ?></button>
    </div>
  </dialog>

  <!-- Popup: rename -->
  <dialog id="dlg-rename" class="rounded-lg shadow-lg p-6 w-96 backdrop:bg-gray-700/50">
    <h2 class="text-lg font-semibold mb-4"><?= e(t('edit')) ?></h2>
    <input id="rename-name" type="text"
           class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
    <div class="flex justify-end mt-4 gap-2">
      <button id="rename-ok" type="button" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"><?= e(t('edit')) ?></button>
      <button data-close type="button" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300"><?= e(t('cancel')) ?></button>
    </div>
  </dialog>

  <!-- Popup: name already exists -->
  <dialog id="dlg-exists" class="rounded-lg shadow-lg p-6 w-96 backdrop:bg-gray-700/50">
    <h2 class="text-lg font-semibold mb-1"><?= e(t('exists.title')) ?></h2>
    <p id="exists-text" class="mb-4 text-gray-500 truncate"></p>
    <input id="exists-name" type="text" class="w-full px-4 py-2 border rounded-md">
    <label class="flex items-center gap-2 mt-3 text-sm">
      <input id="exists-all" type="checkbox"> <?= e(t('apply.to.all')) ?>
    </label>
    <div class="flex justify-end mt-4 gap-2">
      <button id="exists-overwrite" type="button" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"><?= e(t('overwrite')) ?></button>
      <button id="exists-rename" type="button" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"><?= e(t('rename')) ?></button>
      <button id="exists-cancel" type="button" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300"><?= e(t('cancel')) ?></button>
    </div>
  </dialog>
</div>
