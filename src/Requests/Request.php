<?php

namespace Tuliovgomes\LaravelJadlog\Client;

use Carbon\Carbon;
use GuzzleHttp\Client;

class Request
{
    public $Client;

    public function __construct()
    {
        $this->Client = new Client();
    }

    protected const ENDPOINT_CONSULTAR_FRETE   = 'www.jadlog.com.br/embarcador/api/frete/valor';
    protected const ENDPOINT_INCLUIR_ENVIO     = 'www.jadlog.com.br/embarcador/api/pedido/incluir';
    protected const ENDPOINT_CANCELAR_REGISTRO = 'www.jadlog.com.br/embarcador/api/pedido/cancelar';
    protected const ENDPOINT_RASTREAR_REGISTRO = 'www.jadlog.com.br/embarcador/api/tracking/consultar';

    public function requestValorFrete($fields, $token)
    {
        if (config('app.env') != 'production') {
            return $this->mockResponseValorFrete();
        }

        return $this->sendRequestValorFrete($fields, $token);
    }

    public function requestIncluir($fields, $token)
    {
        if (config('app.env') != 'production') {
            return $this->mockResponseIncluir();
        }

        return $this->sendRequestIncluir($fields, $token);
    }

    public function requestCancelar($fields, $token)
    {
        if (config('app.env') != 'production') {
            return $this->mockResponseCancelar();
        }

        return $this->sendRequestCancelar($fields, $token);
    }

    public function requestRastrear($fields, $token)
    {
        if (config('app.env') != 'production') {
            return $this->mockResponseRastrear();
        }

        return $this->sendRequestRastrear($fields, $token);
    }

    public function requestDataEntrega($fields, $token)
    {
        if (config('app.env') != 'production') {
            return $this->mockResponseDataEntrega();
        }

        return $this->sendRequestDataEntrega($fields, $token);
    }

    private function mockResponseValorFrete()
    {
        return ['valor' => 10];
    }

    private function mockResponseIncluir()
    {
        return ['codigo_jadlog' => 10];
    }

    private function mockResponseCancelar()
    {
        return true;
    }

    private function mockResponseRastrear()
    {
        return [
            'rastreio' => [
               [
                    'data'      => '02/02/2021',
                    'hora'      => '00:00:00',
                    'descricao' => 'saiu',
                    'cidade'    => 'cidade teste',
                    'local'     => 'teste'
               ], [
                'data'      => '03/02/2021',
                'hora'      => '00:00:00',
                'descricao' => 'chegou',
                'cidade'    => 'cidade teste',
                'local'     => 'teste'
               ]
            ]
        ];
    }

    private function mockResponseDataEntrega()
    {
        return carbon::now()->format('Y/m/d H:i:s');
    }

    private function sendRequestValorFrete($fields, $token)
    {
        try {
            $response = $this->client->request(
                'POST',
                self::ENDPOINT_CONSULTAR_FRETE,
                [
                    'headers' => [
                        'Content-Type'  => 'application/json',
                        'Authorization' => $token,
                    ],
                    'body' => json_encode($fields)
                ]
            );

            $response = json_decode($response->getBody()->getContents())->frete[0];
            if (!isset($response->vltotal)) {
                return false;
            }

            return $response->vltotal;
        } catch (\Exception $e) {
            return false;
        }
    }


    private function sendRequestIncluir($fields, $token)
    {
        try {
            $response = $this->client->request(
                'POST',
                self::ENDPOINT_INCLUIR_ENVIO,
                [
                    'headers' => [
                        'Content-Type'  => 'application/json',
                        'Authorization' => $token,
                    ],
                    'body' => json_encode($fields)
                ]
            );

            $response = json_decode($response->getBody()->getContents());
            if (isset($response->erro->id) && $response->erro->id == '-2') {
                $codigoColeta = self::verificaDadoInserido($response->erro);
                if (!$codigoColeta) {
                    return false;
                }

                return $codigoColeta;
            }

            return $response->codigo;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    private function sendRequestCancelar($fields, $token)
    {
        try {
            $response = $this->client->request(
                'POST',
                self::ENDPOINT_CANCELAR_REGISTRO,
                [
                    'headers' => [
                        'Content-Type'  => 'application/json',
                        'Authorization' => $token,
                    ],
                    'body' => json_encode($fields)
                ]
            );

            $response = json_decode($response->getBody()->getContents());
            if (isset($response->erro) && $response->erro->id == '-2') {
                return $response->erro->descricao . PHP_EOL . $response->erro->detalhe;
            }

            return $response;
        } catch (\Exception $ex) {
            return false;
        }
    }

    private function sendRequestRastrear($fields, $token)
    {
        try {
            $response = $this->client->request(
                'POST',
                self::ENDPOINT_RASTREAR_REGISTRO,
                [
                    'headers' => [
                        'Content-Type'  => 'application/json',
                        'Authorization' => $token,
                    ],
                    'body' => json_encode($fields)
                ]
            );

            $response = json_decode($response->getBody()->getContents())->consulta[0];

            if (isset($response->tracking->eventos)) {
                $dados = [
                    'status' => true,
                    'codigo' => $response->codigo
                ];

                for ($i = 0; $i < count($response->tracking->eventos); $i++) {
                    $data = Carbon::createFromFormat('Y-m-d H:i:s', $response->tracking->eventos[$i]->data);
                    $dados['evento'][] = [
                        'data'      => $data->format('d/m/Y H:i:s'),
                        'descricao' => (string) $response->tracking->eventos[$i]->status,
                        'cidade'    => (string) $response->tracking->eventos[$i]->unidade ?? null,
                        'local'     => (string) $response->tracking->eventos[$i]->unidade ?? null
                    ];
                }
            } else {
                $dados = [
                    'status' => false
                ];
            }

            return $dados;
        } catch (\Exception $ex) {
            return [
                'status' => false
            ];
        }
    }

    private function sendRequestDataEntrega($fields, $token)
    {
        try {
            $response = $this->client->request(
                'POST',
                self::ENDPOINT_RASTREAR_REGISTRO,
                [
                    'headers' => [
                        'Content-Type'  => 'application/json',
                        'Authorization' => $token,
                    ],
                    'body' => json_encode($fields)
                ]
            );

            if (!isset($response['evento'])) {
                return false;
            }

            $evento = array_pop($response['evento']);

            if (strcasecmp(trim($evento['descricao']), 'Entregue') == 0) {
                $data = $evento['data'] . ' ' . $evento['hora'];

                return Carbon::createFromFormat('d/m/Y H:i:s', $data);
            }

            return false;
        } catch (\Exception $ex) {
            return false;
        }
    }


    private static function verificaDadoInserido($erro)
    {
        $array = explode(': ', $erro->descricao);
        if (count($array) == 4 && preg_replace('/[0-9]+/', '', $array[1]) == ' já foi enviado. Solicitacao de coleta') {
            return onlynumber($array[2]);
        }

        return false;
    }
}
