<?php

class BranchController extends Controller
{
    // Get all branches
    // API endpoint: GET /api/branches
    public function index()
    {
        $branchModel = $this->model('Branch');
        $branches = $branchModel->getAllBranches();
        
        // Format branches to match frontend expected structure
        $formattedBranches = array_map(function($branch) {
            return array(
                'branchid' => $branch['branch_id'],
                'branchname' => $branch['branch_name'],
                'location' => $branch['branch_location'],
                'contact_number' => $branch['contact_number'],
                'opening_time' => $branch['opening_time'],
                'closing_time' => $branch['closing_time']
            );
        }, $branches);
        
        header('Content-Type: application/json');
        echo json_encode($formattedBranches);
    }

    // Get branch by ID
    // API endpoint: GET /api/branches/{id}
    public function show($id)
    {
        $branchModel = $this->model('Branch');
        $branch = $branchModel->getBranchById($id);
        
        if (!$branch) {
            http_response_code(404);
            echo json_encode(array('error' => 'Branch not found'));
            return;
        }
        
        // Format branch to match frontend expected structure
        $formattedBranch = array(
            'branchid' => $branch['branch_id'],
            'branchname' => $branch['branch_name'],
            'location' => $branch['branch_location'],
            'contact_number' => $branch['contact_number'],
            'opening_time' => $branch['opening_time'],
            'closing_time' => $branch['closing_time']
        );
        
        header('Content-Type: application/json');
        echo json_encode($formattedBranch);
    }

    // Get services for a specific branch
    // API endpoint: GET /api/branches/{id}/services
    public function services($branchId)
    {
        $branchModel = $this->model('Branch');
        $services = $branchModel->getBranchServices($branchId);
        
        header('Content-Type: application/json');
        echo json_encode($services);
    }

    // Get categories for a specific branch
    // API endpoint: GET /api/branches/{id}/categories
    public function categories($branchId)
    {
        $branchModel = $this->model('Branch');
        $categories = $branchModel->getBranchCategories($branchId);
        
        header('Content-Type: application/json');
        echo json_encode($categories);
    }

    // Get reviews for a specific branch
    // API endpoint: GET /api/branches/{id}/reviews
    public function reviews($branchId)
    {
        $branchModel = $this->model('Branch');

        $reviews = $branchModel->getBranchReviews($branchId);
        $summary = $branchModel->getBranchRatingSummary($branchId);

        header('Content-Type: application/json');
        echo json_encode([
            'summary' => $summary,
            'reviews' => $reviews
        ]);
    }

}

?>
