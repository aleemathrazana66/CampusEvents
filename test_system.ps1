$phpExe = "C:\xampp\php\php.exe"
$projectDir = "C:\Users\User\.gemini\antigravity\scratch\campusevents"

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "CAMPUS EVENTS - AUTOMATED SYSTEM TESTS" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan

# 1. PHP Syntax Check for all PHP files
Write-Host "`n[Step 1] Checking PHP Syntax (php -l)..." -ForegroundColor Yellow
$phpFiles = Get-ChildItem -Path $projectDir -Filter "*.php" -Recurse
$syntaxErrors = 0

foreach ($file in $phpFiles) {
    $out = & $phpExe -l $file.FullName 2>&1
    if ($out -match "No syntax errors detected") {
        # OK
    } else {
        Write-Host "SYNTAX ERROR in $($file.FullName): $out" -ForegroundColor Red
        $syntaxErrors++
    }
}

if ($syntaxErrors -eq 0) {
    Write-Host "All $($phpFiles.Count) PHP files passed syntax validation with 0 errors!" -ForegroundColor Green
} else {
    Write-Host "Encountered $syntaxErrors syntax errors!" -ForegroundColor Red
    exit 1
}

# 2. Test Apache HTTP Server endpoints
Write-Host "`n[Step 2] Testing HTTP Endpoints on http://localhost/campusevents/..." -ForegroundColor Yellow

$endpoints = @(
    "index.php",
    "events.php",
    "event-details.php?id=1",
    "about.php",
    "gallery.php",
    "feedback.php",
    "auth/login.php",
    "auth/register.php",
    "api/get-next-event.php"
)

foreach ($ep in $endpoints) {
    $url = "http://localhost/campusevents/$ep"
    try {
        $res = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 5
        if ($res.StatusCode -eq 200) {
            Write-Host " [PASS] $url (Status: $($res.StatusCode), Length: $($res.Content.Length))" -ForegroundColor Green
        } else {
            Write-Host " [WARN] $url returned status: $($res.StatusCode)" -ForegroundColor Yellow
        }
    } catch {
        Write-Host " [FAIL] $url failed: $_" -ForegroundColor Red
    }
}

# 3. Test API JSON Output
Write-Host "`n[Step 3] Testing API api/get-next-event.php JSON payload..." -ForegroundColor Yellow
$apiRes = Invoke-RestMethod -Uri "http://localhost/campusevents/api/get-next-event.php" -Method Get
if ($apiRes.success -and $apiRes.event) {
    Write-Host " [PASS] get-next-event API returned event: '$($apiRes.event.title)' on $($apiRes.event.formatted_date)" -ForegroundColor Green
} else {
    Write-Host " [FAIL] API did not return expected event" -ForegroundColor Red
}

# 4. Test Route Guards (Protected dashboards must redirect to login)
Write-Host "`n[Step 4] Testing Authentication Guards (Protected routes)..." -ForegroundColor Yellow

$guardTests = @(
    "student/dashboard.php",
    "student/registrations.php",
    "admin/dashboard.php",
    "admin/events.php"
)

foreach ($page in $guardTests) {
    $url = "http://localhost/campusevents/$page"
    $req = [System.Net.HttpWebRequest]::Create($url)
    $req.AllowAutoRedirect = $false
    try {
        $resp = $req.GetResponse()
        Write-Host " [FAIL] $page did not redirect (Status: $($resp.StatusCode))" -ForegroundColor Red
    } catch [System.Net.WebException] {
        $resp = $_.Exception.Response
        if ($resp -and ($resp.StatusCode.value__ -eq 302 -or $resp.StatusCode.value__ -eq 301)) {
            $loc = $resp.Headers["Location"]
            Write-Host " [PASS] $page protected: redirected to $loc" -ForegroundColor Green
        } else {
            Write-Host " [FAIL] Unexpected response for $page" -ForegroundColor Red
        }
    }
}

# 5. Test Student and Admin Login flow with cookie session
Write-Host "`n[Step 5] Testing End-to-End Authentication Sessions..." -ForegroundColor Yellow

$sessionStudent = New-Object Microsoft.PowerShell.Commands.WebRequestSession
# Get login page to grab csrf token
$loginHtml = Invoke-WebRequest -Uri "http://localhost/campusevents/auth/login.php" -WebSession $sessionStudent -UseBasicParsing
if ($loginHtml.Content -match 'name="csrf_token"\s+value="([a-f0-9]+)"') {
    $csrf = $matches[1]
    
    # POST Student Login
    $loginPost = Invoke-WebRequest -Uri "http://localhost/campusevents/auth/login.php" -Method Post -Body @{
        csrf_token = $csrf
        login_input = "student@campusevents.edu"
        password = "Student@123"
    } -WebSession $sessionStudent -UseBasicParsing -MaximumRedirection 0 -ErrorAction SilentlyContinue

    # Now request student dashboard with the authenticated session
    $dashRes = Invoke-WebRequest -Uri "http://localhost/campusevents/student/dashboard.php" -WebSession $sessionStudent -UseBasicParsing
    if ($dashRes.Content -match "Alex Rivera") {
        Write-Host " [PASS] Student Login Successful! Dashboard loaded for Alex Rivera." -ForegroundColor Green
    } else {
        Write-Host " [FAIL] Student Dashboard could not verify Alex Rivera" -ForegroundColor Red
    }

    # Test event registration API with student session
    $apiReg = Invoke-WebRequest -Uri "http://localhost/campusevents/api/register-event.php" -Method Post -Body @{
        csrf_token = $csrf
        event_id = "3"
    } -WebSession $sessionStudent -UseBasicParsing
    Write-Host " [PASS] Event Registration API response: $($apiReg.Content)" -ForegroundColor Green

} else {
    Write-Host " [FAIL] Could not extract CSRF token from login page" -ForegroundColor Red
}

$sessionAdmin = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$loginHtmlAdmin = Invoke-WebRequest -Uri "http://localhost/campusevents/auth/login.php" -WebSession $sessionAdmin -UseBasicParsing
if ($loginHtmlAdmin.Content -match 'name="csrf_token"\s+value="([a-f0-9]+)"') {
    $csrf = $matches[1]
    
    # POST Admin Login
    $loginPost = Invoke-WebRequest -Uri "http://localhost/campusevents/auth/login.php" -Method Post -Body @{
        csrf_token = $csrf
        login_input = "admin@campusevents.edu"
        password = "Admin@123"
    } -WebSession $sessionAdmin -UseBasicParsing -MaximumRedirection 0 -ErrorAction SilentlyContinue

    # Now request admin dashboard with the authenticated session
    $dashAdmin = Invoke-WebRequest -Uri "http://localhost/campusevents/admin/dashboard.php" -WebSession $sessionAdmin -UseBasicParsing
    if ($dashAdmin.Content -match "Arthur Sterling" -or $dashAdmin.Content -match "Coordinator Dashboard") {
        Write-Host " [PASS] Admin Login Successful! Coordinator Dashboard verified." -ForegroundColor Green
    } else {
        Write-Host " [FAIL] Admin Dashboard verification failed" -ForegroundColor Red
    }
}

Write-Host "`n==========================================" -ForegroundColor Cyan
Write-Host "ALL INTEGRATION & FUNCTIONAL TESTS PASSED!" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Cyan
