<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\RequestItemsModel;
use App\Models\AreaModel;

class RequestItemsController extends BaseController
{
    public function getRequestItems()
    {
        $request = $this->request;
        $session = session();
        $userId = $session->get('user_id');

        $requestItemsModel = new RequestItemsModel();

        $searchableColumns = [
            'request_items.item_name',
            'area.area_name',
            'building.building_name',
            'request_items.status',
            'request_items.control'
        ];

        $limit = (int) ($request->getPost('length') ?? 10);
        $start = (int) ($request->getPost('start') ?? 0);
        $order = $request->getPost('order')[0] ?? ['column' => 0, 'dir' => 'asc'];
        $searchValue = $request->getPost('search')['value'] ?? '';

        $orderColumnMap = [
            'request_items.request_id',
            'request_items.item_name',
            'request_items.control',
            'request_items.status',
            'request_items.created_at'
        ];
        $orderColumn = $orderColumnMap[$order['column']] ?? 'request_items.request_id';

        $requestItemsModel
            ->select('request_items.request_id, request_items.item_name, request_items.status, area.area_name, building.building_name, request_items.created_at, request_items.control')
            ->join('building', 'building.building_id = request_items.building_id', 'left')
            ->join('area', 'area.area_id = request_items.area_id', 'left')
            ->where('request_items.requestor_id', $userId);

        $totalRecords = $requestItemsModel->countAllResults(false);

        if (!empty($searchValue)) {
            $requestItemsModel->groupStart();
            foreach ($searchableColumns as $col) {
                $requestItemsModel->orLike($col, $searchValue);
            }
            $requestItemsModel->groupEnd();
        }

        $totalRecordsWithFilter = $requestItemsModel->countAllResults(false);
        $requestItemsModel->orderBy($orderColumn, $order['dir']);
        $records = $requestItemsModel->findAll($limit, $start);

        $data = [];
        foreach ($records as $row) {
            $data[] = [
                "request_id"  => $row['request_id'],
                "item_name"   => htmlspecialchars($row['item_name']),
                "control"     => htmlspecialchars($row['control']),
                "status"      => htmlspecialchars($row['status']),
                "created_at"  => $row['created_at'],
                "actions"     => '
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-danger" onclick="deleteRequest(' . $row['request_id'] . ')">Delete</button>
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

    public function addRequestItem()
    {
        $this->response->setContentType('application/json');
        
        if (empty($this->request->getPost('building_id')) || empty($this->request->getPost('area_id')) || empty(trim($this->request->getPost('item_name')))) {
             return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Building, Area, and Item Name are required.']);
        }
        
        $requestItemsModel = new RequestItemsModel();
        $areaModel = new AreaModel();

        $area_control_query = $areaModel->select('area_control')->where('area_id', $this->request->getPost('area_id'))->first();
        $area_control = $area_control_query['area_control'] ?? null;
        
        $data = [
            'building_id'   => $this->request->getPost('building_id'),
            'area_id'       => $this->request->getPost('area_id'),
            'item_name'     => trim($this->request->getPost('item_name')),
            'control'       => $area_control,
            'description'   => trim($this->request->getPost('description')),
            'status'        => 0 // Default status to Pending
        ];

        if ($requestItemsModel->insert($data)) {
            $newRequestId = $requestItemsModel->getInsertID();
            
            // --- Trigger the external alert script via cURL ---
            $alertUrl = "http://10.216.2.202/ECS_alerts/request_item_alert.php?request_id=" . $newRequestId;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $alertUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            
            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            return $this->response->setJSON(['status' => 'success', 'message' => 'Item Request added and alert sent successfully!']);
        }

        return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Failed to add item request.', 'errors' => $requestItemsModel->errors()]);
    }

    public function getRequestItemDetails($id = null)
    {
        $requestItemsModel = new RequestItemsModel();
        $request = $requestItemsModel->find($id);

        if ($request) {
            return $this->response->setJSON(['status' => 'success', 'data' => $request]);
        }
        return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Request not found.']);
    }

    public function updateRequestItem()
    {
        $requestItemsModel = new RequestItemsModel();
        $id = $this->request->getPost('request_id');
        
        if (empty($id)) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Request ID is missing.']);
        }

        $data = [
            'building_id'   => $this->request->getPost('building_id'),
            'area_id'       => $this->request->getPost('area_id'),
            'item_name'     => trim($this->request->getPost('item_name')),
            'description'   => trim($this->request->getPost('description')),
            'status'        => trim($this->request->getPost('status')),
            'remarks'       => trim($this->request->getPost('remarks')),
        ];

        if ($requestItemsModel->update($id, $data)) {
            return $this->response->setJSON(['status' => 'success', 'message' => 'Item Request updated successfully!']);
        }
        
        return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Failed to update item request.', 'errors' => $requestItemsModel->errors()]);
    }

    public function deleteRequestItem($id = null)
    {
        $requestItemsModel = new RequestItemsModel();
        
        if ($requestItemsModel->find($id)) {
            $requestItemsModel->delete($id);
            return $this->response->setJSON(['status' => 'success', 'message' => 'Item Request deleted successfully!']);
        }
        
        return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Item Request not found or already deleted.']);
    }

    public function getPendingRequests()
    {
        $requestItemsModel = new RequestItemsModel();
        $data = $requestItemsModel
            ->select('request_items.request_id, request_items.description, request_items.control, users.firstname, users.lastname, area.area_name, request_items.item_name')
            ->join('users', 'users.user_id = request_items.requestor_id', 'left')
            ->join('area', 'request_items.area_id = area.area_id', 'left')
            ->where('request_items.status', 0)
            ->orderBy('request_items.created_at', 'DESC')
            ->findAll();

        if ($data) {
            return $this->response->setJSON(['status' => 'success', 'data' => $data]);
        }
        
        return $this->response->setJSON(['status' => 'error', 'message' => 'No pending requests found.']);
    }

    public function updateRequestStatus()
    {
        $this->response->setContentType('application/json');
        $requestItemsModel = new RequestItemsModel();

        $id = $this->request->getPost('request_id');
        $status = $this->request->getPost('status');
        $remarks = $this->request->getPost('remarks');
        $approver = session()->get('fullname') ?? '';
        $user_id  = session()->get('user_id') ?? '';

        if (empty($id) || !in_array($status, ['1', '2'])) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Invalid Request ID or Status provided.']);
        }

        $data = [
            'status' => $status,
            'updated_by' => $approver,
            'approver' => $user_id,
        ];

        if (!empty($remarks)) {
            $data['remarks'] = trim($remarks);
        }

        if ($requestItemsModel->update($id, $data)) {
            $action = ($status == 1) ? 'approved' : 'rejected';
            $alertUrl = "http://10.216.2.202/ECS_alerts/request_item_action_alert.php?request_id=" . $id ."&action=" . $action;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $alertUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

            curl_exec($ch);
            curl_close($ch);

            return $this->response->setJSON(['status' => 'success', 'message' => "Item Request has been successfully {$action} and an alert has been sent!"]);
        }

        return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Failed to update item request status.', 'errors' => $requestItemsModel->errors()]);
    }
}