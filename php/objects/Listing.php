<?php
require_once __DIR__ . '/../Utils/DatabaseConnection.php';

class Listing {
    public $id;
    public $name;
    public $price;
    public $description;
    public $condition_id;
    public $category_id;
    public $seller_id;
    public $is_sold    = 0;
    public $created_at;

    private $db;
    private $conn;

    public function __construct() {
        $this->db   = new DatabaseConnection();
        $this->conn = $this->db->getConnection();
    }

    // ----------------------------------------------------------
    // INSERT a new listing (returns true/false)
    // ----------------------------------------------------------
    public function insert() {
        $sql  = "INSERT INTO listing (name, price, description, condition_id, category_id, seller_id)
                 VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "sdsiis",
            $this->name,
            $this->price,
            $this->description,
            $this->condition_id,
            $this->category_id,
            $this->seller_id
        );
        $success = $stmt->execute();
        if ($success) {
            $this->id = $stmt->insert_id;
        }
        $stmt->close();
        return $success;
    }

    // ----------------------------------------------------------
    // Attach a photo to a listing
    // ----------------------------------------------------------
    public function addPhoto($listingId, $photoUrl, $sortOrder = 0) {
        $sql  = "INSERT INTO listing_photo (listing_id, photo_url, sort_order) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isi", $listingId, $photoUrl, $sortOrder);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    // ----------------------------------------------------------
    // Get all photos for a listing
    // ----------------------------------------------------------
    public function getPhotos($listingId) {
        $sql  = "SELECT * FROM listing_photo WHERE listing_id = ? ORDER BY sort_order ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $listingId);
        $stmt->execute();
        $result = $stmt->get_result();
        $photos = [];
        while ($row = $result->fetch_assoc()) {
            $photos[] = $row;
        }
        $stmt->close();
        return $photos;
    }

    // ----------------------------------------------------------
    // Fetch listings for the Explore page (with filters)
    //   $filters = [
    //     'category_id'  => int|null,
    //     'condition_id' => int|null,
    //     'search'       => string|null,
    //     'sort'         => 'newest'|'price-low'|'price-high',
    //     'limit'        => int,
    //     'offset'       => int,
    //   ]
    // ----------------------------------------------------------
    public function getListings(array $filters = []) {
        $where  = ["l.is_sold = 0"];
        $params = [];
        $types  = "";

        if (!empty($filters['category_id'])) {
            $where[]  = "l.category_id = ?";
            $params[] = (int) $filters['category_id'];
            $types   .= "i";
        }

        if (!empty($filters['condition_id'])) {
            $where[]  = "l.condition_id = ?";
            $params[] = (int) $filters['condition_id'];
            $types   .= "i";
        }

        if (!empty($filters['search'])) {
            $where[]  = "(l.name LIKE ? OR l.description LIKE ?)";
            $pattern  = "%" . $filters['search'] . "%";
            $params[] = $pattern;
            $params[] = $pattern;
            $types   .= "ss";
        }

        $whereClause = implode(" AND ", $where);

        $sort = $filters['sort'] ?? 'newest';
        $orderBy = match($sort) {
            'price-low'  => "l.price ASC",
            'price-high' => "l.price DESC",
            default      => "l.created_at DESC",
        };

        $limit  = (int) ($filters['limit']  ?? 40);
        $offset = (int) ($filters['offset'] ?? 0);

        $sql = "
            SELECT
                l.id,
                l.name,
                l.price,
                l.description,
                l.created_at,
                l.is_sold,
                c.name      AS condition_name,
                c.id        AS condition_id,
                cat.name    AS category_name,
                cat.id      AS category_id,
                u.username  AS seller_username,
                u.id        AS seller_id,
                (
                    SELECT lp.photo_url
                    FROM   listing_photo lp
                    WHERE  lp.listing_id = l.id
                    ORDER  BY lp.sort_order ASC
                    LIMIT  1
                ) AS cover_photo
            FROM      listing     l
            JOIN      `condition` c   ON c.id   = l.condition_id
            LEFT JOIN category    cat ON cat.id  = l.category_id
            JOIN      user        u   ON u.id    = l.seller_id
            WHERE  {$whereClause}
            ORDER  BY {$orderBy}
            LIMIT  ? OFFSET ?
        ";

        $params[] = $limit;
        $params[] = $offset;
        $types   .= "ii";

        $stmt = $this->conn->prepare($sql);
        if ($types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result   = $stmt->get_result();
        $listings = [];
        while ($row = $result->fetch_assoc()) {
            $listings[] = $row;
        }
        $stmt->close();
        return $listings;
    }

    // ----------------------------------------------------------
    // Get a single listing by ID (with photos array)
    // ----------------------------------------------------------
    public function getListingById($id) {
        $sql  = "
            SELECT
                l.*,
                c.name   AS condition_name,
                cat.name AS category_name,
                u.username AS seller_username
            FROM      listing     l
            JOIN      `condition` c   ON c.id  = l.condition_id
            LEFT JOIN category    cat ON cat.id = l.category_id
            JOIN      user        u   ON u.id   = l.seller_id
            WHERE l.id = ?
            LIMIT 1
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $listing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($listing) {
            $listing['photos'] = $this->getPhotos($id);
        }
        return $listing;
    }

    // ----------------------------------------------------------
    // Get all listings by a specific seller
    // ----------------------------------------------------------
    public function getListingsBySeller($sellerId) {
        return $this->getListings(['seller_id_raw' => $sellerId]);
    }

    // ----------------------------------------------------------
    // Mark listing as sold
    // ----------------------------------------------------------
    public function markSold($id) {
        $sql  = "UPDATE listing SET is_sold = 1 WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    // ----------------------------------------------------------
    // Delete a listing (also cascades photos via FK)
    // ----------------------------------------------------------
    public function delete($id) {
        $sql  = "DELETE FROM listing WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
}
?>