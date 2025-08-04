<?php

namespace Botble\Newsletter\Drivers;

use Botble\Newsletter\Contracts\Provider;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class Mailjet extends AbstractProvider implements Provider
{
    /**
     * Base URL de la API de Mailjet.
     */
    protected string $apiUrl = 'https://api.mailjet.com/v3/REST';

    /**
     * Ejecuta la petición usando Basic Auth,
     * tomando credenciales desde los settings.
     */
    protected function request(string $method, string $uri, array $data = []): Response
    {
        $apiKey    = setting('newsletter_mailjet_api_key');
        $apiSecret = setting('newsletter_mailjet_api_secret');

        if (empty($apiKey) || empty($apiSecret)) {
            throw new \RuntimeException('Mailjet API Key o Secret no configurados. Revisa los ajustes del boletín.');
        }

        return Http::withBasicAuth($apiKey, $apiSecret)
            ->{$method}("{$this->apiUrl}{$uri}", $data);
    }

    /**
     * Devuelve todas las listas de contactos.
     */
    public function contacts(): array
    {
        $response = $this->request('GET', '/contactslist');
        return Arr::get($response->json(), 'Data', []);
    }

    /**
     * Suscribe o actualiza un contacto y lo añade a la lista.
     */
    public function subscribe(string $email, array $mergeFields = []): array
    {
        // 1) Crear o actualizar el contacto
        $body = array_merge(['Email' => $email], $mergeFields);
        $this->request('POST', '/contact', $body);

        // 2) Añadirlo a la lista (add no force)
        $listId   = setting('newsletter_mailjet_list_id');
        $response = $this->request('POST', "/contactslist/{$listId}/managecontact", [
            'Email'  => $email,
            'Action' => 'addnoforce',
        ]);

        return $response->json();
    }

    /**
     * Da de baja un contacto de la lista.
     */
    public function unsubscribe(string $email): array
    {
        $listId   = setting('newsletter_mailjet_list_id');
        $response = $this->request('POST', "/contactslist/{$listId}/managecontact", [
            'Email'  => $email,
            'Action' => 'unsub',
        ]);

        return $response->json();
    }
}
