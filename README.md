# منصة مدرسة حبة خردل التعليمية
# Mustard Seed School Platform

منصة تعليمية متكاملة لخدمة الكنيسة، تحتوي على نظام تسجيل الدخول، لوحة تحكم للمسؤولين، واجهة تفاعلية للمخدومين (الطلاب)، ونظام رفع الفيديوهات والامتحانات.

## التقنيات المستخدمة (Tech Stack)
- **البنية التحتية (Backend):** PHP 8+ (Vanilla / No Frameworks)
- **قاعدة البيانات (Database):** MySQL (PDO)
- **الواجهات (Frontend):** HTML5, CSS3, JavaScript (ES6+), Bootstrap 5
- **الأمان (Security):** Zero-Trust Architecture, CSRF Protection, Password Hashing, Anti-IDM Video Streaming

## هيكل المشروع (Project Structure)
- `api/`: يحتوي على ملفات الواجهة البرمجية (API Controllers, Routing, Database Connection).
- `assets/`: يحتوي على الصور، التنسيقات (CSS)، وملفات الجافاسكريبت.
- `views/`: واجهات المستخدم (لوحة الإدارة، واجهة الطالب، صفحة الدخول).
- `router.php`: نقطة الدخول للتوجيه (Front Controller) للتشغيل المحلي.
- `index.php`: نقطة التوجيه الرئيسية للإنتاج.

## طريقة التشغيل محلياً (Local Development)

يمكن تشغيل المشروع بسهولة عبر خادم PHP المدمج:
```bash
php -S localhost:8000 router.php
```
ثم قم بزيارة `http://localhost:8000` في المتصفح.

## قاعدة البيانات
المشروع متصل بقاعدة بيانات MySQL. تأكد من تشغيل خادم MySQL (مثل XAMPP) قبل تشغيل المشروع. يتم إنشاء قاعدة البيانات تلقائياً، ولكن يمكنك استيراد الجداول من ملف `api/schema.sql` إذا لزم الأمر. إعدادات الاتصال موجودة في `api/db.php`.

## حقوق النشر
جميع الحقوق محفوظة. تم تطوير هذا المشروع لخدمة احتياجات "حبة خردل".
