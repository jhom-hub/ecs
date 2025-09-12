<?php
namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UsersModel;
use App\Models\AreaModel;
use App\Models\BuildingModel;
use App\Models\ItemModel;
use App\Models\FindingsTypeModel;
use App\Models\DriModel;
use App\Models\DepartmentModel;
use App\Models\ChecksheetDataModel;

class PagesController extends BaseController
{
    public function main()
    {
        $data = $this->getSessionData();
        if (!$data) {
            return redirect()->to('http://10.216.15.10/ecs');
        }
        $page = $this->request->getUri()->getSegment(1);
        $data['content'] = $page;
        return view('pages/main', $data);
    }

    public function login()
    {
        return view('index');
    }

    public function loadContent()
    {
        $this->response->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type');

        $method = strtolower($this->request->getMethod());
        if (!in_array($method, ['post', 'get'])) {
            return $this->response->setStatusCode(405)->setJSON(['error' => 'Method not allowed']);
        }

        $page = $this->request->getPost('page') ?? $this->request->getGet('page');
        $title = $this->request->getPost('title') ?? $this->request->getGet('title');

        if (empty($page)) {
            return $this->response->setJSON(['error' => 'No page specified']);
        }

        $page = preg_replace('/[^a-zA-Z0-9_-]/', '', $page);

        $pageMapping = [
            'dashboard' => 'pages/dashboard',
            'dashboard_sam' => 'pages/dashboard_sam',
            'checksheet' => 'pages/checksheet',
            'corrective_action' => 'pages/corrective_action',
            'data_summary' => 'pages/data_summary', // Your new page
            'audit_trail' => 'pages/audit_trail',
            'send_request' => 'pages/send_request',
            'building_maintenance' => 'pages/maintenance/building_maintenance',
            'area_maintenance' => 'pages/maintenance/area_maintenance',
            'item_maintenance' => 'pages/maintenance/item_maintenance',
            'users_maintenance' => 'pages/maintenance/users_maintenance',
            'auditor_maintenance' => 'pages/maintenance/auditor_maintenance',
            'department_maintenance' => 'pages/maintenance/department_maintenance',
            'division_maintenance' => 'pages/maintenance/division_maintenance',
            'section_maintenance' => 'pages/maintenance/section_maintenance',
            'dri_maintenance' => 'pages/maintenance/dri_maintenance',
            'checksheet_maintenance' => 'pages/maintenance/checksheet_maintenance',
            'findings_maintenance' => 'pages/maintenance/findings_maintenance',
        ];

        if (!array_key_exists($page, $pageMapping)) {
            return $this->response->setJSON(['error' => 'Page not found: ' . htmlspecialchars($page)]);
        }

        $viewName = $pageMapping[$page];
        $data = [
            'pageTitle' => $title,
            'currentPage' => $page
        ];

        $data = array_merge($data, $this->getSessionData());

        if ($page === 'data_summary') {
            $buildingModel = new BuildingModel();
            $departmentModel = new DepartmentModel();
            $driModel = new DriModel();
            
            $data['buildings'] = $buildingModel->orderBy('building_name', 'ASC')->findAll();
            $data['departments'] = $departmentModel->orderBy('department_name', 'ASC')->findAll();
            $data['dris'] = $driModel->orderBy('fullname', 'ASC')->findAll();
        }

        if ($page === 'dashboard' || $page === 'dashboard_sam') {
            $model = new AreaModel();
            $checksheetDataMdl = new ChecksheetDataModel();
            $findingsModel = new FindingsTypeModel();
            $itemModel = new ItemModel();

            $data['areas'] = $model->findAll();
            $data['checksheetInfos'] = $checksheetDataMdl->findAll();
            $data['findings'] = $findingsModel->findAll();
            $data['items'] = $itemModel->findAll();
        }


        $viewPath = APPPATH . 'Views/' . $viewName . '.php';
        if (file_exists($viewPath)) {
            // Pass the fetched data to the view
            $content = view($viewName, $data);
        } else {
            $content = "<h4>Page under construction: {$title}</h4>";
        }
        
        return $this->response->setJSON([
            'content' => $content,
            'title' => $title,
            'page' => $page,
            'success' => true
        ]);
    }

    private function getSessionData()
    {
        if (session()->get('is_logged_in')) {
            return [
                'user_id' => session()->get('user_id'),
                'fullname' => session()->get('fullname'),
                'role' => session()->get('role')
            ];
        }
        return [];
    }
}