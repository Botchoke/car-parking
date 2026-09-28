<?php
session_start();

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}

include "../db.php";

$month = isset($_GET['month']) ? intval($_GET['month']) : date("n");
$year = isset($_GET['year']) ? intval($_GET['year']) : date("Y");
$currentMonthName = date("F", mktime(0,0,0,$month,1));

$revenueQuery = mysqli_query($conn,"
    SELECT 
        SUM(total_fee) as revenue, 
        COUNT(*) as cars 
    FROM history 
    WHERE MONTH(time_out) = '$month' 
    AND YEAR(time_out) = '$year'
");
$revenueData = mysqli_fetch_assoc($revenueQuery);
$monthlyRevenue = $revenueData['revenue'] ?? 0;
$monthlyCars = $revenueData['cars'] ?? 0;

$activeQuery = mysqli_query($conn, "SELECT COUNT(*) as active FROM vehicles");
$activeData = mysqli_fetch_assoc($activeQuery);
$activeCars = $activeData['active'] ?? 0;

$totalCarsThisMonth = $monthlyCars;

$yearQuery = mysqli_query($conn,"
    SELECT SUM(total_fee) AS totalYearSales 
    FROM history 
    WHERE YEAR(time_out) = '$year'
");
$yearData = mysqli_fetch_assoc($yearQuery);
$totalYearSales = $yearData['totalYearSales'] ?? 0;

$daysInMonth = date("t", mktime(0,0,0,$month,1));
$dailyRevenueData = [];

for($d = 1; $d <= $daysInMonth; $d++) {
    $dayQuery = mysqli_query($conn,"
        SELECT 
            SUM(total_fee) as revenue,
            COUNT(*) as cars 
        FROM history 
        WHERE DAY(time_out) = '$d' 
        AND MONTH(time_out) = '$month' 
        AND YEAR(time_out) = '$year'
    ");
    $dayData = mysqli_fetch_assoc($dayQuery);
    
    $dateObj = new DateTime("$year-$month-$d");
    $weekDay = $dateObj->format('D');
    
    $dailyRevenueData[$d] = [
        'week' => $weekDay,
        'revenue' => $dayData['revenue'] ?? 0,
        'cars' => $dayData['cars'] ?? 0
    ];
}

$dayOfWeekData = [
    'Mon' => 0,
    'Tue' => 0,
    'Wed' => 0,
    'Thu' => 0,
    'Fri' => 0,
    'Sat' => 0,
    'Sun' => 0
];

$dayQuery = mysqli_query($conn,"
    SELECT 
        DAYNAME(time_out) as day_name,
        SUM(total_fee) as revenue
    FROM history 
    WHERE MONTH(time_out) = '$month' 
    AND YEAR(time_out) = '$year'
    GROUP BY DAYNAME(time_out)
");

while($row = mysqli_fetch_assoc($dayQuery)) {
    $dayOfWeekData[$row['day_name']] = $row['revenue'];
}

$monthlyDistribution = [];
$totalYearRevenue = 0;
for($m = 1; $m <= 12; $m++) {
    $mQuery = mysqli_query($conn,"
        SELECT SUM(total_fee) as revenue 
        FROM history 
        WHERE MONTH(time_out) = '$m' 
        AND YEAR(time_out) = '$year'
    ");
    $mData = mysqli_fetch_assoc($mQuery);
    $revenue = $mData['revenue'] ?? 0;
    $totalYearRevenue += $revenue;
    $monthlyDistribution[] = [
        'month' => date("M", mktime(0,0,0,$m,1)),
        'revenue' => $revenue
    ];
}

$maxRevenue = 0;
foreach($monthlyDistribution as $item) {
    if($item['revenue'] > $maxRevenue) $maxRevenue = $item['revenue'];
}
if($maxRevenue == 0) $maxRevenue = 1;

$totalDailyRevenue = 0;
$totalDailyCars = 0;
foreach($dailyRevenueData as $day) {
    $totalDailyRevenue += $day['revenue'];
    $totalDailyCars += $day['cars'];
}
$daysWithData = count(array_filter($dailyRevenueData, function($d) { return $d['cars'] > 0 || $d['revenue'] > 0; }));

$highestRevenueDay = '';
$highestRevenueAmount = 0;
foreach($dailyRevenueData as $dayNum => $day) {
    if($day['revenue'] > $highestRevenueAmount) {
        $highestRevenueAmount = $day['revenue'];
        $highestRevenueDay = $dayNum;
    }
}
$highestDayName = $highestRevenueDay ? date('D', mktime(0,0,0,$month,$highestRevenueDay,$year)) : '-';

$highestDayOfWeek = '';
$highestDayOfWeekAmount = 0;
foreach($dayOfWeekData as $day => $amount) {
    if($amount > $highestDayOfWeekAmount) {
        $highestDayOfWeekAmount = $amount;
        $highestDayOfWeek = $day;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Parking Revenue Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <div class="dashboard">
        <div class="top-bar">
            <div class="brand">
                <h1>PARKING <span>REVENUE</span></h1>
                <span class="sub">DASHBOARD</span>
            </div>
            <div class="nav-links">
                <a href="index.php"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="welcome">
            <i class="fas fa-user-circle"></i> Welcome back, <?php echo $_SESSION['admin']; ?>!
            <span class="current-date-badge">
                <i class="fas fa-calendar-day"></i> <?php echo date('F j, Y (l)'); ?>
            </span>
        </div>

        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-label"><i class="fas fa-wallet"></i> Total Earnings</div>
                <div class="kpi-value">₱<?php echo number_format($monthlyRevenue); ?></div>
                <div class="kpi-sub"><i class="fas fa-calendar-alt"></i> <?php echo $currentMonthName; ?></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label"><i class="fas fa-car"></i> Total Cars</div>
                <div class="kpi-value"><?php echo $totalCarsThisMonth; ?></div>
                <div class="kpi-sub"><i class="fas fa-calendar-alt"></i> <?php echo $currentMonthName; ?></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label"><i class="fas fa-chart-line"></i> Sales This Year</div>
                <div class="kpi-value">₱<?php echo number_format($totalYearSales, 2); ?></div>
                <div class="kpi-sub"><i class="fas fa-calendar-alt"></i> Year <?php echo $year; ?></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label"><i class="fas fa-car-side"></i> Active Vehicles</div>
                <div class="kpi-value"><?php echo $activeCars; ?></div>
                <div class="kpi-sub"><i class="fas fa-clock"></i> Currently Parked</div>
            </div>
        </div>

        <div class="row-2col">
            <div class="card">
                <div class="card-title"><i class="fas fa-chart-pie"></i> Monthly Revenue Distribution (<?php echo $year; ?>)</div>
                <div class="month-dist">
                    <?php foreach($monthlyDistribution as $item): 
                        $pct = ($item['revenue'] / $maxRevenue) * 100;
                        $percent = ($totalYearRevenue > 0) ? round(($item['revenue'] / $totalYearRevenue) * 100, 1) : 0;
                    ?>
                        <div class="month-row">
                            <span class="month-label"><?php echo $item['month']; ?></span>
                            <div class="bar-track">
                                <div class="bar-fill" style="width: <?php echo $pct; ?>%;"></div>
                            </div>
                            <span class="percent-label"><?php echo $percent; ?>%</span>
                            <span class="month-value">₱<?php echo number_format($item['revenue']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-title"><i class="fas fa-calendar-week"></i> Revenue by Day of the Week</div>
                <div class="day-revenue" id="dayRevenue">
                    <?php 
                    $maxDayAmount = max(array_values($dayOfWeekData));
                    if($maxDayAmount == 0) $maxDayAmount = 1;
                    foreach($dayOfWeekData as $day => $amount): 
                        $pct = ($amount / $maxDayAmount) * 100;
                    ?>
                        <div class="day-row">
                            <span class="day-label"><?php echo $day; ?></span>
                            <div class="day-bar-track">
                                <div class="day-bar-fill" style="width: <?php echo $pct; ?>%;"></div>
                            </div>
                            <span class="day-amount">₱<?php echo number_format($amount); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="highest-day">
                    <span class="label"><i class="fas fa-crown" style="color: #f59e0b; margin-right: 6px;"></i> Highest Revenue Day</span>
                    <span class="value"><?php echo $highestDayOfWeek ?: '-'; ?></span>
                    <span class="sub">₱<?php echo number_format($highestDayOfWeekAmount); ?></span>
                </div>
            </div>
        </div>

        <div class="stats-row">
            <div class="stat-card highlight">
                <div class="stat-label">Highest Revenue Day</div>
                <div class="stat-value"><?php echo $highestDayName; ?></div>
                <div class="stat-sub">₱<?php echo number_format($highestRevenueAmount); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Revenue (Month)</div>
                <div class="stat-value">₱<?php echo number_format($monthlyRevenue); ?></div>
                <div class="stat-sub"><?php echo $totalCarsThisMonth; ?> Cars</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Average Revenue (Day)</div>
                <div class="stat-value">₱<?php echo $daysWithData > 0 ? number_format($totalDailyRevenue / $daysWithData, 2) : '0.00'; ?></div>
                <div class="stat-sub">Per Day</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Active Vehicles</div>
                <div class="stat-value"><?php echo $activeCars; ?></div>
                <div class="stat-sub">Currently Parked</div>
            </div>
        </div>

        <div class="card daily-overview">
            <div class="card-title">
                <i class="fas fa-table"></i> <?php echo $currentMonthName; ?> <?php echo $year; ?> — Daily Revenue Overview
                <span class="today-highlight">
                    <i class="fas fa-calendar-check"></i> Today: <?php echo date('F j, Y (D)'); ?>
                </span>
            </div>
            
            <div class="daily-grid">
                <?php 
                $dayCounter = 1;
                while($dayCounter <= $daysInMonth):
                ?>
                    <div class="week-group">
                        <?php 
                        $weekStart = $dayCounter;
                        $weekEnd = min($dayCounter + 6, $daysInMonth);
                        for($d = $weekStart; $d <= $weekEnd; $d++):
                            $data = $dailyRevenueData[$d] ?? ['week' => '', 'revenue' => 0, 'cars' => 0];
                            $isToday = ($d == date("j") && $month == date("n") && $year == date("Y"));
                            $hasCars = ($data['cars'] > 0);
                            $hasRevenue = ($data['revenue'] > 0);
                            
                            $displayRevenue = $hasRevenue ? '₱' . number_format($data['revenue']) : ($hasCars ? '—' : '—');
                            $displayCars = $hasCars ? $data['cars'] : '—';
                            $weekDay = $data['week'];
                            
                            $class = '';
                            if($isToday) $class = 'today';
                            elseif(!$hasCars && !$hasRevenue) $class = 'no-data';
                            else $class = 'has-revenue';
                        ?>
                            <div class="day-card <?php echo $class; ?>">
                                <div class="day-number"><?php echo $d; ?></div>
                                <div class="day-week"><?php echo $weekDay; ?></div>
                                <div class="day-revenue"><?php echo $displayRevenue; ?></div>
                                <div class="day-cars">
                                    <i class="fas fa-car"></i> <?php echo $displayCars; ?>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                <?php 
                    $dayCounter += 7;
                endwhile; 
                ?>
            </div>

            <div class="legend">
                <span class="legend-item">
                    <span class="legend-dot today-dot"></span> Today
                </span>
                <span class="legend-item">
                    <span class="legend-dot has-revenue-dot"></span> Has cars / revenue
                </span>
                <span class="legend-item">
                    <span class="legend-dot no-data-dot"></span> No data / Future
                </span>
            </div>
        </div>

        <div class="card month-nav">
            <div class="card-title"><i class="fas fa-calendar-alt"></i> Select Month</div>
            <div class="months">
                <?php for($m=1; $m<=12; $m++): ?>
                    <a href="revenue.php?month=<?php echo $m; ?>&year=<?php echo $year; ?>" class="<?php echo ($m == $month) ? 'active' : ''; ?>">
                        <?php echo date("M", mktime(0,0,0,$m,1)); ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</body>
</html>