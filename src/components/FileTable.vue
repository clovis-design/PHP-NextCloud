<template>
    <div class="h-full">
        <!-- Boutons pour fichiers :thumbsup: -->
        <div class="flex flex-row gap-2 p-2">
            <button
                :class="getClassButton('default')"
                @click="changeTab('default')"
            >
                {{ translate('all.files') }}
            </button>
            <button 
                :class="getClassButton('favorites')"
                @click="changeTab('favorites')"
            >
                {{ translate('favorites') }}
            </button>
        </div>

        <div class="flex flex-col h-full w-full border">
            <!-- Breadcrumb -->
            <div class="flex flex-row mt-1 ml-3 items-start container">
                <NcBreadcrumbs class="max-h-8">
                    <NcBreadcrumb name="Home" title="Title of the Home folder" @click="handleClickBreadcrumb(-1)">
                    </NcBreadcrumb>
                    <NcBreadcrumb v-if="getBreadcrumbParts().length > 0" v-for="(part, index) in breadcrumbParts"
                        :key="index" :name="part" @click="handleClickBreadcrumb(index)">
                    </NcBreadcrumb>
                    <template #actions>
                        <div class="flex items-center ml-2">
                            <button v-if="!isTransfering" @click="toggleAddFilePopup" :disabled="currentTab === 'favorites' && current_dir === '/'"
                                class="flex items-center space-x-2 bg-blue-100 text-blue-600 font-medium px-4 py-2 rounded-md hover:bg-blue-200 transition">
                                <Plus :size="20" />
                                <span>{{translate('new')}}</span>
                            </button>
                            <div v-else>
                                <ProgressBar :value="transferProgress" :color="transferStatus" />
                            </div>
                        </div>
                    </template>
                </NcBreadcrumbs>
            </div>


            <!-- Popup pour la création de fichier -->
            <div v-if="isAddFilePopupVisible"
                class="fixed inset-0 flex items-center justify-center bg-gray-700 bg-opacity-50 z-50">
                <div class="dark:bg-NcBlack bg-white rounded-lg shadow-lg p-6 w-96">
                    <h2 class="text-lg font-semibold mb-4">{{ translate('create.new.file') }}</h2>
                    <input v-model="newFileName" type="text" :placeholder="translate('name.of.file')"
                        class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    <div class="flex justify-end mt-4 space-x-2">
                        <button @click="createNewFile"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                            {{translate('create')}}
                        </button>
                        <button @click="toggleAddFilePopup"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition">
                            {{translate('cancel')}}
                        </button>
                    </div>
                </div>
            </div>

            <!-- En-tête -->
            <div class="flex h-12 items-center border-b border-gray-300">
                <div class="w-7/12 px-4 py-2 text-gray-500 font-semibold border-r border-gray-300">{{ translate('name') }}</div>
                <div class="w-2/12 px-4 py-2 text-gray-500 font-semibold border-r border-gray-300">{{ translate('type') }}</div>
                <div class="w-2/12 px-4 py-2 text-gray-500 font-semibold">{{ translate('size') }}</div>
                <div class="w-1/12 px-4 py-2 text-gray-500 font-semibold">{{ translate('options') }}</div>
            </div>

            <!-- Contenu -->
            <div :class="[
                'overflow-y-auto h-full mb-14 rounded-xl',
                isDragging && isDroppable ? 'border-green-500 border-4 border-dashed transition-all ease-in-out' : 
                isDragging && !isDroppable ? 'border-red-500 border-4 border-dashed transition-all ease-in-out !cursor-no-drop' : ''
            ]" @drop.prevent="onDrop" @dragover.prevent="onDragOver" @dragenter.prevent="onDragEnter"
                @dragleave.prevent="onDragLeave($event)" @dragend="onDragEnd">

                <div v-for="file in files" :key="file.filename"
                    class="flex h-16 items-center dark:hover:bg-NcGray/20 hover:bg-NcWhite rounded-lg border-b last:border-b-0 border-gray-300 cursor-pointer"
                    @click="handleClickElem(file)">

                    <!-- Nom -->
                    <div class="w-7/12 flex items-center px-4 py-2 border-r border-gray-300 cursor-pointer">
                        <div class="w-12 h-12 flex items-center justify-cente cursor-pointer">
                            <template v-if="file.type === 'directory'">
                                <svg fill="currentColor" viewBox="0 0 24 24" class="text-NcBlue w-10 h-10 ">
                                    <path
                                        d="M10,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V8C22,6.89 21.1,6 20,6H12L10,4Z">
                                    </path>
                                </svg>
                            </template>
                            <template v-if="file.type === 'file' && file.basename.split('.').pop() !== 'zip'">
                                <div :class="['flex items-center justify-center cursor-pointer']">
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
                            <template v-if="file.type === 'file' && file.basename.split('.').pop() === 'zip'">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-10 h-10 ">
                                    <path fill="#969696"
                                        d="M5.12,5H18.87L17.93,4H5.93L5.12,5M20.54,5.23C20.83,5.57 21,6 21,6.5V19A2,2 0 0,1 19,21H5A2,2 0 0,1 3,19V6.5C3,6 3.17,5.57 3.46,5.23L4.84,3.55C5.12,3.21 5.53,3 6,3H18C18.47,3 18.88,3.21 19.15,3.55L20.54,5.23M6,18H12V15H6V18Z" />
                                </svg>
                            </template>
                        </div>
                        <div class="ml-4 cursor-pointer max-sm:max-w-32 truncate">{{ file.basename }}</div>
                    </div>

                    <!-- Type -->
                    <div class="w-2/12 px-4 py-2 border-r border-gray-300 cursor-pointer">
                        {{ file.type === 'directory' ? 'Dossier' : 'Fichier' }}
                    </div>

                    <!-- Taille -->
                    <div class="w-2/12 px-4 py-2 cursor-pointer">
                        {{ file.type === 'directory' ? '-' : formatFileSize(file.size) }}
                    </div>

                    <!-- Options -->
                    <div class="w-1/12 px-4 py-2" @click.stop>
                        <NcActions>
                            <NcActionButton @click="deleteElem(file)" :closeAfterClick="true">
                                <template #icon>
                                    <Delete :size="20" />
                                </template>
                                {{ translate('delete') }}
                            </NcActionButton>
                            <NcActionButton @click="editElem(file)" :closeAfterClick="true">
                                <template #icon>
                                    <Pencil :size="20" />
                                </template>
                                {{ translate('edit') }}
                            </NcActionButton>
                        </NcActions>
                    </div>
                </div>
            </div>

            <EditFileName v-if="!editDialogDisabled" :initialFileName="initialFileName" :isDirectory="isDirectory" :translate="translate"
                @update="updateFileName" @close="closeEditDialog">
            </EditFileName>
            <FileExistsDialog v-if="!fileExistDialogDisabled" :fileName="initialFileName" :isDirectory="isDirectory" :translate="translate"
                @overwrite="setOverwrite" @rename="setRename" @cancel="cancelDrop">
            </FileExistsDialog>
        </div>
    </div>
