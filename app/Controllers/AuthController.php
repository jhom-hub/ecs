<?php


namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UsersModel;
use App\Models\AuditTrailModel;
use App\Models\NCFLmasterdataModel;
use App\Models\NPFLmasterdataModel;
use App\Models\TblAccountNcflModel;
use App\Models\TblAccountNpflModel;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\DriModel;

class AuthController extends BaseController
{
    protected $session;

    public function __construct()
    {
        $this->session = session();
    }

    public function login()
    {
        $this->response->setContentType('application/json');
        $usersModel = new UsersModel();
        $auditTrailModel = new AuditTrailModel();
        $DriModel = new DriModel();

        try {
            $username = $this->request->getPost('username');
            $password = $this->request->getPost('password');

            
            $user = $usersModel->where('username', $username)->first();
            $dri = $DriModel->where('user_id', $user['user_id'])->first();

            if ($user) {
                
                if (md5($password, $user['password'])) {
                    
                    $fullname = $user['firstname'] . ' ' . $user['lastname'];
                    $sessionData = [
                        'user_id'      => $user['user_id'],
                        'fullname'     => $fullname,
                        'email'         => $user['email'],
                        'department_id'         => $user['department_id'],
                        'division_id'         => $user['division_id'],
                        'section_id'         => $user['section_id'],
                        'area_id'         => $dri['area_id'] ?? 0,
                        'role'         => $user['role'],
                        'status'         => $user['status'],
                        'is_logged_in' => true
                    ];
                    $this->session->set($sessionData);

                    if ($user['role'] == "GUEST"){
                        $redirectLink = base_url('dashboard');
                    } else {
                        $redirectLink = base_url('dashboard');
                    }

                    
                    $auditTrailModel->save([
                        'name'       => $fullname,
                        'action'     => 'LOGGED IN',
                        'ip_address' => $this->request->getIPAddress()
                    ]);

                    return $this->response->setJSON([
                        'status'   => 'success',
                        'message'  => 'Login Successfully!',
                        'redirect' => $redirectLink
                    ]);
                }

                return $this->response->setStatusCode(401)->setJSON([
                    'status'  => 'error',
                    'message' => 'Invalid username or password.'
                ]);
            }

            
            $TblAccountNcflModel = new TblAccountNcflModel();
            $TblAccountNpflModel = new TblAccountNpflModel();
            $ncflMasterDataModel = new NCFLmasterdataModel();
            $npflMasterDataModel = new NPFLmasterdataModel();

            $ncfluserLoginInfo = $TblAccountNcflModel->where('fcusername', $username)->first();
            $npfluserLoginInfo = $TblAccountNpflModel->where('userName', $username)->first();

            $userInfo = null;
            $loginusername = null;
            $loginpassword = null;

            if ($ncfluserLoginInfo) {
                $userInfo = $ncflMasterDataModel->where('fcEmpNo', $ncfluserLoginInfo['fcempnumber'])->first();
                $loginusername = $ncfluserLoginInfo['fcusername'];
                $loginpassword = $ncfluserLoginInfo['fcuserpassword']; 
            } elseif ($npfluserLoginInfo) {
                $userInfo = $npflMasterDataModel->where('fcEmpNo', $npfluserLoginInfo['empID'])->first();
                $loginusername = $npfluserLoginInfo['userName'];
                $loginpassword = md5($npfluserLoginInfo['userPassWord']); 
            }

            if ($userInfo && md5($password) === $loginpassword) {
                
                $dataToInsert = [
                    'employee_id' => $userInfo['fcEmpNo'],
                    'username'    => $loginusername,
                    'password'    => md5($password), 
                    'firstname'   => $userInfo['fcEmpFName'],
                    'lastname'    => $userInfo['fcEmpLName'],
                    'email'         => $ncfluserLoginInfo['fcEmailAddress'] ?? $npfluserLoginInfo['fcEmailAddress'],
                    'attempt'     => 0,
                    'role'        => 'GUEST', 
                    'status'      => 'ACTIVE'
                ];

                $insertedId = $usersModel->insert($dataToInsert);

                
                $fullname = $userInfo['fcEmpFName'] . ' ' . $userInfo['fcEmpLName'];
                $sessionData = [
                    'user_id'      => $insertedId,
                    'role'         => 'GUEST',
                    'fullname'     => $fullname,
                    'is_logged_in' => true
                ];
                $this->session->set($sessionData);

                
                $auditTrailModel->save([
                    'name'       => $fullname,
                    'action'     => 'LOGGED IN (Imported from Master DB)',
                    'ip_address' => $this->request->getIPAddress()
                ]);

                return $this->response->setJSON([
                    'status'   => 'success',
                    'message'  => 'Login Successful! Account synced from master database.',
                    'redirect' => base_url('checksheet')
                ]);
            }

            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Invalid username or password.'
            ]);

        } catch (\Exception $e) {
            log_message('error', '[login] ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'An unexpected error occurred: ' . $e->getMessage()
            ]);
        }
    }
    

    public function logout()
    {
        $auditTrailModel = new AuditTrailModel();
        $auditTrailModel->save([
            'name'       => $this->session->get('fullname'),
            'action'     => 'LOGGED OUT',
            'ip_address' => $this->request->getIPAddress()
        ]);

        $this->session->destroy();
        return redirect()->to(base_url());
    }

    public function testSession()
    {
        $sessionData = $this->session->get();
        return $this->response->setJSON([
            'session_data' => $sessionData,
            'is_logged_in' => $this->session->get('is_logged_in') ?? false
        ]);
    }
}