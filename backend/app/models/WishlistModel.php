<?php

class WishlistModel
{
    private $db;

    public function __construct()
    {
        $database    = new Database();
        $this->db    = $database->getConnection();
    }

    // ─────────────────────────────────────────────
    // Toggle wishlist (add if not exists, remove if exists)
    // Returns: 'added' | 'removed' | false
    // ─────────────────────────────────────────────
    public function toggle(array $data)
    {
        $userId  = (int) $data['user_id'];
        $branchId = (int) $data['branch_id'];
        $type    = $data['wishlist_type']; // 'service' or 'package'

        if ($type === 'service') {
            $bsoId = (int) $data['branch_service_override_id'];
            $dsId  = (int) $data['default_service_id'];

            // Check if exists
            $checkSql = 'SELECT wishlist_id FROM customer_wishlists 
                         WHERE user_id = ? AND branch_service_override_id = ?';
            $stmt = $this->db->prepare($checkSql);
            $stmt->bind_param('ii', $userId, $bsoId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Remove
                $row = $result->fetch_assoc();
                $delSql = 'DELETE FROM customer_wishlists WHERE wishlist_id = ?';
                $delStmt = $this->db->prepare($delSql);
                $delStmt->bind_param('i', $row['wishlist_id']);
                return $delStmt->execute() ? 'removed' : false;
            } else {
                // Add
                $insSql = 'INSERT INTO customer_wishlists 
                           (user_id, branch_id, wishlist_type, default_service_id, branch_service_override_id)
                           VALUES (?, ?, "service", ?, ?)';
                $insStmt = $this->db->prepare($insSql);
                $insStmt->bind_param('iiii', $userId, $branchId, $dsId, $bsoId);
                return $insStmt->execute() ? 'added' : false;
            }

        } elseif ($type === 'package') {
            $packageId = (int) $data['package_id'];

            // Check if exists
            $checkSql = 'SELECT wishlist_id FROM customer_wishlists 
                         WHERE user_id = ? AND package_id = ?';
            $stmt = $this->db->prepare($checkSql);
            $stmt->bind_param('ii', $userId, $packageId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Remove
                $row = $result->fetch_assoc();
                $delSql = 'DELETE FROM customer_wishlists WHERE wishlist_id = ?';
                $delStmt = $this->db->prepare($delSql);
                $delStmt->bind_param('i', $row['wishlist_id']);
                return $delStmt->execute() ? 'removed' : false;
            } else {
                // Add
                $insSql = 'INSERT INTO customer_wishlists 
                           (user_id, branch_id, wishlist_type, package_id)
                           VALUES (?, ?, "package", ?)';
                $insStmt = $this->db->prepare($insSql);
                $insStmt->bind_param('iii', $userId, $branchId, $packageId);
                return $insStmt->execute() ? 'added' : false;
            }
        }

        return false;
    }

    // ─────────────────────────────────────────────
    // Get wishlist status for a user at a branch
    // Returns arrays of wishlisted service IDs and package IDs
    // ─────────────────────────────────────────────
    public function getUserWishlistForBranch(int $userId, int $branchId): array
    {
        $sql = 'SELECT wishlist_type, branch_service_override_id, package_id
                FROM customer_wishlists
                WHERE user_id = ? AND branch_id = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $userId, $branchId);
        $stmt->execute();
        $result = $stmt->get_result();

        $serviceIds = [];
        $packageIds = [];
        while ($row = $result->fetch_assoc()) {
            if ($row['wishlist_type'] === 'service' && $row['branch_service_override_id']) {
                $serviceIds[] = (int) $row['branch_service_override_id'];
            }
            if ($row['wishlist_type'] === 'package' && $row['package_id']) {
                $packageIds[] = (int) $row['package_id'];
            }
        }

