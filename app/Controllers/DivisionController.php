<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\DivisionModel;
use App\Models\DepartmentModel;

class DivisionController extends BaseController
{
    public function getDivisions()
    {
        $request = $this->request;
        $divisionModel = new DivisionModel();

        // Base query with join
        $divisionModel->select('division.division_id, division.division_name, department.department_name, division.created_at, division.updated_at')
                      ->join('department', 'department.department_id = division.department_id');

        // Searchable columns
        $searchableColumns = ['division_name', 'department.department_name'];

        $limit = (int) $request->getPost('length') ?? 10;
        $start = (int) $request->getPost('start') ?? 0;
        $order = $request->getPost('order')[0] ?? ['column' => 0, 'dir' => 'asc'];
        $searchValue = $request->getPost('search')['value'] ?? '';

        // Validate order column index
        $orderColumn = ['division_id', 'division_name', 'department_name', 'created_at', 'updated_at'][$order['column']] ?? 'division_name';

        // Total records (before filtering)
        $totalRecords = $divisionModel->countAllResults(false);

        // Apply search filter
        if (!empty($searchValue)) {
            $divisionModel->groupStart();
            foreach ($searchableColumns as $col) {
                $divisionModel->orLike($col, $searchValue);
            }
            $divisionModel->groupEnd();
        }

        // Total filtered records
        $totalRecordsWithFilter = $divisionModel->countAllResults(false);

        // Fetch records
        $divisionModel->orderBy($orderColumn, $order['dir']);
        $records = $divisionModel->findAll($limit, $start);

        $data = [];
        foreach ($records as $row) {
            $data[] = [
                "division_id"     => $row['division_id'],
                "division_name"   => htmlspecialchars($row['division_name']),
                "department_name" => htmlspecialchars($row['department_name']),
                "created_at"      => $row['created_at'],
                "updated_at"      => $row['updated_at'],
                "actions"         => '
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-info" onclick="editDivision(' . $row['division_id'] . ')">Update</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteDivision(' . $row['division_id'] . ')">Delete</button>
                    </div>'
            ];
        }

        return $this->response->setJSON([
            "draw"            => (int) $request->getPost('draw'),
            "recordsTotal"    => $totalRecords,
            "recordsFiltered" => $totalRecordsWithFilter,
            "data"            => $data,
        ]);
    }

    public function addDivision()
    {
        $this->response->setContentType('application/json');
        $divisionModel = new DivisionModel();
        
        $divisionName = trim($this->request->getPost('division_name'));
        $departmentId = trim($this->request->getPost('department_id'));

        if (empty($divisionName) || empty($departmentId)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'error',
                'message' => 'All fields are required.'
            ]);
        }
        
        if ($divisionModel->where('division_name', $divisionName)->where('department_id', $departmentId)->first()) {
            return $this->response->setStatusCode(409)->setJSON([
                'status' => 'error',
                'message' => 'This Division is already registered under the selected Department.'
            ]);
        }

        $dataToInsert = [
            'division_name' => $divisionName,
            'department_id' => $departmentId
        ];

        if ($divisionModel->insert($dataToInsert)) {
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Division added successfully!'
            ]);
        }

        return $this->response->setStatusCode(400)->setJSON([
            'status' => 'error',
            'message' => 'Failed to add division.',
            'errors' => $divisionModel->errors() ?? []
        ]);
    }

    public function getDivisionDetails($id = null)
    {
        $divisionModel = new DivisionModel();
        $division = $divisionModel->find($id);

        if ($division) {
            return $this->response->setJSON(['status' => 'success', 'data' => $division]);
        }
        return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Division not found.']);
    }

    public function updateDivision()
    {
        $divisionModel = new DivisionModel();
        $id = $this->request->getPost('division_id');

        $data = [
            'division_name' => trim($this->request->getPost('division_name')),
            'department_id' => trim($this->request->getPost('department_id')),
        ];

        if ($divisionModel->update($id, $data)) {
            return $this->response->setJSON(['status' => 'success', 'message' => 'Division updated successfully!']);
        }
        
        return $this->response->setStatusCode(400)->setJSON([
            'status' => 'error', 
            'message' => 'Failed to update division.',
            'errors' => $divisionModel->errors()
        ]);
    }

    public function deleteDivision($id = null)
    {
        $divisionModel = new DivisionModel();
        
        if ($divisionModel->find($id)) {
            $divisionModel->delete($id);
            return $this->response->setJSON(['status' => 'success', 'message' => 'Division deleted successfully!']);
        }
        
        return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Division not found or already deleted.']);
    }

    // Helper function to get departments for dropdowns
    public function getDepartmentsForDropdown()
    {
        $departmentModel = new DepartmentModel();
        $departments = $departmentModel->findAll();
        return $this->response->setJSON(['status' => 'success', 'data' => $departments]);
    }
}
