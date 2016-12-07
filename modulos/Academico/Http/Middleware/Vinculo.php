<?php

namespace Modulos\Academico\Http\Middleware;

use Auth;
use Closure;
use Modulos\Academico\Repositories\GrupoRepository;
use Modulos\Academico\Repositories\MatrizCurricularRepository;
use Modulos\Academico\Repositories\ModuloMatrizRepository;
use Modulos\Academico\Repositories\OfertaCursoRepository;
use Modulos\Academico\Repositories\TurmaRepository;
use Modulos\Academico\Repositories\TutorGrupoRepository;
use Modulos\Academico\Repositories\VinculoRepository;
use Modulos\Academico\Repositories\AlunoRepository;

class Vinculo
{
    private $vinculoRepository;
    private $matrizCurricularRepository;
    private $ofertaCursoRepository;
    private $turmaRepository;
    private $grupoRepository;
    private $moduloMatrizRepository;
    private $tutorGrupoRepository;
    private $alunoRepository;

    public function __construct( VinculoRepository $vinculoRepository,
                                MatrizCurricularRepository $matrizCurricularRepository,
                                OfertaCursoRepository $ofertaCursoRepository,
                                TurmaRepository $turmaRepository,
                                GrupoRepository $grupoRepository,
                                ModuloMatrizRepository $moduloMatrizRepository,
                                TutorGrupoRepository $tutorGrupoRepository,
                                AlunoRepository $alunoRepository )
    {
        $this->vinculoRepository = $vinculoRepository;
        $this->matrizCurricularRepository = $matrizCurricularRepository;
        $this->ofertaCursoRepository = $ofertaCursoRepository;
        $this->turmaRepository = $turmaRepository;
        $this->grupoRepository = $grupoRepository;
        $this->moduloMatrizRepository = $moduloMatrizRepository;
        $this->tutorGrupoRepository = $tutorGrupoRepository;
        $this->alunoRepository = $alunoRepository;
    }

    public function handle($request, Closure $next)
    {
        $rota = $this->routeName($request);

        switch ($rota) {
            case "cursos":
                return $this->handleCursos($request, $next);
                break;
            case "matrizescurriculares":
                return $this->handleMatrizesCurriculares($request, $next);
                break;
            case "ofertascursos":
                return $this->handleOfertasCursos($request, $next);
                break;
            case "turmas":
                return $this->handleTurmas($request, $next);
                break;
            case "grupos":
                return $this->handleGrupos($request, $next);
                break;
            case "tutoresgrupos":
                return $this->handleTutoresGrupos($request, $next);
                break;
            case "modulosmatrizes":
                return $this->handleModulosMatrizes($request, $next);
                break;
            case "alunos":
                return $this->handleAlunos($request, $next);
                break;
            case "matriculasofertasdisciplinas":
                return $this->handleMatriculaDisciplina($request, $next);
                break;
            default:
                flash()->error('Você não tem autorização para acessar este recurso. Contate o Administrador.');
                return redirect()->back();
        }
    }

    private function routeName($request)
    {
        $path = explode('/', $request->getPathInfo());

        if($path[2] == "async"){
            return $path[3];
        }

        return $path[2];
    }

    private function actionName($request)
    {
        $path = explode('/', $request->getPathInfo());

        if($path[2] == "async"){
            return $path[4];
        }

        return $path[3];
    }

    /**
     * Verifica e filtra os vinculos da rota cursos
     * @param $request
     * @param Closure $next
     * @return \Illuminate\Http\RedirectResponse
     */
    private function handleCursos($request, Closure $next)
    {
        $cursoId = $request->id;

        if (is_null($cursoId)) {
            return $next($request);
        }

        if ($this->vinculoRepository->userHasVinculo(Auth::user()->usr_id, $cursoId)) {
            return $next($request);
        }

        flash()->error('Você não tem autorização para acessar este recurso. Contate o Administrador.');
        return redirect()->route('academico.cursos.index');
    }

