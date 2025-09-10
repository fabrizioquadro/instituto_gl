<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Entrada;
use App\Models\Medicamento;
use App\Models\Fornecedor;
use App\Models\Estoque;
use App\Models\CodigoBarraMedicamento;

class EntradaSistemaController extends Controller
{
    public function index(){
        $user = auth()->user();
        if(!$user){
            $user = session()->get('user');
        }
        $entradas = Entrada::where('clinica_id', $user->clinica_id)->get();
        return view('sistema/entradas/index', compact('entradas'));
    }

    public function adicionar(){
        $fornecedores = Fornecedor::all()->sortBy('nome');
        $medicamentos = Medicamento::all()->sortBy('nome');
        return view('sistema/entradas/adicionar', compact('fornecedores','medicamentos'));
    }

    public function insert(Request $request){
        try {
            $user = auth()->user();
            if(!$user){
                $user = session()->get('user');
            }
            $dados = [
                'clinica_id' => $user->clinica_id,
                'fornecedor_id' => $request->fornecedor_id,
                'nota' => $request->nota,
                'data' => $request->data,
                'valor' => $request->total_entrada ? valorFormDb($request->total_entrada) : '0.00',
            ];
            $entrada = Entrada::create($dados);

            if($request->hasFile('arquivo') && $request->file('arquivo')->isValid()){
                $arquivo = $request->arquivo;
                $extensao = $arquivo->extension();

                $nm_arquivo = $entrada->id.".".$extensao;
                $request->arquivo->move(public_path('img/entradas/notas'), $nm_arquivo);

                $entrada->arquivo = $nm_arquivo;
                $entrada->save();
            }

            //vamos adicionar os medicamentos
            for($i=1 ; $i<=$request->contador_medicamentos ; $i++){
                $var = "medicamento_id_".$i;
                $medicamento_id = $request->$var;
                if($medicamento_id){
                    $var = "quantidade_".$i;
                    $quantidade = $request->$var;

                    $var = "valor_".$i;
                    $valor = $request->$var ? $request->$var : '0,00';

                    $var = "total_".$i;
                    $total = $request->$var ? $request->$var : '0,00';

                    $var = "lote_".$i;
                    $lote = $request->$var;

                    $var = "dt_vencimento_".$i;
                    $dt_vencimento = $request->$var;

                    $var = "codigo_barras_".$i;
                    $codigo_barras = $request->$var;

                    $dados = [
                        'clinica_id' => $entrada->clinica_id,
                        'entrada_id' => $entrada->id,
                        'medicamento_id' => $medicamento_id,
                        'origem' => 'Entrada',
                        'tipo' => 'Entrada',
                        'quantidade' => $quantidade,
                        'valor' => valorFormDb($valor),
                        'total' => valorFormDb($total),
                        'lote' => strtoupper($lote),
                        'dt_vencimento' => $dt_vencimento,
                        'codigo_barras' => $codigo_barras,
                    ];
                    $estoque = Estoque::create($dados);
                    //vamos atualizar o valor do medicamento na sua tabela
                    if($estoque->valor > 0){
                        $medicamento = Medicamento::where('id', $medicamento_id)->first();
                        $medicamento->ultimo_valor_pg = valorFormDb($valor);
                        $medicamento->save();
                    }
                }
            }

            return redirect()->route('sistema.entradas')->with('mensagem', 'Entrada Cadastrada');
        } catch (\Exception $e) {
            return redirect()->route('sistema.entradas')->with('mensagem_erro', $e->getMessage());
        }
    }

    public function editar($id){
        $entrada = Entrada::where('id', $id)->first();
        $fornecedores = Fornecedor::all()->sortBy('nome');
        $medicamentos = Medicamento::all()->sortBy('nome');
        $i = 0;
        return view('sistema/entradas/editar', compact('entrada',
        'fornecedores','medicamentos','i'));
    }

