<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class MaintenanceController extends BaseController
{
    protected $buildingMdl;
    protected $areaMdl;
    protected $itemMdl;
    protected $findingsMdl;

    public function __construct()
    {

    }

    public function index()
    {
        return view('pages/maintenance/building_maintenance');
    }
}
