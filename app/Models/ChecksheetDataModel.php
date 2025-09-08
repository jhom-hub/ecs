<?php

namespace App\Models;

use CodeIgniter\Model;

class ChecksheetDataModel extends Model
{
    protected $table            = 'checksheet_data';
    protected $primaryKey       = 'data_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'checksheet_id',
        'building_id',
        'area_id',
        'item_id',
        'dri_id',
        'findings_id',
        'finding_image',
        'action_description',
        'action_image',
        'status',
        'priority',
        'control',
        'sub_control',
        'remarks',
        'created_by',
        'updated_by',
        'submitted_date'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    // public function getFindingsByArea($areaId = null)
    // {
    //     return $this->select('
    //             cd.area_id,
    //             cd.findings_id,
    //             cd.item_id,
    //             cd.finding_image,
    //             ft.findings_name,
    //             a.area_name,
    //             a.status,
    //             a.x_coords,
    //             a.y_coords
    //         ')
    //         ->from('checksheet_data cd')
    //         ->join('area a', 'cd.area_id = a.area_id', 'left')
    //         ->join('item i', 'cd.item_id = i.item_id', 'left')
    //         ->join('findings_type ft', 'cd.findings_id = ft.findings_id', 'left');

    //     if($areaId){
    //         $builder->where('a.area_id', $areaId);
    //     }
    //     return $builder->get()->getResultArray();
    // }
}