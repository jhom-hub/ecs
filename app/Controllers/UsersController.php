<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UsersModel;
// ADDED: Import master data models to look up employee information
use App\Models\NCFLmasterdataModel;
use App\Models\NPFLmasterdataModel;
use App\Models\TblAccountNcflModel;
use App\Models\TblAccountNpflModel;
use CodeIgniter\HTTP\ResponseInterface;

class UsersController extends BaseController
{
    public function getUsers()
    {
        $request = $this->request;
        $usersModel = new UsersModel();

        // UPDATED: Added columns for server-side ordering
        $searchableColumns = ['employee_id', 'username', 'firstname', 'lastname', 'role', 'status'];

        $limit = (int) $request->getPost('length') ?? 10;
        $start = (int) $request->getPost('start') ?? 0;
        $order = $request->getPost('order')[0] ?? ['column' => 0, 'dir' => 'asc'];
        $searchValue = $request->getPost('search')['value'] ?? '';
        
        // Mapped the DataTable column index to the actual database column name
        $orderColumnMap = ['user_id', 'username', 'firstname', 'lastname', 'status', 'role'];
        $orderColumn = $orderColumnMap[$order['column']] ?? 'user_id';


        $totalRecords = $usersModel->countAll();

        if (!empty($searchValue)) {
            $usersModel->groupStart();
            foreach ($searchableColumns as $col) {
                $usersModel->orLike($col, $searchValue);
            }
            $usersModel->groupEnd();
        }

        $totalRecordsWithFilter = $usersModel->countAllResults(false);

        $usersModel->orderBy($orderColumn, $order['dir']);
        $records = $usersModel->findAll($limit, $start);

        $data = [];
        foreach ($records as $row) {
            $statusBadge = '<span class="badge ' . ($row['status'] === 'ACTIVE' ? 'bg-success' : 'bg-danger') . '">' . htmlspecialchars($row['status']) . '</span>';

            $data[] = [
                // CHANGED: The column order now matches the view
                "user_id"     => $row['user_id'],
                "username"    => htmlspecialchars($row['username']),
                "firstname"   => htmlspecialchars($row['firstname']),
                "lastname"    => htmlspecialchars($row['lastname']),
                "status"      => $statusBadge,
                "role"        => htmlspecialchars($row['role']),
                "actions"     => '
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-info" onclick="editUser(' . $row['user_id'] . ')">Update</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteUser(' . $row['user_id'] . ')">Delete</button>
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

    /**
     * REVAMPED: This method now fetches user data from master DBs using Employee ID,
     * similar to the AuthController's first-time login logic.
     */
    public function addUser()
    {
        $this->response->setContentType('application/json');

        try {
            $usersModel = new UsersModel();
            $employeeID = trim($this->request->getPost('employee_id'));

            // 1. Validate required form fields
            $role = trim($this->request->getPost('role'));
            $department_id = $this->request->getPost('department_id');
            $division_id = $this->request->getPost('division_id');
            $section_id = $this->request->getPost('section_id');

            if (empty($employeeID) || empty($role) || empty($department_id) || empty($division_id) || empty($section_id)) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 'error', 'message' => 'Employee ID and all organizational fields are required.'
                ]);
            }

            // 2. Check if user already exists locally
            if ($usersModel->where('employee_id', $employeeID)->first()) {
                return $this->response->setStatusCode(409)->setJSON([
                    'status' => 'error', 'message' => 'This Employee ID is already registered in the system.'
                ]);
            }

            // 3. Look for the employee in master databases
            $ncflMasterDataModel = new NCFLmasterdataModel();
            $npflMasterDataModel = new NPFLmasterdataModel();
            $userInfo = $ncflMasterDataModel->where('fcEmpNo', $employeeID)->first()
                     ?? $npflMasterDataModel->where('fcEmpNo', $employeeID)->first();

            if (!$userInfo) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error', 'message' => 'Employee ID not found in the master databases (NCFL/NPFL).'
                ]);
            }

            // 4. Find the corresponding username and password info from account tables
            $TblAccountNcflModel = new TblAccountNcflModel();
            $TblAccountNpflModel = new TblAccountNpflModel();
            $ncfluserLoginInfo = $TblAccountNcflModel->where('fcempnumber', $employeeID)->first();
            $npfluserLoginInfo = $TblAccountNpflModel->where('empID', $employeeID)->first();

            $loginusername = null;
            $loginpassword = null;

            if ($ncfluserLoginInfo) {
                $loginusername = $ncfluserLoginInfo['fcusername'];
                $loginpassword = $ncfluserLoginInfo['fcuserpassword'];
            } elseif ($npfluserLoginInfo) {
                $loginusername = $npfluserLoginInfo['userName'];
                $loginpassword = md5($npfluserLoginInfo['userPassWord']);
            }

            if (!$loginusername) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error', 'message' => 'Could not find a login account associated with this Employee ID.'
                ]);
            }

            // 5. Prepare data and insert into the local users table
            $dataToInsert = [
                'employee_id'   => $userInfo['fcEmpNo'],
                'username'      => $loginusername,
                'password'      => $loginpassword, // Use the already hashed password
                'firstname'     => $userInfo['fcEmpFName'],
                'lastname'      => $userInfo['fcEmpLName'],
                'lastname'      => $userInfo['fcEmpLName'],
                'email'         => $ncfluserLoginInfo['fcEmailAddress'] ?? $npfluserLoginInfo['fcEmailAddress'],
                'department_id' => $department_id,
                'division_id'   => $division_id,
                'section_id'    => $section_id,
                'role'          => $role,
                'attempt'       => 0,
                'status'        => 'ACTIVE' // Default status
            ];
            
            if ($usersModel->insert($dataToInsert)) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'User synced and added successfully from master database!'
                ]);
            }

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error', 'message' => 'Failed to save user data.', 'errors' => $usersModel->errors() ?? []
            ]);

        } catch (\Exception $e) {
            log_message('error', '[addUser] ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error', 'message' => 'An unexpected error occurred.'
            ]);
        }
    }


    public function getUserDetails($id = null)
    {
        $usersModel = new UsersModel();
        $user = $usersModel->find($id);

        if ($user) {
            return $this->response->setJSON(['status' => 'success', 'data' => $user]);
        }
        return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'User not found.']);
    }

    /**
     * UPDATED: This method now allows updating organizational structure along with role and status.
     */
    public function updateUser()
    {
        $usersModel = new UsersModel();
        $id = $this->request->getPost('user_id');

        if (!$id) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'User ID is missing.']);
        }
        
        $data = [
            'department_id' => $this->request->getPost('department_id'),
            'division_id'   => $this->request->getPost('division_id'),
            'section_id'    => $this->request->getPost('section_id'),
            'role'          => trim($this->request->getPost('role')),
            'status'        => strtoupper(trim($this->request->getPost('status')))
        ];

        // Basic validation
        if (in_array(null, $data, true) || in_array('', $data, true)) {
             return $this->response->setStatusCode(400)->setJSON([
                'status' => 'error',
                'message' => 'All fields are required.'
            ]);
        }

        if ($usersModel->update($id, $data)) {
            return $this->response->setJSON(['status' => 'success', 'message' => 'User updated successfully!']);
        }
        
        return $this->response->setStatusCode(400)->setJSON([
            'status' => 'error', 
            'message' => 'Failed to update user.',
            'errors' => $usersModel->errors()
        ]);
    }

    public function deleteUser($id = null)
    {
        $usersModel = new UsersModel();
        
        if ($usersModel->find($id)) {
            $usersModel->delete($id);
            return $this->response->setJSON(['status' => 'success', 'message' => 'User deleted successfully!']);
        }
        
        return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'User not found or already deleted.']);
    }
}