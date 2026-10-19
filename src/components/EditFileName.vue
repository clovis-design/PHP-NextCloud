<template>
<div  class="fixed inset-0 flex items-center justify-center bg-gray-700 bg-opacity-50 z-50" @click="closeModal">
    <div class="dark:bg-NcBlack bg-white rounded-lg shadow-lg p-6 w-96" @click.stop>
        <h2 class="text-lg font-semibold mb-4">{{ translate('modify.file.name') }}</h2>
        <input
            type="text"
            v-model="newFileName"
            @input="onInputChange"
            @keyup.enter="save"
            placeholder="Entrez le nom du fichier"
            class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
        <div class="flex justify-end mt-4 space-x-2">
            <button @click="save" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition">{{ translate('rename') }}</button>
            <button @click="closeModal" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition">{{ translate('cancel') }}</button>
        </div>
    </div>
</div>
</template>

<script>
export default {
    name: "FileNameEditor",
    props: {
        initialFileName: {
            type: String,
            required: true,
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
        let newFileName = this.initialFileName;
        let extension = '';
        let nbParts = 1;
        if(!this.isDirectory) {
            let nameSplit = newFileName.split('.');
            nbParts = nameSplit.length;
            if (nameSplit.length > 1) {
                extension = nameSplit.pop();
            }
        }
        
        return {
            newFileName,
            extension,
            nbParts
        };
    },
    methods: {
        save() {
            if(this.newFileName !== ''){
                // Séparer le nom de fichier sans l'extension
                const fileNameWithoutExtension = this.newFileName.slice(0, this.newFileName.lastIndexOf('.'));
                const fileNameWithoutPoints = fileNameWithoutExtension.replace(/\./g, "");
                if (fileNameWithoutPoints !== '') {
                    // Re-construire le nom du fichier avec l'extension d'origine
                    const newFileNameWithOriginalExtension = fileNameWithoutExtension + '.' + this.extension;

                    if (!this.isDirectory && this.newFileName !== newFileNameWithOriginalExtension) {
                        // L'extension a été modifiée, on rétablit l'extension correcte
                        this.newFileName = newFileNameWithOriginalExtension;
                    }

                    this.$emit("update", { initialFileName: this.initialFileName, newFileName: this.newFileName });
                    this.closeModal();
                }
            }
        },
        closeModal() {
            this.$emit("close");
        },
        onInputChange() {
            if (!this.isDirectory) {
                this.newFileName = this.removeExtensionSurplus(this.newFileName);
                let lastIndex = this.newFileName.lastIndexOf('.');
                let fileNameWithoutExtension;
                if(lastIndex != -1) {
                    fileNameWithoutExtension = this.newFileName.slice(0, lastIndex);
                }
                else {
                    fileNameWithoutExtension = this.newFileName.slice(0);
                }

                const newFileNameWithOriginalExtension = fileNameWithoutExtension + '.' + this.extension;

                // Si l'extension est différente de celle d'origine, on la rétablit
                if (this.extension !== '' && this.newFileName !== newFileNameWithOriginalExtension) {
                    // Vous pouvez ici vérifier si l'extension a été modifiée et la rétablir
                    this.newFileName = newFileNameWithOriginalExtension;
                }
            }
        },
        removeExtensionSurplus(name){
            let splitName = name.split('.');

            if(this.nbParts != splitName.length) {
                let lenExtension = this.extension.length;
                return name.slice(0, name.length - lenExtension);
            }
            else{
                return name;
            }
        }
    },
};
</script>

<style scoped>

</style>
