<?php

namespace App\Models;

use CodeIgniter\Model;

class ChecksheetHoldModel extends Model
{
    protected $table            = 'checksheet_hold';
    protected $primaryKey       = 'hold_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'data_id',
        'action_description',
        'action_image',
        'status',
        'declared_closure_date'
    ];

    // Dates
    protected $useTimestamps = false;
}