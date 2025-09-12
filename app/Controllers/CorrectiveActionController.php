<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ChecksheetDataModel;
use App\Models\ChecksheetHoldModel;
use App\Models\ChecksheetInfoModel;

class CorrectiveActionController extends BaseController
{
    public function getPending()
    {
        $model = new ChecksheetDataModel();
        $request = $this->request;
        $session = session();
        $departmentId = $session->get('department_id');

        $searchableColumns = ['c.checksheet_id', 'a.area_name', 'b.building_name', 'i.item_name', 'c.feedback'];
        $orderColumnMap = ['c.checksheet_id', 'a.area_name', 'b.building_name', 'i.item_name', 'findings', 'feedback'];

        $limit = (int) ($request->getPost('length') ?? 10);
        $start = (int) ($request->getPost('start') ?? 0);
        $order = $request->getPost('order')[0] ?? ['column' => 0, 'dir' => 'desc'];
        $searchValue = $request->getPost('search')['value'] ?? '';
        $orderColumn = $orderColumnMap[$order['column']] ?? 'c.checksheet_id';
        $currentDate = date('Y-m-d');

        // ✅ UPDATED: Added 'c.feedback' to the SELECT statement
        $model->select("
            c.checksheet_id, c.data_id, c.priority, c.status, c.item_id, c.feedback,
            a.area_name, b.building_name, i.item_name,
            GROUP_CONCAT(DISTINCT ft.findings_name SEPARATOR ', ') as findings
        ")
        ->from('checksheet_data c')
        ->join('area a', 'c.area_id = a.area_id', 'left')
        ->join('building b', 'c.building_id = b.building_id', 'left')
        ->join('item i', 'c.item_id = i.item_id', 'left')
        ->join("JSON_TABLE(c.findings_id, '$[*]' COLUMNS (fid VARCHAR(20) PATH '$')) AS jt", '1=1', 'left')
        ->join('findings_type ft', 'ft.findings_id = jt.fid', 'left')
        ->where('c.dri_id', $departmentId)
        ->where('c.submitted_date', $currentDate)
        // ✅ UPDATED: This logic now includes status 1 items if they have feedback
        ->groupStart()
            ->whereIn('c.status', [0, 3]) // Condition 1: Status is NG or NG HOLD
            ->orGroupStart()              // OR
                ->where('c.status', 1)    // Condition 2: Status is 'For Verification'
                ->where('c.feedback IS NOT NULL') // AND Feedback exists
                ->where("c.feedback != ''")       // AND Feedback is not an empty string
            ->groupEnd()
        ->groupEnd()
        ->groupBy('c.data_id')
        ->orderBy('c.priority', 'desc');

        $totalRecordsBuilder = clone $model->builder();
        $totalRecords = $totalRecordsBuilder->countAllResults(false);

        if (!empty($searchValue)) {
            $model->groupStart();
            foreach ($searchableColumns as $col) {
                $model->orLike($col, $searchValue);
            }
            $model->groupEnd();
        }

        $totalRecordsWithFilterBuilder = clone $model->builder();
        $totalRecordsWithFilter = $totalRecordsWithFilterBuilder->countAllResults(false);

        $model->orderBy($orderColumn, $order['dir']);
        $records = $model->findAll($limit, $start);

        $data = [];
        foreach ($records as $row) {
            $data[] = [
                "checksheet_id" => $row['checksheet_id'],
                "data_id"       => $row['data_id'],
                "priority"      => (int)($row['priority'] ?? 0),
                "item_id"       => $row['item_id'],
                "area_name"     => htmlspecialchars($row['area_name']),
                "building_name" => htmlspecialchars($row['building_name']),
                "item_name"     => htmlspecialchars($row['item_name']),
                "findings"      => !empty($row['findings']) ? htmlspecialchars($row['findings']) : 'No Findings Recorded',
                "status"        => (int)($row['status'] ?? 0),
                "feedback"      => !empty($row['feedback']) ? htmlspecialchars($row['feedback']) : null
            ];
        }

        return $this->response->setJSON([
            "draw"            => (int) $request->getPost('draw'),
            "recordsTotal"    => $totalRecords,
            "recordsFiltered" => $totalRecordsWithFilter,
            "data"            => $data,
        ]);
    }

    public function getItemDetails($dataId, $itemId)
    {
        $db = \Config\Database::connect();
        $mainBuilder = $db->table('checksheet_data c');
        $mainBuilder->select('c.*, a.area_name, b.building_name, i.item_name');
        $mainBuilder->join('area a', 'c.area_id = a.area_id', 'left');
        $mainBuilder->join('building b', 'c.building_id = b.building_id', 'left');
        $mainBuilder->join('item i', 'c.item_id = i.item_id', 'left');
        $mainBuilder->where('c.data_id', $dataId);
        $mainRecord = $mainBuilder->get()->getFirstRow('array');

        if (!$mainRecord) {
            return $this->response->setJSON(['success' => false, 'message' => 'Details not found.']);
        }
        
        if ($mainRecord && $mainRecord['status'] == 3) {
            $holdModel = new ChecksheetHoldModel();
            $holdRecord = $holdModel->where('data_id', $dataId)
                                    ->orderBy('hold_id', 'DESC')
                                    ->first();

            if ($holdRecord) {
                $mainRecord['action_description'] = $holdRecord['action_description'];
                $mainRecord['declared_closure_date'] = $holdRecord['declared_closure_date'];
            }
        }

        $findings_ids = json_decode($mainRecord['findings_id'] ?? '[]', true);
        $findings_names = [];
        if (is_array($findings_ids) && !empty($findings_ids)) {
            $findingsBuilder = $db->table('findings_type');
            $findingsBuilder->select('findings_name');
            $findingsBuilder->whereIn('findings_id', $findings_ids);
            $findingsResult = $findingsBuilder->get()->getResultArray();
            foreach ($findingsResult as $row) {
                $findings_names[] = $row['findings_name'];
            }
        }
        $mainRecord['findings'] = !empty($findings_names) ? implode(', ', $findings_names) : 'N/A';
        
        if (!empty($mainRecord['finding_image'])) {
            $mainRecord['finding_image'] = base_url('uploads/findings/' . $mainRecord['finding_image']);
        }
        
        // ✅ ADDED: Ensure action_image also has the full URL
        if (!empty($mainRecord['action_image'])) {
            $mainRecord['action_image'] = base_url('uploads/actions/' . $mainRecord['action_image']);
        }

        return $this->response->setJSON(['success' => true, 'data' => $mainRecord]);
    }

    public function submitAction()
    {
        $checksheetDataModel = new ChecksheetDataModel();
        $holdModel           = new ChecksheetHoldModel();
        $infoModel           = new ChecksheetInfoModel();
        $areaModel           = new \App\Models\AreaModel();

        $session  = session();
        $userId   = $session->get('user_id');
        $dataId   = $this->request->getPost('data_id');
        $desc     = $this->request->getPost('action_description');
        $closure  = $this->request->getPost('closure_date');
        $image    = $this->request->getFile('action_image');

        // Validate record exists
        $record = $checksheetDataModel->find($dataId);
        if (!$record) {
            return $this->response->setJSON(['success' => false, 'message' => 'Original record not found.']);
        }
        $checksheetId = $record['checksheet_id'];
        $isHold = $this->request->getPost('is_hold') === '1';
        $imageIsProvided = ($image && $image->isValid());

        if ($imageIsProvided) {
            // ✅ CASE: SUBMIT COMPLETION
            $currentDate = date('mdY');
            $extension   = $image->getExtension();
            $imageName   = $currentDate . '_' . $userId . '_' . time() . '.' . $extension;
            $image->move(FCPATH . 'uploads/actions', $imageName);

            // Update main record
            $dataUpdate = [
                'status'             => 1,
                'action_description' => $desc,
                'feedback' => null,
                'action_image'       => $imageName,
            ];
            $checksheetDataModel->update($dataId, $dataUpdate);

            // Update the *latest* hold record for this data
            $latestHold = $holdModel->where('data_id', $dataId)->orderBy('hold_id', 'DESC')->first();
            if ($latestHold) {
                $holdModel->update($latestHold['hold_id'], [
                    'status'             => 1,
                    'action_description' => $desc,
                    'action_image'       => $imageName
                ]);
            }

            // Check if all items for this checksheet are now closed
            $today = date('Y-m-d');
            $remaining = $checksheetDataModel->where('checksheet_id', $checksheetId)
                                            ->where('submitted_date', $today)
                                            ->whereIn('status', [0, 3]) // PENDING or HOLD
                                            ->countAllResults();
            if ($remaining === 0) {
                $infoModel->update($checksheetId, ['status' => 2]); // 2 = completed
                $checksheet = $infoModel->find($checksheetId);
                if ($checksheet && isset($checksheet['area_id'])) {
                    $areaModel->update($checksheet['area_id'], ['status' => 'OK']);
                }
            }

            // Fire alert
            $this->triggerAlert("http://10.216.2.202/ECS_alerts/corrective_action_done_alert.php?data_id=" . $dataId);

            return $this->response->setJSON(['success' => true, 'message' => 'Corrective action submitted successfully!']);
        
        } else if ($isHold) {
            $holdRef = time(); // simple reference number

            $holdData = [
                'data_id'               => $dataId,
                'checksheet_id'         => $checksheetId,
                'hold_ref'              => $holdRef,
                'status'                => 0, // 0 = on-hold
                'action_description'    => $desc,
                'declared_closure_date' => !empty($closure) ? $closure : null,
                'created_at'            => date('Y-m-d H:i:s')
            ];
            $holdModel->insert($holdData);

            // Update main record to NG HOLD
            $checksheetDataModel->update($dataId, ['status' => 3]); // 3 = NG HOLD

            // Fire alert
            $this->triggerAlert("http://10.216.2.202/ECS_alerts/corrective_action_hold_alert.php?data_id=" . $dataId . "&checksheet_id=" . $checksheetId);

            return $this->response->setJSON([
                'success'  => true,
                'message'  => 'Action logged and put on hold successfully!',
                'hold_ref' => $holdRef
            ]);
        } else {
             return $this->response->setJSON(['success' => false, 'message' => 'To complete an action, an image is required. To put on hold, the hold switch must be toggled.']);
        }
    }

    /**
     * Helper to trigger alerts
     */
    private function triggerAlert($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_exec($ch);
        curl_close($ch);
    }
    
    /**
     * ✅ NEW: Get feedback activity for the DRI
     */
    public function getFeedbackActivity()
    {
        $db = \Config\Database::connect();
        $request = $this->request;
        $session = session();
        $departmentId = $session->get('department_id');

        $page = (int)($request->getGet('page') ?? 1);
        $searchValue = $request->getGet('search') ?? '';
        $perPage = 5; 
        $offset = ($page - 1) * $perPage;

        $searchableColumns = ['i.item_name', 'a.area_name', 'cd.feedback'];
        
        $builder = $db->table('checksheet_data cd');
        $builder->select('cd.data_id, cd.feedback, cd.updated_at, i.item_name, a.area_name');
        $builder->join('item i', 'i.item_id = cd.item_id', 'left');
        $builder->join('area a', 'a.area_id = cd.area_id', 'left');
        $builder->where('cd.dri_id', $departmentId);
        $builder->where('cd.feedback IS NOT NULL');
        $builder->where("cd.feedback != ''");
        $builder->where("cd.status != 2");
        $builder->where("cd.status != 0");

        if (!empty($searchValue)) {
            $builder->groupStart();
            foreach ($searchableColumns as $col) { $builder->orLike($col, $searchValue); }
            $builder->groupEnd();
        }

        $totalRecords = (clone $builder)->countAllResults(false);
        $totalPages = ceil($totalRecords / $perPage);

        $builder->orderBy('cd.updated_at', 'DESC')->limit($perPage, $offset);
        $records = $builder->get()->getResult();

        return $this->response->setJSON([
            "status" => "success", 
            "data" => $records, 
            "pagination" => ["currentPage" => $page, "totalPages" => $totalPages]
        ]);
    }

    public function getHoldActivity()
    {
        $db = \Config\Database::connect();
        $request = $this->request;

        $page = (int)($request->getGet('page') ?? 1);
        $searchValue = $request->getGet('search') ?? '';
        $perPage = 5; 
        $offset = ($page - 1) * $perPage;

        $searchableColumns = ['i.item_name', 'a.area_name', 'ch.action_description', 'd.department_name'];
        
        $builder = $db->table('checksheet_hold ch');
        $builder->select('ch.action_description, ch.status, ch.created_at, i.item_name, a.area_name, d.department_name, cd.data_id');
        $builder->join('checksheet_data cd', 'cd.data_id = ch.data_id', 'left');
        $builder->join('item i', 'i.item_id = cd.item_id', 'left');
        $builder->join('area a', 'a.area_id = cd.area_id', 'left');
        $builder->join('users u', 'u.user_id = cd.dri_id', 'left');
        $builder->where('ch.status', 0);
        $builder->join('department d', 'd.department_id = u.department_id', 'left');

        if (!empty($searchValue)) {
            $builder->groupStart();
            foreach ($searchableColumns as $col) { $builder->orLike($col, $searchValue); }
            $builder->groupEnd();
        }

        $totalRecords = (clone $builder)->countAllResults();
        $totalPages = ceil($totalRecords / $perPage);

        $builder->orderBy('ch.created_at', 'DESC')->limit($perPage, $offset);
        $records = $builder->get()->getResult();

        return $this->response->setJSON(["status" => "success", "data" => $records, "pagination" => ["currentPage" => $page, "totalPages" => $totalPages]]);
    }
    
    public function getCompleteActivityDetails($dataId = null)
    {
        if (!$dataId) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Data ID is required.']);
        }

        $db = \Config\Database::connect();
        $builder = $db->table('checksheet_data cd');
        // ✅ UPDATED: Added cd.checksheet_id to the select statement
        $builder->select('
            cd.checksheet_id, cd.status, cd.sub_control, cd.finding_image, cd.action_image, 
            cd.action_description, cd.findings_id, a.area_name, b.building_name, i.item_name
        ');
        $builder->join('area a', 'cd.area_id = a.area_id', 'left');
        $builder->join('building b', 'cd.building_id = b.building_id', 'left');
        $builder->join('item i', 'cd.item_id = i.item_id', 'left');
        $builder->where('cd.data_id', $dataId);
        $record = $builder->get()->getFirstRow('array');

        if (!$record) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Activity details not found.']);
        }

        if ($record['status'] == 3) { // 3 = NG HOLD
            $holdBuilder = $db->table('checksheet_hold');
            $holdRecord = $holdBuilder->where('data_id', $dataId)->orderBy('hold_id', 'DESC')->get()->getFirstRow('array');
            if ($holdRecord) {
                $record['action_description'] = $holdRecord['action_description'];
            }
        }
        
        $findings_ids = json_decode($record['findings_id'] ?? '[]', true);
        if (!empty($findings_ids)) {
            $findingsBuilder = $db->table('findings_type');
            $findingsResult = $findingsBuilder->select('findings_name')->whereIn('findings_id', $findings_ids)->get()->getResultArray();
            $record['findings'] = implode(', ', array_column($findingsResult, 'findings_name'));
        } else {
            $record['findings'] = 'N/A';
        }
        
        if (!empty($record['finding_image'])) $record['finding_image'] = base_url('uploads/findings/' . $record['finding_image']);
        if (!empty($record['action_image'])) $record['action_image'] = base_url('uploads/actions/' . $record['action_image']);

        return $this->response->setJSON(['success' => true, 'data' => $record]);
    }
}