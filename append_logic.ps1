$filePath = "d:\system\study-is-funny\api\sessions.php"
$content = Get-Content $filePath -Raw
$newFunctions = @"

function purchaseStudentSession() {
    try {
        $client = `$GLOBALS['mongoClient'];
        $databaseName = `$GLOBALS['databaseName'];

        $phone = `$_GET['phone'] ?? '';
        $sessionNumber = (int)(`$_GET['sessionNumber'] ?? `$_GET['session_number'] ?? 0);
        $subject = `$_GET['subject'] ?? '';
        $grade = `$_GET['grade'] ?? '';

        if (!`$phone || !`$sessionNumber || !`$subject || !`$grade) {
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            return;
        }

        // Map subject/grade to physical collection
        `$targetCollection = null;
        `$normSubject = normalizeSubject(`$subject);
        `$normGrade = strtolower(trim(`$grade));
        
        if (`$normGrade === 'senior1') {
            if (`$normSubject === 'mathematics') `$targetCollection = 'senior1_math';
        } elseif (`$normGrade === 'senior2') {
            if (`$normSubject === 'mathematics') `$targetCollection = 'senior2_pure_math';
            elseif (`$normSubject === 'mechanics') `$targetCollection = 'senior2_mechanics';
            elseif (`$normSubject === 'physics') `$targetCollection = 'senior2_physics';
        } elseif (`$normGrade === 'senior3') {
            if (`$normSubject === 'mathematics') `$targetCollection = 'senior3_math';
            elseif (`$normSubject === 'physics') `$targetCollection = 'senior3_physics';
            elseif (`$normSubject === 'statistics') `$targetCollection = 'senior3_statistics';
        }

        if (!`$targetCollection) {
            echo json_encode(['success' => false, 'message' => 'Invalid subject or grade (' . `$grade . ' / ' . `$subject . ')']);
            return;
        }

        // Generate phone variations
        `$phoneVariations = [
            `$phone,
            normalizePhoneNumber(`$phone),
            convertTo20Format(`$phone),
        ];
        `$phoneVariations = array_unique(array_filter(`$phoneVariations));

        // 1. Find the student in the target collection
        `$query = new MongoDB\Driver\Query(['phone' => ['$in' => `$phoneVariations], 'isActive' => true]);
        `$cursor = `$client->executeQuery("`$databaseName.`$targetCollection", `$query);
        `$student = current(`$cursor->toArray());

        if (!`$student) {
            echo json_encode(['success' => false, 'message' => 'You are not enrolled in this (' . `$subject . ') subject for ' . `$grade . '. Please contact support.']);
            return;
        }

        // 2. Check balance and cost
        `$balance = isset(`$student->balance) ? (float)`$student->balance : 0;
        `$cost = (isset(`$student->paymentAmount) && (float)`$student->paymentAmount > 0) ? (float)`$student->paymentAmount : 80;

        if (`$balance < `$cost) {
            echo json_encode([
                'success' => false, 
                'message' => 'Insufficient balance. You need ' . `$cost . ' EGP but only have ' . `$balance . ' EGP.',
                'balance' => `$balance,
                'cost' => `$cost
            ]);
            return;
        }

        `$sessionKey = 'session_' . `$sessionNumber;
        
        // 3. Perform the purchase (Deduct balance and Grant access)
        `$bulk = new MongoDB\Driver\BulkWrite();
        `$bulk->update(
            ['_id' => `$student->_id],
            ['`$inc' => ['balance' => -`$cost], '`$set' => [
                `$sessionKey . '.online_session' => true,
                `$sessionKey . '.purchased_at' => date('Y-m-d\TH:i:s.v\Z'),
                `$sessionKey . '.attendanceStatus' => 'absence'
            ]],
            ['multi' => false]
        );
        `$client->executeBulkWrite("`$databaseName.`$targetCollection", `$bulk);

        // 4. Record Transaction
        `$transactionBulk = new MongoDB\Driver\BulkWrite();
        `$transactionBulk->insert([
            'studentId' => `$student->studentId ?? null,
            'studentName' => `$student->studentName ?? 'Student',
            'subject' => `$subject . ' (' . `$grade . ')',
            'type' => 'online_purchase',
            'amount' => `$cost,
            'previousBalance' => `$balance,
            'newBalance' => `$balance - `$cost,
            'note' => 'Automatic purchase for Session #' . `$sessionNumber . ' (Online)',
            'recordedBy' => 'system_online_purchase',
            'createdAt' => new MongoDB\BSON\UTCDateTime(time() * 1000)
        ]);
        `$client->executeBulkWrite("`$databaseName.transactions", `$transactionBulk);

        // 5. Sync to all_students_view
        `$syncBulk = new MongoDB\Driver\BulkWrite();
        `$syncBulk->update(
            ['phone' => ['$in' => `$phoneVariations], 'subject' => ['$regex' => `$subject, '`$options' => 'i']],
            ['`$inc' => ['balance' => -`$cost], '`$set' => [
                `$sessionKey . '.online_session' => true,
                `$sessionKey . '.purchased_at' => date('Y-m-d\TH:i:s.v\Z'),
                `$sessionKey . '.attendanceStatus' => 'absence'
            ]],
            ['multi' => false]
        );
        `$client->executeBulkWrite("`$databaseName.all_students_view", `$syncBulk);

        echo json_encode([
            'success' => true,
            'message' => 'Session purchased successfully!',
            'newBalance' => `$balance - `$cost
        ]);

    } catch (Exception `$e) {
        echo json_encode(['success' => false, 'message' => 'Purchase error: ' . `$e->getMessage()]);
    }
}

function normalizePhoneNumber(`$phone) {
    `$clean = preg_replace('/[^0-9]/', '', `$phone);
    if (strlen(`$clean) === 12 && substr(`$clean, 0, 2) === '20') {
        return '0' . substr(`$clean, 2);
    }
    if (strlen(`$clean) === 11 && substr(`$clean, 0, 1) === '0') {
        return `$clean;
    }
    return `$phone;
}

function convertTo20Format(`$phone) {
    `$clean = preg_replace('/[^0-9]/', '', `$phone);
    if (strlen(`$clean) === 11 && substr(`$clean, 0, 1) === '0') {
        return '+2' . `$clean;
    }
    if (strlen(`$clean) === 12 && substr(`$clean, 0, 2) === '20') {
        return '+' . `$clean;
    }
    return `$phone;
}

function normalizeSubject(`$subject) {
    `$s = strtolower(trim(`$subject));
    if (strpos(`$s, 'math') !== false) return 'mathematics';
    if (strpos(`$s, 'physics') !== false) return 'physics';
    if (strpos(`$s, 'mechanics') !== false) return 'mechanics';
    if (strpos(`$s, 'stat') !== false) return 'mathematics'; 
    return `$s;
}
"@

# Remove existing ?> at the end if it exists
if ($content.Trim().EndsWith("?>")) {
    $content = $content.TrimEnd(" `t`n`r").Substring(0, $content.TrimEnd(" `t`n`r").Length - 2)
}

$content += $newFunctions + "`n?>`n"
Set-Content -Path $filePath -Value $content -Encoding UTF8
