<?php
/**
 * DOT Bank - Doctors of Tomorrow Question Bank
 * Main Entry Point — Arabic RTL Public Landing Page
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

if (!is_installed()) {
    header('Location: ' . url('setup.php'));
    exit;
}

if (Auth::check()) {
    if (Auth::isAdmin()) {
        header('Location: ' . url('admin/dashboard.php'));
    } else {
        header('Location: ' . url('student/dashboard.php'));
    }
    exit;
}

$loginUrl = url('login.php');
$brandName = APP_NAME;
$brandFullName = APP_FULL_NAME;
$year = date('Y');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $brandName ?> — بنك أسئلة ومنصة اختبارات تعليمية</title>
    <link rel="stylesheet" href="<?= url('assets/css/app.css?v=landing-1') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" integrity="sha384-PPIZEGYM1v8zp5Py7UjFb79S58UeqCL9pYVnVPURKEqvioPROaVAJKKLzvH2rDnI" crossorigin="anonymous">
</head>
<body class="lp-body">
    <header class="lp-header">
        <div class="lp-container lp-header-inner">
            <a href="<?= $loginUrl ?>" class="lp-brand">
                <div class="lp-brand-icon">DOT</div>
                <div class="lp-brand-text">
                    <span class="lp-brand-name"><?= $brandName ?></span>
                </div>
            </a>
            <nav class="lp-header-nav" aria-label=" التنقل الرئيسي">
                <a href="#features" class="lp-nav-link">المميزات</a>
                <a href="#question-types" class="lp-nav-link">أنواع الأسئلة</a>
                <a href="#how-it-works" class="lp-nav-link">كيف يعمل</a>
            </nav>
            <a href="<?= $loginUrl ?>" class="btn btn-primary btn-sm lp-login-btn">
                <i class="fa-solid fa-arrow-right-to-bracket" aria-hidden="true"></i>
                <span>دخول</span>
            </a>
        </div>
    </header>

    <main class="lp-main">
        <!-- Hero Section -->
        <section class="lp-hero">
            <div class="lp-container lp-hero-inner">
                <div class="lp-hero-copy">
                    <span class="lp-eyebrow">
                        <i class="fa-solid fa-book-medical" aria-hidden="true"></i>
                        بنك أسئلة ومنصة اختبارات تعليمية
                    </span>
                    <h1 class="lp-hero-title">تعلّم، اختبر نفسك، وراجع نتائجك بطريقة أكثر تنظيمًا.</h1>
                    <p class="lp-hero-desc">DOT Bank منصة متكاملة لتنظيم بنك الأسئلة الطبية وإعداد اختبارات تفاعلية لمراجعة المواد الدراسية وتتبع تقدمك الأكاديمي.</p>
                    <div class="lp-hero-actions">
                        <a href="<?= $loginUrl ?>" class="btn btn-primary btn-lg">
                            <i class="fa-solid fa-rocket" aria-hidden="true"></i>
                            ابدأ الآن
                        </a>
                        <a href="<?= $loginUrl ?>" class="btn btn-secondary btn-lg">
                            <i class="fa-solid fa-arrow-right-to-bracket" aria-hidden="true"></i>
                            تسجيل الدخول
                        </a>
                    </div>
                </div>
                <div class="lp-hero-visual" aria-hidden="true">
                    <div class="lp-hero-card lp-hero-card--violet">
                        <div class="lp-hero-card-icon"><i class="fa-solid fa-layer-group"></i></div>
                        <span class="lp-hero-card-label">الوحدات</span>
                    </div>
                    <div class="lp-hero-card lp-hero-card--apricot">
                        <div class="lp-hero-card-icon"><i class="fa-solid fa-pen-to-square"></i></div>
                        <span class="lp-hero-card-label">الاختبارات</span>
                    </div>
                    <div class="lp-hero-card lp-hero-card--teal">
                        <div class="lp-hero-card-icon"><i class="fa-solid fa-circle-check"></i></div>
                        <span class="lp-hero-card-label">النتائج</span>
                    </div>
                    <div class="lp-hero-orbit lp-hero-orbit--1"></div>
                    <div class="lp-hero-orbit lp-hero-orbit--2"></div>
                </div>
            </div>
        </section>

        <!-- What is DOT Bank? -->
        <section class="lp-about">
            <div class="lp-container lp-about-inner">
                <span class="lp-section-eyebrow">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                    ما هو DOT Bank؟
                </span>
                <h2 class="lp-section-title">بيئة منظمة لإدارة الأسئلة والتدريب الفعّال</h2>
                <p class="lp-about-desc">يوفر DOT Bank بيئة منظمة لإدارة بنك الأسئلة وإنشاء اختبارات مخصصة ومراجعة النتائج بعد كل اختبار. يدعم المنصة ستة أنواع من الأسئلة مع خيارات توزيع متعددة.</p>
            </div>
        </section>

        <!-- Features -->
        <section class="lp-features" id="features">
            <div class="lp-container">
                <span class="lp-section-eyebrow lp-section-eyebrow--center">
                    <i class="fa-solid fa-star" aria-hidden="true"></i>
                    المميزات
                </span>
                <h2 class="lp-section-title lp-section-title--center">كل ما تحتاجه في مكان واحد</h2>
                <div class="lp-features-grid">
                    <div class="lp-feature-card">
                        <div class="lp-feature-icon"><i class="fa-solid fa-book-open"></i></div>
                        <h3 class="lp-feature-title">بنك أسئلة منظم</h3>
                        <p class="lp-feature-desc">تنظيم الأسئلة داخل الوحدات والمواد بشكل هرمي يسهّل البحث والاستخدام.</p>
                    </div>
                    <div class="lp-feature-card">
                        <div class="lp-feature-icon lp-feature-icon--apricot"><i class="fa-solid fa-pen-to-square"></i></div>
                        <h3 class="lp-feature-title">إنشاء الاختبارات</h3>
                        <p class="lp-feature-desc">اختيار المواد وتكوين اختبار وفق التوزيع المطلوب مع خوارزميات تخطيط دقيقة.</p>
                    </div>
                    <div class="lp-feature-card">
                        <div class="lp-feature-icon"><i class="fa-solid fa-shapes"></i></div>
                        <h3 class="lp-feature-title">أنواع متعددة من الأسئلة</h3>
                        <p class="lp-feature-desc">MCQ، True/False، Match، Complete، Compare، Essay — ستة أنواع تغطي احتياجات التعلم.</p>
                    </div>
                    <div class="lp-feature-card">
                        <div class="lp-feature-icon lp-feature-icon--apricot"><i class="fa-solid fa-chart-bar"></i></div>
                        <h3 class="lp-feature-title">نتائج ومراجعة</h3>
                        <p class="lp-feature-desc">عرض النتيجة ومراجعة الإجابات بعد انتهاء الاختبار مع توضيح الإجابات الصحيحة.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Question Types -->
        <section class="lp-qtypes" id="question-types">
            <div class="lp-container">
                <span class="lp-section-eyebrow lp-section-eyebrow--center">
                    <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                    أنواع الأسئلة
                </span>
                <h2 class="lp-section-title lp-section-title--center">ستة أنواع لتنويع التقييم</h2>
                <div class="lp-qtypes-grid">
                    <div class="lp-qtype-item">
                        <div class="lp-qtype-icon"><i class="fa-solid fa-circle-dot"></i></div>
                        <div class="lp-qtype-label">اختيار من متعدد</div>
                        <div class="lp-qtype-sub">MCQ</div>
                    </div>
                    <div class="lp-qtype-item">
                        <div class="lp-qtype-icon"><i class="fa-solid fa-circle-check"></i></div>
                        <div class="lp-qtype-label">صح أو خطأ</div>
                        <div class="lp-qtype-sub">True / False</div>
                    </div>
                    <div class="lp-qtype-item">
                        <div class="lp-qtype-icon"><i class="fa-solid fa-right-left"></i></div>
                        <div class="lp-qtype-label">المطابقة</div>
                        <div class="lp-qtype-sub">Match</div>
                    </div>
                    <div class="lp-qtype-item">
                        <div class="lp-qtype-icon"><i class="fa-solid fa-pen"></i></div>
                        <div class="lp-qtype-label">إكمال</div>
                        <div class="lp-qtype-sub">Complete</div>
                    </div>
                    <div class="lp-qtype-item">
                        <div class="lp-qtype-icon"><i class="fa-solid fa-scale-balanced"></i></div>
                        <div class="lp-qtype-label">مقارنة</div>
                        <div class="lp-qtype-sub">Compare</div>
                    </div>
                    <div class="lp-qtype-item">
                        <div class="lp-qtype-icon"><i class="fa-solid fa-file-lines"></i></div>
                        <div class="lp-qtype-label">سؤال مقالي</div>
                        <div class="lp-qtype-sub">Essay</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works -->
        <section class="lp-steps" id="how-it-works">
            <div class="lp-container">
                <span class="lp-section-eyebrow lp-section-eyebrow--center">
                    <i class="fa-solid fa-shoe-prints" aria-hidden="true"></i>
                    كيف يعمل
                </span>
                <h2 class="lp-section-title lp-section-title--center">ثلاث خطوات بسيطة</h2>
                <div class="lp-steps-grid">
                    <div class="lp-step-card">
                        <div class="lp-step-number">١</div>
                        <h3 class="lp-step-title">اختر</h3>
                        <p class="lp-step-desc">اختر الوحدة والمواد والأسئلة المناسبة.</p>
                    </div>
                    <div class="lp-step-connector" aria-hidden="true"><i class="fa-solid fa-chevron-left"></i></div>
                    <div class="lp-step-card">
                        <div class="lp-step-number lp-step-number--apricot">٢</div>
                        <h3 class="lp-step-title">اختبر نفسك</h3>
                        <p class="lp-step-desc">أجب عن الاختبار بأنواعه المختلفة.</p>
                    </div>
                    <div class="lp-step-connector" aria-hidden="true"><i class="fa-solid fa-chevron-left"></i></div>
                    <div class="lp-step-card">
                        <div class="lp-step-number lp-step-number--teal">٣</div>
                        <h3 class="lp-step-title">راجعي نتيجتك</h3>
                        <p class="lp-step-desc">شاهدي نتيجتك وراجعي إجاباتك.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Final CTA -->
        <section class="lp-cta">
            <div class="lp-container lp-cta-inner">
                <h2 class="lp-cta-title">جاهزة تبدأ؟</h2>
                <p class="lp-cta-desc">ادخلي إلى DOT Bank وابدئي التدريب على بنك الأسئلة الخاص بك.</p>
                <a href="<?= $loginUrl ?>" class="btn btn-primary btn-lg lp-cta-btn">
                    <i class="fa-solid fa-arrow-right-to-bracket" aria-hidden="true"></i>
                    دخول إلى المنصة
                </a>
            </div>
        </section>
    </main>

    <footer class="lp-footer">
        <div class="lp-container lp-footer-inner">
            <div class="lp-footer-brand">
                <div class="lp-brand-icon lp-brand-icon--sm">DOT</div>
                <div class="lp-footer-copy">
                    <strong><?= $brandName ?></strong> — <?= $brandFullName ?>
                </div>
            </div>
            <div class="lp-footer-copy-small">
                &copy; <?= $year ?> Doctors of Tomorrow. All rights reserved.
            </div>
        </div>
    </footer>
</body>
</html>
