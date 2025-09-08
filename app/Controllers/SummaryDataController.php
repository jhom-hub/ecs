<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class SummaryDataController extends BaseController
{
    /**
     * Provides server-side data for the summary DataTable.
     * The index() function has been removed as it's no longer needed in the SPA.
     */
    public function getSummaryData()
    {
        $request = $this->request;
        $db = \Config\Database::connect();
        $builder = $db->table('checksheet_data as cd');

        // Main SELECT statement with all necessary columns
        $builder->select('
            cd.data_id, cd.status, cd.remarks, cd.submitted_date,
            b.building_name,
            a.area_name,
            i.item_name,
            ft.findings_name,
            d.fullname as dri_name,
            sec.section_name,
            div.division_name,
            dep.department_name
        ');

        // All necessary JOINs
        $builder->join('building b', 'cd.building_id = b.building_id', 'left');
        $builder->join('area a', 'cd.area_id = a.area_id', 'left');
        $builder->join('item i', 'cd.item_id = i.item_id', 'left');
        $builder->join('dri d', 'cd.dri_id = d.dri_id', 'left');
        $builder->join('findings_type ft', 'cd.findings_id = ft.findings_id', 'left');
        $builder->join('section sec', 'd.section_id = sec.section_id', 'left');
        $builder->join('division div', 'd.division_id = div.division_id', 'left');
        $builder->join('department dep', 'd.department_id = dep.department_id', 'left');
        
        // --- Custom Filters ---
        if ($buildingId = $request->getPost('building_id')) {
            $builder->where('cd.building_id', $buildingId);
        }
        if ($areaId = $request->getPost('area_id')) {
            $builder->where('cd.area_id', $areaId);
        }
        if ($departmentId = $request->getPost('department_id')) {
            $builder->where('d.department_id', $departmentId);
        }
        if ($divisionId = $request->getPost('division_id')) {
            $builder->where('d.division_id', $divisionId);
        }
        if ($sectionId = $request->getPost('section_id')) {
            $builder->where('d.section_id', $sectionId);
        }
        if ($driId = $request->getPost('dri_id')) {
            $builder->where('cd.dri_id', $driId);
        }
        if ($status = $request->getPost('status')) {
            $builder->where('cd.status', $status);
        }
        if ($startDate = $request->getPost('start_date')) {
            $builder->where('cd.submitted_date >=', $startDate);
        }
        if ($endDate = $request->getPost('end_date')) {
            $builder->where('cd.submitted_date <=', $endDate . ' 23:59:59');
        }

        // --- DataTable Parameters ---
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
            $builder->orLike('d.fullname', $searchValue);
            $builder->orLike('ft.findings_name', $searchValue);
            $builder->orLike('cd.status', $searchValue);
            $builder->orLike('dep.department_name', $searchValue);
            $builder->groupEnd();
        }

        $totalRecordsWithFilter = $builder->countAllResults(false);

        // --- Order and Limit ---
        $orderableColumns = [
            'data_id', 'building_name', 'area_name', 'item_name', 
            'dri_name', 'department_name', 'division_name', 'section_name', 
            'findings_name', 'status', 'submitted_date'
        ];
        $orderColumn = $orderableColumns[$order['column']] ?? 'data_id';
        $builder->orderBy($orderColumn, $order['dir']);
        $builder->limit($limit, $start);

        $records = $builder->get()->getResultArray();

        $data = [];
        foreach ($records as $row) {
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