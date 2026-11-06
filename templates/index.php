
<?php

declare(strict_types=1);

use OCP\Util;

$appId = OCA\WebTransfer\AppInfo\Application::APP_ID;

// Chargement des fichiers JS et CSS
Util::addStyle($appId, 'output');

Util::addScript($appId, 'vendor/jszip.min');
Util::addScript($appId, 'WebContentViewer');
Util::addScript($appId, 'FileTable');
Util::addScript($appId, 'App');

// Paramètres transmis par le contrôleur Nextcloud
$archiveUrl = $_['archiveUrl'] ?? '';
$mode = $_['mode'] ?? '';

// Propriétés attendues par App.php
$zipUrl = $archiveUrl;

// Traduction temporaire
$translate = fn($key) => $key;
?>

<!-- Informations utilisées par le JavaScript -->
<div
    id="archiveInfos"
    data-archive-url="<?= htmlspecialchars(
        $archiveUrl,
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
    data-mode="<?= htmlspecialchars(
        $mode,
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
></div>

<!-- Application WebTransfer -->
<div id="webtransfer">
    <?php include __DIR__ . '/App.php'; ?>
</div>
