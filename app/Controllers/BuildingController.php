<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\BuildingModel;

class BuildingController extends BaseController
{
    public function getBuildings()
    {
        $request = $this->request;
        $buildingModel = new BuildingModel();

        // Searchable columns
        $searchableColumns = ['building_name'];

        $limit = (int) $request->getPost('length') ?? 10;
        $start = (int) $request->getPost('start') ?? 0;
        $order = $request->getPost('order')[0] ?? ['column' => 0, 'dir' => 'asc'];
        $searchValue = $request->getPost('search')['value'] ?? '';

        // Validate order column index
        $orderColumn = ['building_id', 'building_name', 'created_at', 'updated_at'][$order['column']] ?? 'building_name';

        // Total records
        $totalRecords = $buildingModel->countAll();

        // Apply search filter
        if (!empty($searchValue)) {
            $buildingModel->groupStart();
            foreach ($searchableColumns as $col) {
                $buildingModel->orLike($col, $searchValue);
            }
            $buildingModel->groupEnd();
        }

        // Total filtered records
        $totalRecordsWithFilter = $buildingModel->countAllResults(false);

        // Fetch records
        $buildingModel->orderBy($orderColumn, $order['dir']);
        $records = $buildingModel->findAll($limit, $start);

        $data = [];
        foreach ($records as $row) {
            $data[] = [
                "building_id"   => $row['building_id'],
                "building_name" => htmlspecialchars($row['building_name']),
                "created_at"    => $row['created_at'],
                "updated_at"    => $row['updated_at'],
                "actions"       => '
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-info" onclick="editBuilding(' . $row['building_id'] . ')">Update</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteBuilding(' . $row['building_id'] . ')">Delete</button>
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

    public function addBuilding()
    {
        $this->response->setContentType('application/json');
        $buildingModel = new BuildingModel();
        
        $buildingName = trim($this->request->getPost('building_name'));

        if (empty($buildingName)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'error',
                'message' => 'Building name is required.'
            ]);
        }
        
        if ($buildingModel->where('building_name', $buildingName)->first()) {
            return $this->response->setStatusCode(409)->setJSON([
                'status' => 'error',
                'message' => 'This Building is already registered.'
            ]);
        }

        $dataToInsert = ['building_name' => $buildingName];

        if ($buildingModel->insert($dataToInsert)) {
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Building added successfully!'
            ]);
        }

        return $this->response->setStatusCode(400)->setJSON([
            'status' => 'error',
            'message' => 'Failed to add building.',
            'errors' => $buildingModel->errors() ?? []
        ]);
    }

    public function getBuildingDetails($id = null)
    {
        $buildingModel = new BuildingModel();
        $building = $buildingModel->find($id);

        if ($building) {
            return $this->response->setJSON(['status' => 'success', 'data' => $building]);
        }
        return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Building not found.']);
    }

    public function updateBuilding()
    {
        $buildingModel = new BuildingModel();
        $id = $this->request->getPost('building_id');

        $data = [
            'building_name' => trim($this->request->getPost('building_name')),
        ];

        if ($buildingModel->update($id, $data)) {
            return $this->response->setJSON(['status' => 'success', 'message' => 'Building updated successfully!']);
        }
        
        return $this->response->setStatusCode(400)->setJSON([
            'status' => 'error', 
            'message' => 'Failed to update building.',
            'errors' => $buildingModel->errors()
        ]);
    }

    public function deleteBuilding($id = null)
    {
        $buildingModel = new BuildingModel();
        
        if ($buildingModel->find($id)) {
            $buildingModel->delete($id);
            return $this->response->setJSON(['status' => 'success', 'message' => 'Building deleted successfully!']);
        }
        
        return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Building not found or already deleted.']);
    }
}
