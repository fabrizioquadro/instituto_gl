<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Administrador;
use App\Models\User;
use App\Models\Clinica;
use App\Models\Estoque;
use App\Models\Paciente;
use App\Models\Procedimento;
use App\Models\FinanceiroProcedimento;
use App\Models\Financeiro;
use App\Models\FinanceiroFormasPagamento;
use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;


class LoginController extends Controller
{
    public function index(){
        return view('login/index');
    }

    public function login(Request $request){
        $adm = Administrador::where('email', $request->email)->first();
        if($adm){
            if(Hash::check($request->password, $adm->password)){
                session()->put('administrador', $adm);
                session()->put('layout', 'admin');
                $user = new User();
                $user->id = '0';
                $user->nome = "Adm";
                $user->tipo = "Secretária";
                $user->clinica_id = Clinica::all()->first() ? Clinica::all()->first()->id : '';
                session()->put('user', $user);

                return redirect()->route('adm.dashboard');
            }
            else{
                return redirect()->back()->with('erro', "Senha Inválido");
            }
        }
        else{
            $dados = $request->except('_token');
            if(Auth::attempt($dados)){
                $request->session()->regenerate();
                $user = auth()->user();
                session()->put('layout', 'sistema');
                return redirect()->route('sistema.dashboard');
            }
            else{
                return redirect()->back()->with('erro', "Email ou senha inválidos");
            }
        }
    }

    public function esqueceu_senha(){
        return view('login/esqueceu_senha');
    }

    public function recuperar_senha(Request $request){
        $adm = Administrador::where('email', $request->email)->first();
        if($adm){
            $novaSenha = createPassword(8, true, true, true, false);

            $adm->password = bcrypt($novaSenha);
            $adm->save();

            $mensagem = "
            <h4>Nova Senha de Acesso ao Instituto GL - Sistema Online</h4>
            <p>
                Foi alterado por sua solicitação a senha de acesso ao sistema.
            </p>
            <p>
                Sua nova senha é: $novaSenha
            </p>
            ";

            enviarMail($adm->email, 'Nova Senha de Acesso', $mensagem);

            return redirect()->route('index')->with('sucesso','Sua nova senha foi enviado para o seu email.');
        }
        else{
            $user = User::where('email', $request->email)->first();
            if($user){
                $novaSenha = createPassword(8, true, true, true, false);

                $user->password = bcrypt($novaSenha);
                $user->save();

                $mensagem = "
                <h4>Nova Senha de Acesso ao Instituto GL - Sistema Online</h4>
                <p>
                    Foi alterado por sua solicitação a senha de acesso ao sistema.
                </p>
                <p>
                    Sua nova senha é: $novaSenha
                </p>
                ";

                enviarMail($user->email, 'Nova Senha de Acesso', $mensagem);

                return redirect()->route('index')->with('sucesso','Sua nova senha foi enviado para o seu email.');
            }
            else{
                return redirect()->back()->with('erro', "Email inválido");
            }
        }
    }

    public function logout(Request $request){
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('index');
    }

    public function teste(){
        $api = new ApiFlegowController();
        $api->get_procedimentos_profissional();

    }

