<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Http\Controllers\ApiFlegowController;

if(!function_exists('api')){
    function api(){
        return new ApiFlegowController();        
    }
}


if(!function_exists('data_escrita')){
    function data_escrita($data){
        $var = explode('-', $data);
        $ano = $var[0];
        $mes = $var[1];
        $dia = $var[2];

        if($mes == '1' || $mes == '01'){
            $mes = 'janeiro';
        }
        elseif($mes == '2' || $mes == '02'){
            $mes = 'fevereiro';
        }
        elseif($mes == '3' || $mes == '03'){
            $mes = 'março';
        }
        elseif($mes == '4' || $mes == '04'){
            $mes = 'abril';
        }
        elseif($mes == '5' || $mes == '05'){
            $mes = 'maio';
        }
        elseif($mes == '6' || $mes == '06'){
            $mes = 'junho';
        }
        elseif($mes == '7' || $mes == '07'){
            $mes = 'julho';
        }
        elseif($mes == '8' || $mes == '08'){
            $mes = 'agosto';
        }
        elseif($mes == '9' || $mes == '09'){
            $mes = 'setembro';
        }
        elseif($mes == '10'){
            $mes = 'outubro';
        }
        elseif($mes == '11'){
            $mes = 'novembro';
        }
        elseif($mes == '12'){
            $mes = 'dezembro';
        }

        $array_semana = ['domingo','segunda-feira','terça-feira','quarta-feira','quinta-feira','sexta-feira','sábado'];
        $dia_semana = date('w', strtotime($data));

        return $array_semana[$dia_semana]." $dia de $mes de $ano";
    }
}

if(!function_exists('createPassword')){
    function createPassword($tamanho, $maiusculas, $minusculas, $numeros, $simbolos){
        $senha = "";
        $ma = "ABCDEFGHIJKLMNOPQRSTUVYXWZ"; // $ma contem as letras maiúsculas
        $mi = "abcdefghijklmnopqrstuvyxwz"; // $mi contem as letras minusculas
        $nu = "0123456789"; // $nu contem os números
        $si = "!@#$%¨&*()_+="; // $si contem os símbolos

        if ($maiusculas){
            // se $maiusculas for "true", a variável $ma é embaralhada e adicionada para a variável $senha
            $senha .= str_shuffle($ma);
        }

        if ($minusculas){
            // se $minusculas for "true", a variável $mi é embaralhada e adicionada para a variável $senha
            $senha .= str_shuffle($mi);
        }

        if ($numeros){
            // se $numeros for "true", a variável $nu é embaralhada e adicionada para a variável $senha
            $senha .= str_shuffle($nu);
        }

        if ($simbolos){
            // se $simbolos for "true", a variável $si é embaralhada e adicionada para a variável $senha
            $senha .= str_shuffle($si);
        }

        // retorna a senha embaralhada com "str_shuffle" com o tamanho definido pela variável $tamanho
        return substr(str_shuffle($senha),0,$tamanho);

    }
}

if(!function_exists('dataDbForm')){
    function dataDbForm($data){
        if(!$data){
            return null;
        }
        $data = explode("-", $data);
        $data = $data[2]."/".$data[1]."/".$data[0];
        return $data;
    }
}

if(!function_exists('dataFormDb')){
    function dataFormDb($data){
        $data = explode("/", $data);
        $data = $data[2]."-".$data[1]."-".$data[0];
        return $data;
    }
}

if(!function_exists('valorFormDb')){
    function valorFormDb($valor){
        //vamos procurar se foi digitado a ,
        $virgula = strpos($valor, ',');

        if($virgula === false){
            $valor = str_replace(".","",$valor);
            $valor = $valor.".00";
            return $valor;
        }

        $var = explode(',', $valor);
        $variavel = $var[1];
        $var = str_replace('.', '', $var[0]);
        $valor = $var.'.'.$variavel[0].$variavel[1];
        return $valor;
    }
}

if(!function_exists('valorDbForm')){
    function valorDbForm($valor){
        return number_format($valor,2,",",".");
    }
}

if(!function_exists('enviarMail')){
    function enviarMail($destinatario, $assunto, $mensagem){
        $mail = new PHPMailer(true);
        try {
            //Server settings
            $mail->setLanguage('br');
            $mail->CharSet = "utf8";
            $mail->SMTPDebug = 0;
            $mail->isSMTP();
            $mail->Host = 'smtp.hostinger.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'contato@gapp.app.br';
            $mail->Password = '*TCw22:vN';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            $mail->FromName = "Instituto Coffea - Sistema Online";
            $mail->From = "contato@gapp.app.br";
            $mail->IsHTML(true);
            $mail->Subject = $assunto;
            $mail->Body = $mensagem;
            $mail->AddAddress($destinatario);
            $mail->Send();
        }
        catch (Exception $e) {
            die($mail->ErrorInfo);
        }
    }
}
?>