    public function update(Request $request){
        try {
            $dados = [
                'fornecedor_id' => $request->fornecedor_id,
                'nota' => $request->nota,
                'data' => $request->data,
                'valor' => valorFormDb($request->total_entrada),
            ];
            Entrada::where('id', $request->entrada_id)->update($dados);
            $entrada = Entrada::where('id', $request->entrada_id)->first();

            if($request->hasFile('arquivo') && $request->file('arquivo')->isValid()){
                $arquivo = $request->arquivo;
                $extensao = $arquivo->extension();

                $nm_arquivo = $entrada->id.".".$extensao;
                $request->arquivo->move(public_path('img/entradas/notas'), $nm_arquivo);

                $entrada->arquivo = $nm_arquivo;
                $entrada->save();
            }

            //vamos adicionar os medicamentos mas antes vamos deletar os que estão lá
            Estoque::where('entrada_id', $entrada->id)->delete();
            for($i=1 ; $i<=$request->contador_medicamentos ; $i++){
                $var = "medicamento_id_".$i;
                $medicamento_id = $request->$var;
                if($medicamento_id){
                    $var = "quantidade_".$i;
                    $quantidade = $request->$var;

                    $var = "valor_".$i;
                    $valor = $request->$var;

                    $var = "total_".$i;
                    $total = $request->$var;

                    $var = "lote_".$i;
                    $lote = $request->$var;

                    $var = "dt_vencimento_".$i;
                    $dt_vencimento = $request->$var;

                    $var = "codigo_barras_".$i;
                    $codigo_barras = $request->$var;

                    $dados = [
                        'clinica_id' => $entrada->clinica_id,
                        'entrada_id' => $entrada->id,
                        'medicamento_id' => $medicamento_id,
                        'origem' => 'Entrada',
                        'tipo' => 'Entrada',
                        'quantidade' => $quantidade,
                        'valor' => valorFormDb($valor),
                        'total' => valorFormDb($total),
                        'lote' => strtoupper($lote),
                        'dt_vencimento' => $dt_vencimento,
                        'codigo_barras' => $codigo_barras,
                    ];
                    Estoque::create($dados);
                    //vamos atualizar o valor do medicamento na sua tabela
                    $medicamento = Medicamento::where('id', $medicamento_id)->first();
                    $medicamento->ultimo_valor_pg = valorFormDb($valor);
                    $medicamento->save();
                }
            }

            return redirect()->route('sistema.entradas')->with('mensagem', 'Entrada Editada');
        } catch (\Exception $e) {
            return redirect()->route('sistema.entradas')->with('mensagem_erro', $e->getMessage());
        }

    }

    public function excluir($id){
        $entrada = Entrada::where('id', $id)->first();
        return view('sistema/entradas/excluir', compact('entrada'));
    }

    public function delete(Request $request){
        try {
            $entrada = Entrada::where('id', $request->entrada_id)->first();
            Estoque::where('entrada_id', $entrada->id)->delete();
            $entrada->delete();

            return redirect()->route('sistema.entradas')->with('mensagem', 'Entrada Excluída');
        } catch (\Exception $e) {
            return redirect()->route('sistema.entradas')->with('mensagem_erro', $e->getMessage());
        }
    }

    public function visualizar($id){
        $entrada = Entrada::where('id', $id)->first();
        return view('sistema/entradas/visualizar', compact('entrada'));
    }

    public function gerar_codigo_barras(){
        $controle = CodigoBarraMedicamento::where('medicamento_id', $_GET['medicamento_id'])->first();
        if(!$controle){
            $codigo = 1;
            $dados = [
                'medicamento_id' => $_GET['medicamento_id'],
                'contador' => $codigo,
            ];
            CodigoBarraMedicamento::create($dados);
        }
        else{
            $codigo = $controle->contador + 1;
            $controle->contador = $codigo;
            $controle->save();
        }

        $parte_med = (string)$_GET['medicamento_id'];
        while(strlen($parte_med) < 2){
            $parte_med = '0'.$parte_med;
        }

        $parte_cod = (string)$codigo;
        while(strlen($parte_cod) < 5){
            $parte_cod = '0'.$parte_cod;
        }

        $retorno['codigo'] = $parte_med.$parte_cod;
        echo json_encode($retorno);
    }

