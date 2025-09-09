<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class InboxController extends BaseController
{
    public function getHoldActivity()
    {
        $db = \Config\Database::connect();
        $request = $this->request;

        // --- Parameters for custom pagination and search ---
        $page = (int)($request->getGet('page') ?? 1);
        $searchValue = $request->getGet('search') ?? '';
        $perPage = 5; // How many items to show per page
        $offset = ($page - 1) * $perPage;

        // --- MODIFIED: Searchable columns now include department_name ---
        $searchableColumns = [
            'i.item_name',
            'a.area_name',
            'ch.action_description',
            'd.department_name'
        ];
        
        // --- Base Query ---
        $builder = $db->table('checksheet_hold ch');
        
        // --- MODIFIED: Selects department_name instead of user's name ---
        $builder->select('
            ch.action_description,
            ch.status,
            ch.created_at,
            i.item_name,
            a.area_name,
            d.department_name
        ');
        $builder->join('checksheet_data cd', 'cd.data_id = ch.data_id', 'left');
        $builder->join('item i', 'i.item_id = cd.item_id', 'left');
        $builder->join('area a', 'a.area_id = cd.area_id', 'left');
        $builder->join('users u', 'u.user_id = cd.dri_id', 'left');
        // --- ADDED: Join the department table ---
        $builder->join('department d', 'd.department_id = u.department_id', 'left');

        // --- Apply Search Filter ---
        if (!empty($searchValue)) {
            $builder->groupStart();
            foreach ($searchableColumns as $col) {
                $builder->orLike($col, $searchValue);
            }
            $builder->groupEnd();
        }

        // --- Get Total Filtered Record Count for Pagination ---
        $totalRecordsBuilder = clone $builder;
        $totalRecords = $totalRecordsBuilder->countAllResults();
        $totalPages = ceil($totalRecords / $perPage);

        $builder->orderBy('ch.created_at', 'DESC');
        // $builder->orderBy('ch.status', 'DESC');
        $builder->limit($perPage, $offset);
        
        $records = $builder->get()->getResult();

        // --- Final JSON Response with Pagination Info ---
        return $this->response->setJSON([
            "status" => "success",
            "data" => $records,
            "pagination" => [
                "currentPage" => $page,
                "totalPages" => $totalPages,
                "totalRecords" => $totalRecords,
                "perPage" => $perPage,
            ]
        ]);
    }
}