    /**
     * Verifica e filtra os vinculos da rota Matrizes Curriculares
     * @param $request
     * @param Closure $next
     * @return \Illuminate\Http\RedirectResponse
     */
    private function handleMatrizesCurriculares($request, Closure $next)
    {
        $id = $request->id;
        $action = $this->actionName($request);

        if (is_null($id)) {
            return $next($request);
        }

        if (($action == "index" || $action == "create") && $request->getMethod() == "GET") {
            if ($this->vinculoRepository->userHasVinculo(Auth::user()->usr_id, $id)) {
                return $next($request);
            }

            flash()->error('Você não tem autorização para acessar este recurso. Contate o Administrador.');
            return redirect()->route('academico.cursos.index');
        }

        $matriz = $this->matrizCurricularRepository->find($id);

        if (!$matriz) {
            flash()->error('Você não tem autorização para acessar este recurso. Contate o Administrador.');

            return redirect()->route('academico.cursos.index');
        }

        if ($this->vinculoRepository->userHasVinculo(Auth::user()->usr_id, $matriz->mtc_crs_id)) {
            return $next($request);
        }

        flash()->error('Você não tem autorização para acessar este recurso. Contate o Administrador.');
        return redirect()->route('academico.cursos.index');
    }

    /**
     * Verifica e filtra os vinculos da rota Ofertas Cursos
     * @param $request
     * @param Closure $next
     * @return \Illuminate\Http\RedirectResponse
     */
    private function handleOfertasCursos($request, Closure $next)
    {
        $id = $request->ofc_crs_id;

        if (is_null($id)) {
            return $next($request);
        }

        if ($this->vinculoRepository->userHasVinculo(Auth::user()->usr_id, $id)) {
            return $next($request);
        }

        flash()->error('Você não tem autorização para acessar este recurso. Contate o Administrador.');
        return redirect()->route('academico.ofertascursos.index');
    }

    /**
     * Verifica e filtra os vinculos da rota Turmas
     * @param $request
     * @param Closure $next
     * @return \Illuminate\Http\RedirectResponse
     */
    private function handleTurmas($request, Closure $next)
    {
        $id = $request->id;
        $action = $this->actionName($request);

        if (is_null($id)) {
            return $next($request);
        }

        if (($action == "index" || $action == "create") && $request->getMethod() == "GET") {
            $oferta = $this->ofertaCursoRepository->find($id);

            if (!$oferta) {
                flash()->error('Você não tem autorização para acessar este recurso.');

                return redirect()->route('academico.cursos.index');
            }

            if ($this->vinculoRepository->userHasVinculo(Auth::user()->usr_id, $oferta->ofc_crs_id)) {
                return $next($request);
            }

            flash()->error('Você não tem autorização para acessar este recurso. Contate o Administrador.');

            return redirect()->route('academico.cursos.index');
        }

        $curso = $this->turmaRepository->getCurso($id);

        if (!$curso) {
            flash()->error('Você não tem autorização para acessar este recurso. Contate o Administrador.');

            return redirect()->route('academico.cursos.index');
        }

        if ($this->vinculoRepository->userHasVinculo(Auth::user()->usr_id, $curso)) {
            return $next($request);
        }

        flash()->error('Você não tem autorização para acessar este recurso. Contate o Administrador.');

        return redirect()->route('academico.cursos.index');
    }

    /**
     * Verifica e filtra os vinculos da rota Grupos
     * @param $request
     * @param Closure $next
     * @return \Illuminate\Http\RedirectResponse
     */
    private function handleGrupos($request, Closure $next)
    {
        $id = $request->id;
        $action = $this->actionName($request);

        if (is_null($id)) {
            return $next($request);
        }

        if (($action == "index" || $action == "create") && $request->getMethod() == "GET") {
            $curso = $this->turmaRepository->getCurso($id);

            if (!$curso) {
                flash()->error('Você não tem autorização para acessar este recurso. Contate o Administrador.');
                return redirect()->route('academico.cursos.index');
            }

            if ($this->vinculoRepository->userHasVinculo(Auth::user()->usr_id, $curso)) {
                return $next($request);
            }

            flash()->error('Você não tem autorização para acessar este recurso. Contate o Administrador.');
            return redirect()->route('academico.cursos.index');
        }

        $grupo = $this->grupoRepository->find($id);
        $curso = $this->turmaRepository->getCurso($grupo->grp_trm_id);

        if ($this->vinculoRepository->userHasVinculo(Auth::user()->usr_id, $curso)) {
            return $next($request);
        }

        flash()->error('Você não tem autorização para acessar este recurso. Contate o Administrador.');
        return redirect()->route('academico.cursos.index');
    }


