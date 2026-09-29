# لایف‌روس — هسته / LifeRuss Core

نسخهٔ ۱.۵.۰. هسته جدول‌ها، کاتالوگ، CRM، مسیر تحصیل، ریدایرکت، پایش ۴۰۴، ورود دومرحله‌ای، و پل Rank Math / Polylang را دارد. اسکیمای پایگاه ۱.۱.۰ است (`lr_not_found`).

Version 1.5.0. Core ships the tables, catalog, CRM, study paths, redirects, the 404 monitor, TOTP login, and the Rank Math / Polylang bridges. The database schema is 1.1.0 (`lr_not_found`).

## نصب / Install

نیاز: وردپرس ۶.۴ یا جدیدتر، PHP ۸.۱ یا جدیدتر، MySQL/MariaDB با `utf8mb4`.

Requirements: WordPress 6.4+, PHP 8.1+, MySQL or MariaDB with `utf8mb4`.

1. پوشهٔ `liferuss-core` را در `wp-content/plugins/` بگذارید.
2. از پیشخوان افزونهٔ «لایف‌روس — هسته» را فعال کنید. فعال‌سازی جدول‌ها را با `dbDelta` می‌سازد، نقش‌ها را همگام می‌کند، ارائه‌دهندگان رتبه و درخت خدمات را (اگر نباشند) می‌کارد، و پیوندهای یکتا را تازه می‌کند.
3. غیرفعال کردن داده را پاک نمی‌کند. حذف افزونه هم داده را نگه می‌دارد، مگر در **لایف‌روس ← تنظیمات ← امنیت** گزینهٔ حذف داده روشن شده باشد.

Copy the `liferuss-core` folder into `wp-content/plugins/`, then activate **لایف‌روس — هسته**. Activation creates tables, syncs roles, seeds ranking providers and the service tree, and flushes rewrites. Deactivation and uninstall keep data unless **LifeRuss → Settings → Security** enables data deletion.

بررسی استاندارد کد (اختیاری):

```bash
cd wp-content/plugins/liferuss-core
composer install
composer phpcs
```

## جدول‌ها / Tables

همه با پیشوند `{wpdb prefix}lr_` ساخته می‌شوند. موتور InnoDB. کلید خارجی فیزیکی فقط بین جدول‌های `lr_*` و با `ON DELETE RESTRICT` است. ارجاع به نوشته، کاربر، و ترم در لایهٔ برنامه کنترل می‌شود.

All use the `{wpdb prefix}lr_` prefix, InnoDB. Physical foreign keys exist only between `lr_*` tables (`ON DELETE RESTRICT`). References to posts, users, and terms are enforced in PHP.

فاز ۱:

- `lr_activity_logs` (فقط افزودنی، بدون `deleted_at`)
- `lr_redirects`
- `lr_not_found` (بدون `deleted_at`؛ پاک‌سازی با کرون)
- `lr_translations` (بدون `deleted_at`)
- `lr_relations` (بدون `deleted_at`)
- `lr_view_stats_daily` (کلید مرکب، بدون حذف نرم)
- `lr_ranking_providers`
- `lr_cities`
- `lr_universities`
- `lr_fields`
- `lr_university_fields`
- `lr_tuition_fees`
- `lr_dormitory_fees`
- `lr_university_approvals`
- `lr_university_rankings`
- `lr_admission_requirements`
- `lr_intakes`
- `lr_prep_programs`
- `lr_lesson_vocab` (بدون `deleted_at`)
- `lr_services`
- `lr_leads` (ستون `legacy_post_id` برای مهاجرت بعدی)
- `lr_lead_notes`
- `lr_lead_tasks`
- `lr_lead_files` (بدون `updated_at`)
- `lr_lead_status_history` (فقط افزودنی)
- `lr_admission_requests`
- `lr_exchange_requests`
- `lr_cargo_requests`
- `lr_trade_requests`

فاز ۲، همین حالا ساخته می‌شود چون ارزان است:

- `lr_quizzes`
- `lr_quiz_questions` (بدون `deleted_at`)

