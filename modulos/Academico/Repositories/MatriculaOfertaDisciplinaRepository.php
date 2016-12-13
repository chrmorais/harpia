<?php

namespace Modulos\Academico\Repositories;

use Modulos\Academico\Models\MatriculaOfertaDisciplina;
use Modulos\Core\Repository\BaseRepository;
use DB;

class MatriculaOfertaDisciplinaRepository extends BaseRepository
{
    protected $moduloDisciplinaRepository;
    protected $ofertaDisciplinaRepository;

    public function __construct(
        MatriculaOfertaDisciplina $matricula,
        ModuloDisciplinaRepository $modulo,
        OfertaDisciplinaRepository $oferta
    )
    {
        $this->model = $matricula;
        $this->moduloDisciplinaRepository = $modulo;
        $this->ofertaDisciplinaRepository = $oferta;
    }

    public function findBy(array $options) {
        $query = $this->model;

        foreach ($options as $key => $value) {
            $query = $query->where($key, '=', $value);
        }

        return $query->get();
    }

    public function getDisciplinasCursadasByAluno($alunoId, $options = null)
    {
        $query = $this->model
            ->join('acd_matriculas', function ($join) {
                $join->on('mof_mat_id', '=', 'mat_id');
            })
            ->join('acd_ofertas_disciplinas', function ($join) {
                $join->on('mof_ofd_id', '=', 'ofd_id');
            })
            ->join('acd_modulos_disciplinas', function ($join) {
                $join->on('ofd_mdc_id', '=', 'mdc_id');
            })
            ->join('acd_disciplinas', function ($join) {
                $join->on('mdc_dis_id', '=', 'dis_id');
            })
            ->join('acd_professores', function ($join) {
                $join->on('ofd_prf_id', '=', 'prf_id');
            })
            ->join('gra_pessoas', function ($join) {
                $join->on('prf_pes_id', '=', 'pes_id');
            })
            ->where('mat_alu_id', '=', $alunoId);

        if (!is_null($options)) {
            foreach ($options as $key => $value) {
                $query = $query->where($key, '=', $value);
            }
        }

        $disciplinas = $query->get();

        if ($disciplinas->count()) {
            for ($i=0;$i<$disciplinas->count();$i++) {
                $quantMatriculas = $this->model
                                        ->where('mof_ofd_id', '=', $disciplinas[$i]->ofd_id)
                                        ->where('mof_status', '=', 'cursando')
                                        ->count();
                $disciplinas[$i]->quant_matriculas = $quantMatriculas;
            }
        }

        return $disciplinas;
    }

    public function getDisciplinasOfertadasNotCursadasByAluno($alunoId, $turmaId, $periodoId)
    {
        // pega as disciplinas cursadas pelo aluno
        $disciplinasCursadas = $this->getDisciplinasCursadasByAluno($alunoId, [
            'ofd_per_id' => $periodoId,
            'ofd_trm_id' => $turmaId
        ])->pluck('mof_ofd_id')->toArray();

        // pega as disciplinas ofertadas no periodo e turma correspondentes, e verifica se o aluno
        // está matriculado ou não em cada disciplina
        $query = DB::table('acd_ofertas_disciplinas')
                    ->join('acd_modulos_disciplinas', function ($join) {
                        $join->on('ofd_mdc_id', '=', 'mdc_id');
                    })
                    ->join('acd_disciplinas', function ($join) {
                        $join->on('mdc_dis_id', '=', 'dis_id');
                    })
                    ->join('acd_professores', function ($join) {
                        $join->on('ofd_prf_id', '=', 'prf_id');
                    })
                    ->join('gra_pessoas', function ($join) {
                        $join->on('prf_pes_id', '=', 'pes_id');
                    })
                    ->select(
                        'ofd_id',
                        'dis_nome',
                        'dis_creditos',
                        'dis_carga_horaria',
                        'ofd_qtd_vagas',
                        'pes_nome'
                    )
                    ->where('ofd_per_id', '=', $periodoId)
                    ->where('ofd_trm_id', '=', $turmaId);

        if (!empty($disciplinasCursadas)) {
            $query = $query->whereNotIn('ofd_id', $disciplinasCursadas);
        } else {
            $query = $query->whereNotIn('ofd_id', [0]);
        }

        $disciplinasOfertadas =  $query->get();


        if ($disciplinasOfertadas->count()) {
            for ($i=0;$i<$disciplinasOfertadas->count();$i++) {
                $quantMatriculas = $this->model
                                        ->where('mof_ofd_id', '=', $disciplinasOfertadas[$i]->ofd_id)
                                        ->where('mof_status', '=', 'cursando')
                                        ->count();
                $disciplinasOfertadas[$i]->quant_matriculas = $quantMatriculas;
                $disciplinasOfertadas[$i]->disponivel = 1;

                if ($quantMatriculas >= $disciplinasOfertadas[$i]->ofd_qtd_vagas) {
                    $disciplinasOfertadas[$i]->disponivel = 0;
                }
            }
        }

        return $disciplinasOfertadas;
    }

