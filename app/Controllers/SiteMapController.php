<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\AreaModel;

class SiteMapController extends BaseController
{
    public function index()
    {
        $model = new AreaModel();
        $data['areas'] = $model->findAll();
        return view('pages/dashboard', $data);
    }
}