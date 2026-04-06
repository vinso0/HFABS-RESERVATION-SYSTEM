<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/AnalyticsModel.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

function pdfStr(string $text): string {
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);
}

function pdfMoney(float $amount): string {
    return 'PHP ' . number_format($amount, 2);
}

class AnalyticsPDF extends FPDF {

    public $branchName = '';
    public $reportDate = '';
    public $logoPath   = '';

    public function Header() {
        $this->SetFillColor(217, 26, 126);
        $this->Rect(0, 0, 210, 24, 'F');

        $logoX = 4;
        $logoY = 3;
        $logoH = 16;
        $textX = 10;

        if ($this->logoPath && file_exists($this->logoPath)) {
            $imgInfo = @getimagesize($this->logoPath);
            if ($imgInfo !== false) {
                $mimeToType = [
                    'image/jpeg' => 'JPG',
                    'image/png'  => 'PNG',
                    'image/gif'  => 'GIF',
                ];
                $type = $mimeToType[$imgInfo['mime'] ?? ''] ?? null;
                if ($type !== null) {
                    try {
                        $this->Image($this->logoPath, $logoX, $logoY, 0, $logoH, $type);
                        $imgW          = $imgInfo[0];
                        $imgHpx        = $imgInfo[1];
                        $ratio         = $imgHpx > 0 ? $imgW / $imgHpx : 1;
                        $logoRenderedW = $logoH * $ratio;
                        $textX         = $logoX + $logoRenderedW + 3;
                    } catch (Exception $e) {
                        $textX = 10;
                    }
                }
            }
        }

        $this->SetFont('Arial', 'B', 13);
        $this->SetTextColor(255, 255, 255);
        $this->SetXY($textX, 6);
        $this->Cell(210 - $textX - 4, 12, pdfStr('HAPPY FACE & BODY SPA - ' . strtoupper($this->branchName)), 0, 1, 'L');

        $this->SetFillColor(181, 21, 106);
        $this->Rect(0, 24, 210, 8, 'F');
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(255, 220, 240);
        $this->SetXY(10, 25);
        $this->Cell(0, 6, pdfStr('Analytics Report  |  Generated: ' . $this->reportDate), 0, 1, 'L');

        $this->Ln(5);
    }

    public function Footer() {
        $this->SetY(-12);
        $this->SetFont('Arial', 'I', 7);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 6, 'Page ' . $this->PageNo() . '/{nb} - HFABS Reservation System - Confidential', 0, 0, 'C');
    }

    public function SectionTitle($title) {
        $this->Ln(2);
        $this->SetFillColor(217, 26, 126);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 9, pdfStr(' ' . $title), 0, 1, 'L', true);
        $this->SetTextColor(26, 26, 46);
        $this->Ln(1);
    }

    public function TableHeader($headers, $widths, $aligns = []) {
        $this->SetFillColor(242, 173, 213);
        $this->SetTextColor(142, 16, 83);
        $this->SetFont('Arial', 'B', 8);
        foreach ($headers as $i => $h) {
            $align = $aligns[$i] ?? 'C';
            $this->Cell($widths[$i], 7, pdfStr($h), 1, 0, $align, true);
        }
        $this->Ln();
        $this->SetTextColor(26, 26, 46);
    }

    public function TableRow($values, $widths, $alt = false, $aligns = []) {
        $this->SetFont('Arial', '', 8);
        if ($alt) {
            $this->SetFillColor(253, 242, 248);
        } else {
            $this->SetFillColor(255, 255, 255);
        }
        $maxLines = 1;
        foreach ($values as $i => $v) {
            $w = $widths[$i];
            $charsPerLine = max(1, (int)($w / 1.95));
            $lines = ceil(strlen((string)$v) / $charsPerLine);
            if ($lines > $maxLines) $maxLines = $lines;
        }
        $cellH = max(6, $maxLines * 5);

        foreach ($values as $i => $v) {
            $align = $aligns[$i] ?? 'L';
            $this->Cell($widths[$i], $cellH, pdfStr((string)$v), 1, 0, $align, true);
        }
        $this->Ln();
    }

    public function TableEmpty($totalWidth) {
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(200, 150, 180);
        $this->Cell($totalWidth, 6, 'No data available', 1, 1, 'C');
        $this->SetTextColor(26, 26, 46);
    }

    public function SummaryBox($label, $value, $x, $y, $w = 88, $h = 18) {
        $this->SetXY($x, $y);
        $this->SetFillColor(253, 242, 248);
        $this->SetDrawColor(248, 215, 235);
        $this->Rect($x, $y, $w, $h, 'FD');
        $this->SetFont('Arial', 'B', 7);
        $this->SetTextColor(217, 26, 126);
        $this->SetXY($x + 3, $y + 2);
        $this->Cell($w - 6, 5, pdfStr(strtoupper($label)), 0, 1, 'L');
        $this->SetFont('Arial', 'B', 13);
        $this->SetTextColor(26, 26, 46);
        $this->SetXY($x + 3, $y + 7);
        $this->Cell($w - 6, 8, pdfStr($value), 0, 1, 'L');
    }
}

