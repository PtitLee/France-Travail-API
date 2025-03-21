<?php

namespace App\Service;

use Exception;
use Throwable;
use App\Entity\Offer;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class FranceTravailAPIService
{

    public function __construct(private HttpClientInterface $client) {}

    /**
     * @return array<string>
     * @throws Exception
     */
    private function generateToken(): array
    {
        if (
            !isset($_ENV['FRANCE_TRAVAIL_CLIENT_ID'])
            || !isset($_ENV['FRANCE_TRAVAIL_CLIENT_SECRET'])
            || empty($_ENV['FRANCE_TRAVAIL_CLIENT_ID'])
            || empty($_ENV['FRANCE_TRAVAIL_CLIENT_SECRET'])
        ) {
            throw new Exception("France Travail API credentials are not set");
        }

        try {
            $result = $this->client->request(
                'POST',
                'https://entreprise.francetravail.fr/connexion/oauth2/access_token?realm=%2Fpartenaire',
                [
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                    'body' => [
                        'grant_type' => 'client_credentials',
                        'client_id' => $_ENV['FRANCE_TRAVAIL_CLIENT_ID'],
                        'client_secret' => $_ENV['FRANCE_TRAVAIL_CLIENT_SECRET'],
                        'scope' => 'api_offresdemploiv2 o2dsoffre'
                    ],
                ],
            );

            $responseBody = $result->toArray();

            if (
                array_key_exists('access_token', $responseBody)
                && array_key_exists('expires_in', $responseBody)
            ) {
                return $responseBody;
            }
            throw new Exception("France Travail API token is empty");
        } catch (Throwable $e) {
            throw new Exception("France Travail API token key is not present in the response");
        }
    }

    private function getToken(): string
    {
        $cache = new FilesystemAdapter();
        $token = $cache->get('france_travail_token', function (ItemInterface $item): string {

            $item->expiresAfter((int) $this->generateToken()['expires_in']);

            return $this->generateToken()['access_token'];
        });

        return $token;
    }


    /**
     * @return iterable<Offer>
     */
    public function getOffers(): iterable
    {
        $token = $this->getToken();

        $response = $this->client->request('GET', 'https://api.francetravail.io/partenaire/offresdemploi/v2/offres/search', [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        $data = $response->toArray();
        foreach ($data['resultats'] as $offerData) {
            $offer = new Offer();
            $offer->setId($offerData['id']);
            $offer->setTitle($offerData['intitule']);
            $offer->setDescription($offerData['description']);
            //TODO: validate url
            //TODO: get urlOrigine when url urlPostulation is null ?
            $offer->setApplyUrl(isset($offerData['contact']['urlPostulation']) ? $offerData['contact']['urlPostulation'] : null);
            //TODO: validate the contract type from a enum
            $offer->setContractType($offerData['typeContrat']);

            yield  $offer;
        }
    }
}
