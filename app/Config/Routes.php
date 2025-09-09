<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'PagesController::login');
$routes->get('/dash_board', 'DashboarController::index');

$routes->group('/', function ($routes) {
    $routes->get('dashboard', 'PagesController::main');
    $routes->get('dash_board', 'SiteMapController::index');
    $routes->get('inbox', 'PagesController::main');
    $routes->get('checksheet', 'PagesController::main');
    $routes->get('audit_trail', 'PagesController::main');
    $routes->get('data_summary', 'PagesController::main');
    $routes->get('send_request', 'PagesController::main');
    $routes->get('corrective_action', 'PagesController::main');

    $routes->get('building_maintenance', 'PagesController::main');
    $routes->get('area_maintenance', 'PagesController::main');
    $routes->get('item_maintenance', 'PagesController::main');
    $routes->get('users_maintenance', 'PagesController::main');

    $routes->get('department_maintenance', 'PagesController::main');
    $routes->get('division_maintenance', 'PagesController::main');
    $routes->get('section_maintenance', 'PagesController::main');
    $routes->get('auditor_maintenance', 'PagesController::main');
    $routes->get('dri_maintenance', 'PagesController::main');
    $routes->get('checksheet_maintenance', 'PagesController::main');
    $routes->get('findings_maintenance', 'PagesController::main');

    $routes->post('auth/login', 'AuthController::login');
    $routes->get('auth/logout', 'AuthController::logout');
});

$routes->match(['GET', 'POST'], 'load-content', 'PagesController::loadContent');

$routes->group('users_maintenance', static function ($routes) {
    $routes->post('getUsers', 'UsersController::getUsers');
    $routes->post('add', 'UsersController::addUser');
    $routes->post('update', 'UsersController::updateUser');
    $routes->post('delete/(:num)', 'UsersController::deleteUser/$1');
    $routes->get('details/(:num)', 'UsersController::getUserDetails/$1');
});

$routes->group('department_maintenance', static function ($routes) {
    $routes->post('getDepartments', 'DepartmentController::getDepartments');
    $routes->post('addDepartment', 'DepartmentController::addDepartment');
    $routes->post('updateDepartment', 'DepartmentController::updateDepartment');
    $routes->post('deleteDepartment/(:num)', 'DepartmentController::deleteDepartment/$1');
    $routes->get('details/(:num)', 'DepartmentController::getDepartmentDetails/$1');
});

$routes->group('division_maintenance', static function ($routes) {
    $routes->post('getDivisions', 'DivisionController::getDivisions');
    $routes->post('addDivision', 'DivisionController::addDivision');
    $routes->post('updateDivision', 'DivisionController::updateDivision');
    $routes->post('deleteDivision/(:num)', 'DivisionController::deleteDivision/$1');
    $routes->get('details/(:num)', 'DivisionController::getDivisionDetails/$1');
    $routes->get('getDepartmentsForDropdown', 'DivisionController::getDepartmentsForDropdown');
});

$routes->group('section_maintenance', static function ($routes) {
    $routes->post('getSections', 'SectionController::getSections');
    $routes->post('addSection', 'SectionController::addSection');
    $routes->post('updateSection', 'SectionController::updateSection');
    $routes->post('deleteSection/(:num)', 'SectionController::deleteSection/$1');
    $routes->get('details/(:num)', 'SectionController::getSectionDetails/$1');
    $routes->get('getDivisionsByDepartment/(:num)', 'SectionController::getDivisionsByDepartment/$1');
});

$routes->group('building_maintenance', static function ($routes) {
    $routes->post('getBuildings', 'BuildingController::getBuildings');
    $routes->post('addBuilding', 'BuildingController::addBuilding');
    $routes->post('updateBuilding', 'BuildingController::updateBuilding');
    $routes->post('deleteBuilding/(:num)', 'BuildingController::deleteBuilding/$1');
    $routes->get('details/(:num)', 'BuildingController::getBuildingDetails/$1');
});

$routes->group('area_maintenance', static function ($routes) {
    $routes->post('getAreas', 'AreaController::getAreas');
    $routes->post('addArea', 'AreaController::addArea');
    $routes->post('updateArea', 'AreaController::updateArea');
    $routes->post('deleteArea/(:num)', 'AreaController::deleteArea/$1');
    $routes->get('details/(:num)', 'AreaController::getAreaDetails/$1');
    $routes->get('getBuildingsForDropdown', 'AreaController::getBuildingsForDropdown');
});

$routes->group('item_maintenance', static function ($routes) {
    $routes->post('getItems', 'ItemController::getItems');
    $routes->post('addItem', 'ItemController::addItem');
    $routes->post('updateItem', 'ItemController::updateItem');
    $routes->post('deleteItem/(:num)', 'ItemController::deleteItem/$1');
    $routes->get('details/(:num)', 'ItemController::getItemDetails/$1');
    $routes->get('getAreasByBuilding/(:num)', 'ItemController::getAreasByBuilding/$1');
});

$routes->group('auditor_maintenance', function ($routes) {
    $routes->post('getAuditors', 'AuditorController::getAuditors');
    $routes->post('create', 'AuditorController::create');
    $routes->get('fetchOne/(:num)', 'AuditorController::fetchOne/$1');
    $routes->post('update', 'AuditorController::update');
    $routes->post('delete/(:num)', 'AuditorController::delete/$1');
    $routes->get('getAreas', 'AuditorController::getAreas');
    $routes->get('getUsers', 'AuditorController::getUsers');
    $routes->get('getAssignmentsForUser/(:num)', 'AuditorController::getAssignmentsForUser/$1');
});

