<?php
// Get user data and analysis
require_once 'functions/user_management.php';
$db = Database::getInstance();
$userManager = new UserManagement($db->getConnection());
$user = $userManager->getUserById($_SESSION['user_id']);

// Get analysis data - check for ID parameter first, fallback to latest
$analysisId = $_GET['id'] ?? null;
$analysisData = null;
$blueprintData = null;

if ($analysisId) {
    // Load specific analysis by ID
    $analysis = $userManager->getAnalysisById($analysisId, $_SESSION['user_id']);
    if ($analysis && isset($analysis['analysis_result'])) {
        $analysisData = json_decode($analysis['analysis_result'], true);
        $currency = $analysisData['summary']['currency'] ?? 'USD';
        $currencySymbol = getCurrencySymbol($currency);

        // Check if blueprint already exists
        if (!empty($analysis['blueprint_result'])) {
            $blueprintData = json_decode($analysis['blueprint_result'], true);
        } else {
            // Generate new blueprint with OpenAI
            require_once 'functions/api_handler.php';
            try {
                $userInfo = json_encode([
                    'income' => $user['income'] ?? null,
                    'age' => $_POST['age'] ?? null,
                    'country' => $_POST['country'] ?? null,
                    'occupation' => $_POST['occupation'] ?? null,
                    'gender' => $_POST['gender'] ?? null,
                    'motivation' => $_POST['motivation'] ?? null
                ]);

                $blueprintData = generateBlueprintWithOpenAI(json_encode($analysisData), $userInfo);

                // Save blueprint to database
                $pdo = Database::getInstance()->getConnection();
                $stmt = $pdo->prepare("UPDATE uploads SET blueprint_result = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([json_encode($blueprintData), $analysisId, $_SESSION['user_id']]);

            } catch (Exception $e) {
                // Fallback to basic calculation if AI fails
                $blueprintData = determineBlueprintData($analysisData);
            }
        }
    }
} else {
    // Get latest analysis data
    $latestAnalysis = $userManager->getLatestAnalysis($_SESSION['user_id']);
    if ($latestAnalysis && isset($latestAnalysis['analysis_result'])) {
        $analysisData = json_decode($latestAnalysis['analysis_result'], true);
        $currency = $analysisData['summary']['currency'] ?? 'USD';
        $currencySymbol = getCurrencySymbol($currency);

        // Check if blueprint already exists
        if (!empty($latestAnalysis['blueprint_result'])) {
            $blueprintData = json_decode($latestAnalysis['blueprint_result'], true);
        } else {
            // Generate new blueprint with OpenAI
            require_once 'functions/api_handler.php';
            try {
                $userInfo = json_encode([
                    'age' => $_POST['age'] ?? null,
                    'country' => $_POST['country'] ?? null,
                    'occupation' => $_POST['occupation'] ?? null,
                    'gender' => $_POST['gender'] ?? null,
                    'motivation' => $_POST['motivation'] ?? null
                ]);

                $blueprintData = generateBlueprintWithOpenAI(json_encode($analysisData), $userInfo);

                // Save blueprint to database
                $pdo = Database::getInstance()->getConnection();
                $stmt = $pdo->prepare("UPDATE uploads SET blueprint_result = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([json_encode($blueprintData), $latestAnalysis['id'], $_SESSION['user_id']]);

            } catch (Exception $e) {
                // Fallback to basic calculation if AI fails
                $blueprintData = determineBlueprintData($analysisData);
            }
        }
    }
}

function getCurrencySymbol($currency) {
    $symbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'BDT' => '৳',
        'AED' => 'د.إ',
        'SAR' => '﷼',
        'INR' => '₹',
        'JPY' => '¥',
        'CNY' => '¥',
        'KRW' => '₩',
        'THB' => '฿',
        'VND' => '₫',
        'MYR' => 'RM',
        'SGD' => 'S$',
        'PHP' => '₱',
        'IDR' => 'Rp',
        'PKR' => '₨',
        'LKR' => '₨',
        'NPR' => '₨',
        'MMK' => 'K',
        'LAK' => '₭',
        'KHR' => '៛',
        'BND' => 'B$'
    ];
    return $symbols[$currency] ?? '$';
}

function determineBlueprintData($data) {
    $needCats = ['Rent/Mortgage','Utilities','Groceries','Fees/Other','Transportation','Personal Care','Debt Repayment','Pet Supplies','Online Courses'];
    $needs = 0;
    $wants = 0;
    $save = 0;

    if (isset($data['categorizedExpenses'])) {
        foreach ($data['categorizedExpenses'] as $e) {
            $category = $e['category'] ?? '';
            $amount = $e['amount'] ?? 0;

            if (strpos($category, 'Savings') !== false || strpos($category, 'Investment') !== false) {
                $save += $amount;
            } elseif (in_array($category, $needCats) && (!isset($e['isLeak']) || !$e['isLeak'])) {
                $needs += $amount;
            } else {
                $wants += $amount;
            }
        }
    }

    $total = $data['summary']['totalCredit'] ?? 0;
    $needsP = $total ? ($needs/$total)*100 : 0;
    $wantsP = $total ? ($wants/$total)*100 : 0;
    $saveP  = $total ? ($save/$total)*100  : 0;

    $topLeaks = [];
    if (isset($data['categorizedExpenses'])) {
        $leaks = array_filter($data['categorizedExpenses'], function($e) {
            return isset($e['isLeak']) && $e['isLeak'];
        });
        usort($leaks, function($a, $b) {
            return ($b['amount'] ?? 0) - ($a['amount'] ?? 0);
        });
        $topLeaks = array_slice(array_column($leaks, 'category'), 0, 3);
    }

    return [
        'needs' => ['amount' => $needs, 'percent' => $needsP],
        'wants' => ['amount' => $wants, 'percent' => $wantsP],
        'save' => ['amount' => $save, 'percent' => $saveP],
        'topLeaks' => $topLeaks,
        'leakTotal' => $data['summary']['totalDiscretionaryLeaks'] ?? 0,
        'totalIncome' => $total
    ];
}
?>

<main>
    <!-- Pass user account status to JavaScript -->
    <script>window.userHasAccount = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;</script>

    <!-- Success/Error Messages -->
    <?php if (isset($_GET['success'])): ?>
    <div id="successMessage" class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            Your blueprint has been unlocked successfully!
        </div>
    </div>
    <script>
        setTimeout(() => {
            const msg = document.getElementById('successMessage');
            if (msg) msg.remove();
        }, 3000);
    </script>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
    <div id="errorMessage" class="fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
            Failed to save your information. Please try again.
        </div>
    </div>
    <script>
        setTimeout(() => {
            const msg = document.getElementById('errorMessage');
            if (msg) msg.remove();
        }, 5000);
    </script>
    <?php endif; ?>

    <!-- BLUEPRINT MODAL (shown only once) -->
    <?php if (!$hasBlueprintData): ?>
    <div id="blueprintModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <div class="inline-block align-middle bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border-t-8 border-violet-700">
                <div class="bg-white px-4 pt-5 pb-4 p-6">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="text-2xl font-bold text-gray-900" id="modal-title">Unlock Your Wealth Blueprint</h3>
                        <button onclick="closeBlueprintModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <form id="blueprintForm" action="process_blueprint.php" method="POST" class="space-y-4">
                        <div>
                            <label for="user_age" class="block text-sm font-medium text-gray-700">Age</label>
                            <input type="number" id="user_age" name="age" required min="18" max="120" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-violet-700 focus:border-violet-700">
                        </div>

                        <div>
                            <label for="user_country" class="block text-sm font-medium text-gray-700">Country</label>
                            <select id="user_country" name="country" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-violet-700 focus:border-violet-700">
                                <option value="">Select Country</option>
                                <option value="AF">Afghanistan</option>
                                <option value="AL">Albania</option>
                                <option value="DZ">Algeria</option>
                                <option value="AS">American Samoa</option>
                                <option value="AD">Andorra</option>
                                <option value="AO">Angola</option>
                                <option value="AI">Anguilla</option>
                                <option value="AQ">Antarctica</option>
                                <option value="AG">Antigua and Barbuda</option>
                                <option value="AR">Argentina</option>
                                <option value="AM">Armenia</option>
                                <option value="AW">Aruba</option>
                                <option value="AU">Australia</option>
                                <option value="AT">Austria</option>
                                <option value="AZ">Azerbaijan</option>
                                <option value="BS">Bahamas</option>
                                <option value="BH">Bahrain</option>
                                <option value="BD">Bangladesh</option>
                                <option value="BB">Barbados</option>
                                <option value="BY">Belarus</option>
                                <option value="BE">Belgium</option>
                                <option value="BZ">Belize</option>
                                <option value="BJ">Benin</option>
                                <option value="BM">Bermuda</option>
                                <option value="BT">Bhutan</option>
                                <option value="BO">Bolivia</option>
                                <option value="BA">Bosnia and Herzegovina</option>
                                <option value="BW">Botswana</option>
                                <option value="BV">Bouvet Island</option>
                                <option value="BR">Brazil</option>
                                <option value="IO">British Indian Ocean Territory</option>
                                <option value="BN">Brunei Darussalam</option>
                                <option value="BG">Bulgaria</option>
                                <option value="BF">Burkina Faso</option>
                                <option value="BI">Burundi</option>
                                <option value="KH">Cambodia</option>
                                <option value="CM">Cameroon</option>
                                <option value="CA">Canada</option>
                                <option value="CV">Cape Verde</option>
                                <option value="KY">Cayman Islands</option>
                                <option value="CF">Central African Republic</option>
                                <option value="TD">Chad</option>
                                <option value="CL">Chile</option>
                                <option value="CN">China</option>
                                <option value="CX">Christmas Island</option>
                                <option value="CC">Cocos (Keeling) Islands</option>
                                <option value="CO">Colombia</option>
                                <option value="KM">Comoros</option>
                                <option value="CG">Congo</option>
                                <option value="CD">Congo, the Democratic Republic of the</option>
                                <option value="CK">Cook Islands</option>
                                <option value="CR">Costa Rica</option>
                                <option value="CI">Côte d'Ivoire</option>
                                <option value="HR">Croatia</option>
                                <option value="CU">Cuba</option>
                                <option value="CY">Cyprus</option>
                                <option value="CZ">Czech Republic</option>
                                <option value="DK">Denmark</option>
                                <option value="DJ">Djibouti</option>
                                <option value="DM">Dominica</option>
                                <option value="DO">Dominican Republic</option>
                                <option value="EC">Ecuador</option>
                                <option value="EG">Egypt</option>
                                <option value="SV">El Salvador</option>
                                <option value="GQ">Equatorial Guinea</option>
                                <option value="ER">Eritrea</option>
                                <option value="EE">Estonia</option>
                                <option value="ET">Ethiopia</option>
                                <option value="FK">Falkland Islands (Malvinas)</option>
                                <option value="FO">Faroe Islands</option>
                                <option value="FJ">Fiji</option>
                                <option value="FI">Finland</option>
                                <option value="FR">France</option>
                                <option value="GF">French Guiana</option>
                                <option value="PF">French Polynesia</option>
                                <option value="TF">French Southern Territories</option>
                                <option value="GA">Gabon</option>
                                <option value="GM">Gambia</option>
                                <option value="GE">Georgia</option>
                                <option value="DE">Germany</option>
                                <option value="GH">Ghana</option>
                                <option value="GI">Gibraltar</option>
                                <option value="GR">Greece</option>
                                <option value="GL">Greenland</option>
                                <option value="GD">Grenada</option>
                                <option value="GP">Guadeloupe</option>
                                <option value="GU">Guam</option>
                                <option value="GT">Guatemala</option>
                                <option value="GG">Guernsey</option>
                                <option value="GN">Guinea</option>
                                <option value="GW">Guinea-Bissau</option>
                                <option value="GY">Guyana</option>
                                <option value="HT">Haiti</option>
                                <option value="HM">Heard Island and McDonald Islands</option>
                                <option value="VA">Holy See (Vatican City State)</option>
                                <option value="HN">Honduras</option>
                                <option value="HK">Hong Kong</option>
                                <option value="HU">Hungary</option>
                                <option value="IS">Iceland</option>
                                <option value="IN">India</option>
                                <option value="ID">Indonesia</option>
                                <option value="IR">Iran, Islamic Republic of</option>
                                <option value="IQ">Iraq</option>
                                <option value="IE">Ireland</option>
                                <option value="IM">Isle of Man</option>
                                <option value="IL">Israel</option>
                                <option value="IT">Italy</option>
                                <option value="JM">Jamaica</option>
                                <option value="JP">Japan</option>
                                <option value="JE">Jersey</option>
                                <option value="JO">Jordan</option>
                                <option value="KZ">Kazakhstan</option>
                                <option value="KE">Kenya</option>
                                <option value="KI">Kiribati</option>
                                <option value="KP">Korea, Democratic People's Republic of</option>
                                <option value="KR">Korea, Republic of</option>
                                <option value="KW">Kuwait</option>
                                <option value="KG">Kyrgyzstan</option>
                                <option value="LA">Lao People's Democratic Republic</option>
                                <option value="LV">Latvia</option>
                                <option value="LB">Lebanon</option>
                                <option value="LS">Lesotho</option>
                                <option value="LR">Liberia</option>
                                <option value="LY">Libyan Arab Jamahiriya</option>
                                <option value="LI">Liechtenstein</option>
                                <option value="LT">Lithuania</option>
                                <option value="LU">Luxembourg</option>
                                <option value="MO">Macao</option>
                                <option value="MK">Macedonia, the former Yugoslav Republic of</option>
                                <option value="MG">Madagascar</option>
                                <option value="MW">Malawi</option>
                                <option value="MY">Malaysia</option>
                                <option value="MV">Maldives</option>
                                <option value="ML">Mali</option>
                                <option value="MT">Malta</option>
                                <option value="MH">Marshall Islands</option>
                                <option value="MQ">Martinique</option>
                                <option value="MR">Mauritania</option>
                                <option value="MU">Mauritius</option>
                                <option value="YT">Mayotte</option>
                                <option value="MX">Mexico</option>
                                <option value="FM">Micronesia, Federated States of</option>
                                <option value="MD">Moldova, Republic of</option>
                                <option value="MC">Monaco</option>
                                <option value="MN">Mongolia</option>
                                <option value="ME">Montenegro</option>
                                <option value="MS">Montserrat</option>
                                <option value="MA">Morocco</option>
                                <option value="MZ">Mozambique</option>
                                <option value="MM">Myanmar</option>
                                <option value="NA">Namibia</option>
                                <option value="NR">Nauru</option>
                                <option value="NP">Nepal</option>
                                <option value="NL">Netherlands</option>
                                <option value="AN">Netherlands Antilles</option>
                                <option value="NC">New Caledonia</option>
                                <option value="NZ">New Zealand</option>
                                <option value="NI">Nicaragua</option>
                                <option value="NE">Niger</option>
                                <option value="NG">Nigeria</option>
                                <option value="NU">Niue</option>
                                <option value="NF">Norfolk Island</option>
                                <option value="MP">Northern Mariana Islands</option>
                                <option value="NO">Norway</option>
                                <option value="OM">Oman</option>
                                <option value="PK">Pakistan</option>
                                <option value="PW">Palau</option>
                                <option value="PS">Palestinian Territory, Occupied</option>
                                <option value="PA">Panama</option>
                                <option value="PG">Papua New Guinea</option>
                                <option value="PY">Paraguay</option>
                                <option value="PE">Peru</option>
                                <option value="PH">Philippines</option>
                                <option value="PN">Pitcairn</option>
                                <option value="PL">Poland</option>
                                <option value="PT">Portugal</option>
                                <option value="PR">Puerto Rico</option>
                                <option value="QA">Qatar</option>
                                <option value="RE">Réunion</option>
                                <option value="RO">Romania</option>
                                <option value="RU">Russian Federation</option>
                                <option value="RW">Rwanda</option>
                                <option value="SH">Saint Helena</option>
                                <option value="KN">Saint Kitts and Nevis</option>
                                <option value="LC">Saint Lucia</option>
                                <option value="PM">Saint Pierre and Miquelon</option>
                                <option value="VC">Saint Vincent and the Grenadines</option>
                                <option value="WS">Samoa</option>
                                <option value="SM">San Marino</option>
                                <option value="ST">Sao Tome and Principe</option>
                                <option value="SA">Saudi Arabia</option>
                                <option value="SN">Senegal</option>
                                <option value="RS">Serbia</option>
                                <option value="SC">Seychelles</option>
                                <option value="SL">Sierra Leone</option>
                                <option value="SG">Singapore</option>
                                <option value="SK">Slovakia</option>
                                <option value="SI">Slovenia</option>
                                <option value="SB">Solomon Islands</option>
                                <option value="SO">Somalia</option>
                                <option value="ZA">South Africa</option>
                                <option value="GS">South Georgia and the South Sandwich Islands</option>
                                <option value="ES">Spain</option>
                                <option value="LK">Sri Lanka</option>
                                <option value="SD">Sudan</option>
                                <option value="SR">Suriname</option>
                                <option value="SJ">Svalbard and Jan Mayen</option>
                                <option value="SZ">Swaziland</option>
                                <option value="SE">Sweden</option>
                                <option value="CH">Switzerland</option>
                                <option value="SY">Syrian Arab Republic</option>
                                <option value="TW">Taiwan, Province of China</option>
                                <option value="TJ">Tajikistan</option>
                                <option value="TZ">Tanzania, United Republic of</option>
                                <option value="TH">Thailand</option>
                                <option value="TL">Timor-Leste</option>
                                <option value="TG">Togo</option>
                                <option value="TK">Tokelau</option>
                                <option value="TO">Tonga</option>
                                <option value="TT">Trinidad and Tobago</option>
                                <option value="TN">Tunisia</option>
                                <option value="TR">Turkey</option>
                                <option value="TM">Turkmenistan</option>
                                <option value="TC">Turks and Caicos Islands</option>
                                <option value="TV">Tuvalu</option>
                                <option value="UG">Uganda</option>
                                <option value="UA">Ukraine</option>
                                <option value="AE">United Arab Emirates</option>
                                <option value="GB">United Kingdom</option>
                                <option value="US">United States</option>
                                <option value="UM">United States Minor Outlying Islands</option>
                                <option value="UY">Uruguay</option>
                                <option value="UZ">Uzbekistan</option>
                                <option value="VU">Vanuatu</option>
                                <option value="VE">Venezuela</option>
                                <option value="VN">Viet Nam</option>
                                <option value="VG">Virgin Islands, British</option>
                                <option value="VI">Virgin Islands, U.S.</option>
                                <option value="WF">Wallis and Futuna</option>
                                <option value="EH">Western Sahara</option>
                                <option value="YE">Yemen</option>
                                <option value="ZM">Zambia</option>
                                <option value="ZW">Zimbabwe</option>
                            </select>
                        </div>

                        <div>
                            <label for="user_occupation" class="block text-sm font-medium text-gray-700">Occupation</label>
                            <input type="text" id="user_occupation" name="occupation" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-violet-700 focus:border-violet-700" placeholder="e.g., Software Engineer, Teacher, Business Owner">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">Gender</label>
                            <div class="flex flex-wrap gap-6">
                                <label class="flex items-center">
                                    <input type="radio" name="gender" value="male" required class="h-4 w-4 text-violet-600 focus:ring-violet-500 border-gray-300">
                                    <span class="ml-2 text-gray-700">Male</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="gender" value="female" required class="h-4 w-4 text-violet-600 focus:ring-violet-500 border-gray-300">
                                    <span class="ml-2 text-gray-700">Female</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label for="user_motivation" class="block text-sm font-medium text-gray-700">What drives you to do this?</label>
                            <select id="user_motivation" name="motivation" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-violet-700 focus:border-violet-700">
                                <option value="">Select your primary motivation</option>
                                <option value="manage_expense">Need help to manage expenses</option>
                                <option value="build_wealth">Build wealth and stop the rat race</option>
                                <option value="check_wife_spending">Check my wife's spending habits</option>
                                <option value="check_husband_spending">Check my husband's spending habits</option>
                            </select>
                        </div>

                        <div class="flex justify-end space-x-3 pt-4">
                            <button type="button" onclick="closeBlueprintModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                                Cancel
                            </button>
                            <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200">
                                Unlock My Blueprint
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- BLUEPRINT PAGE CONTENT CONTAINER  -->
    <div id="blueprintPage" class="py-16 md:py-24 bg-gray-50 min-h-screen <?php echo !$hasBlueprintData ? 'hidden' : ''; ?>">
        <div id="blueprint-content-container" class="max-w-4xl mx-auto bg-white p-6 sm:p-10 rounded-xl shadow-2xl">

            <header class="text-center mb-10">
                <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 mb-2">
                    <?php echo $user['name']; ?>, Here's Your Personalized Wealth Blueprint
                </h1>
                <p id="blueprintIntro" class="text-xl text-indigo-600 font-semibold">
                    <?php if (isset($blueprintData['key_insights']) && is_array($blueprintData['key_insights'])): ?>
                        Follow this Action Plan to help you Manage your Finances better and Build Wealth <br /> You Financial Health Score is: <?php echo number_format($blueprintData['financial_health_score'] ?? 0, 1); ?>/100
                    <?php else: ?>
                        Action Plan to Recover <?php echo $currencySymbol; ?><?php echo number_format($blueprintData['leakTotal'] ?? 0, 2); ?> in Monthly Leaks.
                    <?php endif; ?>
                </p>
            </header>

            <!-- Wealth Allocation Section -->
            <section class="mb-12 border-b pb-8">
                <h2 class="text-3xl font-bold text-gray-800 mb-6 border-l-4 border-indigo-500 pl-4">1. Your Personalized 50/30/20 Snapshot</h2>
                <p class="text-lg text-gray-600 mb-8">
                <?php echo $user['name']; ?>, based on your needs we can identify from your bank statement, it seems the <strong>50/30/20 Rule</strong> will be well suited to you. This rule for managing finances better is the industry standard for financial health. Below is your current allocation, revealing exactly where your money is currently going versus the target. </p>
                <p class="text-lg text-gray-600 mb-8">&nbsp;</p>
                <p class="text-lg text-gray-600 mb-8">Here  is how you should ideally be allocating your monthly income, which will allow you to live comfortably within your means, as well as work on doing some background money management that will help you build wealth over the longer term.</p>

                <div class="flex flex-col lg:flex-row items-center justify-between gap-10">

                    <!-- Chart Visualization (Dynamic Background Set by JS) -->
                    <div class="flex-shrink-0">
                        <div id="blueprintChartRing" class="blueprint-chart-ring">
                            <div class="blueprint-chart-inner">
                                <span class="text-2xl font-bold text-gray-800" id="currentIncomeDisplay"><?php echo $currencySymbol; ?><?php echo number_format($user['income'] ?? 0, 0); ?></span>
                                <span class="text-sm text-gray-500">Your Income</span>
                            </div>
                            
                            
                      </div>
                    </div>

                    <!-- Legend and Details (Dynamic) -->
                    <div class="w-full lg:w-1/2">
                        <div class="space-y-4">
                            <?php if (isset($blueprintData['wealth_allocation'])): ?>
                                <div class="p-4 bg-emerald-50 rounded-lg flex items-start gap-4 shadow">
                                    <div class="w-4 h-4 bg-emerald-500 rounded-full mt-1 flex-shrink-0"></div>
                                    <div>
                                        <h3 class="text-xl font-semibold text-gray-800"><span id="needsActual"><?php echo number_format($blueprintData['wealth_allocation']['needs_percent'] ?? 50, 1); ?>%</span> Needs <span class="text-emerald-600 text-base ml-2"><?php echo $currencySymbol; ?><?php echo number_format($blueprintData['wealth_allocation']['needs_amount'] ?? 0, 2); ?></span></h3>
                                        <p class="text-gray-600 text-sm">Essential, fixed costs like rent/mortgage, minimum loan payments, utilities, and basic groceries. <strong>Goal:</strong> Ensure 50% or less of your income covers only these essentials.</p>
                                    </div>
                                </div>

                                <div class="p-4 bg-amber-50 rounded-lg flex items-start gap-4 shadow">
                                    <div class="w-4 h-4 bg-amber-500 rounded-full mt-1 flex-shrink-0"></div>
                                    <div>
                                        <h3 class="text-xl font-semibold text-gray-800"><span id="wantsActual"><?php echo number_format($blueprintData['wealth_allocation']['wants_percent'] ?? 30, 1); ?>%</span> Wants <span class="text-amber-600 text-base ml-2">You should be Spending at most: <?php echo $currencySymbol; ?><?php echo number_format($blueprintData['wealth_allocation']['wants_amount'] ?? 0, 2); ?></span></h3>
                                        <p class="text-gray-600 text-sm">Discretionary spending: dining out, entertainment, hobbies, travel, premium subscriptions, and non-essential shopping. <strong>Goal:</strong> This is your primary target for trimming excess spending.</p>
                                    </div>
                                </div>

                                <div class="p-4 bg-blue-50 rounded-lg flex items-start gap-4 shadow">
                                    <div class="w-4 h-4 bg-blue-500 rounded-full mt-1 flex-shrink-0"></div>
                                    <div>
                                        <h3 class="text-xl font-semibold text-gray-800"><span id="saveActual"><?php echo number_format($blueprintData['wealth_allocation']['savings_percent'] ?? 20, 1); ?>%</span> Save & Invest <span class="text-blue-600 text-base ml-2"><?php echo $currencySymbol; ?><?php echo number_format($blueprintData['wealth_allocation']['savings_amount'] ?? 0, 2); ?></span></h3>
                                        <p class="text-gray-600 text-sm">Funding your future: Emergency Fund contributions, retirement accounts, stock/index fund investments, and accelerated debt repayment. <strong>Goal:</strong> Automate this 20% first (Pay Yourself First).</p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="p-4 bg-emerald-50 rounded-lg flex items-start gap-4 shadow">
                                    <div class="w-4 h-4 bg-emerald-500 rounded-full mt-1 flex-shrink-0"></div>
                                    <div>
                                        <h3 class="text-xl font-semibold text-gray-800"><span id="needsActual"><?php echo number_format($blueprintData['needs']['percent'] ?? 50, 1); ?>%</span> Needs <span class="text-emerald-600 text-base ml-2">Actual: <?php echo number_format($blueprintData['needs']['percent'] ?? 0, 1); ?>%</span></h3>
                                        <p class="text-gray-600 text-sm">Essential, fixed costs (rent, utilities, required debt payments).</p>
                                    </div>
                                </div>

                                <div class="p-4 bg-amber-50 rounded-lg flex items-start gap-4 shadow">
                                    <div class="w-4 h-4 bg-amber-500 rounded-full mt-1 flex-shrink-0"></div>
                                    <div>
                                        <h3 class="text-xl font-semibold text-gray-800"><span id="wantsActual"><?php echo number_format($blueprintData['wants']['percent'] ?? 30, 1); ?>%</span> Wants <span class="text-amber-600 text-base ml-2">Actual: <?php echo number_format($blueprintData['wants']['percent'] ?? 0, 1); ?>%</span></h3>
                                        <p class="text-gray-600 text-sm">Discretionary spending (dining, entertainment, non-essential shopping).</p>
                                    </div>
                                </div>

                                <div class="p-4 bg-blue-50 rounded-lg flex items-start gap-4 shadow">
                                    <div class="w-4 h-4 bg-blue-500 rounded-full mt-1 flex-shrink-0"></div>
                                    <div>
                                        <h3 class="text-xl font-semibold text-gray-800"><span id="saveActual"><?php echo number_format($blueprintData['save']['percent'] ?? 20, 1); ?>%</span> Save & Invest <span class="text-blue-600 text-base ml-2">Actual: <?php echo number_format($blueprintData['save']['percent'] ?? 0, 1); ?>%</span></h3>
                                        <p class="text-gray-600 text-sm">Emergency fund, retirement, investments.</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Personalized Action Plan Section -->
            <section class="mb-12">
                <h2 class="text-3xl font-bold text-gray-800 mb-6 border-l-4 border-indigo-500 pl-4">2. Your Personalized 4-Point Action Plan</h2>
                <p id="actionPlanIntro" class="text-lg text-gray-600 mb-8">
                    <?php
                    $leakNames = !empty($blueprintData['topLeaks']) ? implode(', ', $blueprintData['topLeaks']) : 'general discretionary spending';
                    $leakTotal = $blueprintData['leakTotal'] ?? 0;
                    echo "The AI identified your top spending leaks of <strong>" . $currencySymbol . number_format($leakTotal, 2) . "</strong> in <strong>$leakNames</strong>.";
                    ?>
                </p>

                <?php if (isset($blueprintData['action_plan']) && is_array($blueprintData['action_plan'])): ?>
                    <ol id="actionPlanList" class="space-y-6 list-decimal list-inside text-gray-700">
                        <?php foreach ($blueprintData['action_plan'] as $step): ?>
                            <li class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm">
                                <strong class="text-indigo-600 text-xl block mb-1">Step <?php echo $step['step_number']; ?>: <?php echo htmlspecialchars($step['title']); ?></strong>
                                <p><?php echo htmlspecialchars($step['description']); ?></p>
                                <?php if (isset($step['estimated_savings']) && $step['estimated_savings'] > 0): ?>
                                    <p class="text-green-600 font-semibold mt-2">💰 Potential Monthly Savings: <?php echo $currencySymbol; ?><?php echo number_format($step['estimated_savings'], 2); ?></p>
                                <?php endif; ?>
                                <div class="flex gap-4 mt-2 text-sm text-gray-500">
                                    <span>Difficulty: <?php echo htmlspecialchars($step['difficulty'] ?? 'Medium'); ?></span>
                                    <span>Timeframe: <?php echo htmlspecialchars($step['timeframe'] ?? 'Ongoing'); ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php else: ?>
                    <ol id="actionPlanList" class="space-y-6 list-decimal list-inside text-gray-700">
                        <?php
                        $income = $blueprintData['totalIncome'] ?? 0;
                        $topLeaks = $blueprintData['topLeaks'] ?? [];
                        $leakTotal = $blueprintData['leakTotal'] ?? 0;
                        ?>

                        <!-- Step 1 – always -->
                        <li class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm">
                            <strong class="text-indigo-600 text-xl block mb-1">Step 1: Automate the 20% Investment</strong>
                            <p>Immediately set up an automatic transfer for <strong>$<?php echo number_format($income * 0.2, 2); ?></strong> (20% of income) to a high-yield account the day you get paid.</p>
                        </li>

                        <!-- Step 2 – leak-specific or generic -->
                        <?php if (!empty($topLeaks)): ?>
                            <li class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm">
                                <strong class="text-indigo-600 text-xl block mb-1">Step 2: Cap Your Top Spending Leaks</strong>
                                <p>Cut <strong><?php echo implode(', ', $topLeaks); ?></strong> by 30% — that's <strong><?php echo $currencySymbol; ?><?php echo number_format($leakTotal * 0.3, 2); ?></strong> — and move it to the automated savings.</p>
                            </li>
                        <?php else: ?>
                            <li class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm">
                                <strong class="text-indigo-600 text-xl block mb-1">Step 2: Implement a 'Wants' Hard Limit</strong>
                                <p>Cap all discretionary spending at <strong><?php echo $currencySymbol; ?><?php echo number_format($income * 0.3, 2); ?></strong> (30% of income).</p>
                            </li>
                        <?php endif; ?>

                        <!-- Step 3 & 4 – universal -->
                        <li class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm">
                            <strong class="text-indigo-600 text-xl block mb-1">Step 3: Audit Hidden Subscriptions</strong>
                            <p>Cancel any recurring service you haven't used in 30 days.</p>
                        </li>
                        <li class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm">
                            <strong class="text-indigo-600 text-xl block mb-1">Step 4: 48-Hour Purchase Pause</strong>
                            <p>For any non-essential purchase over $50, wait 48 hours before buying.</p>
                        </li>
                    </ol>
                <?php endif; ?>
            </section>

            <!-- Key Insights Section (AI-generated) -->
            <?php if (isset($blueprintData['key_insights']) && is_array($blueprintData['key_insights'])): ?>
            <section class="mb-12">
                <h2 class="text-3xl font-bold text-gray-800 mb-6 border-l-4 border-indigo-500 pl-4">3. Key Financial Insights</h2>
                <div class="bg-blue-50 p-6 rounded-xl shadow-lg">
                    <ul class="space-y-3">
                        <?php foreach ($blueprintData['key_insights'] as $insight): ?>
                            <li class="flex items-start gap-3">
                                <span class="text-blue-600 text-xl">💡</span>
                                <span class="text-gray-800"><?php echo htmlspecialchars($insight); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>
            <?php endif; ?>

            <!-- Improvement Areas Section (AI-generated) -->
            <?php if (isset($blueprintData['improvement_areas']) && is_array($blueprintData['improvement_areas'])): ?>
            <section class="mb-12">
                <h2 class="text-3xl font-bold text-gray-800 mb-6 border-l-4 border-indigo-500 pl-4">4. Areas for Improvement</h2>
                <div class="grid md:grid-cols-2 gap-6">
                    <?php foreach ($blueprintData['improvement_areas'] as $area): ?>
                        <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-orange-500">
                            <h3 class="text-xl font-semibold text-gray-800 mb-3"><?php echo htmlspecialchars($area['category']); ?></h3>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Current Spending:</span>
                                    <span class="font-semibold text-red-600"><?php echo $currencySymbol; ?><?php echo number_format($area['current_spending'], 2); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Recommended:</span>
                                    <span class="font-semibold text-green-600"><?php echo $currencySymbol; ?><?php echo number_format($area['recommended_spending'], 2); ?></span>
                                </div>
                                <div class="flex justify-between border-t pt-2">
                                    <span class="text-gray-600">Potential Savings:</span>
                                    <span class="font-bold text-green-700"><?php echo $currencySymbol; ?><?php echo number_format($area['potential_savings'], 2); ?>/month</span>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="inline-block px-3 py-1 bg-<?php echo $area['priority'] === 'high' ? 'red' : ($area['priority'] === 'medium' ? 'yellow' : 'green'); ?>-100 text-<?php echo $area['priority'] === 'high' ? 'red' : ($area['priority'] === 'medium' ? 'yellow' : 'green'); ?>-800 text-xs font-semibold rounded-full">
                                    <?php echo ucfirst($area['priority']); ?> Priority
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Monthly Targets Section (AI-generated) -->
            <?php if (isset($blueprintData['monthly_targets'])): ?>
            <section class="mb-12">
                <h2 class="text-3xl font-bold text-gray-800 mb-6 border-l-4 border-indigo-500 pl-4">5. Your Monthly Wealth Building Targets</h2>
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-green-50 p-6 rounded-xl shadow-lg text-center">
                        <div class="text-3xl mb-2">🏦</div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Emergency Fund</h3>
                        <p class="text-2xl font-bold text-green-600"><?php echo $currencySymbol; ?><?php echo number_format($blueprintData['monthly_targets']['emergency_fund_target'], 2); ?></p>
                        <p class="text-sm text-gray-600">Monthly contribution target</p>
                    </div>
                    <div class="bg-blue-50 p-6 rounded-xl shadow-lg text-center">
                        <div class="text-3xl mb-2">📈</div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Investments</h3>
                        <p class="text-2xl font-bold text-blue-600"><?php echo $currencySymbol; ?><?php echo number_format($blueprintData['monthly_targets']['investment_target'], 2); ?></p>
                        <p class="text-sm text-gray-600">Monthly investment target</p>
                    </div>
                    <div class="bg-red-50 p-6 rounded-xl shadow-lg text-center">
                        <div class="text-3xl mb-2">💳</div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Debt Reduction</h3>
                        <p class="text-2xl font-bold text-red-600"><?php echo $currencySymbol; ?><?php echo number_format($blueprintData['monthly_targets']['debt_reduction_target'], 2); ?></p>
                        <p class="text-sm text-gray-600">Monthly debt payoff target</p>
                    </div>
                    <div class="bg-purple-50 p-6 rounded-xl shadow-lg text-center">
                        <div class="text-3xl mb-2">💰</div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Wealth Goal</h3>
                        <p class="text-2xl font-bold text-purple-600"><?php echo $currencySymbol; ?><?php echo number_format($blueprintData['monthly_targets']['wealth_accumulation_goal'], 2); ?></p>
                        <p class="text-sm text-gray-600">Monthly wealth accumulation</p>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <!-- PDF Download CTA -->
            <div class="text-center mt-10 pdf-hide">
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <button id="downloadPdfButton" onclick="generatePdf()" class="px-8 py-4 bg-indigo-600 text-white font-bold text-lg rounded-xl shadow-lg hover:bg-indigo-700 transition duration-300 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-indigo-300">
                        Download Blueprint as PDF
                    </button>
                    <button onclick="openUploadModal()" class="px-8 py-4 bg-violet-600 text-white font-bold text-lg rounded-xl shadow-lg hover:bg-violet-700 transition duration-300 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-violet-300">
                        Analyze Another PDF
                    </button>
                </div>
                <p class="text-sm text-gray-500 mt-3">Keep this blueprint handy as you adjust your spending habits.</p>
            </div>

        </div>
    </div>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
// Initialize the pie chart when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Use the Chart.js version instead of the CSS gradient version
    renderBlueprintChart();
});

