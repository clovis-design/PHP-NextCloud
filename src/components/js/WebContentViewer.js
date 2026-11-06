/* eslint-disable indent */
/* eslint-disable semi */
/* WebContentViewer — navigateur seul, sans Vue. Nécessite JSZip chargé avant ce fichier. */
(() => {
  'use strict';

  class WebContentViewer {
    constructor(root) {
      this.root = root;
      this.zipUrl = root.dataset.zipUrl;
      this.mode = root.dataset.mode;
      this.zipContent = [];
      this.folderMap = Object.create(null);
      this.currentDir = '';
      this.checkedFiles = new Map();
      this.checkedAll = false;
      this.zipName = '';
      this.zipSize = 0;
      this.isLoading = false;
      this.list = root.querySelector('[data-file-list]');
      this.breadcrumbs = root.querySelector('[data-breadcrumbs]');
      this.loading = root.querySelector('[data-loading]');
      this.empty = root.querySelector('[data-empty]');
      this.error = root.querySelector('[data-error]');
      this.checkAll = root.querySelector('[data-check-all]');

      this.checkAll.addEventListener('change', () => this.handleCheckAll(this.checkAll.checked));
      this.render();
      this.loadContent();
    }

    async fetchParameters(route) {
      if (!this.zipUrl) throw new Error('URL du fichier absente');
      if (!window.OC?.generateUrl) {
        throw new Error('Nextcloud (OC.generateUrl) est requis pour charger les fichiers');
      }
      const url = new URL(
        window.OC.generateUrl(`/apps/webtransfer/${route}`),
        window.location.origin,
      );
      url.searchParams.set('url', this.zipUrl);
      const response = await fetch(url.toString(), { credentials: 'same-origin' });
      if (!response.ok) throw new Error(`${route}: HTTP ${response.status}`);
      const json = await response.json();
      if (!json?.parameters) throw new Error(`Réponse ${route} invalide`);
      return json.parameters;
    }

    // Conversion de la chaîne binaire attendue par l'ancien contrôleur en Uint8Array.
    // Si le contrôleur renvoie du base64, la conversion doit être adaptée.
    toBytes(binaryString) {
      if (typeof binaryString !== 'string') {
        throw new Error('Données binaires attendues sous forme de chaîne');
      }
      const bytes = new Uint8Array(binaryString.length);
      for (let i = 0; i < binaryString.length; i++) bytes[i] = binaryString.charCodeAt(i) & 255;
      return bytes;
    }

    async loadContent() {
      this.setLoading(true);
      try {
        const metadata = await this.fetchParameters('getFileMetadata');
        const fileName = metadata.filename || this.zipUrl.split('/').pop() || 'fichier';
        const payload = await this.fetchParameters('getFile');
        const bytes = this.toBytes(payload.data);
        this.zipSize = bytes.byteLength;
        this.zipName = fileName;

        if (this.mode === 'zip') {
          const forbidden = ['.docx', '.xlsx', '.odt', '.epub', '.pptx'];
          if (forbidden.some((ext) => fileName.toLowerCase().endsWith(ext))) {
            throw new Error('Cette archive ne doit pas être décompressée');
          }
          if (typeof window.JSZip === 'undefined') throw new Error('JSZip n’est pas chargé');
          const archive = await window.JSZip.loadAsync(bytes);
          const files = [];
          const entries = Object.values(archive.files);
          for (const entry of entries) {
            const segments = entry.name.split('/').filter(Boolean);
            let level = files;
            for (let i = 0; i < segments.length; i++) {
              const name = segments[i];
              const isDirectory = i < segments.length - 1 || entry.dir;
              let node = level.find((f) => f.name === name && f.isDirectory === isDirectory);
              if (!node) {
                node = {
                  name,
                  isDirectory,
                  size: 0,
                  content: null,
                  children: isDirectory ? [] : null,
                  unzip: null,
                };
                level.push(node);
              }
              if (isDirectory) {
                level = node.children;
              } else {
                node.size = entry._data?.uncompressedSize ?? 0;
                // Extraction différée : le contenu est disponible à l'upload/drag.
                node.unzip = entry.async('blob').then((blob) => {
                  node.content = blob;
                });
              }
            }
          }
          this.zipContent = files;
        } else if (this.mode === 'file') {
          const blob = new Blob([bytes], { type: 'application/octet-stream' });
          this.zipContent = [
            {
              name: fileName,
              isDirectory: false,
              size: bytes.byteLength,
              content: blob,
              children: null,
              unzip: Promise.resolve(),
            },
          ];
        } else {
          throw new Error(`Mode inconnu : ${this.mode}`);
        }
        this.folderMap = Object.create(null);
        this.render();
      } catch (error) {
        console.error('WebContentViewer :', error);
        this.error.textContent = error.message;
        this.error.hidden = false;
      } finally {
        this.setLoading(false);
      }
    }

    setLoading(value) {
      this.isLoading = value;
      this.loading.hidden = !value;
      if (value) {
        this.list.hidden = true;
        this.empty.hidden = true;
        this.error.hidden = true;
      } else this.render();
    }

    currentFiles() {
      let level = this.zipContent;
      if (!this.currentDir) return level;
      for (const part of this.currentDir.split('/')) {
        const folder = level.find((f) => f.isDirectory && f.name === part);
        if (!folder) return [];
        level = folder.children;
      }
      return level;
    }

    render() {
      if (this.isLoading) return;
      const files = [...this.currentFiles()].sort((a, b) => a.name.localeCompare(b.name));
      this.list.replaceChildren();
      this.breadcrumbs.replaceChildren();
      const parts = this.currentDir ? this.currentDir.split('/') : [];
      this.addBreadcrumb('Home', '');
      parts.forEach((part, i) => this.addBreadcrumb(part, parts.slice(0, i + 1).join('/')));
      this.list.hidden = files.length === 0;
      this.empty.hidden = files.length !== 0;

      for (const file of files) {
        const path = this.currentDir ? `${this.currentDir}/${file.name}` : file.name;
        const row = document.createElement('div');
        row.className =
          'flex h-16 w-full items-center gap-2 pl-4 rounded-lg border-b border-gray-300 hover:bg-NcWhite dark:hover:bg-NcGray';
        row.draggable = true;
        row.addEventListener('dragstart', (event) => this.onDragStart(file, path, event));
        row.addEventListener('dragend', (event) =>
          this.root.dispatchEvent(
            new CustomEvent('dragEnded', {
              bubbles: true,
              detail: { originalEvent: event },
            }),
          ),
        );

        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.checked = this.checkedFiles.has(path);
        checkbox.setAttribute('aria-label', `Sélectionner ${file.name}`);
        checkbox.addEventListener('click', (event) => event.stopPropagation());
        checkbox.addEventListener('change', () => this.toggleChecked(path, file, checkbox.checked));
        row.append(checkbox);

        const nameCell = document.createElement('div');
        nameCell.className = 'w-5/6 flex items-center gap-2 px-4 py-2 truncate cursor-pointer';
        const icon = document.createElement('span');
        icon.className = 'text-NcBlue text-2xl';
        icon.textContent = file.isDirectory ? '📁' : '📄';
        icon.setAttribute('aria-hidden', 'true');
        const label = document.createElement('span');
        label.className = 'truncate';
        label.textContent = file.name; // textContent empêche toute injection HTML
        nameCell.append(icon, label);
        if (file.isDirectory) nameCell.addEventListener('click', () => this.openFolder(path));
        row.append(nameCell);

        const size = document.createElement('span');
        size.className = 'w-1/6 py-2';
        size.textContent = file.isDirectory ? '-' : this.formatFileSize(file.size);
        row.append(size);
        this.list.append(row);
      }
      this.checkAll.checked =
        files.length > 0 &&
        files.every((file) =>
          this.checkedFiles.has(this.currentDir ? `${this.currentDir}/${file.name}` : file.name),
        );
    }

    addBreadcrumb(label, path) {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'text-NcBlue px-2 py-1 cursor-pointer';
      button.textContent = label;
      button.addEventListener('click', () => this.openFolder(path));
      this.breadcrumbs.append(button);
      const sep = document.createElement('span');
      sep.textContent = '/';
      this.breadcrumbs.append(sep);
    }

    openFolder(path) {
      this.currentDir = path;
      this.checkedFiles.clear();
      this.render();
    }

    toggleChecked(path, file, checked) {
      if (checked) this.checkedFiles.set(path, file);
      else this.checkedFiles.delete(path);
      this.render();
    }

    handleCheckAll(checked) {
      for (const file of this.currentFiles()) {
        const path = this.currentDir ? `${this.currentDir}/${file.name}` : file.name;
        if (checked) this.checkedFiles.set(path, file);
        else this.checkedFiles.delete(path);
      }
      this.render();
    }

    formatFileSize(size) {
      if (size < 1024) return `${size} B`;
      if (size < 1024 ** 2) return `${(size / 1024).toFixed(2)} KB`;
      if (size < 1024 ** 3) return `${(size / 1024 ** 2).toFixed(2)} MB`;
      return `${(size / 1024 ** 3).toFixed(2)} GB`;
    }

    async unpackFolder(folder) {
      const files = [];
      const visit = (node) => {
        if (node.isDirectory) node.children.forEach(visit);
        else files.push(node);
      };
      visit(folder);
      await Promise.all(files.map((f) => f.unzip).filter(Boolean));
    }

    async onDragStart(file, path, event) {
      try {
        const selected = [...this.checkedFiles.values()];
        let payload = file;
        if (selected.length) {
          payload = {
            name: file.name,
            isDirectory: true,
            isList: true,
            children: selected,
            unzip: Promise.all(selected.map((f) => f.unzip).filter(Boolean)),
          };
          await Promise.all(
            selected.map((f) => (f.isDirectory ? this.unpackFolder(f) : f.unzip)).filter(Boolean),
          );
        } else if (file.isDirectory) {
          await this.unpackFolder(file);
        } else if (file.unzip) {
          await file.unzip;
        }
        // Important : un traitement asynchrone dans dragstart peut empêcher
        // la prise en compte de dataTransfer. L'événement est conservé pour
        // l'intégration existante, mais le contrat de drag & drop est à tester.
        this.root.dispatchEvent(
          new CustomEvent('file-upload', {
            bubbles: true,
            detail: payload,
          }),
        );
      } catch (error) {
        console.error('Erreur lors du glisser-déposer :', error);
      }
    }
  }

  document
    .querySelectorAll('[data-web-content-viewer]')
    .forEach((root) => new WebContentViewer(root));
})();
