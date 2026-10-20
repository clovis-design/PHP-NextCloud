/* eslint-disable indent */
/* eslint-disable semi */
function closeModal() {
  document.getElementById('fileNameModal').hidden = true;
}

document.getElementById('cancelButton').addEventListener('click', closeModal);
document.getElementById('fileNameModal').addEventListener('click', closeModal);

function removeExtensionSurplus(name) {
  const splitName = name.split('.');

  if (this.nbParts !== splitName.length) {
    const lenExtension = this.extension.length;
    return name.slice(0, name.length - lenExtension);
  }

  return name;
}

function onInputChange() {
  if (!isDirectory) {
    let newFileName = input.value;
    newFileName = removeExtensionSurplus(newFileName);
    const lastIndex = newFileName.lastIndexOf('.');
    let fileNameWithoutExtension;
    if (lastIndex !== -1) {
      fileNameWithoutExtension = newFileName.slice(0, lastIndex);
    } else {
      fileNameWithoutExtension = newFileName.slice(0);
    }

    const newFileNameWithOriginalExtension = fileNameWithoutExtension + '.' + extension;

    // Si l'extension est différente de celle d'origine, on la rétablit
    if (extension !== '' && newFileName !== newFileNameWithOriginalExtension) {
      // Vous pouvez ici vérifier si l'extension a été modifiée et la rétablir
      newFileName = newFileNameWithOriginalExtension;
    }
    input.value = newFileName;
  }
}

const input = document.getElementById('newFileName');
const isDirectory = input.dataset.isDirectory === 'true';
const extension = input.dataset.extension;

input.addEventListener('input', onInputChange);
