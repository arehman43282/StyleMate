<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't show errors in output, log them instead
header('Content-Type: application/json');

function log_error($msg) {
    file_put_contents('skin_debug.log', "[" . date('Y-m-d H:i:s') . "] " . $msg . "\n", FILE_APPEND);
}

try {
    require_once 'db_connect.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['fetch_products_only'])) {
            $db_colors = json_decode($_POST['colors'], true);
            $gender = $_POST['gender'] ?? null;
            
            if (!$gender) {
                throw new Exception('Gender parameter is missing.');
            }
            
            $genderFilter = ($gender === 'Boy') ? 'Men' : 'Women';
            $regex = !empty($db_colors) ? implode('|', $db_colors) : 'nothing_matches_this_regex';
            
            $query = "SELECT p.Products_ID, p.Products_Name, p.price, p.image 
                      FROM Products p 
                      JOIN Categories c ON p.Categories_ID = c.Categories_ID 
                      WHERE (p.Products_Name REGEXP ? OR p.Description REGEXP ?) 
                      AND p.Status = 1 
                      AND c.Category_Group = ? 
                      ORDER BY RAND() LIMIT 12";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sss", $regex, $regex, $genderFilter);
            $stmt->execute();
            $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // REMOVED Fallback query that mixed genders
            
            shuffle($products);
            echo json_encode(['success' => true, 'products' => array_slice($products, 0, 12)]);
            exit;
        }

        if (isset($_POST['image'])) {
            $data = $_POST['image'];
            if (strpos($data, ';base64,') === false) {
                throw new Exception('Invalid image format');
            }
            $image_parts = explode(";base64,", $data);
            $image_base64 = base64_decode($image_parts[1]);
            
            $temp_dir = 'temp_uploads';
            if (!file_exists($temp_dir)) {
                if (!mkdir($temp_dir, 0777, true)) {
                    throw new Exception('Failed to create temp directory');
                }
            }
            
            $file_name = $temp_dir . '/skin_' . time() . '.jpg';
            if (file_put_contents($file_name, $image_base64) === false) {
                throw new Exception('Failed to save temp image');
            }
            
            $command = "python3 analyze_skin.py " . escapeshellarg($file_name) . " 2>&1";
            $output = shell_exec($command);
            log_error("Command: $command\nOutput: $output");
            
            $json_start = strpos($output, '{');
            $json_end = strrpos($output, '}');
            
            if ($json_start !== false && $json_end !== false) {
                $json_str = substr($output, $json_start, $json_end - $json_start + 1);
                $result = json_decode($json_str, true);
            } else {
                throw new Exception('Python script returned invalid JSON: ' . $output);
            }
            
            if (file_exists($file_name)) unlink($file_name);
            
            if ($result && isset($result['success']) && $result['success']) {
                $db_colors = $result['db_colors'];
                $gender = $result['gender']; // Removed default 'Boy' - Python ensures gender or fails
                
                if (!$gender) {
                     throw new Exception('Gender not detected by AI.');
                }

                $genderFilter = ($gender === 'Boy') ? 'Men' : 'Women';
                $regex = !empty($db_colors) ? implode('|', $db_colors) : 'nothing_matches_this_regex';
                
                $query = "SELECT p.Products_ID, p.Products_Name, p.price, p.image 
                          FROM Products p 
                          JOIN Categories c ON p.Categories_ID = c.Categories_ID 
                          WHERE (p.Products_Name REGEXP ? OR p.Description REGEXP ?) 
                          AND p.Status = 1 
                          AND c.Category_Group = ? 
                          ORDER BY RAND() LIMIT 12";
                
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sss", $regex, $regex, $genderFilter);
                $stmt->execute();
                $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                
                // REMOVED Fallback query
                
                shuffle($products);
                $result['products'] = array_slice($products, 0, 12);
                
                $final_json = json_encode($result);
                if ($final_json === false) {
                    throw new Exception('JSON Encode Error: ' . json_last_error_msg());
                }
                echo $final_json;
            } else {
                throw new Exception($result['message'] ?? 'AI analysis failed');
            }
        } else {
            throw new Exception('No image data provided');
        }
    } else {
        throw new Exception('Invalid request method');
    }
} catch (Exception $e) {
    log_error("Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
