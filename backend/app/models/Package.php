<?php
class Package extends Database
{
    public function __construct()
    {
        parent::__construct();
    }

    private function cleanServiceIds($serviceIds)
    {
        if (!is_array($serviceIds)) {
            return [];
        }

        $clean = [];
        foreach ($serviceIds as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $clean[] = $id;
            }
        }

        return array_values(array_unique($clean));
    }

    public function getBranchServices($branchId)
    {
        $sql = "
            SELECT
                bso.branch_service_override_id,
                COALESCE(NULLIF(bso.display_name, ''), ds.service_name) AS service_name,
                COALESCE(bso.duration_minutes_override, ds.duration_minutes) AS duration_minutes,
                COALESCE(bso.price_override, ds.price) AS service_price
            FROM branch_service_overrides bso
            INNER JOIN default_services ds
                ON ds.service_id = bso.default_service_id
            WHERE bso.branch_id = ?
              AND COALESCE(bso.is_available_override, ds.is_available) = 1
            ORDER BY service_name ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $branchId);
        $stmt->execute();
        $result = $stmt->get_result();

        $services = [];
        while ($row = $result->fetch_assoc()) {
            $services[] = $row;
        }

        $stmt->close();
        return $services;
    }

    public function getAllPackages($branchId)
    {
        $sql = "
            SELECT
                bp.package_id,
                bp.branch_id,
                bp.package_name,
                bp.description,
                bp.package_price,
                bp.total_duration_minutes,
                bp.is_available,
                GROUP_CONCAT(
                    COALESCE(NULLIF(bso.display_name, ''), ds.service_name)
                    ORDER BY bps.sort_order ASC
                    SEPARATOR ', '
                ) AS included_services,
                COUNT(bps.branch_service_override_id) AS service_count
            FROM branch_packages bp
            LEFT JOIN branch_package_services bps
                ON bp.package_id = bps.package_id
            LEFT JOIN branch_service_overrides bso
                ON bps.branch_service_override_id = bso.branch_service_override_id
            LEFT JOIN default_services ds
                ON ds.service_id = bso.default_service_id
            WHERE bp.branch_id = ?
            GROUP BY
                bp.package_id,
                bp.branch_id,
                bp.package_name,
                bp.description,
                bp.package_price,
                bp.total_duration_minutes,
                bp.is_available
            ORDER BY bp.package_name ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $branchId);
        $stmt->execute();
        $result = $stmt->get_result();

        $packages = [];
        while ($row = $result->fetch_assoc()) {
            $packages[] = $row;
        }

        $stmt->close();
        return $packages;
    }

    public function getPackageById($packageId, $branchId)
    {
        $sql = "
            SELECT
                package_id,
                branch_id,
                package_name,
                description,
                package_price,
                total_duration_minutes,
                is_available
            FROM branch_packages
            WHERE package_id = ? AND branch_id = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $packageId, $branchId);
        $stmt->execute();
        $result = $stmt->get_result();
        $package = $result->fetch_assoc();
        $stmt->close();

        if (!$package) {
            return null;
        }

        $serviceSql = "
            SELECT branch_service_override_id
            FROM branch_package_services
            WHERE package_id = ?
            ORDER BY sort_order ASC
        ";

        $serviceStmt = $this->db->prepare($serviceSql);
        $serviceStmt->bind_param('i', $packageId);
        $serviceStmt->execute();
        $serviceResult = $serviceStmt->get_result();

        $selectedServices = [];
        while ($row = $serviceResult->fetch_assoc()) {
            $selectedServices[] = (int)$row['branch_service_override_id'];
        }

        $serviceStmt->close();

        $package['selected_services'] = $selectedServices;
        return $package;
    }

    private function packageExistsInBranch($packageId, $branchId)
    {
        $sql = "SELECT package_id FROM branch_packages WHERE package_id = ? AND branch_id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $packageId, $branchId);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    private function validateSelectedServices($branchId, $serviceIds)
    {
        $serviceIds = $this->cleanServiceIds($serviceIds);

        if (count($serviceIds) < 2) {
            return [
                'success' => false,
                'message' => 'A package must contain at least 2 services.'
            ];
        }

        $idsList = implode(',', $serviceIds);

        $sql = "
            SELECT COUNT(*) AS total
            FROM branch_service_overrides
            WHERE branch_id = ?
              AND branch_service_override_id IN ($idsList)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $branchId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ((int)$result['total'] !== count($serviceIds)) {
            return [
                'success' => false,
                'message' => 'All selected services must belong to the same branch as the package.'
            ];
        }

        return [
            'success' => true,
            'service_ids' => $serviceIds
        ];
    }

    private function computeDurationMinutes($branchId, $serviceIds)
    {
        $serviceIds = $this->cleanServiceIds($serviceIds);
        if (empty($serviceIds)) {
            return 0;
        }

        $idsList = implode(',', $serviceIds);

        $sql = "
            SELECT SUM(
                COALESCE(bso.duration_minutes_override, ds.duration_minutes)
            ) AS total_duration
            FROM branch_service_overrides bso
            INNER JOIN default_services ds
                ON ds.service_id = bso.default_service_id
            WHERE bso.branch_id = ?
              AND bso.branch_service_override_id IN ($idsList)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $branchId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int)($result['total_duration'] ?? 0);
    }

    private function syncPackageServices($packageId, $serviceIds)
    {
        $deleteSql = "DELETE FROM branch_package_services WHERE package_id = ?";
        $deleteStmt = $this->db->prepare($deleteSql);
        $deleteStmt->bind_param('i', $packageId);
        $deleteStmt->execute();
        $deleteStmt->close();

        $insertSql = "
            INSERT INTO branch_package_services
                (package_id, branch_service_override_id, sort_order)
            VALUES (?, ?, ?)
        ";

        $insertStmt = $this->db->prepare($insertSql);

        $sortOrder = 1;
        foreach ($serviceIds as $serviceId) {
            $insertStmt->bind_param('iii', $packageId, $serviceId, $sortOrder);
            $insertStmt->execute();
            $sortOrder++;
        }

        $insertStmt->close();
    }

    public function createPackage($branchId, $payload)
    {
        $packageName = trim($payload['package_name'] ?? '');
        $description = trim($payload['description'] ?? '');
        $packagePrice = (float)($payload['package_price'] ?? 0);
        $isAvailable = isset($payload['is_available']) ? (int)$payload['is_available'] : 1;
        $serviceIds = $payload['service_ids'] ?? [];

        if ($packageName === '') {
            return ['success' => false, 'message' => 'Package name is required.'];
        }

        if ($packagePrice < 0) {
            return ['success' => false, 'message' => 'Package price must be 0 or higher.'];
        }

        $validation = $this->validateSelectedServices($branchId, $serviceIds);
        if (!$validation['success']) {
            return $validation;
        }

        $serviceIds = $validation['service_ids'];
        $totalDuration = $this->computeDurationMinutes($branchId, $serviceIds);

        $this->db->begin_transaction();

        try {
            $sql = "
                INSERT INTO branch_packages
                    (branch_id, package_name, description, package_price, total_duration_minutes, is_available)
                VALUES (?, ?, ?, ?, ?, ?)
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param(
                'issdii',
                $branchId,
                $packageName,
                $description,
                $packagePrice,
                $totalDuration,
                $isAvailable
            );
            $stmt->execute();
            $packageId = $this->db->insert_id;
            $stmt->close();

            $this->syncPackageServices($packageId, $serviceIds);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Package created successfully.'
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'Failed to create package: ' . $e->getMessage()
            ];
        }
    }

    public function updatePackage($branchId, $payload)
    {
        $packageId = (int)($payload['package_id'] ?? 0);
        $packageName = trim($payload['package_name'] ?? '');
        $description = trim($payload['description'] ?? '');
        $packagePrice = (float)($payload['package_price'] ?? 0);
        $isAvailable = isset($payload['is_available']) ? (int)$payload['is_available'] : 1;
        $serviceIds = $payload['service_ids'] ?? [];

        if ($packageId <= 0) {
            return ['success' => false, 'message' => 'Invalid package ID.'];
        }

        if (!$this->packageExistsInBranch($packageId, $branchId)) {
            return ['success' => false, 'message' => 'Package not found for this branch.'];
        }

        if ($packageName === '') {
            return ['success' => false, 'message' => 'Package name is required.'];
        }

        if ($packagePrice < 0) {
            return ['success' => false, 'message' => 'Package price must be 0 or higher.'];
        }

        $validation = $this->validateSelectedServices($branchId, $serviceIds);
        if (!$validation['success']) {
            return $validation;
        }

        $serviceIds = $validation['service_ids'];
        $totalDuration = $this->computeDurationMinutes($branchId, $serviceIds);

        $this->db->begin_transaction();

        try {
            $sql = "
                UPDATE branch_packages
                SET
                    package_name = ?,
                    description = ?,
                    package_price = ?,
                    total_duration_minutes = ?,
                    is_available = ?
                WHERE package_id = ? AND branch_id = ?
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param(
                'ssdiiii',
                $packageName,
                $description,
                $packagePrice,
                $totalDuration,
                $isAvailable,
                $packageId,
                $branchId
            );
            $stmt->execute();
            $stmt->close();

            $this->syncPackageServices($packageId, $serviceIds);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Package updated successfully.'
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'Failed to update package: ' . $e->getMessage()
            ];
        }
    }

    public function deletePackage($branchId, $packageId)
    {
        if (!$this->packageExistsInBranch($packageId, $branchId)) {
            return ['success' => false, 'message' => 'Package not found for this branch.'];
        }

        $hasBookedPackageColumn = false;
        $columnCheck = $this->db->query("SHOW COLUMNS FROM reservation_services LIKE 'booked_package_id'");
        if ($columnCheck && $columnCheck->num_rows > 0) {
            $hasBookedPackageColumn = true;
        }

        if ($hasBookedPackageColumn) {
            $usageSql = "SELECT COUNT(*) AS total FROM reservation_services WHERE booked_package_id = ?";
            $usageStmt = $this->db->prepare($usageSql);
            $usageStmt->bind_param('i', $packageId);
            $usageStmt->execute();
            $usageResult = $usageStmt->get_result()->fetch_assoc();
            $usageStmt->close();

            if ((int)$usageResult['total'] > 0) {
                return [
                    'success' => false,
                    'message' => 'This package is already used in reservations and cannot be deleted.'
                ];
            }
        }

        $this->db->begin_transaction();

        try {
            $deleteServicesSql = "DELETE FROM branch_package_services WHERE package_id = ?";
            $deleteServicesStmt = $this->db->prepare($deleteServicesSql);
            $deleteServicesStmt->bind_param('i', $packageId);
            $deleteServicesStmt->execute();
            $deleteServicesStmt->close();

            $deletePackageSql = "DELETE FROM branch_packages WHERE package_id = ? AND branch_id = ?";
            $deletePackageStmt = $this->db->prepare($deletePackageSql);
            $deletePackageStmt->bind_param('ii', $packageId, $branchId);
            $deletePackageStmt->execute();
            $deletePackageStmt->close();

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Package deleted successfully.'
            ];
        } catch (Exception $e) {
            $this->db->rollback();

            return [
                'success' => false,
                'message' => 'Failed to delete package: ' . $e->getMessage()
            ];
        }
    }

    public function getAvailablePackagesForBranch($branchId)
    {
        $sql = "
            SELECT
                bp.package_id,
                bp.package_name,
                bp.description,
                bp.package_price,
                bp.total_duration_minutes,
                bp.is_available,
                GROUP_CONCAT(
                    COALESCE(NULLIF(bso.display_name, ''), ds.service_name)
                    ORDER BY bps.sort_order ASC
                    SEPARATOR ', '
                ) AS included_services,
                COUNT(bps.branch_service_override_id) AS service_count
            FROM branch_packages bp
            LEFT JOIN branch_package_services bps
                ON bp.package_id = bps.package_id
            LEFT JOIN branch_service_overrides bso
                ON bps.branch_service_override_id = bso.branch_service_override_id
            LEFT JOIN default_services ds
                ON ds.service_id = bso.default_service_id
            WHERE bp.branch_id = ?
            AND bp.is_available = 1
            GROUP BY
                bp.package_id,
                bp.package_name,
                bp.description,
                bp.package_price,
                bp.total_duration_minutes,
                bp.is_available
            ORDER BY bp.package_name ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $branchId);
        $stmt->execute();
        $result = $stmt->get_result();

        $packages = [];
        while ($row = $result->fetch_assoc()) {
            $packages[] = $row;
        }

        $stmt->close();
        return $packages;
    }
}
