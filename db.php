<?php
require_once './config.php';

class DB
{
    private $conn = null, $created = false;

    public function __construct()
    {
        /* Connect */
        try 
        {
            $this->conn = new PDO("mysql:host=".HOST.";dbname=".DB, USER, PASSWORD);
            // set the PDO error mode to exception
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//            echo "Connected successfully\n";
        } 
        catch(PDOException $e) 
        {
            echo "Connection failed: " . $e->getMessage();
        }
    }

    /* Creates the DB if necessary.  Assumes the existence of any table means the work is done. */
    private function createDB()
    {
        if($this->created)
            return;
        $stmt = $this->conn->query("select count(*) as cnt from information_schema.tables where table_schema = '".DB."'");
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $row = $stmt->fetch();
        if($row['cnt'] > 0)
        {
            $this->created = true;
            return;
        }

        /* Actually create the datables. */
        $stmt = $this->conn->prepare("create table employees(id bigint unsigned not null primary key auto_increment, name varchar(255) not null)");
        $stmt->execute();
        $stmt = $this->conn->prepare("create table companies(id bigint unsigned not null primary key auto_increment, name varchar(255) not null, address varchar(255) not null)");
        $stmt->execute();
        $stmt = $this->conn->prepare("create table emp_comp(emp_id bigint, comp_id bigint, unique(comp_id,emp_id))");
        $stmt->execute();

        $this->created = true;
    }

    /* Looks up or creates an employee with the given name. */
    public function getEmployeeId($empName)
    {
        $this->createDB();
        $stmt = $this->conn->prepare("select id from employees where replace(lower(name), ' ', '') = replace(lower(:en),' ', '')");
        $stmt->bindParam(':en', $empName);
        $stmt->execute();
        $row = $stmt->fetch();
        if($row)
            return $row['id']-'0';
        $stmt = $this->conn->prepare("insert into employees set name = :en");
        $stmt->bindParam(':en', $empName);
        $stmt->execute();
        return $this->conn->lastInsertId();
    }

    /* Looks up or creates an copmany with the given name and address. */
    public function getCompanyId($cname, $caddr)
    {
        $this->createDB();
        $stmt = $this->conn->prepare("select id from companies where replace(lower(name), ' ', '') = replace(lower(:cn),' ', '') and replace(lower(address), ' ', '') = replace(lower(:ca),' ', '')");
        $stmt->bindParam(':cn', $cname);
        $stmt->bindParam(':ca', $caddr);
        $stmt->execute();
        $row = $stmt->fetch();
        if($row)
            return $row['id']-'0';
        $stmt = $this->conn->prepare("insert into companies set name = :cn, address = :ca");
        $stmt->bindParam(':cn', $cname);
        $stmt->bindParam(':ca', $caddr);
        $stmt->execute();
        return $this->conn->lastInsertId();
    }

    /* Adds the employee to the company.  The employee may already be working at a competitor. */
    public function addEmployee($empId, $companyId)
    {
        $this->createDB();
        $stmt = $this->conn->prepare("select count(*) as cnt from emp_comp where emp_id = :eid and comp_id = :cid");
        $stmt->bindParam(':eid', $empId);
        $stmt->bindParam(':cid', $companyId);
        $stmt->execute();
        $row = $stmt->fetch();
        if($row['cnt'] == 1)
            return;
        $stmt = $this->conn->prepare("insert into emp_comp set emp_id = :eid, comp_id = :cid");
        $stmt->bindParam(':eid', $empId);
        $stmt->bindParam(':cid', $companyId);
        $stmt->execute();
    }

    /* Name, addr, id, emp/comp */
    public function getCompanies()
    {
        $result = [];
        $stmt = $this->conn->prepare("select name,address,t1.cnt from companies left join (select comp_id,count(comp_id) as cnt from emp_comp group by comp_id) as t1 on companies.id = t1.comp_id");
        $stmt->execute();
        while($row = $stmt->fetch())
        {
            if($row['cnt'] == null)
                $row['cnt'] = 0;
            $result[] = $row;
        }

        return $result;
    }

    public function getEmployees()
    {
        $result = [];
        $stmt = $this->conn->prepare("select employees.id, t1.comp_id, employees.name as ename, companies.name from (select * from emp_comp) as t1 right join employees
        on t1.emp_id = employees.id left join companies on t1.comp_id = companies.id");
        $stmt->execute();
        while($row = $stmt->fetch())
        {
            if(!$row['id'])
                continue;
            if(!isset($result[$row['id']]))
                $result[$row['id']] = ['ename'=>$row['ename'], 'companies'=>[]];
            if($row['comp_id'])
            {
                $result[$row['id']]['companies'][] = [$row['comp_id'],$row['name']];
            }
        }

        return $result;
    }
    
}


/*
$db = new DB();
$tests = ['emp 1', 'eMp 1', 'emp1', 'Emp 1', 'Emp 2'];
foreach($tests as $emp)
{
    $id = $db->getEmployeeId($emp);
    echo "$emp, $id\n";
}

$tests = [['company1','1 company Blvd.'], ['Company 4','4 Company Blvd']];
foreach($tests as $comp)
{
    $id = $db->getCompanyId($comp[0], $comp[1]);
    echo "$comp[0], $comp[1], $id\n";
}

$eid = $db->getEmployeeId('emp 1');
$cid = $db->getCompanyId('company1','1 company Blvd.');
echo "$eid, $cid\n";
$db->addEmployee($eid, $cid);
$cid = $db->getCompanyId('company2','2 company Blvd.');
$db->addEmployee($eid, $cid);
echo "$eid, $cid\n";
$eid = $db->getEmployeeId('emp 2');
$db->addEmployee($eid, $cid);
echo "$eid, $cid\n";
$result = $db->getCompanies();
print_r($result);
*/
