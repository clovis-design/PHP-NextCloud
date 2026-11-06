/* eslint-disable semi */
/* eslint-disable indent */

const app = document.getElementById('app');

if (app) {
  const normalMode = app.querySelector('#normalMode');
  const testMode = app.querySelector('#testMode');

  const testUrlInput = app.querySelector('#testUrl');
  const runTestButton = app.querySelector('#runTestButton');
  const testResultsContainer = app.querySelector('#testResultsContainer');
  const testResults = app.querySelector('#testResults');

  // Équivalent de data() dans Vue
  let sharedFile = null;
  let dragEnded = false;

  // Équivalent de mounted()
  const urlParams = new URLSearchParams(window.location.search);
  const isTestMode = urlParams.get('test') === 'true';

  normalMode.hidden = isTestMode;
  testMode.hidden = !isTestMode;

  // Équivalent de handleFileUpload()
  function handleFileUpload(file) {
    sharedFile = file;

    // Prévenir FileTable qu'un fichier a été sélectionné
    app.dispatchEvent(
      new CustomEvent('webtransfer:file-selected', {
        bubbles: true,
        detail: {
          file: sharedFile,
        },
      }),
    );
  }

  // Équivalent de toggleDragEnded()
  function toggleDragEnded() {
    dragEnded = !dragEnded;
    sharedFile = null;

    app.dispatchEvent(
      new CustomEvent('webtransfer:drag-ended', {
        bubbles: true,
        detail: {
          dragEnded,
        },
      }),
    );
  }

  // Recevoir les événements de WebContentViewer
  app.addEventListener('file-upload', (event) => {
    event.stopPropagation();
    handleFileUpload(event.detail);
  });

  app.addEventListener('dragEnded', (event) => {
    event.stopPropagation();
    toggleDragEnded();
  });

  // Tester la récupération du nom depuis une URL
  async function getFilenameFromUrl(fileUrl) {
    if (typeof OC === 'undefined') {
      throw new Error('API Nextcloud OC indisponible dans cette page');
    }

    const metadataUrl = OC.generateUrl('/apps/webtransfer/getFileMetadata');

    const response = await fetch(`${metadataUrl}?url=${encodeURIComponent(fileUrl)}`);

    if (!response.ok) {
      throw new Error(`Erreur HTTP ${response.status}`);
    }

    const metadataJson = await response.json();

    const originalFileName = metadataJson?.parameters?.filename;

    if (!originalFileName) {
      throw new Error('Nom du fichier absent de la réponse');
    }

    return originalFileName;
  }

  // Équivalent de runTest()
  async function runTest() {
    const testUrl = testUrlInput.value.trim();

    testResultsContainer.hidden = false;

    if (!testUrl) {
      testResults.textContent = '❌ Veuillez entrer une URL de test.';
      return;
    }

    try {
      testResults.textContent = '⏳ Test en cours...';

      const filename = await getFilenameFromUrl(testUrl);

      testResults.textContent = `✅ Nom du fichier extrait : ${filename}`;
    } catch (error) {
      testResults.textContent = `❌ Erreur : ${error.message}`;
      console.error('Erreur lors du test :', error);
    }
  }

  // Remplacer @click="runTest"
  runTestButton?.addEventListener('click', runTest);
}
