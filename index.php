<?php
require_once('db.php');
$db = new DB();
if($_REQUEST['action'] === 'companyList')
{
    header('Content-Type: application/json; charset=utf-8');
    $data = $db->getCompanies();
    echo json_encode($data);
}
else if($_REQUEST['action'] === 'employeeList')
{
    header('Content-Type: application/json; charset=utf-8');
    $employees = $db->getEmployees();
    echo json_encode($employees);
}
?>