نسخهٔ اسکیما در گزینهٔ `lr_db_version` است. ارتقا با مقایسهٔ همین گزینه و اجرای دوبارهٔ `dbDelta` انجام می‌شود.

## نوع‌های محتوا و طبقه‌بندی / CPTs and taxonomies

| نوع | مسیر |
| --- | --- |
| `lr_university` | `/universities/` |
| `lr_field` | `/fields/` |
| `lr_city` | `/cities/` |
| `lr_guide` | `/russia-guide/` و `/russia-guide/{دسته}/{نامک}/` |
| `lr_course` | `/russian-language/` و `/russian-language/{دوره}/` |
| `lr_lesson` | `/russian-language/{دوره}/{درس}/` |
| `lr_faq` | عمومی نیست، REST و بازبینی دارد |
| `lr_scholarship` | `/scholarships/` و `/scholarships/{نامک}/` |
| `lr_testimonial` | عمومی نیست، REST و بازبینی دارد |

طبقه‌بندی‌ها: `lr_field_group`، `lr_guide_cat`، `lr_faq_group`، `lr_level`.

دانشگاه، رشته، و شهر یک ردیف سایهٔ ۱:۱ در `lr_universities` / `lr_fields` / `lr_cities` دارند. ذخیره، زباله‌دان، و حذف دائمی نوشته این ردیف را همگام می‌کند. تغییر نامک یک ریدایرکت ۳۰۱ در `lr_redirects` می‌نویسد و رانر فرانت همان ردیف را پیش از قالب اجرا می‌کند. اگر یک برگهٔ منتشرشده از قبل همان مسیر را داشته باشد (مثلاً برگهٔ قالب `/universities/`)، برگه بر بازنویسی نوع محتوا مقدم است.

وضعیت سفارشی `lr_archived` معادل `archived` در ردیف سایه است.

## نقش‌ها / Roles

نُه نقش ماتریس دسترسی:

| کلید | برچسب |
| --- | --- |
| `administrator` | مدیر ارشد |
| `lr_admin` | مدیر (محدود، بدون افزونه و پوسته و تنظیمات هسته) |
| `lr_content_manager` | مدیر محتوا |
| `lr_writer` | نویسنده |
| `lr_seo_manager` | مدیر سئو |
| `lr_consultant` | مشاور |
| `lr_exchange_operator` | اپراتور صرافی |
| `lr_cargo_operator` | اپراتور کارگو |
| `lr_trade_operator` | اپراتور تجارت |

همگام‌سازی نقش idempotent است. نقش‌های `lr_*` هر بار با ماتریس جایگزین می‌شوند. به `administrator` فقط قابلیت‌های لایف‌روس اضافه می‌شود و قابلیت‌های هستهٔ وردپرس حذف نمی‌شوند. هش ماتریس در `lr_roles_hash` است.

`lr_admin` همان الگوی مدیر محدود `modiriat-wordpress` است: منوی افزونه، پوسته، سفارشی‌ساز، به‌روزرسانی، و ابزار هسته پنهان و مسدود است، و این نقش نمی‌تواند کاربر `administrator` را ویرایش یا منصوب کند. نویسنده فقط پیوست‌های خودش را ویرایش می‌کند.

محدودهٔ پرس‌وجو:

- لید: `lr_manage_leads` همه را می‌بیند. مشاور و اپراتور فقط `consultant_id` خودشان را می‌بینند.
- درخواست پذیرش: مدیر همه را می‌بیند. مشاور فقط درخواست‌هایی را می‌بیند که لیدشان به او وصل است.
- صرافی، کارگو، تجارت: مدیر (`lr_manage_leads`) همه را می‌بیند. اپراتور ردیف‌های بدون مسئول و ردیف‌های خودش را می‌بیند تا صف کار جدید خالی نماند.

متای کاربر: `lr_phone`، `lr_telegram`، `lr_2fa_enabled` (خودِ ورود دومرحله‌ای هنوز نیست)، `lr_allowed_services`، `lr_last_login_at`، `lr_deleted_at` (ورود را می‌بندد). نقش مشاور به‌طور پیش‌فرض سرویس `education` می‌گیرد و هر اپراتور گروه سرویس خودش را.

