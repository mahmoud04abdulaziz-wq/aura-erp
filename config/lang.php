<?php
/**
 * AURA ERP — Language / i18n System
 * Session-based language switch: 'en' (default) or 'ar'.
 * The redirect logic is in app.php (before output).
 * This file provides $currentLang, $isRTL, and the t() helper.
 */

$currentLang = $_SESSION['lang'] ?? 'en';
$isRTL = ($currentLang === 'ar');

// Translation dictionary
$translations = [
    'en' => [
        // Storefront Nav
        'home' => 'Home',
        'shop' => 'Shop',
        'our_process' => 'Our Process',
        'careers' => 'Careers',
        'cart' => 'Cart',
        'login' => 'Login',
        'log_in' => 'Log In',
        'logout' => 'Logout',
        'user_profile' => 'User Profile',
        'change_password' => 'Change Password',
        'customer_account' => 'Customer Account',
        
        // Hero
        'hero_arabic_name' => 'مسك للحجر الصناعي والديكور',
        'hero_title' => 'Premium Artificial Stone<br>& Decorative Solutions',
        'hero_desc' => 'Engineered in Jordan. Built to last. MiskStone manufactures high-quality artificial marble, granite, and decorative panels using advanced vibro-compression and curing technologies for construction, interior design, and commercial projects across the Middle East.',
        'explore_catalog' => 'Explore Catalog',
        'learn_more' => 'Learn More',
        
        // About
        'about_miskstone' => 'About MiskStone',
        'advanced_manufacturing' => 'Advanced Manufacturing',
        'advanced_manufacturing_desc' => 'Our factory employs vibro-compression technology and controlled curing environments to produce artificial stone that rivals natural marble and granite in durability and aesthetics, at a fraction of the cost.',
        'engineered_formulas' => 'Engineered Formulas',
        'engineered_formulas_desc' => 'Each product line uses a proprietary mix of white/grey cement, quartz aggregates, marble chips, iron oxide pigments, and polyester resin — precisely calibrated for color consistency, structural integrity, and weather resistance.',
        'regional_leader' => 'Regional Leader',
        'regional_leader_desc' => 'Based in Jordan, MiskStone serves clients across the Middle East — from residential villas in Amman to large-scale commercial developments in the Gulf. We combine local craftsmanship with international quality standards.',
        
        // Manufacturing Process
        'our_manufacturing_process' => 'Our Manufacturing Process',
        'raw_material_mixing' => 'Raw Material Mixing',
        'raw_material_desc' => 'Cement, aggregates, and pigments are measured to recipe specifications and mixed uniformly.',
        'vibro_compression' => 'Vibro-Compression',
        'vibro_desc' => 'The mixture is poured into molds and subjected to vibration + hydraulic pressure to remove air pockets.',
        'controlled_curing' => 'Controlled Curing',
        'curing_desc' => 'Slabs cure for 18–36 hours in temperature-controlled chambers for maximum hardness.',
        'qa_finishing' => 'QA & Finishing',
        'qa_desc' => 'Each piece undergoes quality inspection, polishing, and edge finishing before packaging.',
        'read_detailed_process' => 'Read Detailed Process',
        
        // Products
        'our_product_catalog' => 'Our Product Catalog',
        'view_details' => 'View Details',
        'no_products' => 'No catalog items found. Contact sales for availability.',
        'view_all_products' => 'View All Products & Filters',
        'standard_factory_size' => 'Standard Factory Size',
        
        // Shop
        'browse_products' => 'Browse Available Products',
        'search' => 'Search',
        'search_placeholder' => 'Search products by name...',
        'max_price' => 'Max Price (per unit)',
        'apply_filters' => 'Apply Filters',
        'clear' => 'Clear',
        'found' => 'Found',
        'product_word' => 'product',
        'products_word' => 'products',
        'in_stock' => 'In Stock',
        'out_of_stock' => 'Out of Stock',
        'no_filter_results' => 'No products match your filters. Try adjusting your search.',
        'per_unit' => '/ unit',
        'standard_size' => 'Standard Size',
        
        // Product Single
        'product_not_found' => 'Product Not Found',
        'product_not_found_desc' => "The product you're looking for doesn't exist or has been removed.",
        'back_to_shop' => 'Back to Shop',
        'added_to_cart' => 'added to your cart!',
        'view_cart' => 'View Cart',
        'measurement' => 'Measurement',
        'warehouse' => 'Warehouse',
        'main_warehouse' => 'Main Warehouse',
        'stock_status' => 'Stock Status',
        'available' => 'available',
        'quantity' => 'Quantity',
        'custom_description' => 'Custom Description / Project Notes',
        'custom_desc_placeholder' => 'e.g. Cut to 40×40cm panels, polished finish, for hotel lobby project...',
        'add_to_cart' => 'Add to Cart',
        'sign_up_to_order' => 'Sign Up to Order',
        'continue_shopping' => 'Continue Shopping',
        
        // Quote Modal
        'request_quote' => 'Request a Quote',
        'quote_desc' => 'Our sales team will get back to you with pricing and lead times for:',
        'company_name' => 'Company Name',
        'contact_person' => 'Contact Person',
        'email_address' => 'Email Address',
        'phone_number' => 'Phone Number',
        'preferred_payment' => 'Preferred Payment Method',
        'bank_transfer' => 'Bank Transfer',
        'credit_card' => 'Credit Card',
        'cash_on_delivery' => 'Cash on Delivery',
        'letter_of_credit' => 'Letter of Credit (L/C)',
        'project_details' => 'Project Details / Quantity Required',
        'project_placeholder' => 'Tell us about your project, required quantities, and delivery timeline...',
        'submit_inquiry' => 'Submit Inquiry',
        
        // Footer
        'footer_location' => 'Jordan · Middle East · International Shipping Available',
        'powered_by' => 'Powered by AURA ERP',
        
        // ERP
        'language' => 'Language',
        'settings' => 'Settings',
    ],
    'ar' => [
        // Storefront Nav
        'home' => 'الرئيسية',
        'shop' => 'المتجر',
        'our_process' => 'طريقة التصنيع',
        'careers' => 'الوظائف',
        'cart' => 'السلة',
        'login' => 'تسجيل الدخول',
        'log_in' => 'تسجيل الدخول',
        'logout' => 'تسجيل الخروج',
        'user_profile' => 'الملف الشخصي',
        'change_password' => 'تغيير كلمة المرور',
        'customer_account' => 'حساب العميل',
        
        // Hero
        'hero_arabic_name' => 'مسك للحجر الصناعي والديكور',
        'hero_title' => 'حجر صناعي فاخر<br>وحلول ديكور متكاملة',
        'hero_desc' => 'صُنع في الأردن. مُصمم ليدوم. تُصنّع مسك الحجر الصناعي ألواح الرخام والجرانيت الصناعي واللوحات الزخرفية باستخدام تقنيات الضغط الاهتزازي والمعالجة المتقدمة لمشاريع البناء والتصميم الداخلي في الشرق الأوسط.',
        'explore_catalog' => 'تصفح المنتجات',
        'learn_more' => 'اعرف أكثر',
        
        // About
        'about_miskstone' => 'عن مسك للحجر',
        'advanced_manufacturing' => 'تصنيع متقدم',
        'advanced_manufacturing_desc' => 'يستخدم مصنعنا تقنية الضغط الاهتزازي وبيئات المعالجة المُتحكم بها لإنتاج حجر صناعي ينافس الرخام والجرانيت الطبيعي في المتانة والجمال، بجزء بسيط من التكلفة.',
        'engineered_formulas' => 'تركيبات مهندسة',
        'engineered_formulas_desc' => 'يستخدم كل خط إنتاج مزيجاً خاصاً من الإسمنت الأبيض/الرمادي وحبيبات الكوارتز ورقائق الرخام وأصباغ أكسيد الحديد وراتنج البوليستر — مُعاير بدقة لثبات اللون والمتانة الهيكلية ومقاومة الطقس.',
        'regional_leader' => 'رائد إقليمي',
        'regional_leader_desc' => 'تخدم مسك، ومقرها الأردن، العملاء في جميع أنحاء الشرق الأوسط — من الفلل السكنية في عمّان إلى المشاريع التجارية الكبرى في الخليج. نجمع بين الحرفية المحلية والمعايير الدولية.',
        
        // Manufacturing Process
        'our_manufacturing_process' => 'مراحل التصنيع',
        'raw_material_mixing' => 'خلط المواد الخام',
        'raw_material_desc' => 'يتم قياس الإسمنت والحبيبات والأصباغ وفق المواصفات وخلطها بشكل موحد.',
        'vibro_compression' => 'الضغط الاهتزازي',
        'vibro_desc' => 'يُصب الخليط في القوالب ويتعرض للاهتزاز والضغط الهيدروليكي لإزالة فقاعات الهواء.',
        'controlled_curing' => 'المعالجة المتحكم بها',
        'curing_desc' => 'تُعالج الألواح لمدة 18-36 ساعة في غرف مُتحكم بدرجة حرارتها لأقصى صلابة.',
        'qa_finishing' => 'الجودة والتشطيب',
        'qa_desc' => 'تخضع كل قطعة لفحص الجودة والتلميع وتشطيب الحواف قبل التعبئة.',
        'read_detailed_process' => 'اقرأ التفاصيل الكاملة',
        
        // Products
        'our_product_catalog' => 'كتالوج المنتجات',
        'view_details' => 'عرض التفاصيل',
        'no_products' => 'لا توجد منتجات. تواصل مع فريق المبيعات.',
        'view_all_products' => 'عرض جميع المنتجات والفلاتر',
        'standard_factory_size' => 'حجم المصنع القياسي',
        
        // Shop
        'browse_products' => 'تصفح المنتجات المتاحة',
        'search' => 'بحث',
        'search_placeholder' => 'ابحث عن المنتجات بالاسم...',
        'max_price' => 'أقصى سعر (للوحدة)',
        'apply_filters' => 'تطبيق الفلاتر',
        'clear' => 'مسح',
        'found' => 'وجدنا',
        'product_word' => 'منتج',
        'products_word' => 'منتجات',
        'in_stock' => 'متوفر',
        'out_of_stock' => 'غير متوفر',
        'no_filter_results' => 'لا توجد منتجات مطابقة. حاول تعديل البحث.',
        'per_unit' => '/ وحدة',
        'standard_size' => 'حجم قياسي',
        
        // Product Single
        'product_not_found' => 'المنتج غير موجود',
        'product_not_found_desc' => 'المنتج الذي تبحث عنه غير موجود أو تمت إزالته.',
        'back_to_shop' => 'العودة للمتجر',
        'added_to_cart' => 'تمت الإضافة إلى السلة!',
        'view_cart' => 'عرض السلة',
        'measurement' => 'القياس',
        'warehouse' => 'المستودع',
        'main_warehouse' => 'المستودع الرئيسي',
        'stock_status' => 'حالة المخزون',
        'available' => 'متوفر',
        'quantity' => 'الكمية',
        'custom_description' => 'وصف مخصص / ملاحظات المشروع',
        'custom_desc_placeholder' => 'مثال: قص إلى ألواح 40×40 سم، تشطيب مصقول، لمشروع لوبي فندق...',
        'add_to_cart' => 'أضف إلى السلة',
        'sign_up_to_order' => 'سجل للطلب',
        'continue_shopping' => 'متابعة التسوق',
        
        // Quote Modal
        'request_quote' => 'طلب عرض سعر',
        'quote_desc' => 'سيتواصل معك فريق المبيعات بالأسعار ومواعيد التسليم لـ:',
        'company_name' => 'اسم الشركة',
        'contact_person' => 'اسم الشخص',
        'email_address' => 'البريد الإلكتروني',
        'phone_number' => 'رقم الهاتف',
        'preferred_payment' => 'طريقة الدفع المفضلة',
        'bank_transfer' => 'تحويل بنكي',
        'credit_card' => 'بطاقة ائتمان',
        'cash_on_delivery' => 'الدفع عند الاستلام',
        'letter_of_credit' => 'اعتماد مستندي',
        'project_details' => 'تفاصيل المشروع / الكمية المطلوبة',
        'project_placeholder' => 'أخبرنا عن مشروعك والكميات المطلوبة ومواعيد التسليم...',
        'submit_inquiry' => 'إرسال الاستفسار',
        
        // Footer
        'footer_location' => 'الأردن · الشرق الأوسط · شحن دولي متاح',
        'powered_by' => 'مدعوم بنظام AURA ERP',
        
        // ERP
        'language' => 'اللغة',
        'settings' => 'الإعدادات',
    ],
];

/**
 * Translate helper — returns the string for the current language, or fallback to English, or key itself.
 */
function t($key) {
    global $translations, $currentLang;
    return $translations[$currentLang][$key] ?? $translations['en'][$key] ?? $key;
}