    public function verifyMatriculaDisciplina($matriculaId, $ofertaId)
    {
        $query = $this->model->where('mof_ofd_id', '=', $ofertaId)
                             ->where('mof_mat_id', '=', $matriculaId)
                            ->where('mof_status', '=', 'cursando');

        return $query->first();
    }

    public function verifyQtdVagas($ofertaId)
    {
        $query = $this->model
                    ->join('acd_ofertas_disciplinas', 'mof_ofd_id', '=', 'ofd_id')
                    ->where('mof_ofd_id', '=', $ofertaId)
                    ->where('mof_status', '=', 'cursando')
                    ->get();

        if ($query->count()) {
            $vagas = $query[0]->ofd_qtd_vagas;
            $qtd = $query->count();

            if (($vagas == $qtd)) {
                return false;
            }
        }

        return true;
    }

    public function getMatriculasByOfertaDisciplina($ofertaId)
    {
        return $this->model->where('mof_ofd_id', '=', $ofertaId)->get();
    }
    
    public function verifyIfAlunoAprovadoPreRequisitos($matriculaId, $ofertaDisciplinaId) 
    {
        $ofertaDisciplina = $this->ofertaDisciplinaRepository->find($ofertaDisciplinaId);

        $preRequisitos = $this->moduloDisciplinaRepository->getDisciplinasPreRequisitos($ofertaDisciplina->ofd_mdc_id);

        if(!empty($preRequisitos)) {

            $quantAprovadas = 0;

            foreach ($preRequisitos as $req) {
                // busca a oferta de disciplina
                $oferta = $this->ofertaDisciplinaRepository->findAll(['ofd_mdc_id' => $req->mdc_id])->first();

                // busca a matricula do aluno nessa disciplina
                $matriculaOferta = $this->findBy(['mof_mat_id' => $matriculaId, 'mof_ofd_id' => $oferta->ofd_id])->first();
                //dd($matriculaOferta);

                if($matriculaOferta) {
                    if(in_array($matriculaOferta->mof_situacaomatricula, [1, 2])) {
                        $quantAprovadas++;
                    }
                }
            }

            if($quantAprovadas < count($preRequisitos)) {
                return false;
            }
        }

        return true;
    }

    public function createMatricula(array $data)
    {
        $ofertaId = $data['ofd_id'];

        // verifica se a disciplina possui vagas disponiveis
        if (!($this->verifyQtdVagas($ofertaId))) {
            return array("type" => "error", "message" => "Sem vagas disponiveis");
        }

        // verifica se já existe uma matricula ativa nessa oferta de disciplina
        $matriculaExists = $this->verifyMatriculaDisciplina($data['mat_id'], $data['ofd_id']);

        if($matriculaExists) {
            return array("type" => "error", "message" => "Aluno já matriculado nessa disciplina para esse periodo e turma");
        }

        // verifica se o aluno está aprovado nas disciplinas pre-requisitos, caso existam
        $aprovadoPreRequisitos = $this->verifyIfAlunoAprovadoPreRequisitos($data['mat_id'], $data['ofd_id']);

        if(!$aprovadoPreRequisitos) {
            return array("type" => "error", "message" => "Aluno possui pre-requisitos não satisfeitos");
        }

        $this->create([
            'mof_mat_id' => $data['mat_id'],
            'mof_ofd_id' => $data['ofd_id'],
            'mof_tipo_matricula' => 'matriculacomum',
            'mof_situacaomatricula' => null,
            'mof_status' => 'cursando'
        ]);

        return array('type' => 'success', 'message' => 'Aluno matriculado com sucesso!');
    }
}
