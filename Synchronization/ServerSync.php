<?php
/*
 * This file is part of the Manuel Aguirre Project.
 *
 * (c) Manuel Aguirre <programador.manuel@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ManuelAguirre\Bundle\TranslationBundle\Synchronization;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Message\Response;
use ManuelAguirre\Bundle\TranslationBundle\Entity\Translation;
use Symfony\Component\HttpFoundation\Response as SfResponse;

/**
 */
class ServerSync
{
    /**
     * @var Client
     */
    protected $guzzle;
    protected $apiKey;
    protected $config;

    function __construct($guzzle, $apiKey)
    {
        $this->guzzle = $guzzle;
        $this->apiKey = $apiKey;

        $this->config = array('headers' => array('Api-Key' => $apiKey));
    }

    public function add(Translation $translation)
    {
        return $this->update($translation);
    }

    public function update(Translation $translation, $force = false)
    {
        $postData = array(
            'force' => $force,
            'domain' => $translation->getDomain(),
            'code' => $translation->getCode(),
            'version' => $translation->getVersion(),
            'new' => $translation->getNew(),
            'active' => $translation->getActive(),
            'autogenerated' => $translation->getAutogenerated(),
            'files' => $translation->getFiles(),
            'values' => array(),
        );

        foreach ($translation->getValues() as $locale => $value) {
            $postData['values'][$locale] = $value->getValue();
        }

        try {
            $response = $this->guzzle->post('save', array(
                    'body' => $postData,
                ) + $this->config);

            $translation->setVersion((string) $response->getBody());
            $translation->setLocalEditions(0);
            $translation->setIsChanged(false);
            $translation->setServerEditions(0);
            $translation->setConflicts(false);
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() == SfResponse::HTTP_BAD_REQUEST
            ) {
                //$translation->setConflicts(true);
            }
        }
    }

    public function find($code, $domain)
    {
        $response = $this->guzzle->get('find', array(
                'query' => array(
                    'code' => $code,
                    'domain' => $domain,
                )
            ) + $this->config);

        return $response->json();
    }

    public function findAll()
    {
        $response = $this->guzzle->get('get-all', $this->config);

        return $response->json();
    }

    public function findAllChanged()
    {
        $response = $this->guzzle->get('get-all-changed', $this->config);

        return $response->json();
    }


    public function markUpdated($code, $domain)
    {
        $response = $this->guzzle->post('mark-updated', array(
                'body' => array(
                    'code' => $code,
                    'domain' => $domain,
                ),
            ) + $this->config);

        return (string) $response->getBody();
    }

    public function generateBackup()
    {
        $response = $this->guzzle->post('generate-backup', $this->config);

        return (string) $response->getBody();
    }
}