    public function teste_assinatura(){
        /*
        $pfxPath = public_path('certificado.pfx');
        $pfxPass = 'drgustavo123';
        $sourcePdf = public_path('procedimentos.pdf');
        $outName = 'documento_assinado.pdf';

        $pfxContents = file_get_contents($pfxPath);
        if ($pfxContents === false) {
            throw new \Exception("Não conseguiu ler o arquivo PFX em: $pfxPath");
        }

        $certs = [];
        if (!openssl_pkcs12_read($pfxContents, $certs, $pfxPass)) {
            // fallback: informe ao usuário ou converta com openssl CLI
            throw new \Exception("Falha ao ler o PFX com openssl_pkcs12_read(). Tente converter para PEM via 'openssl pkcs12 -in certificado.pfx -out cert.pem -nodes' e use o PEM.");
        }

        // $certs agora contém 'cert' (PEM), 'pkey' (PEM) e possivelmente 'extracerts'
        $certPem = $certs['cert'];
        $pkeyPem = $certs['pkey'];

        // 2) Info da assinatura (visível / metadados)
        $info = [
            'Name' => 'Dr Gustavo',
            'Location' => 'São Paulo, Brazil',
            'Reason' => 'Assinatura do documento',
            'ContactInfo' => 'fabrizio.quadro@gmail.com'
        ];

        // 3) Criar objeto FPDI/TCPDF e configurar assinatura
        $pdf = new Fpdi();

        // Opcional: meta
        $pdf->SetCreator('Meu sistema');
        $pdf->SetAuthor('Meu sistema');
        $pdf->SetTitle('Documento Assinado');

        // IMPORTANTE: configure a assinatura antes de gerar páginas/fechar o doc
        // parâmetros: cert (string/file), pkey (string/file), password, algo, certificationlevel, info
        // recomenda-se passar strings PEM (como estamos fazendo)
        $pdf->setSignature($certPem, $pkeyPem, '', '', 2, $info);

        // 4) Importe páginas do PDF fonte e adicione a aparência da assinatura
        $pageCount = $pdf->setSourceFile($sourcePdf);
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $tplId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($tplId);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tplId);

            // só adiciona a aparência na última página, ou onde preferir
            if ($pageNo === $pageCount) {
                $x = $size['width'] - 60;
                $y = $size['height'] - 40;
                $w = 50; $h = 25;

                $pdf->SetFont('helvetica', '', 8);
                $pdf->Text($x, $y, "Assinado digitalmente por:\nNome do Signatário");

                // vincula a área de assinatura digital
                $pdf->setSignatureAppearance($x, $y, $w, $h);

                // Ex.: caixa visível no canto inferior direito (x, y, w, h)
                $x = $size['width'] - 150;
                $y = $size['height'] - 130;
                $w = 100; $h = 100;
                // desenha imagem ou apenas a área
                //$pdf->Image('assinatura_visual.png', $x+2, $y+2, 40, 20);
                $pdf->setSignatureAppearance($x, $y, $w, $h);
                // se preferir só criar a caixa sem conteúdo visual:
                // $pdf->addEmptySignatureAppearance($x, $y, $w, $h);
                //
            }
        }

        // 5) Output
        $pdf->Output($outName, 'I'); // 'I' = inline no browser, 'D' = download, 'F' = salvar no disco
        */
        // Instancie o TCPDF (assegure-se de ter o `use Elibyy\TCPDF\Facades\TCPDF;`)
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Seu Nome');
        $pdf->SetTitle('Título do Documento');
        $pdf->SetSubject('Assunto do Documento');

        $pdf->AddPage();

        $html = '<h1>Olá Mundo!</h1><p>Este é um exemplo de texto usando <strong>TCPDF</strong>.</p>';
        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->Output('teste.pdf', 'I'); // 'I' = inline no browser, 'D' = download, 'F' = salvar no disco
    }

    public function integra_api_kamino(){
        $client = new \GuzzleHttp\Client();

        $response = $client->request('POST', 'https://sandbox.kamino.tech/api/pessoa/grava',
            [
                'body' => '{"ID":0,"Nome":"Thaize Armas","CPFCNPJ":"01236835050","Cliente":true}',
                'headers' => [
                    'App' => '8fcd7f0a-7f3c-4edd-adfa-cb7603d486f4',
                    'CN' => 'Sandbox3138',
                    'Hash' => 'SYWBgkeFPn46R4VCgTpEhIKCOn6ChX46gYBHRj5CgkRJRoVEQklBPoJBPkE6gkeAQDpEQoBBOn5JSUI6Rn5HhEU+RYFHQEVKln6PgoCRnEJAQklJRw==',
                    'IDUsr' => '87',
                    'Usr' => '3820d202-d7b1-43b2-a883-6a7e505c7159',
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                 ],
            ]
        );

        echo $response->getBody();
    }

