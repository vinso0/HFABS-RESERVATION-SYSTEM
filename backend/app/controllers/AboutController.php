<?php

class AboutController extends Controller
{
    // ── GET: Fetch About content ──────────────────────────────────────────────
    public function getAbout()
    {
        header('Content-Type: application/json');
        $aboutModel = $this->model('About');
        $data = $aboutModel->getAbout();
        echo json_encode(['success' => true, 'data' => $data]);
    }

    // ── GET: Fetch active policies (customer-facing) ───────────────────────────
    public function getPolicies()
    {
        header('Content-Type: application/json');
        $aboutModel = $this->model('About');
        $data = $aboutModel->getPolicies();
        echo json_encode(['success' => true, 'data' => $data]);
    }

    // ── GET: Fetch all policies including hidden (Superadmin) ─────────────────
    public function getAllPolicies()
    {
        header('Content-Type: application/json');
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        $aboutModel = $this->model('About');
        $data = $aboutModel->getAllPolicies();
        echo json_encode(['success' => true, 'data' => $data]);
    }

    // ── POST: Save About content (Superadmin only) ────────────────────────────
    public function saveAbout()
    {
        header('Content-Type: application/json');
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $input       = json_decode(file_get_contents('php://input'), true);
        $title       = trim($input['title']       ?? '');
        $description = trim($input['description'] ?? '');
        $vision      = trim($input['vision']      ?? '');
        $mission     = trim($input['mission']     ?? '');

        if (empty($description)) {
            echo json_encode(['success' => false, 'message' => 'Description is required.']);
            return;
        }

        $aboutModel = $this->model('About');
        $result     = $aboutModel->saveAbout($title, $description, $vision, $mission);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'About content saved successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to save about content.']);
        }
    }

    // ── POST: Add a new policy (Superadmin only) ──────────────────────────────
    public function addPolicy()
    {
        header('Content-Type: application/json');
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $input      = json_decode(file_get_contents('php://input'), true);
        $title      = trim($input['title']      ?? '');
        $content    = trim($input['content']    ?? '');
        $sort_order = intval($input['sort_order'] ?? 0);

        if (empty($title) || empty($content)) {
            echo json_encode(['success' => false, 'message' => 'Title and content are required.']);
            return;
        }

        $aboutModel = $this->model('About');
        $newId      = $aboutModel->addPolicy($title, $content, $sort_order);

        echo json_encode(['success' => true, 'message' => 'Policy added.', 'policy_id' => $newId]);
    }

    // ── POST: Update an existing policy (Superadmin only) ────────────────────
    public function updatePolicy()
    {
        header('Content-Type: application/json');
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $input      = json_decode(file_get_contents('php://input'), true);
        $id         = intval($input['policy_id']   ?? 0);
        $title      = trim($input['title']         ?? '');
        $content    = trim($input['content']       ?? '');
        $sort_order = intval($input['sort_order']  ?? 0);
        $is_active  = intval($input['is_active']   ?? 1);

        if (!$id || empty($title) || empty($content)) {
            echo json_encode(['success' => false, 'message' => 'Policy ID, title, and content are required.']);
            return;
        }

        $aboutModel = $this->model('About');
        $result     = $aboutModel->updatePolicy($id, $title, $content, $sort_order, $is_active);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Policy updated.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update policy.']);
        }
    }

    // ── POST: Delete a policy (Superadmin only) ───────────────────────────────
    public function deletePolicy()
    {
        header('Content-Type: application/json');
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $id    = intval($input['policy_id'] ?? 0);

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Policy ID is required.']);
            return;
        }

        $aboutModel = $this->model('About');
        $result     = $aboutModel->deletePolicy($id);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Policy deleted.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to delete policy.']);
        }
    }
}