<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ItemModel;
use App\Models\AreaModel;
use App\Models\BuildingModel;
use App\Models\FindingsTypeModel; // ADDED: FindingsTypeModel is now required

class ItemController extends BaseController
{
    public function getItems()
    {
        $request = $this->request;
        $itemModel = new ItemModel();

        $itemModel->select('item.item_id, item.item_name, area.area_name, building.building_name, item.created_at, item.updated_at')
                  ->join('area', 'area.area_id = item.area_id')
                  ->join('building', 'building.building_id = area.building_id');

        $searchableColumns = ['item_name', 'area.area_name', 'building.building_name'];

        $limit = (int) ($request->getPost('length') ?? 10);
        $start = (int) ($request->getPost('start') ?? 0);
        $order = $request->getPost('order')[0] ?? ['column' => 0, 'dir' => 'asc'];
        $searchValue = $request->getPost('search')['value'] ?? '';

        $orderColumn = ['item_id', 'item_name', 'area_name', 'building_name', 'created_at', 'updated_at'][$order['column']] ?? 'item_name';
        $totalRecords = $itemModel->countAllResults(false);

        if (!empty($searchValue)) {
            $itemModel->groupStart();
            foreach ($searchableColumns as $col) {
                $itemModel->orLike($col, $searchValue);
            }
            $itemModel->groupEnd();
        }

        $totalRecordsWithFilter = $itemModel->countAllResults(false);
        $itemModel->orderBy($orderColumn, $order['dir']);
        $records = $itemModel->findAll($limit, $start);

        $data = [];
        foreach ($records as $row) {
            $data[] = [
                "item_id"        => $row['item_id'],
                "item_name"      => htmlspecialchars($row['item_name']),
                "area_name"      => htmlspecialchars($row['area_name']),
                "building_name"  => htmlspecialchars($row['building_name']),
                "created_at"     => $row['created_at'],
                "updated_at"     => $row['updated_at'],
                "actions"        => '<div class="btn-group" role="group"><button class="btn btn-sm btn-info" onclick="editItem(' . $row['item_id'] . ')">Update</button><button class="btn btn-sm btn-danger" onclick="deleteItem(' . $row['item_id'] . ')">Delete</button></div>'
            ];
        }

        return $this->response->setJSON([
            "draw"            => (int) $request->getPost('draw'),
            "recordsTotal"    => $totalRecords,
            "recordsFiltered" => $totalRecordsWithFilter,
            "data"            => $data,
        ]);
    }

    public function addItem()
    {
        $this->response->setContentType('application/json');
        $itemModel = new ItemModel();
        $findingsTypeModel = new FindingsTypeModel();
        
        $itemsData = $this->request->getPost('items');
        $areaId = trim($this->request->getPost('area_id'));

        if (empty($itemsData) || !is_array($itemsData) || empty($areaId)) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Area and at least one item are required.']);
        }

        $areaModel = new AreaModel();
        $area = $areaModel->find($areaId);
        if (!$area) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Selected area not found.']);
        }
        $buildingId = $area['building_id'];

        $db = \Config\Database::connect();
        $db->transStart();

        foreach ($itemsData as $item) {
            $trimmedItemName = trim($item['name']);
            if (!empty($trimmedItemName)) {
                // Insert the item
                $itemToInsert = [
                    'item_name'   => $trimmedItemName,
                    'control'     => $trimmedItemName . time(),
                    'area_id'     => $areaId,
                    'building_id' => $buildingId
                ];
                $itemModel->insert($itemToInsert);
                $newItemId = $itemModel->getInsertID();

                // Insert its findings
                if (!empty($item['findings']) && is_array($item['findings'])) {
                    foreach ($item['findings'] as $findingName) {
                        $trimmedFindingName = trim($findingName);
                        if (!empty($trimmedFindingName)) {
                            $findingToInsert = [
                                'item_id'       => $newItemId,
                                'findings_name' => $trimmedFindingName,
                            ];
                            $findingsTypeModel->insert($findingToInsert);
                        }
                    }
                }
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
             return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Failed to add items and findings due to a database error.']);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Items and findings added successfully!']);
    }
    
    // UPDATED: Now gets the item and its findings for editing
    public function getItemDetails($id = null)
    {
        $itemModel = new ItemModel();
        $findingsTypeModel = new FindingsTypeModel();

        $item = $itemModel
            ->select('item.*, area.building_id')
            ->join('area', 'area.area_id = item.area_id', 'left')
            ->find($id);

        if ($item) {
            $findings = $findingsTypeModel->where('item_id', $id)->findAll();
            $item['findings'] = $findings;
            return $this->response->setJSON(['status' => 'success', 'data' => $item]);
        }
        return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Item not found.']);
    }

    // UPDATED: Now updates an item and replaces its findings
    public function updateItem()
    {
        $itemModel = new ItemModel();
        $findingsTypeModel = new FindingsTypeModel();

        $id = $this->request->getPost('item_id');
        $itemsData = $this->request->getPost('items');
        $areaId = trim($this->request->getPost('area_id'));

        // In update mode, we only expect one item
        if (empty($id) || empty($itemsData[0]['name']) || empty($areaId)) {
             return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'All fields are required for update.']);
        }
        $itemName = trim($itemsData[0]['name']);
        
        $areaModel = new AreaModel();
        $area = $areaModel->find($areaId);
        if (!$area) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Selected area not found.']);
        }
        $buildingId = $area['building_id'];

        $db = \Config\Database::connect();
        $db->transStart();
        
        // 1. Update the item itself
        $itemData = [
            'item_name'   => $itemName,
            'area_id'     => $areaId,
            'building_id' => $buildingId,
        ];
        $itemModel->update($id, $itemData);

        // 2. Delete all old findings for this item
        $findingsTypeModel->where('item_id', $id)->delete();

        // 3. Insert the new list of findings
        if (!empty($itemsData[0]['findings']) && is_array($itemsData[0]['findings'])) {
            foreach ($itemsData[0]['findings'] as $findingName) {
                $trimmedFindingName = trim($findingName);
                if (!empty($trimmedFindingName)) {
                    $findingToInsert = [
                        'item_id'       => $id,
                        'findings_name' => $trimmedFindingName,
                    ];
                    $findingsTypeModel->insert($findingToInsert);
                }
            }
        }

        $db->transComplete();
        
        if ($db->transStatus() === false) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Failed to update item due to a database error.']);
        }
        
        return $this->response->setJSON(['status' => 'success', 'message' => 'Item and findings updated successfully!']);
    }

    public function deleteItem($id = null)
    {
        $itemModel = new ItemModel();
        $findingsTypeModel = new FindingsTypeModel();
        
        if ($itemModel->find($id)) {
            $db = \Config\Database::connect();
            $db->transStart();
            // Delete findings first, then the item
            $findingsTypeModel->where('item_id', $id)->delete();
            $itemModel->delete($id);
            $db->transComplete();

            if ($db->transStatus() === false) {
                 return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Failed to delete item due to a database error.']);
            }
            return $this->response->setJSON(['status' => 'success', 'message' => 'Item and its findings deleted successfully!']);
        }
        
        return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Item not found or already deleted.']);
    }

    public function getAreasByBuilding($buildingId)
    {
        $areaModel = new AreaModel();
        $areas = $areaModel->where('building_id', $buildingId)->findAll();
        return $this->response->setJSON(['status' => 'success', 'data' => $areas]);
    }
}

