<?php
namespace App\Helpers;
use setasign\Fpdi\Tcpdf\Fpdi;

class GerarPdf extends Fpdi{
    public function Header() {
        $this->Image(public_path('img/logo.png'), 5, 5, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        // Definir a fonte para o título
        //$this->SetFont('helvetica', 'B', 20);
        //$this->Cell(0, 35, 'Relatório de Procedimentos', 1, false, 'C', 0, '', 0, false, 'M',);
        $this->SetFont('helvetica', 'B', 20);
        $this->MultiCell(
            0,   // largura
            30,    // altura
            'Relatório de Procedimentos',          // texto
            0,              // borda
            'C',            // alinhamento horizontal
            false,           // preenchimento
            1,              // quebra de linha
            '',             // x
            '',             // y
            true,           // reset height
            0,              // stretch
            false,          // ishtml
            true,           // autopadding
            0,              // maxh
            'M',            // alinhamento vertical (M = Middle)
            true            // fitcell
        );
        $this->Line(10, 35, 200, 35);
    }
}
