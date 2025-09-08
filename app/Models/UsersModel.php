<?php

namespace App\Models;

use CodeIgniter\Model;

class UsersModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'user_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $DBGroup = 'default';
    
    protected $allowedFields = [
        'employee_id',
        'username',
        'password',
        'firstname',
        'lastname',
        'email',
        'department_id',
        'division_id',
        'section_id',
        'status',
        'attempt',
        'role',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'employee_id' => 'required|is_unique[users.employee_id,user_id,{user_id}]',
        'username'    => 'required|is_unique[users.username,user_id,{user_id}]',
        'role'        => 'required',
        'status'      => 'required'
    ];
    protected $validationMessages = [
        'employee_id' => [
            'is_unique' => 'This Employee ID is already registered.'
        ],
        'username' => [
            'is_unique' => 'This username is already taken.'
        ]
    ];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;
}