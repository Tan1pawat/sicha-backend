<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use App\Models\Bill;
use App\Models\BillOrder;
use App\Models\Company;
use App\Models\Prison;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ExcelController extends Controller
{
    public function generateInvoice($billId)
    {
        // Load the template file
        $templatePath = storage_path('app/public/ใบเสร็จรับเงิน.xlsx');
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        $bill = Bill::with('billOrders.product.unit')->find($billId);
        if (!$bill) {
            return response()->json(['error' => 'Bill not found'], 404);
        }

        $company = Company::find($bill->company_id);
        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }
        $prison = Prison::find($bill->prison_id);
        if (!$prison) {
            return response()->json(['error' => 'Prison not found'], 404);
        }
        // return $bill; //debug
        $company_name = $company->name;
        $title = "ห้างหุ้นส่วนจำกัด " . $company_name;
        $tab_name = $company_name . ' ' . $bill->date;
        $tab_name = preg_replace('/[\\\\\\/\\:\\*\\?\\[\\]]/', '-', $tab_name);
        $sheet->setTitle($tab_name);
        $sheet->mergeCells('A1:F1'); // Merge cells
        $sheet->setCellValue('A1', $title); // Set company name
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 28,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->setCellValue('A2', $company->sender_address);
        $sheet->setCellValue('A3', "โทร.0890820242");
        $sheet->setCellValue('A4', "เลขประจำตัวผู้เสียภาษี " . $company->sender_tax_number . "(สำนักงานใหญ่)");
        $sheet->setCellValue('F5', $bill->code);
        $sheet->setCellValue('B5', $prison->name);
        $sheet->getStyle('B5')->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->setCellValue('B6', $prison->receiver_address);
        $sheet->getStyle('B6')->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->setCellValue('A7', "เลขประจำตัวผู้เสียภาษี " . $prison->receiver_tax_number);

        $startRow = 10;
        $billOrderCount = count($bill->billOrders);

        if ($billOrderCount > 0) {
            $sheet->insertNewRowBefore($startRow, $billOrderCount);
        }
        foreach ($bill->billOrders as $index => $order) {
            $currentRow = $startRow + $index;

            $sheet->setCellValue('A' . $currentRow, $index + 1);               // Row number
            $sheet->setCellValue('B' . $currentRow, $order->product->name);    // Product name
            $sheet->setCellValue('C' . $currentRow, $order->value);           // Value
            $sheet->setCellValue('D' . $currentRow, $order->product->unit->name);           // Unit
            $sheet->setCellValue('E' . $currentRow, $order->price);           // Price
            $sheet->setCellValue('F' . $currentRow, $order->price * $order->value); // Total (price * value)
        }

        // Sum income cell
        $sumIncomeRow = $startRow + $billOrderCount;
        $sheet->setCellValue('F' . $sumIncomeRow, $bill->sum_income);

        // Add CONCATENATE formula in column B
        $bahtTextFormula = sprintf('=CONCATENATE("(",BAHTTEXT(F%d),")")', $sumIncomeRow);
        $sheet->setCellValue('B' . $sumIncomeRow, $bahtTextFormula);

        // Save the file to storage or download
        $fileName = 'ใบเสร็จรับเงิน_' . $bill->code . '.xlsx';
        $filePath = storage_path('app/public/' . $fileName);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($filePath);

        // Return the file as a download
        return response()->download($filePath)->deleteFileAfterSend();
    }

    public function generateOrder($billId)
    {
        // Load the template file
        $templatePath = storage_path('app/public/ใบส่งของ.xlsx');
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        $bill = Bill::with('billOrders.product.unit')->find($billId);
        if (!$bill) {
            return response()->json(['error' => 'Bill not found'], 404);
        }

        $company = Company::find($bill->company_id);
        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }
        $prison = Prison::find($bill->prison_id);
        if (!$prison) {
            return response()->json(['error' => 'Prison not found'], 404);
        }
        // return $bill; //debug
        $company_name = $company->name;
        $title = "ห้างหุ้นส่วนจำกัด " . $company_name;
        $tab_name = $company_name . ' ' . $bill->date;
        $tab_name = preg_replace('/[\\\\\\/\\:\\*\\?\\[\\]]/', '-', $tab_name);
        $sheet->setTitle($tab_name);
        $sheet->mergeCells('A1:F1'); // Merge cells
        $sheet->setCellValue('A1', $title); // Set company name
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 28,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->setCellValue('A2', $company->sender_address);
        $sheet->setCellValue('A3', "โทร.0890820242");
        $sheet->setCellValue('A4', "เลขประจำตัวผู้เสียภาษี " . $company->sender_tax_number . "(สำนักงานใหญ่)");
        $sheet->setCellValue('F5', $bill->code);
        $sheet->setCellValue('B5', $prison->name);
        $sheet->getStyle('B5')->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->setCellValue('B6', $prison->receiver_address);
        $sheet->getStyle('B6')->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->setCellValue('A7', "เลขประจำตัวผู้เสียภาษี " . $prison->receiver_tax_number);

        $startRow = 10;
        $billOrderCount = count($bill->billOrders);

        if ($billOrderCount > 0) {
            $sheet->insertNewRowBefore($startRow, $billOrderCount);
        }
        foreach ($bill->billOrders as $index => $order) {
            $currentRow = $startRow + $index;

            $sheet->setCellValue('A' . $currentRow, $index + 1);               // Row number
            $sheet->setCellValue('B' . $currentRow, $order->product->name);    // Product name
            $sheet->setCellValue('C' . $currentRow, $order->value);           // Value
            $sheet->setCellValue('D' . $currentRow, $order->product->unit->name);           // Unit
            $sheet->setCellValue('E' . $currentRow, $order->price);           // Price
            $sheet->setCellValue('F' . $currentRow, $order->price * $order->value); // Total (price * value)
        }

        // Sum income cell
        $sumIncomeRow = $startRow + $billOrderCount;
        $sheet->setCellValue('F' . $sumIncomeRow, $bill->sum_income);

        // Add CONCATENATE formula in column B
        $bahtTextFormula = sprintf('=CONCATENATE("(",BAHTTEXT(F%d),")")', $sumIncomeRow);
        $sheet->setCellValue('B' . $sumIncomeRow, $bahtTextFormula);

        // Save the file to storage or download
        $fileName = 'ใบส่งของ_' . $bill->code . '.xlsx';
        $filePath = storage_path('app/public/' . $fileName);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($filePath);

        // Return the file as a download
        return response()->download($filePath)->deleteFileAfterSend();
    }
}
