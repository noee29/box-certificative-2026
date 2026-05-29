<?php

// Authentication unit-style tests (simple, no external test framework).
session_start();
require("../config/database.php");

$tests = [];

function add_result(array &$tests, string $name, bool $passed, string $details = ""): void {
    $tests[] = ["name" => $name, "passed" => $passed, "details" => $details];
}

function assert_true($condition): bool {
    return $condition === true;
}

// Unit test: password hashing + verification
$plain = "TestPassword123!";
$hash = password_hash($plain, PASSWORD_DEFAULT);
add_result($tests, "Password hash verifies", assert_true(password_verify($plain, $hash)), "password_verify should return true");
add_result($tests, "Password hash rejects wrong value", assert_true(!password_verify("wrong", $hash)), "password_verify should return false");

// Optional DB-backed tests (run only if input provided)
$inputEmail = trim($_POST["email"] ?? "");
$inputPassword = $_POST["password"] ?? "";

if ($inputEmail !== "" && $inputPassword !== "") {
    $req = $bdd->prepare("SELECT * FROM users WHERE email = ?");
    $req->execute([$inputEmail]);
    $user = $req->fetch();

    add_result($tests, "User exists for provided email", assert_true((bool) $user), $user ? "User found" : "No user found");
    if ($user) {
        $ok = password_verify($inputPassword, $user['password']);
        add_result($tests, "Password matches DB hash", assert_true($ok), $ok ? "Match" : "Mismatch");

        $badOk = password_verify($inputPassword . "_wrong", $user['password']);
        add_result($tests, "Wrong password is rejected", assert_true(!$badOk), $badOk ? "Unexpected match" : "Rejected");
    }

    $fakeEmail = "no_such_user_" . time() . "@example.test";
    $req = $bdd->prepare("SELECT * FROM users WHERE email = ?");
    $req->execute([$fakeEmail]);
    $missing = $req->fetch();
    add_result($tests, "Non-existent email is rejected", assert_true(!$missing), $missing ? "Unexpected user found" : "Not found");
} else {
    add_result($tests, "DB auth tests skipped", true, "Provide email/password to run DB checks");
}

$passCount = count(array_filter($tests, fn($t) => $t['passed']));
$totalCount = count($tests);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Authentication Tests</title>
</head>
<body>

    <h1>Authentication Tests</h1>
    <p>Passed: <?= $passCount ?> / <?= $totalCount ?></p>

    <form action="" method="POST">
        <input type="email" name="email" placeholder="Existing account email">
        <input type="password" name="password" placeholder="Password">
        <button type="submit">Run DB Checks</button>
    </form>

    <ul>
        <?php foreach ($tests as $test): ?>
            <li>
                <?= $test['passed'] ? 'PASS' : 'FAIL' ?> -
                <?= htmlspecialchars($test['name']) ?>
                <?php if ($test['details'] !== ''): ?>
                    (<?= htmlspecialchars($test['details']) ?>)
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>

</body>
</html>
