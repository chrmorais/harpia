<?php

namespace Modulos\Seguranca\Http\Controllers;

use Harpia\Providers\ActionButton\TButton;
use Modulos\Core\Http\Controller\BaseController;
use Modulos\Seguranca\Repositories\ModuloRepository;
use Modulos\Seguranca\Http\Requests\ModuloRequest;
use Illuminate\Http\Request;

class ModulosController extends BaseController
{
    protected $moduloRepository;

    public function __construct(ModuloRepository $modulo)
    {
        $this->moduloRepository = $modulo;
    }

    public function getIndex(Request $request)
    {
        $btnNovo = new TButton();
        $btnNovo->setName('Novo')->setAction('/seguranca/modulos/create')->setIcon('fa fa-plus')->setStyle('btn btn-app bg-olive');

        $actionButtons[] = $btnNovo;

        $tableData = $this->moduloRepository->paginateRequest($request->all());

        return view('Seguranca::modulos.index', ['tableData' => $tableData, 'actionButton' => $actionButtons]);
    }

    public function getCreate()
    {
        return view('Seguranca::modulos.create');
    }

    public function postCreate(ModuloRequest $request)
    {
        try {
            $modulo = $this->moduloRepository->create($request->all());

            if (!$modulo) {
                 flash()->error('Erro ao tentar salvar.');

                 return redirect()->back()->withInput($request->all());
             }

             flash()->success('Módulo criado com sucesso.');

             return redirect('/seguranca/modulos');
        } catch (\Exception $e) {
            if (config('app.debug')) {
                throw $e;
            } else {
                flash()->success('Erro ao tentar salvar. Caso o problema persista, entre em contato com o suporte.');

                return redirect()->back();
            }
        }
    }

    public function getEdit($moduloId)
    {
        $modulo = $this->moduloRepository->find($moduloId);

        if (!$modulo) {
            flash()->error('Módulo não existe.');

            return redirect()->back();
        }

        return view('Seguranca::modulos.edit', compact('modulo'));
    }

    public function putEdit($id, ModuloRequest $request)
    {
        try {
            $modulo = $this->moduloRepository->find($id);

            if (!$modulo) {
                flash()->error('Módulo não existe.');

                return redirect('/seguranca/modulos');
            }

            $requestData = $request->only($this->moduloRepository->getFillableModelFields());

            if (!$this->moduloRepository->update($requestData, $modulo->mod_id, 'mod_id')) {
                 flash()->error('Erro ao tentar salvar.');

                return redirect()->back()->withInput($request->all());
             }

             flash()->success('Módulo atualizado com sucesso.');

             return redirect('/seguranca/modulos');
        } catch (\Exception $e) {
            if (config('app.debug')) {
                throw $e;
            } else {
                flash()->success('Erro ao tentar salvar. Caso o problema persista, entre em contato com o suporte.');

                return redirect()->back();
            }
        }
    }

     public function postDelete(Request $request)
     {
         try {
             $moduloId = $request->get('id');

             if($this->moduloRepository->delete($moduloId)) {
                 flash()->success('Módulo excluído com sucesso.');
             } else {
                 flash()->error('Erro ao tentar excluir o módulo');
             }

             return redirect()->back();
         } catch (\Exception $e) {
             if (config('app.debug')) {
                 throw $e;
             } else {
                 flash()->success('Erro ao tentar salvar. Caso o problema persista, entre em contato com o suporte.');

                 return redirect()->back();
             }
         }
     }
}