class AnalyticsController extends Controller {

    private $model;
    private $branch_id;
    private $branch_name;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $allowed = ['admin', 'cashier'];
        if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $this->branch_id   = $_SESSION['branch_id']   ?? null;
        $this->branch_name = $_SESSION['branch_name'] ?? 'Branch';

        if (!$this->branch_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No branch in session']);
            exit;
        }

        $db          = new Database();
        $this->model = new AnalyticsModel($db->getConnection());
    }

    // ─────────────────────
    // EXCEL EXPORT 
    // ─────────────────────
    public function exportExcel() {
        date_default_timezone_set('Asia/Manila');

        $data       = $this->model->getAllAnalytics($this->branch_id);
        $reportDate = date('F d, Y \a\t h:i A');
        $branchName = strtoupper($this->branch_name);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('HFABS Admin')
            ->setTitle('Analytics Report - ' . $this->branch_name)
            ->setSubject('Branch Analytics Report');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Analytics Report');

        // ── Style definitions ──────────────────────────────────────────────

        $titleStyle = [
            'font'      => ['bold' => true, 'size' => 18, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D91A7E']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ];

        $subTitleStyle = [
            'font'      => ['italic' => true, 'size' => 11, 'color' => ['rgb' => 'D91A7E']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ];

        $sectionStyle = [
            'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B5156A']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'indent'     => 1,
            ],
        ];

        $headerStyle = [
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '8E1053']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2ADD5']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D991BC']]],
        ];

        $dataStyle = [
            'font'      => ['size' => 11],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'F0D0E4']]],
        ];

        $altStyle = [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FDF2F8']],
        ];

        // ── Header rows ───────────────────────────────────────────────────

        $row = 1;

        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'HAPPY FACE & BODY SPA - ' . $branchName);
        $sheet->getStyle('A1:F1')->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(38);

        $row = 2;
        $sheet->mergeCells('A2:F2');
        $sheet->setCellValue('A2', 'Analytics Report  |  Generated: ' . $reportDate);
        $sheet->getStyle('A2:F2')->applyFromArray($subTitleStyle);
        $sheet->getRowDimension(2)->setRowHeight(22);
        $row = 4;

        // ── Section writer closure ────────────────────────────────────────
        $writeSection = function(
            string $title,
            array  $headers,
            array  $rows,
            int    &$row,
            array  $colWidths = []
        ) use ($sheet, $sectionStyle, $headerStyle, $dataStyle, $altStyle) {

            $colCount = count($headers);
            $endCol   = chr(64 + $colCount);

            // Section title row
            $sheet->mergeCells('A' . $row . ':' . $endCol . $row);
            $sheet->setCellValue('A' . $row, '  ' . $title);
            $sheet->getStyle('A' . $row . ':' . $endCol . $row)->applyFromArray($sectionStyle);
            $sheet->getRowDimension($row)->setRowHeight(26);   // ← was 22
            $row++;

            // Column header row
            foreach ($headers as $i => $header) {
                $col = chr(65 + $i);
                $sheet->setCellValue($col . $row, $header);
                if (!empty($colWidths[$i])) {
                    $sheet->getColumnDimension($col)->setWidth($colWidths[$i]);
                } else {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            }
            $sheet->getStyle('A' . $row . ':' . $endCol . $row)->applyFromArray($headerStyle);
            $sheet->getRowDimension($row)->setRowHeight(24);   // ← was 18
            $row++;

            // Data rows
            if (empty($rows)) {
                $sheet->mergeCells('A' . $row . ':' . $endCol . $row);
                $sheet->setCellValue('A' . $row, 'No data available');
                $sheet->getStyle('A' . $row)->applyFromArray([
                    'font'      => ['italic' => true, 'color' => ['rgb' => '9CA3AF']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension($row)->setRowHeight(20);
                $row++;
            } else {
                foreach ($rows as $idx => $dataRow) {
                    $vals = array_values($dataRow);
                    foreach ($vals as $i => $val) {
                        $col = chr(65 + $i);
                        $sheet->setCellValue($col . $row, $val);
                    }
                    $rangeRef = 'A' . $row . ':' . $endCol . $row;
                    $sheet->getStyle($rangeRef)->applyFromArray($dataStyle);
                    if ($idx % 2 === 1) {
                        $sheet->getStyle($rangeRef)->applyFromArray($altStyle);
                    }
                    $sheet->getRowDimension($row)->setRowHeight(20);   // ← was 16
                    $row++;
                }
            }

            $row += 2;
        };

        // ── Sections ──────────────────────────────────────────────────────

        $writeSection('1. Reservations Per Day (Last 30 Days)',
            ['Date', 'Total Reservations'],
            array_map(fn($r) => [$r['period'], $r['total']], $data['reservations_per_day']),
            $row, [28, 32]);

        $writeSection('2. Reservations Per Week (Last 12 Weeks)',
            ['Week Starting', 'Total Reservations'],
            array_map(fn($r) => [$r['week_start'], $r['total']], $data['reservations_per_week']),
            $row, [28, 32]);

        $writeSection('3. Reservations Per Month',
            ['Month', 'Total Reservations'],
            array_map(fn($r) => [$r['label'], $r['total']], $data['reservations_per_month']),
            $row, [28, 32]);

        $writeSection('4. Reservations Per Year',
            ['Year', 'Total Reservations'],
            array_map(fn($r) => [$r['period'], $r['total']], $data['reservations_per_year']),
            $row, [20, 32]);

        $writeSection('5. Most Reserved Days of the Week',
            ['Day of Week', 'Total Reservations'],
            array_map(fn($r) => [$r['day_name'], $r['total']], $data['most_reserved_days']),
            $row, [28, 32]);

        $writeSection('6. Most Reserved Months',
            ['Month', 'Total Reservations'],
            array_map(fn($r) => [$r['month_name'], $r['total']], $data['most_reserved_months']),
            $row, [28, 32]);

        $writeSection('7. Returning Customers',
            ['Username', 'Email', 'Completed Reservations'],
            array_map(fn($r) => [$r['username'], $r['email'], $r['reservation_count']], $data['returning_customers']),
            $row, [26, 46, 32]);

        $writeSection('8. Most Reserved Services (Top 10)',
            ['Service Name', 'Category', 'Bookings', 'Total Revenue (PHP)'],
            array_map(fn($r) => [
                $r['service_name'],
                ucfirst($r['category'] ?? '-'),
                $r['booking_count'],
                number_format((float)$r['total_revenue'], 2),
            ], $data['most_reserved_services']),
            $row, [40, 26, 18, 30]);

        $writeSection('9. Revenue Per Day (Last 30 Days)',
            ['Date', 'Total Revenue (PHP)', 'Transactions'],
            array_map(fn($r) => [
                $r['period'],
                number_format((float)$r['total_revenue'], 2),
                $r['transaction_count'],
            ], $data['revenue_per_day']),
            $row, [28, 32, 24]);

        $writeSection('10. Revenue Per Month',
            ['Month', 'Total Revenue (PHP)', 'Transactions'],
            array_map(fn($r) => [
                $r['label'],
                number_format((float)$r['total_revenue'], 2),
                $r['transaction_count'],
            ], $data['revenue_per_month']),
            $row, [28, 32, 24]);

        $writeSection('11. Revenue Per Year',
            ['Year', 'Total Revenue (PHP)', 'Transactions'],
            array_map(fn($r) => [
                $r['period'],
                number_format((float)$r['total_revenue'], 2),
                $r['transaction_count'],
            ], $data['revenue_per_year']),
            $row, [20, 32, 24]);

        // ── Page setup ────────────────────────────────────────────────────
        $sheet->getPageSetup()
            ->setFitToPage(true)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageMargins()
            ->setTop(0.5)->setBottom(0.5)
            ->setLeft(0.5)->setRight(0.5);
        $sheet->getPageSetup()->setPaperSize(
            \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4
        );

        $filename = 'HFABS_Analytics_' . str_replace(' ', '_', $this->branch_name) . '_' . date('Y-m-d') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // ──────────────────────────
    // PDF EXPORT
    // ──────────────────────────
    public function exportPDF() {
        date_default_timezone_set('Asia/Manila');

        $data       = $this->model->getAllAnalytics($this->branch_id);
        $reportDate = date('F d, Y \a\t g:i A');
        $branchName = ucwords($this->branch_name);

        $pdf = new AnalyticsPDF('P', 'mm', 'A4');
        $pdf->branchName = $branchName;
        $pdf->reportDate = $reportDate;

        $logoJpg = __DIR__ . '/../../assets/logo.jpg';
        $logoPng = __DIR__ . '/../../assets/logo.png';
        if (file_exists($logoJpg)) {
            $pdf->logoPath = $logoJpg;
        } elseif (file_exists($logoPng)) {
            $pdf->logoPath = $logoPng;
        } else {
            $pdf->logoPath = '';
        }

        $pdf->AliasNbPages();
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(true, 18);

        $totalReservations = 0;
        foreach ($data['reservations_per_day'] as $r) {
            $totalReservations += (int)$r['total'];
        }
        $totalRevenue = 0;
        foreach ($data['revenue_per_day'] as $r) {
            $totalRevenue += (float)$r['total_revenue'];
        }
        $topService     = !empty($data['most_reserved_services'])
            ? $data['most_reserved_services'][0]['service_name']
            : 'N/A';
        $returningCount = (string)count($data['returning_customers']);

        $boxW   = 88;
        $boxH   = 20;
        $colL   = 10;
        $colR   = 108;
        $rowGap = 4;
        $row1Y  = $pdf->GetY();

        $pdf->SummaryBox('Total Reservations (Last 30 Days)', (string)$totalReservations, $colL, $row1Y, $boxW, $boxH);
        $pdf->SummaryBox('Total Revenue (Last 30 Days)', 'PHP ' . number_format($totalRevenue, 2), $colR, $row1Y, $boxW, $boxH);

        $row2Y = $row1Y + $boxH + $rowGap;
        $pdf->SummaryBox('Top Service', $topService, $colL, $row2Y, $boxW, $boxH);
        $pdf->SummaryBox('Returning Customers', $returningCount, $colR, $row2Y, $boxW, $boxH);

        $pdf->SetY($row2Y + $boxH + 6);

        $pdf->SectionTitle('1. Reservations Per Day (Last 30 Days)');
        $pdf->TableHeader(['Date', 'Total Reservations'], [130, 60], ['L', 'C']);
        foreach ($data['reservations_per_day'] as $idx => $r) {
            $pdf->TableRow([$r['period'], $r['total']], [130, 60], $idx % 2 === 1, ['L', 'C']);
        }
        if (empty($data['reservations_per_day'])) { $pdf->TableEmpty(190); }
        $pdf->Ln(4);

        $pdf->SectionTitle('2. Reservations Per Week (Last 12 Weeks)');
        $pdf->TableHeader(['Week Starting', 'Total Reservations'], [130, 60], ['L', 'C']);
        foreach ($data['reservations_per_week'] as $idx => $r) {
            $pdf->TableRow([$r['week_start'], $r['total']], [130, 60], $idx % 2 === 1, ['L', 'C']);
        }
        if (empty($data['reservations_per_week'])) { $pdf->TableEmpty(190); }
        $pdf->Ln(4);

        $pdf->SectionTitle('3. Reservations Per Month');
        $pdf->TableHeader(['Month', 'Total Reservations'], [130, 60], ['L', 'C']);
        foreach ($data['reservations_per_month'] as $idx => $r) {
            $pdf->TableRow([$r['label'], $r['total']], [130, 60], $idx % 2 === 1, ['L', 'C']);
        }
        if (empty($data['reservations_per_month'])) { $pdf->TableEmpty(190); }
        $pdf->Ln(4);

        $pdf->SectionTitle('4. Reservations Per Year');
        $pdf->TableHeader(['Year', 'Total Reservations'], [130, 60], ['L', 'C']);
        foreach ($data['reservations_per_year'] as $idx => $r) {
            $pdf->TableRow([$r['period'], $r['total']], [130, 60], $idx % 2 === 1, ['L', 'C']);
        }
        if (empty($data['reservations_per_year'])) { $pdf->TableEmpty(190); }
        $pdf->Ln(4);

        $pdf->SectionTitle('5. Most Reserved Days of the Week');
        $pdf->TableHeader(['Day of Week', 'Total Reservations'], [130, 60], ['L', 'C']);
        foreach ($data['most_reserved_days'] as $idx => $r) {
            $pdf->TableRow([$r['day_name'], $r['total']], [130, 60], $idx % 2 === 1, ['L', 'C']);
        }
        if (empty($data['most_reserved_days'])) { $pdf->TableEmpty(190); }
        $pdf->Ln(4);

        $pdf->SectionTitle('6. Most Reserved Months');
        $pdf->TableHeader(['Month', 'Total Reservations'], [130, 60], ['L', 'C']);
        foreach ($data['most_reserved_months'] as $idx => $r) {
            $pdf->TableRow([$r['month_name'], $r['total']], [130, 60], $idx % 2 === 1, ['L', 'C']);
        }
        if (empty($data['most_reserved_months'])) { $pdf->TableEmpty(190); }
        $pdf->Ln(4);

        $pdf->SectionTitle('7. Returning Customers');
        $pdf->TableHeader(['Username', 'Email', 'Completed'], [45, 110, 35], ['L', 'L', 'C']);
        foreach ($data['returning_customers'] as $idx => $r) {
            $pdf->TableRow(
                [$r['username'], $r['email'], $r['reservation_count']],
                [45, 110, 35], $idx % 2 === 1, ['L', 'L', 'C']
            );
        }
        if (empty($data['returning_customers'])) { $pdf->TableEmpty(190); }
        $pdf->Ln(4);

        $pdf->SectionTitle('8. Most Reserved Services (Top 10)');
        $pdf->TableHeader(['Service Name', 'Category', 'Bookings', 'Revenue (PHP)'], [75, 45, 25, 45], ['L', 'L', 'C', 'R']);
        foreach ($data['most_reserved_services'] as $idx => $r) {
            $pdf->TableRow([
                $r['service_name'],
                ucfirst($r['category'] ?? '-'),
                $r['booking_count'],
                pdfMoney((float)$r['total_revenue']),
            ], [75, 45, 25, 45], $idx % 2 === 1, ['L', 'L', 'C', 'R']);
        }
        if (empty($data['most_reserved_services'])) { $pdf->TableEmpty(190); }
        $pdf->Ln(4);

        $pdf->SectionTitle('9. Revenue Per Day (Last 30 Days)');
        $pdf->TableHeader(['Date', 'Total Revenue (PHP)', 'Transactions'], [80, 75, 35], ['L', 'R', 'C']);
        foreach ($data['revenue_per_day'] as $idx => $r) {
            $pdf->TableRow(
                [$r['period'], pdfMoney((float)$r['total_revenue']), $r['transaction_count']],
                [80, 75, 35], $idx % 2 === 1, ['L', 'R', 'C']
            );
        }
        if (empty($data['revenue_per_day'])) { $pdf->TableEmpty(190); }
        $pdf->Ln(4);

        $pdf->SectionTitle('10. Revenue Per Month');
        $pdf->TableHeader(['Month', 'Total Revenue (PHP)', 'Transactions'], [80, 75, 35], ['L', 'R', 'C']);
        foreach ($data['revenue_per_month'] as $idx => $r) {
            $pdf->TableRow(
                [$r['label'], pdfMoney((float)$r['total_revenue']), $r['transaction_count']],
                [80, 75, 35], $idx % 2 === 1, ['L', 'R', 'C']
            );
        }
        if (empty($data['revenue_per_month'])) { $pdf->TableEmpty(190); }
        $pdf->Ln(4);

        $pdf->SectionTitle('11. Revenue Per Year');
        $pdf->TableHeader(['Year', 'Total Revenue (PHP)', 'Transactions'], [80, 75, 35], ['L', 'R', 'C']);
        foreach ($data['revenue_per_year'] as $idx => $r) {
            $pdf->TableRow(
                [$r['period'], pdfMoney((float)$r['total_revenue']), $r['transaction_count']],
                [80, 75, 35], $idx % 2 === 1, ['L', 'R', 'C']
            );
        }
        if (empty($data['revenue_per_year'])) { $pdf->TableEmpty(190); }

        $filename = 'HFABS_Analytics_' . str_replace(' ', '_', $this->branch_name) . '_' . date('Y-m-d') . '.pdf';
        $pdf->Output('D', $filename);
        exit;
    }
}