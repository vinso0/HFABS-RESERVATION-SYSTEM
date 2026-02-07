<?php

class BranchController extends Controller
{
    public function index()
    {
        $branchModel = $this->model('Branch');
        $branches = $branchModel->getAllBranches();
        
        header('Content-Type: application/json');
        echo json_encode($branches);
    }

    public function show($id)
    {
        $branchModel = $this->model('Branch');
        $branch = $branchModel->getBranchById($id);
        
        header('Content-Type: application/json');
        echo json_encode($branch);
    }
}

?>