        return ['service_ids' => $serviceIds, 'package_ids' => $packageIds];
    }

    // ─────────────────────────────────────────────
    // Get ALL wishlisted items for a specific user
    // ─────────────────────────────────────────────
   public function getUserAllWishlists(int $userId): array
  {
      $sql = 'SELECT 
                  cw.wishlist_id,
                  cw.branch_id,
                  cw.wishlist_type,
                  cw.branch_service_override_id,
                  cw.package_id,
                  cw.created_at,
                  b.branch_name,

                  -- Service fields (override takes priority over default)
                  COALESCE(NULLIF(bso.display_name, ""), ds.service_name)             AS service_name,
                  COALESCE(bso.description_override, ds.description)                  AS service_description,
                  COALESCE(bso.image_path_override, ds.image_path)                    AS service_image,
                  COALESCE(bso.price_override, ds.price)                              AS service_price,
                  COALESCE(bso.duration_minutes_override, ds.duration_minutes)        AS service_duration,

                  -- Package fields
                  bp.package_name,
                  bp.description                                                       AS package_description,
                  bp.package_price,
                  bp.total_duration_minutes                                            AS package_duration

              FROM customer_wishlists cw
              LEFT JOIN branch b
                    ON cw.branch_id = b.branch_id
              LEFT JOIN branch_service_overrides bso
                    ON cw.branch_service_override_id = bso.branch_service_override_id
              LEFT JOIN default_services ds
                    ON bso.default_service_id = ds.service_id
              LEFT JOIN branch_packages bp
                    ON cw.package_id = bp.package_id
              WHERE cw.user_id = ?
              ORDER BY cw.created_at DESC';

      $stmt = $this->db->prepare($sql);
      $stmt->bind_param('i', $userId);
      $stmt->execute();
      $result = $stmt->get_result();
      $rows = [];
      while ($row = $result->fetch_assoc()) {
          $rows[] = $row;
      }
      return $rows;
  }

    // ─────────────────────────────────────────────
    // Admin: Wishlist count per service per branch
    // ─────────────────────────────────────────────
    public function getServiceWishlistCounts(int $branchId): array
    {
        $sql = 'SELECT 
                    bso.branch_service_override_id,
                    COALESCE(NULLIF(bso.display_name,""), ds.service_name) AS service_name,
                    COUNT(cw.wishlist_id) AS wishlist_count
                FROM branch_service_overrides bso
                LEFT JOIN default_services ds ON bso.default_service_id = ds.service_id
                LEFT JOIN customer_wishlists cw 
                       ON cw.branch_service_override_id = bso.branch_service_override_id
                       AND cw.wishlist_type = "service"
                WHERE bso.branch_id = ?
                GROUP BY bso.branch_service_override_id, service_name
                ORDER BY wishlist_count DESC, service_name ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $branchId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    // ─────────────────────────────────────────────
    // Admin: Wishlist count per package per branch
    // ─────────────────────────────────────────────
    public function getPackageWishlistCounts(int $branchId): array
    {
        $sql = 'SELECT 
                    bp.package_id,
                    bp.package_name,
                    COUNT(cw.wishlist_id) AS wishlist_count
                FROM branch_packages bp
                LEFT JOIN customer_wishlists cw 
                       ON cw.package_id = bp.package_id
                       AND cw.wishlist_type = "package"
                WHERE bp.branch_id = ?
                GROUP BY bp.package_id, bp.package_name
                ORDER BY wishlist_count DESC, bp.package_name ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $branchId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    // ─────────────────────────────────────────────
    // Admin: Summary totals for dashboard KPI
    // ─────────────────────────────────────────────
    public function getWishlistSummary(int $branchId): array
    {
        $sql = 'SELECT 
                    wishlist_type,
                    COUNT(*) AS total
                FROM customer_wishlists
                WHERE branch_id = ?
                GROUP BY wishlist_type';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $branchId);
        $stmt->execute();
        $result = $stmt->get_result();
        $summary = ['service' => 0, 'package' => 0];
        while ($row = $result->fetch_assoc()) {
            $summary[$row['wishlist_type']] = (int) $row['total'];
        }
        return $summary;
    }
}