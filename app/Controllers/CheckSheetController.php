<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\ItemModel;
use App\Models\FindingsTypeModel;
use App\Models\ChecksheetInfoModel;
use App\Models\ChecksheetDataModel;
use App\Models\DriModel;
use App\Models\AreaModel;

class CheckSheetController extends BaseController
{
    public function getAll()
    {
        $request = $this->request;
        $session = session();
        $userId = $session->get('user_id');

        $ChecksheetInfoModel = new ChecksheetInfoModel();

        $searchableColumns = ['c.checksheet_id', 'a.area_name', 'b.building_name'];
        $limit = (int) ($request->getPost('length') ?? 10);
        $start = (int) ($request->getPost('start') ?? 0);
        $order = $request->getPost('order')[0] ?? ['column' => 0, 'dir' => 'asc'];
        $searchValue = $request->getPost('search')['value'] ?? '';
        $orderColumnMap = ['c.checksheet_id', 'a.area_name', 'b.building_name'];
        $orderColumn = $orderColumnMap[$order['column']] ?? 'c.checksheet_id';

        $ChecksheetInfoModel
            ->select('c.checksheet_id, c.status, a.area_name, b.building_name, COUNT(DISTINCT CASE WHEN (e.priority = 1 AND e.status != 2) THEN e.checksheet_id END) AS priority_count')
            ->from('checksheet_info c')
            ->join('area a', 'c.area_id = a.area_id', 'left')
            ->join('building b', 'c.building_id = b.building_id', 'left')
            ->join('auditors d', 'd.area_id = c.area_id', 'left')
            ->join('checksheet_data e', 'e.checksheet_id = c.checksheet_id', 'left')
            ->where('d.user_id', $userId)
            ->groupBy('c.checksheet_id, c.status, a.area_name, b.building_name')
            ->distinct();

        $totalRecords = $ChecksheetInfoModel->countAllResults(false);

        if (!empty($searchValue)) {
            $ChecksheetInfoModel->groupStart();
            foreach ($searchableColumns as $col) {
                $ChecksheetInfoModel->orLike($col, $searchValue);
            }
            $ChecksheetInfoModel->groupEnd();
        }

        $totalRecordsWithFilter = $ChecksheetInfoModel->countAllResults(false);

        $ChecksheetInfoModel->orderBy($orderColumn, $order['dir']);
        $records = $ChecksheetInfoModel->findAll($limit, $start);

        $data = [];
        foreach ($records as $row) {
            $data[] = [
                "checksheet_id"   => $row['checksheet_id'],
                "area_name"       => htmlspecialchars($row['area_name']),
                "building_name"   => htmlspecialchars($row['building_name']),
                "status"          => htmlspecialchars($row['status']),
                "priority_count"  => (int) $row['priority_count'],
                "actions"         => '<div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-primary" onclick="viewRequest(' . $row['checksheet_id'] . ')">View</button>
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
    
    public function viewChecksheet()
    {
        $id = $this->request->getPost('checksheet_id');
        $ChecksheetInfoModel = new ChecksheetInfoModel();

        $record = $ChecksheetInfoModel
            ->select('checksheet_info.checksheet_id, a.area_name, b.building_name, checksheet_info.status')
            ->join('area a', 'checksheet_info.area_id = a.area_id', 'left')
            ->join('building b', 'checksheet_info.building_id = b.building_id', 'left')
            ->where('checksheet_info.checksheet_id', $id)
            ->first();

        if ($record) {
            switch ($record['status']) {
                case '0': $record['status'] = 'Pending'; break;
                case '1': $record['status'] = 'Checked'; break;
                case '2': $record['status'] = 'For Verification'; break;
                case '3': $record['status'] = 'DONE'; break;
                default: $record['status'] = 'Unknown'; break;
            }

            return $this->response->setJSON(['status' => 'success', 'data' => $record]);
        } else {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Record not found']);
        }
    }
    
    public function getChecksheetReviewData($checksheetId = null)
    {
        if (!$checksheetId) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Checksheet ID is required.']);
        }
        $currentDate = date('Y-m-d');
        $db = \Config\Database::connect();

        $infoBuilder = $db->table('checksheet_info ci');
        $info = $infoBuilder->select('ci.checksheet_id, a.area_name, b.building_name')
                            ->join('area a', 'ci.area_id = a.area_id', 'left')
                            ->join('building b', 'ci.building_id = b.building_id', 'left')
                            ->where('ci.checksheet_id', $checksheetId)
                            ->get()->getFirstRow('array');

        if (!$info) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Checksheet not found.']);
        }

        $dataBuilder = $db->table('checksheet_data cd');
        $items = $dataBuilder
            ->select('
                cd.data_id, cd.feedback, i.item_name, cd.sub_control, cd.status, cd.priority, 
                ft.findings_name, cd.finding_image, d.fullname as dri_name,
                cd.remarks, cd.action_description, cd.action_image, cd.feedback
            ')
            ->join('item i', 'cd.item_id = i.item_id', 'left')
            ->join('dri d', 'cd.dri_id = d.dri_id', 'left')
            ->join('findings_type ft', 'cd.findings_id = ft.findings_id', 'left')
            ->where('cd.checksheet_id', $checksheetId)
            ->where('cd.submitted_date', $currentDate)
            ->get()->getResultArray();

        foreach ($items as &$item) {
            $item['findings_list'] = $item['findings_name'] ?? '---';
            unset($item['findings_name']);
            
            if (!empty($item['finding_image'])) $item['finding_image'] = base_url('uploads/findings/' . $item['finding_image']);
            if (!empty($item['action_image'])) $item['action_image'] = base_url('uploads/actions/' . $item['action_image']);
        }

        return $this->response->setJSON(['status' => 'success', 'data' => ['info' => $info, 'items' => $items]]);
    }

    public function saveChecksheetData()
    {
        $this->response->setContentType('application/json');
        $checksheetDataModel = new ChecksheetDataModel();
        $checksheetInfoModel = new ChecksheetInfoModel();
        $areaModel = new AreaModel();

        $checksheetId = $this->request->getPost('checksheet_id');
        $itemIds      = $this->request->getPost('item_id');
        $statuses     = $this->request->getPost('status');
        $priorities   = $this->request->getPost('priority');
        $findingIds   = $this->request->getPost('findings_id') ?? [];
        $driIds       = $this->request->getPost('dri_id') ?? [];
        $remarks      = $this->request->getPost('remarks') ?? [];
        $controls     = $this->request->getPost('control') ?? [];
        $subControls  = $this->request->getPost('sub_control') ?? [];
        $images       = ($this->request->getFiles())['finding_image'] ?? [];

        if (empty($checksheetId) || empty($itemIds)) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Missing required data.']);
        }
        $checksheetInfo = $checksheetInfoModel->find($checksheetId);
        if (!$checksheetInfo) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Parent Checksheet record not found.']);
        }

        $rowCount = count($itemIds);
        $hasNgStatus = false;
        for ($i = 0; $i < $rowCount; $i++) {
            if (isset($statuses[$i]) && $statuses[$i] == '0') {
                $hasNgStatus = true;
                if (empty($driIds[$i])) {
                    return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => "Row " . ($i + 1) . ": A DRI must be selected for NG items."]);
                }
            }
        }
        
        $dataToInsert = [];
        for ($i = 0; $i < $rowCount; $i++) {
            $imageName = null;
            if (isset($images[$i]) && $images[$i]->isValid() && !$images[$i]->hasMoved()) {
                $newName = $images[$i]->getRandomName();
                if ($images[$i]->move(FCPATH . 'uploads/findings', $newName)) {
                    $imageName = $newName;
                }
            }
            $dataToInsert[] = [
                'checksheet_id'  => $checksheetId, 'building_id'    => $checksheetInfo['building_id'],
                'area_id'        => $checksheetInfo['area_id'], 'item_id'        => $itemIds[$i],
                'status'         => $statuses[$i], 'priority'       => $priorities[$i] ?? 0,
                'findings_id'    => $findingIds[$i] ?? null, 'dri_id'         => $driIds[$i] ?? null,
                'remarks'        => $remarks[$i] ?? '', 'finding_image'  => $imageName,
                'control'        => $controls[$i] ?? null, 'sub_control'    => $subControls[$i] ?? null,
                'created_by'     => session()->get('user_id') ?? 0, 'submitted_date' => date('Y-m-d'),
            ];
        }

        if (!empty($dataToInsert)) {
            if ($checksheetDataModel->insertBatch($dataToInsert)) {
                $checksheetInfoModel->update($checksheetId, ['status' => 0]); 
                $areaId = $checksheetInfo['area_id'];

                if ($hasNgStatus) {
                    if ($areaId) $areaModel->update($areaId, ['status' => 'NG']);
                    
                    $uniqueDriIds = [];
                    for ($i = 0; $i < count($itemIds); $i++) {
                        if (isset($statuses[$i]) && $statuses[$i] == '0' && !empty($driIds[$i])) {
                            $uniqueDriIds[$driIds[$i]] = true;
                        }
                    }
                    
                    foreach (array_keys($uniqueDriIds) as $departmentId) {
                        $alertUrl = "http://10.216.2.202/ECS_alerts/audit_notification_alert.php?checksheet_id=" . $checksheetId . "&department_id=" . $departmentId;
                        
                        $ch = curl_init($alertUrl);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                        curl_exec($ch);
                        curl_close($ch);
                    }
                    
                    return $this->response->setJSON(['status' => 'success', 'message' => "Checksheet saved! Area status set to NG and notification sent."]);
                } else {
                    if ($areaId) $areaModel->update($areaId, ['status' => 'OK']);
                    return $this->response->setJSON(['status' => 'success', 'message' => 'Checksheet data saved successfully. No NG items found.']);
                }
            }
        }
        return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Failed to save checksheet data.']);
    }

    public function submitFeedback()
    {
        $this->response->setContentType('application/json');
        $dataId = $this->request->getPost('data_id');
        $feedback = $this->request->getPost('feedback');

        if (empty($dataId) || $feedback === null) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Data ID and feedback are required.']);
        }

        $checksheetDataModel = new ChecksheetDataModel();
        $data = ['feedback' => trim($feedback)];

        if ($checksheetDataModel->update($dataId, $data)) {
            try {
                $alertUrl = "http://10.216.2.202/ECS_alerts/feedback_notification_alert.php?data_id=" . $dataId;
                
                $ch = curl_init($alertUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_exec($ch);
                curl_close($ch);
            } catch (\Exception $e) {
                log_message('error', 'Feedback alert cURL failed: ' . $e->getMessage());
            }

            return $this->response->setJSON(['status' => 'success', 'message' => 'Feedback submitted successfully.']);
        } else {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Failed to submit feedback.']);
        }
    }

    public function approveItems()
    {
        $this->response->setContentType('application/json');
        $checksheetId = $this->request->getPost('checksheet_id');
        $dataIds = $this->request->getPost('data_ids');
        if (empty($checksheetId) || empty($dataIds) || !is_array($dataIds)) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Invalid data provided for approval.']);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $checksheetDataModel = new ChecksheetDataModel();
        $checksheetDataModel->whereIn('data_id', $dataIds)->set(['status' => 2])->update();
        
        $remainingItems = $checksheetDataModel
            ->where('checksheet_id', $checksheetId)
            ->where('submitted_date', date('Y-m-d'))
            ->whereIn('status', [0, 1, 3])
            ->countAllResults();

        if ($remainingItems === 0) {
            $checksheetInfoModel = new ChecksheetInfoModel();
            $checksheetInfoModel->update($checksheetId, ['status' => 3]);
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Database transaction failed.']);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Selected items approved successfully!']);
    }

    public function getChecksheetDropdownData($checksheetId = null)
    {
        if (!$checksheetId) {
            return $this->response->setStatusCode(400)->setJSON(['status'  => 'error', 'message' => 'Checksheet ID is required.']);
        }
        $checksheetInfoModel = new ChecksheetInfoModel();
        $itemModel           = new ItemModel();
        $driModel            = new DriModel();
        $checksheetInfo = $checksheetInfoModel
            ->select('checksheet_info.area_id, area.area_name')
            ->join('area', 'area.area_id = checksheet_info.area_id', 'left')
            ->find($checksheetId);

        if (!$checksheetInfo) {
            return $this->response->setStatusCode(404)->setJSON(['status'  => 'error', 'message' => 'Checksheet not found.']);
        }
        $areaId   = $checksheetInfo['area_id'];
        $areaName = $checksheetInfo['area_name'];
        $items = $itemModel->select('item_id, item_name, control')->where('area_id', $areaId)->orderBy('item_name', 'ASC')->findAll();
        $dris = $driModel->select('dri.dri_id, dri.fullname')->where('dri.area_id', $areaId)->orderBy('dri.fullname', 'ASC')->findAll();
        return $this->response->setJSON(['status' => 'success', 'data'   => ['items' => $items, 'dris' => $dris, 'area_name' => $areaName]]);
    }

    public function getFindingsByItem($itemId = null)
    {
        if (!$itemId) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Item ID is required.']);
        }
        $findingsModel = new FindingsTypeModel();
        $findings = $findingsModel->where('item_id', $itemId)->orderBy('findings_name', 'ASC')->findAll();
        return $this->response->setJSON(['status' => 'success', 'data' => $findings]);
    }
}