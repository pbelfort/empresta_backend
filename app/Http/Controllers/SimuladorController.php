<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SimuladorController extends Controller
{
    private $dadosSimulador;
    private $simulacao = [];

    public function simular(Request $request)
    {
        $this->carregarArquivoDadosSimulador()
             ->simularEmprestimo($request->valor_emprestimo)
             ->filtrarInstituicao($request->instituicoes)
             ->filtrarConvenio($request->convenios)
             ->filtrarParcelas($request->parcela);

        return \response()->json($this->simulacao);
    }

    private function carregarArquivoDadosSimulador() : self
    {
        $this->dadosSimulador = json_decode(\File::get(storage_path("app/public/simulador/taxas_instituicoes.json")));
        return $this;
    }

    private function simularEmprestimo(float $valorEmprestimo) : self
    {
        foreach ($this->dadosSimulador as $dados) {
            $this->simulacao[$dados->instituicao][] = [
                "taxa"            => $dados->taxaJuros,
                "parcelas"        => $dados->parcelas,
                "valor_parcela"    => $this->calcularValorDaParcela($valorEmprestimo, $dados->coeficiente),
                "convenio"        => $dados->convenio,
            ];
        }

        //order by parcelas
        foreach ($this->simulacao as &$inst) {
        usort($inst, function ($a, $b) {
            return $a['parcelas'] <=> $b['parcelas']; 
                });
            }

        return $this;
    }

    private function calcularValorDaParcela(float $valorEmprestimo, float $coeficiente) : float
    {
        return round($valorEmprestimo * $coeficiente, 2);
    }

    private function filtrarInstituicao(array $instituicoes) : self
    {
        if (\count($instituicoes))
        {
            $arrayAux = [];
            foreach ($instituicoes AS $key => $instituicao)
            {
                if (\array_key_exists($instituicao, $this->simulacao))
                {
                     $arrayAux[$instituicao] = $this->simulacao[$instituicao];
                }
            }
            $this->simulacao = $arrayAux;
        }
        return $this;
    }

    private function filtrarConvenio(array $convenios) : self
    {
        if (\count($convenios)) 
        {
            $arrayAux = [];
            foreach ($this->simulacao as $instituicao => $simulacoes) {
                $simulacoesFiltradas = array_filter($simulacoes, function ($simulacao) use ($convenios) {
                return in_array($simulacao['convenio'], $convenios);
            });
            if (!empty($simulacoesFiltradas)) {
                $arrayAux[$instituicao] = $simulacoesFiltradas;
            }
        }
        $this->simulacao = $arrayAux;
        }
        return $this;
    }


    private function filtrarParcelas(int $parcela) : self
    {
        if($parcela > 0)
        {
            $arrayAux = [];
            foreach ($this->simulacao as $instituicao => $simulacoes) {
                $simulacoesFiltradas = array_filter($simulacoes, function ($simulacao) use ($parcela) {
                    return $simulacao['parcelas'] == $parcela;
                });
            if (!empty($simulacoesFiltradas)) {
                $arrayAux[$instituicao] = $simulacoesFiltradas;
            }
        }
        $this->simulacao = $arrayAux;
    }
        return $this;    
    }
}
