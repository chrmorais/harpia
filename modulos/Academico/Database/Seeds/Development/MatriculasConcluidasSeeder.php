<?php

namespace Modulos\Academico\Database\Seeds\Development;

use Illuminate\Database\Seeder;
use Modulos\Academico\Models\LancamentoTcc;
use Modulos\Academico\Models\MatriculaOfertaDisciplina;
use DB;
use Modulos\Academico\Models\Turma;

class MatriculasConcluidasSeeder extends Seeder
{
    public function run()
    {
        // Concluir 10 matriculas em cada turma

        $turmas = Turma::all();

        foreach ($turmas as $turma) {
            $matriculas = $turma->matriculas()->where('mat_situacao', 'cursando')->inRandomOrder()->take(10)->get();

            foreach ($matriculas as $matricula) {
                $disciplinasCursadas = MatriculaOfertaDisciplina::where('mof_mat_id', $matricula->mat_id)->get();

                if ($disciplinasCursadas->count()) {
                    foreach ($disciplinasCursadas as $disciplina) {
                        $disciplina->mof_nota1 = 7;
                        $disciplina->mof_nota2 = 7;
                        $disciplina->mof_nota3 = 7;
                        $disciplina->mof_mediafinal = 7;
                        $disciplina->mof_situacao_matricula = 'aprovado_media';

                        $disciplina->save();
                    }

                    $tcc = DB::table('acd_matriculas_ofertas_disciplinas')
                        ->join('acd_ofertas_disciplinas', function ($join) {
                            $join->on('mof_ofd_id', '=', 'ofd_id');
                        })
                        ->join('acd_modulos_disciplinas', function ($join) {
                            $join->on('ofd_mdc_id', '=', 'mdc_id');
                        })
                        ->where('mdc_tipo_disciplina', '=', 'tcc')
                        ->where('mof_id', '=', $disciplina->mof_id)
                        ->first();

                    if (!is_null($tcc)) {
                        $lancamentoTcc = new LancamentoTcc();
                        $lancamentoTcc->ltc_mof_id = $disciplina->mof_id;
                        $lancamentoTcc->ltc_prf_id = DB::table('acd_professores')->inRandomOrder()->first()->prf_id;
                        $lancamentoTcc->ltc_titulo = 'Monografia';
                        $lancamentoTcc->ltc_tipo = 'monografia';
                        $lancamentoTcc->ltc_data_apresentacao = date('d/m/Y');
                        $lancamentoTcc->save();

                        $matricula->mat_situacao = 'concluido';
                        $matricula->mat_data_conclusao = date('d/m/Y');
                        $matricula->save();
                    }
                }
            }
        }
    }
}
