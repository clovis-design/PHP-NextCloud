<?php

declare(strict_types=1);

namespace OCA\WebTransfer\Controller;

use OCA\WebTransfer\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IResponse;
use OCP\AppFramework\Http\JSONResponse;

/**
 * @psalm-suppress UnusedClass
 */
class PageController extends Controller {
	#[NoCSRFRequired]
	#[NoAdminRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'GET', url: '/')]
	public function index(): TemplateResponse {
		return new TemplateResponse(
			Application::APP_ID,
			'index',
		);
	}

	// Route de la page d'extraction du zip
	#[NoCSRFRequired]
	#[NoAdminRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'GET', url: '/zipDrop')]
	public function zipDrop() {
		// Récupérer le paramètre url (compatible GET et POST)
		$url = $this->request->getParam('url');
		$mode = $this->request->getParam('mode');

		if (!$url) {
			return new \OCP\AppFramework\Http\DataResponse([
				'error' => 'Le paramètre url est manquant'
			], 400);
		}
		if (!$mode) {
			return new \OCP\AppFramework\Http\DataResponse([
				'error' => 'Le paramètre mode est manquant'
			], 400);
		}

		// Optionnel : Validation de l'URL
		if (filter_var($url, FILTER_VALIDATE_URL) === false) {
			return new \OCP\AppFramework\Http\DataResponse([
				'error' => 'url n\'est pas une URL valide'
			], 400);
		}

		$parameters = array('archiveUrl' => $url, 'mode' => $mode);

		// Réponse de succès
		return new TemplateResponse(
			Application::APP_ID,
			'index',
			$parameters
		);
	}

	// Route pour download le fichier
	#[NoCSRFRequired]
	#[NoAdminRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'GET', url: '/getFile')]
	public function getFile() {
		// Récupérer les données envoyées dans la requête
		$zipUrl = $this->request->getParam('url');

		// Initialiser les paramètres de réponse
		$parameters = [
			'status' => 'error', // Par défaut, la réponse indique une erreur
			'message' => '',
			'data' => null
		];

		// Valider l'URL
		if (!$zipUrl || filter_var($zipUrl, FILTER_VALIDATE_URL) === false) {
			$parameters['message'] = 'Invalid URL';
			return new JsonResponse($parameters, 400); // 400 Bad Request
		}

		try {
			// Initialiser cURL
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $zipUrl);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Timeout de 10 secondes
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Suivre les redirections

			// Récupérer le contenu
			$response = curl_exec($ch);

			// Gérer les erreurs cURL
			if (curl_errno($ch)) {
				$parameters['message'] = 'cURL error: ' . curl_error($ch);
				curl_close($ch);
				return new JsonResponse(['parameters' => $parameters, 'status' => 500]); // 500 Internal Server Error
			}

			// Vérifier le code HTTP
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			if ($httpCode !== 200) {
				$parameters['message'] = "HTTP error: $httpCode";
				return new JsonResponse(['parameters' => $parameters, 'status' => $httpCode]);
			}

			// Encodage explicite en UTF-8 si nécessaire
			if (!mb_detect_encoding($response, 'UTF-8', true)) {
				$response = utf8_encode($response);
			}

			// Si tout est OK, construire la réponse
			$parameters['status'] = 'success';
			$parameters['message'] = 'File retrieved successfully';
			$parameters['data'] = $response; // Encodage Base64 pour éviter les problèmes d'encodage JSON

			return new JsonResponse(['parameters' => $parameters, 'status' => 200]); // 200 OK
		} catch (\Exception $e) {
			$parameters['message'] = 'Exception: ' . $e->getMessage();
			return new JsonResponse(['parameters' => $parameters, 'status' => 500]); // 500 Internal Server Error
		}
	}

    // Nouvelle méthode pour récupérer les métadonnées du fichier
    #[NoCSRFRequired]
    #[NoAdminRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/getFileMetadata')]
    public function getFileMetadata(): JsonResponse {
        $zipUrl = $this->request->getParam('url');

        // Initialiser les paramètres de réponse
        $parameters = [
            'status' => 'error',
            'message' => '',
            'filename' => null,
            'mime' => null,
            'size' => null
        ];

        // Valider l'URL
        if (!$zipUrl || filter_var($zipUrl, FILTER_VALIDATE_URL) === false) {
            $parameters['message'] = 'Invalid URL';
            return new JsonResponse(['parameters' => $parameters], 400);
        }

        try {
            // Initialiser cURL pour une requête HEAD
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $zipUrl);
            curl_setopt($ch, CURLOPT_HEADER, true); // Récupérer les en-têtes
            curl_setopt($ch, CURLOPT_NOBODY, true); // Requête HEAD (pas de corps)
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            // Exécuter la requête HEAD
            $headerResponse = curl_exec($ch);

            // Gérer les erreurs cURL
            if (curl_errno($ch)) {
                $parameters['message'] = 'cURL error: ' . curl_error($ch);
                curl_close($ch);
                return new JsonResponse(['parameters' => $parameters], 500);
            }

            // Récupérer le code HTTP
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                $parameters['message'] = "HTTP error: $httpCode";
                return new JsonResponse(['parameters' => $parameters], $httpCode);
            }

            // Extraire Content-Disposition depuis les en-têtes
            $contentDisposition = '';
            if (preg_match('/Content-Disposition: (.+)/i', $headerResponse, $matches)) {
                $contentDisposition = $matches[1];
            }

            // Extraire le nom du fichier depuis Content-Disposition
            $filename = basename(parse_url($zipUrl, PHP_URL_PATH)); // Nom par défaut
            if ($contentDisposition) {
                preg_match('/filename="?([^"]+)"?/i', $contentDisposition, $filenameMatches);
                if (!empty($filenameMatches[1])) {
                    $filename = $filenameMatches[1];
                }
            }

            // Extraire d'autres métadonnées (optionnel)
            $contentType = '';
            if (preg_match('/Content-Type: (.+)/i', $headerResponse, $typeMatches)) {
                $contentType = $typeMatches[1];
            }

            $contentLength = 0;
            if (preg_match('/Content-Length: (\d+)/i', $headerResponse, $lengthMatches)) {
                $contentLength = (int)$lengthMatches[1];
            }

            // Construire la réponse de succès
            $parameters['status'] = 'success';
            $parameters['message'] = 'Metadata retrieved successfully';
            $parameters['filename'] = $filename;
            $parameters['mime'] = $contentType;
            $parameters['size'] = $contentLength;

            return new JsonResponse(['parameters' => $parameters], 200);

        } catch (\Exception $e) {
            $parameters['message'] = 'Exception: ' . $e->getMessage();
            return new JsonResponse(['parameters' => $parameters], 500);
        }
    }
}
