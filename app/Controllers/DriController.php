<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\DriModel;
use App\Models\DepartmentModel;
use App\Models\AreaModel;
use App\Models\SectionModel;

class DriController extends BaseController
{
    public function getDris()
    {
        $request = $this->request;
        $driModel = new DriModel();

        $driModel->select('dri.dri_id, dri.fullname, area.area_name, department.department_name, dri.created_at')
                 ->join('area', 'area.area_id = dri.area_id', 'left')
                 ->join('department', 'department.department_id = dri.department_id', 'left');

        $searchableColumns = ['dri.fullname', 'area.area_name', 'department.department_name'];

        $limit = (int) ($request->getPost('length') ?? 10);
        $start = (int) ($request->getPost('start') ?? 0);
        $order = $request->getPost('order')[0] ?? ['column' => 0, 'dir' => 'asc'];
        $searchValue = $request->getPost('search')['value'] ?? '';

        $orderColumn = ['dri_id', 'fullname', 'area_name', 'department_name', 'created_at'][$order['column']] ?? 'dri_id';

        $totalRecords = $driModel->countAllResults(false);

        if (!empty($searchValue)) {
            $driModel->groupStart();
            foreach ($searchableColumns as $col) {
                $driModel->orLike($col, $searchValue);
            }
            $driModel->groupEnd();
        }

        $totalRecordsWithFilter = $driModel->countAllResults(false);

        $driModel->orderBy($orderColumn, $order['dir']);
        $records = $driModel->findAll($limit, $start);

        $data = [];
        foreach ($records as $row) {
            $data[] = [
                "dri_id"          => $row['dri_id'],
                "fullname"        => htmlspecialchars($row['fullname']),
                "area_name"       => htmlspecialchars($row['area_name']),
                "department_name" => htmlspecialchars($row['department_name']),
                "created_at"      => $row['created_at'],
                "actions"         => '
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-info" onclick="editDri(' . $row['dri_id'] . ')">Update</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteDri(' . $row['dri_id'] . ')">Delete</button>
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

    public function addDri()
    {
        $this->response->setContentType('application/json');
        $driModel = new DriModel();
        $departmentModel = new DepartmentModel();
        $areaModel = new AreaModel();

        $departmentId = $this->request->getPost('department_id');
        $areaId       = $this->request->getPost('area_id');

        if (empty($departmentId) || empty($areaId)) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Area and Department fields are required.']);
        }

        $department = $departmentModel->find($departmentId);
        if (!$department) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Selected department not found.']);
        }

        $area = $areaModel->find($areaId);
        if (!$area) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Selected area not found.']);
        }
        
        $dataToInsert = [
            'department_id'     => $department['department_id'],
            'fullname'          => $department['department_name'],
            'area_id'           => $areaId,
            'area_name'         => $area['area_name'],
            'assigned_area'     => $areaId,
            'assigned_building' => $area['building_id']
        ];

        if ($driModel->insert($dataToInsert)) {
            return $this->response->setJSON(['status' => 'success', 'message' => 'DRI added successfully!']);
        }

        return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Failed to add DRI.', 'errors' => $driModel->errors()]);
    }
    
    public function getDriDetails($id = null)
    {
        $driModel = new DriModel();
        $dri = $driModel
            ->select('dri.*, area.area_name, department.department_name')
            ->join('area', 'area.area_id = dri.area_id', 'left')
            ->join('department', 'department.department_id = dri.department_id', 'left')
            ->find($id);

        if ($dri) {
            return $this->response->setJSON(['status' => 'success', 'data' => $dri]);
        }
        return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'DRI not found.']);
    }

    public function updateDri()
    {
        $driModel = new DriModel();
        $departmentModel = new DepartmentModel();
        $areaModel = new AreaModel();
        
        $id           = $this->request->getPost('dri_id');
        $departmentId = $this->request->getPost('department_id');
        $areaId       = $this->request->getPost('area_id');

        if (empty($id) || empty($departmentId) || empty($areaId)) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Area and Department fields are required.']);
        }

        $department = $departmentModel->find($departmentId);
        if (!$department) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Selected department not found.']);
        }
        
        $area = $areaModel->find($areaId);
        if (!$area) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Selected area not found.']);
        }

        $data = [
            'department_id'     => $department['department_id'],
            'fullname'          => $department['department_name'],
            'area_id'           => $areaId,
            'area_name'         => $area['area_name'],
            'assigned_area'     => $areaId,
            'assigned_building' => $area['building_id']
        ];

        if ($driModel->update($id, $data)) {
            return $this->response->setJSON(['status' => 'success', 'message' => 'DRI updated successfully!']);
        }
        
        return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Failed to update DRI.', 'errors' => $driModel->errors()]);
    }

    public function deleteDri($id = null)
    {
        $driModel = new DriModel();
        
        if ($driModel->find($id)) {
            $driModel->delete($id);
            return $this->response->setJSON(['status' => 'success', 'message' => 'DRI deleted successfully!']);
        }
        
        return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'DRI not found or already deleted.']);
    }

    public function getAreasForDropdown()
    {
        $areaModel = new AreaModel();
        $areas = $areaModel->select('area_id, area_name')
                           ->orderBy('area_name', 'ASC')
                           ->findAll();
        return $this->response->setJSON(['status' => 'success', 'data' => $areas]);
    }

    public function getDepartmentsForDropdown()
    {
        $departmentModel = new DepartmentModel();
        $departments = $departmentModel->select('department_id, department_name')
                                       ->orderBy('department_name', 'ASC')
                                       ->findAll();
        return $this->response->setJSON(['status' => 'success', 'data' => $departments]);
    }
}