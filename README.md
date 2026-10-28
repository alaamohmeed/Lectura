# Lectura — مركز مواد الدورة الدراسية | Course Material Hub

دليل ثنائي اللغة للتثبيت، النشر، والصيانة. يبدأ القسم العربي أولاً، ثم القسم الإنجليزي.

Bilingual installation, deployment, and maintenance guide. Arabic comes first, followed by English.

- [العربية](#العربية)
- [English](#english)

---

## العربية

### 1. نظرة عامة

**Lectura** منصة ويب خاصة بدعوة فقط لمشاركة مواد الدورة الدراسية: المحاضرات، الواجبات، الملخصات، الامتحانات السابقة، والإعلانات. الواجهة عربية RTL افتراضياً، مع واجهة إنجليزية LTR، ووضعين فاتح وداكن مع خيار تلقائي حسب النظام.

المنصة مبنية بـ **PHP 8.1+** و **PDO/MySQL** و **Bootstrap 5.3** و **JavaScript** عادي، دون خطوة بناء (No build step). تُخزَّن الملفات المرفوعة خارج المجلد العام `public/`، ولا تُقدَّم إلا عبر `download.php` بعد التحقق من تسجيل الدخول.

### 2. المزايا الرئيسية

- تسجيل دخول بالبريد وكلمة المرور، وتسجيل بالدعوة فقط.
- أربعة أدوار: مشرف (admin)، محاضر (lecturer)، مساعد (assistant)، طالب (student).
- دعوات لمرة واحدة صالحة 7 أيام، مع اختيار دور الطالب/المحاضر عند إنشاء الدعوة.
- حماية من محاولات الدخول المتكررة: قفل 10 دقائق بعد 5 محاولات فاشلة لكل بريد + IP.
- إدارة الفصول الدراسية، المواد، العناصر، والإعلانات.
- رفع PDF وOffice والصور وZIP، بحد افتراضي 10 MB للملف.
- معاينة PDF والصور داخل المتصفح، وتنزيل بقية الأنواع كمرفق.
- أحدث التحديثات مع شارات «جديد»، إعلانات مثبتة، ومواعيد تسليم مع عدّاد تنازلي.
- تحديث تلقائي للمحتوى في الصفحات المفتوحة (الرئيسية، المادة، العنصر، الأرشيف) دون إعادة تحميل الصفحة.
- مشاركة عبر واتساب بنص جاهز للطاقم المصرّح فقط، دون رابط في رسالة الملف. عند حفظ رابط مجموعة واتساب من **الإدارة → مشاركة المجموعة**، تُنسخ الرسالة تلقائياً ويُفتح رابط المجموعة في تبويب جديد؛ لا يُعرض رابط المجموعة على الطلاب.
- خط عربي ذاتي الاستضافة IBM Plex Sans Arabic (رخصة SIL OFL) وخط Inter للإنجليزية.
- إعدادات المستخدم: اللغة، السمة، إشعارات البريد، وتغيير كلمة المرور.
- تصميم متجاوب من 320px إلى 1440px، وواجهة عربية/إنجليزية كاملة.

### 3. الأدوار والصلاحيات

| الدور | الصلاحيات |
|---|---|
| **مشرف (admin)** | كل شيء: المستخدمون، الدعوات (طالب/محاضر فقط)، الفصول، المواد، العناصر، جميع الإعلانات، إعدادات مشاركة المجموعة، والرفع إلى أي مادة. |
| **محاضر (lecturer)** | رفع وتعديل وحذف وإدارة مواد المواد المسندة إليه فقط، وإدارة/تثبيت/حذف الإعلانات التي أنشأها، وإرسال البريد والمشاركة إلى مجموعة واتساب. لا يملك إدارة المستخدمين أو الدعوات أو الفصول أو المواد أو إعدادات المشاركة. |
| **مساعد (assistant)** | رفع وتعديل وحذف المواد في كل المواد، ومشاركتها عبر واتساب. لا يملك إدارة المستخدمين أو الدعوات أو الفصول أو المواد أو الإعلانات أو إعدادات المشاركة. تعيينات مساعدي المادة تصبح استعلامية فقط ولا تقيد الوصول. |
| **طالب (student)** | عرض وتنزيل المواد، الإعلانات، والمواعيد. لا يرى بطاقة مشاركة واتساب أو رابط المجموعة، ولا يملك أي صلاحيات إدارية. |

كل صفحات المحتوى تتطلب تسجيل الدخول. صفحات `login.php` و`register.php` و`setup.php` فقط عامة، و`setup.php` يتوقف تلقائياً بعد إنشاء أول مشرف. التنزيل يتطلب تسجيل الدخول أيضاً. عرض وإدارة الإعلانات من صفحة واحدة للمشرف والمحاضر فقط؛ إعلانات المحاضر محصورة به.

### 4. المتطلبات

- **PHP 8.1 أو أحدث** (تم الاختبار على PHP 8.2.12).
- إضافات PHP: `pdo_mysql`، `fileinfo`، `mbstring`، `openssl`، `json`، `session`.
- `intl` اختياري: يحسّن تنسيق التواريخ، وللمنصة بديل مترجم عند غيابه.
- **MySQL 5.7+** أو **MariaDB 10.3+** بترميز `utf8mb4`.
- **Apache** مع `mod_headers` (و`mod_rewrite` اختياري لصفحات الأخطاء)، أو إعداد مكافئ على Nginx.
- **Composer 2** لتثبيت PHPMailer.
- صلاحية كتابة لـ `storage/uploads` و`storage/logs`.
- اتصال بالإنترنت لتحميل Bootstrap وBootstrap Icons من CDN؛ الخطوط IBM Plex Sans Arabic وInter ذاتية الاستضافة.
- خادم SMTP اختياري للإشعارات البريدية؛ بدونه يُستخدم `mail()` أو تُسجَّل محاولات فاشلة دون تعطيل الرفع.

### 5. بنية المشروع

```text
Lectura/
├── config/
│   ├── config.example.php     # قالب الإعدادات
│   └── config.php             # إعداداتك المحلية/الإنتاجية (غير مرفوعة إلى Git)
├── database/
│   ├── schema.sql             # إنشاء الجداول (يحذف الجداول الحالية عند الاستيراد)
│   ├── seed.sql               # حساب المشرف فقط (لا محتوى)
│   └── migration_lecturer_group.sql # ترقية قاعدة موجودة بالتغييرات الجديدة
├── lang/
│   ├── ar.php                 # نصوص الواجهة العربية
│   └── en.php                 # نصوص الواجهة الإنجليزية
├── public/                    # المجلد الجذر للموقع (Document Root)
│   ├── index.php              # الرئيسية
│   ├── login.php              # تسجيل الدخول
│   ├── register.php           # التسجيل بالدعوة
│   ├── setup.php              # إنشاء أول مشرف (لمرة واحدة)
│   ├── download.php           # التنزيل المحمي
│   ├── item.php               # تفاصيل عنصر
│   ├── course.php             # صفحة مادة
│   ├── archive.php            # الأرشيف
│   ├── settings.php           # إعدادات المستخدم
│   ├── api/
│   │   └── live.php           # فحص نسخة المحتوى للتحديث الحيّ
│   ├── admin/                 # صفحات الإدارة
│   │   └── sharing.php        # إعداد رابط مجموعة واتساب
│   └── assets/                # CSS، JS، الصور، الخطوط
├── src/
│   ├── bootstrap.php          # التهيئة، الجلسة، الترويسات الأمنية
│   ├── db.php                 # اتصال PDO ودوال قاعدة البيانات
│   ├── live.php               # نسخة المحتوى للتحديث الحيّ
│   ├── helpers.php            # الهروب، التواريخ، المشاركة، الأدوات
│   ├── settings.php           # إعدادات التطبيق في قاعدة البيانات
│   ├── i18n.php               # اللغة والاتجاه
│   ├── auth.php               # الدخول، الصلاحيات، الدعوات، القفل
│   ├── upload.php             # التحقق من الملفات وتخزينها
│   ├── notify.php             # التحديثات، المواعيد، الإشعارات
│   ├── mailer.php             # PHPMailer / mail() والرسائل
│   └── layout/                # الهيدر، الفوتر، الأجزاء المشتركة
├── storage/                   # خارج المجلد العام
│   ├── uploads/               # الملفات المرفوعة (محمية)
│   └── logs/                  # السجلات (محمية)
├── vendor/                    # حزم Composer (تُنشأ بـ composer install)
├── composer.json
├── composer.lock
└── README.md
```

> **مهم:** يجب أن يكون جذر الموقع على الخادم هو مجلد `public/`، وليس مجلد المشروع كاملاً. هذا يمنع الوصول المباشر إلى `config/` و`storage/` و`vendor/`.

### 6. التثبيت المحلي على XAMPP (Windows)

1. شغّل **Apache** و**MySQL** من لوحة XAMPP.
2. ضع المشروع في `C:\xampp\htdocs\Lectura`.
3. أنشئ قاعدة البيانات واستورد المخطط:

```bat
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS lectura CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 lectura < C:\xampp\htdocs\Lectura\database\schema.sql"
```

4. (اختياري) أنشئ حساب المشرف الأولي:

```bat
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 lectura < C:\xampp\htdocs\Lectura\database\seed.sql"
```

> على Windows استخدم `cmd /c` مع إعادة التوجيه `<` كما هو موضح، لأن تمرير ملف SQL عبر PowerShell قد يفسد ترميز UTF-8. تحذير: `schema.sql` يحتوي على `DROP TABLE`، فلا تستورده فوق بيانات حقيقية دون نسخة احتياطية.

5. ثبّت الحزم:

```bat
cd C:\xampp\htdocs\Lectura
composer install
```

6. انسخ ملف الإعدادات وعدّله:

```bat
copy config\config.example.php config\config.php
```

قيم محلية نموذجية:

```php
'app' => [
    'base_url' => 'http://localhost/Lectura/public',
    'name' => 'Lectura',
    'default_lang' => 'ar',
    'timezone' => 'UTC', // أو Africa/Algiers لعرض التوقيت المحلي
    'debug' => true,
    'setup_secret' => 'local-dev-setup-secret',
],
'db' => [
    'host' => 'localhost',
    'name' => 'lectura',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4',
],
'mail' => [
    'use_smtp' => false, // لا يوجد SMTP محلي عادةً
],
'session' => [
    'cookie_secure' => false, // لأن التطوير المحلي على HTTP
],
```

7. تأكد من وجود المجلدين `storage/uploads` و`storage/logs` وقابلية الكتابة فيهما.
8. افتح: `http://localhost/Lectura/public/`
9. إذا استوردت `seed.sql`، سجّل الدخول بحساب المشرف `alaa.m.swidi@gmail.com`. إذا استوردت `schema.sql` فقط، افتح:
   `http://localhost/Lectura/public/setup.php`
   وأدخل `setup_secret` لإنشاء أول مشرف.
10. بعد إنشاء المشرف، احذف `public/setup.php` أو اترك `setup_secret` فارغاً/عشوائياً. الملف يعيد 404 تلقائياً بعد وجود مشرف.

> **ملاحظة محلية:** إذا فتحت المشروع من مجلد فرعي مثل `/Lectura/public`، فقد تعرض أخطاء Apache غير المعروفة صفحة 404 الافتراضية بدلاً من صفحة المنصة المنسقة، لأن مسارات `ErrorDocument` في `public/.htaccess` تبدأ من جذر النطاق. أخطاء التطبيق نفسها (403/404) تظل منسقة. للحصول على السلوك الكامل محلياً، استخدم Virtual Host يكون جذره `...\Lectura\public` واضبط `base_url` على عنوانه.

### 7. الحساب الإداري الأولي

بعد استيراد `database/seed.sql` يصبح لديك مشرف واحد فقط، دون أي فصول أو مواد أو عناصر أو إعلانات أو مستخدمين آخرين:

| الدور | البريد الإلكتروني | كلمة المرور المؤقتة |
|---|---|---|
| مشرف | `alaa.m.swidi@gmail.com` | `Alaa@2026!` |

**مهم:**
- غيّر كلمة المرور المؤقتة فوراً من **الإعدادات → تغيير كلمة المرور**.
- لا توجد بيانات محتوى مسبقة؛ ابدأ بإضافة فصل دراسي، ثم مادة، ثم ارفع العناصر.
- إذا لم تستورد `seed.sql`، استخدم `setup.php` لإنشاء مشرف مختلف.
- `seed.sql` آمن كبداية إنتاجية لأنه لا ينشئ سوى المشرف، لكن استبدل كلمة المرور المؤقتة قبل فتح الموقع للمستخدمين؛ لا تنشر الموقع بهذه الكلمة.
- لا تستورد `schema.sql` فوق قاعدة تحتوي بيانات؛ فهو يحذف الجداول.

### 7.1 ترقية قاعدة بيانات موجودة

لإضافة دور **المحاضر** وربط المحاضرين بالمواد وإعدادات مشاركة المجموعة إلى قاعدة بيانات تحتوي بيانات حقيقية:

1. خذ نسخة احتياطية كاملة أولًا:

```bat
cmd /c "C:\xampp\mysql\bin\mysqldump.exe -u root --default-character-set=utf8mb4 --single-transaction lectura > storage\backups\lectura_before_upgrade.sql"
```

2. شغّل الترقية الإضافية مرة واحدة:

```bat
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 lectura < database\migration_lecturer_group.sql"
```

3. بعد نشر الملفات الجديدة، سجّل الدخول كمشرف واستخدم **الدعوات** لإنشاء حساب محاضر، ثم **المواد → تعديل المادة** لتعيين المحاضرين؛ أخيرًا اضبط رابط المجموعة في **مشاركة المجموعة**.

> الترقية لا تعيد إنشاء الجداول ولا تحذف المستخدمين والمحتوى. هي تغيير إضافي: توسيع `role`، إضافة `invites.role`، إنشاء `course_lecturers` و`app_settings`. لا تشغّلها مرتين.

### 8. النشر على alwaysdata خطوة بخطوة

#### 8.1 إنشاء الموقع وقاعدة البيانات

1. أنشئ حساباً/موقعاً في alwaysdata، وفعّل **SSH** و**Composer** إن لم يكونا مفعّلين.
2. من لوحة alwaysdata: **Databases → MySQL → Add a database**.
3. أنشئ قاعدة باسم مثل `ACCOUNT_lectura` ومستخدماً لها، واحتفظ بالمضيف (`mysql-ACCOUNT.alwaysdata.net`) والاسم والمستخدم وكلمة المرور.
4. أنشئ صندوق بريد إن أردت إشعارات بريدية موثوقة، واحتفظ ببيانات SMTP من قسم البريد.

#### 8.2 رفع الملفات

ارفع المشروع إلى الدليل الخاص بك، مثلاً `~/www/lectura`، بحيث يكون:

```text
~/www/lectura/public/          ← جذر الموقع
~/www/lectura/config/
~/www/lectura/storage/
...
```

مثال عبر `rsync` من جهازك (استبدل `ACCOUNT`):

```bash
rsync -avz \
  --exclude='config/config.php' \
  --exclude='vendor/' \
  --exclude='storage/uploads/*' \
  ./ ACCOUNT@ssh-ACCOUNT.alwaysdata.net:~/www/lectura/
```

أو ارفع عبر SFTP. لا ترفع `config/config.php` المحلي فوق إعدادات الإنتاج.

#### 8.3 تثبيت الحزم

```bash
ssh ACCOUNT@ssh-ACCOUNT.alwaysdata.net
cd ~/www/lectura
composer install --no-dev --optimize-autoloader
```

إذا لم يتوفر Composer في الحساب، ثبّت PHPMailer يدوياً داخل `vendor/` أو استخدم `mail()` كبديل، مع العلم أن التسليم قد يكون أقل موثوقية.

#### 8.4 استيراد قاعدة البيانات

من جلسة SSH داخل مجلد المشروع:

```bash
mysql -h mysql-ACCOUNT.alwaysdata.net -u ACCOUNT_lectura -p ACCOUNT_lectura < database/schema.sql
```

لإنشاء حساب المشرف الأولي فقط (بدون محتوى):

```bash
mysql -h mysql-ACCOUNT.alwaysdata.net -u ACCOUNT_lectura -p ACCOUNT_lectura < database/seed.sql
```

يمكنك أيضاً الاستيراد عبر phpMyAdmin من لوحة alwaysdata. تذكّر أن `schema.sql` يحذف الجداول الموجودة.

> عند ترقية تثبيت موجود، استخدم `database/migration_lecturer_group.sql` بدلًا من `schema.sql` بعد أخذ نسخة احتياطية (راجع القسم 7.1).

#### 8.5 إعداد `config.php`

```bash
cp config/config.example.php config/config.php
nano config/config.php
```

اضبط القيم الأساسية:

```php
'app' => [
    'base_url' => 'https://ACCOUNT.alwaysdata.net', // بدون شرطة مائلة أخيرة
    'name' => 'Lectura',
    'default_lang' => 'ar',
    'timezone' => 'Africa/Algiers', // اختياري؛ قاعدة البيانات تخزن UTC دائماً
    'debug' => false,               // مهم جداً في الإنتاج
    'setup_secret' => 'ضع-هنا-سلسلة-عشوائية-طويلة',
],
'db' => [
    'host' => 'mysql-ACCOUNT.alwaysdata.net',
    'name' => 'ACCOUNT_lectura',
    'user' => 'ACCOUNT_lectura',
    'pass' => 'كلمة-مرور-قاعدة-البيانات',
    'charset' => 'utf8mb4',
],
'mail' => [
    'use_smtp' => true,
    'host' => 'smtp-ACCOUNT.alwaysdata.net', // راجع إعدادات بريد alwaysdata
    'port' => 587,
    'username' => 'noreply@your-domain.tld',
    'password' => 'كلمة-مرور-البريد',
    'encryption' => 'tls',
    'from_email' => 'noreply@your-domain.tld',
    'from_name' => 'Lectura',
],
'session' => [
    'cookie_secure' => true, // مطلوب مع HTTPS
],
```

> إن لم تضبط SMTP، اجعل `use_smtp` مساوياً `false`، أو ستُسجَّل محاولات إرسال فاشلة. فشل البريد لا يمنع رفع المواد.

#### 8.6 الصلاحيات

```bash
chmod 755 storage storage/uploads storage/logs
```

PHP على alwaysdata يعمل تحت حسابك، لذلك يكفي أن تكون المجلدات قابلة للكتابة من المالك. لا تستخدم `777`.

#### 8.7 إعداد الموقع في alwaysdata

من **Sites**:

- اجعل **Root directory** هو `~/www/lectura/public`.
- اختر **PHP 8.1** أو أحدث.
- فعّل **HTTPS** (Let's Encrypt) وفرض التحويل إلى HTTPS.
- ارفع `upload_max_filesize` إلى 12M على الأقل و`post_max_size` إلى 14M على الأقل إذا أردت السماح بحد 10 MB للملف. اضبط ذلك من إعدادات PHP للموقع.
- تأكد من أن Apache يسمح بـ `.htaccess` (Alwaysdata يدعمه عادةً).

#### 8.8 إنشاء أول مشرف

- **بدون `seed.sql`:** افتح `https://ACCOUNT.alwaysdata.net/setup.php`، أدخل `setup_secret` وبيانات المشرف. بعد النجاح احذف الملف:

```bash
rm ~/www/lectura/public/setup.php
```

- **مع `seed.sql`:** الموقع يحتوي مشرفاً واحداً جاهزاً، لذلك `setup.php` يعيد 404. سجّل الدخول فوراً وغيّر كلمة المرور المؤقتة، ثم ابدأ بإضافة الفصول والمواد.

#### 8.9 التحقق بعد النشر

- افتح صفحة الدخول وتأكد أنها عربية RTL افتراضياً.
- سجّل الدخول، وافتح الرئيسية، ثم جرّب رفع ملف PDF وتنزيله.
- جرّب دعوة محاضر ثم سجّل بها، وعيّنه في مادة، وتحقق من ظهور زر الرفع في تلك المادة فقط.
- افتح **مشاركة المجموعة**، وألصق رابط `https://chat.whatsapp.com/...`، ثم جرّب المشاركة كمستخدم طاقم مع بقاء الرابط مخفيًا عن الطلاب.
- جرّب دعوة وتسجيل حساب جديد، ثم أعد استخدام الرابط نفسه للتأكد من رفضه.
- جرّب الوضع الفاتح والداكن والتبديل إلى الإنجليزية.
- تحقق من سجل البريد `storage/logs` أو لوحة alwaysdata بعد رفع عنصر.
- اختبر على هاتف بعرض 320px وعلى سطح المكتب بعرض 1440px.

#### 8.10 النسخ الاحتياطي

احتفظ بنسخ دورية من:

- قاعدة البيانات (mysqldump).
- `storage/uploads/` (الملفات المرفوعة).
- `config/config.php` في مكان آمن خارج المستودع.

### 9. مرجع الإعدادات

| المفتاح | الافتراضي | الوصف |
|---|---|---|
| `app.base_url` | `https://yourname.alwaysdata.net` | عنوان المنصة بدون `/` أخيرة. يُستخدم في الروابط والبريد. |
| `app.name` | `Lectura` | اسم المنصة في الواجهة والبريد. |
| `app.default_lang` | `ar` | اللغة الافتراضية للزائر غير المسجل. |
| `app.timezone` | `UTC` | توقيت عرض التواريخ واستقبال مواعيد التسليم. التخزين في قاعدة البيانات UTC دائماً. |
| `app.debug` | `false` | إظهار تفاصيل الأخطاء. اجعله `false` في الإنتاج. |
| `app.setup_secret` | — | سر إنشاء أول مشرف. احذف `setup.php` بعده. |
| `db.host/name/user/pass` | — | بيانات MySQL. |
| `db.charset` | `utf8mb4` | ترميز الاتصال. |
| `mail.use_smtp` | `false` | `true` لاستخدام PHPMailer/SMTP عند توفر Composer، وإلا `mail()`. |
| `mail.host/port/username/password/encryption` | — | بيانات SMTP. |
| `mail.from_email/from_name` | — | مرسل الإشعارات. |
| `upload.path` | `storage/uploads` | مسار التخزين خارج `public`. |
| `upload.max_size` | `10 * 1024 * 1024` | الحد الأقصى لحجم الملف الواحد. |
| `upload.allowed_types` | PDF/Office/صور/ZIP | قائمة MIME المسموحة. |
| `upload.allowed_extensions` | `pdf, doc, docx, ...` | الامتدادات المسموحة (يُتحقق منها مع MIME). |
| `invite.token_length` | `32` | عدد بايتات العشوائية (ينتج 64 حرف hex). |
| `invite.expiry_days` | `7` | صلاحية الدعوة بالأيام. |
| `auth.max_failed_attempts` | `5` | عدد المحاولات الفاشلة قبل القفل. |
| `auth.lockout_minutes` | `10` | مدة القفل بالدقائق. |
| `storage.quota` | `100 MB` | سعة تقديرية لعداد لوحة التحكم فقط، وليست حصة القرص الفعلية. |
| `storage.warning_percent` | `80` | نسبة تحذير العداد. |
| `session.lifetime` | 30 يوماً | عمر الجلسة. |
| `session.cookie_secure` | `true` | اجعله `false` فقط على HTTP المحلي. |
| `session.cookie_httponly` | `true` | منع وصول JavaScript إلى كوكي الجلسة. |
| `session.cookie_samesite` | `Lax` | حماية إضافية من CSRF. |

### 10. الأمان

- استعلامات PDO مُهيأة مسبقاً (Prepared Statements) في كل التعاملات مع قاعدة البيانات.
- كلمات المرور مُخزنة بـ `password_hash()` ولا تُعرض أبداً.
- رمز CSRF على كل طلب POST يغيّر الحالة، والطلب غير الصالح يعيد 403.
- تهريب المخرجات عبر `e()`/`ea()` لمنع XSS.
- فحص الصلاحيات على الخادم في كل صفحة إدارية، وليس في القوائم فقط.
- الدعوات لمرة واحدة داخل معاملة مع `SELECT ... FOR UPDATE`، ومقارنة السر عبر `hash_equals`.
- قفل الدخول مفعّل لكل بريد + IP.
- التحقق من الملفات بالامتداد وMIME عبر `finfo`، وتخزينها بأسماء عشوائية خارج المجلد العام.
- التنزيل عبر `download.php` مع التحقق من الجلسة؛ معاينة inline للـ PDF والصور فقط، والبقية attachment.
- ترويسات أمنية: `X-Content-Type-Options`، `X-Frame-Options`، `Referrer-Policy`، وCSP يسمح فقط بمصادر CDN اللازمة.
- كوكي الجلسة `HttpOnly` و`SameSite=Lax` و`Secure` في الإنتاج.
- `setup.php` يتوقف تلقائياً بعد وجود مشرف.
- `debug=false` في الإنتاج، والأخطاء لا تُعرض للمستخدم.

### 11. الافتراضات والقرارات

1. **لا يوجد جدول تسجيل في المواد:** كل طالب نشط يمكنه رؤية وتنزيل مواد كل المواد. الصلاحيات عالمية، والمساعدون فقط يُسندون إلى مواد محددة.
2. **المشرف يرفع إلى أي مادة**، وتُعرض مواد الفصل الحالي أولاً؛ المساعد يرى فقط المواد المسندة إليه.
3. **البريد أفضل جهد (best-effort):** يُرسل بشكل متزامن إلى كل طالب نشط لديه `email_notify=1`. فشله يُسجَّل في `notification_log` ولا يمنع الرفع. واتساب هو المسار الأساسي، وتظهر أدوات المشاركة للمشرف والمساعدين فقط؛ ورسالة الملف لا تتضمن رابطاً.
4. **شارة «جديد»** تُبنى على `users.last_seen_at`. إذا كانت `NULL` تُعد عناصر آخر 7 أيام جديدة. يتحدث `last_seen_at` بعد عرض الرئيسية، لذا تبقى الشارات حتى زيارة الرئيسية التالية.
5. **الإعلانات** قابلة للتجاهل على مستوى الجهاز/المتصفح عبر `localStorage`، ولا يوجد تتبع قراءة من الخادم.
6. **اللغة:** تفضيل المستخدم في قاعدة البيانات، ثم كوكي `lectura_lang`، ثم العربية افتراضياً. لا يوجد كشف تلقائي من `Accept-Language`.
7. **السمة:** `localStorage` على الجهاز يتقدم على تفضيل قاعدة البيانات، والافتراضي `auto`. زر الشريط العلوي يحدّث الجهاز وقاعدة البيانات للمستخدم المسجل، وصفحة الإعدادات تحفظ في قاعدة البيانات.
8. **الفصل الحالي:** يُدار بتعيين `is_current` في التطبيق داخل معاملة، وليس بقيد فريد في قاعدة البيانات.
9. **المواعيد النهائية:** تُدخل بتوقيت `app.timezone` وتُحوَّل إلى UTC عند الحفظ؛ تُعرض بتوقيت `app.timezone`، ويحسب JavaScript العدّاد من UTC.
10. **الملفات:** الحد الافتراضي 10 MB للملف، والأسماء الأصلية في قاعدة البيانات، والأسماء الفعلية عشوائية. PDF والصور فقط قابلة للمعاينة inline.
11. **حصة التخزين:** `storage.quota` تقدير لعداد لوحة التحكم فقط؛ الحصة الحقيقية يديرها المستضيف.
12. **البريد لا يُعاد إرساله:** لا توجد طابور مهام أو إعادة محاولة تلقائية. راجع `notification_log` والسجلات.
13. **لا توجد إعادة تعيين كلمة مرور بالبريد:** المشرف يعيد تعيين كلمات المرور، والدعوات تدير الانضمام.
14. **`seed.sql` ينشئ حساب مشرف واحداً فقط** دون أي محتوى أو مستخدمين آخرين، وهو بداية إنتاجية صالحة بعد تغيير كلمة المرور المؤقتة. البديل: `schema.sql` ثم `setup.php`.
15. **العناصر مرتبطة دائماً بفصل ومادة.** حذف مادة يحذف عناصرها وملفاتها الفعلية. لا يمكن حذف الفصل الحالي أو فصل لا يزال يحتوي مواد.
16. **موعد التسليم يُستخدم للواجبات فقط**؛ الأنواع الأخرى تُحفظ دون موعد حتى لو أُدخل.
17. **الروابط المباشرة لـ `config/` و`storage/` ممنوعة**، ويفترض أن جذر الموقع هو `public/`.
18. **لا خطوة بناء:** Bootstrap وIcons والخطوط من CDN، لذا يلزم اتصال بالإنترنت. النشر المغلق يحتاج استضافة الأصول محلياً وتعديل CSP.
19. **السجلات** في `storage/logs`، ويُفضّل تدويرها دورياً.
20. **`ErrorDocument`** في `public/.htaccess` يفترض أن الموقع على جذر النطاق. هذا صحيح على alwaysdata عند جعل الجذر `public/`، لكنه قد لا يعمل في مجلد فرعي محلي.
21. **النسخ الاحتياطي** مسؤولية المشغّل؛ لا توجد نسخ تلقائية في التطبيق.
22. **لا يوجد «تذكرني»** منفصل؛ عمر الجلسة الافتراضي 30 يوماً.
23. **الترجمة** ملفات PHP ثابتة في `lang/`، ويمكن إضافة لغة ثالثة بتوسيع `i18n_languages()` والملفات والـ CSP عند الحاجة.
24. **حد الرفع الفعلي** يتأثر أيضاً بـ `upload_max_filesize` و`post_max_size` في PHP. تجاوز `post_max_size` يعيد رسالة محلية واضحة بدل خطأ CSRF.
25. **التحديث الحيّ** يستقصي `public/api/live.php` كل 5 ثوانٍ (20 ثانية عند إخفاء التبويب) ويقارن رقم `content_version`. تُحدَّث صفحات الرئيسية والمادة والعنصر والأرشيف في مكانها دون إعادة تحميل؛ صفحات الإدارة لا تُحدَّث تلقائياً. لا يوجد WebSocket/SSE.

### 12. القيود المعروفة

- لا توجد اختبارات آلية شاملة؛ الاعتماد على قائمة التحقق اليدوية.
- لا يوجد فحص فيروسات للملفات، فقط قائمة أنواع MIME/امتدادات.
- لا يوجد رفع مجزأ (chunked/resumable) للملفات الكبيرة.
- التحديث الحيّ يعتمد على استقصاء كل 5 ثوانٍ، وليس دفعاً فورياً عبر WebSocket/SSE؛ صفحات الإدارة تتطلب تحديثاً يدوياً.
- لا توجد خصوصية لكل مادة أو تسجيل طلاب في مواد محددة.
- لا توجد إعادة محاولة تلقائية للبريد الفاشل.
- البحث والتصفية يتمان في المتصفح على القائمة المحمّلة.
- قوائم الإدارة تعرض كل السجلات دون ترقيم صفحات؛ مناسبة لفصل/مجموعة صغيرة.
- عداد التخزين تقديري ولا يمثل حصة القرص الفعلية.
- الاعتماد على CDN قد يمنع الأيقونات/الخطوط في شبكة بلا إنترنت.
- لا يوجد سجل تدقيق كامل؛ يوجد فقط سجل إشعارات البريد.
- صفحات Apache الافتراضية للأخطاء غير المعروفة قد تظهر في مجلد فرعي محلي (راجع القسم 6).

### 13. استكشاف الأخطاء

| المشكلة | السبب المحتمل | الحل |
|---|---|---|
| صفحة 500 بعد التثبيت | إعداد خاطئ أو إضافة PHP مفقودة | اجعل `debug=true` محلياً، راجع سجل Apache و`storage/logs`، وتأكد من `pdo_mysql` و`fileinfo`. |
| الدخول يعيدك لصفحة الدخول | `base_url` أو `cookie_secure` غير متوافق مع HTTP/HTTPS | اضبط `base_url` بدقة، واجعل `cookie_secure=false` محلياً و`true` على HTTPS. |
| فشل رفع الملف | تجاوز حدود PHP أو صلاحيات `storage` | ارفع `upload_max_filesize`/`post_max_size`، وامنح PHP الكتابة على `storage/uploads`. |
| الملف مرفوض رغم أن نوعه مسموح | MIME الفعلي لا يطابق القائمة | راجع `upload.allowed_types` و`allowed_extensions`، وتأكد من محتوى الملف الحقيقي. |
| البريد لا يصل | SMTP غير مضبوط أو `mail()` معطّل | اضبط SMTP، أو استخدم واتساب؛ راجع `notification_log` والسجلات. الرفع لا يتأثر. |
| الأيقونات أو الخطوط لا تظهر | CSP أو CDN محجوب | اسمح بـ `cdn.jsdelivr.net` و`fonts.googleapis.com` و`fonts.gstatic.com`. |
| `setup.php` يعيد 404 | يوجد مشرف بالفعل أو السر فارغ | هذا سلوك مقصود. استخدم حساب المشرف الحالي. |
| الدعوة غير صالحة | منتهية أو مستخدمة | أنشئ دعوة جديدة من لوحة الإدارة. |
| الحساب مقفل | 5 محاولات فاشلة خلال 10 دقائق | انتظر انتهاء المدة، أو احذف سجلات `login_attempts` لهذا البريد/IP. |
| صفحة 404 غير منسقة محلياً | مسارات `ErrorDocument` من جذر النطاق | استخدم Virtual Host جذره `public/` أو عدّل المسارات في `.htaccess` مؤقتاً. |
| تعديلات CSS/JS لا تظهر | ذاكرة متصفح قديمة | حدّث بقوة (Ctrl+F5)؛ الأصول تُصدَّر مع `?v=filemtime`. |
| التحديث الحيّ لا يظهر | جدول `content_version` مفقود أو JavaScript محجوب | استورد `schema.sql` أو أنشئ الجدول، وتأكد من تحميل `live.js` وعدم حجب الطلبات. |
| حروف عربية تالفة بعد استيراد SQL | ترميز استيراد خاطئ | استورد بـ `--default-character-set=utf8mb4` واستخدم `cmd /c` على Windows. |

### 14. قائمة التحقق النهائية

- [x] تسجيل الدخول والخروج، والتحقق من الصلاحيات على الخادم.
- [x] دعوة جديدة، تسجيل حساب، ورفض إعادة استخدام الدعوة.
- [x] قفل الدخول بعد المحاولات الفاشلة.
- [x] إدارة الفصول، المواد، العناصر، الإعلانات، المستخدمين، والدعوات.
- [x] رفع PDF وملفات مسموحة، ورفض `.exe`، والتحقق من حد 10 MB والطلبات الأكبر.
- [x] تنزيل محمي، منع الوصول المباشر إلى `storage/`، ومعاينة PDF inline.
- [x] مشاركة واتساب للمشرف والمساعدين فقط، بنص عربي/إنجليزي دون رابط في رسالة الملف، وتسجيل فشل البريد دون تعطيل الرفع.
- [x] «أحدث التحديثات»، شارات «جديد»، الإعلانات المثبتة، والمواعيد والعدّاد.
- [x] تحديث حيّ للمحتوى في الصفحات المفتوحة دون إعادة تحميل، مع الحفاظ على البحث والتصفية.
- [x] التبديل بين العربية/الإنجليزية وRTL/LTR، والسمات الفاتحة/الداكنة/التلقائية.
- [x] تغيير كلمة المرور، إعدادات الإشعارات، واللغة، والسمة.
- [x] اختبارات استجابة 320/375/768/1024/1440 دون تجاوز أفقي.
- [x] ترويسات الأمان، CSRF، تهريب المخرجات، وتشفير كلمات المرور.
- [x] `php -l` لكل ملفات PHP و`node --check` لكل ملفات JS وتطابق مفاتيح الترجمة.
- [x] تثبيت Composer وPHPMailer، واختبار تحويل التوقيت من UTC إلى توقيت العرض.

> أُجري التحقق الأساسي محلياً على PHP 8.2.12 وMariaDB 10.4.32. يُنصح بإعادة تنفيذ القائمة بعد أي تعديل على الإعدادات أو الإضافات.

---

## English

### 1. Overview

**Lectura** is an invite-only course-material hub for lectures, assignments, summaries, past exams, and announcements. Arabic RTL is the default interface, with a full English LTR interface and light/dark/auto themes.

It is built with **PHP 8.1+**, **PDO/MySQL**, **Bootstrap 5.3**, and vanilla JavaScript, with **no build step**. Uploaded files live outside `public/` and are served only through `download.php` after authentication and authorization checks.

### 2. Key Features

- Email/password login and invite-only registration.
- Three roles: admin, assistant, and student.
- Single-use invite links valid for 7 days.
- Login lockout: 10 minutes after 5 failed attempts per email + IP.
- Admin management of terms, courses, items, announcements, users, and invites.
- PDF, Office, image, and ZIP uploads; default 10 MB per file.
- Inline PDF/image preview; other file types are downloaded as attachments.
- Latest-updates feed with New badges, pinned announcements, and deadline countdowns.
- Automatic in-place content updates on open Home, course, item, and archive pages without reloading.
- WhatsApp-first prefilled sharing for admins and assistants only (the share card is hidden from students), with item messages containing no link, plus best-effort email notifications that never block uploads.
- User settings for language, theme, email notifications, and password.
- Responsive from 320px through 1440px, with complete Arabic/English UI.

### 3. Roles and Permissions

| Role | Permissions |
|---|---|
| **Admin** | Everything: users, invites, terms, courses, items, announcements, and uploads to any course. |
| **Assistant** | Upload, edit, and delete materials only in assigned courses, and share them via WhatsApp. No user, invite, term, course, or announcement management. |
| **Student** | View and download materials, announcements, and deadlines. No WhatsApp share card and no admin access. |

All content pages require login. Only `login.php`, `register.php`, and `setup.php` are public. `setup.php` disables itself after the first admin exists. Downloads also require login.

### 4. Requirements

- **PHP 8.1+** (tested on PHP 8.2.12).
- PHP extensions: `pdo_mysql`, `fileinfo`, `mbstring`, `openssl`, `json`, `session`.
- `intl` is optional; translated fallback date formatting is included.
- **MySQL 5.7+** or **MariaDB 10.3+** with `utf8mb4`.
- **Apache** with `mod_headers` (`mod_rewrite` optional for error pages), or equivalent Nginx configuration.
- **Composer 2** for PHPMailer.
- Write access to `storage/uploads` and `storage/logs`.
- Internet access for Bootstrap, Bootstrap Icons, and Google Fonts from CDNs.
- Optional SMTP server; otherwise PHP `mail()` is used or failures are logged without blocking uploads.

### 5. Project Structure

```text
Lectura/
├── config/
│   ├── config.example.php     # Configuration template
│   └── config.php             # Your local/production config (never committed)
├── database/
│   ├── schema.sql             # Schema creation (drops existing tables on import)
│   └── seed.sql               # Admin account only (no content)
├── lang/
│   ├── ar.php                 # Arabic UI strings
│   └── en.php                 # English UI strings
├── public/                    # Web document root
│   ├── index.php              # Home
│   ├── login.php
│   ├── register.php           # Invite registration
│   ├── setup.php              # One-time first-admin setup
│   ├── download.php           # Protected downloads
│   ├── item.php               # Item detail
│   ├── course.php             # Course page
│   ├── archive.php            # Archive
│   ├── settings.php           # User settings
│   ├── api/
│   │   └── live.php           # Lightweight live-content version check
│   ├── admin/                 # Admin pages
│   └── assets/                # CSS, JS, images
├── src/
│   ├── bootstrap.php          # Bootstrap, session, security headers
│   ├── db.php                 # PDO connection and DB helpers
│   ├── live.php               # Live content versioning
│   ├── helpers.php            # Escaping, dates, sharing, utilities
│   ├── i18n.php               # Language and direction
│   ├── auth.php               # Authentication, roles, invites, lockout
│   ├── upload.php             # File validation and storage
│   ├── notify.php             # Feed, deadlines, notifications
│   ├── mailer.php             # PHPMailer / mail() and email templates
│   └── layout/                # Header, footer, shared partials
├── storage/                   # Outside the public root
│   ├── uploads/               # Uploaded files (private)
│   └── logs/                  # Logs (private)
├── vendor/                    # Composer packages (created by composer install)
├── composer.json
├── composer.lock
└── README.md
```

> **Important:** the web server document root must be the `public/` directory, not the project root. This blocks direct access to `config/`, `storage/`, and `vendor/`.

### 6. Local Setup on XAMPP (Windows)

1. Start **Apache** and **MySQL** from the XAMPP control panel.
2. Place the project in `C:\xampp\htdocs\Lectura`.
3. Create and import the database:

```bat
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS lectura CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 lectura < C:\xampp\htdocs\Lectura\database\schema.sql"
```

4. (Optional) create the initial admin account:

```bat
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 lectura < C:\xampp\htdocs\Lectura\database\seed.sql"
```

> On Windows, use `cmd /c` with `<` as shown; piping SQL through PowerShell can corrupt UTF-8. Warning: `schema.sql` contains `DROP TABLE` statements, so do not import it over real data without a backup.

5. Install dependencies:

```bat
cd C:\xampp\htdocs\Lectura
composer install
```

6. Copy and edit the configuration:

```bat
copy config\config.example.php config\config.php
```

Typical local values:

```php
'app' => [
    'base_url' => 'http://localhost/Lectura/public',
    'name' => 'Lectura',
    'default_lang' => 'ar',
    'timezone' => 'UTC', // or Africa/Algiers for local-time display
    'debug' => true,
    'setup_secret' => 'local-dev-setup-secret',
],
'db' => [
    'host' => 'localhost',
    'name' => 'lectura',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4',
],
'mail' => [
    'use_smtp' => false, // no local SMTP by default
],
'session' => [
    'cookie_secure' => false, // local development is HTTP
],
```

7. Ensure `storage/uploads` and `storage/logs` exist and are writable.
8. Open `http://localhost/Lectura/public/`.
9. If you imported `seed.sql`, log in with `alaa.m.swidi@gmail.com`. If you imported only `schema.sql`, open `http://localhost/Lectura/public/setup.php`, enter the setup secret, and create the first admin.
10. After setup, delete `public/setup.php` or leave a random `setup_secret`. The script returns 404 once an admin exists.

> **Local note:** when served from a subdirectory such as `/Lectura/public`, Apache's default 404 page may handle unknown URLs instead of the app's styled page, because `ErrorDocument` paths in `public/.htaccess` are domain-root-relative. App-generated 403/404 responses remain styled. For full behavior, use a Virtual Host whose root is `...\Lectura\public` and set `base_url` accordingly.

### 7. Initial Admin Account

After importing `database/seed.sql`, the site contains exactly one admin and no courses or other content:

| Role | Email | Temporary password |
|---|---|---|
| Admin | `alaa.m.swidi@gmail.com` | `Alaa@2026!` |

**Important:**
- Change the temporary password immediately in **Settings → Change password**.
- There are no terms, courses, items, announcements, invites, or student accounts. Start by creating a term, then a course, then upload items.
- If you skip `seed.sql`, use `setup.php` to create a different admin.
- `seed.sql` is safe as an initial production baseline because it creates only the admin, but replace the temporary password before opening the site to users; never deploy with this password.
- Do not import `schema.sql` over an existing database; it drops the tables.

### 8. alwaysdata Deployment

#### 8.1 Create the site and database

1. Create an alwaysdata account/site and enable **SSH** and **Composer** if needed.
2. In alwaysdata, open **Databases → MySQL → Add a database**.
3. Create a database such as `ACCOUNT_lectura` and its user. Note the host (`mysql-ACCOUNT.alwaysdata.net`), database name, username, and password.
4. If you want reliable email, create a mailbox and note its SMTP settings from the Mail section.

#### 8.2 Upload the project

Upload the project to a directory such as `~/www/lectura`, so that:

```text
~/www/lectura/public/          ← site root
~/www/lectura/config/
~/www/lectura/storage/
...
```

Example `rsync` from your machine (replace `ACCOUNT`):

```bash
rsync -avz \
  --exclude='config/config.php' \
  --exclude='vendor/' \
  --exclude='storage/uploads/*' \
  ./ ACCOUNT@ssh-ACCOUNT.alwaysdata.net:~/www/lectura/
```

Or use SFTP. Never overwrite the production `config/config.php` with your local copy.

#### 8.3 Install dependencies

```bash
ssh ACCOUNT@ssh-ACCOUNT.alwaysdata.net
cd ~/www/lectura
composer install --no-dev --optimize-autoloader
```

If Composer is unavailable, install PHPMailer manually under `vendor/` or use the `mail()` fallback, accepting lower delivery reliability.

#### 8.4 Import the database

From an SSH session in the project directory:

```bash
mysql -h mysql-ACCOUNT.alwaysdata.net -u ACCOUNT_lectura -p ACCOUNT_lectura < database/schema.sql
```

Initial admin only (no content):

```bash
mysql -h mysql-ACCOUNT.alwaysdata.net -u ACCOUNT_lectura -p ACCOUNT_lectura < database/seed.sql
```

You can also import through phpMyAdmin in alwaysdata. Remember that `schema.sql` drops existing tables.

#### 8.5 Configure `config.php`

```bash
cp config/config.example.php config/config.php
nano config/config.php
```

Core production values:

```php
'app' => [
    'base_url' => 'https://ACCOUNT.alwaysdata.net', // no trailing slash
    'name' => 'Lectura',
    'default_lang' => 'ar',
    'timezone' => 'Africa/Algiers', // optional; the database always stores UTC
    'debug' => false,               // essential in production
    'setup_secret' => 'use-a-long-random-string-here',
],
'db' => [
    'host' => 'mysql-ACCOUNT.alwaysdata.net',
    'name' => 'ACCOUNT_lectura',
    'user' => 'ACCOUNT_lectura',
    'pass' => 'database-password',
    'charset' => 'utf8mb4',
],
'mail' => [
    'use_smtp' => true,
    'host' => 'smtp-ACCOUNT.alwaysdata.net', // verify in alwaysdata Mail settings
    'port' => 587,
    'username' => 'noreply@your-domain.tld',
    'password' => 'mailbox-password',
    'encryption' => 'tls',
    'from_email' => 'noreply@your-domain.tld',
    'from_name' => 'Lectura',
],
'session' => [
    'cookie_secure' => true, // required with HTTPS
],
```

> If SMTP is not configured, set `use_smtp` to `false`; otherwise attempts will fail and be logged. Email failure never blocks an upload.

#### 8.6 Permissions

```bash
chmod 755 storage storage/uploads storage/logs
```

alwaysdata runs PHP under your account, so owner-write access is sufficient. Do not use `777`.

#### 8.7 Site settings

In **Sites**:

- Set **Root directory** to `~/www/lectura/public`.
- Select **PHP 8.1** or newer.
- Enable **HTTPS** (Let's Encrypt) and force HTTPS.
- Raise `upload_max_filesize` to at least 12M and `post_max_size` to at least 14M if you want the app's 10 MB per-file limit to be usable. Adjust these in the site's PHP settings.
- Ensure Apache allows `.htaccess` (alwaysdata normally supports it).

#### 8.8 Create the first admin

- **Without `seed.sql`:** open `https://ACCOUNT.alwaysdata.net/setup.php`, enter the setup secret and admin details, then delete the script:

```bash
rm ~/www/lectura/public/setup.php
```

- **With `seed.sql`:** one admin already exists, so `setup.php` returns 404. Sign in immediately and change the temporary password, then start adding terms and courses.

#### 8.9 Post-deployment checks

- Open the login page and confirm it defaults to Arabic RTL.
- Sign in, open Home, upload a PDF, and download it.
- Create an invite, register a user, then try the same link again and confirm it is rejected.
- Toggle light/dark themes and switch to English.
- Check `storage/logs` or the alwaysdata mail panel after uploading an item.
- Test at 320px on a phone and 1440px on desktop.

#### 8.10 Backups

Keep regular backups of:

- the database (mysqldump),
- `storage/uploads/`,
- `config/config.php`, stored securely outside the repository.

### 9. Configuration Reference

| Key | Default | Description |
|---|---|---|
| `app.base_url` | `https://yourname.alwaysdata.net` | Public URL without a trailing slash. Used in links and emails. |
| `app.name` | `Lectura` | Brand name in the UI and emails. |
| `app.default_lang` | `ar` | Default language for guests. |
| `app.timezone` | `UTC` | Timezone for date display and due-date input. Database storage is always UTC. |
| `app.debug` | `false` | Show error details. Keep `false` in production. |
| `app.setup_secret` | — | Secret for first-admin setup. Delete `setup.php` afterward. |
| `db.host/name/user/pass` | — | MySQL credentials. |
| `db.charset` | `utf8mb4` | Connection charset. |
| `mail.use_smtp` | `false` | `true` uses PHPMailer/SMTP when Composer is available; otherwise `mail()`. |
| `mail.host/port/username/password/encryption` | — | SMTP settings. |
| `mail.from_email/from_name` | — | Notification sender. |
| `upload.path` | `storage/uploads` | Storage path outside `public`. |
| `upload.max_size` | `10 * 1024 * 1024` | Maximum size per file. |
| `upload.allowed_types` | PDF/Office/images/ZIP | Allowed MIME types. |
| `upload.allowed_extensions` | `pdf, doc, docx, ...` | Allowed extensions, checked alongside MIME. |
| `invite.token_length` | `32` | Random bytes (produces 64 hex characters). |
| `invite.expiry_days` | `7` | Invite validity in days. |
| `auth.max_failed_attempts` | `5` | Failures before lockout. |
| `auth.lockout_minutes` | `10` | Lockout duration in minutes. |
| `storage.quota` | `100 MB` | Estimate for the dashboard meter only, not a real disk quota. |
| `storage.warning_percent` | `80` | Meter warning threshold. |
| `session.lifetime` | 30 days | Session lifetime. |
| `session.cookie_secure` | `true` | Set `false` only for local HTTP. |
| `session.cookie_httponly` | `true` | Blocks JavaScript access to the session cookie. |
| `session.cookie_samesite` | `Lax` | Additional CSRF protection. |

### 10. Security

- Prepared PDO statements for all database access.
- Passwords stored with `password_hash()` and never displayed.
- CSRF token on every state-changing POST; invalid tokens return 403.
- Output escaping via `e()`/`ea()` to prevent XSS.
- Server-side role checks on every admin page, not just hidden menu items.
- Single-use invites consumed inside a transaction with `SELECT ... FOR UPDATE`; secret comparison uses `hash_equals`.
- Login lockout per email + IP.
- Upload validation by extension and `finfo` MIME; random storage names outside the web root.
- Downloads require authentication and authorization. PDF/images may be inline; everything else is an attachment.
- Security headers include `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, and a CSP limited to required CDNs.
- Session cookie is `HttpOnly`, `SameSite=Lax`, and `Secure` in production.
- `setup.php` disables itself after an admin exists.
- `debug=false` in production; errors are not exposed to users.

### 11. Assumptions and Decisions

1. **No course enrollment table:** every active student can view and download all course materials. Roles are global; assistants are assigned to specific courses.
2. **Admins may upload to any course**, with current-term courses shown first; assistants only see assigned courses.
3. **Email is best-effort:** messages are sent synchronously to every active student with `email_notify=1`. Failures are recorded in `notification_log` and never block an upload. WhatsApp is the primary sharing path. Share controls are visible to admins and assistants only, and item messages contain no link.
4. **New badges** are based on `users.last_seen_at`. A `NULL` value treats items from the last 7 days as new. `last_seen_at` updates after Home renders, so badges persist until the next Home visit.
5. **Announcements** are dismissible per browser/device via `localStorage`; there is no server-side read tracking.
6. **Language precedence:** user database preference, then the `lectura_lang` cookie, then Arabic. Browser `Accept-Language` auto-detection is not used.
7. **Theme precedence:** device `localStorage` overrides the database preference; the default is `auto`. The topbar toggle updates both the device and the database for signed-in users; Settings saves to the database.
8. **Current term** is managed by setting `is_current` application-side inside a transaction, not by a database unique constraint.
9. **Deadlines** are entered in `app.timezone`, converted to UTC on save, displayed in `app.timezone`, and counted down from UTC by JavaScript.
10. **Files:** default 10 MB per file; original names are kept in the database and physical names are random. Only PDF/images preview inline.
11. **Storage quota:** `storage.quota` is an estimate for the dashboard meter only; the host manages the real disk quota.
12. **No email retry queue:** inspect `notification_log` and logs; failed emails are not retried automatically.
13. **No email password reset:** admins reset passwords, and invites handle onboarding.
14. **`seed.sql` creates a single admin** with no content or other users. It is a valid production baseline after changing the temporary password. Alternative: `schema.sql` then `setup.php`.
15. **Items always belong to a term and course.** Deleting a course removes its items and physical files. The current term, or a term that still has courses, cannot be deleted.
16. **Due dates apply to assignments only**; other item types are saved without a deadline even if one is entered.
17. **Direct URLs to `config/` and `storage/` are denied**, assuming the document root is `public/`.
18. **No build step:** Bootstrap, icons, and fonts come from CDNs, so internet access is required. Fully offline deployment needs local assets and a CSP update.
19. **Logs** live in `storage/logs`; rotate them periodically.
20. **`ErrorDocument`** in `public/.htaccess` assumes domain-root deployment. This is correct on alwaysdata with a `public/` root, but may not work in a local subdirectory.
21. **Backups are the operator's responsibility;** the app has no automatic backup.
22. **No separate "remember me"**; the default session lifetime is 30 days.
23. **Translations** are static PHP files in `lang/`; adding a third language requires extending `i18n_languages()`, adding files, and updating CSP assets if needed.
24. **Real upload limits** are also constrained by PHP's `upload_max_filesize` and `post_max_size`. Requests exceeding `post_max_size` return a localized error instead of a CSRF error.
25. **Live updates** poll `public/api/live.php` every 5 seconds (20 seconds while the tab is hidden) and compare the `content_version` number. Home, course, item, and archive pages update in place without reloading; admin pages are not auto-refreshed. There is no WebSocket/SSE push.

### 12. Known Limitations

- No comprehensive automated test suite; verification is manual.
- No virus scanning; only MIME/extension allowlists.
- No chunked or resumable uploads for large files.
- Live updates poll every 5 seconds instead of using WebSocket/SSE push; admin pages still require a manual refresh.
- No per-course privacy or student enrollment.
- No automatic retry for failed emails.
- Search/filtering is client-side over the loaded list.
- Admin lists have no pagination; suitable for a single class/small cohort.
- The storage meter is approximate, not a real disk quota.
- CDN dependency can break icons/fonts on offline networks.
- No full audit log; only the email notification log exists.
- Unknown-URL Apache error pages may be unstyled in a local subdirectory (see Section 6).

### 13. Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| HTTP 500 after installation | Bad configuration or missing PHP extension | Set `debug=true` locally, check Apache logs and `storage/logs`, verify `pdo_mysql` and `fileinfo`. |
| Login redirects back to login | `base_url` or `cookie_secure` mismatch | Set the exact URL; use `cookie_secure=false` locally and `true` on HTTPS. |
| Upload fails | PHP limits or `storage` permissions | Raise `upload_max_filesize`/`post_max_size` and grant PHP write access to `storage/uploads`. |
| File rejected although its type is allowed | Real MIME does not match the allowlist | Check `upload.allowed_types` and `allowed_extensions`, and inspect the file's actual content type. |
| Email not delivered | SMTP not configured or `mail()` disabled | Configure SMTP or use WhatsApp; check `notification_log` and logs. Uploads still succeed. |
| Icons/fonts missing | CSP or blocked CDN | Allow `cdn.jsdelivr.net`, `fonts.googleapis.com`, and `fonts.gstatic.com`. |
| `setup.php` returns 404 | An admin already exists or the secret is empty | Expected behavior; use the existing admin account. |
| Invite invalid | Expired or already used | Create a new invite from the admin panel. |
| Account locked | 5 failed attempts within 10 minutes | Wait for the lockout, or remove `login_attempts` rows for that email/IP. |
| Unstyled 404 locally | Domain-root `ErrorDocument` paths | Use a Virtual Host rooted at `public/`, or temporarily adjust the paths in `.htaccess`. |
| CSS/JS changes do not appear | Browser cache | Hard refresh (Ctrl+F5); assets are versioned with `?v=filemtime`. |
| Live updates do not appear | Missing `content_version` table or blocked JavaScript | Import `schema.sql` or create the table, and verify `live.js` loads and requests are not blocked. |
| Arabic text corrupted after SQL import | Wrong import encoding | Import with `--default-character-set=utf8mb4`; on Windows use `cmd /c`. |

### 14. Acceptance Checklist

- [x] Login/logout and server-side role enforcement.
- [x] Invite creation, registration, and rejection of reused invites.
- [x] Login lockout after repeated failures.
- [x] Term, course, item, announcement, user, and invite management.
- [x] PDF/allowed uploads, `.exe` rejection, 10 MB limit, and oversized-request handling.
- [x] Protected downloads, blocked direct `storage/` access, and inline PDF preview.
- [x] Admin/assistant-only WhatsApp sharing, Arabic/English text with no link in item messages, and email failure logging without blocking uploads.
- [x] Latest updates, New badges, pinned announcements, deadlines, and countdowns.
- [x] Live in-place content updates on open pages without reloading, preserving search and filters.
- [x] Arabic/English and RTL/LTR switching, plus light/dark/auto themes.
- [x] Password change, notification settings, language, and theme settings.
- [x] Responsive checks at 320/375/768/1024/1440 with no horizontal overflow.
- [x] Security headers, CSRF, output escaping, and password hashing.
- [x] `php -l` on all PHP files, `node --check` on all JS files, and translation-key parity.
- [x] Composer/PHPMailer installation and UTC-to-display-timezone conversion test.

> Core verification was performed locally on PHP 8.2.12 and MariaDB 10.4.32. Re-run this checklist after configuration or dependency changes.
"# lectura" 
