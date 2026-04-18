<?php

class ServicesController extends Controller
{
    private $servicesModel;

    public function __construct()
    {
        $this->servicesModel = $this->model('Services');
    }

    // =========================================
    // DEFAULT SERVICES ENDPOINTS
    // =========================================

    // Get all default services
    // API endpoint: GET /services
    public function index()
    {
        $services = $this->servicesModel->getAllDefaultServices();
        
        header('Content-Type: application/json');
        echo json_encode(array(
            'success' => true,
            'data' => $services
        ));
    }

    // Get default service by ID
    // API endpoint: GET /services/{id}
    public function show($id)
    {
        $service = $this->servicesModel->getDefaultServiceById($id);
        
        if (!$service) {
            http_response_code(404);
            echo json_encode(array('success' => false, 'error' => 'Service not found'));
            return;
        }
        
        header('Content-Type: application/json');
        echo json_encode(array(
            'success' => true,
            'data' => $service
        ));
    }

    // Create a new default service
    // API endpoint: POST /services
    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            http_response_code(400);
            echo json_encode(array('success' => false, 'error' => 'Invalid request data'));
            return;
        }

        $requiredFields = ['category_id', 'service_name', 'description', 'duration_minutes', 'price'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(array('success' => false, 'error' => "Missing required field: $field"));
                return;
            }
        }

        $serviceData = array(
            'category_id'      => $data['category_id'],
            'service_name'     => $data['service_name'],
            'description'      => $data['description'],
            'duration_minutes' => $data['duration_minutes'],
            'price'            => $data['price'],
            'is_available'     => $data['is_available'] ?? 1,
            'image_path'       => null   // default; overwritten below if image provided
        );

        // ── Handle base64 image ──
        if (!empty($data['image_base64'])) {
            $imagePath = $this->saveBase64Image($data['image_base64']);
            if ($imagePath) {
                $serviceData['image_path'] = $imagePath;
            }
        }

        $serviceId = $this->servicesModel->createDefaultService($serviceData);

        if ($serviceId) {
            http_response_code(201);
            echo json_encode(array(
                'success' => true,
                'message' => 'Service created successfully',
                'data'    => array('service_id' => $serviceId)
            ));
        } else {
            http_response_code(500);
            echo json_encode(array('success' => false, 'error' => 'Failed to create service'));
        }
    }

    // Create a new default service with branch override
    // API endpoint: POST /services/storeWithBranch
    public function storeWithBranch()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(array('success' => false, 'error' => 'Invalid request data'));
            return;
        }

        $requiredFields = ['category_id', 'service_name', 'description', 'duration_minutes', 'price', 'branch_id'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(array('success' => false, 'error' => "Missing required field: $field"));
                return;
            }
        }

        // Create the default service first
        $serviceData = array(
            'category_id' => $data['category_id'],
            'service_name' => $data['service_name'],
            'description' => $data['description'],
            'duration_minutes' => $data['duration_minutes'],
            'price' => $data['price'],
            'is_available' => $data['is_available'] ?? 1
        );

        $serviceId = $this->servicesModel->createDefaultService($serviceData);
        
        if (!$serviceId) {
            http_response_code(500);
            echo json_encode(array('success' => false, 'error' => 'Failed to create default service'));
            return;
        }

        // Create branch service override for the new service (with values from the default service)
        $overrideData = array(
            'branch_id' => $data['branch_id'],
            'default_service_id' => $serviceId,
            'display_name' => $data['service_name'],
            'description_override' => $data['description'],
            'duration_minutes_override' => $data['duration_minutes'],
            'price_override' => $data['price'],
            'is_available_override' => $data['is_available'] ?? 1
        );

        $overrideId = $this->servicesModel->createBranchServiceOverride($overrideData);
        
        if (!$overrideId || $overrideId === 'duplicate') {
            // Rollback - delete the default service we just created
            $this->servicesModel->deleteDefaultService($serviceId);
            http_response_code(500);
            echo json_encode(array('success' => false, 'error' => 'Failed to create branch service override'));
            return;
        }
        
        http_response_code(201);
        echo json_encode(array(
            'success' => true,
            'message' => 'Service created successfully',
            'data' => array(
                'service_id' => $serviceId,
                'branch_service_override_id' => $overrideId
            )
        ));
    }

    // Update a default service
    // API endpoint: PUT /services/{id}
    public function update($id)
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            http_response_code(400);
            echo json_encode(array('success' => false, 'error' => 'Invalid request data'));
            return;
        }

        $serviceData = array(
            'category_id'      => $data['category_id']      ?? null,
            'service_name'     => $data['service_name']      ?? null,
            'description'      => $data['description']       ?? null,
            'duration_minutes' => $data['duration_minutes']  ?? null,
            'price'            => $data['price']             ?? null,
            'is_available'     => $data['is_available']      ?? null
        );

        // ── Handle base64 image upload ──
        if (!empty($data['image_base64'])) {
            $imagePath = $this->saveBase64Image($data['image_base64']);
            if ($imagePath) {
                // Delete old image file if one exists
                $existing = $this->servicesModel->getDefaultServiceById($id);
                if ($existing && !empty($existing['image_path'])) {
                    $oldFull = dirname(__DIR__, 2) . '/' . ltrim($existing['image_path'], '/');
                    if (file_exists($oldFull)) @unlink($oldFull);
                }
                $serviceData['image_path'] = $imagePath;
            }
        } elseif (isset($data['remove_image']) && $data['remove_image'] == '1') {
            // Superadmin explicitly removed the image
            $existing = $this->servicesModel->getDefaultServiceById($id);
            if ($existing && !empty($existing['image_path'])) {
                $oldFull = dirname(__DIR__, 2) . '/' . ltrim($existing['image_path'], '/');
                if (file_exists($oldFull)) @unlink($oldFull);
            }
            $serviceData['image_path'] = null;
        }

        // Remove null values (fields not sent = not changed), but allow explicit null for image_path
        $serviceData = array_filter($serviceData, function($value) {
            return $value !== null;
        });
        // Re-add image_path = null explicitly if remove was requested
        if (isset($data['remove_image']) && $data['remove_image'] == '1') {
            $serviceData['image_path'] = null;
        }

        if (empty($serviceData)) {
            http_response_code(400);
            echo json_encode(array('success' => false, 'error' => 'No data to update'));
            return;
        }

        $result = $this->servicesModel->updateDefaultService($id, $serviceData);

        if ($result) {
            echo json_encode(array('success' => true, 'message' => 'Service updated successfully'));
        } else {
            http_response_code(500);
            echo json_encode(array('success' => false, 'error' => 'Failed to update service'));
        }
    }

    // Delete a default service
    // API endpoint: DELETE /services/{id}
    public function destroy($id)
    {
        $result = $this->servicesModel->deleteDefaultService($id);
        
        if ($result) {
            echo json_encode(array(
                'success' => true,
                'message' => 'Service deleted successfully'
            ));
        } else {
            http_response_code(500);
            echo json_encode(array('success' => false, 'error' => 'Failed to delete service'));
        }
    }

    // =========================================
    // BRANCH SERVICES ENDPOINTS
    // =========================================

    // Get services for a specific branch
    // API endpoint: GET /services/branch/{branchId}
    public function branchServices($branchId)
    {
        $services = $this->servicesModel->getBranchServices($branchId);

        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                . '://' . $_SERVER['HTTP_HOST'];

        $formattedServices = array_map(function($service) use ($baseUrl) {

            // Branch override image (if any)
            $overridePath = !empty($service['image_path_override']) ? $service['image_path_override'] : null;
            // Global default image (if any)
            $globalPath   = !empty($service['image_path'])          ? $service['image_path']          : null;

            // image_url: override first, then global fallback
            $mergedPath = $overridePath ?? $globalPath;
            $imageUrl       = $mergedPath   ? $baseUrl . '/HFABS/backend/' . ltrim($mergedPath, '/')   : null;
            $globalImageUrl = $globalPath   ? $baseUrl . '/HFABS/backend/' . ltrim($globalPath, '/')   : null;

            return array(
                'branch_service_override_id' => $service['serviceid'],
                'branch_id'                  => $service['branch_id'],
                'default_service_id'         => $service['default_service_id'],
                'display_name'               => $service['servicename'],
                'description'                => $service['description'],
                'price'                      => $service['price'],
                'duration'                   => $service['duration'],
                'category_id'                => $service['category_id'],
                'category'                   => !empty($service['category'])
                                                ? ucfirst(strtolower($service['category'])) . ' Services'
                                                : 'Other Services',
                'is_available'               => $service['isavailable'],
                'image_url'                  => $imageUrl,
                'global_image_url'           => $globalImageUrl,
            );
        }, $services);

        header('Content-Type: application/json');
        echo json_encode(array('success' => true, 'data' => $formattedServices));
    }


    // Get branch service override by ID
    // API endpoint: GET /services/branch-service/{id}
    public function branchServiceShow($id)
    {
        $service = $this->servicesModel->getBranchServiceById($id);
        
        if (!$service) {
            http_response_code(404);
            echo json_encode(array('success' => false, 'error' => 'Branch service not found'));
            return;
        }
        
        header('Content-Type: application/json');
        echo json_encode(array(
            'success' => true,
            'data' => $service
        ));
    }

    // Create branch service override
    // API endpoint: POST /services/branch-service
    public function branchServiceStore()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(array('success' => false, 'error' => 'Invalid request data'));
            return;
        }

        $requiredFields = ['branch_id', 'default_service_id'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(array('success' => false, 'error' => "Missing required field: $field"));
                return;
            }
        }

        $serviceData = array(
            'branch_id' => $data['branch_id'],
            'default_service_id' => $data['default_service_id'],
            'display_name' => $data['display_name'] ?? null,
            'description_override' => $data['description_override'] ?? null,
            'duration_minutes_override' => $data['duration_minutes_override'] ?? null,
            'price_override' => $data['price_override'] ?? null,
            'is_available_override' => $data['is_available_override'] ?? null
        );

        $serviceId = $this->servicesModel->createBranchServiceOverride($serviceData);
        
        if ($serviceId === 'duplicate') {
            http_response_code(409);
            echo json_encode(array('success' => false, 'error' => 'This service already exists for this branch'));
            return;
        }
        
        if ($serviceId) {
            http_response_code(201);
            echo json_encode(array(
                'success' => true,
                'message' => 'Branch service created successfully',
                'data' => array('branch_service_override_id' => $serviceId)
            ));
        } else {
            http_response_code(500);
            echo json_encode(array('success' => false, 'error' => 'Failed to create branch service'));
        }
    }

    // Update branch service override (Admin side)
    // API endpoint: POST /services/branchServiceUpdate/{id}
    public function branchServiceUpdate($id)
    {
        // Detect whether this is multipart/form-data (file upload) or JSON
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $isMultipart = strpos($contentType, 'multipart/form-data') !== false;

        if ($isMultipart) {
            // Data comes from $_POST when multipart
            $data = $_POST;
        } else {
            // Fallback: JSON body (legacy support)
            $data = json_decode(file_get_contents('php://input'), true);
        }

        if (empty($data)) {
            http_response_code(400);
            echo json_encode(array('success' => false, 'error' => 'Invalid request data'));
            return;
        }

        // Build update array from submitted fields
        $serviceData = array();

        if (isset($data['display_name']) && $data['display_name'] !== '') {
            $serviceData['display_name'] = $data['display_name'];
        }
        if (isset($data['description_override'])) {
            $serviceData['description_override'] = $data['description_override'];
        }
        if (isset($data['duration_minutes_override']) && $data['duration_minutes_override'] !== '') {
            $serviceData['duration_minutes_override'] = (int) $data['duration_minutes_override'];
        }
        if (isset($data['price_override']) && $data['price_override'] !== '') {
            $serviceData['price_override'] = (float) $data['price_override'];
        }
        if (isset($data['is_available_override'])) {
            $serviceData['is_available_override'] = (int) $data['is_available_override'];
        }

        // Handle image: file upload takes priority
        if (!empty($_FILES['service_image']) && $_FILES['service_image']['error'] === UPLOAD_ERR_OK) {

            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $maxSize      = 5 * 1024 * 1024; // 5MB

            if ($_FILES['service_image']['size'] > $maxSize) {
                http_response_code(400);
                echo json_encode(array('success' => false, 'error' => 'Image exceeds 5MB limit'));
                return;
            }

            $finfo    = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $_FILES['service_image']['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $allowedTypes)) {
                http_response_code(400);
                echo json_encode(array('success' => false, 'error' => 'Invalid file type. Use JPEG, PNG, WebP or GIF.'));
                return;
            }

            // Delete old override image if it exists
            $existing = $this->servicesModel->getBranchServiceById($id);
            if ($existing && !empty($existing['image_path_override'])) {
                $oldFull = dirname(__DIR__, 2) . '/' . ltrim($existing['image_path_override'], '/');
                if (file_exists($oldFull)) @unlink($oldFull);
            }

            // Save new image
            $uploadDir = dirname(__DIR__, 2) . '/uploads/services/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $ext      = strtolower(pathinfo($_FILES['service_image']['name'], PATHINFO_EXTENSION));
            $filename = 'service_' . uniqid('', true) . '.' . $ext;
            $destPath = $uploadDir . $filename;

            if (!move_uploaded_file($_FILES['service_image']['tmp_name'], $destPath)) {
                http_response_code(500);
                echo json_encode(array('success' => false, 'error' => 'Failed to save image file'));
                return;
            }

            $serviceData['image_path_override'] = 'uploads/services/' . $filename;

        } elseif (isset($data['remove_image']) && $data['remove_image'] == '1') {
            // User explicitly removed the image
            $existing = $this->servicesModel->getBranchServiceById($id);
            if ($existing && !empty($existing['image_path_override'])) {
                $oldFull = dirname(__DIR__, 2) . '/' . ltrim($existing['image_path_override'], '/');
                if (file_exists($oldFull)) @unlink($oldFull);
            }
            $serviceData['image_path_override'] = null;
        }

        if (empty($serviceData)) {
            http_response_code(400);
            echo json_encode(array('success' => false, 'error' => 'No data to update'));
            return;
        }

        $result = $this->servicesModel->updateBranchServiceOverride($id, $serviceData);

        if ($result) {
            echo json_encode(array('success' => true, 'message' => 'Branch service updated successfully'));
        } else {
            http_response_code(500);
            echo json_encode(array('success' => false, 'error' => 'Failed to update branch service'));
        }
    }

    // Update service availability
    // API endpoint: PUT /services/branch-service/{id}/availability
    public function updateAvailability($id)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['is_available'])) {
            http_response_code(400);
            echo json_encode(array('success' => false, 'error' => 'Missing is_available field'));
            return;
        }

        $result = $this->servicesModel->updateServiceAvailability($id, $data['is_available']);
        
        if ($result) {
            echo json_encode(array(
                'success' => true,
                'message' => 'Service availability updated successfully'
            ));
        } else {
            http_response_code(500);
            echo json_encode(array('success' => false, 'error' => 'Failed to update service availability'));
        }
    }

    // Update service price
    // API endpoint: PUT /services/branch-service/{id}/price
    public function updatePrice($id)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['price'])) {
            http_response_code(400);
            echo json_encode(array('success' => false, 'error' => 'Missing price field'));
            return;
        }

        $result = $this->servicesModel->updateServicePrice($id, $data['price']);
        
        if ($result) {
            echo json_encode(array(
                'success' => true,
                'message' => 'Service price updated successfully'
            ));
        } else {
            http_response_code(500);
            echo json_encode(array('success' => false, 'error' => 'Failed to update service price'));
        }
    }

    // Update service duration
    // API endpoint: PUT /services/branch-service/{id}/duration
    public function updateDuration($id)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['duration'])) {
            http_response_code(400);
            echo json_encode(array('success' => false, 'error' => 'Missing duration field'));
            return;
        }

        $result = $this->servicesModel->updateServiceDuration($id, $data['duration']);
        
        if ($result) {
            echo json_encode(array(
                'success' => true,
                'message' => 'Service duration updated successfully'
            ));
        } else {
            http_response_code(500);
            echo json_encode(array('success' => false, 'error' => 'Failed to update service duration'));
        }
    }

    // Delete branch service override
    // API endpoint: DELETE /services/branch-service/{id}
    public function branchServiceDestroy($id)
    {
        $result = $this->servicesModel->deleteBranchServiceOverride($id);
        
        if ($result) {
            echo json_encode(array(
                'success' => true,
                'message' => 'Branch service deleted successfully'
            ));
        } else {
            http_response_code(500);
            echo json_encode(array('success' => false, 'error' => 'Failed to delete branch service'));
        }
    }

    // =========================================
    // CATEGORIES ENDPOINTS
    // =========================================

    // Get all categories
    // API endpoint: GET /services/categories
    public function categories()
    {
        $categories = $this->servicesModel->getAllCategories();
        
        // Format categories to match frontend expected structure
        $formattedCategories = array_map(function($category) {
            return array(
                'service_category_id' => $category['service_category_id'],
                'category_name' => ucfirst(strtolower($category['category_name'])),
                'description' => $category['description'],
                'def_capacity' => $category['def_capacity']
            );
        }, $categories);
        
        header('Content-Type: application/json');
        echo json_encode(array(
            'success' => true,
            'data' => $formattedCategories
        ));
    }

    // Get all categories (alias for categoriesList)
    // API endpoint: GET /services/categoriesList
    public function categoriesList()
    {
        return $this->categories();
    }

    // Get category by ID
    // API endpoint: GET /services/categories/{id}
    public function categoryShow($id)
    {
        $category = $this->servicesModel->getCategoryById($id);
        
        if (!$category) {
            http_response_code(404);
            echo json_encode(array('success' => false, 'error' => 'Category not found'));
            return;
        }
        
        header('Content-Type: application/json');
        echo json_encode(array(
            'success' => true,
            'data' => $category
        ));
    }

    // Create a new category
    // API endpoint: POST /services/categories
    public function categoryStore()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(array('success' => false, 'error' => 'Invalid request data'));
            return;
        }

        $requiredFields = ['category_name', 'description', 'def_capacity'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(array('success' => false, 'error' => "Missing required field: $field"));
                return;
            }
        }

        $categoryData = array(
            'category_name' => $data['category_name'],
            'description' => $data['description'],
            'def_capacity' => $data['def_capacity']
        );

        $categoryId = $this->servicesModel->createCategory($categoryData);
        
        if ($categoryId) {
            http_response_code(201);
            echo json_encode(array(
                'success' => true,
                'message' => 'Category created successfully',
                'data' => array('service_category_id' => $categoryId)
            ));
        } else {
            http_response_code(500);
            echo json_encode(array('success' => false, 'error' => 'Failed to create category'));
        }
    }

    // Update a category
    // API endpoint: PUT /services/categories/{id}
    public function categoryUpdate($id)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(array('success' => false, 'error' => 'Invalid request data'));
            return;
        }

        $categoryData = array(
            'category_name' => $data['category_name'] ?? null,
            'description' => $data['description'] ?? null,
            'def_capacity' => $data['def_capacity'] ?? null
        );

        // Remove null values
        $categoryData = array_filter($categoryData, function($value) {
            return $value !== null;
        });

        if (empty($categoryData)) {
            http_response_code(400);
            echo json_encode(array('success' => false, 'error' => 'No data to update'));
            return;
        }

        $result = $this->servicesModel->updateCategory($id, $categoryData);
        
        if ($result) {
            echo json_encode(array(
                'success' => true,
                'message' => 'Category updated successfully'
            ));
        } else {
            http_response_code(500);
            echo json_encode(array('success' => false, 'error' => 'Failed to update category'));
        }
    }

    // Delete a category
    // API endpoint: DELETE /services/categories/{id}
    public function categoryDestroy($id)
    {
        $result = $this->servicesModel->deleteCategory($id);
        
        if ($result) {
            echo json_encode(array(
                'success' => true,
                'message' => 'Category deleted successfully'
            ));
        } else {
            http_response_code(500);
            echo json_encode(array('success' => false, 'error' => 'Failed to delete category'));
        }
    }

    // =========================================
    // BRANCH CATEGORIES ENDPOINTS
    // =========================================

    // Get categories for a specific branch
    // API endpoint: GET /services/branch-categories/{branchId}
    public function branchCategories($branchId)
    {
        $categories = $this->servicesModel->getBranchCategories($branchId);
        
        // Format categories to match frontend expected structure
        $formattedCategories = array_map(function($category) {
            return array(
                'branch_category_override_id' => $category['categoryid'],
                'branch_id' => $category['branch_id'],
                'default_category_id' => $category['default_category_id'],
                'display_name' => $category['categoryname'],
                'description' => $category['description'],
                'capacity' => $category['capacity'],
                'is_active' => $category['isactive']
            );
        }, $categories);
        
        header('Content-Type: application/json');
        echo json_encode(array(
            'success' => true,
            'data' => $formattedCategories
        ));
    }

    // Get branch category override by ID
    // API endpoint: GET /services/branch-category/{id}
    public function branchCategoryShow($id)
    {
        $category = $this->servicesModel->getBranchCategoryById($id);
        
        if (!$category) {
            http_response_code(404);
            echo json_encode(array('success' => false, 'error' => 'Branch category not found'));
            return;
        }
        
        header('Content-Type: application/json');
        echo json_encode(array(
            'success' => true,
            'data' => $category
        ));
    }

    // Create branch category override
    // API endpoint: POST /services/branch-category
    public function branchCategoryStore()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(array('success' => false, 'error' => 'Invalid request data'));
            return;
        }

        $requiredFields = ['branch_id', 'default_category_id'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(array('success' => false, 'error' => "Missing required field: $field"));
                return;
            }
        }

        $categoryData = array(
            'branch_id' => $data['branch_id'],
            'default_category_id' => $data['default_category_id'],
            'display_name' => $data['display_name'] ?? null,
            'description_override' => $data['description_override'] ?? null,
            'capacity_override' => $data['capacity_override'] ?? null,
            'is_active_override' => $data['is_active_override'] ?? 1
        );

        $categoryId = $this->servicesModel->createBranchCategoryOverride($categoryData);
        
        if ($categoryId) {
            http_response_code(201);
            echo json_encode(array(
                'success' => true,
                'message' => 'Branch category created successfully',
                'data' => array('branch_category_override_id' => $categoryId)
            ));
        } else {
            http_response_code(500);
            echo json_encode(array('success' => false, 'error' => 'Failed to create branch category'));
        }
    }

    // Update branch category override
    // API endpoint: PUT /services/branch-category/{id}
    public function branchCategoryUpdate($id)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(array('success' => false, 'error' => 'Invalid request data'));
            return;
        }

        $categoryData = array(
            'display_name' => $data['display_name'] ?? null,
            'description_override' => $data['description_override'] ?? null,
            'capacity_override' => $data['capacity_override'] ?? null,
            'is_active_override' => $data['is_active_override'] ?? null
        );

        // Remove null values
        $categoryData = array_filter($categoryData, function($value) {
            return $value !== null;
        });

        if (empty($categoryData)) {
            http_response_code(400);
            echo json_encode(array('success' => false, 'error' => 'No data to update'));
            return;
        }

        $result = $this->servicesModel->updateBranchCategoryOverride($id, $categoryData);
        
        if ($result) {
            echo json_encode(array(
                'success' => true,
                'message' => 'Branch category updated successfully'
            ));
        } else {
            http_response_code(500);
            echo json_encode(array('success' => false, 'error' => 'Failed to update branch category'));
        }
    }

    // Update category capacity for a branch
    // API endpoint: PUT /services/branch-category/{id}/capacity
    public function updateCapacity($id)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['capacity'])) {
            http_response_code(400);
            echo json_encode(array('success' => false, 'error' => 'Missing capacity field'));
            return;
        }

        $result = $this->servicesModel->updateCategoryCapacity($id, $data['capacity']);
        
        if ($result) {
            echo json_encode(array(
                'success' => true,
                'message' => 'Category capacity updated successfully'
            ));
        } else {
            http_response_code(500);
            echo json_encode(array('success' => false, 'error' => 'Failed to update category capacity'));
        }
    }

    // Delete branch category override
    // API endpoint: DELETE /services/branch-category/{id}
    public function branchCategoryDestroy($id)
    {
        $result = $this->servicesModel->deleteBranchCategoryOverride($id);
        
        if ($result) {
            echo json_encode(array(
                'success' => true,
                'message' => 'Branch category deleted successfully'
            ));
        } else {
            http_response_code(500);
            echo json_encode(array('success' => false, 'error' => 'Failed to delete branch category'));
        }
    }

    // ── GET ?url=services/dateCapacities ──
    public function dateCapacities()
    {
        header('Content-Type: application/json');
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'cashier'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $branchId = (int) ($_SESSION['branch_id'] ?? 0);
        $data     = $this->servicesModel->getDateCapacities($branchId);

        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    // ── POST ?url=services/saveDateCapacity ──
    public function saveDateCapacity()
    {
        header('Content-Type: application/json');
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $input    = json_decode(file_get_contents('php://input'), true);
        $branchId = (int) ($_SESSION['branch_id'] ?? 0);

        $branchCategoryOverrideId = (int) ($input['branch_category_override_id'] ?? 0);
        $date     = trim($input['override_date'] ?? '');
        $capacity = (int) ($input['capacity_override'] ?? 0);
        $reason   = trim($input['reason'] ?? '');

        if (!$branchCategoryOverrideId || !$date || $capacity < 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing or invalid fields: branch_category_override_id, override_date, capacity_override']);
            exit;
        }

        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid date format. Use YYYY-MM-DD.']);
            exit;
        }

        // Reject past dates
        if ($date < date('Y-m-d')) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Cannot set capacity for a past date.']);
            exit;
        }

        $result = $this->servicesModel->saveDateCapacity(
            $branchId, $branchCategoryOverrideId, $date, $capacity, $reason ?: null
        );

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Date capacity saved successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to save date capacity.']);
        }
        exit;
    }

    // ── POST ?url=services/removeDateCapacity ──
    public function removeDateCapacity()
    {
        header('Content-Type: application/json');
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $input    = json_decode(file_get_contents('php://input'), true);
        $id       = (int) ($input['id'] ?? 0);
        $branchId = (int) ($_SESSION['branch_id'] ?? 0);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing id.']);
            exit;
        }

        $result = $this->servicesModel->removeDateCapacity($id, $branchId);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Date capacity removed.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to remove date capacity.']);
        }
        exit;
    }

    private function saveBase64Image($base64String)
    {
        // Strip data URI prefix: "data:image/jpeg;base64,..."
        if (strpos($base64String, ',') !== false) {
            list($meta, $base64String) = explode(',', $base64String, 2);
        }

        $imageData = base64_decode($base64String);
        if (!$imageData) return null;

        // Detect extension from MIME in the data URI header (default jpeg)
        $ext = 'jpg';
        if (isset($meta) && preg_match('/image\/(\w+)/', $meta, $matches)) {
            $extMap = ['jpeg' => 'jpg', 'jpg' => 'jpg', 'png' => 'png', 'webp' => 'webp', 'gif' => 'gif'];
            $ext = $extMap[strtolower($matches[1])] ?? 'jpg';
        }

        $uploadDir = dirname(__DIR__, 2) . '/uploads/services/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

        $filename = 'service_' . uniqid('', true) . '.' . $ext;
        $fullPath = $uploadDir . $filename;

        if (file_put_contents($fullPath, $imageData) === false) return null;

        return 'uploads/services/' . $filename;
    }
}