    public function teste_financeiro(){
        die();
        $codigo = '982920260107175422';

        //vamos analizar se já possui financeiro esse codigo
        $procedimentos = Procedimento::where('codigo', $codigo)->orderBy('nr_procedimento')->get();

        $financeiro_id = null;
        $controle_financeiro = true;
        $vl_procedimentos = 0;

        foreach($procedimentos as $procedimento){
            $vl_procedimentos += $procedimento->valor;
            if($controle_financeiro){
                $var = FinanceiroProcedimento::where('procedimento_id', $procedimento->id)->first();
                if($var){
                    $financeiro_id = $var->financeiro_id;
                    $controle_financeiro = false;
                }
            }
        }

        if($controle_financeiro){
            $dados = [
                'clinica_id' => $procedimento->clinica_id,
                'paciente_id' => $procedimento->paciente_id,
                'medico' => $procedimento->medico,
                'dt_pagamento' => date('Y-m-d'),
                'vl_consulta' => '0.00',
                'vl_procedimentos' => $vl_procedimentos,
                'vl_desconto' => '0.00',
                'vl_pagamento' => '0.00',
                'tipo_pagamento' => 'teste',
                'forma_pagamento' => 'teste',
                'parcelas' => '1',
            ];
            $financeiro = Financeiro::create($dados);

            foreach($procedimentos as $procedimento){
                $dados = [
                    'financeiro_id' => $financeiro->id,
                    'procedimento_id' => $procedimento->id,
                ];
                FinanceiroProcedimento::create($dados);
            }
        }
        else{
            $financeiro = Financeiro::where('id', $financeiro_id)->first();
        }

        //vamos recalcular tudo
        $valor_pago = FinanceiroFormasPagamento::where('financeiro_id', $financeiro->id)->sum('vl_pagamento');

        if($valor_pago > 0){
            if($valor_pago >= $financeiro->vl_consulta){
                $vl_consulta_pagamento = $financeiro->vl_consulta;
            }
            elseif($valor_pago < $financeiro->vl_consulta){
                $vl_consulta_pagamento = $valor_pago;
            }
            $valor_pago -= $vl_consulta_pagamento;
            $financeiro->vl_consulta_pagamento = $vl_consulta_pagamento;
            $financeiro->save();
        }
        else{
            $financeiro->vl_consulta_pagamento = '0.00';
            $financeiro->save();
        }

        //para aplicar o desconto nos procedimentos
        $valor_pago += $financeiro->vl_desconto;

        $procedimentos = Procedimento::where('codigo', $codigo)
        ->where('st_pagamento','<>','Pendente')
        ->orderBy('nr_procedimento')->get();

        dd($procedimentos);

        foreach($procedimentos as $procedimento){
            if($valor_pago > 0){
                if($valor_pago >= $procedimento->valor){
                    $st_pagamento = 'Sim';
                    $vl_pago = $procedimento->valor;
                }
                elseif($valor_pago < $procedimento->valor){
                    $st_pagamento = 'Parcial';
                    $vl_pago = $valor_pago;
                }

                $valor_pago -= $vl_pago;

                $procedimento->st_pagamento = $st_pagamento;
                $procedimento->tipo_pagamento = $financeiro->tipo_pagamento;
                $procedimento->forma_pagamento = $financeiro->forma_pagamento;
                $procedimento->parcelas = $financeiro->parcelas;
                $procedimento->obs_pagamento = $financeiro->obs_pagamento;
                $procedimento->data_pagamento = $financeiro->dt_pagamento;
                $procedimento->vl_pago = $vl_pago;
                $procedimento->save();
            }
            else{
                if($procedimento->situacao != "Semana Sem Aplicação"){
                    $procedimento->st_pagamento = 'Não';
                    $procedimento->vl_pago = '0.00';
                    $procedimento->save();
                }
            }
        }





    }

}
