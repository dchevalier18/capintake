<?php

declare(strict_types=1);

namespace App\Exports;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Writes the Module 4 workbook (FNPI / Services / All Characteristics)
 * to a temp file and returns its path. Sheet layout mirrors the NASCSP
 * SmartForm submission workbook.
 */
class CsbgModule4XlsxExport
{
    public function __construct(protected Module4RowBuilder $rowBuilder) {}

    /**
     * Write the workbook and return the temp file path.
     */
    public function write(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'csbg-module4-').'.xlsx';

        $writer = new Writer;
        $writer->openToFile($path);

        $headerStyle = (new Style)->setFontBold();

        $sheets = [
            'FNPI' => $this->rowBuilder->sectionARows(),
            'Services' => $this->rowBuilder->sectionBRows(),
            'All Characteristics' => $this->rowBuilder->sectionCRows(),
        ];

        $first = true;
        foreach ($sheets as $name => $rows) {
            if ($first) {
                $sheet = $writer->getCurrentSheet();
                $first = false;
            } else {
                $sheet = $writer->addNewSheetAndMakeItCurrent();
            }
            $sheet->setName($name);

            foreach ($rows as $index => $row) {
                $writer->addRow(Row::fromValues($row, $index === 0 ? $headerStyle : null));
            }
        }

        $writer->close();

        return $path;
    }
}