$routes->group('dri_maintenance', static function ($routes) {
    $routes->post('getDris', 'DriController::getDris');
    $routes->post('addDri', 'DriController::addDri');
    $routes->post('updateDri', 'DriController::updateDri');
    $routes->post('deleteDri/(:num)', 'DriController::deleteDri/$1');
    $routes->get('details/(:num)', 'DriController::getDriDetails/$1');
    $routes->get('getSectionsByDivision/(:num)', 'DriController::getSectionsByDivision/$1');
    $routes->get('getUsersForDropdown', 'DriController::getUsersForDropdown');
    $routes->get('getAreasForDropdown', 'DriController::getAreasForDropdown');
});

$routes->group('checksheet_maintenance', static function ($routes) {
    $routes->post('getChecksheets', 'ChecksheetDataController::getChecksheets');
    $routes->post('addChecksheet', 'ChecksheetDataController::addChecksheet');
    $routes->post('updateChecksheet', 'ChecksheetDataController::updateChecksheet');
    $routes->post('deleteChecksheet/(:num)', 'ChecksheetDataController::deleteChecksheet/$1');
    $routes->get('details/(:num)', 'ChecksheetDataController::getChecksheetDetails/$1');
    
    $routes->get('getItemsByArea/(:num)', 'ItemController::getItemsByArea/$1');
    $routes->get('getDrisBySection/(:num)', 'DriController::getDrisBySection/$1');
    $routes->get('getFindingsByItem/(:num)', 'ChecksheetDataController::getFindingsByItem/$1');
    $routes->get('getAuditorsByArea/(:num)', 'ChecksheetDataController::getAuditorsByArea/$1');
});

$routes->group('findings_type_maintenance', static function ($routes) {
    $routes->post('getFindingsTypes', 'FindingsTypeController::getFindingsTypes');
    $routes->post('addFindingsType', 'FindingsTypeController::addFindingsType');
    $routes->post('updateFindingsType', 'FindingsTypeController::updateFindingsType');
    $routes->post('deleteFindingsType/(:num)', 'FindingsTypeController::deleteFindingsType/$1');
    $routes->get('details/(:num)', 'FindingsTypeController::getFindingsTypeDetails/$1');
    $routes->get('getItemsByArea/(:num)', 'FindingsTypeController::getItemsByArea/$1');
});

$routes->group('send_request', static function ($routes) {
    $routes->post('getRequestItems', 'RequestItemsController::getRequestItems');
    $routes->post('addRequestItem', 'RequestItemsController::addRequestItem');
    $routes->post('updateRequestItem', 'RequestItemsController::updateRequestItem');
    $routes->post('deleteRequestItem/(:num)', 'RequestItemsController::deleteRequestItem/$1');
    $routes->get('details/(:num)', 'RequestItemsController::getRequestItemDetails/$1');
    $routes->get('getItemsByBuilding/(:num)', 'ItemController::getItemsByBuilding/$1');
});

$routes->group('corrective_action', static function ($routes) {
    $routes->post('getPending', 'CorrectiveActionController::getPending');
    $routes->post('submitAction', 'CorrectiveActionController::submitAction');
    $routes->get('getItemDetails/(:num)/(:num)', 'CorrectiveActionController::getItemDetails/$1/$2');
});

$routes->group('checksheet', static function ($routes) {
    $routes->POST('getAll', 'CheckSheetController::getAll');
    $routes->POST('viewChecksheet', 'CheckSheetController::viewChecksheet');
    $routes->GET('getPendingRequests', 'RequestItemsController::getPendingRequests');
    $routes->POST('updateRequestStatus', 'RequestItemsController::updateRequestStatus');
    $routes->POST('saveChecksheetData', 'CheckSheetController::saveChecksheetData');
    $routes->get('getDropdownData/(:num)', 'CheckSheetController::getChecksheetDropdownData/$1');
    $routes->get('getFindingsByItem/(:num)', 'CheckSheetController::getFindingsByItem/$1');
    $routes->get('getChecksheetReviewData/(:num)', 'CheckSheetController::getChecksheetReviewData/$1');
});

$routes->group('audit_trail', static function ($routes) {
    $routes->POST('getTrails', 'AuditTrailController::getTrails');
    $routes->get('download-audit-trail', 'AuditTrailController::downloadAuditTrail');
});

$routes->group('dashboard', static function ($routes) {
    $routes->get('get_area_count', 'DashboardController::getAreaCount');
    $routes->get('get_area_ng', 'DashboardController::getAreaNg');
    $routes->get('get_pending_actions', 'DashboardController::getPendingActions');
    $routes->get('get_inspections', 'DashboardController::getInspections');
    $routes->get('area-details/(:num)', 'DashboardController::getAreaDetails/$1');
    $routes->get('get_all_buildings', 'DashboardController::getAllBuildings');
    $routes->get('get_all_items', 'DashboardController::getAllItemsWithNgCount');
    $routes->post('get-items-details', 'DashboardController::getItemsDetails');
});

$routes->group('inbox', static function ($routes) {
    $routes->get('getHoldActivity', 'InboxController::getHoldActivity');
});

$routes->group('summary', function ($routes) {
    $routes->post('getSummaryData', 'SummaryDataController::getSummaryData');
});

    $routes->get('loginguest', 'DashboardController::loginasguest');