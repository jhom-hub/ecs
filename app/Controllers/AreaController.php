<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AreaModel;
use App\Models\BuildingModel;
use App\Models\ItemModel;

class AreaController extends BaseController
{
    public function getAreas()
    {
        $request = $this->request;
        $areaModel = new AreaModel();

        $areaModel->select('area.area_id, area.area_name, building.building_name, area.created_at, area.updated_at')
                  ->join('building', 'building.building_id = area.building_id');

        $searchableColumns = ['area_name', 'building.building_name'];

        $limit = (int) ($request->getPost('length') ?? 10);
        $start = (int) ($request->getPost('start') ?? 0);
        $order = $request->getPost('order')[0] ?? ['column' => 0, 'dir' => 'asc'];
        $searchValue = $request->getPost('search')['value'] ?? '';

        $orderColumn = ['area_id', 'area_name', 'building_name', 'created_at', 'updated_at'][$order['column']] ?? 'area_name';
        $totalRecords = $areaModel->countAllResults(false);

        if (!empty($searchValue)) {
            $areaModel->groupStart();
            foreach ($searchableColumns as $col) {
                $areaModel->orLike($col, $searchValue);
            }
            $areaModel->groupEnd();
        }

        $totalRecordsWithFilter = $areaModel->countAllResults(false);
        $areaModel->orderBy($orderColumn, $order['dir']);
        $records = $areaModel->findAll($limit, $start);

        $data = [];
        foreach ($records as $row) {
            $data[] = [
                "area_id"        => $row['area_id'],
                "area_name"      => htmlspecialchars($row['area_name']),
                "building_name"  => htmlspecialchars($row['building_name']),
                "created_at"     => $row['created_at'],
                "updated_at"     => $row['updated_at'],
                "actions"        => '<div class="btn-group" role="group"><button class="btn btn-sm btn-info" onclick="editArea(' . $row['area_id'] . ')">Update</button><button class="btn btn-sm btn-danger" onclick="deleteArea(' . $row['area_id'] . ')">Delete</button></div>'
            ];
        }

        return $this->response->setJSON([
            "draw"            => (int) $request->getPost('draw'),
            "recordsTotal"    => $totalRecords,
            "recordsFiltered" => $totalRecordsWithFilter,
            "data"            => $data,
        ]);
    }

    public function addArea()
    {
        $this->response->setContentType('application/json');
        $areaModel = new AreaModel();
        $itemModel = new ItemModel();
        
        $areaName = trim($this->request->getPost('area_name'));
        $buildingId = trim($this->request->getPost('building_id'));
        $itemNames = $this->request->getPost('item_name') ?? [];

        if (empty($areaName) || empty($buildingId)) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Area Name and Building are required.']);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $areaData = [
            'area_name'    => $areaName,
            'area_control' => $areaName .'-'. time(),
            'building_id'  => $buildingId,
            'x_coords'     => $this->request->getPost('x_coords') ?? null,
            'y_coords'     => $this->request->getPost('y_coords') ?? null,
        ];
        $areaModel->insert($areaData);
        $areaId = $areaModel->getInsertID();

        if (!empty($itemNames)) {
            for ($i = 0; $i < count($itemNames); $i++) {
                $trimmedItemName = trim($itemNames[$i]);
                if (!empty($trimmedItemName)) {
                    $itemData = [
                        'area_id'     => $areaId,
                        'building_id' => $buildingId,
                        'item_name'   => $trimmedItemName,
                        'control'     => $trimmedItemName . '_' . time(), // AUTOMATIC CONTROL
                    ];
                    $itemModel->insert($itemData);
                }
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Failed to add area due to a database error.']);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Area and its items added successfully!']);
    }

    public function getAreaDetails($id = null)
    {
        $areaModel = new AreaModel();
        $itemModel = new ItemModel();

        $area = $areaModel->find($id);
        if ($area) {
            $items = $itemModel->where('area_id', $id)->findAll();
            $area['items'] = $items;
            return $this->response->setJSON(['status' => 'success', 'data' => $area]);
        }
        return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Area not found.']);
    }

    public function updateArea()
    {
        $this->response->setContentType('application/json');
        $areaModel = new AreaModel();
        $itemModel = new ItemModel();
        
        $areaId = $this->request->getPost('area_id');
        $areaName = trim($this->request->getPost('area_name'));
        $buildingId = trim($this->request->getPost('building_id'));
        $itemNames = $this->request->getPost('item_name') ?? [];

        if (empty($areaId) || empty($areaName) || empty($buildingId)) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Area ID, Name, and Building are required.']);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $areaData = [
            'area_name'   => $areaName,
            'building_id' => $buildingId,
            'x_coords'    => $this->request->getPost('x_coords') ?? null,
            'y_coords'    => $this->request->getPost('y_coords') ?? null,
        ];
        $areaModel->update($areaId, $areaData);

        $itemModel->where('area_id', $areaId)->delete();
        
        if (!empty($itemNames)) {
            for ($i = 0; $i < count($itemNames); $i++) {
                $trimmedItemName = trim($itemNames[$i]);
                if (!empty($trimmedItemName)) {
                    $itemData = [
                        'area_id'     => $areaId,
                        'building_id' => $buildingId,
                        'item_name'   => $trimmedItemName,
                        'control'     => $trimmedItemName . '_' . time(), // AUTOMATIC CONTROL
                    ];
                    $itemModel->insert($itemData);
                }
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Failed to update area due to a database error.']);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Area and its items updated successfully!']);
    }

    public function deleteArea($id = null)
    {
        $areaModel = new AreaModel();
        $itemModel = new ItemModel();
        
        if ($areaModel->find($id)) {
            $db = \Config\Database::connect();
            $db->transStart();

            $itemModel->where('area_id', $id)->delete();
            $areaModel->delete($id);

            $db->transComplete();

            if ($db->transStatus() === false) {
                 return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Failed to delete area due to a database error.']);
            }
            return $this->response->setJSON(['status' => 'success', 'message' => 'Area and its items deleted successfully!']);
        }
        
        return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Area not found or already deleted.']);
    }

    public function getBuildingsForDropdown()
    {
        $buildingModel = new BuildingModel();
        $buildings = $buildingModel->findAll();
        return $this->response->setJSON(['status' => 'success', 'data' => $buildings]);
    }
}

