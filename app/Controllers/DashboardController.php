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
            a.area_name,
            cd.finding_image,
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
        GROUP BY a.area_name, cd.finding_image
    ";

        $result = $db->query($sql, [$areaId])->getRow();

        return $this->response->setJSON($result);
    }

    public function getAllBuildings()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('building b');
        $builder->select('b.building_id, b.building_name, COUNT(CASE WHEN a.status = "NG" THEN 1 END) AS ng_count');
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

        $builder->groupBy('i.item_name');
        $query = $builder->get();
        $results = $query->getResultArray();

        // Format results to show "NG Count / Total Count"
        foreach ($results as &$row) {
            $row['ng_ratio'] = $row['ng_count'] . '/' . $row['total_count'];
        }

        return $this->response->setJSON($results);
    }

}
