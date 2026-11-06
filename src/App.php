
<?php
/**
 * App.php
 *
 * Variables attendues du parent :
 * - $zipUrl : string
 * - $translate : callable
 */

// Valeurs de secours pour les tests locaux
$zipUrl = $zipUrl ?? '';
$translate = $translate ?? fn($key) => $key;

// Équivalent du mode test
$isTestMode = ($_GET['test'] ?? '') === 'true';

// Sécuriser les textes affichés dans le HTML
$escape = fn($value) => htmlspecialchars(
    (string) $value,
    ENT_QUOTES,
    'UTF-8'
);
?>

<div
    id="app"
    class="h-full w-full dark:bg-black/80 bg-white/80"
>
    <!-- Mode normal -->
    <div
        id="normalMode"
        class="h-full w-full flex flex-col sm:flex-row"
        <?= $isTestMode ? 'hidden' : '' ?>
    >
        <!-- Première section -->
        <div
            class="w-full sm:w-1/3 max-sm:h-2/5 p-4 sm:m-6 sm:mr-0 rounded-xl dark:bg-NcBlack/30 bg-white/80"
        >
            <?php
                // Props de WebContentViewer
                // $zipUrl et $translate sont déjà disponibles

                include __DIR__ . '/WebContentViewer.php';
            ?>
        </div>

        <!-- Deuxième section -->
        <div
            class="w-full sm:w-2/3 max-sm:h-3/5 p-4 sm:m-6 sm:ml-4 dark:bg-NcBlack/30 bg-white rounded-xl"
        >
            <?php
                // Valeurs initiales des props de FileTable
                $file = null;
                $dragEnded = false;

                include __DIR__ . '/FileTable.php';
            ?>
        </div>
    </div>

    <!-- Mode test -->
    <div
        id="testMode"
        class="h-full w-full p-4"
        <?= !$isTestMode ? 'hidden' : '' ?>
    >
        <div class="bg-white dark:bg-NcBlack/30 rounded-xl p-6">
            <h1 class="text-2xl font-bold mb-4 text-gray-800 dark:text-white">
                🧪 Mode Test
            </h1>

            <!-- URL de test -->
            <div class="mb-4">
                <label
                    for="testUrl"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                >
                    URL de test :
                </label>

                <input
                    id="testUrl"
                    type="text"
                    class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-NcBlack/50 text-gray-800 dark:text-white"
                    placeholder="https://example.com/fichier.odt"
                />
            </div>

            <!-- Lancer le test -->
            <button
                id="runTestButton"
                type="button"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition"
            >
                Tester
            </button>

            <!-- Résultats -->
            <div
                id="testResultsContainer"
                class="mt-6 p-4 bg-gray-100 dark:bg-NcBlack/20 rounded-md"
                hidden
            >
                <h2 class="font-semibold mb-2 text-gray-800 dark:text-white">
                    Résultats :
                </h2>

                <pre
                    id="testResults"
                    class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap"
                ></pre>
            </div>
        </div>
    </div>
</div>
