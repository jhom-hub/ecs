<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\DepartmentModel;

class DepartmentController extends BaseController
{
    public function getDepartments()
    {
        $request = $this->request;
        $departmentModel = new DepartmentModel();

        // Searchable columns
        $searchableColumns = ['department_name'];

        $limit = (int) $request->getPost('length') ?? 10;
        $start = (int) $request->getPost('start') ?? 0;
        $order = $request->getPost('order')[0] ?? ['column' => 0, 'dir' => 'asc'];
        $searchValue = $request->getPost('search')['value'] ?? '';

        // Validate order column index
        $orderColumn = ['department_id', 'department_name', 'created_at', 'updated_at'][$order['column']] ?? 'department_name';

        // Total records
        $totalRecords = $departmentModel->countAll();

        // Apply search filter
        if (!empty($searchValue)) {
            $departmentModel->groupStart();
            foreach ($searchableColumns as $col) {
                $departmentModel->orLike($col, $searchValue);
            }
            $departmentModel->groupEnd();
        }

        // Total filtered records
        $totalRecordsWithFilter = $departmentModel->countAllResults(false);

        // Fetch records
        $departmentModel->orderBy($orderColumn, $order['dir']);
        $records = $departmentModel->findAll($limit, $start);

        $data = [];
        foreach ($records as $row) {
            $data[] = [
                "department_id"   => $row['department_id'],
                "department_name" => htmlspecialchars($row['department_name']),
                "created_at"      => $row['created_at'],
                "updated_at"      => $row['updated_at'],
                "actions"         => '
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-info" onclick="editDepartment(' . $row['department_id'] . ')">Update</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteDepartment(' . $row['department_id'] . ')">Delete</button>
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

    public function addDepartment()
    {
        $this->response->setContentType('application/json');
        $departmentModel = new DepartmentModel();
        
        $departmentName = trim($this->request->getPost('department_name'));

        if (empty($departmentName)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'error',
                'message' => 'Department name is required.'
            ]);
        }
        
        if ($departmentModel->where('department_name', $departmentName)->first()) {
            return $this->response->setStatusCode(409)->setJSON([
                'status' => 'error',
                'message' => 'This Department is already registered.'
            ]);
        }

        $dataToInsert = ['department_name' => $departmentName];

        if ($departmentModel->insert($dataToInsert)) {
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Department added successfully!'
            ]);
        }

        return $this->response->setStatusCode(400)->setJSON([
            'status' => 'error',
            'message' => 'Failed to add department.',
            'errors' => $departmentModel->errors() ?? []
        ]);
    }

    public function getDepartmentDetails($id = null)
    {
        $departmentModel = new DepartmentModel();
        $department = $departmentModel->find($id);

        if ($department) {
            return $this->response->setJSON(['status' => 'success', 'data' => $department]);
        }
        return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Department not found.']);
    }

    public function updateDepartment()
    {
        $departmentModel = new DepartmentModel();
        $id = $this->request->getPost('department_id');

        $data = [
            'department_name' => trim($this->request->getPost('department_name')),
        ];

        if ($departmentModel->update($id, $data)) {
            return $this->response->setJSON(['status' => 'success', 'message' => 'Department updated successfully!']);
        }
        
        return $this->response->setStatusCode(400)->setJSON([
            'status' => 'error', 
            'message' => 'Failed to update department.',
            'errors' => $departmentModel->errors()
        ]);
    }

    public function deleteDepartment($id = null)
    {
        $departmentModel = new DepartmentModel();
        
        if ($departmentModel->find($id)) {
            $departmentModel->delete($id);
            return $this->response->setJSON(['status' => 'success', 'message' => 'Department deleted successfully!']);
        }
        
        return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Department not found or already deleted.']);
    }
}
