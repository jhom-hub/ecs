<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\AuditorModel;
use App\Models\UsersModel;
use App\Models\AreaModel;

class AuditorController extends BaseController
{
    public function index()
    {
        return view('auditor_maintenance');
    }

    public function getAuditors()
    {
        // No changes needed here, it reads from auditors.fullname
        $request = $this->request;
        $AuditorModel = new AuditorModel();

        $AuditorModel->select("auditors.user_id, auditors.fullname, GROUP_CONCAT(area.area_name SEPARATOR ', ') as area_name, MIN(auditors.created_at) as created_at")
            ->join('area', 'area.area_id = auditors.area_id', 'left')
            ->groupBy('auditors.user_id, auditors.fullname');

        $searchableColumns = ['fullname', 'area.area_name'];
        $limit = (int) $request->getPost('length') ?? 10;
        $start = (int) $request->getPost('start') ?? 0;
        $order = $request->getPost('order')[0] ?? ['column' => 0, 'dir' => 'asc'];
        $searchValue = $request->getPost('search')['value'] ?? '';
        $orderColumn = ['user_id', 'fullname', 'area_name', 'created_at'][$order['column']] ?? 'user_id';
        
        $builder = clone $AuditorModel->builder();
        $totalRecords = $builder->countAllResults(false);

        if (!empty($searchValue)) {
            $AuditorModel->groupStart();
            foreach ($searchableColumns as $col) {
                if ($col === 'area.area_name') {
                     $AuditorModel->orHaving("GROUP_CONCAT(area.area_name SEPARATOR ', ') LIKE '%" . $searchValue . "%'");
                } else {
                     $AuditorModel->orLike($col, $searchValue);
                }
            }
            $AuditorModel->groupEnd();
        }

        $builderWithFilter = clone $AuditorModel->builder();
        $totalRecordsWithFilter = $builderWithFilter->countAllResults(false);
        
        $AuditorModel->orderBy($orderColumn, $order['dir']);
        $records = $AuditorModel->findAll($limit, $start);

        $data = [];
        foreach ($records as $row) {
            $data[] = [
                "user_id"         => $row['user_id'],
                "fullname"        => htmlspecialchars($row['fullname']),
                "area_name"       => htmlspecialchars($row['area_name']),
                "created_at"      => $row['created_at'],
                "actions"         => '
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-info" onclick="editAuditor(' . $row['user_id'] . ')">Update</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteAuditor(' . $row['user_id'] . ')">Delete</button>
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

    public function create()
    {
        $auditorModel = new AuditorModel();
        $userModel = new UsersModel();
        $db = \Config\Database::connect();

        $userId = $this->request->getPost('user_id');
        $areaIds = $this->request->getPost('area_id') ?? [];

        if (empty($userId) || empty($areaIds)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'User and at least one Area are required.'])->setStatusCode(400);
        }

        $user = $userModel->find($userId);
        if (!$user) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'User not found.'])->setStatusCode(404);
        }

        // MODIFIED: Concatenate the name for storage in the auditors table
        $userFullName = $user['firstname'] . ' ' . $user['lastname'];

        $db->transStart();

        foreach ($areaIds as $areaId) {
            if ($auditorModel->where(['user_id' => $userId, 'area_id' => $areaId])->first()) {
                continue;
            }
            $data = [
                'user_id'  => $userId,
                'area_id'  => $areaId,
                'fullname' => $userFullName, // Use the concatenated name
                'email'    => $user['email'],
            ];
            $auditorModel->insert($data);
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Database transaction failed.'])->setStatusCode(500);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Auditor assignments created successfully.']);
    }
    
    public function getAssignmentsForUser($userId)
    {
        $auditorModel = new AuditorModel();
        $assignments = $auditorModel->where('user_id', $userId)->findAll();
        $areaIds = array_column($assignments, 'area_id');
        
        $userModel = new UsersModel();
        $user = $userModel->find($userId);

        if ($user) {
             // MODIFIED: Manually add the concatenated 'fullname' to the user array
             $user['fullname'] = $user['firstname'] . ' ' . $user['lastname'];

             return $this->response->setJSON([
                'status' => 'success', 
                'user' => $user,
                'area_ids' => $areaIds
            ]);
        } else {
            return $this->response->setJSON(['status' => 'error', 'message' => 'User not found.'])->setStatusCode(404);
        }
    }

    public function update()
    {
        $auditorModel = new AuditorModel();
        $userModel = new UsersModel();
        $db = \Config\Database::connect();

        $userId = $this->request->getPost('user_id');
        $newAreaIds = $this->request->getPost('area_id') ?? [];

        if (empty($userId)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'User ID is missing.'])->setStatusCode(400);
        }
        
        $user = $userModel->find($userId);
        if (!$user) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'User not found.'])->setStatusCode(404);
        }

        // MODIFIED: Concatenate the name for storage in the auditors table
        $userFullName = $user['firstname'] . ' ' . $user['lastname'];

        $db->transStart();

        $currentAssignments = $auditorModel->where('user_id', $userId)->findAll();
        $currentAreaIds = array_column($currentAssignments, 'area_id');

        $areasToDelete = array_diff($currentAreaIds, $newAreaIds);
        if (!empty($areasToDelete)) {
            $auditorModel->where('user_id', $userId)->whereIn('area_id', $areasToDelete)->delete();
        }

        $areasToAdd = array_diff($newAreaIds, $currentAreaIds);
        foreach ($areasToAdd as $areaId) {
            $data = [
                'user_id'  => $userId,
                'area_id'  => $areaId,
                'fullname' => $userFullName, // Use the concatenated name
                'email'    => $user['email'],
            ];
            $auditorModel->insert($data);
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Database transaction failed while updating.'])->setStatusCode(500);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Auditor assignments updated successfully.']);
    }

    public function delete($userId)
    {
        $auditorModel = new AuditorModel();
        if ($auditorModel->where('user_id', $userId)->first()) {
            $auditorModel->where('user_id', $userId)->delete();
            return $this->response->setJSON(['status' => 'success', 'message' => 'All assignments for this auditor have been deleted.']);
        } else {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Auditor not found.'])->setStatusCode(404);
        }
    }

    public function getAreas()
    {
        $areaModel = new AreaModel();
        return $this->response->setJSON($areaModel->findAll());
    }
    
    public function getUsers()
    {
        $userModel = new UsersModel();
        // MODIFIED: Use the database's CONCAT function to create a 'fullname' field on the fly.
        $users = $userModel->select("user_id, CONCAT(firstname, ' ', lastname) as fullname")->findAll();
        return $this->response->setJSON($users);
    }
}