## تنظیمات / Settings

**لایف‌روس ← تنظیمات لایف‌روس**. گروه‌ها: عمومی، تماس و شبکه‌ها، پاورقی، نرخ ارز دستی (با `updated_at` و `updated_by`؛ ذخیره نرخ، `amount_usd` شهریه‌های همان ارز را همان لحظه دوباره حساب می‌کند و در گزارش فعالیت می‌نویسد)، اعلان تلگرام و ایمیل به تفکیک سرویس، اسکریپت رهگیری، متن فرم، امنیت، زبان، پشتیبان. تزریق اسکریپت در قالب، Polylang، و job پشتیبان در این نسخه اجرا نمی‌شوند.

## مهاجرت `liferuss-leads`

مهاجرت idempotent و قابل ادامه است. از پنل **CRM — همه لیدها** دکمهٔ «مهاجرت لیدهای قبلی» (با گزینهٔ dry-run) یا از WP-CLI:

```bash
wp liferuss migrate-leads --dry-run
wp liferuss migrate-leads --batch=50
```

- `legacy_post_id` به شناسهٔ نوشتهٔ CPT `liferuss_lead` وصل می‌شود. اجرای دوباره همان ردیف را دوباره نمی‌سازد.
- وضعیت‌ها در متای افزونهٔ قبلی `new` / `in_progress` / `done` هستند و به `new` / `contacted` / `completed` می‌روند. شکل `in-progress` هم پذیرفته می‌شود.
- نوع فرم `consult` روی سرویس پذیرش، `freight` روی کارگو، `trade` روی تجارت، `contact` روی تماس، و `exchange` روی صرافی می‌نشیند. برای هر نوع به‌جز تماس یک ردیف درخواست ساخته می‌شود.
- افزونهٔ قبلی جدول یادداشت و تاریخچه ندارد. مهاجرت یک ردیف تاریخچه با دلیل `migration` می‌نویسد و متای اضافه را در پیام لید کپی می‌کند.
- کاربر `liferuss_support` نقش مشاور می‌گیرد، مگر اینکه فقط فرم کارگو، تجارت، یا صرافی داشته باشد که در آن صورت اپراتور همان سرویس می‌شود. سرویس‌های مجاز در `lr_allowed_services` ذخیره می‌شوند. نقش قبلی حذف نمی‌شود.
- بعد از فعال بودن هسته، افزونهٔ `liferuss-leads` هوک‌های ذخیرهٔ فرم را برمی‌دارد و اگر هنوز فعال باشد دادهٔ جدید نمی‌نویسد.

Migration is resumable. `wp liferuss migrate-leads` copies `liferuss_lead` posts into `lr_leads` (`legacy_post_id`), maps `new` / `in_progress` / `done` to `new` / `contacted` / `completed`, writes the matching request row, and maps `liferuss_support` users onto a consultant or operator plus `lr_allowed_services`. While core is active, the old plugin stops accepting new submissions.

## تفسیرهای مشخصات / Spec interpretations

