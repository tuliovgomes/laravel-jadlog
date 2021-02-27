<?php

namespace Tuliovgomes\LaravelJadlog\Builder;

use Tuliovgomes\LaravelJadlog\Requests\Request;

class LaravelJadlog
{
    public $request;

    public function __construct()
    {
        $this->request = Request::class;
    }

    private static function tiposDeEnvios()
    {
        return [
           'package'   => 3,
           'economico' => 5,
        ];
    }


    public static function gettiposDeEnvios()
    {
        return self::tiposDeEnvios();
    }

    public function consultarFrete(stdclass $data, $token)
    {
        $dados = [
            'frete' => [[
                'cepori'      => $data->cepOrigem,
                'cepdes'      => $data->cepDestinatario,
                'frap'        => 'N',
                'peso'        => $data->peso, // cubagem = (altura * comprimento * largura) / 6000
                'cnpj'        => $data->cnpj,
                'conta'       => $data->conta,
                'contrato'    => $data->contrato,
                'modalidade'  => $data->modalidadeEnvio,
                'tpentrega'   => 'D',
                'tpseguro'    => 'N',
                'vldeclarado' =>  $data->valorDeclarado,
                'vlColeta'    => $data->valorColeta
           ]]
        ];

        return $this->request->requestValorFrete($dados, $token);
    }

    public function consultarDataEntrega(stdclass $data, $token)
    {
        $dados = [
            'consulta' => [[
                'codigo' => $data->numeroJadlog
            ]]
        ];

        return $this->request->requestValorFrete($dados, $token);
    }

    public function cancelarColeta(stdclass $data, $token)
    {
        $dados = [
            'codigo' => $data->numeroJadlog
        ];

        return $this->request->sendRequestCancelar($dados, $token);
    }

    public function criarColeta(stdclass $data, $token)
    {
        if (!is_numeric($data->destinatarioNumero)) {
            if (strtolower($data->destinatarioNumero) != 'sn' && strtolower($data->destinatarioNumero) != 's/n') {
                return false;
            }

            $data->destinatarioNumero = 0;
            $data->destinatarioComplemento = mb_convert_encoding(substr('S/N ' . $data->destinatarioComplemento, 0, 19), 'UTF-8', 'auto');
        }

        $dados = [
            'conteudo'      => $data->descricao,
            'pedido'        => ["$data->identificaor"],
            'totPeso'       => $data->peso, // cubagem = (altura * comprimento * largura) / 6000
            'totValor'      => number_format($data->valor / 100, 2, '.', ''), // em centavos
            'obs'           => null,
            'modalidade'    => $data->modalidadeEnvio,
            'contaCorrente' => $data->conta,
            'tpColeta'      => 'K',
            'tipoFrete'     => 0,
            'cdUnidadeOri'  => $data->codigoUnidadeOrigem, //917
            'cdUnidadeDes'  => null,
            'cdPickupOri'   => null,
            'cdPickupDes'   => null,
            'nrContrato'    => null,
            'servico'       => 0,
            'shipmentId'    => null,
            'vlColeta'      => null,
            'rem'           => [
                'nome'     => $data->remetenteNome,
                'cnpjCpf'  => $data->remetenteCnpj,
                'ie'       => (string) $data->remetenteIe,
                'endereco' => $data->remetenteEndereco,
                'numero'   => $data->remetenteNumero,
                'compl'    => $data->remetenteComplemento,
                'bairro'   => $data->remetenteBairro,
                'cidade'   => $data->remetenteCidade,
                'uf'       => $data->rementeUf,
                'cep'      => $data->remetenteCep,
                'fone'     => $data->remetenteTelefone,
                'cel'      => $data->remetenteTelefone,
                'email'    => $data->remetenteEmail,
                'contato'  => $data->remetenteContato
            ],
            'des' => [
                'nome'     => mb_convert_encoding(substr($data->destinatarioNome, 0, 59), 'UTF-8', 'auto'),
                'cnpjCpf'  => $data->destinatarioDocumento,
                'ie'       => $data->destinatarioIe,
                'endereco' => mb_convert_encoding(substr($data->destinatarioEndereco, 0, 79), 'UTF-8', 'auto'),
                'numero'   => $data->destinatarioNumero,
                'compl'    => $data->destinatarioComplemento,
                'bairro'   => mb_convert_encoding(substr($data->destinatarioBairro, 0, 59), 'UTF-8', 'auto'),
                'cidade'   => mb_convert_encoding(substr($data->destinatarioCidade, 0, 59), 'UTF-8', 'auto'),
                'uf'       => $data->destinatarioUf,
                'cep'      => onlyNumber($data->destinatarioCep),
                'fone'     => $data->destinatarioTelefone,
                'cel'      => $data->destinatarioCelular,
                'email'    => substr($data->destinatarioEmail, 0, 99),
                'contato'  => mb_convert_encoding(substr($data->destinatarioContato, 0, 39), 'UTF-8', 'auto')
            ],
            'dfe' => [[
                'cfop'        => $data->cfop,
                'danfeCte'    => $data->notaDanfeChave,
                'nrDoc'       => $data->notaNumero,
                'serie'       => $data->NotaSerie,
                'tpDocumento' => $data->notaTipo,
                'valor'       => $data->notaValor
            ]],
            'volume' => [[
                'altura'        => number_format($data->volumeAltura, 0),
                'comprimento'   => number_format($data->volumeComprimento, 0),
                'identificador' => $data->volumeIdentificador,
                'largura'       => number_format($data->volumeLargura, 0),
                'peso'          => $data->volumePeso
            ]]
        ];

        return $this->request->requestIncluir($dados, $token);
    }
}
