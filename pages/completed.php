<?php
require_once "../bootstrap.php";
require_once "../services/ServerService.php";
require_once "../services/DotEnvService.php";
require_once "../services/ValidationService.php";
require_once "../database/Session.php";
require_once "../database/Click.php";

(new DotEnvService(__DIR__ . "/../.env"))->load();

// Get the current session and delete all clicks
$aff_sub4 = ValidationService::affSub4();
$session = new Session();
$current_session = $session->get(ServerService::getIpAddress(), $aff_sub4);
if (!$current_session) {
    echo "Invalid request attempted";
    die();
}

$username = htmlspecialchars((string)$current_session["username"]);
$followers = (int)$current_session["followers"];
$platform_label = $current_session["platform"] === "tiktok" ? "TikTok" : "Instagram";
$followers_label = number_format($followers);

$click = new Click();
$click->deleteBySessionId($current_session["id"]);

// Remove the aff_sub4 cookie
if (isset($_COOKIE["aff_sub4"])) {
    unset($_COOKIE["aff_sub4"]);
    setcookie("aff_sub4", "", time() - 3600, "/");
}

$app_name = (string)getenv("APP_NAME");
$app_version = (string)getenv("APP_VERSION");
$support_email = (string)getenv("SUPPORT_EMAIL");

// Cache-bust CSS by its last-modified time so browsers always fetch the current build
$css_file = __DIR__ . "/../assets/css/output.css";
$css_ver = is_file($css_file) ? filemtime($css_file) : $app_version;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= htmlspecialchars($app_name) ?> — Order placed</title>
    <link rel="stylesheet" href="../assets/css/output.css?v=<?= htmlspecialchars((string)$css_ver) ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
</head>
<body class="bg-canvas text-primary antialiased ambient">
<div class="min-h-screen w-full flex flex-col justify-center px-5 py-8">
    <div class="w-full max-w-[440px] mx-auto">

        <header class="flex items-center gap-3 mb-7">
            <span class="logo-ring">
                <img src="../assets/imgs/ig-interaction.png" alt="">
            </span>
            <span class="text-[17px] font-bold text-white tracking-tight"><?= htmlspecialchars($app_name) ?></span>
        </header>

        <section class="glass-card p-6 text-center">

            <div class="success-badge mx-auto mb-5">
                <i class="ti ti-check text-3xl text-white"></i>
            </div>

            <h1 class="text-[26px] font-extrabold leading-tight tracking-tight text-white mb-2">
                Order <span class="text-gradient">placed</span>
            </h1>

            <p class="text-[15px] text-neutral-400 leading-relaxed mb-6">
                Your follower delivery has started. Most orders complete within
                <span class="text-white font-semibold">24–72 hours</span>.
            </p>

            <div class="rounded-2xl border border-white/10 bg-black/30 text-left mb-6">
                <div class="summary-row">
                    <span class="text-[13px] text-neutral-500">Account</span>
                    <span class="text-[14px] font-semibold text-white truncate">@<?= $username ?></span>
                </div>
                <div class="summary-row">
                    <span class="text-[13px] text-neutral-500">Platform</span>
                    <span class="text-[14px] font-semibold text-white"><?= htmlspecialchars($platform_label) ?></span>
                </div>
                <div class="summary-row">
                    <span class="text-[13px] text-neutral-500">Package</span>
                    <span class="text-[14px] font-semibold text-white"><?= $followers_label ?> followers</span>
                </div>
                <div class="summary-row">
                    <span class="text-[13px] text-neutral-500">Status</span>
                    <span class="pill">
                        <span class="pulse-dot"></span>
                        <span class="text-[12px] font-medium text-neutral-200">Delivery in progress</span>
                    </span>
                </div>
            </div>

            <p class="text-[13px] text-neutral-500 leading-relaxed">
                Keep your profile <span class="text-neutral-300 font-medium">public</span> during delivery.
                We never ask for your password.
            </p>

            <?php if ($support_email !== ""): ?>
            <p class="text-[13px] text-neutral-500 mt-4">
                Questions?
                <a href="mailto:<?= htmlspecialchars($support_email) ?>"
                   class="text-fuchsia-400 hover:text-fuchsia-300 transition-colors duration-200">
                    <?= htmlspecialchars($support_email) ?>
                </a>
            </p>
            <?php endif; ?>
        </section>

        <footer class="mt-6 px-1 text-center space-y-2">
            <p class="text-[12px] text-neutral-500 leading-relaxed">
                Free through partner offers. Delivery typically starts within a few hours. Account must be public.
            </p>
            <p class="text-[11px] text-neutral-600 leading-relaxed">
                Not affiliated with Instagram or TikTok. Offers are provided by third-party partners.
                We never ask for your password.
            </p>
            <?php if ($app_version !== ""): ?>
            <p class="text-[11px] text-neutral-700">v<?= htmlspecialchars($app_version) ?></p>
            <?php endif; ?>
        </footer>
    </div>
</div>
</body>
</html>
