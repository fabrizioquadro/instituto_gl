<?php
$files = [
    'c:\xampp\htdocs\instituto_gl\resources\views\sistema\dashboard\enfermeira_acessar_procedimento.blade.php',
    'c:\xampp\htdocs\instituto_gl\resources\views\sistema\dashboard\enfermeira_acessar_procedimento_new.blade.php'
];

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    $pattern = "/\\$\\('#texto_confirmacao_medicamentos'\\)\\.text\\(texto\\);\\s*let modalConfirmar = new bootstrap\\.Modal\\(document\\.getElementById\\('modal_confirmar_aplicacao'\\)\\);\\s*modalConfirmar\\.show\\(\\);/";
    
    $replace = "if (!\$('#formulario_aplicacao')[0].checkValidity()) {\n        \$('#formulario_aplicacao')[0].reportValidity();\n        return;\n    }\n\n    \$('#texto_confirmacao_medicamentos').text(texto);\n\n    \$('#modal_confirmar_aplicacao').appendTo('body');\n    let modalConfirmar = new bootstrap.Modal(document.getElementById('modal_confirmar_aplicacao'));\n    modalConfirmar.show();";
    
    $content = preg_replace($pattern, $replace, $content);
    file_put_contents($file, $content);
}
echo "Fixed modals";
