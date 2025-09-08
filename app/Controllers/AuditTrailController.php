<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\AuditTrailModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AuditTrailController extends BaseController
{
    public function getTrails()
    {
        $request = $this->request;
        $AuditTrailModel = new AuditTrailModel();

        $searchableColumns = ['id', 'name', 'action', 'ip_address'];

        $limit = (int) ($request->getPost('length') ?? 10);
        $start = (int) ($request->getPost('start') ?? 0);
        $order = $request->getPost('order')[0] ?? ['column' => 0, 'dir' => 'asc'];
        $searchValue = $request->getPost('search')['value'] ?? '';

        $orderColumnMap = ['id', 'name', 'action', 'ip_address'];
        $orderColumn = $orderColumnMap[$order['column']] ?? 'id';

        $totalRecords = $AuditTrailModel->countAllResults(false);

        if (!empty($searchValue)) {
            $AuditTrailModel->groupStart();
            foreach ($searchableColumns as $col) {
                $AuditTrailModel->orLike($col, $searchValue);
            }
            $AuditTrailModel->groupEnd();
        }

        $totalRecordsWithFilter = $AuditTrailModel->countAllResults(false);

        $AuditTrailModel->orderBy($orderColumn, $order['dir']);
        $records = $AuditTrailModel->findAll($limit, $start);

        $data = [];
        foreach ($records as $row) {
            $data[] = [
                "id"         => $row['id'],
                "name"       => htmlspecialchars($row['name']),
                "action"     => htmlspecialchars($row['action']),
                "ip_address" => htmlspecialchars($row['ip_address']),
                "created_at" => htmlspecialchars($row['created_at']),
                "updated_at" => htmlspecialchars($row['updated_at']),
            ];
        }

        return $this->response->setJSON([
            "draw"            => (int) $request->getPost('draw'),
            "recordsTotal"    => $totalRecords,
            "recordsFiltered" => $totalRecordsWithFilter,
            "data"            => $data,
        ]);
    }

    public function downloadAuditTrail()
    {
        $db = \Config\Database::connect();
        $query = $db->table('audit_trail')->get();
        $data = $query->getResultArray();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // column headers
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Name');
        $sheet->setCellValue('C1', 'Action');
        $sheet->setCellValue('D1', 'IP_Address');
        $sheet->setCellValue('E1', 'Created At');

        $row = 2;
        foreach($data as $record){
            $sheet->setCellValue('A' . $row, $record['id']);
            $sheet->setCellValue('B' . $row, $record['name']);
            $sheet->setCellValue('C' . $row, $record['action']);
            $sheet->setCellValue('D' . $row, $record['ip_address']);
            $sheet->setCellValue('E' . $row, $record['created_at']);
        }

        // http headers
        $filename = 'audit_trail_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        // Output the file
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}