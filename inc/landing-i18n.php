<?php
/**
 * Packaged EN / RU / AR overlays for landing options.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Landing overlay for one language.
 *
 * @param string $lang en|ru|ar.
 * @return array
 */
function liferuss_landing_i18n( $lang ) {
	$all = liferuss_landing_i18n_all();
	return isset( $all[ $lang ] ) ? $all[ $lang ] : array();
}

/**
 * All landing translations.
 *
 * @return array<string, array>
 */
function liferuss_landing_i18n_all() {
	return array(
		'en' => array(
			'home_landings_eyebrow'  => 'More ways to work with us',
			'home_landings_title'    => 'Freight and trade between Iran and Russia',
			'home_landings_subtitle' => 'Alongside study advice, we also help with cargo shipping and product sourcing between Iran and Russia.',
			'home_landings'          => array(
				array( 'title' => 'Freight, cargo, and shipping', 'text' => 'Commercial cargo, product samples, personal belongings, and student documents between Iran and Russia.', 'cta' => 'Request a shipment' ),
				array( 'title' => 'Trade, supply, and sourcing', 'text' => 'Buying from Russia, exporting Iranian goods, and dedicated sourcing for traders.', 'cta' => 'Request sourcing' ),
			),
			'freight_hero_eyebrow'   => 'Iran ↔ Russia · Cargo & Delivery',
			'freight_hero_headline'  => 'Freight, cargo, and shipping between Iran and Russia',
			'freight_hero_accent'    => 'Iran and Russia',
			'freight_hero_lead'      => 'LifeRuss accompanies commercial cargo, samples, personal items, and student documents from the request form through to delivery.',
			'freight_hero_cta_text'  => 'Submit a shipping request',
			'freight_trust'          => array(
				array( 'title' => 'End-to-end support', 'text' => 'From request to delivery' ),
				array( 'title' => 'Full coordination', 'text' => 'One team in Iran and Russia' ),
				array( 'title' => 'Logistics network', 'text' => 'Air, land, and cargo' ),
				array( 'title' => 'Safe shipping', 'text' => 'Tracking and status updates' ),
			),
			'freight_services'       => array(
				array( 'title' => 'Specialist cargo and freight', 'text' => 'Commercial shipments, bulky cargo, and FCL / LCL with air, land, and combined routes.' ),
				array( 'title' => 'Sample shipments for traders', 'text' => 'Send commercial samples and catalogues for exhibitions and negotiations.' ),
				array( 'title' => 'Personal belongings', 'text' => 'Household goods and personal items for students and relocating families.' ),
				array( 'title' => 'Student documents and parcels', 'text' => 'Secure shipping of diplomas, transcripts, and light parcels.' ),
			),
			'freight_process_title'    => 'How we work',
			'freight_process_subtitle' => 'From the first request through to delivery at destination.',
			'freight_process'          => array(
				array( 'num' => '1', 'title' => 'Submit a request', 'text' => 'Complete the form and share cargo details.' ),
				array( 'num' => '2', 'title' => 'Review the cargo', 'text' => 'Check volume, weight, and customs limits.' ),
				array( 'num' => '3', 'title' => 'Route and cost', 'text' => 'We propose a method, timeline, and estimate.' ),
				array( 'num' => '4', 'title' => 'Handover and shipping', 'text' => 'Packing, pickup, and tracking.' ),
				array( 'num' => '5', 'title' => 'Delivery', 'text' => 'Safe handover in the destination city.' ),
			),
			'freight_form_title'       => 'Shipping request form',
			'freight_form_intro'       => 'Send cargo details so we can outline the route, timing, and an estimate.',
			'freight_form_banner_title'=> 'A reliable Iran–Russia link',
			'freight_form_name_label'  => 'Full name',
			'freight_form_phone_label' => 'Phone / WhatsApp',
			'freight_form_origin_label'=> 'Origin (city / country)',
			'freight_form_dest_label'  => 'Destination (city / country)',
			'freight_form_type_label'  => 'Cargo type',
			'freight_form_submit'      => 'Send request',
			'freight_form_success'     => 'Your shipping request is in. LifeRuss advisers will contact you shortly.',
			'freight_items_title'      => 'Common shipments',
			'freight_items_subtitle'   => 'Examples of items often sent between Iran and Russia.',
			'freight_items'            => array(
				array( 'title' => 'Documents' ),
				array( 'title' => 'Personal items' ),
				array( 'title' => 'Product samples' ),
				array( 'title' => 'Student parcels' ),
				array( 'title' => 'Cosmetics' ),
				array( 'title' => 'Parts and goods' ),
				array( 'title' => 'Commercial cargo' ),
			),
			'freight_items_notice'     => 'If your goods are not listed or may be restricted, coordinate with LifeRuss before shipping. Some items need customs permits.',
			'freight_cta_title'        => 'Ready to ship?',
			'freight_cta_text'         => 'We will outline the route, timing, and an estimate clearly.',
			'freight_cta1_text'        => 'Request a shipment',
			'freight_cta2_text'        => 'Talk to LifeRuss cargo',
			'freight_seo_title'        => 'Freight and shipping Iran–Russia | LifeRuss',
			'freight_seo_description'  => 'Cargo, samples, personal items, and student documents between Iran and Russia with LifeRuss.',
			'trade_hero_eyebrow'       => 'Iran ↔ Russia · Trade & Sourcing',
			'trade_hero_headline'      => 'Trade, supply, and sourcing between Iran and Russia',
			'trade_hero_accent'        => 'Iran and Russia',
			'trade_hero_lead'          => 'LifeRuss supports international trade, buying from Russia, exporting Iranian goods, and dedicated sourcing for traders.',
			'trade_hero_cta_text'      => 'Submit a sourcing request',
			'trade_trust'              => array(
				array( 'title' => 'Reliable supply', 'text' => 'A reviewed supplier network' ),
				array( 'title' => 'Dedicated solutions', 'text' => 'Buy, export, and source' ),
				array( 'title' => 'International network', 'text' => 'Iran, Russia, and third routes' ),
				array( 'title' => 'Steady trade growth', 'text' => 'From sample to contract' ),
			),
			'trade_services'           => array(
				array( 'title' => 'Dedicated sourcing for companies', 'text' => 'Find a supplier that matches volume, destination, and specifications.' ),
				array( 'title' => 'International supply', 'text' => 'Food, spices, grains, nuts, and industrial goods from a regional network.' ),
				array( 'title' => 'Buying from Russia', 'text' => 'Cosmetics, raw materials, and specialist Russian goods.' ),
				array( 'title' => 'Exporting Iranian products', 'text' => 'Pistachios, dates, food, and exportable Iranian goods to Russia.' ),
			),
			'trade_process_title'      => 'How we work',
			'trade_process_subtitle'   => 'A clear path to sourcing goods',
			'trade_process'            => array(
				array( 'num' => '1', 'title' => 'Submit a request', 'text' => 'Share the product and target market.' ),
				array( 'num' => '2', 'title' => 'Review the need', 'text' => 'We analyse specifications and volume.' ),
				array( 'num' => '3', 'title' => 'Find a supplier', 'text' => 'Search a trusted international network.' ),
				array( 'num' => '4', 'title' => 'Price and terms', 'text' => 'Quote, timing, and delivery terms.' ),
				array( 'num' => '5', 'title' => 'Buy and trade', 'text' => 'Contract, purchase, and shipping path.' ),
			),
			'trade_form_title'         => 'Sourcing request form',
			'trade_form_intro'         => 'Send product and market details so we can outline a sourcing path.',
			'trade_form_banner_title'  => 'Borderless trade via Iran and Russia',
			'trade_form_name_label'    => 'Full name',
			'trade_form_phone_label'   => 'Phone / WhatsApp',
			'trade_form_product_label' => 'Product name',
			'trade_form_category_label'=> 'Product category',
			'trade_form_submit'        => 'Send request',
			'trade_form_success'       => 'Your sourcing request is in. LifeRuss advisers will contact you shortly.',
			'trade_cats_title'         => 'Product categories',
			'trade_cats_subtitle'      => 'Some of the groups LifeRuss can help source.',
			'trade_cats'               => array(
				array( 'title' => 'Nuts and dried fruit' ),
				array( 'title' => 'Dates' ),
				array( 'title' => 'Food' ),
				array( 'title' => 'Spices' ),
				array( 'title' => 'Grains' ),
				array( 'title' => 'Cosmetics and hygiene' ),
				array( 'title' => 'Russian goods' ),
			),
			'trade_cats_notice'        => 'If your product is not listed, send the specifications so we can check sourcing options.',
			'trade_cta_title'          => 'Ready to start trading?',
			'trade_cta_text'           => 'From sourcing to contract and shipping, one team stays with you.',
			'trade_cta1_text'          => 'Request sourcing',
			'trade_cta2_text'          => 'Talk to LifeRuss trade',
			'trade_seo_title'          => 'Trade and sourcing Iran–Russia | LifeRuss',
			'trade_seo_description'    => 'Sourcing, buying from Russia, and exporting Iranian goods with LifeRuss.',
		),
		'ru' => array(
			'home_landings_eyebrow'  => 'Другие направления сотрудничества',
			'home_landings_title'    => 'Грузы и торговля между Ираном и Россией',
			'home_landings_subtitle' => 'Помимо учёбы помогаем с доставкой грузов и поиском товаров между Ираном и Россией.',
			'home_landings'          => array(
				array( 'title' => 'Грузы, карго и доставка', 'text' => 'Коммерческие грузы, образцы, личные вещи и студенческие документы.', 'cta' => 'Заявка на отправку' ),
				array( 'title' => 'Торговля и сорсинг', 'text' => 'Закупки в России, экспорт иранских товаров и поиск поставщиков.', 'cta' => 'Заявка на поставку' ),
			),
			'freight_hero_headline'  => 'Грузы, карго и доставка между Ираном и Россией',
			'freight_hero_accent'    => 'Ираном и Россией',
			'freight_hero_lead'      => 'LifeRuss сопровождает коммерческие грузы, образцы, личные вещи и документы от заявки до вручения.',
			'freight_hero_cta_text'  => 'Оставить заявку на отправку',
			'freight_services'       => array(
				array( 'title' => 'Специализированное карго', 'text' => 'Коммерческие и крупногабаритные грузы, FCL / LCL, авиа и авто.' ),
				array( 'title' => 'Образцы для бизнеса', 'text' => 'Отправка образцов и каталогов для выставок и переговоров.' ),
				array( 'title' => 'Личные вещи', 'text' => 'Переезд студентов и семей с надёжной упаковкой.' ),
				array( 'title' => 'Студенческие документы', 'text' => 'Безопасная доставка дипломов, оценок и лёгких посылок.' ),
			),
			'freight_process_title'  => 'Этапы работы',
			'freight_process'        => array(
				array( 'num' => '1', 'title' => 'Заявка', 'text' => 'Форма и данные о грузе.' ),
				array( 'num' => '2', 'title' => 'Проверка груза', 'text' => 'Объём, вес и таможенные ограничения.' ),
				array( 'num' => '3', 'title' => 'Маршрут и цена', 'text' => 'Способ, сроки и ориентир стоимости.' ),
				array( 'num' => '4', 'title' => 'Отправка', 'text' => 'Упаковка, сдача и отслеживание.' ),
				array( 'num' => '5', 'title' => 'Вручение', 'text' => 'Безопасная доставка в городе назначения.' ),
			),
			'freight_form_title'     => 'Форма заявки на отправку',
			'freight_form_submit'    => 'Отправить заявку',
			'freight_items_title'    => 'Что обычно отправляют',
			'freight_cta_title'      => 'Готовы отправить груз?',
			'freight_cta1_text'      => 'Заявка на отправку',
			'freight_seo_title'      => 'Грузы Иран–Россия | LifeRuss',
			'trade_hero_headline'    => 'Торговля, поставки и сорсинг между Ираном и Россией',
			'trade_hero_accent'      => 'Ираном и Россией',
			'trade_hero_lead'        => 'LifeRuss помогает с международной торговлей, закупками в России, экспортом иранских товаров и поиском поставщиков.',
			'trade_hero_cta_text'    => 'Оставить заявку на поставку',
			'trade_services'         => array(
				array( 'title' => 'Сорсинг для компаний', 'text' => 'Ищем поставщика под объём, направление и техзадание.' ),
				array( 'title' => 'Международные поставки', 'text' => 'Продукты, специи, зерно, орехи и промышленные товары.' ),
				array( 'title' => 'Закупки в России', 'text' => 'Косметика, сырьё и специализированные российские товары.' ),
				array( 'title' => 'Экспорт из Ирана', 'text' => 'Фисташки, финики, продукты питания и экспортные товары в Россию.' ),
			),
			'trade_process_title'    => 'Этапы работы',
			'trade_process'          => array(
				array( 'num' => '1', 'title' => 'Заявка', 'text' => 'Товар и целевой рынок.' ),
				array( 'num' => '2', 'title' => 'Разбор задачи', 'text' => 'Анализ объёма и характеристик.' ),
				array( 'num' => '3', 'title' => 'Поиск поставщика', 'text' => 'Проверенная международная сеть.' ),
				array( 'num' => '4', 'title' => 'Цена и условия', 'text' => 'Коммерческое предложение и сроки.' ),
				array( 'num' => '5', 'title' => 'Сделка', 'text' => 'Договор, закупка и логистика.' ),
			),
			'trade_form_title'       => 'Форма заявки на поставку',
			'trade_form_submit'      => 'Отправить заявку',
			'trade_cats_title'       => 'Категории товаров',
			'trade_cta_title'        => 'Готовы начать торговлю?',
			'trade_cta1_text'        => 'Заявка на поставку',
			'trade_seo_title'        => 'Торговля Иран–Россия | LifeRuss',
		),
		'ar' => array(
			'home_landings_eyebrow'  => 'مسارات إضافية للتعاون',
			'home_landings_title'    => 'الشحن والتجارة بين إيران وروسيا',
			'home_landings_subtitle' => 'إلى جانب استشارات الدراسة نرافق شحن البضائع وتوريد السلع بين إيران وروسيا.',
			'home_landings'          => array(
				array( 'title' => 'الشحن والكارغو', 'text' => 'بضائع تجارية وعيّنات وأغراض شخصية ووثائق طلابية بين إيران وروسيا.', 'cta' => 'طلب شحن' ),
				array( 'title' => 'التجارة والتوريد', 'text' => 'الشراء من روسيا وتصدير منتجات إيران والتوريد المخصص للتجار.', 'cta' => 'طلب توريد' ),
			),
			'freight_hero_headline'  => 'الشحن والكارغو بين إيران وروسيا',
			'freight_hero_accent'    => 'إيران وروسيا',
			'freight_hero_lead'      => 'ترافق لایف روس الشحنات التجارية والعيّنات والأغراض الشخصية ووثائق الطلاب من الطلب حتى التسليم.',
			'freight_hero_cta_text'  => 'تسجيل طلب الشحن',
			'freight_services'       => array(
				array( 'title' => 'كارغو وشحن متخصص', 'text' => 'شحنات تجارية وأحمال كبيرة وFCL / LCL جوياً وبرياً.' ),
				array( 'title' => 'إرسال عيّنات للتجار', 'text' => 'إرسال عيّنات وكتالوجات للمعارض والمفاوضات.' ),
				array( 'title' => 'الأغراض الشخصية', 'text' => 'أثاث وأغراض الطلاب والعائلات مع تغليف آمن.' ),
				array( 'title' => 'وثائق وطرود طلابية', 'text' => 'إرسال آمن للشهادات وكشوف الدرجات والطرود الخفيفة.' ),
			),
			'freight_process_title'  => 'مراحل التعاون',
			'freight_process'        => array(
				array( 'num' => '١', 'title' => 'تسجيل الطلب', 'text' => 'تعبئة النموذج وبيانات الشحنة.' ),
				array( 'num' => '٢', 'title' => 'مراجعة نوع البضاعة', 'text' => 'الحجم والوزن وقيود الجمارك.' ),
				array( 'num' => '٣', 'title' => 'المسار والتكلفة', 'text' => 'اقتراح الطريقة والوقت والتقدير.' ),
				array( 'num' => '٤', 'title' => 'التسليم والشحن', 'text' => 'التغليف والاستلام والتتبع.' ),
				array( 'num' => '٥', 'title' => 'التسليم في الوجهة', 'text' => 'تسليم آمن في مدينة الوصول.' ),
			),
			'freight_form_title'     => 'نموذج طلب الشحن',
			'freight_form_submit'    => 'إرسال الطلب',
			'freight_items_title'    => 'شحنات شائعة',
			'freight_cta_title'      => 'هل أنتم جاهزون للشحن؟',
			'freight_cta1_text'      => 'طلب شحن',
			'freight_seo_title'      => 'الشحن بين إيران وروسيا | لایف روس',
			'trade_hero_headline'    => 'التجارة والتوريد بين إيران وروسيا',
			'trade_hero_accent'      => 'إيران وروسيا',
			'trade_hero_lead'        => 'ترافق لایف روس التجارة الدولية والشراء من روسيا وتصدير منتجات إيران والتوريد المخصص.',
			'trade_hero_cta_text'    => 'تسجيل طلب التوريد',
			'trade_services'         => array(
				array( 'title' => 'توريد مخصص للشركات', 'text' => 'إيجاد مورّد يناسب الحجم والوجهة والمواصفات.' ),
				array( 'title' => 'توريد دولي', 'text' => 'أغذية وتوابل وحبوب ومكسرات وسلع صناعية.' ),
				array( 'title' => 'الشراء من روسيا', 'text' => 'مستحضرات ومواد أولية وسلع روسية متخصصة.' ),
				array( 'title' => 'تصدير منتجات إيران', 'text' => 'فستق وتمر وأغذية ومنتجات قابلة للتصدير إلى روسيا.' ),
			),
			'trade_process_title'    => 'مراحل التعاون',
			'trade_process'          => array(
				array( 'num' => '١', 'title' => 'تسجيل الطلب', 'text' => 'بيانات السلعة والسوق المستهدف.' ),
				array( 'num' => '٢', 'title' => 'مراجعة الاحتياج', 'text' => 'تحليل المواصفات والحجم.' ),
				array( 'num' => '٣', 'title' => 'إيجاد المورّد', 'text' => 'البحث في شبكة موثوقة.' ),
				array( 'num' => '٤', 'title' => 'السعر والشروط', 'text' => 'عرض السعر ووقت التسليم.' ),
				array( 'num' => '٥', 'title' => 'التنسيق والشراء', 'text' => 'العقد والشراء ومسار الشحن.' ),
			),
			'trade_form_title'       => 'نموذج طلب التوريد',
			'trade_form_submit'      => 'إرسال الطلب',
			'trade_cats_title'       => 'فئات المنتجات',
			'trade_cta_title'        => 'هل أنتم جاهزون للتجارة؟',
			'trade_cta1_text'        => 'طلب توريد',
			'trade_seo_title'        => 'التجارة بين إيران وروسيا | لایف روس',
		),
	);
}
