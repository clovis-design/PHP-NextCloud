<?php

declare(strict_types=1);

use OCP\Util;

$appId = OCA\WebTransfer\AppInfo\Application::APP_ID;

// Chargement des fichiers JS et CSS
Util::addStyle($appId, 'output');

Util::addScript($appId, 'vendor/jszip.min');

// Composants
Util::addScript($appId, 'EditFileName');
Util::addScript($appId, 'FileExistsDialog');
Util::addScript($appId, 'ProgressBar');
Util::addScript($appId, 'FileTable');
Util::addScript($appId, 'WebContentViewer');

// Initialisation principale en dernier
Util::addScript($appId, 'App');

// Paramètres transmis par le contrôleur Nextcloud
$archiveUrl = $_['archiveUrl'] ?? '';
$mode = $_['mode'] ?? '';

// Propriétés attendues par App.php
$zipUrl = $archiveUrl;

// Traduction temporaire
$translate = fn($key) => \OC::$server
    ->getL10N('webtransfer')
    ->t($key);
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
    <?php require __DIR__ . '/../src/App.php'; ?>
</div>
