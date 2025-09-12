<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class InboxController extends BaseController
{
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