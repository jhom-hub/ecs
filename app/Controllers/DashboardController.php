<?php

namespace App\Controllers;
use App\Models\UsersModel;
use App\Models\AreaModel;
use App\Models\BuildingModel;
use App\Models\ItemModel;
use App\Models\FindingsTypeModel;
use App\Models\DriModel;
use App\Models\DepartmentModel;
use App\Models\ChecksheetDataModel;

class DashboardController extends BaseController
{
    protected $areaModel;
    protected $buildingModel;
    protected $checksheetDataModel;
    protected $session;

    public function __construct()
    {
        $this->areaModel = new AreaModel();
        $this->buildingModel = new BuildingModel();
        $this->checksheetDataModel = new ChecksheetDataModel();
    }
    public function index()
    {
        $model = new AreaModel();
        $checksheetDataMdl = new ChecksheetDataModel();
        $findingsModel = new FindingsTypeModel();
        $itemModel = new ItemModel();

        $data['areas'] = $model->findAll();
        $data['checksheetInfos'] = $checksheetDataMdl->findAll();
        $data['findings'] = $findingsModel->findAll();
        $data['items'] = $itemModel->findAll();

        return view('pages/dashboard', $data);
    }

    public function getAreaCount()
    {
        if ($this->request->isAJAX()) {
            $db = \Config\Database::connect();
            $builder = $db->table('area');
            $count = $builder->countAllResults();

            return $this->response->setJSON([
                'count' => $count
            ]);
        }

        return $this->fail('Invalid request', 400);
    }

    public function getAreaNg()
    {
        if ($this->request->isAJAX()) {
            $db = \Config\Database::connect();
            $builder = $db->table('area');
            $builder->where('status', 'NG');
            $count = $builder->countAllResults();

            return $this->response->setJSON([
                'count' => $count
            ]);
        }

        return $this->fail('Invalid request', 400);
    }

    public function getPendingActions()
    {
        if ($this->request->isAJAX()) {
            $db = \Config\Database::connect();
            $builder = $db->table('checksheet_data');
            $builder->where('status', 0);
            $builder->where('DATE(created_at)', date('Y-m-d'));
            $count = $builder->countAllResults();

            return $this->response->setJSON([
                'count' => $count
            ]);
        }

        return $this->fail('Invalid request', 400);
    }

    public function getInspections()
    {
        if ($this->request->isAJAX()) {
            $db = \Config\Database::connect();
            $builder = $db->table('checksheet_data');
            $builder->where('DATE(created_at)', date('Y-m-d'));
            $count = $builder->countAllResults();

            return $this->response->setJSON([
                'count' => $count
            ]);
        }

        return $this->fail('Invalid request', 400);
    }

    public function getAreaFindings($areaId)
    {
        $findingsModel = new FindingsModel();
        $findings = $findingsModel->getAreaFindingsGrouped();

        // Find the specific area
        $areaFindings = array_filter($findings, function ($area) use ($areaId) {
            return $area['area_id'] == $areaId;
        });

        if (empty($areaFindings)) {
            return $this->response->setJSON(['error' => 'No findings found']);
        }

        return $this->response->setJSON(array_values($areaFindings)[0]);
    }

public function getAreaDetails($areaId)
{
    $db = \Config\Database::connect();

    $sql = "
        SELECT 
            cd.checksheet_id,
            a.area_name,
            -- Use GROUP_CONCAT to get all item names in a single string
            GROUP_CONCAT(i.item_name SEPARATOR ', ') AS item_names,
            -- Use GROUP_CONCAT to get all image filenames in a single string
            GROUP_CONCAT(cd.finding_image SEPARATOR ',') AS finding_images,
            GROUP_CONCAT(ft.findings_name ORDER BY ft.findings_id SEPARATOR ', ') AS findings_names
        FROM checksheet_data AS cd
        LEFT JOIN area AS a 
            ON a.area_id = cd.area_id
        LEFT JOIN item AS i 
            ON i.item_id = cd.item_id
        CROSS JOIN JSON_TABLE(
            cd.findings_id,
            '$[*]' COLUMNS (
                findings_id VARCHAR(10) PATH '$'
            )
        ) AS exploded
        LEFT JOIN findings_type AS ft 
            ON ft.findings_id = exploded.findings_id
        WHERE a.status = 'NG'
        AND cd.area_id = ?
        -- Group by checksheet_id to consolidate all findings for that checksheet
        GROUP BY a.area_name, cd.checksheet_id
    ";

    $result = $db->query($sql, [$areaId])->getRow();

    return $this->response->setJSON($result);
}