function renderBlueprintChart() {
    const chartRing = document.getElementById('blueprintChartRing');
    if (!chartRing) {
        console.error('blueprintChartRing element not found');
        return;
    }

    // Clear any existing chart
    const existingCanvas = chartRing.querySelector('canvas');
    if (existingCanvas) {
        existingCanvas.remove();
    }

    // Create canvas for the pie chart
    const canvas = document.createElement('canvas');
    canvas.width = 250;
    canvas.height = 250;
    canvas.style.position = 'absolute';
    canvas.style.top = '0';
    canvas.style.left = '0';
    chartRing.appendChild(canvas);

    const ctx = canvas.getContext('2d');
    if (!ctx) {
        console.error('Could not get 2D context from canvas');
        return;
    }

    // Get data from blueprintData (passed from PHP)
    const needsPercent = <?php echo isset($blueprintData['wealth_allocation']) ? ($blueprintData['wealth_allocation']['needs_percent'] ?? 50) : 50; ?>;
    const wantsPercent = <?php echo isset($blueprintData['wealth_allocation']) ? ($blueprintData['wealth_allocation']['wants_percent'] ?? 30) : 30; ?>;
    const savingsPercent = <?php echo isset($blueprintData['wealth_allocation']) ? ($blueprintData['wealth_allocation']['savings_percent'] ?? 20) : 20; ?>;

    // Pie chart data
    const data = {
        labels: ['Needs', 'Wants', 'Savings'],
        datasets: [{
            data: [needsPercent, wantsPercent, savingsPercent],
            backgroundColor: [
                '#10B981', // emerald-500 for needs
                '#F59E0B', // amber-500 for wants
                '#3B82F6'  // blue-500 for savings
            ],
            borderWidth: 0
        }]
    };

    // Create pie chart
    try {
        new Chart(ctx, {
            type: 'doughnut',
            data: data,
            options: {
                responsive: false,
                maintainAspectRatio: false,
                cutout: '60%', // Creates the ring effect
                plugins: {
                    legend: {
                        display: false // Hide legend, we show it separately
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed + '%';
                            }
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error creating chart:', error);
    }
}

async function generatePdf() {
    const button = document.getElementById('downloadPdfButton');
    const originalText = button.textContent;
    button.textContent = 'Generating PDF...';
    button.disabled = true;

    try {
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p', 'mm', 'a4');

        // Get the blueprint content container
        const element = document.getElementById('blueprint-content-container');

        // Temporarily hide the PDF download section for capture
        const pdfHideElements = element.querySelectorAll('.pdf-hide');
        pdfHideElements.forEach(el => el.style.display = 'none');

        // Use html2canvas to capture the content
        const canvas = await html2canvas(element, {
            scale: 2,
            useCORS: true,
            allowTaint: true,
            backgroundColor: '#ffffff'
        });

        // Restore the PDF download section visibility
        pdfHideElements.forEach(el => el.style.display = '');

        const imgData = canvas.toDataURL('image/png');
        const imgWidth = 210; // A4 width in mm
        const pageHeight = 295; // A4 height in mm
        const imgHeight = (canvas.height * imgWidth) / canvas.width;
        let heightLeft = imgHeight;

        let position = 0;

        // Add first page
        pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
        heightLeft -= pageHeight;

        // Add additional pages if content is longer
        while (heightLeft >= 0) {
            position = heightLeft - imgHeight;
            pdf.addPage();
            pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;
        }

        // Save the PDF
        const filename = 'wealth-blueprint-' + new Date().toISOString().split('T')[0] + '.pdf';
        pdf.save(filename);

    } catch (error) {
        console.error('Error generating PDF:', error);
        alert('Error generating PDF. Please try again.');
    } finally {
        button.textContent = originalText;
        button.disabled = false;
    }
}
</script>

<style>
/* Blueprint Custom CSS for the simulated chart */
.blueprint-chart-ring {
    width: 250px;
    height: 250px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    position: relative;
}
.blueprint-chart-inner {
    width: 150px;
    height: 150px;
    background-color: #ffffff;
    border-radius: 50%;
    position: absolute;
    z-index: 10;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
}
/* Print Styles for PDF */
@media print {
    body { background-color: white !important; }
    .no-print { display: none !important; }
    .pdf-hide { display: none !important; }
    #blueprint-content-container { box-shadow: none !important; }
}
</style>