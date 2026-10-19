<<<<<<< HEAD
# Application Webtransfer

## Compilation du JavaScript

Une fois l'application clonée depuis le dépôt Git, suivez les étapes ci-dessous pour construire les fichiers nécessaires :

1. Assurez-vous que toutes les dépendances sont installées en exécutant la commande suivante à la racine du projet :
   ```bash
   npm install
   ```

2. Exécutez le script `buildAll` défini dans le fichier `package.json` avec la commande suivante :
   ```bash
   npm run buildAll
   ```

### Remarque importante
Lorsque l'application sera publiée sur l'App Store de Nextcloud, il faudra vérifier le processus de build automatique des applications. La méthode actuelle (`npm run buildAll`) pourrait ne pas être compatible avec les pipelines de build utilisés pour l'App Store.

Nous recommandons de documenter ce processus ou d'adapter le workflow en conséquence une fois les exigences de l'App Store clarifiées.

---

## Dépendances et outils nécessaires

Pour pouvoir construire et exécuter l'application, vous devez disposer des outils suivants :

- **Node.js**
- **npm** : Livré avec Node.js

Assurez-vous que ces outils sont correctement installés avant de procéder à la compilation.

---

## Scripts disponibles

Le fichier `package.json` contient plusieurs scripts utiles pour le développement et le déploiement de l'application. Voici une liste des principaux scripts et leur rôle :

- **`build`** : Compile l'application en mode production.
  ```bash
  npm run build
  ```

- **`dev`** : Compile l'application en mode développement.
  ```bash
  npm run dev
  ```

- **`watch`** : Surveille les fichiers pour des modifications et reconstruit automatiquement.
  ```bash
  npm run watch
  ```

- **`lint`** : Analyse le code source pour détecter les erreurs avec ESLint.
  ```bash
  npm run lint
  ```

- **`stylelint`** : Analyse les fichiers CSS/SCSS/Vue pour détecter des erreurs de style.
  ```bash
  npm run stylelint
  ```

- **`tailwind`** : Génère le fichier CSS principal (`output.css`) à partir des classes Tailwind.
  ```bash
  npm run tailwind
  
=======
# ArchiveLoaderNC

Classement d'archives sur NextCloud
>>>>>>> 5c56c02 (Initial commit)
