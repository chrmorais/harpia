<?php

namespace Modulos\Geral\Repositories;

use Modulos\Core\Repository\BaseRepository;
use Modulos\Geral\Models\Documento;

class DocumentoRepository extends BaseRepository
{
    public function __construct(Documento $documento)
    {
        $this->model = $documento;
    }

    public function getCpfByPessoa($pessoaId)
    {
        return $this->model
                    ->join('gra_tipos_documentos', 'doc_tpd_id', 'tpd_id')
                    ->where('doc_pes_id', '=', $pessoaId)
                    ->where('tpd_nome', 'CPF')
                    ->get();
    }

    public function verifyCpf($cpf, $idPessoa = null)
    {
        $result = $this->model->where('doc_conteudo', $cpf)->where('doc_tpd_id', 2)->get();

        if (!$result->isEmpty()) {
            if (!is_null($idPessoa)) {
                $result = $result->where('doc_pes_id', $idPessoa);

                if (!$result->isEmpty()) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    public function updateDocumento(array $data, array $options)
    {
        $query = $this->model;

        foreach ($options as $key => $value) {
            $query = $query->where($key, '=', $value);
        }

        return $query->update($data);
    }

    public function updateOrCreate(array $attributes, array $data)
    {
        return $this->model->updateOrCreate($attributes, $data);
    }
}
