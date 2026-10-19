<template>
    <div class="flex flex-col h-full w-full border">
        <!-- Breadcrumb -->
        <div class="flex flex-row mt-1 items-start container">
            <NcBreadcrumbs class="max-h-8">
                <NcBreadcrumb name="Home" title="Title of the Home folder" @click="handleClickBreadcrumb(-1)">
                </NcBreadcrumb>
                <NcBreadcrumb v-if="getBreadcrumbParts().length > 0" v-for="(part, index) in breadcrumbParts"
                    :key="index" :name="part" @click="handleClickBreadcrumb(index)">
                </NcBreadcrumb>
            </NcBreadcrumbs>
        </div>

        <div class="flex h-12 items-center border-b border-gray-300">
            <div @click.stop class="flex items-center cursor-pointer">
                <input type="checkbox" id="checkbox-file"
                    class="NIQUE TA MERE CA CHANGE RIEN PARCE QUE HTML/CSS C DE LA MERDE"
                    @change="handleCheckAll($event)" v-model="checkedAll" />
            </div>
            <div class="ml-2 w-5/6 px-4 py-2 text-gray-500 font-semibold border-r border-gray-300">{{
                translate('name')}}</div>
            <div class="w-1/6 px-4 py-2 text-gray-500 font-semibold">{{ translate('size') }}</div>
        </div>
        <!-- Archive depliee -->
        <div v-if="!isLoading && zipContent.length !== 0" class="overflow-y-auto h-full">
            <div v-for="(file, index) in cachedSortedFiles" :key="file.fullPath" class="flex flex-col">

                <div class="flex flex-row w-full gap-2">

                    <div v-if="isVisible(file)" @click.stop class="flex items-center cursor-pointer">
                        <input type="checkbox" id="checkbox-file"
                            class="NIQUE TA MERE SA CHANGE RIEN PARCE QUE HTML/CSS C DE LA MERDE"
                            @change="handleCheckboxChange(file, $event)" :checked="isChecked(file)" />
                    </div>

                    <div class="flex w-full h-16 dark:hover:bg-NcGray hover:bg-NcWhite items-center pl-4 cursor-pointer rounded-lg border-b last:border-b-0 border-gray-300"
                        @click="toggleFolder(file)" v-if="file.isDirectory && isVisible(file)" draggable="true"
                        @dragstart="onDragStart(file)" @dragend="onDragEnd">

                        <div class="w-5/6 flex items-center py-2 border-r border-gray-300 cursor-pointer">
                            <div class="w-12 h-12 flex items-center justify-center cursor-pointer">
                                <template>
                                    <svg fill="currentColor" viewBox="0 0 24 24" class="text-NcBlue w-10 h-10 ">
                                        <path
                                            d="M10,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V8C22,6.89 21.1,6 20,6H12L10,4Z">
                                        </path>
                                    </svg>
                                </template>
                            </div>
                            <div class="w-4/6 flex items-center py-2 border-r border-gray-300 cursor-pointer">
                                <span class="ml-2 truncate cursor-pointer">{{ file.name }}</span>
                            </div>
                        </div>
                        <div class="w-1/6 px-4 py-2 cursor-pointer">-</div>
                    </div>


                    <div class="flex h-16 w-full dark:hover:bg-NcGray hover:bg-NcWhite items-center pl-4 cursor-pointer rounded-lg border-b last:border-b-0 border-gray-300"
                        v-else-if="isVisible(file)" draggable="true" @dragstart="onDragStart(file, $event)">
                        <template>
                            <div class="flex items-center justify-center cursor-pointer">
                                <svg viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" xml:space="preserve"
                                    class="w-10 h-10"
                                    style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2">
                                    <path
                                        d="M6 22c-.55 0-1.021-.196-1.412-.587A1.927 1.927 0 0 1 4 20V4c0-.55.196-1.021.588-1.413A1.926 1.926 0 0 1 6 2h8l6 6v12a1.93 1.93 0 0 1-.587 1.413A1.93 1.93 0 0 1 18 22H6Z"
                                        style="fill:#969696;fill-rule:nonzero"
                                        transform="matrix(.7 0 0 .7 -.43 -.388)" />
                                </svg>
                            </div>
                        </template>
                        <div class="w-5/6 flex items-center px-4 py-2  cursor-pointer">
                            <div class="truncate max-sm:max-w-32 max-w-96 cursor-pointer">{{ file.name }}</div>
                        </div>
                        <div class="w-1/6 py-2 cursor-pointer">
                            {{ formatFileSize(file.size) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="isLoading" class="flex h-full items-center justify-center">
            <component :is="Loading" class="text-white w-24 h-24 animate-spin" :size="40" />
        </div>
        <div v-if="!isLoading && zipContent.length === 0" class="flex h-full items-center justify-center">
            <span class="text-gray-500">{{ translate('no.content') }}</span>
        </div>
    </div>
</template>

<script>
    import NcBreadcrumbs from '@nextcloud/vue/dist/Components/NcBreadcrumbs.js';
import NcBreadcrumb from '@nextcloud/vue/dist/Components/NcBreadcrumb.js';
import JSZip from 'jszip';
import ChevronRightIcon from 'vue-material-design-icons/ChevronRight.vue';
import ChevronDownIcon from 'vue-material-design-icons/ChevronDown.vue';
import Loading from 'vue-material-design-icons/Loading.vue';
import { ref } from 'vue';
import {fileTypeFromBuffer} from 'file-type';

export default {
    name: 'WebContentViewer',
    components: {
        NcBreadcrumbs,
        NcBreadcrumb
    },
    data() {
        return {
            zipContent: [],
            folderMap: {},
            archiveUrl: '',
            mode : '',
            ChevronRightIcon,
            ChevronDownIcon,
            isLoading: ref(false),
            Loading,
            zipName: '',
            zipSize: 0,
            currentDir: '',
            breadcrumbParts: [],
            cochedFiles: [],
            checkedAll: false,
            cachedSortedFiles: null,
        };
    },
    props: {
        zipUrl: {
            type: String,
            required: true,
        },
        translate: {
            type: Function,
            Required: true,
        }
    },
    computed:{
        sortedFiles() {
            const flattenAndSort = (files, parentPath = '') => {
                const flatList = [];
                files.forEach(file => {
                    const fullPath = parentPath ? `${parentPath}/${file.name}` : file.name;

                    // Toujours ajouter le dossier parent
                    flatList.push({
                        ...file,
                        fullPath,
                        parentPath,
                    });

                    // Ajouter les enfants uniquement si le dossier est ouvert
                    if (file.isDirectory && this.folderMap[fullPath] && file.children) {
                        flatList.push(...flattenAndSort(file.children, fullPath));
                    }
                });

                return flatList.sort((a, b) => a.fullPath.localeCompare(b.fullPath));
            };

            return flattenAndSort(this.zipContent);
        },
    },
    watch: {
        sortedFiles: {
            handler(newVal) {
                this.cachedSortedFiles = newVal; // Met à jour la variable chaque fois que sortedFiles change
            },
            immediate: true, // Met à jour dès que le composant est monté
        },
    },
    async mounted() {
        this.isLoading = true;
        const webTransferDiv = document.getElementById('archiveInfos');
        if (webTransferDiv) {
            this.archiveUrl = webTransferDiv.dataset.archiveUrl;
            this.mode = webTransferDiv.getAttribute('feur');
        } else {
            console.error('Pas d\'informations pour recuperer l\'archive');
        }
        await this.loadZipContent();
        
        this.isLoading = false;
    },
    methods: {
        async loadZipContent() {
            try {
		const metadataUrl = OC.generateUrl('/apps/webtransfer/getFileMetadata?url=' + encodeURIComponent(this.zipUrl));
		const metadataResponse = await fetch(metadataUrl);
		const metadataJson = await metadataResponse.json();
		const originalFileName = metadataJson.parameters.filename;

                var baseUrl = OC.generateUrl('/apps/webtransfer/getFile');
                let fullUrl = baseUrl + '?url=' + encodeURIComponent(this.zipUrl);
                let response = await fetch(fullUrl);
                let responseJson = await response.json();
                const zipData = responseJson.parameters.data;

		const first10Chars = zipData.substring(0,4);

                if(this.mode === "zip") {
                    // Check si le debut du fichier correspond a celui d'un zip
                    if(!this.isNotToBeUncompressed() && (first10Chars === 'PK\x03\x04' || first10Chars === 'PK\x05\x06' || first10Chars === 'PK\x07\x08')) {
                        this.zipName = this.zipUrl.split('/').pop();
                        const zip = await JSZip.loadAsync(zipData);
                        this.zipSize = zipData.size;

                        const files = [];

                        zip.forEach((relativePath, file) => {
                            const pathParts = relativePath.split('/').filter(Boolean);
                            let currentLevel = files;

                            for (let i = 0; i < pathParts.length; i++) {
                                const partName = pathParts[i];
                                const isDirectory = i < pathParts.length - 1 || file.dir;
                                let existing = currentLevel.find(f => f.name === partName && f.isDirectory === isDirectory);

                                let promise;

                                if (!isDirectory) {
                                    promise = file.async("blob").then(content => {
                                        existing.content = content;
                                    });
                                }

                                if (!existing) {
                                    existing = {
                                        name: pathParts[i],
                                        isDirectory,
                                        size: isDirectory ? 0 : file._data.uncompressedSize,
                                        content: isDirectory ? null : '',  // Initialiser 'content' pour les fichiers
                                        children: isDirectory ? [] : null,
                                        depth: pathParts.length, // Profondeur du fichier dans l'arborescence
                                        //remove the name of the file from the path
                                        parentPath: i > 0 ? pathParts[i - 1] : '',
                                        unzip: promise
                                    };
                                    currentLevel.push(existing);
                                }

                                if (isDirectory) {
                                    currentLevel = existing.children;
                                }
                            }
                        });

                        // Attendre que tous les contenus de fichier soient extraits
                        this.zipContent = files;

                        // Initialiser folderMap
                        const initializeFolderMap = (files, parentPath = '') => {
                            files.forEach(file => {
                                const fullPath = parentPath ? `${parentPath}/${file.name}` : file.name;
                                this.$set(this.folderMap, fullPath, false);
                                if (file.isDirectory && file.children) {
                                    initializeFolderMap(file.children, fullPath);
                                }
                            });
                        };

                        initializeFolderMap(this.zipContent);
                        console.log('Contenu du ZIP chargé avec succès');
                    }
                    else {
                        throw new Error("ERROR : file is not a ZIP file.");
                    }
                }
                else if(this.mode == "file"){
                    const uint8Array = new Uint8Array(zipData.length);
                    for (let i = 0; i <zipData.length; i++) {
                        uint8Array[i] = zipData.charCodeAt(i);
                    }

                    try {
                        let type = await fileTypeFromBuffer(uint8Array);
			
			const file = new File([uint8Array], originalFileName, { type: type.mime });

			//const file = new File([uint8Array], 'file.' + type.ext, {type: type.mime});
                        let entry = [{
			    name: file.name,
                            isDirectory: false,
                            size: file.size,
                            content: uint8Array,  // Initialiser 'content' pour les fichiers
                            children: null,
                            depth: 0, // Profondeur du fichier dans l'arborescence
                            //remove the name of the file from the path
                            parentPath: '',
                        }]
                        this.zipContent = entry;
                        console.log('Fichier chargé avec succès');
                    } catch (e) {
                        console.log('Erreur lors du telechargement du fichier.');
                    }
                }
                else {
                    throw new Error("ERROR : unknowed mode :", this.mode);
                }
            } catch (error) {
                console.error('Erreur lors du chargement du contenu du ZIP :', error);
            }
        },
        isNotToBeUncompressed(){
            /**
             * Vérifie si l'archive à extraire fait partie des fichiers qu'il ne faut pas extraire
             * 
             * @returns true si c'est un type de fichier à ne pas extraire, false sinon
             */
            const forbiddenExtensions = ['.docx', '.xlsx', '.odt', '.epub', '.pptx'];

            const fileName = this.zipUrl.split('/').pop().toLowerCase();
            const hasForbiddenExtension = forbiddenExtensions.some(ext => fileName.endsWith(ext));

            if (hasForbiddenExtension) {
                return true;
            }
            else {
                return false;
            }
        },
        onDragEnd(event) {
            event.preventDefault();
            this.$emit('dragEnded');
        },
        handleCheckboxChange(file, event) {
            if (event.target.checked) {
                this.cocheFile(file);
            } else {
                this.decocheFile(file);
            }
        },
        getFullPath(file) {
            if (!file.parentPath || file.parentPath === '') {
                return file.name;
            } else {
                return `${file.parentPath}/${file.name}`;
            }
        },
        cocheFile(file) {
            if (!this.cochedFiles.some(f => this.getFullPath(f) === this.getFullPath(file))) {
                this.cochedFiles.push(file);
            }
        },
        decocheFile(file) {
            this.cochedFiles = this.cochedFiles.filter(f => this.getFullPath(f) !== this.getFullPath(file));
        },
        isChecked(file) {
            return this.cochedFiles.some(f => this.getFullPath(f) === this.getFullPath(file));
        },
        formatFileSize(size) {
            if (size < 1024) return `${size} B`;
            if (size < 1024 * 1024) return `${(size / 1024).toFixed(2)} KB`;
            if (size < 1024 * 1024 * 1024) return `${(size / 1024 / 1024).toFixed(2)} MB`;
            return `${(size / 1024 / 1024 / 1024).toFixed(2)} GB`;
        },
        toggleFolder(file) {
            if (!file.isDirectory) return;
            this.uncheckAll();
            const currentState = this.folderMap[file.fullPath];
            const parentPath = file.parentPath;

            if (!currentState) {
                this.currentDir = file.fullPath;
            }
            else {
                this.currentDir = parentPath;
            }
            this.$set(this.folderMap, file.fullPath, !currentState);

            if (parentPath !== '') {
                const parentState = this.folderMap[parentPath];
                this.$set(this.folderMap, parentPath, !parentState);
            }
            this.breadcrumbParts = this.getBreadcrumbParts()
        },
        async dragZip() {
            try {
                const zip = { name: this.zipName, url: this.zipUrl };
                this.$emit('zip-upload', zip);
            } catch (error) {
                console.error('Erreur lors du drag du ZIP :', error);
            }
        },
        async onDragStart(file, event) {
            const getFilesFromFolder = (folder) => {
                const files = [];
                if (!folder.children || folder.children.length === 0) return files;

                for (let i = 0; i < folder.children.length; i++) {
                    const child = folder.children[i];
                    if (child.isDirectory) {
                        files.push(...getFilesFromFolder(child));
                    } else {
                        files.push(child);
                    }
                }
                return files;
            };

            if (this.cochedFiles.length > 0) {

                const folder = {
                    // Si des fichiers sont cochés, utiliser cette liste
                    name: file.name,
                    isDirectory: true,
                    isList: true,
                    children: this.cochedFiles,
                    unzip: Promise.all(this.cochedFiles.map(file => file.unzip))
                };

                try {
                    await folder.unzip;
                    this.$emit('file-upload', folder);
                } catch (error) {
                    console.error('Erreur lors du drag start :', error);
                }

            } else {
                // Logique existante pour un seul fichier/dossier
                try {
                    if (file.isDirectory) {
                        const files = getFilesFromFolder(file);
                        const filesToUnzip = files.map(file => file.unzip);
                        await Promise.all(filesToUnzip);
                    } else {
                        await file.unzip;
                    }
                    this.$emit('file-upload', file);
                } catch (error) {
                    console.error('Erreur lors du drag start :', error);
                }
            }
        },
        isVisible(file) {
            let parentPath = file.parentPath;
            if (this.currentDir === parentPath) {
                return true;
            }
            else {
                return false;
            }
        },
        getBreadcrumbParts() {
            // Si le currentDir est un simple '/', on le renvoie sous forme de tableau vide.
            if (this.currentDir === '') return [];
            return this.currentDir.split('/').filter(part => part);
        },
        generateCrumbHref(index) {
            const parts = this.breadcrumbParts.slice(0, index + 1);
            return parts.join('/');
        },
        handleClickBreadcrumb(index) {
            this.uncheckAll();
            if (this.isTransfering) return;
            let dir = '';
            if (index >= -1) {
                dir = this.generateCrumbHref(index);
            }
            this.currentDir = dir;
            this.breadcrumbParts = this.getBreadcrumbParts();
            let file = {
                fullPath: dir,
                parentPath: this.generateCrumbHref(index - 1),
                isDirectory: true,
            };

            Object.keys(this.folderMap).forEach(key => {
                this.folderMap[key] = false;
            });
            this.toggleFolder(file)
        },
        uncheckAll() {
            this.cochedFiles = [];
            this.checkedAll = false;
        },
        handleCheckAll(event) {
            this.sortedFiles.forEach(file => {
                if(this.isVisible(file)) {
                    if (event.target.checked) {
                        this.cocheFile(file);
                    } else {
                        this.decocheFile(file);
                    }
                }
            })
        }
    },
};
</script>

<style scoped>
/* Ajoutez ici des styles si nécessaire */
</style>