    public function getAllBuildings()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('building b');
        $builder->select('b.building_id, b.building_name, b.x_coords, b.y_coords, COUNT(CASE WHEN a.status = "NG" THEN 1 END) AS ng_count');
        $builder->join('area a', 'b.building_id = a.building_id', 'left');
        $builder->groupBy('b.building_id', 'b.building_name');
        $query = $builder->get();
        $results = $query->getResultArray();

        return $this->response->setJSON($results);
    }

    // public function countOfAllBuildingsNg()
    // {
    //     $db = \Config\Database::connect();
    //     $builder = $db->table('building b');
    //     $builder->select('b.building_id, b.building_name, COUNT(CASE WHEN a.status = "NG" THEN 1 END) AS ng_count');
    //     $builder->join('area a', 'b.building_id = a.building_id', 'left');
    //     $builder->groupBy('b.building_name', 'b.building_id');
    //     $query = $builder->get();

    //     return $query->getResultArray();
    // }

    public function getAllItemsWithNgCount()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('item i');

        // Select item name, total count, and NG count using conditional aggregation
        $builder->select('i.item_name, COUNT(*) as total_count, 
                      SUM(CASE WHEN a.status = "NG" THEN 1 ELSE 0 END) as ng_count');

        $builder->join('area a', 'i.area_id = a.area_id', 'left');

        // Optional search filter
        $searchTerm = $this->request->getGet('search');
        if ($searchTerm) {
            $builder->like('i.item_name', $searchTerm);
        }

        // date filtering
        $startDate = $this->request->getGet('start_date');
        $endDate = $this->request->getGet('end_date');

        if(!empty($startDate) && !empty($endDate)){
            $builder->where('a.created_at >=', $startDate . ' 00:00:00');
            $builder->where('a.created_at <=', $endDate . ' 23:59:59');
        }

        $builder->groupBy('i.item_name');
        $query = $builder->get();
        $results = $query->getResultArray();

        // Format results to show "NG Count / Total Count"
        foreach ($results as &$row) {
            $row['ng_ratio'] = $row['ng_count'] . '/' . $row['total_count'];
        }

        return $this->response->setJSON($results);
    }

    public function getItemsDetails()
    {
        $db = \Config\Database::connect();
        $request = service('request');

        $draw = $request->getPost('draw');
        $start = $request->getPost('start');
        $length = $request->getPost('length');
        $search = $request->getPost('search')['value'];
        $order = $request->getPost('order');
        $itemName = $request->getPost('item_name');

        // Base query
        $baseBuilder = $db->table('building b')
            ->select('b.building_name, a.area_name, i.item_name, a.status')
            ->join('area a', 'b.building_id = a.building_id', 'left')
            ->join('item i', 'a.area_id = i.area_id', 'left');

        if ($itemName) {
            $baseBuilder->where('i.item_name', $itemName);
        }

        // --- Total records
        $builder = clone $baseBuilder;
        $totalRecords = $builder->countAllResults();

        // --- Apply search
        $builder = clone $baseBuilder;
        if (!empty($search)) {
            $builder->groupStart()
                ->like('b.building_name', $search)
                ->orLike('a.area_name', $search)
                ->orLike('i.item_name', $search)
                ->orLike('a.status', $search)
                ->groupEnd();
        }
        $totalFiltered = $builder->countAllResults(false);

        // --- Ordering
        $columns = ['b.building_name', 'a.area_name', 'i.item_name', 'a.status'];
        if (!empty($order)) {
            $colIndex = $order[0]['column'];
            $colDir = $order[0]['dir'];
            $builder->orderBy($columns[$colIndex], $colDir);
        }

        // --- Paging
        if ($length != -1) {
            $builder->limit($length, $start);
        }

        $data = $builder->get()->getResultArray();

        return $this->response->setJSON([
            "draw" => intval($draw),
            "recordsTotal" => $totalRecords,
            "recordsFiltered" => $totalFiltered,
            "data" => $data
        ]);
    }

    public function getMonthlyNgCount()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('checksheet_data cd');
        $builder->select('MONTH(created_at) as month, SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as ng_count');
        $builder->groupBy('month');
        $builder->orderBy('month');
        $query = $builder->get();
        $results = $query->getResultArray();

        $data = array_fill(1, 12, 0);
        foreach($results as $row){
            $data[$row['month']] = (int) $row['ng_count'];
        }

        return $this->response->setJSON(array_values($data));
    }

}
