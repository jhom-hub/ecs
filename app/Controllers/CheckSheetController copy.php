<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\ItemModel;
use App\Models\FindingsTypeModel;
use App\Models\ChecksheetInfoModel;
use App\Models\ChecksheetDataModel;
use App\Models\DriModel;

class CheckSheetController extends BaseController
{
    public function getPending()
    {
        $request = $this->request;
        $ChecksheetInfoModel = new ChecksheetInfoModel();

        $searchableColumns = ['c.checksheet_id', 'a.area_name', 'b.building_name'];

        $limit = (int) ($request->getPost('length') ?? 10);
        $start = (int) ($request->getPost('start') ?? 0);
        $order = $request->getPost('order')[0] ?? ['column' => 0, 'dir' => 'asc'];
        $searchValue = $request->getPost('search')['value'] ?? '';

        $orderColumnMap = ['c.checksheet_id', 'a.area_name', 'b.building_name'];
        $orderColumn = $orderColumnMap[$order['column']] ?? 'c.checksheet_id';

        $ChecksheetInfoModel
            ->select('c.checksheet_id, a.area_name, b.building_name')
            ->from('checksheet_info c')
            ->join('area a', 'c.area_id = a.area_id', 'left')
            ->join('building b', 'c.building_id = b.building_id', 'left')
            ->where('c.status', 0);

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
                "checksheet_id"  => $row['checksheet_id'],
                "area_name"      => htmlspecialchars($row['area_name']),
                "building_name"  => htmlspecialchars($row['building_name']),
                "actions"        => '<div class="btn-group" role="group">
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

    public function getChecked()
    {
        $request = $this->request;
        $ChecksheetInfoModel = new ChecksheetInfoModel();

        $searchableColumns = ['c.checksheet_id', 'a.area_name', 'b.building_name'];

        $limit = (int) ($request->getPost('length') ?? 10);
        $start = (int) ($request->getPost('start') ?? 0);
        $order = $request->getPost('order')[0] ?? ['column' => 0, 'dir' => 'asc'];
        $searchValue = $request->getPost('search')['value'] ?? '';

        $orderColumnMap = ['c.checksheet_id', 'a.area_name', 'b.building_name'];
        $orderColumn = $orderColumnMap[$order['column']] ?? 'c.checksheet_id';

        // ADDED Joins to get names instead of IDs
        $ChecksheetInfoModel
            ->select('c.checksheet_id, a.area_name, b.building_name')
            ->from('checksheet_info c')
            ->join('area a', 'c.area_id = a.area_id', 'left')
            ->join('building b', 'c.building_id = b.building_id', 'left')
            ->where('c.status', 1);

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
                "checksheet_id" => $row['checksheet_id'],
                "area_name"       => htmlspecialchars($row['area_name']),
                "building_name"   => htmlspecialchars($row['building_name']),
                "actions"       => '<div class="btn-group" role="group">
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

    public function getApproved()
    {
        $request = $this->request;
        $ChecksheetInfoModel = new ChecksheetInfoModel();

        $searchableColumns = ['c.checksheet_id', 'a.area_name', 'b.building_name'];

        $limit = (int) ($request->getPost('length') ?? 10);
        $start = (int) ($request->getPost('start') ?? 0);
        $order = $request->getPost('order')[0] ?? ['column' => 0, 'dir' => 'asc'];
        $searchValue = $request->getPost('search')['value'] ?? '';

        $orderColumnMap = ['c.checksheet_id', 'a.area_name', 'b.building_name'];
        $orderColumn = $orderColumnMap[$order['column']] ?? 'c.checksheet_id';

        // ADDED Joins to get names instead of IDs
        $ChecksheetInfoModel
            ->select('c.checksheet_id, a.area_name, b.building_name')
            ->from('checksheet_info c')
            ->join('area a', 'c.area_id = a.area_id', 'left')
            ->join('building b', 'c.building_id = b.building_id', 'left')
            ->where('c.status', 2);

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
                "checksheet_id" => $row['checksheet_id'],
                "area_name"       => htmlspecialchars($row['area_name']),
                "building_name"   => htmlspecialchars($row['building_name']),
                "actions"       => '<div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-secondary" onclick="reviewRequest(' . $row['checksheet_id'] . ')">Review</button>
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
                case '0':
                    $record['status'] = 'Pending';
                    break;
                case '1':
                    $record['status'] = 'Checked';
                    break;
                case '2':
                    $record['status'] = 'Approved';
                    break;
                default:
                    $record['status'] = 'Unknown';
                    break;
            }

            return $this->response->setJSON([
                'status' => 'success',
                'data' => $record
            ]);
        } else {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Record not found'
            ]);
        }
    }

    // In app/Controllers/CheckSheetController.php

    public function saveChecksheetData()
    {
        $this->response->setContentType('application/json');
        $checksheetDataModel = new ChecksheetDataModel();
        $checksheetInfoModel = new ChecksheetInfoModel();

        $checksheetId = $this->request->getPost('checksheet_id');
        $itemIds      = $this->request->getPost('item_id');
        $statuses     = $this->request->getPost('status');

        // --- FIX #1: Ensure all optional/disable-able fields default to an empty array ---
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

        $dataToInsert = [];
        $rowCount = count($itemIds);

        for ($i = 0; $i < $rowCount; $i++) {
            $imageName = null;

            // --- FIX #2: Safely handle potentially missing image data for this row ---
            if (isset($images[$i]) && $images[$i]->isValid() && !$images[$i]->hasMoved()) {
                $subControlValue = $subControls[$i] ?? 'image'; // Use a fallback name if sub-control is missing
                $currentDate = date('mdY');
                $extension = $images[$i]->getExtension();
                $newName = "{$currentDate}_{$subControlValue}.{$extension}";

                if ($images[$i]->move(FCPATH . 'uploads', $newName)) {
                    $imageName = $newName;
                }
            }

            $dataToInsert[] = [
                'checksheet_id' => $checksheetId,
                'building_id'   => $checksheetInfo['building_id'],
                'area_id'       => $checksheetInfo['area_id'],
                'item_id'       => $itemIds[$i],
                'status'        => $statuses[$i],
                'findings_id'    => $findingIds[$i] ?? null,
                'dri_id'        => $driIds[$i] ?? null,
                'remarks'       => $remarks[$i] ?? '',
                'finding_image' => $imageName,
                'control'       => $controls[$i] ?? null,
                'sub_control'   => $subControls[$i] ?? null,
                'created_by'    => session()->get('user_id') ?? 0,
            ];
        }

        if (!empty($dataToInsert)) {
            if ($checksheetDataModel->insertBatch($dataToInsert)) {
                $checksheetInfoModel->update($checksheetId, ['status' => 1]);
                return $this->response->setJSON(['status' => 'success', 'message' => 'Checksheet data saved successfully.']);
            }
        }

        return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Failed to save checksheet data.', 'errors' => $checksheetDataModel->errors()]);
    }

    public function getChecksheetDropdownData($checksheetId = null)
    {
        if (!$checksheetId) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Checksheet ID is required.']);
        }

        $checksheetInfoModel = new ChecksheetInfoModel();
        $itemModel = new ItemModel();
        $driModel = new DriModel();

        $checksheetInfo = $checksheetInfoModel
            ->select('checksheet_info.area_id, area.area_name')
            ->join('area', 'area.area_id = checksheet_info.area_id', 'left')
            ->find($checksheetId);

        if (!$checksheetInfo) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Checksheet not found.']);
        }

        $areaId = $checksheetInfo['area_id'];
        $areaName = $checksheetInfo['area_name'];

        $items = $itemModel->select('item_id, item_name, control')
                        ->where('area_id', $areaId)
                        ->orderBy('item_name', 'ASC')
                        ->findAll();
        $dris = $driModel->where('area_id', $areaId)->orderBy('fullname', 'ASC')->findAll();

        return $this->response->setJSON([
            'status' => 'success',
            'data' => [
                'items' => $items,
                'dris' => $dris,
                'area_name' => $areaName
            ]
        ]);
    }

    public function getFindingsByItem($itemId = null)
    {
        if (!$itemId) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Item ID is required.']);
        }

        $findingsModel = new FindingsTypeModel();
        $findings = $findingsModel->where('item_id', $itemId)->orderBy('findings_name', 'ASC')->findAll();

        return $this->response->setJSON([
            'status' => 'success',
            'data' => $findings
        ]);
    }
}
