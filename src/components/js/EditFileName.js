/* eslint-disable indent */
/* eslint-disable semi */

const input = document.getElementById('newFileName');
const isDirectory = input.dataset.isDirectory === 'true';
const nbParts = Number(input.dataset.nbParts);
const extension = input.dataset.extension;
const saveButton = document.getElementById('saveButton');

function closeModal() {
  const modal = document.getElementById('fileNameModal');
  modal.style.display = 'none';
}

function removeExtensionSurplus(name) {
  const splitName = name.split('.');
  if (nbParts !== splitName.length) {
    const lenExtension = extension.length;
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

function save() {
  let newFileName = input.value;

  if (newFileName.trim() === '') {
    return;
  }
  if (!isDirectory) {
    const lastIndex = newFileName.lastIndexOf('.');

    const fileNameWithoutExtension =
      lastIndex !== -1 ? newFileName.slice(0, lastIndex) : newFileName;

    const fileNameWithoutPoints = fileNameWithoutExtension.replaceAll('.', '');

    if (fileNameWithoutPoints.trim() === '') {
      return;
    }

    if (extension !== '') {
      newFileName = `${fileNameWithoutExtension}.${extension}`;
    }
  } else {
    if (newFileName.replaceAll('.', '').trim() === '') {
      return;
    }
  }

  input.value = newFileName;

  document.dispatchEvent(
    new CustomEvent('filename:update', {
      detail: {
        initialFileName: input.dataset.initialFileName,
        newFileName: newFileName,
      },
    }),
  );

  closeModal();

  console.log('Nouveau nom :', newFileName);
}

document.getElementById('cancelButton').addEventListener('click', closeModal);
document.getElementById('fileNameModal').addEventListener('click', (event) => {
  if (event.target === event.currentTarget) {
    closeModal();
  }
});
input.addEventListener('input', onInputChange);
saveButton.addEventListener('click', save);
input.addEventListener('keyup', (event) => {
  if (event.key === 'Enter') {
    save();
  }
});
