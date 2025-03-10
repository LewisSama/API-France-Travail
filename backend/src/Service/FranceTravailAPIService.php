<?php
namespace App\Service;

use App\Entity\Offer;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

class FranceTravailAPIService {

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    private function generateToken(): string {

        $client = new Client();
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];
        $url = 'https://entreprise.francetravail.fr/connexion/oauth2/access_token';
        $query = http_build_query([
            'realm' => '/partenaire',
            'grant_type' => 'client_credentials',
            'client_id' => $_ENV['FRANCE_TRAVAIL_CLIENT_ID'],
            'client_secret' => $_ENV['FRANCE_TRAVAIL_CLIENT_SECRET'],
            'scope' => 'api_offresdemploiv2 o2dsoffre'
        ]);
        $request = new Request('POST', $url . '?' . $query, $headers);
        $res = $client->sendAsync($request)->wait();

        $responseBody = json_decode($res->getBody(), true);

        if(array_key_exists('access_token', $responseBody)) {
            if(!empty($responseBody['access_token'])) {
                return $responseBody['access_token'];
            }
            throw new Exception("FRANCE TRAVAIL API - An error occurred while generating the token: France Travail API token is empty");
        }

        throw new Exception("FRANCE TRAVAIL API - An error occurred while generating the token: France Travail API token key is not present in the response");
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getToken():string {

        $cache = new FilesystemAdapter();
        $token = $cache->get('france_travail_token', function (ItemInterface $item): string {

            ##HARDCODED VALUE: TODO: use expires_in from the response
            $item->expiresAfter(1499);

            return $this->generateToken();
        });

        return $token;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function retrieveOffersResponseByRange(int $min, int $max): mixed {
        $token = $this->getToken();

        $client = new Client();
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ];

        $body = [
            'commune' => '35238,75101,33063',
            'distance' => 0,
            'publieeDepuis' => 31, //Warning: France travail doc error ? it says integer but seems to only accept 1,3,7,14,31
            'range' => $min."-".$max
        ];

        $query = http_build_query($body);
        $request = new Request('GET', 'https://api.francetravail.io/partenaire/offresdemploi/v2/offres/search?' . $query, $headers);

        return $client->sendAsync($request)->wait();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function retrieveOffersCount(): int {
        $res = $this->retrieveOffersResponseByRange(0,1);

        $resHeaders = $res->getHeaders();
        preg_match("/\/(\d+)$/", $resHeaders['Content-Range'][0], $matches);
        $nbOffers = $matches[1];

        //OVERRIDE TEST VALUE TODO:Remove
        $nbOffers = 305;

        return $nbOffers;

    }

    /**
     * @throws InvalidArgumentException
     */
    public function retrieveAllOffersAndPersist(): void
    {
        $nbOffers = $this->retrieveOffersCount();
        $res = [];
        $min=0;
        $max=149;

        while($min<$nbOffers) {
            $response = $this->retrieveOffersResponseByRange($min, $max);
            $res = array_merge($res,  json_decode($response->getBody(),true)['resultats']);

            sleep(1);//TODO: Change this to max request nb / second

            $min = $max + 1;
            $min+149 > $nbOffers ? $max = $nbOffers : $max = $min + 148;

        }

        foreach ($res as $offerRes) {
            dump($offerRes);
            #todo: Add unique constraint on franceTravailID

            $offer = new Offer();
            $offer->setFranceTravailID($offerRes['id']);
            $offer->setTitle($offerRes['intitule']);
            $offer->setDescription($offerRes['description']);
            $offer->setURL($offerRes['id']);
            $offer->setCompagny($offerRes['entreprise']['nom']);

            $offer->setCreatedAt(\DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.u\Z', $offerRes['dateCreation']));

            $this->entityManager->persist($offer);

            //TODO: test efficacity between here and outside foreach loop
            $this->entityManager->flush();
            
            dump($offer);

            exit;
        }

        dump($res);




    }
}