    /**
     * Verifica e filtra os vinculos da rota Turmas
     * @param $request
     * @param Closure $next
     * @return \Illuminate\Http\RedirectResponse
     */
    private function handleModulosMatrizes($request, Closure $next)
    {
        $id = $request->id;
        $action = $this->actionName($request);

        if (is_null($id)) {
            return $next($request);
        }

        if (($action == "index" || $action == "create") && $request->getMethod() == "GET") {
            $matriz = $this->matrizCurricularRepository->find($id);

            if (!$matriz) {
                flash()->error('Você não tem autorização para acessar este recurso. Contate o Administrador.');
                return redirect()->route('academico.cursos.index');
            }

            if ($this->vinculoRepository->userHasVinculo(Auth::user()->usr_id, $matriz->mtc_crs_id)) {
                return $next($request);
            }

            flash()->error('Você não tem autorização para acessar este recurso. Contate o Administrador.');
            return redirect()->route('academico.cursos.index');
        }

        $modulo = $this->moduloMatrizRepository->find($id);
        $matriz = $this->matrizCurricularRepository->find($modulo->mdo_mtc_id);

        if ($this->vinculoRepository->userHasVinculo(Auth::user()->usr_id, $matriz->mtc_crs_id)) {
            return $next($request);
        }

        flash()->error('Você não tem autorização para acessar este recurso. Contate o Administrador.');
        return redirect()->route('academico.cursos.index');
    }

    /**
     * Verifica e filtra os vinculos da rota Tutores Grupos
     * @param $request
     * @param Closure $next
     * @return mixed
     */
    private function handleTutoresGrupos($request, Closure $next)
    {
        $id = $request->id;
        $action = $this->actionName($request);

        if (is_null($id)) {
            return $next($request);
        }

        if (($action == "index" || $action == "create") && $request->getMethod() == "GET") {
            $grupo = $this->grupoRepository->find($id);

            if (!$grupo) {
                flash()->error('Você não tem autorização para acessar este recurso. Contate o Administrador.');
                return redirect()->route('academico.cursos.index');
            }

            $curso = $this->turmaRepository->getCurso($grupo->grp_trm_id);

            if (!$curso) {
                flash()->error('Você não tem autorização para acessar este recurso. Contate o Administrador.');
                return redirect()->route('academico.cursos.index');
            }


            if ($this->vinculoRepository->userHasVinculo(Auth::user()->usr_id, $curso)) {
                return $next($request);
            }

            flash()->error('Você não tem autorização para acessar este recurso. Contate o Administrador.');
            return redirect()->route('academico.cursos.index');
        }

        $tutorGrupo = $this->tutorGrupoRepository->find($id);
        $grupo = $this->grupoRepository->find($tutorGrupo->ttg_grp_id);
        $curso = $this->turmaRepository->getCurso($grupo->grp_trm_id);

        if ($this->vinculoRepository->userHasVinculo(Auth::user()->usr_id, $curso)) {
            return $next($request);
        }

        flash()->error('Você não tem autorização para acessar este recurso. Contate o Administrador.');
        return redirect()->route('academico.cursos.index');
    }

    private function handleAlunos($request, Closure $next)
    {
        $id = $request->id;
        $action = $this->actionName($request);

        if (is_null($id) || ($action == "create")) {
            return $next($request);
        }

        if(($action == "edit") || ($action == "show")){
            /* TODO:
             AQUI:
               1 - Obter cursos nos quais o aluno esta matriculado
               2 - Checar se o usuario atual tem vinculo com qualquer dos cursos do Aluno

            AlunoRepository:
               1 - Implementar getCursos para retornar os cursos nos quais o Aluno esta matriculado
               2 - Reescrever Paginate: mostrar somente alunos sem matriculas ou matriculado em algum curso com vinculo ao usuario atual
            */
        }

        flash()->error('Você não tem autorização para acessar este recurso. Contate o Administrador.');
        return redirect()->route('academico.alunos.index');
    }

    private function handleMatriculaDisciplina($request, Closure $next)
    {
        return $next($request);
    }
}