- مدیر ارشد کلید `administrator` است، نه یک نقش نهم `lr_*`. ماتریس تأییدشده همین را می‌گوید.
- ماتریس ۲۶ ردیف دارد و همه پیاده شده‌اند.
- جملهٔ «اپراتور فقط رکورد خودش را می‌بیند» برای لیدها دقیق است. برای درخواست سرویس، صف شامل ردیف‌های بدون مسئول هم هست.
- حذف دائمی نوشتهٔ دانشگاه/رشته/شهر فقط با `lr_delete_permanently` (مدیر ارشد) ممکن است و اگر ردیف وابسته باشد متوقف می‌شود (`RESTRICT`).
- `user_id` در `lr_activity_logs` بعد از حذف کاربر باقی می‌ماند. ستون‌های کاربرِ تهی‌پذیر خالی می‌شوند؛ `lead_notes.user_id` و `lead_tasks.assigned_to` به کاربر جایگزین منتقل می‌شوند.
- ستون‌های کش دانشگاه (`min_tuition_usd`، `best_world_rank`) و شمار دانشگاه هر رشته هنگام ذخیرهٔ رشته، شهریه، یا رتبه دوباره حساب می‌شوند.
- تعداد دانشجو، تماس، گالری، پیوند سؤال‌های متداول، و عنوان/توضیح سئو در متای نوشته است؛ جدول ERD ستونی برایشان ندارد. نوع دانشگاه همان `ownership` (دولتی/خصوصی) است.
- آرشیو `/universities/`، `/fields/` و `/cities/` حتی اگر برگه‌ای با همین نام منتشر شده باشد، آرشیو نوع محتوا است. فیلترها و صفحهٔ دوم `noindex,follow` هستند و canonical به آرشیو بدون فیلتر برمی‌گردد.
- دادهٔ نمونه پیش‌نویس است (`lr_demo_catalog`). تا منتشر نشود در سایت دیده نمی‌شود.
- `health_ministry_status` پیش‌فرض `unknown` دارد.
- Rank Math، Polylang، و سازندهٔ بخش خارج از این نسخه است. ریدایرکت فرانت از `lr_redirects` اجرا می‌شود (۳۰۱، ۳۰۲، ۴۱۰)، بازدید را می‌شمارد، زنجیره را تا پنج گام جمع می‌کند، و چرخه را دنبال نمی‌کند. فهرست مدیریت در **لایف‌روس ← ریدایرکت‌ها** است.
- نشانی عمومی خدمات از نمونه‌های نقشهٔ سایت است (حالت حمل و نوع پرداخت)، نه از هر فرزند درخت خدمات ERD. `/cargo/` همان لندینگ باربری است و `/freight/` با ۳۰۱ به آن می‌رود. استعلام نرخ پرداخت و نرخ زنده ندارد. فروشگاه نیست.
- زیرصفحه‌های صرافی، کارگو، و تجارت، و دانستنی نمونه، پیش‌نویس‌اند (`lr_service_seed`). مجله در `/blog/` است و سه نوشتهٔ اولیه نامک انگلیسی گرفته‌اند.
- فرم‌های قالب consult، admission، freight، trade، exchange، immigration، و contact به `POST /wp-json/liferuss/v1/leads` می‌روند و اگر جاوااسکریپت نباشد همان `admin-post.php` قبلی را دارند. مشاوره و پذیرش روی `admission_requests` می‌نشینند (`program_type`: degree، padfak، direct_course، scholarship). مهاجرت روی سرویس `migration` است و ردیف درخواست جدا ندارد چون ERD جدول مهاجرت ندارد. استعلام نرخ فقط مبلغ و ارز را در `exchange_requests` می‌نویسد.
- بورسیه جدول ERD ندارد. نوع `lr_scholarship` به‌همراه متای مهلت، پوشش، شرایط، منبع، و `last_verified_at` است. برگه‌های مسیر تحصیل و مهاجرت قالب `templates/path.php` و متای ساختاری دارند؛ سازندهٔ آزاد بخش هنوز نیست.
- دادهٔ نمونهٔ مسیرها و بورسیه‌ها پیش‌نویس است (`lr_path_seed`). تا منتشر نشود در سایت دیده نمی‌شود. پرسش و نظر نمونه عمومی نیستند و فقط بعد از انتشار صفحهٔ مادر دیده می‌شوند.
- فیلتر تاریخ در فهرست لید میلادی است (`input type="date"`). نمایش تاریخ در جدول و جزئیات شمسی است و tooltip میلادی UTC دارد.
- نوتیفیکیشن ایمیل و تلگرام با `lr_notify_lead` حدود ۱۵ ثانیه بعد از ثبت، از wp-cron ارسال می‌شود. خطای ارسال به بازدیدکننده برنمی‌گردد.
- فایل لید بیرون از `uploads` در `wp-content/liferuss-private` است. پاک‌سازی روزانه فایل‌هایی را حذف می‌کند که `purge_after`شان گذشته و `purged_at` خالی است. بستن لید (`completed` یا `lost`) این تاریخ را ۱۲ ماه بعد می‌گذارد.