    public function etiquetas_imprimir($id){
        $array_ids = json_decode($id);

        $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
        //echo $generator->getBarcode('081231723897', $generator::TYPE_CODE_128, 1, 20);

        $html = '
        <!DOCTYPE html>
        <html lang="pt-br">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Impressão de Etiquetas</title>
                <style>
                @page{
                    size: 100mm 15mm
                }
                *{
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                .linha{
                    margin-left: 3mm;
                    height: 15mm;
                    display: flex;
                }
                .etiqueta{
                    width: 30mm;
                    height: 15mm;
                    margin-right: 3mm;
                    text-align: center;
                    align-items: center;
                    justify-content: center;
                    font-size: 10px;
                    background-color: #f0f0f0;
                }
                @media print {
                    .linha{
                        margin-left: 3mm;
                        height: 15mm;
                        display: flex;
                    }
                    .etiqueta{
                        width: 30mm;
                        height: 15mm;
                        margin-right: 3mm;
                        text-align: center;
                        align-items: center;
                        justify-content: center;
                        font-size: 10px;
                        background-color: #f0f0f0;
                    }
                    .linha {
                        page-break-after: always;
                    }
                }
                </style>
            </head>
            <body>
                <div class="linha">'
                ;
                    $contador = 0;
                    foreach($array_ids as $id){
                        $estoque = Estoque::where('id', $id)->first();
                        for($i=1 ; $i<=$estoque->quantidade ; $i++){
                            $contador++;
                            if($contador > 3){
                                $contador = 1;
                                $html .= '
                                </div>
                                <div class="linha">
                                ';
                            };
                            $html .= '
                            <div class="etiqueta">
                                ';
                                    $html .= $generator->getBarcode($estoque->codigo_barras, $generator::TYPE_CODE_128, 1.1, 25);
                                $html .= '
                                <br>
                                '.$estoque->codigo_barras.'
                            </div>
                            ';
                        }
                    }
                    $html .= '
                </div>
            </body>
            <script>
                window.addEventListener("load", ()=>{
                    print();
                })

                window.addEventListener("afterprint", ()=>{
                    window.close();
                })
            </script>
        </html>
        ';

        echo $html;
    }

    public function etiquetas_imprimir_teste($id){
        $estoque = Estoque::where('id', $id)->first();
        $altura_pagina = intdiv($estoque->quantidade, 3);
        if($estoque->quantidade % 3 > 0){
            $altura_pagina++;
        }

        $altura_pagina = $altura_pagina * 18;

        $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
        //echo $generator->getBarcode('081231723897', $generator::TYPE_CODE_128, 1, 20);

        $html = '
        <!DOCTYPE html>
        <html lang="pt-br">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Impressão de Etiquetas</title>
                <style>
                @page{
                    size: 100mm 21mm
                }
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                .pagina-etiquetas {
                    display: block;
                    width: 100mm;  /* Largura da área de impressão */
                    height: auto;  /* Altura automática para rolo contínuo */
                    padding: 0 2mm;  /* Padding para garantir o espaçamento correto ao redor das etiquetas */
                    background-color: white;
                    page-break-before: always; /* Previne quebras de página */
                }
                .linha {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 3mm; /* Espaço entre as linhas */
                    page-break-inside: avoid;  /* Impede quebras dentro de uma linha */
                }
                .etiqueta {
                    width: 30mm;    /* Largura de cada etiqueta */
                    height: 15mm;   /* Altura de cada etiqueta */
                    margin-right: 3mm;  /* Espaço entre as etiquetas na mesma linha */
                    text-align: center;
                    border: 1px solid black; /* Borda para visualização */
                    align-items: center;
                    justify-content: center;
                    font-size: 10px;
                    background-color: #f0f0f0;
                    padding-top: 2mm;
                }
                .linha .etiqueta:last-child {
                    margin-right: 0; /* Retira o espaçamento da última etiqueta de cada linha */
                }
                @media print {
                    .pagina-etiquetas {
                        display: block;
                        width: 102mm;  /* Largura da área de impressão */
                        padding: 0 2mm;  /* Padding de 3mm */
                        background-color: white;
                        height: auto;  /* Para rolo contínuo */
                        margin: 0;
                    }
                    .linha {
                        display: flex;
                        justify-content: space-between;
                        margin-bottom: 3mm;
                        page-break-inside: avoid;  /* Evita quebras dentro de uma linha */
                    }
                    .etiqueta {
                        width: 30mm;
                        height: 15mm;
                        margin-right: 3mm;
                        text-align: center;
                        border: 1px solid black;
                        align-items: center;
                        justify-content: center;
                        font-size: 10px;
                        background-color: #f0f0f0;
                        padding-top: 2mm
                    }
                    .linha .etiqueta:last-child {
                        margin-right: 0;
                    }
                    /* Evitar quebras de página entre as etiquetas */
                    .pagina-etiquetas {
                        page-break-after: always;
                    }
                }
                </style>
            </head>
            <body>
                <div class="pagina-etiquetas">
                    <div class="linha">';
                        for($i=0 ; $i<$estoque->quantidade ; $i++){
                            if($i % 3 == 0){
                                $html .= '
                                </div>
                                <div class="linha">
                                ';
                            }
                            $html .= '
                            <div class="etiqueta">
                                ';
                                    $html .= $generator->getBarcode('081231723897', $generator::TYPE_CODE_128, 1, 20);
                                $html .= '
                                <br>
                                '.$estoque->codigo_barras.'
                            </div>
                            ';
                        }
                    $html .= '
                    </div>
                </div>
            </body>
            <script>
                window.addEventListener("load", ()=>{
                    print();
                })

                window.addEventListener("afterprint", ()=>{
                    window.close();
                })
            </script>
        </html>
        ';

        echo $html;


        $teste = '
        <div class="container">
            <div class="row">
                ';
                for($i=1 ; $i<=$estoque->quantidade ; $i++){
                    $html .= '
                    <div class="col-3 mt-3" align="center">
                        <span>
                        ';
                            $html .= $generator->getBarcode($estoque->codigo_barras, $generator::TYPE_CODE_128);
                        $html .= '
                        </span>
                        <span>
                            '.$estoque->codigo_barras.'
                        </span>
                    </div>
                    ';
                }
                $html .= '
            </div>
        </div>
        ';
    }

    public function etiquetas_imprimir_1Pagina($id){
        $estoque = Estoque::where('id', $id)->first();

        $altura_pagina = $estoque->quantidade * 18;

        $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
        //echo $generator->getBarcode('081231723897', $generator::TYPE_CODE_128, 1, 20);

        $html = '
        <!DOCTYPE html>
        <html lang="pt-br">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Impressão de Etiquetas</title>
                <style>
                @page{
                    size: 36mm '.$altura_pagina.'mm
                }
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                .pagina-etiquetas {
                    display: block;
                    width: 36mm;  /* Largura da área de impressão */
                    height: auto;  /* Altura automática para rolo contínuo */
                    padding: 0 3mm;  /* Padding para garantir o espaçamento correto ao redor das etiquetas */
                    background-color: white;
                    page-break-before: always; /* Previne quebras de página */
                }
                .linha {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 3mm; /* Espaço entre as linhas */
                    page-break-inside: avoid;  /* Impede quebras dentro de uma linha */
                }
                .etiqueta {
                    width: 30mm;    /* Largura de cada etiqueta */
                    height: 15mm;   /* Altura de cada etiqueta */
                    margin-right: 3mm;  /* Espaço entre as etiquetas na mesma linha */
                    text-align: center;
                    border: 1px solid black; /* Borda para visualização */
                    align-items: center;
                    justify-content: center;
                    font-size: 10px;
                    background-color: #f0f0f0;
                    padding-top: 3mm;
                }
                .linha .etiqueta:last-child {
                    margin-right: 0; /* Retira o espaçamento da última etiqueta de cada linha */
                }
                @media print {
                    .pagina-etiquetas {
                        display: block;
                        width: 36mm;  /* Largura da área de impressão */
                        padding: 0 3mm;  /* Padding de 3mm */
                        background-color: white;
                        height: auto;  /* Para rolo contínuo */
                        margin: 0;
                    }
                    .linha {
                        display: flex;
                        justify-content: space-between;
                        margin-bottom: 3mm;
                        page-break-inside: avoid;  /* Evita quebras dentro de uma linha */
                    }
                    .etiqueta {
                        width: 30mm;
                        height: 15mm;
                        margin-right: 3mm;
                        text-align: center;
                        border: 1px solid black;
                        align-items: center;
                        justify-content: center;
                        font-size: 10px;
                        background-color: #f0f0f0;
                        padding-top: 3mm
                    }
                    .linha .etiqueta:last-child {
                        margin-right: 0;
                    }
                    /* Evitar quebras de página entre as etiquetas */
                    .pagina-etiquetas {
                        page-break-after: always;
                    }
                }
                </style>
            </head>
            <body>
                <div class="pagina-etiquetas">
                    ';
                        for($i=0 ; $i<$estoque->quantidade ; $i++){

                            $html .= '
                            <div class="linha">
                                <div class="etiqueta">
                                    ';
                                        $html .= $generator->getBarcode('081231723897', $generator::TYPE_CODE_128, 1, 20);
                                        $html .= '
                                        <br>
                                        '.$estoque->codigo_barras.'
                                </div>
                            </div>
                            ';
                        }
                    $html .= '
                </div>
            </body>

        </html>
        ';

        echo $html;


        $teste = '
        <div class="container">
            <div class="row">
                ';
                for($i=1 ; $i<=$estoque->quantidade ; $i++){
                    $html .= '
                    <div class="col-3 mt-3" align="center">
                        <span>
                        ';
                            $html .= $generator->getBarcode($estoque->codigo_barras, $generator::TYPE_CODE_128);
                        $html .= '
                        </span>
                        <span>
                            '.$estoque->codigo_barras.'
                        </span>
                    </div>
                    ';
                }
                $html .= '
            </div>
        </div>
        ';
    }

    public function etiquetas_imprimir_teste1($id){
        $estoque = Estoque::where('id', $id)->first();

        $altura_pagina = $estoque->quantidade * 18;

        $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
        //echo $generator->getBarcode('081231723897', $generator::TYPE_CODE_128, 1, 20);

        $html = '
        <!DOCTYPE html>
        <html lang="pt-br">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Impressão de Etiquetas</title>
                <style>
                @page{
                    size: 36mm 21mm
                }
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                .pagina-etiquetas {
                    display: block;
                    width: 36mm;  /* Largura da área de impressão */
                    height: auto;  /* Altura automática para rolo contínuo */
                    padding: 3mm 3mm;  /* Padding para garantir o espaçamento correto ao redor das etiquetas */
                    background-color: white;
                    page-break-before: always; /* Previne quebras de página */
                }
                .linha {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 3mm; /* Espaço entre as linhas */
                    page-break-inside: avoid;  /* Impede quebras dentro de uma linha */
                }
                .etiqueta {
                    width: 30mm;    /* Largura de cada etiqueta */
                    height: 15mm;   /* Altura de cada etiqueta */
                    margin-right: 3mm;  /* Espaço entre as etiquetas na mesma linha */
                    text-align: center;
                    border: 1px solid black; /* Borda para visualização */
                    align-items: center;
                    justify-content: center;
                    font-size: 10px;
                    background-color: #f0f0f0;
                    padding-top: 3mm;
                }
                .linha .etiqueta:last-child {
                    margin-right: 0; /* Retira o espaçamento da última etiqueta de cada linha */
                }
                @media print {
                    .pagina-etiquetas {
                        display: block;
                        width: 36mm;  /* Largura da área de impressão */
                        padding: 3mm 3mm;  /* Padding de 3mm */
                        background-color: white;
                        height: auto;  /* Para rolo contínuo */
                        margin: 0;
                    }
                    .linha {
                        display: flex;
                        justify-content: space-between;
                        margin-bottom: 3mm;
                        page-break-inside: avoid;  /* Evita quebras dentro de uma linha */
                    }
                    .etiqueta {
                        width: 30mm;
                        height: 15mm;
                        margin-right: 3mm;
                        text-align: center;
                        border: 1px solid black;
                        align-items: center;
                        justify-content: center;
                        font-size: 10px;
                        background-color: #f0f0f0;
                        padding-top: 3mm
                    }
                    .linha .etiqueta:last-child {
                        margin-right: 0;
                    }
                    /* Evitar quebras de página entre as etiquetas */
                    .pagina-etiquetas {
                        page-break-after: always;
                    }
                }
                </style>
            </head>
            <body>
                <div class="pagina-etiquetas">
                    ';
                        for($i=0 ; $i<$estoque->quantidade ; $i++){

                            $html .= '
                            <div class="linha">
                                <div class="etiqueta">
                                    ';
                                        $html .= $generator->getBarcode('081231723897', $generator::TYPE_CODE_128, 1, 20);
                                        $html .= '
                                        <br>
                                        '.$estoque->codigo_barras.'
                                </div>
                            </div>
                            ';
                        }
                    $html .= '
                </div>
            </body>
            <script>
                window.addEventListener("load", ()=>{
                    print();
                })

                window.addEventListener("afterprint", ()=>{
                    window.close();
                })
            </script>
        </html>
        ';

        echo $html;


        $teste = '
        <div class="container">
            <div class="row">
                ';
                for($i=1 ; $i<=$estoque->quantidade ; $i++){
                    $html .= '
                    <div class="col-3 mt-3" align="center">
                        <span>
                        ';
                            $html .= $generator->getBarcode($estoque->codigo_barras, $generator::TYPE_CODE_128);
                        $html .= '
                        </span>
                        <span>
                            '.$estoque->codigo_barras.'
                        </span>
                    </div>
                    ';
                }
                $html .= '
            </div>
        </div>
        ';
    }

}
