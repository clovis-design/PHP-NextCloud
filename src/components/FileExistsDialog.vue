<template>
    <div class="fixed inset-0 flex items-center justify-center bg-gray-700 bg-opacity-50 z-50">
        <div v-if="!displayRename && !displayOverwrite" class="dark:bg-NcBlack bg-white rounded-lg shadow-lg p-6 w-96">
            <h2 class="text-lg font-semibold mb-4">{{ translate('file.already.exist') }}</h2>
            <p>{{ translate('file.pt.1') }}{{ fileName }}{{ translate('file.pt.2') }}</p>
            <div class="flex justify-end mt-4 space-x-2">
                <button @click="toggleOverwrite">{{ translate('overwrite') }}</button>
                <button v-if="!isDirectory" @click="toggleRename">{{ translate('rename') }}</button>
                <button @click="onCancel">{{ translate('cancel') }}</button>
            </div>
        </div>

        <!-- Renommer le fichier -->
        <div v-if="displayRename" class="dark:bg-NcBlack bg-white rounded-lg shadow-lg p-6 w-96">
            <h2 class="text-lg font-semibold mb-4">{{ translate('change.file.name') }}</h2>
            <input
                type="text"
                v-model="newFileName"
                @input="onInputChange"
                @keyup.enter="save"
                :placeholder="translate('enter.file.name')"
                class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <div class="flex justify-end mt-4 space-x-2">
                <button @click="save" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition">{{ translate('confirm') }}</button>
                <button @click="toggleRename" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition">{{ translate('cancel') }}</button>
            </div>
        </div>

        <!-- Appliquer l'ecrasement a tous -->
        <div v-if="displayOverwrite" class="dark:bg-NcBlack bg-white rounded-lg shadow-lg p-6 w-96">
            <h2 class="text-lg font-semibold mb-4">{{ translate('you.are.going.to.erase.file.folder') }}</h2>
            <div class="flex items-center content-evenly">
                <input type="checkbox" v-model="forAll" />
                <p>{{ translate('apply.to.all.*') }}</p>
            </div>
            <p class="text-xs text-gray-400">{{ translate('*.text') }}</p>
            <div class="flex justify-end mt-4 space-x-2">
                <button @click="onOverwrite" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition">{{ translate('confirm') }}</button>
                <button @click="toggleOverwrite" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition">{{ translate('cancel') }}</button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        fileName: {
            type: String,
            required: true
        },
        isDirectory:{
            type: Boolean,
            required: true,
        },
        translate: {
            type: Function,
            Required: true,
        }
    },
    data() {
        var newFileName = this.fileName;
        var extension = '';
        if(!this.isDirectory) {
            let nameSplit = newFileName.split('.');
            if (nameSplit.length > 1) {
                extension = nameSplit.pop();
            }
        }
        
        return {
            displayRename: false,
            displayOverwrite: false,
            forAll: false,
            newFileName,
            extension,
        }
    },
    methods: {
        onOverwrite() {
            this.$emit('overwrite', {forAll : this.forAll});
        },
        onCancel() {
            this.$emit('cancel');
        },
        toggleRename() {
            this.displayRename = !this.displayRename;
        },
        toggleOverwrite(){
            this.displayOverwrite = !this.displayOverwrite;
        },
        save() {
            if(this.newFileName !== ''){
                // Séparer le nom de fichier sans l'extension
                const fileNameWithoutExtension = this.newFileName.slice(0, this.newFileName.lastIndexOf('.'));
                const fileNameWithoutPoints = fileNameWithoutExtension.replace(/\./g, "");
                if(fileNameWithoutPoints !== '' ) {
                    // Re-construire le nom du fichier avec l'extension d'origine
                    const newFileNameWithOriginalExtension = fileNameWithoutExtension + '.' + this.extension;

                    if (!this.isDirectory && this.newFileName !== newFileNameWithOriginalExtension) {
                        // L'extension a été modifiée, on rétablit l'extension correcte
                        this.newFileName = newFileNameWithOriginalExtension;
                    }

                    this.$emit("rename", { newFileName: this.newFileName });
                }
            }
        },
        onInputChange() {
            if (!this.isDirectory) {
                const fileNameWithoutExtension = this.newFileName.slice(0, this.newFileName.lastIndexOf('.'));
                const newFileNameWithOriginalExtension = fileNameWithoutExtension + '.' + this.extension;

                // Si l'extension est différente de celle d'origine, on la rétablit
                if (this.extension !== '' && this.newFileName !== newFileNameWithOriginalExtension) {
                    // Vous pouvez ici vérifier si l'extension a été modifiée et la rétablir
                    
                    this.newFileName = newFileNameWithOriginalExtension;
                }
            }
        },
    }
};
</script>

<style></style>