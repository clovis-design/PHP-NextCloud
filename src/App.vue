<template>
	<div id="app" class="h-full w-full dark:bg-black/80 bg-white/80">
		<!-- Mode normal -->
		<div v-if="!isTestMode" class="h-full w-full flex flex-col sm:flex-row">
			<!-- Première section -->
			<div
				class="w-full sm:w-1/3 max-sm:h-2/5 p-4 sm:m-6 sm:mr-0 rounded-xl dark:bg-NcBlack/30 bg-white/80">
				<WebContentViewer :translate="translate" @file-upload="handleFileUpload" @dragEnded="toggleDragEnded" :zipUrl="zipUrl"/>
			</div>
			<!-- Deuxième section -->
			<div
				class="w-full sm:w-2/3 max-sm:h-3/5 p-4 sm:m-6 sm:ml-4 dark:bg-NcBlack/30 bg-white rounded-xl">
				<FileTable :file="sharedFile" :dragEnded="dragEnded" :translate="translate" @dragEnded="toggleDragEnded"/>
			</div>
		</div>

		<!-- Mode test -->
		<div v-if="isTestMode" class="h-full w-full p-4">
			<div class="bg-white dark:bg-NcBlack/30 rounded-xl p-6">
				<h1 class="text-2xl font-bold mb-4 text-gray-800 dark:text-white">🧪 Mode Test</h1>

				<!-- Champ pour l'URL de test -->
				<div class="mb-4">
					<label for="testUrl" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
						URL de test :
					</label>
					<input
						id="testUrl"
						v-model="testUrl"
						type="text"
						class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-NcBlack/50 text-gray-800 dark:text-white"
						placeholder="https://example.com/fichier.odt"
					/>
				</div>

				<!-- Bouton pour lancer le test -->
				<button
					@click="runTest"
					class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition"
				>
					Tester
				</button>

				<!-- Résultats du test -->
				<div v-if="testResults" class="mt-6 p-4 bg-gray-100 dark:bg-NcBlack/20 rounded-md">
					<h2 class="font-semibold mb-2 text-gray-800 dark:text-white">Résultats :</h2>
					<pre class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ testResults }}</pre>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import FileTable from './components/FileTable.vue';
import WebContentViewer from './components/WebContentViewer.vue';
import './output.css';

// Traduction
import i18next from "i18next";
import file from "./assets/traduction.json";

await i18next.init({
	lng: navigator.language.split('-')[0],
	fallbackLng: "en",
	resources: file,
});

export default {
	name: 'App',
	components: {
		FileTable,
		WebContentViewer
	},
	data() {
		let zipUrl = document.getElementById('archiveInfos').getAttribute('dataarchiveurl');
		return {
			zipUrl,
			sharedFile: null,
			dragEnded: false,
			// Nouveaux champs pour le mode test
			isTestMode: false,
			testUrl: '',
			testResults: '',
		};
	},
	mounted() {
		// Vérifier si l'URL contient ?test=true
const urlParams = new URLSearchParams(window.location.search);
this.isTestMode = urlParams.get('test') === 'true';
},
methods: {
    handleFileUpload(file) {
	this.sharedFile = file;
    },
    toggleDragEnded() {
	this.dragEnded = !this.dragEnded;
	this.sharedFile = null;
    },
    translate(id) {
	return i18next.t(id);
    },
    // Nouvelle méthode pour exécuter les tests
    async runTest() {
	if (!this.testUrl) {
	    this.testResults = "❌ Veuillez entrer une URL de test.";
	    return;
	}

	try {
	    this.testResults = "⏳ Test en cours...";

	    // Tester la fonction getFilenameFromUrl
	    const filename = await this.getFilenameFromUrl(this.testUrl);
	    this.testResults = `✅ Nom2 du fichier extrait : ${filename}\n\n`;

	    // Tester la fonction loadZipContent (si applicable)
	    // Note: Vous pouvez adapter cette partie pour tester d'autres fonctions
	    // this.testResults += `Contenu chargé : ${JSON.stringify(this.zipContent, null, 2)}`;

	} catch (error) {
	    this.testResults = `❌ Erreur : ${error.message}`;
	    console.error("Erreur lors du test :", error);
	}
    },
    // Fonction pour extraire le nom du fichier depuis une URL
    async getFilenameFromUrl(fileUrl) {
	try {
	    const metadataUrl = OC.generateUrl('/apps/webtransfer/getFileMetadata?url=' + encodeURIComponent(fileUrl));
	    const metadataResponse = await fetch(metadataUrl);
	    const metadataJson = await metadataResponse.json();

	    const originalFileName = metadataJson.parameters.filename;
	    console.log("Nom du fichier :", originalFileName);

	    return "originalFileName: " + originalFileName + " json:" + metadataJson;
	    
	} catch (error) {
	    console.error("Erreur dans getFilenameFromUrl :", error);
	    return "erreur" + error; //fileUrl.split('/').pop();
	}
    }
},
};
</script>

<style>

</style>
