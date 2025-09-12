<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\FindingsTypeModel;
use App\Models\ItemModel; // Added for the new function

class FindingsTypeController extends BaseController
{
    public function getFindingsTypes()
    {
        $request = $this->request;
        $findingsTypeModel = new FindingsTypeModel();

        // Base query with joins
        $findingsTypeModel->select('findings_type.findings_id, findings_type.findings_name, item.item_name, area.area_name, building.building_name, findings_type.created_at')
                         ->join('item', 'item.item_id = findings_type.item_id')
                         ->join('area', 'area.area_id = item.area_id')
                         ->join('building', 'building.building_id = area.building_id');

        // Searchable columns
        $searchableColumns = ['findings_name', 'item.item_name', 'area.area_name', 'building.building_name'];

        $limit = (int) $request->getPost('length') ?? 10;
        $start = (int) $request->getPost('start') ?? 0;
        $order = $request->getPost('order')[0] ?? ['column' => 0, 'dir' => 'asc'];
        $searchValue = $request->getPost('search')['value'] ?? '';

        // Validate order column index
        $orderColumn = ['findings_id', 'findings_name', 'item_name', 'area_name', 'building_name', 'created_at'][$order['column']] ?? 'findings_name';

        // Total records
        $totalRecords = $findingsTypeModel->countAllResults(false);

        // Apply search filter
        if (!empty($searchValue)) {
            $findingsTypeModel->groupStart();
            foreach ($searchableColumns as $col) {
                $findingsTypeModel->orLike($col, $searchValue);
            }
            $findingsTypeModel->groupEnd();
        }

        // Total filtered records
        $totalRecordsWithFilter = $findingsTypeModel->countAllResults(false);

        // Fetch records
        $findingsTypeModel->orderBy($orderColumn, $order['dir']);
        $records = $findingsTypeModel->findAll($limit, $start);

        $data = [];
        foreach ($records as $row) {
            $data[] = [
                "findings_id"   => $row['findings_id'],
                "findings_name" => htmlspecialchars($row['findings_name']),
                "item_name"     => htmlspecialchars($row['item_name']),
                "area_name"     => htmlspecialchars($row['area_name']),
                "building_name" => htmlspecialchars($row['building_name']),
                "created_at"    => $row['created_at'],
                "actions"       => '
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-info" onclick="editFinding(' . $row['findings_id'] . ')">Update</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteFinding(' . $row['findings_id'] . ')">Delete</button>
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

    public function addFindingsType()
    {
        $this->response->setContentType('application/json');
        $findingsTypeModel = new FindingsTypeModel();
        
        $findingsName = trim($this->request->getPost('findings_name'));
        $itemId = trim($this->request->getPost('item_id'));

        if (empty($findingsName) || empty($itemId)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'error',
                'message' => 'All fields are required.'
            ]);
        }
        
        if ($findingsTypeModel->where('findings_name', $findingsName)->where('item_id', $itemId)->first()) {
            return $this->response->setStatusCode(409)->setJSON([
                'status' => 'error',
                'message' => 'This Finding Type is already registered for the selected Item.'
            ]);
        }

        $dataToInsert = [
            'findings_name' => $findingsName,
            'item_id' => $itemId
        ];

        if ($findingsTypeModel->insert($dataToInsert)) {
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Finding Type added successfully!'
            ]);
        }

        return $this->response->setStatusCode(400)->setJSON([
            'status' => 'error',
            'message' => 'Failed to add Finding Type.',
            'errors' => $findingsTypeModel->errors() ?? []
        ]);
    }

    // **FIX:** Renamed function to match the AJAX call in the view file.
    public function details($id = null)
    {
        $findingsTypeModel = new FindingsTypeModel();
        $finding = $findingsTypeModel
            ->select('findings_type.*, item.area_id, area.building_id')
            ->join('item', 'item.item_id = findings_type.item_id')
            ->join('area', 'area.area_id = item.area_id')
            ->find($id);

        if ($finding) {
            return $this->response->setJSON(['status' => 'success', 'data' => $finding]);
        }
        return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Finding Type not found.']);
    }

    public function updateFindingsType()
    {
        $findingsTypeModel = new FindingsTypeModel();
        $id = $this->request->getPost('findings_id');

        $data = [
            'findings_name' => trim($this->request->getPost('findings_name')),
            'item_id' => trim($this->request->getPost('item_id')),
        ];

        if ($findingsTypeModel->update($id, $data)) {
            return $this->response->setJSON(['status' => 'success', 'message' => 'Finding Type updated successfully!']);
        }
        
        return $this->response->setStatusCode(400)->setJSON([
            'status' => 'error', 
            'message' => 'Failed to update Finding Type.',
            'errors' => $findingsTypeModel->errors()
        ]);
    }

    public function deleteFindingsType($id = null)
    {
        $findingsTypeModel = new FindingsTypeModel();
        
        if ($findingsTypeModel->find($id)) {
            $findingsTypeModel->delete($id);
            return $this->response->setJSON(['status' => 'success', 'message' => 'Finding Type deleted successfully!']);
        }
        
        return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Finding Type not found or already deleted.']);
    }

    // **FIX:** Added the missing function to get items for the dropdown.
    public function getItemsByArea($areaId = null)
    {
        if ($areaId === null) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Area ID is required.']);
        }

        $itemModel = new ItemModel();
        $items = $itemModel->where('area_id', $areaId)->orderBy('item_name', 'ASC')->findAll();

        return $this->response->setJSON(['status' => 'success', 'data' => $items]);
    }
}