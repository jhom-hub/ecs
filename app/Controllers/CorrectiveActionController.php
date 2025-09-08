<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ChecksheetDataModel;
use App\Models\ChecksheetInfoModel;

class CorrectiveActionController extends BaseController
{
    public function getPending()
    {
        $model = new ChecksheetDataModel();
        $request = $this->request;
        $session = session();
        $userId = $session->get('user_id');

        $searchableColumns = ['c.checksheet_id', 'a.area_name', 'b.building_name', 'i.item_name'];
        $orderColumnMap = ['c.checksheet_id', 'a.area_name', 'b.building_name', 'i.item_name', 'findings'];

        $limit = (int) ($request->getPost('length') ?? 10);
        $start = (int) ($request->getPost('start') ?? 0);
        $order = $request->getPost('order')[0] ?? ['column' => 0, 'dir' => 'desc'];
        $searchValue = $request->getPost('search')['value'] ?? '';
        $orderColumn = $orderColumnMap[$order['column']] ?? 'c.checksheet_id';
        $currentDate = date('Y-m-d');

        $model->select("
            c.checksheet_id,
            c.data_id,
            c.priority,
            c.item_id,
            a.area_name,
            b.building_name,
            i.item_name,
            GROUP_CONCAT(DISTINCT ft.findings_name SEPARATOR ', ') as findings
        ")
        ->from('checksheet_data c')
        ->join('area a', 'c.area_id = a.area_id', 'left')
        ->join('building b', 'c.building_id = b.building_id', 'left')
        ->join('item i', 'c.item_id = i.item_id', 'left')
        ->join(
            "JSON_TABLE(c.findings_id, '$[*]' COLUMNS (fid VARCHAR(20) PATH '$')) AS jt",
            '1=1',
            'left'
        )
        ->join('findings_type ft', 'ft.findings_id = jt.fid', 'left')
        ->where('c.status', 0)
        ->where('c.submitted_date', $currentDate)
        ->groupBy('c.data_id, c.checksheet_id, a.area_name, b.building_name, i.item_name')
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
                "priority"       => $row['priority'],
                "item_id"       => $row['item_id'],
                "area_name"     => htmlspecialchars($row['area_name']),
                "building_name" => htmlspecialchars($row['building_name']),
                "item_name"     => htmlspecialchars($row['item_name']),
                "findings"      => !empty($row['findings']) ? htmlspecialchars($row['findings']) : 'No Findings Recorded',
                "status"        => '<span class="badge bg-danger">NG</span>'
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

        // Step 2: Get the finding names based on the JSON array of IDs from the record.
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

        // Step 3: Combine the results and prepare the final data object for the response.
        $mainRecord['findings'] = !empty($findings_names) ? implode(', ', $findings_names) : 'N/A';
        
        if (!empty($mainRecord['finding_image'])) {
            $mainRecord['finding_image'] = base_url('uploads/findings/' . $mainRecord['finding_image']);
        }

        return $this->response->setJSON(['success' => true, 'data' => $mainRecord]);
    }

    public function submitAction()
    {
        $dataId      = $this->request->getPost('data_id');
        $itemId      = $this->request->getPost('item_id');
        $description = $this->request->getPost('action_description');
        $imageFile   = $this->request->getFile('action_image');
        $currentDate = date('mdY');
        
        $checksheetDataModel = new ChecksheetDataModel();
        $record = $checksheetDataModel->find($dataId);
        if (!$record) {
             return $this->response->setJSON(['success' => false, 'message' => 'Original record not found.']);
        }
        $checksheetId = $record['checksheet_id'];

        $imageName = null;
        if ($imageFile && $imageFile->isValid() && !$imageFile->hasMoved()) {
            $extension = $imageFile->getExtension();
            $imageName = $currentDate . '_' . $itemId . '_' . $checksheetId . '.' . $extension;
            $imageFile->move(FCPATH . 'uploads/actions', $imageName);
        }

        $dataToUpdate = [
            'status'             => 2,
            'action_description' => $description,
            'action_image'       => $imageName
        ];
        
        $updated = $checksheetDataModel->update($dataId, $dataToUpdate);

        if ($updated) {
            $currentDate = date('Y-m-d');
            $remainingItems = $checksheetDataModel->where('checksheet_id', $checksheetId)
                                    ->where('submitted_date', $currentDate)
                                    ->where('status !=', 2)
                                    ->countAllResults();
            if ($remainingItems === 0) {
                $infoModel = new \App\Models\ChecksheetInfoModel();
                $infoModel->where('checksheet_id', $checksheetId)
                        ->set(['status' => 2])
                        ->update();
                $checksheet = $infoModel->where('checksheet_id', $checksheetId)->first();
                if ($checksheet && isset($checksheet['area_id'])) {
                    $areaId = $checksheet['area_id'];

                    $areaModel = new \App\Models\AreaModel();
                    $areaModel->where('area_id', $areaId)
                            ->set(['status' => 'OK'])
                            ->update();
                }
            }

            return $this->response->setJSON(['success' => true, 'message' => 'Corrective action submitted successfully!']);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => 'Failed to update the database.']);
        }
    }
}