</template>

<script>
// NextCloud Components
import { getClient, getRootPath, getFavoriteNodes } from '@nextcloud/files/dav';
import NcBreadcrumbs from '@nextcloud/vue/dist/Components/NcBreadcrumbs.js';
import NcBreadcrumb from '@nextcloud/vue/dist/Components/NcBreadcrumb.js';
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js';
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js';

// Custom components
import ProgressBar from './ProgressBar.vue';
import EditFileName from './EditFileName.vue';
import FileExistsDialog from './FileExistsDialog.vue';

// Icons
import Plus from 'vue-material-design-icons/Plus.vue'
import Delete from 'vue-material-design-icons/Delete.vue';
import Pencil from 'vue-material-design-icons/Pencil.vue'

export default {
    name: 'FileTable',
    components: {
        NcBreadcrumbs,
        NcBreadcrumb,
        Plus,
        NcActions,
        NcActionButton,
        ProgressBar,
        Delete,
        Pencil,
        EditFileName,
        FileExistsDialog
    },
    props: {
        file: {
            type: Object,
            default: null,
        },
        dragEnded: {
            type: Boolean,
            Required: true,
        },
        translate: {
            type: Function,
            Required: true,
        }
    },
    watch: {
        dragEnded(val) {
            if(val === true) {
                this.isDragging = false;
                this.isDroppable = false;
                this.$emit('dragEnded');
            }
        }
    },
    data() {
        return {
            trad: null,
            files: [], // Liste des fichiers et dossiers récupérés
            root_path: getRootPath(),
            current_dir: '/',
            breadcrumbParts: [],
            isAddFilePopupVisible: false,
            newFileName: '',
            isTransfering: false,
            isDragging: false,
            isDroppable: true,
            editDialogDisabled: true,
            fileExistDialogDisabled: true,
            initialFileName: '', // Nom originel du fichier/dossier a edite
            isDirectory: false, // Si l'element a edite est un dossier ou non
            transferProgress: 0,
            transferStatus: 'bg-blue-500',
            overwrite: false,
            applyToAll: false,
            cancelOperation: false,
            rename: false,
            newElemName: '',
            currentTab: 'default',
        };
    },
    async mounted() {
        await this.fetchFiles();
        this.breadcrumbParts = this.getBreadcrumbParts();
    },
    methods: {
        async changeTab(name) {
            this.currentTab = name;
            this.current_dir = '/';
            await this.fetchFiles();
            if(this.currentTab === 'default'){
                this.isDroppable = true;
            }
            else {
                this.isDroppable = false;
            }
        },
        async fetchFiles() {
            try {
                const client = getClient();
                let directoryItems;
                if (this.currentTab === 'default' || (this.currentTab === 'favorites' && this.current_dir !== '/')){
                    directoryItems = await client.getDirectoryContents(this.root_path + this.current_dir);
                }
                else if(this.currentTab === 'favorites'){
                    let favoriteNodes = await getFavoriteNodes(client);
                    directoryItems = this.computeFavoritesNodes(favoriteNodes);
                }

                this.files = directoryItems.map(file => ({
                    basename: file.basename,
                    filename:file.filename,
                    size: file.size,
                    href: client.getFileDownloadLink(file.filename),
                    type: file.type
                }));
            } catch (error) {
                console.error('Erreur lors de la récupération des fichiers et dossiers :', error);
            }
        },
        computeFavoritesNodes(favoriteNodes) {
            let directoryItems = [];

            let i = 0;
            favoriteNodes.forEach(element => {
                // Création de l'objet elemData pour chaque élément
                let elemData = {
                    basename: element._data.displayname,
                    etag: element._attributes.etag,
                    filename: element._attributes.filename,
                    lastmod: element._attributes.lastmod,
                    mime: element._data.mime,
                    size: element._data.size,
                    type: element._attributes.type,
                };

                // Ajout de elemData à directoryItems, indexé par un identifiant unique (par exemple, basename)
                directoryItems[i] = elemData;
                i++;
            });

            return directoryItems;
        },
        formatFileSize(size) {
            if (size < 1024) return `${size} B`;
            if (size < 1024 * 1024) return `${(size / 1024).toFixed(2)} KB`;
            if (size < 1024 * 1024 * 1024) return `${(size / 1024 / 1024).toFixed(2)} MB`;
            return `${(size / 1024 / 1024 / 1024).toFixed(2)} GB`;
        },
        generateCrumbHref(index) {
            const parts = this.breadcrumbParts.slice(0, index + 1);
            return '/' + parts.join('/');
        },
        getBreadcrumbParts() {
            // Si le current_dir est un simple '/', on le renvoie sous forme de tableau vide.
            if (this.current_dir === '/') return [];
            return this.current_dir.split('/').filter(part => part);
        },
        async handleClickElem(file) {
            if (this.isTransfering) return;
            if (file.type === 'directory') {
                if(this.currentTab === 'default'){
                    this.current_dir = this.current_dir === '/' ? '/' + file.basename : this.current_dir + '/' + file.basename;
                }
                else{
                    let path = file.filename;
                    let pathSplited = path.split('/');
                    let result = pathSplited.slice(3);
                    let dir = '';
                    result.forEach(element => {
                        dir += '/' + element
                    });
                    this.current_dir = dir;
                }
                this.breadcrumbParts = this.getBreadcrumbParts()
                await this.fetchFiles();
            } else {
                window.open(file.href, '_blank');
            }
        },
        async handleClickBreadcrumb(index) {
            if (this.isTransfering) return;
            let dir = '/';
            if (index >= -1) {
                dir = this.generateCrumbHref(index);
            }
            this.current_dir = dir;
            this.breadcrumbParts = this.getBreadcrumbParts();
            await this.fetchFiles();
        },
        async createNewFile() {
            if (!this.newFileName) return;

            try {
                const client = getClient();
                let filePath = '';
                if (this.current_dir[this.current_dir.length - 1] === '/') {
                    filePath = `${this.root_path}${this.current_dir}${this.newFileName}`;
                }
                else {
                    filePath = `${this.root_path}${this.current_dir}/${this.newFileName}`;
                }
                const alreadyExists = await this.elemtAlreadyExists(filePath);
                if (!alreadyExists) {
                    await client.createDirectory(filePath, '');
                    this.newFileName = '';
                    this.isAddFilePopupVisible = false;
                    await this.fetchFiles();
                }
                else {
                    alert(this.translate("cant.create.folder") + this.newFileName + this.translate('already.exists'));
                }
            } catch (error) {
                console.error('Erreur lors de la création du fichier :', error);
            }
        },
        toggleAddFilePopup() {
            this.isAddFilePopupVisible = !this.isAddFilePopupVisible;
            if (!this.isAddFilePopupVisible) this.newFileName = '';
        },
        onDragOver(event) {
            event.preventDefault();
            if(this.currentTab === 'favorites' && this.current_dir === '/'){
                event.dataTransfer.dropEffect = 'none';
                this.isDroppable = false;
            }
            else{
                this.isDroppable = true;
            }
            if (!this.isDragging) {
                this.isDragging = true;
            } else {
                return;
            }
        },
        onDragEnter(event){
            event.preventDefault()
        },
        onDragLeave(event) {
            event.preventDefault();
            if (event.target === event.currentTarget) {
                this.isDragging = false;
            }
        },
        onDragEnd() {
            this.isDragging = false;
        },
        async onDrop(event) {
            event.preventDefault();
            this.isDragging = false; // Pour enlever le contour rouge si on ne peut pas drop sinon il reste affiche
            if(this.isDroppable){
                try {
                    this.isTransfering = true;
                    const file = this.file;
                    if (!file) return;

                    if (file.isList) {
                        await this.moveListOfFiles(file);
                    } else {
                        if (file.isDirectory) {
                            await this.moveFilesOfFolder(file, '');
                        } else {
                            this.transferProgress = 25;
                            if (file.content && typeof file.content.arrayBuffer === 'function') {
                                file.content = await file.content.arrayBuffer();
                            }
                            this.transferProgress = 50;
                            await this.moveFileToTarget(file, '');
                            this.transferProgress = 100;
                        }
                    }
                  
                    this.isTransfering = false;
                    this.transferProgress = 0;
                    this.cancelOperation = false;

                } catch (error) {
                    console.error('Erreur lors du drop :', error);
                    this.transferStatus = 'bg-red-500';
                    this.isTransfering = false;
                }
                this.overwrite = false;
                this.applyToAll = false;
                this.rename = false;
                this.newElemName = '';
            }
            this.isDroppable = true;
        },
        async moveListOfFiles(files) {
            for (const file of files.children) {
                this.transferProgress += 100 / files.children.length;
                if (file.isDirectory) {
                    await this.moveFilesOfFolder(file, file.parentPath + '/');
                } else {
                    if (file.content && typeof file.content.arrayBuffer === 'function') {
                        file.content = await file.content.arrayBuffer();
                    }
                    await this.moveFileToTarget(file, '');
                }
            }
        },
        async moveFilesOfFolder(folder, parentPath) {
            await this.createFolder(folder, parentPath + '/');
            const checkChildrenInChildren = (folder) => {
                let total = folder.children.length;
                for (const child of folder.children) {
                    if (child.isDirectory) {
                        total += checkChildrenInChildren(child);
                    }
                }
                return total;
            };

            const progressSteps = Math.floor(100 / checkChildrenInChildren(folder));

            for (const child of folder.children) {
                if(!this.cancelOperation){
                    this.transferProgress += progressSteps;
                    if (child.isDirectory) {
                        await this.moveFilesOfFolder(child, parentPath + '/' + child.parentPath + '/');
                    } else {
                        if (child.content && typeof child.content.arrayBuffer === 'function') {
                            child.content = await child.content.arrayBuffer();
                        }
                        await this.moveFileToTarget(child, parentPath + '/' + child.parentPath + '/');
                    }
                }
            }
        },
        async moveFileToTarget(file, parentPath, newName = null) {
            this.isDirectory = false;
            try {
                const client = getClient();
                // Assurez-vous que le chemin parent est correctement formaté

                let fullPath = '';
                if(!this.rename) {
                    fullPath = `${this.root_path}${this.current_dir}${parentPath}/${file.name}`;
                }
                else if (this.rename && newName){
                    fullPath = `${this.root_path}${this.current_dir}${parentPath}${newName}`;
                }
                else {
                    return null;
                }

                if (ArrayBuffer.isView(file.content)) {
                    file.content = Buffer.from(file.content);
                }

                const alreadyExists = await this.elemtAlreadyExists(fullPath);
                if(!alreadyExists || this.overwrite) {
                    // Évitez les chemins incorrects en utilisant `path.normalize` si disponible
                    await client.putFileContents(fullPath, file.content);

                    if (this.overwrite && !this.applyToAll) {
                        this.overwrite = false;
                    }

                    // Recharge les fichiers après l'opération
                    await this.fetchFiles();
                }
                else {
                    this.initialFileName = file.name;
                    this.fileExistDialogDisabled = false;
                    while (!this.fileExistDialogDisabled) {
                        await this.sleep(50);
                    }
                    if(!this.cancelOperation){
                        if(this.rename) {
                            await this.moveFileToTarget(file, parentPath, this.newElemName);
                            this.rename = false;
                        }
                        else{
                            await this.moveFileToTarget(file,parentPath);
                        }
                    }
                }
            } catch (error) {
                console.error('Erreur lors du déplacement du fichier:', error);
            }
        },
        async createFolder(folder, parentPath) {
            this.isDirectory = true;
            try {
                const client = getClient();
                let fullPath = '';
                fullPath = `${this.root_path}${this.current_dir}${parentPath}${folder.name}`;

                const alreadyExists = await this.elemtAlreadyExists(fullPath);
                if (!alreadyExists) {
                    await client.createDirectory(fullPath);
                    await this.fetchFiles();
                }
                else if(!this.applyToAll){
                    this.initialFileName = folder.name;
                    this.fileExistDialogDisabled = false;
                    while (!this.fileExistDialogDisabled) {
                        await this.sleep();
                    }
                    if(this.overwrite && !this.applyToAll) {
                        this.overwrite = false;
                    }
                }
            } catch (error) {
                console.error('Erreur lors de la création du dossier :', error);
            }
        },
        async deleteElem(file) {
            const client = getClient()
            try {
                let path = this.root_path + this.current_dir + "/" + file.basename;
                await client.deleteFile(path);
            }
            catch (error) {
                console.error('Erreur lors de la suppression d\'un element : ', error);
            }

            await this.fetchFiles();
        },
        /**
         * Change les props pour le composant EditFileName
         * @param file le ficher/dossier dont on veut editer le nom
         */
        async editElem(file) {
            if (file.type === 'file') {
                this.isDirectory = false;
            }
            else {
                this.isDirectory = true;
            }
            this.initialFileName = file.basename;
            this.editDialogDisabled = false;
        },
        /**
         * Ferme la fenetre d'edition du nom du fichier/dossier
         */
        closeEditDialog() {
            this.editDialogDisabled = true;
        },
        closeFileExistsDialog() {
            this.fileExistDialogDisabled = true;
        },
        /**
         * Change le nom du fichier sur le serveur Cloud via un client WebDAV
         * @param names Contient un initialFileName et un newFileName
         */
        async updateFileName(names) {
            if (names.initialFileName !== names.newFileName) {
                const client = getClient()
                try {
                    const oldName = this.root_path + this.current_dir + '/' + names.initialFileName;
                    const newName = this.root_path + this.current_dir + '/' + names.newFileName;
                    let alreadyExists = await this.elemtAlreadyExists(newName);
                    if (!alreadyExists) {
                        await client.moveFile(oldName, newName);
                    }
                    else {
                        alert(this.translate('cant.rename') + names.newFileName + this.translate('already.exists'));
                    }
                }
                catch (error) {
                    console.error('Erreur lors du renommage d\'un element : ', error);
                }
                await this.fetchFiles();
            }
        },
        setOverwrite(options) {
            this.overwrite = true;
            this.applyToAll = options.forAll;
            this.fileExistDialogDisabled = true;
        },
        setRename(options) {
            this.rename = true;
            this.newElemName = options.newFileName;
            this.fileExistDialogDisabled = true;
        },
        /**
         * Check si un fichier ou un dossier existe deja sur le serveur
         * @param path le chemin du fichier/dossier
         */
        async elemtAlreadyExists(path) {
            const client = getClient();
            let exists = await client.exists(path);

            return exists;
        },
        cancelDrop(){
            this.cancelOperation = true;
            this.closeFileExistsDialog();
        },
        getClassButton(name) {
            let cssStyle;

            if(this.currentTab === name) {
                cssStyle = ' !bg-NcBlue/50';
            } else {
                cssStyle = '';
            }

            return cssStyle;
        },
        async sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }
    }
};
</script>

<style scoped>
/* Vous pouvez ajouter des styles personnalisés ici si nécessaire */
</style>