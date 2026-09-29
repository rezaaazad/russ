# لایف‌روس — هسته / LifeRuss Core

نسخهٔ ۱.۰.۰. این افزونه اسکلت پلتفرم است: جداول، نوع‌های محتوا، نقش‌ها، و منوی مدیریت. قالب صفحه و فرم‌های کامل در نسخه‌های بعدی می‌آیند.

Version 1.0.0. This plugin is the platform skeleton: tables, content types, roles, and the admin menu. Front-end templates and full editors come later.

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
| `lr_testimonial` | عمومی نیست، REST و بازبینی دارد |

طبقه‌بندی‌ها: `lr_field_group`، `lr_guide_cat`، `lr_faq_group`، `lr_level`.

دانشگاه، رشته، و شهر یک ردیف سایهٔ ۱:۱ در `lr_universities` / `lr_fields` / `lr_cities` دارند. ذخیره، زباله‌دان، و حذف دائمی نوشته این ردیف را همگام می‌کند. تغییر نامک یک ریدایرکت ۳۰۱ در `lr_redirects` می‌نویسد؛ اجرای ریدایرکت در فرانت در این نسخه نیست. اگر یک برگهٔ منتشرشده از قبل همان مسیر را داشته باشد (مثلاً برگهٔ قالب `/universities/`)، برگه بر بازنویسی نوع محتوا مقدم است.

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

## مهاجرت `liferuss-leads` (انجام نشده)

افزونهٔ فعلی لیدها دست نخورده می‌ماند. در نسخهٔ بعد جدول `lr_leads` آن را جذب می‌کند:

- `legacy_post_id` به شناسهٔ نوشتهٔ CPT `liferuss_lead` وصل می‌شود.
- وضعیت‌ها: `new` → `new`، `in-progress` → `contacted`، `done` → `completed`.
- نوع فرم `consult` / `freight` / `trade` / `contact` به درخت `lr_services` نگاشت می‌شود (`freight` به گروه کارگو).
- نقش `liferuss_support` به مشاور یا اپراتور به‌اضافهٔ `lr_allowed_services` منتقل می‌شود.
- مدیر محدود `modiriat` همان `lr_admin` است.

The existing `liferuss-leads` plugin is unchanged. The next release maps `liferuss_lead` posts onto `lr_leads.legacy_post_id`, the three lead statuses onto `new` / `contacted` / `completed`, form types onto `lr_services`, and `liferuss_support` onto a consultant or operator plus `lr_allowed_services`.

## تفسیرهای مشخصات / Spec interpretations

- مدیر ارشد کلید `administrator` است، نه یک نقش نهم `lr_*`. ماتریس تأییدشده همین را می‌گوید.
- ماتریس ۲۶ ردیف دارد و همه پیاده شده‌اند.
- جملهٔ «اپراتور فقط رکورد خودش را می‌بیند» برای لیدها دقیق است. برای درخواست سرویس، صف شامل ردیف‌های بدون مسئول هم هست.
- حذف دائمی نوشتهٔ دانشگاه/رشته/شهر فقط با `lr_delete_permanently` (مدیر ارشد) ممکن است و اگر ردیف وابسته باشد متوقف می‌شود (`RESTRICT`).
- `user_id` در `lr_activity_logs` بعد از حذف کاربر باقی می‌ماند. ستون‌های کاربرِ تهی‌پذیر خالی می‌شوند؛ `lead_notes.user_id` و `lead_tasks.assigned_to` به کاربر جایگزین منتقل می‌شوند.
- ستون‌های کش دانشگاه (`min_tuition_usd`، وضعیت وزارتخانه‌ها، `best_world_rank`) ذخیره می‌شوند و در این نسخه محاسبه نمی‌شوند.
- `health_ministry_status` پیش‌فرض `unknown` دارد.
- اجرای ریدایرکت فرانت، Rank Math، Polylang، سازندهٔ بخش، و endpoint عمومی لید خارج از این نسخه است.
