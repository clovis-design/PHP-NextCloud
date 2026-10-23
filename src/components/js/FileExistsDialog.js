/* eslint-disable semi */
/* eslint-disable indent */
const modal = document.getElementById('fileConflictModal');

const main = document.getElementById('conflictMain');
const rename = document.getElementById('conflictRename');
const overwrite = document.getElementById('conflictOverwrite');

function showPanel(panel) {
  main.hidden = panel !== 'main';
  rename.hidden = panel !== 'rename';
  overwrite.hidden = panel !== 'overwrite';

  const childModal = rename.querySelector('#fileNameModal');

  if (childModal) {
    childModal.style.display = panel === 'rename' ? 'flex' : 'none';
  }
}

document.getElementById('showRenameButton')?.addEventListener('click', () => showPanel('rename'));

document
  .getElementById('showOverwriteButton')
  .addEventListener('click', () => showPanel('overwrite'));

document.getElementById('backOverwriteButton').addEventListener('click', () => showPanel('main'));

modal.addEventListener('filename:cancel', () => {
  showPanel('main');
});

function onOverwrite() {
  const forAll = document.getElementById('overwriteForAll').checked;

  modal.dispatchEvent(
    new CustomEvent('conflict:overwrite', {
      bubbles: true,
      detail: { forAll },
    }),
  );
}

function onCancel() {
  modal.hidden = true;
  console.log('Cancel button clicked');
  modal.dispatchEvent(
    new CustomEvent('conflict:cancel', {
      bubbles: true,
    }),
  );
}

document.getElementById('confirmOverwriteButton').addEventListener('click', onOverwrite);
document.getElementById('cancelConflictButton').addEventListener('click', onCancel);
