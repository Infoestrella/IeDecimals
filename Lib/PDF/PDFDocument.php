<?php

namespace FacturaScripts\Plugins\IeDecimals\Lib\PDF;

use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Lib\PDF\PDFDocument as ParentClass;

abstract class PDFDocument extends ParentClass
{
    protected function insertBusinessDocBody($model)
    {
        $headers = [];
        $tableOptions = [
            'cols' => [],
            'shadeCol' => [0.95, 0.95, 0.95],
            'shadeHeadingCol' => [0.95, 0.95, 0.95],
            'width' => $this->tableWidth
        ];

        foreach ($this->getLineHeaders() as $key => $value) {
            $headers[$key] = $value['title'];
            if (in_array($value['type'], ['number', 'percentage'], true)) {
                $tableOptions['cols'][$key] = ['justification' => 'right'];
            }
        }

        $tableData = [];
        foreach ($model->getlines() as $line) {
            $data = [];
            foreach ($this->getLineHeaders() as $key => $value) {
                if (property_exists($line, 'mostrar_precio') &&
                    $line->mostrar_precio === false &&
                    in_array($key, ['pvpunitario', 'dtopor', 'dtopor2', 'pvptotal', 'iva', 'recargo', 'irpf'], true)) {
                    continue;
                }

                if ($key === 'referencia') {
                    $data[$key] = empty($line->{$key}) ? Tools::fixHtml($line->descripcion) : Tools::fixHtml($line->{$key} . " - " . $line->descripcion);
                } elseif ($key === 'cantidad' && property_exists($line, 'mostrar_cantidad')) {
                    $data[$key] = $line->mostrar_cantidad ? $line->{$key} : '';
                
                /*Juan 3 decimales en preciosunitarios*/
                } elseif ($key === 'pvpunitario') {
                    $data[$key] = Tools::number($line->{$key},3);
                                
                } elseif ($value['type'] === 'percentage') {
                    $data[$key] = Tools::number($line->{$key}) . '%';
                } elseif ($value['type'] === 'number') {
                    $data[$key] = Tools::number($line->{$key});
                } else {
                    $data[$key] = $line->{$key};
                }
            }

            $tableData[] = $data;

            if (property_exists($line, 'salto_pagina') && $line->salto_pagina) {
                $this->removeEmptyCols($tableData, $headers, Tools::number(0));
                $this->pdf->ezTable($tableData, $headers, '', $tableOptions);
                $tableData = [];
                $this->pdf->ezNewPage();
            }
        }

        if (false === empty($tableData)) {
            $this->removeEmptyCols($tableData, $headers, Tools::number(0));
            $this->pdf->ezTable($tableData, $headers, '', $tableOptions);
        }
    }
}