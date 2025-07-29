<?php
include("../conection.php");

// Verify if running in CLI or via web with valid key
$is_cli = (php_sapi_name() === 'cli');
if (!$is_cli && !isset($authorized_web_call)) {
    die('Invalid access method');
}

// Set execution parameters
ini_set('memory_limit', '-1');
set_time_limit(0);
date_default_timezone_set('UTC');

// Get current datetime
$currentDateTime = date("Y-m-d H:i:s");
$currentDate = date("Y-m-d");

// Log start of process
error_log("Daily ROI Processing started at: $currentDateTime");

// 1. Process ROI for active investments
$investmentsQuery = "SELECT ui.id, ui.user_id, ui.invested_amount, ui.rol_given_days, 
                    ui.total_rol_days, p.package as roi_percent, p.packageId as plan_id
                    FROM user_investments ui
                    JOIN meddolic_config_package_type p ON ui.plan_id = p.packageId
                    WHERE ui.status = 'active'
                    AND '$currentDate' BETWEEN ui.start_date AND ui.end_date
                    AND ui.rol_given_days < ui.total_rol_days";

$investmentsResult = mysqli_query($con, $investmentsQuery);

if (!$investmentsResult) {
    error_log("Error fetching investments: " . mysqli_error($con));
} else {
    while ($investment = mysqli_fetch_assoc($investmentsResult)) {
        // Calculate daily ROI (using package price as ROI percent)
        $roiAmount = ($investment['invested_amount'] * $investment['roi_percent']) / 100;
        
        // Insert ROI record
        $insertQuery = "INSERT INTO roi_income (user_id, investment_id, roi_date, roi_amount, planId, plan_amount, roi_percent, roi_days)
                       VALUES ({$investment['user_id']}, {$investment['id']}, '$currentDate', $roiAmount, 
                       {$investment['plan_id']}, {$investment['invested_amount']}, 
                       {$investment['roi_percent']}, {$investment['total_rol_days']})";
        
        if (!mysqli_query($con, $insertQuery)) {
            error_log("Error inserting ROI record: " . mysqli_error($con));
            continue;
        }
        
        // Update investment status
        $newDaysGiven = $investment['rol_given_days'] + 1;
        $newStatus = ($newDaysGiven >= $investment['total_rol_days']) ? 'completed' : 'active';
        
        $updateQuery = "UPDATE user_investments 
                       SET rol_given_days = $newDaysGiven, status = '$newStatus'
                       WHERE id = {$investment['id']}";
        
        if (!mysqli_query($con, $updateQuery)) {
            error_log("Error updating investment: " . mysqli_error($con));
        }
        
        // Log the transaction
        error_log("Processed ROI for user {$investment['user_id']}, investment {$investment['id']}: $roiAmount");
    }
}

// 2. Close connection and log completion
mysqli_close($con);
error_log("Daily ROI processing completed at: " . date("Y-m-d H:i:s"));
echo "Daily ROI processing completed successfully at " . date("Y-m-d H:i:s");
?>