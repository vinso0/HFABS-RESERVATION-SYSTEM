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

class AnalyticsPDF extends FPDF {
    public $branchName = '';
    public $reportDate = '';

    public function Header() {
        // ── Primary pink title bar ──
        $this->SetFillColor(217, 26, 126);          // #D91A7E
        $this->Rect(0, 0, 210, 22, 'F');
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(255, 255, 255);
        $this->SetXY(10, 5);
        $this->Cell(0, 12, 'HAPPY FACE & BODY SPA - ' . strtoupper($this->branchName), 0, 1, 'L');

        // ── Darker pink sub-header bar ──
        $this->SetFillColor(181, 21, 106);          // #B5156A
        $this->Rect(0, 22, 210, 8, 'F');
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(255, 220, 240);         // soft white-pink
        $this->SetXY(10, 23);
        $this->Cell(0, 6, 'Analytics Report  |  Generated: ' . $this->reportDate, 0, 1, 'L');
        $this->Ln(6);
    }

    public function Footer() {
        $this->SetY(-12);
        $this->SetFont('Arial', 'I', 7);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 6, 'Page ' . $this->PageNo() . '/{nb}  -  HFABS Reservation System  -  Confidential', 0, 0, 'C');
    }

    public function SectionTitle($title) {
        $this->SetFillColor(217, 26, 126);          // #D91A7E
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 8, '  ' . $title, 0, 1, 'L', true);
        $this->SetTextColor(26, 26, 46);            // navy
        $this->Ln(1);
    }

    public function TableHeader($headers, $widths) {
        $this->SetFillColor(242, 173, 213);         // light pink
        $this->SetTextColor(142, 16, 83);           // deep pink
        $this->SetFont('Arial', 'B', 8);
        foreach ($headers as $i => $h) {
            $this->Cell($widths[$i], 7, $h, 1, 0, 'C', true);
        }
        $this->Ln();
        $this->SetTextColor(26, 26, 46);
    }

    public function TableRow($values, $widths, $alt = false) {
        $this->SetFont('Arial', '', 8);
        if ($alt) {
            $this->SetFillColor(253, 242, 248);     // soft pink row
        } else {
            $this->SetFillColor(255, 255, 255);
        }
        foreach ($values as $i => $v) {
            $this->Cell($widths[$i], 6, $v, 1, 0, 'L', true);
        }
        $this->Ln();
    }

    public function TableEmpty($totalWidth) {
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(200, 150, 180);
        $this->Cell($totalWidth, 6, 'No data available', 1, 1, 'C');
        $this->SetTextColor(26, 26, 46);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
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

        // Instantiate the model — pass PDO connection to it
        $db          = new Database();
        $this->model = new AnalyticsModel($db->getConnection());
    }

    // ── Excel Export ──
    public function exportExcel() {
        // Controller's only job: get data from model, pass to export logic
        $data       = $this->model->getAllAnalytics($this->branch_id);
        $reportDate = date('F d, Y');
        $branchName = strtoupper($this->branch_name);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('HFABS Admin')
            ->setTitle('Analytics Report - ' . $this->branch_name)
            ->setSubject('Branch Analytics Report');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Analytics Report');

        // ── Title bar ──
        $titleStyle = [
            'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D91A7E']], // primary pink
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];

        // ── Subtitle ──
        $subTitleStyle = [
            'font'      => ['italic' => true, 'color' => ['rgb' => 'D91A7E']],                    // pink text
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];

        // ── Section header bar ──
        $sectionStyle = [
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B5156A']], // dark pink
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ];

        // ── Column header row ──
        $headerStyle = [
            'font'      => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '8E1053']],         // deep pink text
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2ADD5']], // light pink fill
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'F8D7EB']]],
        ];

        // ── Data rows ──
        $dataStyle = [
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'F8D7EB']]],
        ];

        // ── Alternating rows ──
        $altStyle = [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FDF2F8']],      // #fdf2f8 soft pink
        ];

        $row = 1;

        $sheet->mergeCells('A' . $row . ':F' . $row);
        $sheet->setCellValue('A' . $row, 'HAPPY FACE & BODY SPA - ' . $branchName);
        $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray($titleStyle);
        $sheet->getRowDimension($row)->setRowHeight(30);
        $row++;

        $sheet->mergeCells('A' . $row . ':F' . $row);
        $sheet->setCellValue('A' . $row, 'Analytics Report - Generated: ' . $reportDate);
        $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray($subTitleStyle);
        $row += 2;

        $writeSection = function(string $title, array $headers, array $rows, int &$row)
            use ($sheet, $sectionStyle, $headerStyle, $dataStyle, $altStyle) {

            $colCount = count($headers);
            $endCol   = chr(64 + $colCount);

            $sheet->mergeCells('A' . $row . ':' . $endCol . $row);
            $sheet->setCellValue('A' . $row, '  ' . $title);
            $sheet->getStyle('A' . $row . ':' . $endCol . $row)->applyFromArray($sectionStyle);
            $sheet->getRowDimension($row)->setRowHeight(20);
            $row++;

            foreach ($headers as $i => $header) {
                $col = chr(65 + $i);
                $sheet->setCellValue($col . $row, $header);
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            $sheet->getStyle('A' . $row . ':' . $endCol . $row)->applyFromArray($headerStyle);
            $row++;

            if (empty($rows)) {
                $sheet->mergeCells('A' . $row . ':' . $endCol . $row);
                $sheet->setCellValue('A' . $row, 'No data available');
                $sheet->getStyle('A' . $row)->applyFromArray(['font' => ['italic' => true, 'color' => ['rgb' => '9CA3AF']]]);
                $row++;
            } else {
                foreach ($rows as $idx => $dataRow) {
                    foreach (array_values($dataRow) as $i => $val) {
                        $sheet->setCellValue(chr(65 + $i) . $row, $val);
                    }
                    $sheet->getStyle('A' . $row . ':' . $endCol . $row)->applyFromArray($dataStyle);
                    if ($idx % 2 === 1) {
                        $sheet->getStyle('A' . $row . ':' . $endCol . $row)->applyFromArray($altStyle);
                    }
                    $row++;
                }
            }
            $row += 2;
        };

        $writeSection('1. Reservations Per Day (Last 30 Days)',
            ['Date', 'Total Reservations'],
            array_map(function($r) { return [$r['period'], $r['total']]; }, $data['reservations_per_day']),
            $row);

        $writeSection('2. Reservations Per Week (Last 12 Weeks)',
            ['Week Starting', 'Total Reservations'],
            array_map(function($r) { return [$r['week_start'], $r['total']]; }, $data['reservations_per_week']),
            $row);

        $writeSection('3. Reservations Per Month',
            ['Month', 'Total Reservations'],
            array_map(function($r) { return [$r['label'], $r['total']]; }, $data['reservations_per_month']),
            $row);

        $writeSection('4. Reservations Per Year',
            ['Year', 'Total Reservations'],
            array_map(function($r) { return [$r['period'], $r['total']]; }, $data['reservations_per_year']),
            $row);

        $writeSection('5. Most Reserved Days of the Week',
            ['Day', 'Total Reservations'],
            array_map(function($r) { return [$r['day_name'], $r['total']]; }, $data['most_reserved_days']),
            $row);

        $writeSection('6. Most Reserved Months',
            ['Month', 'Total Reservations'],
            array_map(function($r) { return [$r['month_name'], $r['total']]; }, $data['most_reserved_months']),
            $row);

        $writeSection('7. Returning Customers',
            ['Username', 'Email', 'Completed Reservations'],
            array_map(function($r) { return [$r['username'], $r['email'], $r['reservation_count']]; }, $data['returning_customers']),
            $row);

        $writeSection('8. Most Reserved Services (Top 10)',
            ['Service Name', 'Category', 'Bookings', 'Total Revenue (PHP)'],
            array_map(function($r) {
                return [$r['service_name'], ucfirst($r['category'] ?? '-'), $r['booking_count'], number_format((float)$r['total_revenue'], 2)];
            }, $data['most_reserved_services']),
            $row);

        $writeSection('9. Revenue Per Day (Last 30 Days)',
            ['Date', 'Total Revenue (PHP)', 'Transactions'],
            array_map(function($r) {
                return [$r['period'], number_format((float)$r['total_revenue'], 2), $r['transaction_count']];
            }, $data['revenue_per_day']),
            $row);

        $writeSection('10. Revenue Per Month',
            ['Month', 'Total Revenue (PHP)', 'Transactions'],
            array_map(function($r) {
                return [$r['label'], number_format((float)$r['total_revenue'], 2), $r['transaction_count']];
            }, $data['revenue_per_month']),
            $row);

        $writeSection('11. Revenue Per Year',
            ['Year', 'Total Revenue (PHP)', 'Transactions'],
            array_map(function($r) {
                return [$r['period'], number_format((float)$r['total_revenue'], 2), $r['transaction_count']];
            }, $data['revenue_per_year']),
            $row);

        $filename = 'HFABS_Analytics_' . str_replace(' ', '_', $this->branch_name) . '_' . date('Y-m-d') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // ── PDF Export ──
    public function exportPDF() {
        // Controller's only job: get data from model, pass to export logic
        $data       = $this->model->getAllAnalytics($this->branch_id);
        $reportDate = date('F d, Y \a\t h:i A');
        $branchName = ucwords($this->branch_name);

        $pdf = new AnalyticsPDF('P', 'mm', 'A4');
        $pdf->branchName = $branchName;
        $pdf->reportDate = $reportDate;
        $pdf->AliasNbPages();
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(true, 18);

        $pdf->SectionTitle('1. Reservations Per Day (Last 30 Days)');
        $pdf->TableHeader(['Date', 'Total Reservations'], [100, 90]);
        foreach ($data['reservations_per_day'] as $idx => $r) {
            $pdf->TableRow([$r['period'], $r['total']], [100, 90], $idx % 2 === 1);
        }
        if (empty($data['reservations_per_day'])) { $pdf->TableEmpty(190); }
        $pdf->Ln(4);

        $pdf->SectionTitle('2. Reservations Per Week (Last 12 Weeks)');
        $pdf->TableHeader(['Week Starting', 'Total Reservations'], [100, 90]);
        foreach ($data['reservations_per_week'] as $idx => $r) {
            $pdf->TableRow([$r['week_start'], $r['total']], [100, 90], $idx % 2 === 1);
        }
        if (empty($data['reservations_per_week'])) { $pdf->TableEmpty(190); }
        $pdf->Ln(4);

        $pdf->SectionTitle('3. Reservations Per Month');
        $pdf->TableHeader(['Month', 'Total Reservations'], [100, 90]);
        foreach ($data['reservations_per_month'] as $idx => $r) {
            $pdf->TableRow([$r['label'], $r['total']], [100, 90], $idx % 2 === 1);
        }
        if (empty($data['reservations_per_month'])) { $pdf->TableEmpty(190); }
        $pdf->Ln(4);

        $pdf->SectionTitle('4. Reservations Per Year');
        $pdf->TableHeader(['Year', 'Total Reservations'], [100, 90]);
        foreach ($data['reservations_per_year'] as $idx => $r) {
            $pdf->TableRow([$r['period'], $r['total']], [100, 90], $idx % 2 === 1);
        }
        if (empty($data['reservations_per_year'])) { $pdf->TableEmpty(190); }
        $pdf->Ln(4);

        $pdf->SectionTitle('5. Most Reserved Days of the Week');
        $pdf->TableHeader(['Day', 'Total Reservations'], [100, 90]);
        foreach ($data['most_reserved_days'] as $idx => $r) {
            $pdf->TableRow([$r['day_name'], $r['total']], [100, 90], $idx % 2 === 1);
        }
        if (empty($data['most_reserved_days'])) { $pdf->TableEmpty(190); }
        $pdf->Ln(4);

        $pdf->SectionTitle('6. Most Reserved Months');
        $pdf->TableHeader(['Month', 'Total Reservations'], [100, 90]);
        foreach ($data['most_reserved_months'] as $idx => $r) {
            $pdf->TableRow([$r['month_name'], $r['total']], [100, 90], $idx % 2 === 1);
        }
        if (empty($data['most_reserved_months'])) { $pdf->TableEmpty(190); }
        $pdf->Ln(4);

        $pdf->SectionTitle('7. Returning Customers');
        $pdf->TableHeader(['Username', 'Email', 'Completed Reservations'], [55, 95, 40]);
        foreach ($data['returning_customers'] as $idx => $r) {
            $pdf->TableRow([$r['username'], $r['email'], $r['reservation_count']], [55, 95, 40], $idx % 2 === 1);
        }
        if (empty($data['returning_customers'])) { $pdf->TableEmpty(190); }
        $pdf->Ln(4);

        $pdf->SectionTitle('8. Most Reserved Services (Top 10)');
        $pdf->TableHeader(['Service Name', 'Category', 'Bookings', 'Revenue (PHP)'], [70, 40, 30, 50]);
        foreach ($data['most_reserved_services'] as $idx => $r) {
            $pdf->TableRow([
                $r['service_name'],
                ucfirst($r['category'] ?? '-'),
                $r['booking_count'],
                number_format((float)$r['total_revenue'], 2),
            ], [70, 40, 30, 50], $idx % 2 === 1);
        }
        if (empty($data['most_reserved_services'])) { $pdf->TableEmpty(190); }
        $pdf->Ln(4);

        $pdf->SectionTitle('9. Revenue Per Day (Last 30 Days)');
        $pdf->TableHeader(['Date', 'Total Revenue (PHP)', 'Transactions'], [70, 80, 40]);
        foreach ($data['revenue_per_day'] as $idx => $r) {
            $pdf->TableRow([$r['period'], number_format((float)$r['total_revenue'], 2), $r['transaction_count']], [70, 80, 40], $idx % 2 === 1);
        }
        if (empty($data['revenue_per_day'])) { $pdf->TableEmpty(190); }
        $pdf->Ln(4);

        $pdf->SectionTitle('10. Revenue Per Month');
        $pdf->TableHeader(['Month', 'Total Revenue (PHP)', 'Transactions'], [70, 80, 40]);
        foreach ($data['revenue_per_month'] as $idx => $r) {
            $pdf->TableRow([$r['label'], number_format((float)$r['total_revenue'], 2), $r['transaction_count']], [70, 80, 40], $idx % 2 === 1);
        }
        if (empty($data['revenue_per_month'])) { $pdf->TableEmpty(190); }
        $pdf->Ln(4);

        $pdf->SectionTitle('11. Revenue Per Year');
        $pdf->TableHeader(['Year', 'Total Revenue (PHP)', 'Transactions'], [70, 80, 40]);
        foreach ($data['revenue_per_year'] as $idx => $r) {
            $pdf->TableRow([$r['period'], number_format((float)$r['total_revenue'], 2), $r['transaction_count']], [70, 80, 40], $idx % 2 === 1);
        }
        if (empty($data['revenue_per_year'])) { $pdf->TableEmpty(190); }
        $pdf->Ln(4);

        $filename = 'HFABS_Analytics_' . str_replace(' ', '_', $this->branch_name) . '_' . date('Y-m-d') . '.pdf';
        $pdf->Output('D', $filename);
        exit;
    }
}