<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ChecksheetInfoModel;
use App\Models\AuditorModel;

// RENAMED: The class name has been reverted to match the file name.
class ChecksheetDataController extends BaseController
{
    public function getChecksheets()
    {
        $request = $this->request;
        $checksheetModel = new ChecksheetInfoModel();

        $checksheetModel->distinct()
                        ->select('checksheet_info.checksheet_id, building.building_name, area.area_name, auditors.fullname as dri_name, checksheet_info.created_at')
                        ->join('building', 'building.building_id = checksheet_info.building_id')
                        ->join('area', 'area.area_id = checksheet_info.area_id')
                        ->join('auditors', 'auditors.user_id = checksheet_info.dri_id');

        $searchableColumns = ['building.building_name', 'area.area_name', 'auditors.fullname'];

        $limit = (int) ($request->getPost('length') ?? 10);
        $start = (int) ($request->getPost('start') ?? 0);
        $order = $request->getPost('order')[0] ?? ['column' => 0, 'dir' => 'asc'];
        $searchValue = $request->getPost('search')['value'] ?? '';

        $orderableColumns = ['checksheet_id', 'building_name', 'area_name', 'dri_name', 'created_at'];
        $orderColumn = $orderableColumns[$order['column']] ?? 'checksheet_id';

        $totalRecords = $checksheetModel->countAllResults(false);

        if (!empty($searchValue)) {
            $checksheetModel->groupStart();
            foreach ($searchableColumns as $col) {
                $checksheetModel->orLike($col, $searchValue);
            }
            $checksheetModel->groupEnd();
        }

        $totalRecordsWithFilter = $checksheetModel->countAllResults(false);

        $checksheetModel->orderBy($orderColumn, $order['dir']);
        $records = $checksheetModel->findAll($limit, $start);

        $data = [];
        foreach ($records as $row) {
            $data[] = [
                "checksheet_id"   => $row['checksheet_id'],
                "building_name"   => htmlspecialchars($row['building_name']),
                "area_name"       => htmlspecialchars($row['area_name']),
                "dri_name"        => htmlspecialchars($row['dri_name']),
                "created_at"      => date('Y-m-d H:i:s', strtotime($row['created_at'])),
                "actions"         => '<div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-info" onclick="editChecksheet(' . $row['checksheet_id'] . ')">Update</button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteChecksheet(' . $row['checksheet_id'] . ')">Delete</button>
                                    </div>'
            ];
        }

        return $this->response->setJSON([
            "draw"            => (int) $request->getPost('draw'),
            "recordsTotal"    => $totalRecords, // FIXED: Was missing the '$' prefix
            "recordsFiltered" => $totalRecordsWithFilter,
            "data"            => $data,
        ]);
    }

    public function addChecksheet()
    {
        $this->response->setContentType('application/json');
        $checksheetModel = new ChecksheetInfoModel();
        
        $data = [
            'building_id'   => $this->request->getPost('building_id'),
            'area_id'       => $this->request->getPost('area_id'),
            'dri_id'        => $this->request->getPost('dri_id'), // This is the auditor's user_id
        ];

        $requiredFields = ['building_id', 'area_id', 'dri_id'];
        foreach($requiredFields as $field) {
            if (empty($data[$field])) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 'error',
                    'message' => 'All fields are required.'
                ]);
            }
        }

        if ($checksheetModel->insert($data)) {
            return $this->response->setJSON(['status' => 'success', 'message' => 'Checksheet entry added successfully!']);
        }

        return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Failed to add checksheet entry.', 'errors' => $checksheetModel->errors()]);
    }

    public function getChecksheetDetails($id = null)
    {
        $checksheetModel = new ChecksheetInfoModel();
        
        $checksheet = $checksheetModel
            ->select('checksheet_info.*, area.building_id')
            ->join('area', 'area.area_id = checksheet_info.area_id', 'left')
            ->find($id);

        if ($checksheet) {
            return $this->response->setJSON(['status' => 'success', 'data' => $checksheet]);
        }
        return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Checksheet entry not found.']);
    }

    public function updateChecksheet()
    {
        $checksheetModel = new ChecksheetInfoModel();
        $id = $this->request->getPost('checksheet_id');

        if (empty($id)) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Invalid checksheet ID.']);
        }

        $data = [
            'building_id'   => $this->request->getPost('building_id'),
            'area_id'       => $this->request->getPost('area_id'),
            'dri_id'        => $this->request->getPost('dri_id'), // This is the auditor's user_id
        ];

        if ($checksheetModel->update($id, $data)) {
            return $this->response->setJSON(['status' => 'success', 'message' => 'Checksheet entry updated successfully!']);
        }
        
        return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Failed to update checksheet entry.', 'errors' => $checksheetModel->errors()]);
    }

    public function deleteChecksheet($id = null)
    {
        $checksheetModel = new ChecksheetInfoModel();
        
        if ($checksheetModel->find($id)) {
            $checksheetModel->delete($id);
            return $this->response->setJSON(['status' => 'success', 'message' => 'Checksheet entry deleted successfully!']);
        }
        
        return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Checksheet entry not found or already deleted.']);
    }

    public function getAuditorsByArea($areaId = null)
    {
        if (!$areaId) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Area ID is required.']);
        }

        $auditorModel = new AuditorModel();
        $auditors = $auditorModel->where('area_id', $areaId)->orderBy('fullname', 'ASC')->findAll();

        return $this->response->setJSON(['status' => 'success', 'data' => $auditors]);
    }
}

