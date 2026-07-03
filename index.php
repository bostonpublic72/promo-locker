<?php
require_once "bootstrap.php";
require_once "services/DotEnvService.php";

(new DotEnvService(__DIR__ . "/.env"))->load();

$app_name = (string)getenv("APP_NAME");
$app_version = (string)getenv("APP_VERSION");
$support_email = (string)getenv("SUPPORT_EMAIL");

// Cache-bust CSS by its last-modified time so browsers always fetch the current build
$css_file = __DIR__ . "/assets/css/output.css";
$css_ver = is_file($css_file) ? filemtime($css_file) : $app_version;

// Create affsub4 if it doesn't exist already
$aff_sub4 = $_COOKIE["aff_sub4"] ?? null;
if ($aff_sub4 == null) {
    $uid = bin2hex(random_bytes(16));
    $expires_at = time() + 60 * 60 * 24 * 30; // Expires in 30 days
    $secure = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off")
        || (isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && $_SERVER["HTTP_X_FORWARDED_PROTO"] === "https");
    setcookie("aff_sub4", $uid, [
        "expires" => $expires_at,
        "path" => "/",
        "secure" => $secure,
        "httponly" => true,
        "samesite" => "Lax"
    ]);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= htmlspecialchars($app_name) ?></title>
    <link rel="stylesheet" href="./assets/css/output.css?v=<?= htmlspecialchars((string)$css_ver) ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.0/dist/cdn.min.js"></script>
</head>
<body class="bg-canvas text-primary antialiased ambient">
<div class="min-h-screen w-full flex flex-col justify-center px-5 py-4 md:py-8" x-data>
    <div class="w-full max-w-[440px] mx-auto">

        <header class="flex items-center justify-between mb-4 md:mb-7">
            <div class="flex items-center gap-3">
                <span class="logo-ring">
                    <img src="assets/imgs/ig-interaction.png" alt="">
                </span>
                <span class="text-[17px] font-bold text-white tracking-tight"><?= htmlspecialchars($app_name) ?></span>
            </div>
            <span class="pill">
                <i class="ti ti-lock text-fuchsia-400"></i>
                No password
            </span>
        </header>

        <div class="mb-4 md:mb-6">
            <div class="flex gap-1.5 mb-2.5">
                <div class="step-seg" :class="$store.form.page >= 0 && 'step-seg-on'"></div>
                <div class="step-seg" :class="$store.form.page >= 1 && 'step-seg-on'"></div>
                <div class="step-seg" :class="$store.form.page >= 2 && 'step-seg-on'"></div>
            </div>
            <p class="text-[12px] font-medium uppercase tracking-widest text-neutral-500"
               x-text="'Step ' + ($store.form.page + 1) + ' of 3'"></p>
        </div>

        <section class="glass-card p-4 md:p-6">

            <div x-show="$store.form.errors.length >= 1" x-cloak class="w-full mb-4 space-y-2">
                <template x-for="error in $store.form.errors">
                    <div class="flex items-start gap-2.5 rounded-xl border border-red-400/25 bg-red-950/70 px-3.5 py-2.5">
                        <i class="ti ti-alert-circle text-red-300 text-base shrink-0 mt-px"></i>
                        <p x-text="error" class="text-[13px] font-medium text-red-200 leading-snug"></p>
                    </div>
                </template>
            </div>

            <!-- Step 1: Username + platform -->
            <form @submit.prevent="$store.form.getFollowersPage()"
                  x-show="$store.form.page === 0"
                  x-transition:enter="transition ease-out duration-200"
                  x-transition:enter-start="opacity-0"
                  x-transition:enter-end="opacity-100"
                  class="space-y-4 md:space-y-6">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-fuchsia-400/80 mb-2">🎁 Today's free promo</p>
                    <h1 class="text-[26px] font-extrabold leading-tight tracking-tight text-white mb-1.5">
                        Get <span class="text-gradient">free followers</span>
                    </h1>
                    <p class="text-[15px] text-neutral-400 leading-relaxed">
                        Enter your username and choose your platform. No password, ever.
                    </p>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="username" class="text-[12px] font-semibold uppercase tracking-widest text-neutral-400">
                        Username
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[15px] font-semibold text-neutral-500 pointer-events-none">@</span>
                        <input type="text"
                               id="username"
                               placeholder="yourname"
                               x-model="$store.form.data.username"
                               required
                               class="field-input">
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    <p class="text-[12px] font-semibold uppercase tracking-widest text-neutral-400">Platform</p>
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button"
                                @click="$store.form.setPlatform('instagram')"
                                class="tile flex items-center gap-3 p-3.5"
                                :class="$store.form.data.platform === 'instagram' && 'tile-selected'">
                            <span class="icon-chip chip-ig">
                                <i class="ti ti-brand-instagram text-xl text-white"></i>
                            </span>
                            <span class="text-[15px] font-bold text-white">Instagram</span>
                        </button>
                        <button type="button"
                                @click="$store.form.setPlatform('tiktok')"
                                class="tile flex items-center gap-3 p-3.5"
                                :class="$store.form.data.platform === 'tiktok' && 'tile-selected-tt'">
                            <span class="icon-chip chip-tt">
                                <i class="ti ti-brand-tiktok text-xl"></i>
                            </span>
                            <span class="text-[15px] font-bold text-white">TikTok</span>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-primary">
                    Continue
                    <i class="ti ti-arrow-right text-lg"></i>
                </button>
            </form>

            <!-- Step 2: Follower amount -->
            <form @submit.prevent="$store.form.getOffersPage()"
                  x-show="$store.form.page === 1"
                  x-transition:enter="transition ease-out duration-200"
                  x-transition:enter-start="opacity-0"
                  x-transition:enter-end="opacity-100"
                  class="space-y-5">
                <div>
                    <h2 class="text-[26px] font-extrabold leading-tight tracking-tight text-white mb-1.5">
                        Pick your <span class="text-gradient">package</span>
                    </h2>
                    <p class="text-[15px] text-neutral-400 leading-relaxed">
                        Larger packages require more offers to complete.
                    </p>
                </div>

                <div class="space-y-3 pt-1">
                    <button type="button"
                            @click="$store.form.setFollowerAmount(250)"
                            class="tile w-full flex items-center gap-4 p-4"
                            :class="$store.form.data.amount === 250 && 'tile-selected'">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-baseline gap-1.5">
                                <span class="text-[22px] font-extrabold text-white leading-none">250</span>
                                <span class="text-[13px] font-medium text-neutral-400">followers</span>
                            </div>
                            <p class="text-[12px] text-neutral-500 mt-1.5">Complete 1 offer</p>
                        </div>
                        <span class="radio-dot"><i class="ti ti-check text-[12px] text-white"></i></span>
                    </button>

                    <button type="button"
                            @click="$store.form.setFollowerAmount(500)"
                            class="tile w-full flex items-center gap-4 p-4"
                            :class="$store.form.data.amount === 500 && 'tile-selected'">
                        <span class="badge-pop"><i class="ti ti-flame text-[11px]"></i>Most popular</span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-baseline gap-1.5">
                                <span class="text-[22px] font-extrabold text-white leading-none">500</span>
                                <span class="text-[13px] font-medium text-neutral-400">followers</span>
                            </div>
                            <p class="text-[12px] text-neutral-500 mt-1.5">Complete 2 offers</p>
                        </div>
                        <span class="radio-dot"><i class="ti ti-check text-[12px] text-white"></i></span>
                    </button>

                    <button type="button"
                            @click="$store.form.setFollowerAmount(1000)"
                            class="tile w-full flex items-center gap-4 p-4"
                            :class="$store.form.data.amount === 1000 && 'tile-selected'">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-baseline gap-1.5">
                                <span class="text-[22px] font-extrabold text-white leading-none">1,000</span>
                                <span class="text-[13px] font-medium text-neutral-400">followers</span>
                            </div>
                            <p class="text-[12px] text-neutral-500 mt-1.5">Complete 3 offers</p>
                        </div>
                        <span class="radio-dot"><i class="ti ti-check text-[12px] text-white"></i></span>
                    </button>
                </div>

                <button type="submit" class="btn-primary">
                    Continue
                    <i class="ti ti-arrow-right text-lg"></i>
                </button>
            </form>

            <!-- Step 3: Offers -->
            <div x-show="$store.form.page === 2"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 class="space-y-4">
                <div>
                    <h2 class="text-[26px] font-extrabold leading-tight tracking-tight text-white mb-1.5">
                        Almost <span class="text-gradient">there</span>
                    </h2>
                    <p class="text-[15px] text-neutral-400 leading-relaxed" x-text="$store.form.step3Subhead()"></p>
                </div>

                <!-- Skeleton loading -->
                <div x-show="$store.form.loading" x-cloak class="flex flex-col gap-3 max-h-[420px]">
                    <div class="h-[68px] skeleton"></div>
                    <div class="h-[68px] skeleton"></div>
                    <div class="h-[68px] skeleton"></div>
                </div>

                <!-- Offer list -->
                <div x-show="!$store.form.loading && $store.form.offers.length >= 1"
                     class="offers-scroll flex flex-col gap-3 max-h-[420px] overflow-y-auto pr-0.5">
                    <template x-for="offer in $store.form.offers">
                        <div @click="$store.form.openUrl(offer)" class="offer-row">
                            <img x-bind:src="offer.picture"
                                 x-bind:alt="offer.name_short"
                                 class="w-12 h-12 rounded-xl object-cover shrink-0 ring-1 ring-white/10">
                            <div class="flex-1 min-w-0">
                                <h3 x-text="offer.name_short" class="text-[15px] font-bold text-white truncate"></h3>
                                <p x-text="offer.adcopy" class="text-[12px] text-neutral-400 line-clamp-2 leading-snug mt-0.5"></p>
                            </div>
                            <span class="offer-arrow"><i class="ti ti-chevron-right text-base"></i></span>
                        </div>
                    </template>
                </div>

                <!-- Empty state -->
                <div x-show="!$store.form.loading && $store.form.offers.length === 0"
                     x-cloak
                     class="tile px-4 py-8 text-center cursor-default">
                    <i class="ti ti-hourglass-empty text-2xl text-neutral-500"></i>
                    <p class="text-[14px] text-neutral-400 mt-2">No offers available right now. Try again in a moment.</p>
                </div>

                <!-- Polling status -->
                <div x-show="!$store.form.loading && $store.form.offers.length >= 1"
                     class="flex justify-center pt-1">
                    <span class="pill">
                        <span class="pulse-dot"></span>
                        Waiting for offer completion — checking automatically
                    </span>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-3 gap-2 mt-5">
            <div class="trust-item">
                <i class="ti ti-lock"></i>
                <span>No password<br>needed</span>
            </div>
            <div class="trust-item">
                <i class="ti ti-bolt"></i>
                <span>Starts within<br>hours</span>
            </div>
            <div class="trust-item">
                <i class="ti ti-eye"></i>
                <span>Public accounts<br>only</span>
            </div>
        </div>

        <footer class="mt-6 px-1 text-center space-y-2">
            <p class="text-[12px] text-neutral-500 leading-relaxed">
                Free through partner offers. Delivery typically starts within a few hours. Account must be public.
            </p>
            <p class="text-[11px] text-neutral-600 leading-relaxed">
                Not affiliated with Instagram or TikTok. Offers are provided by third-party partners.
                We never ask for your password.
            </p>
            <?php if ($support_email !== ""): ?>
            <p class="text-[12px] text-neutral-500">
                Support: <a href="mailto:<?= htmlspecialchars($support_email) ?>" class="text-fuchsia-400 hover:text-fuchsia-300 transition-colors duration-200"><?= htmlspecialchars($support_email) ?></a>
            </p>
            <?php endif; ?>
        </footer>
    </div>
</div>

<script src="assets/js/main.js"></script>
</body>
</html>
