<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class SummaryDataController extends BaseController
{
    /**
     * Provides server-side data for the summary DataTable.
     */
    public function getSummaryData()
    {
        $request = $this->request;
        $db = \Config\Database::connect();
        
        $builder = $db->table('checksheet_data as cd');

        // Main SELECT statement now includes remarks for display
        $builder->select('
            cd.data_id, cd.status, cd.remarks, cd.submitted_date,
            b.building_name,
            a.area_name,
            i.item_name,
            ft.findings_name,
            dep.department_name,
            d_dri.fullname as dri_name,
            d_auditor.fullname as auditor_name
        ');

        $builder->join('building b', 'cd.building_id = b.building_id', 'left');
        $builder->join('area a', 'cd.area_id = a.area_id', 'left');
        $builder->join('item i', 'cd.item_id = i.item_id', 'left');
        $builder->join('findings_type ft', 'cd.findings_id = ft.findings_id', 'left');
        $builder->join('dri d_dri', 'cd.dri_id = d_dri.dri_id', 'left');
        $builder->join('checksheet_info ci', 'cd.checksheet_id = ci.checksheet_id', 'left');
        $builder->join('dri d_auditor', 'ci.dri_id = d_auditor.dri_id', 'left');
        $builder->join('department dep', 'd_dri.department_id = dep.department_id', 'left');
        
        if ($buildingId = $request->getPost('building_id')) {
            $builder->where('cd.building_id', $buildingId);
        }
        if ($areaId = $request->getPost('area_id')) {
            $builder->where('cd.area_id', $areaId);
        }
        if ($departmentId = $request->getPost('department_id')) {
            $builder->where('d_dri.department_id', $departmentId);
        }
        if ($driId = $request->getPost('dri_id')) {
            $builder->where('cd.dri_id', $driId);
        }
        if ($auditorId = $request->getPost('auditor_id')) {
            $builder->where('ci.dri_id', $auditorId);
        }
        if ($findingsId = $request->getPost('findings_id')) { // New filter for findings
            $builder->where('cd.findings_id', $findingsId);
        }
        if (($status = $request->getPost('status')) !== null && $status !== '') {
            $builder->where('cd.status', $status);
        }
        if ($startDate = $request->getPost('start_date')) {
            $builder->where('cd.submitted_date >=', $startDate);
        }
        if ($endDate = $request->getPost('end_date')) {
            $builder->where('cd.submitted_date <=', $endDate . ' 23:59:59');
        }

        $limit = (int) $request->getPost('length') ?? 10;
        $start = (int) $request->getPost('start') ?? 0;
        $order = $request->getPost('order')[0] ?? ['column' => 0, 'dir' => 'asc'];
        $searchValue = $request->getPost('search')['value'] ?? '';

        $totalRecords = $builder->countAllResults(false);

        // --- Search ---
        if ($searchValue) {
            $builder->groupStart();
            $builder->like('b.building_name', $searchValue);
            $builder->orLike('a.area_name', $searchValue);
            $builder->orLike('i.item_name', $searchValue);
            $builder->orLike('d_dri.fullname', $searchValue);
            $builder->orLike('d_auditor.fullname', $searchValue);
            $builder->orLike('ft.findings_name', $searchValue);
            $builder->orLike('cd.remarks', $searchValue); // Also search in remarks
            $builder->orLike('dep.department_name', $searchValue);
            $builder->groupEnd();
        }

        $totalRecordsWithFilter = $builder->countAllResults(false);

        // --- Order and Limit ---
        $orderableColumns = [
            'data_id', 'building_name', 'area_name', 'item_name', 
            'dri_name', 'auditor_name', 'department_name', 
            'findings_name', 'status', 'submitted_date'
        ];
        $orderColumn = $orderableColumns[$order['column']] ?? 'data_id';
        $builder->orderBy($orderColumn, $order['dir']);
        $builder->limit($limit, $start);

        $records = $builder->get()->getResultArray();

        $data = [];
        foreach ($records as $row) {
            // Combine Finding and Remarks into a single, formatted field
            $finding_details = '<b>' . esc($row['findings_name']) . '</b>';
            if (!empty($row['remarks'])) {
                $finding_details .= '<br><small>' . esc($row['remarks']) . '</small>';
            }
            $row['finding_details'] = $finding_details;

            // Map status code to text with badges
            switch ($row['status']) {
                case '0':
                    $row['status'] = '<span class="badge bg-danger">NG</span>';
                    break;
                case '1':
                    $row['status'] = '<span class="badge bg-warning text-dark">For Verification</span>';
                    break;
                case '2':
                    $row['status'] = '<span class="badge bg-success">OK</span>';
                    break;
                case '3':
                    $row['status'] = '<span class="badge bg-secondary">HOLD</span>';
                    break;
                default:
                    $row['status'] = '<span class="badge bg-light text-dark">Unknown</span>';
            }
            $data[] = $row;
        }

        return $this->response->setJSON([
            "draw"            => (int) $request->getPost('draw'),
            "recordsTotal"    => $totalRecords,
            "recordsFiltered" => $totalRecordsWithFilter,
            "data"            => $data,
        ]);
    }
}