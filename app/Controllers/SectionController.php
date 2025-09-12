<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\SectionModel;
use App\Models\DivisionModel;
use App\Models\DepartmentModel;

class SectionController extends BaseController
{
    public function getSections()
    {
        $request = $this->request;
        $sectionModel = new SectionModel();

        // Base query with joins
        $sectionModel->select('section.section_id, section.section_name, division.division_name, department.department_name, section.created_at, section.updated_at')
                     ->join('division', 'division.division_id = section.division_id')
                     ->join('department', 'department.department_id = division.department_id');

        // Searchable columns
        $searchableColumns = ['section_name', 'division.division_name', 'department.department_name'];

        $limit = (int) $request->getPost('length') ?? 10;
        $start = (int) $request->getPost('start') ?? 0;
        $order = $request->getPost('order')[0] ?? ['column' => 0, 'dir' => 'asc'];
        $searchValue = $request->getPost('search')['value'] ?? '';

        // Validate order column index
        $orderColumn = ['section_id', 'section_name', 'division_name', 'department_name', 'created_at', 'updated_at'][$order['column']] ?? 'section_name';

        // Total records
        $totalRecords = $sectionModel->countAllResults(false);

        // Apply search filter
        if (!empty($searchValue)) {
            $sectionModel->groupStart();
            foreach ($searchableColumns as $col) {
                $sectionModel->orLike($col, $searchValue);
            }
            $sectionModel->groupEnd();
        }

        // Total filtered records
        $totalRecordsWithFilter = $sectionModel->countAllResults(false);

        // Fetch records
        $sectionModel->orderBy($orderColumn, $order['dir']);
        $records = $sectionModel->findAll($limit, $start);

        $data = [];
        foreach ($records as $row) {
            $data[] = [
                "section_id"      => $row['section_id'],
                "section_name"    => htmlspecialchars($row['section_name']),
                "division_name"   => htmlspecialchars($row['division_name']),
                "department_name" => htmlspecialchars($row['department_name']),
                "created_at"      => $row['created_at'],
                "updated_at"      => $row['updated_at'],
                "actions"         => '
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-info" onclick="editSection(' . $row['section_id'] . ')">Update</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteSection(' . $row['section_id'] . ')">Delete</button>
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

    public function addSection()
    {
        $this->response->setContentType('application/json');
        $sectionModel = new SectionModel();
        
        $sectionName = trim($this->request->getPost('section_name'));
        $divisionId = trim($this->request->getPost('division_id'));

        if (empty($sectionName) || empty($divisionId)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'error',
                'message' => 'All fields are required.'
            ]);
        }
        
        if ($sectionModel->where('section_name', $sectionName)->where('division_id', $divisionId)->first()) {
            return $this->response->setStatusCode(409)->setJSON([
                'status' => 'error',
                'message' => 'This Section is already registered under the selected Division.'
            ]);
        }

        $dataToInsert = [
            'section_name' => $sectionName,
            'division_id' => $divisionId
        ];

        if ($sectionModel->insert($dataToInsert)) {
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Section added successfully!'
            ]);
        }

        return $this->response->setStatusCode(400)->setJSON([
            'status' => 'error',
            'message' => 'Failed to add section.',
            'errors' => $sectionModel->errors() ?? []
        ]);
    }

    public function getSectionDetails($id = null)
    {
        $sectionModel = new SectionModel();
        $section = $sectionModel
            ->select('section.*, division.department_id')
            ->join('division', 'division.division_id = section.division_id')
            ->find($id);

        if ($section) {
            return $this->response->setJSON(['status' => 'success', 'data' => $section]);
        }
        return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Section not found.']);
    }

    public function updateSection()
    {
        $sectionModel = new SectionModel();
        $id = $this->request->getPost('section_id');

        $data = [
            'section_name' => trim($this->request->getPost('section_name')),
            'division_id' => trim($this->request->getPost('division_id')),
        ];

        if ($sectionModel->update($id, $data)) {
            return $this->response->setJSON(['status' => 'success', 'message' => 'Section updated successfully!']);
        }
        
        return $this->response->setStatusCode(400)->setJSON([
            'status' => 'error', 
            'message' => 'Failed to update section.',
            'errors' => $sectionModel->errors()
        ]);
    }

    public function deleteSection($id = null)
    {
        $sectionModel = new SectionModel();
        
        if ($sectionModel->find($id)) {
            $sectionModel->delete($id);
            return $this->response->setJSON(['status' => 'success', 'message' => 'Section deleted successfully!']);
        }
        
        return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Section not found or already deleted.']);
    }

    // Helper function to get divisions based on department
    public function getDivisionsByDepartment($departmentId)
    {
        $divisionModel = new DivisionModel();
        $divisions = $divisionModel->where('department_id', $departmentId)->findAll();
        return $this->response->setJSON(['status' => 'success', 'data' => $divisions]);
    }
}
